<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectSubTask;
use App\Form\ProjectSubTaskType;
use App\Repository\ProjectSubTaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/project')]
#[IsGranted('ROLE_USER')]
class ProjectSubTaskController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProjectSubTaskRepository $subTasks
    ) {
    }

    #[Route('/{id}/subtasks/kanban', name: 'project_subtasks_kanban', methods: ['GET'])]
    public function kanban(Project $project): Response
    {
        $todo  = $this->subTasks->findByProjectAndStatus($project, ProjectSubTask::STATUS_TODO);
        $doing = $this->subTasks->findByProjectAndStatus($project, ProjectSubTask::STATUS_IN_PROGRESS);
        $done  = $this->subTasks->findByProjectAndStatus($project, ProjectSubTask::STATUS_DONE);

        return $this->render('project_subtask/kanban.html.twig', [
            'project' => $project,
            'todo'    => $todo,
            'doing'   => $doing,
            'done'    => $done,
        ]);
    }

    #[Route('/subtasks/{id}/move', name: 'project_subtask_move', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function move(ProjectSubTask $subTask, Request $request): JsonResponse
    {
        $data      = json_decode($request->getContent() ?: '[]', true);
        $status    = $data['status']    ?? null;
        $positions = $data['positions'] ?? []; // [subTaskId => position]

        if (!in_array($status, array_keys(ProjectSubTask::getAvailableStatuses()), true)) {
            return $this->json(['error' => 'Invalid status'], 400);
        }

        $subTask->setStatus($status);
        // Apply positions batch if provided
        foreach ($positions as $id => $pos) {
            $st = $this->em->getRepository(ProjectSubTask::class)->find((int) $id);
            if ($st) {
                $st->setPosition((int) $pos);
            }
        }
        $subTask->setUpdatedAt(new DateTimeImmutable());

        $this->em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/subtasks/{id}/update-raf', name: 'project_subtask_update_raf', methods: ['POST'])]
    public function updateRaf(ProjectSubTask $subTask, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $data = json_decode($request->getContent() ?: '[]', true);
        $raf  = $data['remainingHours'] ?? null;
        if ($raf === null || !is_numeric($raf)) {
            return $this->json(['error' => 'Valeur invalide'], 400);
        }
        $subTask->setRemainingHours(number_format((float) $raf, 2, '.', ''));
        $subTask->setUpdatedAt(new DateTimeImmutable());
        $this->em->flush();

        return $this->json([
            'ok'        => true,
            'progress'  => $subTask->getProgressPercentage(),
            'timeSpent' => $subTask->getTimeSpentHours(),
        ]);
    }

    #[Route('/subtasks/{id}/edit', name: 'project_subtask_edit', methods: ['GET', 'POST'])]
    public function edit(ProjectSubTask $subTask, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $form = $this->createForm(ProjectSubTaskType::class, $subTask, [
            'action' => $this->generateUrl('project_subtask_edit', ['id' => $subTask->getId()]),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $subTask->setUpdatedAt(new DateTimeImmutable());
                $this->em->flush();
                // Re-render the card HTML
                $html = $this->renderView('project_subtask/partials/_card.html.twig', ['st' => $subTask]);

                return $this->json(['ok' => true, 'html' => $html]);
            }
            // Invalid -> return modal with errors
            $html = $this->renderView('project_subtask/_modal_form.html.twig', [
                'subTask' => $subTask,
                'form'    => $form->createView(),
            ]);

            return new JsonResponse(['ok' => false, 'html' => $html], 400);
        }

        // Initial GET -> return modal content
        return $this->render('project_subtask/_modal_form.html.twig', [
            'subTask' => $subTask,
            'form'    => $form->createView(),
        ]);
    }
}
