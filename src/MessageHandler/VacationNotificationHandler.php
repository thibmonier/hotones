<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\VacationNotificationMessage;
use App\Repository\VacationRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class VacationNotificationHandler
{
    public function __construct(
        private readonly VacationRepository $vacationRepository,
        private readonly MailerInterface $mailer
    ) {
    }

    public function __invoke(VacationNotificationMessage $message): void
    {
        $vacation = $this->vacationRepository->find($message->getVacationId());

        if (!$vacation) {
            return; // La demande de congé n'existe plus
        }

        $contributor = $vacation->getContributor();
        $manager     = $contributor->getManager();

        match ($message->getType()) {
            'created'  => $this->sendCreatedNotification($vacation, $manager),
            'approved' => $this->sendApprovedNotification($vacation, $contributor),
            'rejected' => $this->sendRejectedNotification($vacation, $contributor),
            default    => null,
        };
    }

    private function sendCreatedNotification($vacation, $manager): void
    {
        if (!$manager || !$manager->getEmail()) {
            return; // Pas de manager ou pas d'email
        }

        $email = (new TemplatedEmail())
            ->to($manager->getEmail())
            ->subject('Nouvelle demande de congé à valider')
            ->htmlTemplate('emails/vacation_created.html.twig')
            ->context([
                'vacation'    => $vacation,
                'contributor' => $vacation->getContributor(),
                'manager'     => $manager,
            ]);

        $this->mailer->send($email);
    }

    private function sendApprovedNotification($vacation, $contributor): void
    {
        if (!$contributor->getEmail()) {
            return; // Pas d'email
        }

        $email = (new TemplatedEmail())
            ->to($contributor->getEmail())
            ->subject('Votre demande de congé a été approuvée')
            ->htmlTemplate('emails/vacation_approved.html.twig')
            ->context([
                'vacation'    => $vacation,
                'contributor' => $contributor,
            ]);

        $this->mailer->send($email);
    }

    private function sendRejectedNotification($vacation, $contributor): void
    {
        if (!$contributor->getEmail()) {
            return; // Pas d'email
        }

        $email = (new TemplatedEmail())
            ->to($contributor->getEmail())
            ->subject('Votre demande de congé a été rejetée')
            ->htmlTemplate('emails/vacation_rejected.html.twig')
            ->context([
                'vacation'    => $vacation,
                'contributor' => $contributor,
            ]);

        $this->mailer->send($email);
    }
}
