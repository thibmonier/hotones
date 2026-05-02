<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Application\Vacation\Command\RequestVacation\RequestVacationCommand;
use App\Application\Vacation\Command\RequestVacation\RequestVacationHandler;
use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Entity\Contributor;
use App\Entity\User;
use DateTimeImmutable;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Factor the boilerplate that every Vacation functional test was duplicating
 * (REFACTOR-001, sprint-004).
 *
 * Depends on MultiTenantTestTrait being also used (provides $this->testCompany
 * and $this->getEntityManager()).
 *
 * Helpers:
 *   - provisionVacationContributor(email, first, last, roles, manager?)
 *   - loginAs(user)
 *   - generateCsrfToken(id)
 *   - createPendingVacationFor(contributor, reason?, daysFromNow?)
 *   - findMailerMessageWithSubject(subject)
 */
trait VacationFunctionalTrait
{
    /**
     * @param array<string> $roles
     */
    protected function provisionVacationContributor(
        string $email,
        string $firstName,
        string $lastName,
        array $roles,
        ?Contributor $manager = null,
    ): Contributor {
        $em = $this->getEntityManager();

        $user = new User();
        $user->setCompany($this->testCompany);
        $user->setEmail($email);
        $user->setPassword('password');
        $user->firstName = $firstName;
        $user->lastName = $lastName;
        $user->setRoles($roles);
        $em->persist($user);

        $contributor = new Contributor();
        $contributor->setCompany($this->testCompany);
        $contributor->setUser($user);
        $contributor->setFirstName($firstName);
        $contributor->setLastName($lastName);
        $contributor->setActive(true);
        // Contributor has its own `email` field separate from the User's
        // `email`, and the email-sending pipeline reads from
        // Contributor::getEmail() (not User). Set it explicitly so the
        // notification handler can deliver.
        $contributor->email = $email;
        if ($manager !== null) {
            $contributor->setManager($manager);
            // Sync the inverse side — Doctrine doesn't auto-update the
            // manager's `managedContributors` Collection when only the owning
            // side is set. Without this, a subsequent
            // `findOneBy(...)->getManagedContributors()` on the manager would
            // return an empty Collection from the identity-map cached instance
            // and break controller-side authorization checks.
            $manager->addManagedContributor($contributor);
        }
        $em->persist($contributor);
        $em->flush();

        return $contributor;
    }

    /**
     * Authenticate as the given User in the test KernelBrowser.
     *
     * Prefers `KernelBrowser::loginUser()` when the test exposes
     * `$this->client` (the only correct path for routes behind a session
     * firewall: session cookie is set, the firewall sees the user across
     * subsequent requests). Falls back to direct TokenStorage manipulation
     * for tests that don't use a browser (kernel-only).
     */
    protected function loginAs(User $user): void
    {
        if (property_exists($this, 'client') && $this->client !== null) {
            $this->client->loginUser($user);

            return;
        }

        $tokenStorage = static::getContainer()->get('security.token_storage');
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    }

    /**
     * Resolve a CSRF token from a form rendered by the application.
     *
     * The CsrfTokenManager binds tokens to the active session, which only
     * exists during a request lifecycle. After `$client->request()` returns,
     * the session is closed and `RequestStack::getCurrentRequest()` is null,
     * so a direct `tokenManager->getToken($id)` call throws
     * `SessionNotFoundException`.
     *
     * This helper renders the authenticated GET page for the given vacation,
     * extracts the `<input name="_token">` value of the form whose `action`
     * matches the route `$intent` is suffixed onto. The token returned is
     * therefore the *same* one the next POST will receive in the session.
     *
     * Falls back to direct token-manager access for tests that don't carry a
     * `$client` (kernel-only). On strict session firewalls, that path will
     * still throw — callers in that situation should use this method.
     *
     * @param string $intent  e.g. "approve", "reject", "cancel-manager", "cancel"
     * @param string $vacationId  UUID of the vacation
     * @param string $rendererPath  GET route that renders the form
     */
    protected function csrfTokenFromForm(
        string $intent,
        string $vacationId,
        string $rendererPath,
    ): string {
        $crawler = $this->client->request('GET', $rendererPath);

        $intentSuffix = $this->intentRouteSuffix($intent);
        $tokenInput = $crawler->filter("input[name='_token']")
            ->reduce(static function (\Symfony\Component\DomCrawler\Crawler $node) use ($intentSuffix): bool {
                // Only accept tokens whose surrounding form action targets the
                // expected intent: a vacation detail page renders multiple
                // forms (approve/reject/cancel) sharing the same DOM tree.
                $form = $node->ancestors()->filter('form')->first();
                if ($form->count() === 0) {
                    return false;
                }

                return str_contains((string) $form->attr('action'), $intentSuffix);
            });

        if ($tokenInput->count() === 0) {
            throw new \RuntimeException(sprintf(
                'Could not locate CSRF token for intent "%s" on %s',
                $intent,
                $rendererPath,
            ));
        }

        return (string) $tokenInput->first()->attr('value');
    }

    /**
     * Map a CSRF intent to the URL suffix the form's `action` ends with.
     */
    private function intentRouteSuffix(string $intent): string
    {
        return match ($intent) {
            'approve' => '/approuver',
            'reject' => '/rejeter',
            'cancel-manager', 'cancel' => '/annuler',
            default => '/' . $intent,
        };
    }

    /**
     * Legacy programmatic CSRF resolver — only works if the session is open.
     * Kept for tests that submit to routes whose form is on a separate page
     * (e.g. CSRF on `/mes-conges/{id}/annuler` rendered from `/mes-conges`).
     */
    protected function generateCsrfToken(string $id): string
    {
        return static::getContainer()->get('security.csrf.token_manager')->getToken($id)->getValue();
    }

    /**
     * Create a PENDING vacation request for the given contributor by invoking
     * the real RequestVacationHandler — same path as production user submits.
     *
     * @param int $startInDays  Offset (in days) from now for the start date
     * @param int $durationDays How many days the request spans
     */
    protected function createPendingVacationFor(
        Contributor $contributor,
        string $reason = 'Functional test pending',
        int $startInDays = 1,
        int $durationDays = 1,
    ): Vacation {
        /** @var RequestVacationHandler $handler */
        $handler = static::getContainer()->get(RequestVacationHandler::class);

        $vacationId = ($handler)(new RequestVacationCommand(
            contributorId: $contributor->getId(),
            startDate: new DateTimeImmutable(sprintf('+%d days', $startInDays)),
            endDate: new DateTimeImmutable(sprintf('+%d days', $startInDays + $durationDays)),
            type: 'conges_payes',
            dailyHours: '8',
            reason: $reason,
        ));

        // Bypass the repository (which applies a company-context filter)
        // because the test may create a vacation for a contributor in a
        // *different* company than the currently authenticated one. Use the
        // EntityManager directly so the filter is not applied.
        $em = $this->getEntityManager();
        $vacation = $em->find(Vacation::class, $vacationId);

        if ($vacation === null) {
            throw new \RuntimeException(sprintf(
                'Just-created Vacation %s could not be re-fetched',
                $vacationId->getValue(),
            ));
        }

        return $vacation;
    }

    /**
     * Return the most recent mailer message whose subject matches exactly,
     * or null if no such message has been captured by the in-memory transport.
     *
     * Useful for assertions that pivot on a specific email template
     * (vacation_created, vacation_cancelled_by_manager, ...).
     */
    protected function findMailerMessageWithSubject(string $subject): ?Email
    {
        foreach (self::getMailerMessages() as $message) {
            if ($message instanceof Email && $message->getSubject() === $subject) {
                return $message;
            }
        }

        return null;
    }
}
