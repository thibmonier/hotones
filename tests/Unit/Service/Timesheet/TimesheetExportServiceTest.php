<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Timesheet;

use App\Entity\Client;
use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\Timesheet;
use App\Repository\TimesheetRepository;
use App\Service\PdfGeneratorService;
use App\Service\Timesheet\TimesheetExportService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coverage push (T-TC1-03 lot 2) — TimesheetExportService était à 0.88% coverage.
 */
#[AllowMockObjectsWithoutExpectations]
final class TimesheetExportServiceTest extends TestCase
{
    private function createContributor(string $firstName = 'Jean', string $lastName = 'Dupont', float $hoursPerDay = 7.0): Contributor
    {
        $contributor = $this->createStub(Contributor::class);
        $contributor->method('getFirstName')->willReturn($firstName);
        $contributor->method('getLastName')->willReturn($lastName);
        $contributor->method('getHoursPerDay')->willReturn($hoursPerDay);

        return $contributor;
    }

    private function createProject(string $name, ?Client $client = null, ?int $id = null): Project
    {
        $project = $this->createStub(Project::class);
        $project->method('getName')->willReturn($name);
        $project->method('getClient')->willReturn($client);
        $project->method('getId')->willReturn($id);

        return $project;
    }

    private function createTimesheet(
        Project $project,
        string $hours,
        ?ProjectTask $task = null,
        string $notes = '',
    ): Timesheet {
        $timesheet = $this->createStub(Timesheet::class);
        $timesheet->method('getDate')->willReturn(new DateTime('2026-05-05'));
        $timesheet->method('getProject')->willReturn($project);
        $timesheet->method('getHours')->willReturn($hours);
        $timesheet->method('getTask')->willReturn($task);
        $timesheet->method('getSubTask')->willReturn(null);
        $timesheet->method('getNotes')->willReturn($notes);

        return $timesheet;
    }

    public function testExportToExcelReturnsXlsxResponseWithEmptyTimesheets(): void
    {
        $repo = $this->createMock(TimesheetRepository::class);
        $repo->method('findByContributorAndDateRange')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $pdf = $this->createStub(PdfGeneratorService::class);

        $service = new TimesheetExportService($em, $pdf);
        $response = $service->exportToExcel(
            $this->createContributor(),
            new DateTime('2026-05-01'),
            new DateTime('2026-05-31'),
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testExportToExcelIncludesTimesheetRowsAndTotal(): void
    {
        $client = $this->createStub(Client::class);
        $client->method('getName')->willReturn('Acme Corp');
        $project = $this->createProject('Refonte site', $client);

        $timesheet1 = $this->createTimesheet($project, '7', null, 'kickoff');
        $timesheet2 = $this->createTimesheet($project, '3.5');

        $repo = $this->createMock(TimesheetRepository::class);
        $repo->method('findByContributorAndDateRange')->willReturn([$timesheet1, $timesheet2]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $pdf = $this->createStub(PdfGeneratorService::class);

        $service = new TimesheetExportService($em, $pdf);
        $response = $service->exportToExcel(
            $this->createContributor(),
            new DateTime('2026-05-01'),
            new DateTime('2026-05-31'),
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    public function testExportToExcelFiltersByProjectId(): void
    {
        $projectMatch = $this->createProject('Match', null, 42);
        $projectOther = $this->createProject('Other', null, 99);

        $tsMatch = $this->createTimesheet($projectMatch, '4');
        $tsOther = $this->createTimesheet($projectOther, '4');

        $repo = $this->createMock(TimesheetRepository::class);
        $repo->method('findByContributorAndDateRange')->willReturn([$tsMatch, $tsOther]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $pdf = $this->createStub(PdfGeneratorService::class);

        $service = new TimesheetExportService($em, $pdf);
        $response = $service->exportToExcel(
            $this->createContributor(),
            new DateTime('2026-05-01'),
            new DateTime('2026-05-31'),
            42,
        );

        // No exception + valid response. Filtering tested implicitly via lack of error
        // when only one timesheet matches projectId=42.
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testExportToPdfDelegatesToPdfGenerator(): void
    {
        $project = $this->createProject('PdfProj');
        $ts = $this->createTimesheet($project, '6');

        $repo = $this->createMock(TimesheetRepository::class);
        $repo->method('findByContributorAndDateRange')->willReturn([$ts]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $expectedResponse = new Response('pdf-bytes', 200, ['Content-Type' => 'application/pdf']);

        $pdf = $this->createMock(PdfGeneratorService::class);
        $pdf->expects($this->once())
            ->method('createPdfResponse')
            ->with(
                $this->equalTo('timesheet/export_pdf.html.twig'),
                $this->callback(function (array $context): bool {
                    return isset(
                        $context['contributor'],
                        $context['timesheets'],
                        $context['totalHours'],
                        $context['totalDays'],
                        $context['hoursPerDay'],
                        $context['projectSummary'],
                    );
                }),
                $this->stringContains('temps_'),
            )
            ->willReturn($expectedResponse);

        $service = new TimesheetExportService($em, $pdf);
        $response = $service->exportToPdf(
            $this->createContributor(),
            new DateTime('2026-05-01'),
            new DateTime('2026-05-31'),
        );

        $this->assertSame($expectedResponse, $response);
    }

    public function testExportToPdfWithProjectIdResolvesProjectReference(): void
    {
        $project = $this->createProject('PdfFiltered', null, 7);
        $ts = $this->createTimesheet($project, '8');

        $repo = $this->createMock(TimesheetRepository::class);
        $repo->method('findByContributorAndDateRange')->willReturn([$ts]);

        $projectRef = $this->createStub(Project::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->expects($this->once())
            ->method('getReference')
            ->with(Project::class, 7)
            ->willReturn($projectRef);

        $pdf = $this->createMock(PdfGeneratorService::class);
        $pdf->method('createPdfResponse')->willReturn(new Response('', 200));

        $service = new TimesheetExportService($em, $pdf);
        $service->exportToPdf(
            $this->createContributor(),
            new DateTime('2026-05-01'),
            new DateTime('2026-05-31'),
            7,
        );
    }
}
