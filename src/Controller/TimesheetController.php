<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\RunningTimer;
use App\Entity\Timesheet;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $year        = (int) substr($currentWeek, 0, 4);
        $week        = (int) substr($currentWeek, -2);

        // Calculer les dates de début et fin de semaine
        $startDate = new DateTime();
        $startDate->setISODate($year, $week, 1);
        $endDate = clone $startDate;
        $endDate->modify('+6 days');

        $projectRepo     = $em->getRepository(Project::class);
        $contributorRepo = $em->getRepository(Contributor::class);
        $timesheetRepo   = $em->getRepository(Timesheet::class);

        // Récupérer les temps de la semaine pour l'utilisateur connecté via repository
        $contributor       = $contributorRepo->findByUser($this->getUser());
        $timesheets        = [];
        $projectsWithTasks = [];

        if ($contributor) {
            // Récupérer les projets avec tâches assignées au contributeur
            $projectsWithTasks = $contributorRepo->findProjectsWithTasksForContributor($contributor);

            // Ajouter les sous-tâches assignées pour chaque tâche
            foreach ($projectsWithTasks as &$row) {
                $taskList = [];
                foreach ($row['tasks'] as $task) {
                    $subTasks = $em->createQueryBuilder()
                        ->select('st')
                        ->from('App\\Entity\\ProjectSubTask', 'st')
                        ->where('st.task = :task')
                        ->andWhere('st.assignee = :contributor')
                        ->setParameter('task', $task)
                        ->setParameter('contributor', $contributor)
                        ->orderBy('st.position', 'ASC')
                        ->getQuery()
                        ->getResult();
                    $taskList[] = [
                        'task'     => $task,
                        'subTasks' => $subTasks,
                    ];
                }
                $row['taskRows'] = $taskList;
            }
            unset($row);

            $timesheets = $timesheetRepo->findByContributorAndDateRange($contributor, $startDate, $endDate);
        }

        // Organiser les temps par projet, tâche et date
        $timesheetGrid = [];
        foreach ($timesheets as $timesheet) {
            $projectId                                 = $timesheet->getProject()->getId();
            $taskId                                    = $timesheet->getTask() ? $timesheet->getTask()->getId() : 'no_task';
            $date                                      = $timesheet->getDate()->format('Y-m-d');
            $timesheetGrid[$projectId][$taskId][$date] = $timesheet;
            // Carte séparée pour sous-tâches (si présentes)
            if ($timesheet->getSubTask()) {
                $subId                                           = $timesheet->getSubTask()->getId();
                $timesheetGrid['sub'][$projectId][$subId][$date] = $timesheet;
            }
        }

        // Timer en cours pour l'utilisateur (si existant)
        $activeTimer = null;
        if ($contributor) {
            $activeTimer = $em->getRepository(RunningTimer::class)->findActiveByContributor($contributor);
        }

        return $this->render('timesheet/index.html.twig', [
            'projectsWithTasks' => $projectsWithTasks,
            'contributor'       => $contributor,
            'timesheetGrid'     => $timesheetGrid,
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'currentWeek'       => $currentWeek,
            'previousWeek'      => $year.'-W'.str_pad($week - 1, 2, '0', STR_PAD_LEFT),
            'nextWeek'          => $year.'-W'.str_pad($week + 1, 2, '0', STR_PAD_LEFT),
            'activeTimer'       => $activeTimer,
        ]);
    }

    #[Route('/save', name: 'timesheet_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $contributorRepo = $em->getRepository(Contributor::class);
        $timesheetRepo   = $em->getRepository(Timesheet::class);

        $contributor = $contributorRepo->findByUser($this->getUser());
        if (!$contributor) {
            return new JsonResponse(['error' => 'Contributeur non trouvé'], 400);
        }

        $projectId = $request->request->get('project_id');
        $taskId    = $request->request->get('task_id');
        $date      = new DateTime($request->request->get('date'));
        $hours     = (float) $request->request->get('hours');
        $notes     = $request->request->get('notes', '');

        $project = $em->getRepository(Project::class)->find($projectId);
        if (!$project) {
            return new JsonResponse(['error' => 'Projet non trouvé'], 400);
        }

        $task = null;
        if ($taskId) {
            $task = $em->getRepository(ProjectTask::class)->find($taskId);
            if (!$task) {
                return new JsonResponse(['error' => 'Tâche non trouvée'], 400);
            }
            // Vérifier que la tâche appartient au projet
            if ($task->getProject()->getId() !== $project->getId()) {
                return new JsonResponse(['error' => 'La tâche ne correspond pas au projet'], 400);
            }
        }

        // Chercher un timesheet existant via repository (avec tâche si spécifiée)
        $timesheet = $timesheetRepo->findExistingTimesheetWithTask($contributor, $project, $date, $task);

        if (!$timesheet) {
            $timesheet = new Timesheet();
            $timesheet->setContributor($contributor);
            $timesheet->setProject($project);
            $timesheet->setDate($date);
            if ($task) {
                $timesheet->setTask($task);
            }
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
        $timesheetRepo   = $em->getRepository(Timesheet::class);

        $contributor = $contributorRepo->findByUser($this->getUser());
        if (!$contributor) {
            $this->addFlash('error', 'Aucun contributeur associé à votre compte.');

            return $this->redirectToRoute('home');
        }

        $month     = $request->query->get('month', date('Y-m'));
        $startDate = new DateTime($month.'-01');
        $endDate   = clone $startDate;
        $endDate->modify('last day of this month');

        // Utiliser le repository pour récupérer les temps
        $timesheets = $timesheetRepo->findByContributorAndDateRange($contributor, $startDate, $endDate);

        // Calculer les totaux via repository
        $projectTotals = $timesheetRepo->getHoursGroupedByProjectForContributor($contributor, $startDate, $endDate);
        $totalHours    = array_sum(array_map(fn ($t) => $t->getHours(), $timesheets));

        return $this->render('timesheet/my_time.html.twig', [
            'timesheets'    => $timesheets,
            'totalHours'    => $totalHours,
            'projectTotals' => $projectTotals,
            'month'         => $month,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
        ]);
    }

    #[Route('/all', name: 'timesheet_all', methods: ['GET'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function all(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);
        if ($reset && $session) {
            $session->remove('timesheet_all_filters');

            return $this->redirectToRoute('timesheet_all');
        }

        $queryAll = $request->query->all();
        $keys     = ['month', 'project'];
        $has      = count(array_intersect(array_keys($queryAll), $keys)) > 0;
        $saved    = ($session && $session->has('timesheet_all_filters')) ? (array) $session->get('timesheet_all_filters') : [];

        $month     = $has ? ($request->query->get('month', date('Y-m'))) : ($saved['month'] ?? date('Y-m'));
        $projectId = $has ? ($request->query->get('project')) : ($saved['project'] ?? null);

        $startDate = new DateTime($month.'-01');
        $endDate   = clone $startDate;
        $endDate->modify('last day of this month');

        $projectRepo   = $em->getRepository(Project::class);
        $timesheetRepo = $em->getRepository(Timesheet::class);

        // Utiliser le repository avec filtrage optionnel par projet
        $selectedProject = $projectId ? $projectRepo->find($projectId) : null;
        $timesheets      = $timesheetRepo->findForPeriodWithProject($startDate, $endDate, $selectedProject);
        $projects        = $projectRepo->findActiveOrderedByName();

        if ($session) {
            $session->set('timesheet_all_filters', [
                'month'   => $month,
                'project' => $projectId,
            ]);
        }

        // Calculer le nombre de filtres actifs
        $activeFiltersCount = 0;
        if ($projectId) {
            ++$activeFiltersCount;
        }
        if ($month !== date('Y-m')) {
            ++$activeFiltersCount;
        }

        return $this->render('timesheet/all.html.twig', [
            'timesheets'         => $timesheets,
            'projects'           => $projects,
            'month'              => $month,
            'selectedProject'    => $projectId,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'activeFiltersCount' => $activeFiltersCount,
        ]);
    }

    /**
     * Démarre un compteur de temps pour un projet/tâche.
     * Si un compteur est déjà actif, il est arrêté et imputé automatiquement avant de démarrer le nouveau.
     */
    #[Route('/timer/start', name: 'timesheet_timer_start', methods: ['POST'])]
    public function startTimer(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $contributor = $em->getRepository(Contributor::class)->findByUser($this->getUser());
        if (!$contributor) {
            return new JsonResponse(['error' => 'Contributeur non trouvé'], 400);
        }

        $projectId = (int) $request->request->get('project_id');
        $taskId    = $request->request->get('task_id');
        $subTaskId = $request->request->get('sub_task_id');

        $project = $em->getRepository(Project::class)->find($projectId);
        if (!$project) {
            return new JsonResponse(['error' => 'Projet non trouvé'], 400);
        }

        $task = null;
        if ($taskId) {
            $task = $em->getRepository(ProjectTask::class)->find($taskId);
            if (!$task) {
                return new JsonResponse(['error' => 'Tâche non trouvée'], 400);
            }
            if ($task->getProject()->getId() !== $project->getId()) {
                return new JsonResponse(['error' => 'La tâche ne correspond pas au projet'], 400);
            }
        }

        $subTask = null;
        if ($subTaskId) {
            $subTask = $em->getRepository(\App\Entity\ProjectSubTask::class)->find($subTaskId);
            if (!$subTask) {
                return new JsonResponse(['error' => 'Sous-tâche non trouvée'], 400);
            }
            if (($task && $subTask->getTask()->getId() !== $task->getId()) || $subTask->getProject()->getId() !== $project->getId()) {
                return new JsonResponse(['error' => 'La sous-tâche ne correspond pas au projet/tâche'], 400);
            }
        }

        $timerRepo = $em->getRepository(RunningTimer::class);
        $active    = $timerRepo->findActiveByContributor($contributor);

        // Si un timer est actif, l'arrêter et l'imputer
        if ($active) {
            $this->finalizeTimer($active, $em);
        }

        // Démarrer un nouveau timer
        $timer = new RunningTimer();
        $timer->setContributor($contributor)
            ->setProject($project)
            ->setTask($task)
            ->setSubTask($subTask)
            ->setStartedAt(new DateTime());

        $em->persist($timer);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'timer'   => [
                'id'         => $timer->getId(),
                'project'    => ['id' => $project->getId(), 'name' => $project->getName()],
                'task'       => $task ? ['id' => $task->getId(), 'name' => $task->getName()] : null,
                'subTask'    => $subTask ? ['id' => $subTask->getId(), 'title' => $subTask->getTitle()] : null,
                'started_at' => $timer->getStartedAt()->format(DateTimeInterface::ATOM),
            ],
        ]);
    }

    /** Arrête le compteur actif et impute le temps aux timesheets (min 0,125j = 1h). */
    #[Route('/timer/stop', name: 'timesheet_timer_stop', methods: ['POST'])]
    public function stopTimer(EntityManagerInterface $em): JsonResponse
    {
        $contributor = $em->getRepository(Contributor::class)->findByUser($this->getUser());
        if (!$contributor) {
            return new JsonResponse(['error' => 'Contributeur non trouvé'], 400);
        }

        $timer = $em->getRepository(RunningTimer::class)->findActiveByContributor($contributor);
        if (!$timer) {
            return new JsonResponse(['error' => 'Aucun compteur actif'], 400);
        }

        $hoursLogged = $this->finalizeTimer($timer, $em);

        return new JsonResponse(['success' => true, 'hours_logged' => $hoursLogged]);
    }

    /** Donne la liste des projets et tâches assignées pour démarrer un timer. */
    #[Route('/timer/options', name: 'timesheet_timer_options', methods: ['GET'])]
    public function timerOptions(EntityManagerInterface $em): JsonResponse
    {
        $contributor = $em->getRepository(Contributor::class)->findByUser($this->getUser());
        if (!$contributor) {
            return new JsonResponse(['error' => 'Contributeur non trouvé'], 400);
        }

        $rows = $em->getRepository(Contributor::class)->findProjectsWithTasksForContributor($contributor);

        // Récupérer les sous-tâches assignées au contributeur pour chaque tâche
        $projects = [];
        foreach ($rows as $row) {
            /** @var Project $p */
            $p         = $row['project'];
            $taskItems = [];
            foreach ($row['tasks'] as $task) {
                $subTasks = $em->createQueryBuilder()
                    ->select('st')
                    ->from('App\\Entity\\ProjectSubTask', 'st')
                    ->where('st.task = :task')
                    ->andWhere('st.assignee = :contributor')
                    ->setParameter('task', $task)
                    ->setParameter('contributor', $contributor)
                    ->orderBy('st.position', 'ASC')
                    ->getQuery()
                    ->getResult();
                $taskItems[] = [
                    'id'       => $task->getId(),
                    'name'     => $task->getName(),
                    'subTasks' => array_map(fn ($st) => ['id' => $st->getId(), 'title' => $st->getTitle()], $subTasks),
                ];
            }
            $projects[] = [
                'id'    => $p->getId(),
                'name'  => $p->getName(),
                'tasks' => $taskItems,
            ];
        }

        return new JsonResponse(['projects' => $projects]);
    }

    /** Retourne le timer actif pour l'utilisateur courant (si présent). */
    #[Route('/timer/active', name: 'timesheet_timer_active', methods: ['GET'])]
    public function activeTimer(EntityManagerInterface $em): JsonResponse
    {
        $contributor = $em->getRepository(Contributor::class)->findByUser($this->getUser());
        if (!$contributor) {
            return new JsonResponse(['timer' => null]);
        }

        $timer = $em->getRepository(RunningTimer::class)->findActiveByContributor($contributor);
        if (!$timer) {
            return new JsonResponse(['timer' => null]);
        }

        return new JsonResponse([
            'timer' => [
                'id'         => $timer->getId(),
                'project'    => ['id' => $timer->getProject()->getId(), 'name' => $timer->getProject()->getName()],
                'task'       => $timer->getTask() ? ['id' => $timer->getTask()->getId(), 'name' => $timer->getTask()->getName()] : null,
                'subTask'    => $timer->getSubTask() ? ['id' => $timer->getSubTask()->getId(), 'title' => $timer->getSubTask()->getTitle()] : null,
                'started_at' => $timer->getStartedAt()->format(DateTimeInterface::ATOM),
            ],
        ]);
    }

    /**
     * Convertit un timer en entrée de timesheet et le clôture.
     * Retourne le nombre d'heures imputées.
     */
    private function finalizeTimer(RunningTimer $timer, EntityManagerInterface $em): float
    {
        $now = new DateTime();
        $timer->setStoppedAt($now);

        $startedAt = $timer->getStartedAt();
        $elapsed   = $now->getTimestamp() - $startedAt->getTimestamp(); // en secondes
        if ($elapsed < 0) {
            $elapsed = 0;
        }

        // Conversion en heures (2 décimales), minimum 1h (0,125j)
        $hours = round($elapsed / 3600, 2);
        if ($hours < 1.0) {
            $hours = 1.0;
        }

        // Imputation sur la date du jour (date d'arrêt)
        $date = new DateTime($now->format('Y-m-d'));

        $timesheetRepo = $em->getRepository(Timesheet::class);
        $existing      = $timesheetRepo->findExistingTimesheetWithTaskAndSubTask(
            $timer->getContributor(),
            $timer->getProject(),
            $date,
            $timer->getTask(),
            $timer->getSubTask(),
        );

        if ($existing) {
            $newHours = (float) $existing->getHours() + $hours;
            $existing->setHours(number_format($newHours, 2, '.', ''));
            $em->persist($existing);
        } else {
            $t = new Timesheet();
            $t->setContributor($timer->getContributor())
              ->setProject($timer->getProject())
              ->setTask($timer->getTask())
              ->setSubTask($timer->getSubTask())
              ->setDate($date)
              ->setHours(number_format($hours, 2, '.', ''));
            $em->persist($t);
        }

        // Supprimer le timer (ou le laisser historisé). On choisit de le supprimer pour ne garder que l'actif.
        $em->remove($timer);
        $em->flush();

        return (float) $hours;
    }
}
