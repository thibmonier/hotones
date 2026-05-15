<?php

declare(strict_types=1);

namespace App\Application\Project\Export;

use App\Domain\Project\Service\ClientBillingLeadTimeAggregate;
use App\Domain\Project\Service\ClientDsoAggregate;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streamed CSV exporter pour drill-down KPI dashboard (US-116 T-116-03).
 *
 * Colonnes : `client_name, valeur_kpi, sample_count, window`.
 * Réutilise le pattern `DriftReportCsvExporter` (US-113 T-113-04).
 */
final readonly class KpiDrillDownCsvExporter
{
    /**
     * @param list<ClientDsoAggregate>|list<ClientBillingLeadTimeAggregate> $aggregates
     */
    public function createResponse(string $kpi, int $window, array $aggregates): Response
    {
        $filename = sprintf(
            'kpi-drill-down-%s-window-%dj-%s.csv',
            $kpi,
            $window,
            new DateTimeImmutable()->format('Y-m-d-His'),
        );

        $response = new StreamedResponse(function () use ($window, $aggregates): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['client_name', 'valeur_kpi', 'sample_count', 'window'], escape: '');

            foreach ($aggregates as $agg) {
                $value = $agg instanceof ClientDsoAggregate
                    ? $agg->dsoAverageDays
                    : $agg->leadTimeAverageDays;

                fputcsv(
                    $handle,
                    [
                        $agg->clientName,
                        sprintf('%.1f', $value),
                        $agg->sampleCount,
                        sprintf('%dj', $window),
                    ],
                    escape: '',
                );
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            sprintf('attachment; filename="%s"', $filename),
        );

        return $response;
    }
}
