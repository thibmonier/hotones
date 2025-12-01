<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service d'export des données vers Excel.
 */
class ExcelExportService
{
    /**
     * Exporte les données du dashboard analytique vers Excel.
     *
     * @param array             $kpis             KPIs principaux
     * @param array             $monthlyEvolution Évolution mensuelle
     * @param DateTimeInterface $startDate        Date de début
     * @param DateTimeInterface $endDate          Date de fin
     * @param array             $filters          Filtres appliqués
     *
     * @return StreamedResponse Fichier Excel en téléchargement
     */
    public function exportDashboard(
        array $kpis,
        array $monthlyEvolution,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $filters = []
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();

        // Onglet 1: KPIs principaux
        $this->createKPIsSheet($spreadsheet, $kpis, $startDate, $endDate, $filters);

        // Onglet 2: Évolution mensuelle
        $this->createMonthlyEvolutionSheet($spreadsheet, $monthlyEvolution);

        // Onglet 3: Répartition par type (si disponible)
        if (isset($kpis['projectsByType']) && !empty($kpis['projectsByType'])) {
            $this->createProjectTypeSheet($spreadsheet, $kpis['projectsByType']);
        }

        // Onglet 4: Répartition par catégorie (si disponible)
        if (isset($kpis['projectsByCategory']) && !empty($kpis['projectsByCategory'])) {
            $this->createProjectCategorySheet($spreadsheet, $kpis['projectsByCategory']);
        }

        // Onglet 5: Top contributeurs (si disponible)
        if (isset($kpis['topContributors']) && !empty($kpis['topContributors'])) {
            $this->createTopContributorsSheet($spreadsheet, $kpis['topContributors']);
        }

        // Générer le fichier
        $writer = new Xlsx($spreadsheet);

        $filename = sprintf(
            'dashboard_analytics_%s_%s.xlsx',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        );

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Crée l'onglet des KPIs principaux.
     */
    private function createKPIsSheet(
        Spreadsheet $spreadsheet,
        array $kpis,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $filters
    ): void {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('KPIs Principaux');

        // En-tête
        $sheet->setCellValue('A1', 'Dashboard Analytique - KPIs');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);

        // Période
        $sheet->setCellValue('A2', 'Période :');
        $sheet->setCellValue('B2', sprintf(
            '%s au %s',
            $startDate->format('d/m/Y'),
            $endDate->format('d/m/Y'),
        ));

        // Filtres actifs
        $row = 3;
        if (!empty($filters)) {
            $sheet->setCellValue('A'.$row, 'Filtres actifs :');
            ++$row;
            foreach ($filters as $key => $value) {
                if ($value !== null) {
                    $sheet->setCellValue('A'.$row, '  - '.ucfirst($key));
                    $sheet->setCellValue('B'.$row, (string) $value);
                    ++$row;
                }
            }
        }

        $row += 2; // Espace

        // Section Revenus & Marges
        $this->addSectionHeader($sheet, $row, 'Revenus & Marges');
        ++$row;
        $this->addKPILine($sheet, $row++, 'CA Total', number_format($kpis['totalRevenue'] ?? 0, 2, ',', ' ').' €');
        $this->addKPILine($sheet, $row++, 'Coûts Totaux', number_format($kpis['totalCosts'] ?? 0, 2, ',', ' ').' €');
        $this->addKPILine($sheet, $row++, 'Marge Brute', number_format($kpis['grossMargin'] ?? 0, 2, ',', ' ').' €');
        $this->addKPILine($sheet, $row++, 'Taux de Marge', number_format($kpis['marginPercentage'] ?? 0, 2, ',', ' ').' %');

        ++$row;

        // Section Projets
        $this->addSectionHeader($sheet, $row, 'Projets');
        ++$row;
        $this->addKPILine($sheet, $row++, 'Total Projets', (string) ($kpis['totalProjects'] ?? 0));
        $this->addKPILine($sheet, $row++, 'Projets Actifs', (string) ($kpis['activeProjects'] ?? 0));
        $this->addKPILine($sheet, $row++, 'Projets Terminés', (string) ($kpis['completedProjects'] ?? 0));

        ++$row;

        // Section Devis
        $this->addSectionHeader($sheet, $row, 'Devis');
        ++$row;
        $this->addKPILine($sheet, $row++, 'Total Devis', (string) ($kpis['totalOrders'] ?? 0));
        $this->addKPILine($sheet, $row++, 'Devis en Attente', (string) ($kpis['pendingOrders'] ?? 0));
        $this->addKPILine($sheet, $row++, 'Devis Gagnés', (string) ($kpis['wonOrders'] ?? 0));
        $this->addKPILine($sheet, $row++, 'CA en Attente', number_format($kpis['pendingRevenue'] ?? 0, 2, ',', ' ').' €');
        $this->addKPILine($sheet, $row++, 'Taux de Conversion', number_format($kpis['conversionRate'] ?? 0, 2, ',', ' ').' %');

        ++$row;

        // Section Temps & Occupation
        $this->addSectionHeader($sheet, $row, 'Temps & Occupation');
        ++$row;
        $this->addKPILine($sheet, $row++, 'Jours Travaillés', number_format($kpis['totalWorkedDays'] ?? 0, 2, ',', ' '));
        $this->addKPILine($sheet, $row++, 'Jours Vendus', number_format($kpis['totalSoldDays'] ?? 0, 2, ',', ' '));
        $this->addKPILine($sheet, $row++, 'Taux d\'Occupation', number_format($kpis['utilizationRate'] ?? 0, 2, ',', ' ').' %');

        // Ajuster largeurs
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
    }

    /**
     * Crée l'onglet de l'évolution mensuelle.
     */
    private function createMonthlyEvolutionSheet(Spreadsheet $spreadsheet, array $monthlyEvolution): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Évolution Mensuelle');

        // En-têtes
        $sheet->setCellValue('A1', 'Mois');
        $sheet->setCellValue('B1', 'CA (€)');
        $sheet->setCellValue('C1', 'Coûts (€)');
        $sheet->setCellValue('D1', 'Marge (€)');
        $this->applyHeaderStyle($sheet, 'A1:D1');

        // Données
        $row = 2;
        foreach ($monthlyEvolution as $data) {
            $sheet->setCellValue('A'.$row, $data['month'] ?? '');
            $sheet->setCellValue('B'.$row, (float) ($data['revenue'] ?? 0));
            $sheet->setCellValue('C'.$row, (float) ($data['costs'] ?? 0));
            $sheet->setCellValue('D'.$row, (float) ($data['margin'] ?? 0));
            ++$row;
        }

        // Format numérique pour les colonnes monétaires
        $lastRow = $row - 1;
        $sheet->getStyle('B2:D'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

        // Ajuster largeurs
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Crée l'onglet de répartition par type.
     */
    private function createProjectTypeSheet(Spreadsheet $spreadsheet, array $projectsByType): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Répartition Type');

        // En-têtes
        $sheet->setCellValue('A1', 'Type de Projet');
        $sheet->setCellValue('B1', 'Nombre');
        $this->applyHeaderStyle($sheet, 'A1:B1');

        // Données
        $row = 2;
        foreach ($projectsByType as $type => $count) {
            $sheet->setCellValue('A'.$row, ucfirst($type));
            $sheet->setCellValue('B'.$row, (int) $count);
            ++$row;
        }

        // Ajuster largeurs
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Crée l'onglet de répartition par catégorie.
     */
    private function createProjectCategorySheet(Spreadsheet $spreadsheet, array $projectsByCategory): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Répartition Catégorie');

        // En-têtes
        $sheet->setCellValue('A1', 'Catégorie');
        $sheet->setCellValue('B1', 'Nombre de Projets');
        $this->applyHeaderStyle($sheet, 'A1:B1');

        // Données
        $row = 2;
        foreach ($projectsByCategory as $category => $count) {
            $sheet->setCellValue('A'.$row, $category);
            $sheet->setCellValue('B'.$row, (int) $count);
            ++$row;
        }

        // Ajuster largeurs
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Crée l'onglet des top contributeurs.
     */
    private function createTopContributorsSheet(Spreadsheet $spreadsheet, array $topContributors): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Top Contributeurs');

        // En-têtes
        $sheet->setCellValue('A1', 'Contributeur');
        $sheet->setCellValue('B1', 'Heures Travaillées');
        $this->applyHeaderStyle($sheet, 'A1:B1');

        // Données
        $row = 2;
        foreach ($topContributors as $contributor) {
            $sheet->setCellValue('A'.$row, $contributor['name'] ?? 'N/A');
            $sheet->setCellValue('B'.$row, (float) ($contributor['hours'] ?? 0));
            ++$row;
        }

        // Format numérique
        $lastRow = $row - 1;
        $sheet->getStyle('B2:B'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

        // Ajuster largeurs
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Ajoute une ligne de KPI.
     */
    private function addKPILine($sheet, int $row, string $label, string $value): void
    {
        $sheet->setCellValue('A'.$row, $label);
        $sheet->setCellValue('B'.$row, $value);
        $sheet->getStyle('A'.$row)->getFont()->setBold(false);
        $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    /**
     * Ajoute un en-tête de section.
     */
    private function addSectionHeader($sheet, int $row, string $title): void
    {
        $sheet->setCellValue('A'.$row, $title);
        $sheet->mergeCells('A'.$row.':B'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setSize(14)->setBold(true);
        $sheet->getStyle('A'.$row)->applyFromArray([
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ]);
    }

    /**
     * Applique le style d'en-tête.
     */
    private function applyHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }
}
