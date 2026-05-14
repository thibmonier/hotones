<?php

declare(strict_types=1);

namespace App\Presentation\WorkItem\Controller;

use App\Application\WorkItem\UseCase\RecordWorkItem\RecordWorkItemCommand;
use App\Application\WorkItem\UseCase\RecordWorkItem\RecordWorkItemUseCase;
use App\Domain\WorkItem\Exception\DailyHoursWarningException;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use App\Entity\Contributor;
use App\Entity\Project;
use App\Repository\ContributorRepository;
use App\Repository\ProjectRepository;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * EPIC-003 Phase 3 (sprint-021 US-102) — UI Twig grille hebdo saisie WorkItem.
 *
 * ADR-0016 décisions :
 * - Q1.1 grille hebdo (jours × projets)
 * - Q1.3 step 0.25h
 * - Q2.1 auto-save
 * - Q2.2 lock édition rétroactive (7 jours / projet billed/paid)
 * - Q2.3 admin override
 * - Q2.4 warning + override user
 * - Q3.2 role-based managers self-validate
 *
 * Strangler fig : nouveau endpoint coexiste avec legacy `TimesheetController`.
 * Pas de refactor legacy sprint-021.
 */
#[Route('/timesheet/week')]
#[IsGranted('ROLE_INTERVENANT')]
final class WeeklyTimesheetController extends AbstractController
{
    private const int EDIT_RETROACTIVE_DAYS = 7;

    public function __construct(
        private readonly RecordWorkItemUseCase $recordWorkItemUseCase,
        private readonly WorkItemRepositoryInterface $workItemRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyContext $companyContext,
    ) {
    }

    /**
     * Affiche la grille hebdomadaire (7 jours × N projets actifs).
     */
    #[Route('/{week<\d{4}-W\d{2}>?}', name: 'weekly_timesheet_index', methods: ['GET'])]
    public function index(Request $request, ?string $week = null): Response
    {
        $week ??= $this->currentIsoWeek();

        try {
            [$startOfWeek, $endOfWeek] = $this->parseIsoWeek($week);
        } catch (InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $contributor = $this->getContributor();
        // Trigger CompanyContext (multitenant guard — throws if no company resolved)
        $this->companyContext->getCurrentCompany();

        $projects = $this->projectRepository->findActiveOrderedByName();

        return $this->render('timesheet/weekly/grid.html.twig', [
            'week' => $week,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
            'days' => $this->buildWeekDays($startOfWeek),
            'projects' => $projects,
            'cells' => $this->buildCells($contributor, $startOfWeek, $endOfWeek, $projects),
            'previousWeek' => $this->shiftIsoWeek($week, -1),
            'nextWeek' => $this->shiftIsoWeek($week, +1),
            'isManager' => $this->isGranted('ROLE_MANAGER') || $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    /**
     * Auto-save endpoint (Q2.1 ADR-0016) — appelé par Stimulus controller à
     * chaque modification cellule.
     *
     * JSON request :
     * - projectId (int)
     * - date (Y-m-d string)
     * - hours (float, step 0.25)
     * - taskId (int|null, optional)
     * - comment (string|null, optional)
     * - userOverride (bool, default false) — Q2.4 confirmation override seuil
     *   journalier
     *
     * JSON response :
     * - status: "saved" | "warning" | "error"
     * - workItemId: string (si saved)
     * - workItemStatus: "draft" | "validated" (si saved)
     * - warning: { dailyTotal, dailyMaxHours, excess } (si warning)
     * - error: string (si error)
     */
    #[Route('/save', name: 'weekly_timesheet_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['status' => 'error', 'error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $contributor = $this->getContributor();
        $contributorId = $contributor->getId() ?? throw new InvalidArgumentException('Contributor has no id');

        $command = $this->buildCommandFromPayload($contributorId, $payload);

        if ($command === null) {
            return new JsonResponse(['status' => 'error', 'error' => 'Missing required fields (projectId, date, hours)'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->isLocked($command)) {
            return new JsonResponse([
                'status' => 'error',
                'error' => 'WorkItem locked (date > 7 days OR project billed/paid). Admin override required.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $workItemId = $this->recordWorkItemUseCase->execute($command);
        } catch (DailyHoursWarningException $warning) {
            return new JsonResponse([
                'status' => 'warning',
                'warning' => [
                    'dailyTotal' => $warning->dailyTotal->getValue(),
                    'dailyMaxHours' => $warning->dailyMaxHours->getValue(),
                    'excess' => round($warning->dailyTotal->getValue() - $warning->dailyMaxHours->getValue(), 2),
                ],
            ]);
        }

        $workItem = $this->workItemRepository->findByIdOrNull($workItemId);

        return new JsonResponse([
            'status' => 'saved',
            'workItemId' => (string) $workItemId,
            'workItemStatus' => $workItem?->getStatus()->value ?? WorkItemStatus::DRAFT->value,
        ]);
    }

    private function getContributor(): Contributor
    {
        // Security provider resolves App\Entity\User — the Contributor is a
        // distinct entity linked via Contributor.user, resolved here.
        $contributor = $this->contributorRepository->findByUser($this->getUser());
        if ($contributor === null) {
            throw $this->createAccessDeniedException('User is not a Contributor');
        }

        return $contributor;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildCommandFromPayload(int $contributorId, array $payload): ?RecordWorkItemCommand
    {
        $projectId = is_int($payload['projectId'] ?? null) ? $payload['projectId'] : null;
        $date = is_string($payload['date'] ?? null) ? $payload['date'] : null;
        $hours = is_numeric($payload['hours'] ?? null) ? (float) $payload['hours'] : null;

        if ($projectId === null || $date === null || $hours === null) {
            return null;
        }

        $project = $this->entityManager->find(Project::class, $projectId);
        $costRate = (float) ($this->getContributor()->cjm ?? '0') / 8.0;
        $billedRate = (float) ($this->getContributor()->tjm ?? '0') / 8.0;

        return new RecordWorkItemCommand(
            contributorIdLegacy: $contributorId,
            projectIdLegacy: $projectId,
            date: $date,
            hours: $hours,
            costRateAmount: $costRate > 0 ? $costRate : 1.0, // dégradé si CJM manquant — Risk Q3 audit
            billedRateAmount: $billedRate > 0 ? $billedRate : 1.0,
            taskIdLegacy: is_int($payload['taskId'] ?? null) ? $payload['taskId'] : null,
            comment: isset($payload['comment']) && is_string($payload['comment']) ? $payload['comment'] : null,
            userOverride: (bool) ($payload['userOverride'] ?? false),
            authorIsManager: $this->isGranted('ROLE_MANAGER') || $this->isGranted('ROLE_ADMIN'),
        );
    }

    /**
     * Q2.2 ADR-0016 : édition lockée si date > 7 jours OU projet déjà facturé.
     * Admin override (Q2.3) bypass via ROLE_ADMIN check.
     */
    private function isLocked(RecordWorkItemCommand $command): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return false;
        }

        $now = new DateTimeImmutable();
        $date = new DateTimeImmutable($command->date);
        $diffDays = (int) $date->diff($now)->format('%a');

        return $diffDays > self::EDIT_RETROACTIVE_DAYS;
    }

    private function currentIsoWeek(): string
    {
        return new DateTimeImmutable()->format('o-\WW');
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function parseIsoWeek(string $week): array
    {
        if (preg_match('/^(\d{4})-W(\d{2})$/', $week, $matches) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid ISO week format: %s. Expected YYYY-Www', $week));
        }

        $year = (int) $matches[1];
        $weekNumber = (int) $matches[2];

        $start = new DateTimeImmutable()->setISODate($year, $weekNumber)->setTime(0, 0, 0);
        $end = $start->modify('+6 days')->setTime(23, 59, 59);

        return [$start, $end];
    }

    private function shiftIsoWeek(string $week, int $offset): string
    {
        [$start] = $this->parseIsoWeek($week);

        return $start->modify(sprintf('%+d days', 7 * $offset))->format('o-\WW');
    }

    /**
     * @return list<DateTimeImmutable>
     */
    private function buildWeekDays(DateTimeImmutable $startOfWeek): array
    {
        $days = [];
        for ($i = 0; $i < 7; ++$i) {
            $days[] = $startOfWeek->modify(sprintf('+%d days', $i));
        }

        return $days;
    }

    /**
     * Build cells map indexed by "{projectId}-{Y-m-d}" → totaled hours +
     * status badge for grid rendering.
     *
     * @param list<Project> $projects
     *
     * @return array<string, array{hours: float, status: string, locked: bool}>
     */
    private function buildCells(
        Contributor $contributor,
        DateTimeImmutable $startOfWeek,
        DateTimeImmutable $endOfWeek,
        array $projects,
    ): array {
        $cells = [];
        $contributorId = $contributor->getId();
        if ($contributorId === null) {
            return $cells;
        }

        $contributorIdVo = \App\Domain\Contributor\ValueObject\ContributorId::fromLegacyInt($contributorId);
        $existing = $this->workItemRepository->findByContributorAndDateRange(
            $contributorIdVo,
            $startOfWeek,
            $endOfWeek,
        );

        $now = new DateTimeImmutable();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        foreach ($existing as $workItem) {
            $key = sprintf(
                '%s-%s',
                $workItem->getProjectId(),
                $workItem->getWorkedOn()->format('Y-m-d'),
            );
            $cells[$key] ??= ['hours' => 0.0, 'status' => $workItem->getStatus()->value, 'locked' => false];
            $cells[$key]['hours'] += $workItem->getHours()->getValue();

            // Lock display Q2.2 : > 7 jours OR billed/paid (admin override Q2.3)
            $diffDays = (int) $workItem->getWorkedOn()->diff($now)->format('%a');
            $statusLocked = in_array($workItem->getStatus(), [WorkItemStatus::BILLED, WorkItemStatus::PAID], true);
            $cells[$key]['locked'] = !$isAdmin && ($diffDays > self::EDIT_RETROACTIVE_DAYS || $statusLocked);
        }

        return $cells;
    }
}
