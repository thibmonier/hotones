<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AccountDeletionRequest;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service de gestion des emails RGPD.
 * Envoie les notifications pour le workflow de suppression de compte.
 */
class GdprEmailService
{
    private const string DPO_EMAIL  = 'dpo@hotones.example';
    private const string FROM_EMAIL = 'noreply@hotones.example';
    private const string FROM_NAME  = 'HotOnes - RGPD';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Envoie l'email de confirmation de demande de suppression.
     * Contient le lien de confirmation valide 48h.
     */
    public function sendDeletionRequestConfirmation(AccountDeletionRequest $deletionRequest): void
    {
        $user = $deletionRequest->getUser();

        $confirmationUrl = $this->urlGenerator->generate(
            'gdpr_confirm_deletion',
            ['token' => $deletionRequest->getConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $expiryDate = $deletionRequest->getRequestedAt()->modify('+48 hours');

        $email = new TemplatedEmail()
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('⚠️ Confirmation requise - Suppression de compte')
            ->htmlTemplate('emails/gdpr/deletion_request_confirmation.html.twig')
            ->textTemplate('emails/gdpr/deletion_request_confirmation.txt.twig')
            ->context([
                'user'            => $user,
                'confirmationUrl' => $confirmationUrl,
                'expiryDate'      => $expiryDate,
                'dpoEmail'        => self::DPO_EMAIL,
            ]);

        try {
            $this->mailer->send($email);

            $this->logger->info('GDPR deletion request confirmation email sent', [
                'user_id'     => $user->getId(),
                'email'       => $user->getEmail(),
                'request_id'  => $deletionRequest->getId(),
                'expiry_date' => $expiryDate->format('c'),
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to send GDPR deletion request confirmation email', [
                'user_id'    => $user->getId(),
                'request_id' => $deletionRequest->getId(),
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Envoie l'email de confirmation de suppression (début période de grâce 30j).
     */
    public function sendDeletionConfirmed(AccountDeletionRequest $deletionRequest): void
    {
        $user = $deletionRequest->getUser();

        $cancelUrl = $this->urlGenerator->generate('gdpr_my_data', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $confirmedDate = $deletionRequest->getConfirmedAt();
        $scheduledDate = $deletionRequest->getScheduledDeletionAt();
        $reminderDate  = $scheduledDate->modify('-7 days');

        $daysRemaining = new DateTimeImmutable()->diff($scheduledDate)->days;

        $email = new TemplatedEmail()
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('⏱️ Suppression confirmée - Période de grâce de 30 jours')
            ->htmlTemplate('emails/gdpr/deletion_confirmed.html.twig')
            ->textTemplate('emails/gdpr/deletion_confirmed.txt.twig')
            ->context([
                'user'                  => $user,
                'cancelUrl'             => $cancelUrl,
                'confirmedDate'         => $confirmedDate,
                'scheduledDeletionDate' => $scheduledDate,
                'reminderDate'          => $reminderDate,
                'daysRemaining'         => $daysRemaining,
                'dpoEmail'              => self::DPO_EMAIL,
            ]);

        try {
            $this->mailer->send($email);

            $this->logger->info('GDPR deletion confirmed email sent', [
                'user_id'       => $user->getId(),
                'email'         => $user->getEmail(),
                'request_id'    => $deletionRequest->getId(),
                'scheduled_for' => $scheduledDate->format('c'),
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to send GDPR deletion confirmed email', [
                'user_id'    => $user->getId(),
                'request_id' => $deletionRequest->getId(),
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Envoie l'email de rappel (7 jours avant suppression).
     */
    public function sendDeletionReminder(AccountDeletionRequest $deletionRequest): void
    {
        $user = $deletionRequest->getUser();

        $cancelUrl = $this->urlGenerator->generate('gdpr_my_data', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $scheduledDate = $deletionRequest->getScheduledDeletionAt();
        $daysRemaining = new DateTimeImmutable()->diff($scheduledDate)->days;

        $email = new TemplatedEmail()
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('⚠️ Dernière chance - Suppression dans '.$daysRemaining.' jours')
            ->htmlTemplate('emails/gdpr/deletion_reminder.html.twig')
            ->textTemplate('emails/gdpr/deletion_reminder.txt.twig')
            ->context([
                'user'                  => $user,
                'cancelUrl'             => $cancelUrl,
                'scheduledDeletionDate' => $scheduledDate,
                'daysRemaining'         => $daysRemaining,
                'dpoEmail'              => self::DPO_EMAIL,
            ]);

        try {
            $this->mailer->send($email);

            $this->logger->info('GDPR deletion reminder email sent', [
                'user_id'    => $user->getId(),
                'email'      => $user->getEmail(),
                'request_id' => $deletionRequest->getId(),
                'days_left'  => $daysRemaining,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to send GDPR deletion reminder email', [
                'user_id'    => $user->getId(),
                'request_id' => $deletionRequest->getId(),
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Envoie l'email de confirmation d'annulation de suppression.
     */
    public function sendDeletionCancelled(AccountDeletionRequest $deletionRequest): void
    {
        $user = $deletionRequest->getUser();

        $loginUrl = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $cancelledDate = $deletionRequest->getCancelledAt();

        $email = new TemplatedEmail()
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('✅ Suppression annulée - Votre compte est conservé')
            ->htmlTemplate('emails/gdpr/deletion_cancelled.html.twig')
            ->textTemplate('emails/gdpr/deletion_cancelled.txt.twig')
            ->context([
                'user'          => $user,
                'loginUrl'      => $loginUrl,
                'cancelledDate' => $cancelledDate,
                'dpoEmail'      => self::DPO_EMAIL,
            ]);

        try {
            $this->mailer->send($email);

            $this->logger->info('GDPR deletion cancelled email sent', [
                'user_id'    => $user->getId(),
                'email'      => $user->getEmail(),
                'request_id' => $deletionRequest->getId(),
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to send GDPR deletion cancelled email', [
                'user_id'    => $user->getId(),
                'request_id' => $deletionRequest->getId(),
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
