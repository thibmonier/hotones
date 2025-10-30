<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\Planning;
use App\Entity\Timesheet;
use App\Repository\ContributorRepository;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/planning')]
#[IsGranted('ROLE_CHEF_PROJET')]
class PlanningController extends AbstractController
{
    #[Route('', name: 'planning_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ContributorRepository $contributorRepo): Response
    {
        $weeks      = max(1, (int) $request->query->get('weeks', 8));
        $startParam = $request->query->get('start');

        // Filters (arrays)
        $selectedContributors = array_filter((array) $request->query->all('contributors'));
        $selectedManagers     = array_filter((array) $request->query->all('project_managers'));
        $selectedProjects     = array_filter((array) $request->query->all('projects'));
        $selectedProjectTypes = array_filter((array) $request->query->all('project_types'));

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
            ->orderBy('c.name', 'ASC');

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
            $cid                                  = $planning->getContributor()->getId();
            $project                              = $planning->getProject();
            $pid                                  = $project->getId();
            $planningsByContributor[$cid][$pid][] = $planning;
            $availableProjects[$pid]              = $project; // deduplicate by id
            if ($project->getProjectManager()) {
                $availableManagers[$project->getProjectManager()->getId()] = $project->getProjectManager();
            }
        }

        // Compute planned hours totals per contributor per day within visible range
        $plannedByContributor = [];
        foreach ($plannings as $planning) {
            /** @var Planning $planning */
            if ($planning->getStatus() === 'cancelled') {
                continue;
            }
            $cid    = $planning->getContributor()->getId();
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

        // Build grouped structure: one summary row per contributor + project rows
        $groups = [];
        foreach ($contributors as $contributor) {
            $cid         = $contributor->getId();
            $projectRows = [];
            if (isset($planningsByContributor[$cid])) {
                foreach ($planningsByContributor[$cid] as $pid => $items) {
                    $projectRows[] = [
                        'project' => $items[0]->getProject(),
                        'items'   => $items,
                    ];
                }
            }
            // Only include contributors that have either plannings or timesheets in range
            $hasAny = !empty($projectRows) || isset($totalsByContributor[$cid]);
            if ($hasAny) {
                $groups[] = [
                    'contributor' => $contributor,
                    'totals'      => $totalsByContributor[$cid] ?? [],
                    'projectRows' => $projectRows,
                ];
            }
        }

        return $this->render('planning/index.html.twig', [
            'contributors'                  => $contributors,
            'timeline_start'                => $start,
            'timeline_end'                  => $end,
            'timeline_dates'                => $timelineDates,
            'groups'                        => $groups,
            'planned_totals_by_contributor' => $plannedByContributor,
            // filter UI data
            'all_contributors'          => $contributorRepo->findActiveContributors(),
            'all_projects'              => array_values($availableProjects),
            'all_project_managers'      => array_values($availableManagers),
            'project_types'             => ['forfait' => 'Forfait', 'regie' => 'Régie'],
            'selected_contributors'     => $selectedContributors,
            'selected_project_managers' => $selectedManagers,
            'selected_projects'         => $selectedProjects,
            'selected_project_types'    => $selectedProjectTypes,
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

        $planning->setUpdatedAt(new DateTime());
        $em->flush();

        return new JsonResponse(['ok' => true]);
    }
}
