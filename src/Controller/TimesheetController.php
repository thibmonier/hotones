<?php

namespace App\Controller;

use App\Entity\Timesheet;
use App\Entity\Project;
use App\Entity\Contributor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/timesheet')]
#[IsGranted('ROLE_INTERVENANT')]
class TimesheetController extends AbstractController
{
    #[Route('', name: 'timesheet_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $currentWeek = $request->query->get('week', date('Y-W'));
        $year = (int)substr($currentWeek, 0, 4);
        $week = (int)substr($currentWeek, -2);

        // Calculer les dates de début et fin de semaine
        $startDate = new \DateTime();
        $startDate->setISODate($year, $week, 1);
        $endDate = clone $startDate;
        $endDate->modify('+6 days');

        $projectRepo = $em->getRepository(Project::class);
        $contributorRepo = $em->getRepository(Contributor::class);
        $timesheetRepo = $em->getRepository(Timesheet::class);

        // Récupérer les projets actifs via repository
        $projects = $projectRepo->findActiveOrderedByName();

        // Récupérer les temps de la semaine pour l'utilisateur connecté via repository
        $contributor = $contributorRepo->findByUser($this->getUser());
        $timesheets = [];

        if ($contributor) {
            $timesheets = $timesheetRepo->findByContributorAndDateRange($contributor, $startDate, $endDate);
        }

        // Organiser les temps par projet et date
        $timesheetGrid = [];
        foreach ($timesheets as $timesheet) {
            $projectId = $timesheet->getProject()->getId();
            $date = $timesheet->getDate()->format('Y-m-d');
            $timesheetGrid[$projectId][$date] = $timesheet;
        }

        return $this->render('timesheet/index.html.twig', [
            'projects' => $projects,
            'contributor' => $contributor,
            'timesheetGrid' => $timesheetGrid,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currentWeek' => $currentWeek,
            'previousWeek' => $year . '-W' . str_pad($week - 1, 2, '0', STR_PAD_LEFT),
            'nextWeek' => $year . '-W' . str_pad($week + 1, 2, '0', STR_PAD_LEFT),
        ]);
    }

    #[Route('/save', name: 'timesheet_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $contributorRepo = $em->getRepository(Contributor::class);
        $timesheetRepo = $em->getRepository(Timesheet::class);

        $contributor = $contributorRepo->findByUser($this->getUser());
        if (!$contributor) {
            return new JsonResponse(['error' => 'Contributeur non trouvé'], 400);
        }

        $projectId = $request->request->get('project_id');
        $date = new \DateTime($request->request->get('date'));
        $hours = (float)$request->request->get('hours');
        $notes = $request->request->get('notes', '');

        $project = $em->getRepository(Project::class)->find($projectId);
        if (!$project) {
            return new JsonResponse(['error' => 'Projet non trouvé'], 400);
        }

        // Chercher un timesheet existant via repository
        $timesheet = $timesheetRepo->findExistingTimesheet($contributor, $project, $date);

        if (!$timesheet) {
            $timesheet = new Timesheet();
            $timesheet->setContributor($contributor);
            $timesheet->setProject($project);
            $timesheet->setDate($date);
        }

        if ($hours > 0) {
            $timesheet->setHours($hours);
            $timesheet->setNotes($notes);
            $em->persist($timesheet);
        } else {
            // Supprimer si 0 heure
            if ($timesheet->getId()) {
                $em->remove($timesheet);
            }
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/my-time', name: 'timesheet_my_time', methods: ['GET'])]
    public function myTime(Request $request, EntityManagerInterface $em): Response
    {
        $contributorRepo = $em->getRepository(Contributor::class);
        $timesheetRepo = $em->getRepository(Timesheet::class);

        $contributor = $contributorRepo->findByUser($this->getUser());
        if (!$contributor) {
            $this->addFlash('error', 'Aucun contributeur associé à votre compte.');
            return $this->redirectToRoute('home');
        }

        $month = $request->query->get('month', date('Y-m'));
        $startDate = new \DateTime($month . '-01');
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');

        // Utiliser le repository pour récupérer les temps
        $timesheets = $timesheetRepo->findByContributorAndDateRange($contributor, $startDate, $endDate);

        // Calculer les totaux via repository
        $projectTotals = $timesheetRepo->getHoursGroupedByProjectForContributor($contributor, $startDate, $endDate);
        $totalHours = array_sum(array_map(fn ($t) => $t->getHours(), $timesheets));

        return $this->render('timesheet/my_time.html.twig', [
            'timesheets' => $timesheets,
            'totalHours' => $totalHours,
            'projectTotals' => $projectTotals,
            'month' => $month,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    #[Route('/all', name: 'timesheet_all', methods: ['GET'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function all(Request $request, EntityManagerInterface $em): Response
    {
        $month = $request->query->get('month', date('Y-m'));
        $projectId = $request->query->get('project');

        $startDate = new \DateTime($month . '-01');
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');

        $projectRepo = $em->getRepository(Project::class);
        $timesheetRepo = $em->getRepository(Timesheet::class);

        // Utiliser le repository avec filtrage optionnel par projet
        $selectedProject = $projectId ? $projectRepo->find($projectId) : null;
        $timesheets = $timesheetRepo->findForPeriodWithProject($startDate, $endDate, $selectedProject);
        $projects = $projectRepo->findActiveOrderedByName();

        return $this->render('timesheet/all.html.twig', [
            'timesheets' => $timesheets,
            'projects' => $projects,
            'month' => $month,
            'selectedProject' => $projectId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
