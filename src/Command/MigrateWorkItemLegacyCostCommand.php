<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\WorkItem\Migration\HourlyRateProviderInterface;
use App\Domain\WorkItem\Migration\LegacyTimesheetReadModelRepositoryInterface;
use App\Domain\WorkItem\Migration\MigrationDriftDetail;
use App\Domain\WorkItem\Migration\WorkItemMigrationResult;
use App\Domain\WorkItem\Migration\WorkItemMigrator;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * US-113 T-113-03 — Symfony command migrating legacy WorkItem.cost snapshots.
 *
 * Usage :
 *   bin/console app:workitem:migrate-legacy-cost --dry-run
 *   bin/console app:workitem:migrate-legacy-cost --batch-size=100
 *   bin/console app:workitem:migrate-legacy-cost --limit=500
 *
 * Modes :
 *   - `--dry-run`   : ne fait que le compute, log les drifts, aucun write DB
 *   - `--execute`   : applique les writes (legacy_cost_cents + drift flag + migrated_at)
 *                     défaut si --dry-run absent
 *
 * Batch processing 100 items/transaction (configurable via --batch-size).
 * Idempotent : items déjà migrés (migrated_at != null) sont skip silencieusement.
 *
 * Trigger abandon ADR-0013 cas 3 : exit code 2 si drift global > 5 %.
 */
#[AsCommand(
    name: 'app:workitem:migrate-legacy-cost',
    description: 'Migre les snapshots cost WorkItem legacy + détecte drift (US-113)',
)]
final class MigrateWorkItemLegacyCostCommand extends Command
{
    private const int DEFAULT_BATCH_SIZE = 100;

    public function __construct(
        private readonly LegacyTimesheetReadModelRepositoryInterface $repository,
        private readonly HourlyRateProviderInterface $hourlyRateProvider,
        private readonly WorkItemMigrator $migrator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Compute sans écrire en DB')
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Items par batch (default 100)',
                (string) self::DEFAULT_BATCH_SIZE,
            )
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limite globale items processés', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $limit = max(0, (int) $input->getOption('limit'));
        $now = new DateTimeImmutable();

        $total = $this->repository->countAll();
        if ($limit > 0) {
            $total = min($total, $limit);
        }

        $io->title(sprintf(
            'Migration WorkItem legacy cost — %s mode',
            $dryRun ? 'DRY-RUN' : 'EXECUTE',
        ));
        $io->writeln(sprintf('Total timesheets to process : %d (batch %d)', $total, $batchSize));

        $aggregated = new WorkItemMigrationResult(
            migrated: 0,
            alreadyMigrated: 0,
            missingRate: 0,
            drifts: [],
            totalLegacyCostCents: 0,
            totalDriftCents: 0,
        );

        $offset = 0;
        $io->progressStart($total);

        while ($offset < $total) {
            $currentBatch = $batchSize;
            if ($limit > 0) {
                $currentBatch = min($currentBatch, $limit - $offset);
            }

            $batch = $this->repository->findBatch($currentBatch, $offset);
            if ($batch === []) {
                break;
            }

            $result = $this->migrator->migrate($batch, $now);
            $aggregated = $this->mergeResults($aggregated, $result);

            if (!$dryRun) {
                $this->applyWrites($batch, $result, $now);
            }

            $offset += count($batch);
            $io->progressAdvance(count($batch));
        }

        $io->progressFinish();

        $this->reportSummary($io, $aggregated, $dryRun);

        return $aggregated->shouldTriggerAbandonCase3() ? Command::FAILURE : Command::SUCCESS;
    }

    private function mergeResults(WorkItemMigrationResult $a, WorkItemMigrationResult $b): WorkItemMigrationResult
    {
        return new WorkItemMigrationResult(
            migrated: $a->migrated + $b->migrated,
            alreadyMigrated: $a->alreadyMigrated + $b->alreadyMigrated,
            missingRate: $a->missingRate + $b->missingRate,
            drifts: [...$a->drifts, ...$b->drifts],
            totalLegacyCostCents: $a->totalLegacyCostCents + $b->totalLegacyCostCents,
            totalDriftCents: $a->totalDriftCents + $b->totalDriftCents,
        );
    }

    /**
     * @param list<\App\Domain\WorkItem\Migration\LegacyTimesheetRecord> $batch
     */
    private function applyWrites(array $batch, WorkItemMigrationResult $result, DateTimeImmutable $now): void
    {
        $driftMap = [];
        foreach ($result->drifts as $drift) {
            $driftMap[$drift->timesheetId] = $drift->recomputedCostCents;
        }

        foreach ($batch as $record) {
            if ($record->isAlreadyMigrated()) {
                continue;
            }

            $rateCents = $this->hourlyRateProvider->resolveAt($record->contributorId, $record->workDate);
            if ($rateCents === null) {
                continue;
            }

            $recomputed = (int) round($rateCents * $record->hours);
            $drift = isset($driftMap[$record->timesheetId]);

            $this->repository->applyMigrationWrites(
                timesheetId: $record->timesheetId,
                costCents: $recomputed,
                drift: $drift,
                now: $now,
            );
        }
    }

    private function reportSummary(SymfonyStyle $io, WorkItemMigrationResult $result, bool $dryRun): void
    {
        $io->section('Résumé migration');

        $rows = [
            ['Mode', $dryRun ? 'DRY-RUN' : 'EXECUTE'],
            ['Total processed', (string) $result->totalProcessed()],
            ['Migrated', (string) $result->migrated],
            ['Already migrated (skip)', (string) $result->alreadyMigrated],
            ['Missing rate', (string) $result->missingRate],
            ['Drifts > 1 cent', (string) $result->driftCount()],
            ['Total legacy cost cents', (string) $result->totalLegacyCostCents],
            ['Total drift cents', (string) $result->totalDriftCents],
            ['Drift ratio', sprintf('%.4f %%', $result->driftRatio() * 100)],
        ];

        $io->table(['Métrique', 'Valeur'], $rows);

        if ($result->shouldTriggerAbandonCase3()) {
            $io->error(sprintf(
                '⚠️ Trigger abandon ADR-0013 cas 3 — drift global %.2f %% > 5 %%. Décision PO+Tech Lead requise.',
                $result->driftRatio() * 100,
            ));
        } elseif ($result->driftCount() > 0) {
            $io->warning(sprintf('%d drifts détectés. Audit comptable recommandé.', $result->driftCount()));
        } else {
            $io->success('Migration sans drift.');
        }
    }

    /**
     * Expose drifts for upstream CSV export (T-113-04 reuses this getter via service composition).
     *
     * @return list<MigrationDriftDetail>
     */
    public function getLastDrifts(WorkItemMigrationResult $result): array
    {
        return $result->drifts;
    }
}
