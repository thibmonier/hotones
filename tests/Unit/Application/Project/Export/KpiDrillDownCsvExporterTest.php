<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\Export;

use App\Application\Project\Export\KpiDrillDownCsvExporter;
use App\Domain\Project\Service\ClientBillingLeadTimeAggregate;
use App\Domain\Project\Service\ClientDsoAggregate;
use PHPUnit\Framework\TestCase;

final class KpiDrillDownCsvExporterTest extends TestCase
{
    public function testBuildsCsvHeaderEvenWhenAggregatesEmpty(): void
    {
        $exporter = new KpiDrillDownCsvExporter();

        $csv = $exporter->buildCsvString(window: 30, aggregates: []);

        static::assertSame("client_name,valeur_kpi,sample_count,window\n", $csv);
    }

    public function testBuildsCsvForDsoAggregates(): void
    {
        $exporter = new KpiDrillDownCsvExporter();

        $csv = $exporter->buildCsvString(window: 90, aggregates: [
            new ClientDsoAggregate(clientName: 'Acme', dsoAverageDays: 42.7, sampleCount: 5),
            new ClientDsoAggregate(clientName: 'Beta', dsoAverageDays: 10.0, sampleCount: 2),
        ]);

        static::assertStringContainsString('client_name,valeur_kpi,sample_count,window', $csv);
        static::assertStringContainsString('Acme,42.7,5,90j', $csv);
        static::assertStringContainsString('Beta,10.0,2,90j', $csv);
    }

    public function testBuildsCsvForLeadTimeAggregates(): void
    {
        $exporter = new KpiDrillDownCsvExporter();

        $csv = $exporter->buildCsvString(window: 365, aggregates: [
            new ClientBillingLeadTimeAggregate(clientName: 'Gamma', leadTimeAverageDays: 7.5, sampleCount: 12),
        ]);

        static::assertStringContainsString('Gamma,7.5,12,365j', $csv);
    }

    public function testCreatesResponseWithCorrectHeaders(): void
    {
        $exporter = new KpiDrillDownCsvExporter();

        $response = $exporter->createResponse(
            kpi: 'dso',
            window: 30,
            aggregates: [new ClientDsoAggregate(clientName: 'Acme', dsoAverageDays: 30.0, sampleCount: 1)],
        );

        static::assertSame(200, $response->getStatusCode());
        static::assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        static::assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        static::assertStringContainsString('kpi-drill-down-dso-window-30j', (string) $response->headers->get('Content-Disposition'));
        static::assertStringContainsString('Acme,30.0,1,30j', (string) $response->getContent());
    }

    public function testQuotesClientNamesContainingCommas(): void
    {
        $exporter = new KpiDrillDownCsvExporter();

        $csv = $exporter->buildCsvString(window: 30, aggregates: [
            new ClientDsoAggregate(clientName: 'Acme, Inc.', dsoAverageDays: 20.0, sampleCount: 1),
        ]);

        // fputcsv encadre automatiquement le champ avec virgule
        static::assertStringContainsString('"Acme, Inc."', $csv);
    }
}
