<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Repository\ContributorRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my-tasks')]
#[IsGranted('ROLE_INTERVENANT')]
class MyTasksController extends AbstractController
{
    #[Route('', name: 'my_tasks_index', methods: ['GET'])]
    public function index(Request $request, ContributorRepository $contributorRepo): Response
    {
        $currentWeek = $request->query->get('week', date('Y-W'));
        $year        = (int) substr($currentWeek, 0, 4);
        $week        = (int) substr($currentWeek, -2);

        $startDate = new DateTime();
        $startDate->setISODate($year, $week, 1); // Monday
        $endDate = (clone $startDate)->modify('+6 days'); // Sunday

        // Logged in contributor
        $contributor = $contributorRepo->findByUser($this->getUser());
        if (!$contributor instanceof Contributor) {
            $this->addFlash('error', 'Aucun contributeur associé à votre compte.');

            return $this->redirectToRoute('home');
        }

        // Fetch assigned projects with tasks
        $projectsWithTasks = $contributorRepo->findProjectsWithTasksForContributor($contributor);

        // Filter tasks to those overlapping the selected week (or with no dates)
        $filtered = [];
        foreach ($projectsWithTasks as $row) {
            $project = $row['project'];
            $tasks   = $row['tasks'];

            $tasksForWeek = array_values(array_filter($tasks, function ($t) use ($startDate, $endDate) {
                $ts = $t->getStartDate();
                $te = $t->getEndDate();
                if (!$ts && !$te) {
                    return true; // keep tasks without dates
                }
                if ($ts && $ts > $endDate) {
                    return false;
                }
                if ($te && $te < $startDate) {
                    return false;
                }

                return true;
            }));

            if (!empty($tasksForWeek)) {
                $filtered[] = [
                    'project' => $project,
                    'tasks'   => $tasksForWeek,
                ];
            }
        }

        return $this->render('my_tasks/index.html.twig', [
            'contributor'  => $contributor,
            'groups'       => $filtered,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
            'currentWeek'  => $currentWeek,
            'previousWeek' => $year.'-W'.str_pad($week - 1, 2, '0', STR_PAD_LEFT),
            'nextWeek'     => $year.'-W'.str_pad($week + 1, 2, '0', STR_PAD_LEFT),
        ]);
    }
}
