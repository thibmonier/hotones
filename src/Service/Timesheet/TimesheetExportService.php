<?php

declare(strict_types=1);

namespace App\Service\Timesheet;

use App\Entity\Contributor;
use App\Service\PdfGeneratorService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class TimesheetExportService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PdfGeneratorService $pdfGenerator,
    ) {
    }

    public function exportToExcel(
        Contributor $contributor,
        DateTime $start,
        DateTime $end,
        ?int $projectId = null,
    ): Response {
        $timesheets = $this->getTimesheets($contributor, $start, $end, $projectId);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Temps saisis');

        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Projet');
        $sheet->setCellValue('C1', 'Client');
        $sheet->setCellValue('D1', 'Tâche');
        $sheet->setCellValue('E1', 'Sous-tâche');
        $sheet->setCellValue('F1', 'Heures');
        $sheet->setCellValue('G1', 'Jours');
        $sheet->setCellValue('H1', 'Notes');

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row         = 2;
        $totalHours  = 0;
        $hoursPerDay = $contributor->getHoursPerDay();
        foreach ($timesheets as $ts) {
            $hours = (float) $ts->getHours();
            $days  = round($hours / $hoursPerDay, 3);

            $sheet->setCellValue('A'.$row, $ts->getDate()->format('d/m/Y'));
            $sheet->setCellValue('B'.$row, $ts->getProject()->getName());
            $sheet->setCellValue(
                'C'.$row,
                $ts->getProject()->getClient() ? $ts->getProject()->getClient()->getName() : '',
            );
            $sheet->setCellValue('D'.$row, $ts->getTask() ? $ts->getTask()->getName() : '');
            $sheet->setCellValue('E'.$row, $ts->getSubTask() ? $ts->getSubTask()->getTitle() : '');
            $sheet->setCellValue('F'.$row, $hours);
            $sheet->setCellValue('G'.$row, $days);
            $sheet->setCellValue('H'.$row, $ts->getNotes() ?: '');

            $totalHours += $hours;
            ++$row;
        }

        $sheet->setCellValue('E'.$row, 'TOTAL:');
        $sheet->setCellValue('F'.$row, $totalHours);
        $sheet->setCellValue('G'.$row, round($totalHours / $hoursPerDay, 3));
        $sheet
            ->getStyle('E'.$row.':G'.$row)
            ->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'],
                ],
            ]);

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setAutoFilter('A1:H'.($row - 1));

        $writer   = new Xlsx($spreadsheet);
        $filename = sprintf(
            'temps_%s_%s_%s.xlsx',
            $contributor->getFirstName().'_'.$contributor->getLastName(),
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
        );

        $temp = tmpfile();
        $path = stream_get_meta_data($temp)['uri'];
        $writer->save($path);

        $response = new Response(file_get_contents($path));
        fclose($temp);

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    public function exportToPdf(
        Contributor $contributor,
        DateTime $start,
        DateTime $end,
        ?int $projectId = null,
    ): Response {
        $timesheets  = $this->getTimesheets($contributor, $start, $end, $projectId);
        $hoursPerDay = $contributor->getHoursPerDay();

        $totalHours = 0;
        foreach ($timesheets as $ts) {
            $totalHours += (float) $ts->getHours();
        }

        $projectSummary = [];
        foreach ($timesheets as $ts) {
            $projectName = $ts->getProject()->getName();
            if (!isset($projectSummary[$projectName])) {
                $projectSummary[$projectName] = 0;
            }
            $projectSummary[$projectName] += (float) $ts->getHours();
        }

        $project = null;
        if ($projectId) {
            $project = $this->em->getReference(\App\Entity\Project::class, $projectId);
        }

        $filename = sprintf(
            'temps_%s_%s_%s.pdf',
            $contributor->getFirstName().'_'.$contributor->getLastName(),
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
        );

        return $this->pdfGenerator->createPdfResponse(
            'timesheet/export_pdf.html.twig',
            [
                'contributor'    => $contributor,
                'timesheets'     => $timesheets,
                'startDate'      => $start,
                'endDate'        => $end,
                'project'        => $project,
                'totalHours'     => $totalHours,
                'totalDays'      => round($totalHours / $hoursPerDay, 3),
                'hoursPerDay'    => $hoursPerDay,
                'projectSummary' => $projectSummary,
                'generatedAt'    => new DateTime(),
            ],
            $filename,
        );
    }

    /**
     * @return \App\Entity\Timesheet[]
     */
    private function getTimesheets(
        Contributor $contributor,
        DateTime $start,
        DateTime $end,
        ?int $projectId,
    ): array {
        $timesheetRepo = $this->em->getRepository(\App\Entity\Timesheet::class);
        $timesheets    = $timesheetRepo->findByContributorAndDateRange($contributor, $start, $end);

        if ($projectId) {
            $timesheets = array_filter(
                $timesheets,
                fn ($t): bool => $t->getProject()->getId() === $projectId,
            );
        }

        return $timesheets;
    }
}
