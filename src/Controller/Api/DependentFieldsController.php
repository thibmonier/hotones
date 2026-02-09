<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\ProjectRepository;
use App\Repository\ProjectSubTaskRepository;
use App\Repository\ProjectTaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API endpoints for dependent/cascading form fields.
 */
#[Route('/api')]
#[IsGranted('ROLE_USER')]
class DependentFieldsController extends AbstractController
{
    /**
     * Get active projects for a client.
     *
     * Used in cascading selects: Client → Projects
     */
    #[Route('/clients/{id}/projects', name: 'api_client_projects', methods: ['GET'])]
    public function getClientProjects(int $id, ProjectRepository $projectRepository): JsonResponse
    {
        $projects = $projectRepository->findBy(['client' => $id, 'status' => 'active'], ['name' => 'ASC']);

        $data = array_map(fn ($project): array => [
            'id'   => $project->getId(),
            'name' => $project->getName(),
        ], $projects);

        return new JsonResponse($data);
    }

    /**
     * Get active tasks for a project.
     *
     * Used in cascading selects: Project → Tasks
     */
    #[Route('/projects/{id}/tasks', name: 'api_project_tasks', methods: ['GET'])]
    public function getProjectTasks(int $id, ProjectTaskRepository $taskRepository): JsonResponse
    {
        $tasks = $taskRepository->findBy(['project' => $id, 'active' => true], ['position' => 'ASC']);

        $data = array_map(fn ($task): array => [
            'id'          => $task->getId(),
            'name'        => $task->getName(),
            'description' => $task->getDescription(),
        ], $tasks);

        return new JsonResponse($data);
    }

    /**
     * Get subtasks for a task.
     *
     * Used in cascading selects: Task → Subtasks
     */
    #[Route('/tasks/{id}/subtasks', name: 'api_task_subtasks', methods: ['GET'])]
    public function getTaskSubtasks(int $id, ProjectSubTaskRepository $subTaskRepository): JsonResponse
    {
        $subTasks = $subTaskRepository->findBy(['task' => $id], ['position' => 'ASC']);

        $data = array_map(fn ($subTask): array => [
            'id'   => $subTask->getId(),
            'name' => $subTask->getTitle(),
        ], $subTasks);

        return new JsonResponse($data);
    }
}
