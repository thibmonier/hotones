<?php

declare(strict_types=1);

namespace App\Application\Project\Export;

use App\Domain\Project\Service\ClientBillingLeadTimeAggregate;
use App\Domain\Project\Service\ClientConversionAggregate;
use App\Domain\Project\Service\ClientDsoAggregate;
use App\Domain\Project\Service\ClientMarginAdoptionAggregate;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streamed CSV exporter pour drill-down KPI dashboard (US-116 T-116-03,
 * étendu US-119 T-119-03 pour Conversion + Margin).
 *
 * Colonnes : `client_name, valeur_kpi, sample_count, window`.
 * Unité valeur_kpi adaptée par kpi (`jours` / `%`).
 * Réutilise le pattern `DriftReportCsvExporter` (US-113 T-113-04).
 */
final readonly class KpiDrillDownCsvExporter
{
    /**
     * @param list<ClientDsoAggregate>|list<ClientBillingLeadTimeAggregate>|list<ClientConversionAggregate>|list<ClientMarginAdoptionAggregate> $aggregates
     */
    public function createResponse(string $kpi, int $window, array $aggregates): Response
    {
        $filename = sprintf(
            'kpi-drill-down-%s-window-%dj-%s.csv',
            $kpi,
            $window,
            new DateTimeImmutable()->format('Y-m-d-His'),
        );

        // Réponse non-streamée pour testabilité (StreamedResponse callback ne
        // s'exécute pas dans BrowserKit sans terminate()). Volume max attendu
        // ~quelques centaines de lignes — pas besoin de streaming.
        $response = new Response($this->buildCsvString($window, $aggregates));
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            sprintf('attachment; filename="%s"', $filename),
        );

        return $response;
    }

    /**
     * @param list<ClientDsoAggregate>|list<ClientBillingLeadTimeAggregate>|list<ClientConversionAggregate>|list<ClientMarginAdoptionAggregate> $aggregates
     */
    public function buildCsvString(int $window, array $aggregates): string
    {
        $handle = fopen('php://temp', 'r+b');
        if ($handle === false) {
            throw new RuntimeException('Cannot open temp stream for CSV');
        }

        fputcsv($handle, ['client_name', 'valeur_kpi', 'sample_count', 'window'], escape: '');

        foreach ($aggregates as $agg) {
            [$value, $sample] = match (true) {
                $agg instanceof ClientDsoAggregate => [$agg->dsoAverageDays, $agg->sampleCount],
                $agg instanceof ClientBillingLeadTimeAggregate => [$agg->leadTimeAverageDays, $agg->sampleCount],
                $agg instanceof ClientConversionAggregate => [$agg->ratePercent, $agg->emittedCount],
                $agg instanceof ClientMarginAdoptionAggregate => [$agg->freshPercent, $agg->totalActive],
            };

            fputcsv(
                $handle,
                [
                    $agg->clientName,
                    sprintf('%.1f', $value),
                    $sample,
                    sprintf('%dj', $window),
                ],
                escape: '',
            );
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }
}
