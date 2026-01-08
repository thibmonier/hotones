<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectSubTask;
use App\Entity\ProjectTask;
use App\Form\ProjectSubTaskType;
use App\Form\ProjectTaskType;
use App\Repository\ProjectTaskRepository;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/project/{projectId}/tasks')]
#[IsGranted('ROLE_USER')]
class ProjectTaskController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProjectTaskRepository $taskRepo,
        private readonly CompanyContext $companyContext
    ) {
    }

    #[Route('', name: 'project_task_index', methods: ['GET'])]
    public function index(int $projectId): Response
    {
        $project = $this->em->getRepository(Project::class)->find($projectId);
        if (!$project) {
            throw $this->createNotFoundException('Projet non trouvé');
        }

        $tasks = $this->taskRepo->findBy(
            ['project' => $project],
            ['position' => 'ASC', 'id' => 'ASC'],
        );

        return $this->render('project_task/index.html.twig', [
            'project' => $project,
            'tasks'   => $tasks,
        ]);
    }

    #[Route('/new', name: 'project_task_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function new(int $projectId, Request $request): Response
    {
        $project = $this->em->getRepository(Project::class)->find($projectId);
        if (!$project) {
            throw $this->createNotFoundException('Projet non trouvé');
        }

        $task = new ProjectTask();
        $task->setCompany($project->getCompany());
        $task->setProject($project);

        // Définir la position par défaut
        $maxPosition = $this->taskRepo->getMaxPosition($project);
        $task->setPosition($maxPosition + 1);

        $form = $this->createForm(ProjectTaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($task);
            $this->em->flush();

            $this->addFlash('success', 'Tâche créée avec succès.');

            return $this->redirectToRoute('project_task_index', ['projectId' => $project->getId()]);
        }

        return $this->render('project_task/new.html.twig', [
            'project' => $project,
            'task'    => $task,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'project_task_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function edit(int $projectId, ProjectTask $task, Request $request): Response
    {
        if ($task->getProject()->getId() !== $projectId) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ProjectTaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Tâche modifiée avec succès.');

            return $this->redirectToRoute('project_task_index', ['projectId' => $projectId]);
        }

        return $this->render('project_task/edit.html.twig', [
            'project' => $task->getProject(),
            'task'    => $task,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'project_task_show', methods: ['GET'])]
    public function show(int $projectId, ProjectTask $task): Response
    {
        if ($task->getProject()->getId() !== $projectId) {
            throw $this->createNotFoundException();
        }

        return $this->render('project_task/show.html.twig', [
            'project' => $task->getProject(),
            'task'    => $task,
        ]);
    }

    #[Route('/{id}/delete', name: 'project_task_delete', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function delete(int $projectId, ProjectTask $task, Request $request): Response
    {
        if ($task->getProject()->getId() !== $projectId) {
            throw $this->createNotFoundException();
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_task_'.$task->getId(), $token)) {
            // Les sous-tâches seront supprimées en cascade
            $this->em->remove($task);
            $this->em->flush();

            $this->addFlash('success', 'Tâche supprimée avec succès.');
        }

        return $this->redirectToRoute('project_task_index', ['projectId' => $projectId]);
    }

    #[Route('/{id}/subtask/new', name: 'project_task_subtask_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newSubTask(int $projectId, ProjectTask $task, Request $request): Response
    {
        if ($task->getProject()->getId() !== $projectId) {
            throw $this->createNotFoundException();
        }

        $subTask = new ProjectSubTask();
        $subTask->setCompany($task->getCompany());
        $subTask->setTask($task);

        // Position par défaut
        $maxPosition = $this->em->getRepository(ProjectSubTask::class)
            ->createQueryBuilder('st')
            ->select('MAX(st.position)')
            ->where('st.task = :task')
            ->setParameter('task', $task)
            ->getQuery()
            ->getSingleScalarResult();

        $subTask->setPosition((int) $maxPosition + 1);

        $form = $this->createForm(ProjectSubTaskType::class, $subTask);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($subTask);
            $this->em->flush();

            $this->addFlash('success', 'Sous-tâche créée avec succès.');

            return $this->redirectToRoute('project_task_show', [
                'projectId' => $projectId,
                'id'        => $task->getId(),
            ]);
        }

        return $this->render('project_task/subtask_new.html.twig', [
            'project' => $task->getProject(),
            'task'    => $task,
            'subTask' => $subTask,
            'form'    => $form,
        ]);
    }
}
