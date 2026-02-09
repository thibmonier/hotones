<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\LeadCapture;
use App\Message\SendLeadNurturingEmailMessage;
use App\Repository\LeadCaptureRepository;

use function count;
use function in_array;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Commande pour envoyer les emails de nurturing aux leads.
 *
 * Cette commande est destinée à être exécutée quotidiennement via le Scheduler.
 * Elle identifie les leads qui doivent recevoir un email de nurturing (J+1, J+3, J+7)
 * et dispatch des messages pour les envoyer de manière asynchrone.
 */
#[AsCommand(name: 'app:send-nurturing-emails', description: 'Envoie les emails de nurturing aux leads (J+1, J+3, J+7)')]
class SendNurturingEmailsCommand extends Command
{
    public function __construct(
        private readonly LeadCaptureRepository $leadCaptureRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Affiche les emails qui seraient envoyés sans les envoyer réellement',
        )->addOption(
            'day',
            'd',
            InputOption::VALUE_REQUIRED,
            'Ne traiter que les emails d\'un jour spécifique (1, 3 ou 7)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io          = new SymfonyStyle($input, $output);
        $dryRun      = (bool) $input->getOption('dry-run');
        $specificDay = $input->getOption('day') ? (int) $input->getOption('day') : null;

        if ($specificDay !== null && !in_array($specificDay, [1, 3, 7], true)) {
            $io->error('Le paramètre --day doit être 1, 3 ou 7');

            return Command::FAILURE;
        }

        $io->title('Envoi des emails de nurturing');

        if ($dryRun) {
            $io->note('Mode DRY RUN : aucun email ne sera réellement envoyé');
        }

        $stats = [
            'day1' => 0,
            'day3' => 0,
            'day7' => 0,
        ];

        // Récupérer tous les leads avec consentement marketing
        $leads = $this->leadCaptureRepository->findWithMarketingConsent();

        $io->info(sprintf('Traitement de %d leads avec consentement marketing', count($leads)));

        foreach ($leads as $lead) {
            // Email J+1
            if (($specificDay === null || $specificDay === 1) && $lead->shouldReceiveNurturingDay1()) {
                if ($dryRun) {
                    $io->text(sprintf(
                        '[DRY RUN] J+1 : %s (%s) - %d jours depuis création',
                        $lead->getEmail(),
                        $lead->getFullName(),
                        $lead->getDaysSinceCreation(),
                    ));
                } else {
                    $this->dispatchNurturingEmail($lead, 1);
                    $io->text(sprintf('✓ J+1 dispatché : %s', $lead->getEmail()));
                }
                ++$stats['day1'];
            }

            // Email J+3
            if (($specificDay === null || $specificDay === 3) && $lead->shouldReceiveNurturingDay3()) {
                if ($dryRun) {
                    $io->text(sprintf(
                        '[DRY RUN] J+3 : %s (%s) - %d jours depuis création',
                        $lead->getEmail(),
                        $lead->getFullName(),
                        $lead->getDaysSinceCreation(),
                    ));
                } else {
                    $this->dispatchNurturingEmail($lead, 3);
                    $io->text(sprintf('✓ J+3 dispatché : %s', $lead->getEmail()));
                }
                ++$stats['day3'];
            }

            // Email J+7
            if (($specificDay === null || $specificDay === 7) && $lead->shouldReceiveNurturingDay7()) {
                if ($dryRun) {
                    $io->text(sprintf(
                        '[DRY RUN] J+7 : %s (%s) - %d jours depuis création',
                        $lead->getEmail(),
                        $lead->getFullName(),
                        $lead->getDaysSinceCreation(),
                    ));
                } else {
                    $this->dispatchNurturingEmail($lead, 7);
                    $io->text(sprintf('✓ J+7 dispatché : %s', $lead->getEmail()));
                }
                ++$stats['day7'];
            }
        }

        $io->newLine();
        $io->section('Résumé');
        $io->table(['Type', 'Nombre'], [
            ['Emails J+1', $stats['day1']],
            ['Emails J+3', $stats['day3']],
            ['Emails J+7', $stats['day7']],
            ['Total', $stats['day1'] + $stats['day3'] + $stats['day7']],
        ]);

        if ($dryRun) {
            $io->warning('Mode DRY RUN : aucun email n\'a été réellement dispatché');
        } else {
            $io->success(sprintf(
                'Commande terminée. %d emails de nurturing dispatchés.',
                $stats['day1'] + $stats['day3'] + $stats['day7'],
            ));
        }

        return Command::SUCCESS;
    }

    private function dispatchNurturingEmail(LeadCapture $lead, int $dayNumber): void
    {
        $message = new SendLeadNurturingEmailMessage(leadId: $lead->getId(), dayNumber: $dayNumber);

        $this->messageBus->dispatch($message);
    }
}
