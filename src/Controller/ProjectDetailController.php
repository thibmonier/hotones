<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\Contributor;
use App\Entity\Profile;
use App\Form\ProjectTaskType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/project')]
#[IsGranted('ROLE_USER')]
class ProjectDetailController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/{id}/details', name: 'project_details', methods: ['GET'])]
    public function details(Project $project): Response
    {
        // Récupérer les intervenants avec leurs heures
        $projectContributors = $project->getProjectContributorsWithHours();
        
        // Récupérer toutes les tâches du projet triées par position
        $tasks = $this->entityManager->getRepository(ProjectTask::class)
            ->createQueryBuilder('t')
            ->where('t.project = :project')
            ->orderBy('t.position', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();

        // Calculer les métriques du projet
        $metrics = $this->calculateProjectMetrics($project);

        // Récupérer les données pour les graphiques
        $taskProgressData = $this->getTaskProgressData($tasks);
        $contributorHoursData = $this->getContributorHoursData($projectContributors);

        return $this->render('project/details.html.twig', [
            'project' => $project,
            'tasks' => $tasks,
            'projectContributors' => $projectContributors,
            'metrics' => $metrics,
            'taskProgressData' => $taskProgressData,
            'contributorHoursData' => $contributorHoursData,
        ]);
    }

    #[Route('/{id}/tasks/new', name: 'project_task_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function newTask(Project $project, Request $request): Response
    {
        $task = new ProjectTask();
        $task->setProject($project);
        $task->setType(ProjectTask::TYPE_REGULAR);
        $task->setCountsForProfitability(true);
        $task->setStatus('not_started');
        $task->setActive(true);
        
        // Définir la position par défaut (dernière position + 1)
        $lastPosition = $this->entityManager->getRepository(ProjectTask::class)
            ->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->where('t.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $task->setPosition($lastPosition + 1);

        $form = $this->createForm(ProjectTaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($task);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('La tâche « %s » a été créée avec succès.', $task->getName()));
            return $this->redirectToRoute('project_details', ['id' => $project->getId()]);
        }

        return $this->render('project_task/new.html.twig', [
            'project' => $project,
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/task/{id}/edit', name: 'project_task_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function editTask(ProjectTask $task, Request $request): Response
    {
        $form = $this->createForm(ProjectTaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('La tâche « %s » a été modifiée avec succès.', $task->getName()));
            return $this->redirectToRoute('project_details', ['id' => $task->getProject()->getId()]);
        }

        return $this->render('project_task/edit.html.twig', [
            'project' => $task->getProject(),
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/task/{id}/delete', name: 'project_task_delete', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function deleteTask(ProjectTask $task, Request $request): Response
    {
        $project = $task->getProject();
        
        // Vérification du token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $taskName = $task->getName();
            $this->entityManager->remove($task);
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf('La tâche « %s » a été supprimée avec succès.', $taskName));
        } else {
            $this->addFlash('error', 'Token de sécurité invalide. La suppression a été annulée.');
        }

        return $this->redirectToRoute('project_details', ['id' => $project->getId()]);
    }

    private function calculateProjectMetrics(Project $project): array
    {
        return [
            // Chiffres de vente
            'total_sold_amount' => $project->getTotalSoldAmount(), // Via devis
            'total_tasks_sold_amount' => $project->getTotalTasksSoldAmount(), // Via tâches
            
            // Estimations de temps
            'total_sold_hours' => $project->getTotalTasksSoldHours(),
            'total_revised_hours' => $project->getTotalTasksRevisedHours(),
            'total_spent_hours' => $project->getTotalTasksSpentHours(),
            'total_remaining_hours' => $project->getTotalRemainingHours(),
            
            // Conversion en jours (1j = 8h)
            'total_sold_days' => bcdiv($project->getTotalTasksSoldHours(), '8', 2),
            'total_revised_days' => bcdiv($project->getTotalTasksRevisedHours(), '8', 2),
            'total_spent_days' => bcdiv($project->getTotalTasksSpentHours(), '8', 2),
            'total_remaining_days' => bcdiv($project->getTotalRemainingHours(), '8', 2),
            
            // Coûts et marges
            'estimated_cost' => $project->getTotalTasksEstimatedCost(),
            'target_gross_margin' => $project->getTargetGrossMargin(),
            'target_margin_percentage' => $project->getTargetMarginPercentage(),
            
            // Achats
            'purchases_amount' => $project->getPurchasesAmount() ?? '0.00',
            'purchases_description' => $project->getPurchasesDescription(),
            
            // Avancement
            'global_progress' => $project->getGlobalProgress(),
            
            // Nombres
            'total_tasks' => $project->getTasks()->count(),
            'completed_tasks' => $project->getTasks()->filter(fn($t) => $t->getStatus() === 'completed')->count(),
            'in_progress_tasks' => $project->getTasks()->filter(fn($t) => $t->getStatus() === 'in_progress')->count(),
        ];
    }

    private function getTaskProgressData(array $tasks): array
    {
        $labels = [];
        $progressData = [];
        $spentHours = [];
        $remainingHours = [];

        foreach ($tasks as $task) {
            if ($task->getCountsForProfitability()) {
                $labels[] = substr($task->getName(), 0, 20) . (strlen($task->getName()) > 20 ? '...' : '');
                $progressData[] = (float) $task->getProgressPercentage();
                $spentHours[] = (float) $task->getTotalHours();
                $remainingHours[] = (float) $task->getRemainingHours();
            }
        }

        return [
            'labels' => $labels,
            'progress' => $progressData,
            'spentHours' => $spentHours,
            'remainingHours' => $remainingHours,
        ];
    }

    private function getContributorHoursData(array $contributors): array
    {
        $labels = [];
        $spentHours = [];
        $remainingHours = [];
        $estimatedHours = [];

        foreach ($contributors as $contributorData) {
            $contributor = $contributorData['contributor'];
            $labels[] = $contributor->getName();
            $spentHours[] = (float) $contributorData['spent_hours'];
            $remainingHours[] = (float) $contributorData['remaining_hours'];
            $estimatedHours[] = (float) $contributorData['estimated_hours'];
        }

        return [
            'labels' => $labels,
            'spentHours' => $spentHours,
            'remainingHours' => $remainingHours,
            'estimatedHours' => $estimatedHours,
        ];
    }
}