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
        if ($manager !== null) {
            $contributor->setManager($manager);
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
     * Generate a CSRF token for a controller action.
     *
     * The CsrfTokenManager pulls/stores from the session, which only exists
     * once the KernelBrowser has issued at least one request. We warm-up
     * the session with a GET to /mes-conges (an authenticated route, no-op
     * for the action under test) before resolving the token, so the value
     * we hand back is valid for the *same* session the next POST will reuse.
     */
    /**
     * Generate a CSRF token for a controller action.
     *
     * Note: caller must ensure a session is active in the test container
     * (e.g. the test's KernelBrowser has already issued one request that
     * populated `RequestStack`). On strict session-based CSRF setups, this
     * may still fail with `SessionNotFoundException` — see the trait's
     * doc-block for the limitation.
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

        ($handler)(new RequestVacationCommand(
            contributorId: $contributor->getId(),
            startDate: new DateTimeImmutable(sprintf('+%d days', $startInDays)),
            endDate: new DateTimeImmutable(sprintf('+%d days', $startInDays + $durationDays)),
            type: 'conges_payes',
            dailyHours: '8',
            reason: $reason,
        ));

        /** @var VacationRepositoryInterface $repo */
        $repo = static::getContainer()->get(VacationRepositoryInterface::class);
        $vacations = $repo->findByContributor($contributor);

        return end($vacations);
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
