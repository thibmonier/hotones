<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AccountDeletionRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande de traitement des demandes de suppression de comptes.
 * À exécuter quotidiennement via cron pour supprimer les comptes dont la période de grâce est expirée.
 *
 * Usage:
 *   php bin/console app:process-account-deletions
 *   php bin/console app:process-account-deletions --dry-run
 */
#[AsCommand(
    name: 'app:process-account-deletions',
    description: 'Process account deletion requests that have passed their 30-day grace period (GDPR)',
)]
class ProcessAccountDeletionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccountDeletionRequestRepository $deletionRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate deletion without actually deleting accounts')
            ->setHelp(<<<'HELP'
                This command processes account deletion requests that have passed their 30-day grace period.

                <info>GDPR Workflow:</info>
                1. User requests deletion → Email sent with confirmation link
                2. User confirms → 30-day grace period starts
                3. After 30 days → This command executes the deletion
                4. User can cancel anytime during grace period

                <info>What gets deleted:</info>
                - User account (soft delete or anonymization depending on legal requirements)
                - Personal data (following GDPR guidelines)
                - Some data may be retained for legal/accounting obligations

                <info>Cron setup (daily at 3 AM):</info>
                0 3 * * * php /path/to/project/bin/console app:process-account-deletions >> /var/log/gdpr-deletions.log 2>&1
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('GDPR Account Deletion Processor');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No accounts will be actually deleted');
        }

        // Récupérer les demandes dont la période de grâce est expirée
        $dueDeletions = $this->deletionRepository->findDueDeletions();

        if (empty($dueDeletions)) {
            $io->success('No account deletions due at this time.');

            return Command::SUCCESS;
        }

        $io->section(sprintf('Found %d account(s) ready for deletion', count($dueDeletions)));

        $deletedCount = 0;
        $errorCount   = 0;

        foreach ($dueDeletions as $deletionRequest) {
            $user = $deletionRequest->getUser();

            $io->text(sprintf(
                'Processing: User #%d (%s) - Requested: %s - Grace period ended: %s',
                $user->getId(),
                $user->getEmail(),
                $deletionRequest->getRequestedAt()->format('Y-m-d H:i'),
                $deletionRequest->getScheduledDeletionAt()->format('Y-m-d H:i'),
            ));

            try {
                if (!$dryRun) {
                    // IMPORTANT: En production, implémenter une stratégie appropriée:
                    // Option 1: Soft delete (garder le compte mais anonymisé)
                    // Option 2: Hard delete (supprimer complètement)
                    // Option 3: Anonymisation (remplacer les données par des valeurs génériques)

                    // Pour l'instant, on marque juste la demande comme complétée
                    // TODO: Implémenter la suppression/anonymisation réelle du compte
                    $deletionRequest->complete();
                    $this->em->flush();

                    $this->logger->warning('Account deletion completed (PLACEHOLDER - actual deletion not implemented)', [
                        'user_id'    => $user->getId(),
                        'email'      => $user->getEmail(),
                        'request_id' => $deletionRequest->getId(),
                    ]);

                    $io->success(sprintf('✓ Deletion request #%d marked as completed', $deletionRequest->getId()));
                } else {
                    $io->info(sprintf('[DRY RUN] Would delete user #%d (%s)', $user->getId(), $user->getEmail()));
                }

                ++$deletedCount;
            } catch (Exception $e) {
                ++$errorCount;
                $io->error(sprintf('Failed to process deletion for user #%d: %s', $user->getId(), $e->getMessage()));

                $this->logger->error('Account deletion failed', [
                    'user_id'    => $user->getId(),
                    'request_id' => $deletionRequest->getId(),
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $io->newLine();
        $io->section('Summary');
        $io->table(
            ['Metric', 'Count'],
            [
                ['Total due deletions', count($dueDeletions)],
                ['Successfully processed', $deletedCount],
                ['Errors', $errorCount],
            ],
        );

        if ($dryRun) {
            $io->note('This was a DRY RUN. No actual changes were made.');
        }

        if ($errorCount > 0) {
            $io->warning(sprintf('%d deletion(s) failed. Check logs for details.', $errorCount));

            return Command::FAILURE;
        }

        $io->success(sprintf('Successfully processed %d account deletion(s).', $deletedCount));

        return Command::SUCCESS;
    }
}
