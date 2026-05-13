<?php

declare(strict_types=1);

namespace App\Application\WorkItem\Migration;

use App\Domain\WorkItem\Migration\WorkItemMigrationResult;
use DateTimeImmutable;
use RuntimeException;

/**
 * CSV drift report exporter (US-113 T-113-04).
 *
 * Exporte les drifts détectés par {@see WorkItemMigrationResult} dans un
 * fichier CSV pour audit comptable manuel.
 *
 * Colonnes :
 *   timesheet_id, contributor_id, legacy_cost_cents, recomputed_cost_cents,
 *   delta_cents, abs_delta_cents
 *
 * Path par défaut : `var/migration/workitem-cost-drift-{YYYY-MM-DD-HHMMSS}.csv`
 * (cf. US-113 Gherkin Technical Notes).
 */
final readonly class DriftReportCsvExporter
{
    /**
     * @return string Absolute path of the exported CSV file
     */
    public function export(WorkItemMigrationResult $result, string $outputPath): string
    {
        $directory = dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Cannot create directory: %s', $directory));
        }

        $handle = fopen($outputPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Cannot open file for writing: %s', $outputPath));
        }

        try {
            fputcsv($handle, [
                'timesheet_id',
                'contributor_id',
                'legacy_cost_cents',
                'recomputed_cost_cents',
                'delta_cents',
                'abs_delta_cents',
            ], escape: '');

            foreach ($result->drifts as $drift) {
                fputcsv(
                    $handle,
                    [
                        $drift->timesheetId,
                        $drift->contributorId,
                        $drift->legacyCostCents,
                        $drift->recomputedCostCents,
                        $drift->deltaCents(),
                        $drift->absoluteDeltaCents(),
                    ],
                    escape: '',
                );
            }
        } finally {
            fclose($handle);
        }

        return $outputPath;
    }

    public static function defaultPath(string $projectDir, DateTimeImmutable $now): string
    {
        return sprintf(
            '%s/var/migration/workitem-cost-drift-%s.csv',
            rtrim($projectDir, '/'),
            $now->format('Y-m-d-His'),
        );
    }
}
