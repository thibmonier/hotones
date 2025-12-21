<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SendLeadNurturingEmailMessage;
use App\Repository\LeadCaptureRepository;
use App\Service\LeadMagnetMailer;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler pour traiter les envois d'emails de nurturing.
 */
#[AsMessageHandler]
final readonly class SendLeadNurturingEmailMessageHandler
{
    public function __construct(
        private LeadCaptureRepository $leadCaptureRepository,
        private LeadMagnetMailer $leadMagnetMailer,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendLeadNurturingEmailMessage $message): void
    {
        $lead = $this->leadCaptureRepository->find($message->getLeadId());

        if (!$lead) {
            $this->logger->warning('Lead not found for nurturing email', [
                'lead_id'    => $message->getLeadId(),
                'day_number' => $message->getDayNumber(),
            ]);

            return;
        }

        // Vérifier que le lead a donné son consentement marketing
        if (!$lead->hasMarketingConsent()) {
            $this->logger->info('Lead has no marketing consent, skipping nurturing email', [
                'lead_id' => $lead->getId(),
                'email'   => $lead->getEmail(),
            ]);

            return;
        }

        try {
            match ($message->getDayNumber()) {
                1       => $this->sendDay1Email($lead),
                3       => $this->sendDay3Email($lead),
                7       => $this->sendDay7Email($lead),
                default => throw new InvalidArgumentException('Invalid day number: '.$message->getDayNumber()),
            };

            $this->logger->info('Nurturing email sent successfully', [
                'lead_id'    => $lead->getId(),
                'email'      => $lead->getEmail(),
                'day_number' => $message->getDayNumber(),
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to send nurturing email', [
                'lead_id'    => $lead->getId(),
                'email'      => $lead->getEmail(),
                'day_number' => $message->getDayNumber(),
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function sendDay1Email($lead): void
    {
        // Vérifier qu'il n'a pas déjà été envoyé
        if ($lead->getNurturingDay1SentAt() !== null) {
            return;
        }

        $this->leadMagnetMailer->sendNurturingDay1($lead);
        $lead->markNurturingDay1AsSent();
        $this->entityManager->flush();
    }

    private function sendDay3Email($lead): void
    {
        // Vérifier qu'il n'a pas déjà été envoyé
        if ($lead->getNurturingDay3SentAt() !== null) {
            return;
        }

        $this->leadMagnetMailer->sendNurturingDay3($lead);
        $lead->markNurturingDay3AsSent();
        $this->entityManager->flush();
    }

    private function sendDay7Email($lead): void
    {
        // Vérifier qu'il n'a pas déjà été envoyé
        if ($lead->getNurturingDay7SentAt() !== null) {
            return;
        }

        $this->leadMagnetMailer->sendNurturingDay7($lead);
        $lead->markNurturingDay7AsSent();
        $this->entityManager->flush();
    }
}
