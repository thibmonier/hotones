<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\Planning;
use App\Entity\Timesheet;
use App\Entity\Vacation;
use App\Repository\ContributorRepository;
use App\Security\CompanyContext;
use App\Service\Planning\TaceAnalyzer;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/planning')]
#[IsGranted('ROLE_CHEF_PROJET')]
class PlanningController extends AbstractController
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    #[Route('', name: 'planning_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ContributorRepository $contributorRepo, TaceAnalyzer $taceAnalyzer): Response
    {
        $weeks      = max(1, (int) $request->query->get('weeks', 8));
        $startParam = $request->query->get('start');

        // Filters (arrays)
        $selectedContributors = array_filter((array) $request->query->all('contributors'), fn ($v) => $v !== null && $v !== '');
        $selectedManagers     = array_filter((array) $request->query->all('project_managers'), fn ($v) => $v !== null && $v !== '');
        $selectedProjects     = array_filter((array) $request->query->all('projects'), fn ($v) => $v !== null && $v !== '');
        $selectedProjectTypes = array_filter((array) $request->query->all('project_types'), fn ($v) => $v !== null && $v !== '');

        $today = new DateTime('today');
        // Start at Monday of the current week by default
        $start = $startParam ? new DateTime($startParam) : (clone $today)->modify('monday this week');
        $end   = (clone $start)->modify('+'.($weeks * 7 - 1).' days');

        // Build timeline dates (inclusive)
        $period        = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));
        $timelineDates = iterator_to_array($period);

        // Base contributors list (optionally filtered)
        $contributors = $contributorRepo->findActiveContributors();
        if (!empty($selectedContributors)) {
            $contributors = array_values(array_filter($contributors, fn ($c) => in_array((string) $c->getId(), $selectedContributors, true)));
        }

        // Build query with filters
        $qb = $em->getRepository(Planning::class)->createQueryBuilder('p')
            ->leftJoin('p.contributor', 'c')->addSelect('c')
            ->leftJoin('p.project', 'pr')->addSelect('pr')
            ->leftJoin('p.profile', 'pf')->addSelect('pf')
            ->leftJoin('pr.projectManager', 'pm')
            ->where('p.endDate >= :start')
            ->andWhere('p.startDate <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC');

        if (!empty($selectedContributors)) {
            $qb->andWhere('c.id IN (:cids)')->setParameter('cids', $selectedContributors);
        }
        if (!empty($selectedManagers)) {
            $qb->andWhere('pm.id IN (:mids)')->setParameter('mids', $selectedManagers);
        }
        if (!empty($selectedProjects)) {
            $qb->andWhere('pr.id IN (:pids)')->setParameter('pids', $selectedProjects);
        }
        if (!empty($selectedProjectTypes)) {
            $qb->andWhere('pr.projectType IN (:ptypes)')->setParameter('ptypes', $selectedProjectTypes);
        }

        $plannings = $qb->getQuery()->getResult();

        // Collect available filters from result set
        $availableProjects = [];
        $availableManagers = [];

        // Group plannings by contributor id and by project
        $planningsByContributor = [];
        foreach ($plannings as $planning) {
            /** @var Planning $planning */
            $cid                                  = $planning->getContributor()->id;
            $project                              = $planning->getProject();
            $pid                                  = $project->id;
            $planningsByContributor[$cid][$pid][] = $planning;
            $availableProjects[$pid]              = $project; // deduplicate by id
            if ($project->projectManager) {
                $availableManagers[$project->projectManager->getId()] = $project->projectManager;
            }
        }

        // Compute planned hours totals per contributor per day within visible range
        $plannedByContributor = [];
        foreach ($plannings as $planning) {
            /** @var Planning $planning */
            if ($planning->getStatus() === 'cancelled') {
                continue;
            }
            $cid    = $planning->getContributor()->id;
            $ps     = $planning->getStartDate() < $start ? clone $start : clone $planning->getStartDate();
            $pe     = $planning->getEndDate() > $end ? clone $end : clone $planning->getEndDate();
            $cursor = $ps;
            while ($cursor <= $pe) {
                // weekdays only (Mon-Fri); 'w' Sunday=0, Saturday=6
                $w = (int) $cursor->format('w');
                if ($w !== 0 && $w !== 6) {
                    $key                              = $cursor->format('Y-m-d');
                    $plannedByContributor[$cid][$key] = ($plannedByContributor[$cid][$key] ?? 0) + (float) $planning->getDailyHours();
                }
                $cursor = (clone $cursor)->modify('+1 day');
            }
        }

        // Build totals of worked hours per contributor per day (Timesheets) within the range and filters
        $tsQb = $em->getRepository(Timesheet::class)->createQueryBuilder('t')
            ->select('IDENTITY(t.contributor) as cid, t.date as d, SUM(t.hours) as hours')
            ->leftJoin('t.project', 'tp')
            ->where('t.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('cid, t.date');
        if (!empty($selectedContributors)) {
            $tsQb->andWhere('t.contributor IN (:cids)')->setParameter('cids', $selectedContributors);
        }
        if (!empty($selectedProjects)) {
            $tsQb->andWhere('tp.id IN (:pids)')->setParameter('pids', $selectedProjects);
        }
        if (!empty($selectedProjectTypes)) {
            $tsQb->andWhere('tp.projectType IN (:ptypes)')->setParameter('ptypes', $selectedProjectTypes);
        }
        if (!empty($selectedManagers)) {
            $tsQb->leftJoin('tp.projectManager', 'tpm')->andWhere('tpm.id IN (:mids)')->setParameter('mids', $selectedManagers);
        }
        $tsRows              = $tsQb->getQuery()->getResult();
        $totalsByContributor = [];
        foreach ($tsRows as $r) {
            $cid                                 = (int) $r['cid'];
            $dateKey                             = $r['d']->format('Y-m-d');
            $totalsByContributor[$cid][$dateKey] = (float) $r['hours'];
        }

        // Fetch vacations in the date range
        $vacQb = $em->getRepository(Vacation::class)->createQueryBuilder('v')
            ->leftJoin('v.contributor', 'vc')->addSelect('vc')
            ->where('v.endDate >= :start')
            ->andWhere('v.startDate <= :end')
            ->andWhere('v.status = :approved')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('approved', 'approved');
        if (!empty($selectedContributors)) {
            $vacQb->andWhere('vc.id IN (:cids)')->setParameter('cids', $selectedContributors);
        }
        $vacations = $vacQb->getQuery()->getResult();

        // Group vacations by contributor
        $vacationsByContributor = [];
        foreach ($vacations as $vacation) {
            /** @var Vacation $vacation */
            $cid                            = $vacation->getContributor()->getId();
            $vacationsByContributor[$cid]   = $vacationsByContributor[$cid] ?? [];
            $vacationsByContributor[$cid][] = $vacation;
        }

        // Build a map of vacation days per contributor (for display)
        $vacationDaysByContributor = [];
        foreach ($vacations as $vacation) {
            /** @var Vacation $vacation */
            $cid    = $vacation->getContributor()->getId();
            $vs     = $vacation->getStartDate() < $start ? clone $start : clone $vacation->getStartDate();
            $ve     = $vacation->getEndDate() > $end ? clone $end : clone $vacation->getEndDate();
            $cursor = $vs;
            while ($cursor <= $ve) {
                $w = (int) $cursor->format('w');
                if ($w !== 0 && $w !== 6) {
                    $key                                   = $cursor->format('Y-m-d');
                    $vacationDaysByContributor[$cid][$key] = true;
                }
                $cursor = (clone $cursor)->modify('+1 day');
            }
        }

        // Fetch active employment periods for each contributor to get daily work hours
        $employmentPeriods = $em->getRepository(EmploymentPeriod::class)->createQueryBuilder('ep')
            ->leftJoin('ep.contributor', 'epc')->addSelect('epc')
            ->where('ep.startDate <= :end')
            ->andWhere('(ep.endDate IS NULL OR ep.endDate >= :start)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        // Map contributor ID to their daily work hours (average for the period)
        $contributorDailyHours = [];
        foreach ($employmentPeriods as $employmentPeriod) {
            /** @var EmploymentPeriod $employmentPeriod */
            $cid         = $employmentPeriod->contributor->id;
            $weeklyHours = (float) $employmentPeriod->weeklyHours;
            $workPct     = (float) $employmentPeriod->workTimePercentage;
            // Daily hours = (weekly hours * work% / 100) / 5 days
            $dailyHours                    = ($weeklyHours * $workPct / 100) / 5;
            $contributorDailyHours[$cid]   = $contributorDailyHours[$cid] ?? [];
            $contributorDailyHours[$cid][] = $dailyHours;
        }
        // Use the first active period's daily hours for simplicity
        foreach ($contributorDailyHours as $cid => $hours) {
            $contributorDailyHours[$cid] = $hours[0];
        }

        // Calculate weekly staffing rate per contributor
        // Group timeline_dates into weeks
        $weekGroups = [];
        foreach ($timelineDates as $date) {
            $weekKey                = $date->format('o-W'); // ISO-8601 week number
            $weekGroups[$weekKey]   = $weekGroups[$weekKey] ?? [];
            $weekGroups[$weekKey][] = $date;
        }

        $weeklyStaffing = [];
        foreach ($contributors as $contributor) {
            $cid            = $contributor->id;
            $dailyHours     = $contributorDailyHours[$cid] ?? 7.0;
            $weeklyMaxHours = $dailyHours * 5; // 5 working days
            foreach ($weekGroups as $weekKey => $dates) {
                $plannedHours = 0;
                foreach ($dates as $date) {
                    $key = $date->format('Y-m-d');
                    // Skip weekends
                    $w = (int) $date->format('w');
                    if ($w !== 0 && $w !== 6) {
                        $plannedHours += $plannedByContributor[$cid][$key] ?? 0;
                    }
                }
                // Calculate percentage
                $staffingPct                         = $weeklyMaxHours > 0 ? ($plannedHours / $weeklyMaxHours) * 100 : 0;
                $weeklyStaffing[$cid][$weekKey]      = $staffingPct;
                $weeklyStaffing[$cid][$weekKey.'_h'] = $plannedHours;
            }
        }

        // Build grouped structure: one summary row per contributor + project rows
        $groups = [];
        foreach ($contributors as $contributor) {
            $cid         = $contributor->id;
            $projectRows = [];
            if (isset($planningsByContributor[$cid])) {
                foreach ($planningsByContributor[$cid] as $pid => $items) {
                    $projectRows[] = [
                        'project' => $items[0]->getProject(),
                        'items'   => $items,
                    ];
                }
            }
            // Always include all active contributors to allow creating new plannings
            $groups[] = [
                'contributor' => $contributor,
                'totals'      => $totalsByContributor[$cid] ?? [],
                'projectRows' => $projectRows,
            ];
        }

        // Get all projects for creation dropdown (only active projects with minimal data)
        $allProjects = $em->getRepository(\App\Entity\Project::class)->findBy(
            ['status' => 'active'],
            ['name' => 'ASC'],
        );

        // Quick TACE analysis for optimization alerts (only for managers)
        $taceAnalysis = null;
        if ($this->isGranted('ROLE_MANAGER')) {
            try {
                $analysisStart = new DateTime('first day of this month');
                $analysisEnd   = new DateTime('last day of next month');
                $taceAnalysis  = $taceAnalyzer->analyzeAllContributors($analysisStart, $analysisEnd);
            } catch (Exception $e) {
                // Silently fail if analysis fails (missing metrics data)
                $taceAnalysis = ['critical' => [], 'overloaded' => [], 'underutilized' => [], 'optimal' => []];
            }
        }

        return $this->render('planning/index.html.twig', [
            'contributors'                  => $contributors,
            'timeline_start'                => $start,
            'timeline_end'                  => $end,
            'timeline_dates'                => $timelineDates,
            'groups'                        => $groups,
            'planned_totals_by_contributor' => $plannedByContributor,
            'vacations_by_contributor'      => $vacationsByContributor,
            'vacation_days_by_contributor'  => $vacationDaysByContributor,
            'contributor_daily_hours'       => $contributorDailyHours,
            'week_groups'                   => $weekGroups,
            'weekly_staffing'               => $weeklyStaffing,
            // filter UI data
            'all_contributors'          => $contributorRepo->findActiveContributors(),
            'all_projects'              => $allProjects,
            'all_project_managers'      => array_values($availableManagers),
            'project_types'             => ['forfait' => 'Forfait', 'regie' => 'Régie'],
            'selected_contributors'     => $selectedContributors,
            'selected_project_managers' => $selectedManagers,
            'selected_projects'         => $selectedProjects,
            'selected_project_types'    => $selectedProjectTypes,
            'tace_analysis'             => $taceAnalysis,
        ]);
    }

    #[Route('/create', name: 'planning_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $token = $data['_token']                           ?? null;
        if (!$this->isCsrfTokenValid('create_planning', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        // Validate required fields
        if (!isset($data['contributorId']) || !isset($data['projectId']) || !isset($data['startDate']) || !isset($data['endDate'])) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        // Get contributor and project
        $contributor = $em->getRepository(Contributor::class)->find($data['contributorId']);
        $project     = $em->getRepository(\App\Entity\Project::class)->find($data['projectId']);

        if (!$contributor || !$project) {
            return new JsonResponse(['error' => 'Contributor or Project not found'], 400);
        }

        // Create new planning
        $planning = new Planning();
        $planning->setCompany($this->companyContext->getCurrentCompany());
        $planning->setContributor($contributor);
        $planning->setProject($project);
        $planning->setStartDate(new DateTime($data['startDate']));
        $planning->setEndDate(new DateTime($data['endDate']));
        $planning->setDailyHours($data['dailyHours'] ?? '7.00');
        $planning->setStatus($data['status'] ?? 'planned');
        $planning->setNotes($data['notes'] ?? null);

        // Validate capacity
        $validation = $this->validatePlanningCapacity($planning, $em);
        if (!$validation['valid']) {
            return new JsonResponse(['error' => $validation['message']], 400);
        }

        $em->persist($planning);
        $em->flush();

        return new JsonResponse([
            'ok'        => true,
            'id'        => $planning->getId(),
            'startDate' => $planning->getStartDate()->format('Y-m-d'),
            'endDate'   => $planning->getEndDate()->format('Y-m-d'),
        ]);
    }

    #[Route('/{id}/move', name: 'planning_move', methods: ['POST'])]
    public function move(Request $request, Planning $planning, EntityManagerInterface $em): JsonResponse
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $token = $data['_token']                           ?? null;
        if (!$this->isCsrfTokenValid('move_planning_'.$planning->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        $targetDateStr = $data['targetDate'] ?? null;
        if (!$targetDateStr) {
            return new JsonResponse(['error' => 'targetDate required'], 400);
        }
        $targetDate = new DateTime($targetDateStr);

        $oldStart     = clone $planning->getStartDate();
        $oldEnd       = clone $planning->getEndDate();
        $durationDays = (int) $oldStart->diff($oldEnd)->format('%a');

        $planning->setStartDate($targetDate);
        $newEnd = (clone $targetDate)->modify('+'.$durationDays.' days');
        $planning->setEndDate($newEnd);
        $planning->setUpdatedAt(new DateTime());
        $em->flush();

        return new JsonResponse([
            'ok'        => true,
            'startDate' => $planning->getStartDate()->format('Y-m-d'),
            'endDate'   => $planning->getEndDate()->format('Y-m-d'),
        ]);
    }

    #[Route('/{id}/update', name: 'planning_update', methods: ['POST'])]
    public function update(Request $request, Planning $planning, EntityManagerInterface $em): JsonResponse
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $token = $data['_token']                           ?? null;
        if (!$this->isCsrfTokenValid('edit_planning_'.$planning->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        if (isset($data['dailyHours'])) {
            $planning->setDailyHours((string) $data['dailyHours']);
        }
        if (isset($data['notes'])) {
            $planning->setNotes($data['notes']);
        }
        if (isset($data['status'])) {
            $planning->setStatus($data['status']);
        }
        if (isset($data['startDate'])) {
            $planning->setStartDate(new DateTime($data['startDate']));
        }
        if (isset($data['endDate'])) {
            $planning->setEndDate(new DateTime($data['endDate']));
        }

        // Validate daily hours don't exceed contributor's capacity
        $validation = $this->validatePlanningCapacity($planning, $em);
        if (!$validation['valid']) {
            return new JsonResponse(['error' => $validation['message']], 400);
        }

        $planning->setUpdatedAt(new DateTime());
        $em->flush();

        return new JsonResponse(['ok' => true]);
    }

    /**
     * Validates that a planning's daily hours don't exceed the contributor's work capacity.
     */
    private function validatePlanningCapacity(Planning $planning, EntityManagerInterface $em): array
    {
        $contributor = $planning->getContributor();
        $dailyHours  = (float) $planning->getDailyHours();

        // Get active employment period for this contributor
        $period = $em->getRepository(EmploymentPeriod::class)->createQueryBuilder('ep')
            ->where('ep.contributor = :contributor')
            ->andWhere('ep.startDate <= :date')
            ->andWhere('(ep.endDate IS NULL OR ep.endDate >= :date)')
            ->setParameter('contributor', $contributor)
            ->setParameter('date', $planning->getStartDate())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$period) {
            // No active period found, use default 7h
            $maxDailyHours = 7.0;
        } else {
            $weeklyHours   = (float) $period->getWeeklyHours();
            $workPct       = (float) $period->getWorkTimePercentage();
            $maxDailyHours = ($weeklyHours * $workPct / 100) / 5;
        }

        if ($dailyHours > $maxDailyHours) {
            return [
                'valid'   => false,
                'message' => sprintf(
                    'La durée quotidienne (%.2fh) dépasse la capacité du collaborateur (%.2fh/jour)',
                    $dailyHours,
                    $maxDailyHours,
                ),
            ];
        }

        return ['valid' => true];
    }

    #[Route('/{id}/split', name: 'planning_split', methods: ['POST'])]
    public function split(Request $request, Planning $planning, EntityManagerInterface $em): JsonResponse
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $token = $data['_token']                           ?? null;
        if (!$this->isCsrfTokenValid('split_planning_'.$planning->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        $splitDateStr = $data['splitDate'] ?? null;
        if (!$splitDateStr) {
            return new JsonResponse(['error' => 'splitDate required'], 400);
        }

        $splitDate = new DateTime($splitDateStr);
        $startDate = $planning->getStartDate();
        $endDate   = $planning->getEndDate();

        // Validate split date is within the planning range
        if ($splitDate <= $startDate || $splitDate > $endDate) {
            return new JsonResponse(['error' => 'La date de division doit être comprise entre le début et la fin de la planification'], 400);
        }

        // Create the second part (from splitDate to endDate)
        $planning2 = new Planning();
        $planning2->setCompany($planning->getCompany());
        $planning2->setContributor($planning->getContributor());
        $planning2->setProject($planning->getProject());
        $planning2->setStartDate($splitDate);
        $planning2->setEndDate($endDate);
        $planning2->setDailyHours($planning->getDailyHours());
        $planning2->setProfile($planning->getProfile());
        $planning2->setNotes($planning->getNotes());
        $planning2->setStatus($planning->getStatus());
        $em->persist($planning2);

        // Update the first part (startDate to splitDate - 1 day)
        $newEndDate = (clone $splitDate)->modify('-1 day');
        $planning->setEndDate($newEndDate);
        $planning->setUpdatedAt(new DateTime());

        $em->flush();

        return new JsonResponse([
            'ok'           => true,
            'original_id'  => $planning->getId(),
            'new_id'       => $planning2->getId(),
            'original_end' => $planning->getEndDate()->format('Y-m-d'),
            'new_start'    => $planning2->getStartDate()->format('Y-m-d'),
            'new_end'      => $planning2->getEndDate()->format('Y-m-d'),
        ]);
    }
}
