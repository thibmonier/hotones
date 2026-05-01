<?php

declare(strict_types=1);

namespace App\Infrastructure\Vacation\Notification;

use App\Application\Vacation\Notification\Message\VacationNotificationMessage;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationId;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class VacationNotificationHandler
{
    public function __construct(
        private VacationRepositoryInterface $vacationRepository,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(VacationNotificationMessage $message): void
    {
        $vacation = $this->vacationRepository->findByIdOrNull(
            VacationId::fromString($message->getVacationId()),
        );

        if ($vacation === null) {
            return;
        }

        $contributor = $vacation->getContributor();
        $manager = $contributor->getManager();

        match ($message->getType()) {
            'created' => $this->sendCreatedNotification($vacation, $manager),
            'approved' => $this->sendApprovedNotification($vacation, $contributor),
            'rejected' => $this->sendRejectedNotification($vacation, $contributor),
            'cancelled' => $this->sendCancelledByContributorNotification($vacation, $manager),
            'cancelled-by-manager' => $this->sendCancelledByManagerNotification($vacation, $contributor),
            default => null,
        };
    }

    private function sendCreatedNotification(mixed $vacation, mixed $manager): void
    {
        if (!$manager || !$manager->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->to($manager->getEmail())
            ->subject('Nouvelle demande de conge a valider')
            ->htmlTemplate('emails/vacation_created.html.twig')
            ->context([
                'vacation' => $vacation,
                'contributor' => $vacation->getContributor(),
                'manager' => $manager,
            ]);

        $this->mailer->send($email);
    }

    private function sendApprovedNotification(mixed $vacation, mixed $contributor): void
    {
        if (!$contributor->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->to($contributor->getEmail())
            ->subject('Votre demande de conge a ete approuvee')
            ->htmlTemplate('emails/vacation_approved.html.twig')
            ->context([
                'vacation' => $vacation,
                'contributor' => $contributor,
            ]);

        $this->mailer->send($email);
    }

    private function sendRejectedNotification(mixed $vacation, mixed $contributor): void
    {
        if (!$contributor->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->to($contributor->getEmail())
            ->subject('Votre demande de conge a ete rejetee')
            ->htmlTemplate('emails/vacation_rejected.html.twig')
            ->context([
                'vacation' => $vacation,
                'contributor' => $contributor,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Intervenant annule sa propre demande PENDING -> on tient le manager au courant.
     */
    private function sendCancelledByContributorNotification(mixed $vacation, mixed $manager): void
    {
        if (!$manager || !$manager->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->to($manager->getEmail())
            ->subject('Demande de conge annulee par le collaborateur')
            ->htmlTemplate('emails/vacation_cancelled.html.twig')
            ->context([
                'vacation' => $vacation,
                'contributor' => $vacation->getContributor(),
                'manager' => $manager,
            ]);

        $this->mailer->send($email);
    }

    /**
     * TECH-DEBT-001 — Manager annule (US-069 PENDING ou APPROVED) -> intervenant prevenu.
     */
    private function sendCancelledByManagerNotification(mixed $vacation, mixed $contributor): void
    {
        if (!$contributor->getEmail()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->to($contributor->getEmail())
            ->subject('Votre demande de conge a ete annulee par votre manager')
            ->htmlTemplate('emails/vacation_cancelled_by_manager.html.twig')
            ->context([
                'vacation' => $vacation,
                'contributor' => $contributor,
            ]);

        $this->mailer->send($email);
    }
}
