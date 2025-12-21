<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProcessNurturingEmailsMessage;
use App\Message\SendLeadNurturingEmailMessage;
use App\Repository\LeadCaptureRepository;

use function count;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handler pour traiter le processus quotidien d'envoi des emails de nurturing.
 *
 * Ce handler est déclenché par le Scheduler et dispatche individuellement
 * les messages SendLeadNurturingEmailMessage pour chaque lead éligible.
 */
#[AsMessageHandler]
final readonly class ProcessNurturingEmailsMessageHandler
{
    public function __construct(
        private LeadCaptureRepository $leadCaptureRepository,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessNurturingEmailsMessage $message): void
    {
        $this->logger->info('Starting nurturing emails processing');

        $stats = [
            'day1' => 0,
            'day3' => 0,
            'day7' => 0,
        ];

        // Récupérer tous les leads avec consentement marketing
        $leads = $this->leadCaptureRepository->findWithMarketingConsent();

        $this->logger->info('Found leads with marketing consent', [
            'count' => count($leads),
        ]);

        foreach ($leads as $lead) {
            // Email J+1
            if ($lead->shouldReceiveNurturingDay1()) {
                $this->messageBus->dispatch(new SendLeadNurturingEmailMessage(
                    leadId: $lead->getId(),
                    dayNumber: 1,
                ));
                ++$stats['day1'];
            }

            // Email J+3
            if ($lead->shouldReceiveNurturingDay3()) {
                $this->messageBus->dispatch(new SendLeadNurturingEmailMessage(
                    leadId: $lead->getId(),
                    dayNumber: 3,
                ));
                ++$stats['day3'];
            }

            // Email J+7
            if ($lead->shouldReceiveNurturingDay7()) {
                $this->messageBus->dispatch(new SendLeadNurturingEmailMessage(
                    leadId: $lead->getId(),
                    dayNumber: 7,
                ));
                ++$stats['day7'];
            }
        }

        $total = $stats['day1'] + $stats['day3'] + $stats['day7'];

        $this->logger->info('Nurturing emails dispatched', [
            'day1'  => $stats['day1'],
            'day3'  => $stats['day3'],
            'day7'  => $stats['day7'],
            'total' => $total,
        ]);
    }
}
