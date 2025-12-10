<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\OnboardingTask;
use App\Repository\ContributorRepository;
use App\Service\OnboardingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/onboarding')]
#[IsGranted('ROLE_INTERVENANT')]
class OnboardingController extends AbstractController
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly ContributorRepository $contributorRepository,
    ) {
    }

    #[Route('/{id}', name: 'onboarding_show', methods: ['GET'])]
    public function show(Contributor $contributor): Response
    {
        // Check access: contributor can see their own, manager can see team members
        $user = $this->getUser();
        if ($contributor->getUser() !== $user && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet onboarding.');
        }

        $tasksByWeek = $this->onboardingService->getTasksByWeek($contributor);
        $summary     = $this->onboardingService->getOnboardingSummary($contributor);

        return $this->render('onboarding/show.html.twig', [
            'contributor'   => $contributor,
            'tasks_by_week' => $tasksByWeek,
            'summary'       => $summary,
        ]);
    }

    #[Route('/task/{id}/complete', name: 'onboarding_task_complete', methods: ['POST'])]
    public function completeTask(Request $request, OnboardingTask $task): Response
    {
        $user        = $this->getUser();
        $contributor = $task->getContributor();

        // Check access
        if ($contributor->getUser() !== $user && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('complete-task-'.$task->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('onboarding_show', ['id' => $contributor->getId()]);
        }

        $comments = $request->request->get('comments');
        $this->onboardingService->completeTask($task, $comments);

        $this->addFlash('success', 'Tâche marquée comme terminée.');

        return $this->redirectToRoute('onboarding_show', ['id' => $contributor->getId()]);
    }

    #[Route('/task/{id}/update-status', name: 'onboarding_task_update_status', methods: ['POST'])]
    public function updateTaskStatus(Request $request, OnboardingTask $task): Response
    {
        $user        = $this->getUser();
        $contributor = $task->getContributor();

        // Check access
        if ($contributor->getUser() !== $user && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('update-task-'.$task->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
        }

        $status = $request->request->get('status');
        if (!in_array($status, ['a_faire', 'en_cours', 'termine'], true)) {
            return $this->json(['success' => false, 'error' => 'Statut invalide'], 400);
        }

        $this->onboardingService->updateTaskStatus($task, $status);

        return $this->json([
            'success'      => true,
            'status'       => $status,
            'status_label' => $task->getStatusLabel(),
        ]);
    }

    #[Route('/team', name: 'onboarding_team', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function team(): Response
    {
        // Get all active contributors with onboarding
        $contributors = $this->contributorRepository->findActiveContributors();

        $onboardingData = [];
        foreach ($contributors as $contributor) {
            $summary = $this->onboardingService->getOnboardingSummary($contributor);

            // Only include contributors with onboarding tasks
            if ($summary['total'] > 0) {
                $onboardingData[] = [
                    'contributor' => $contributor,
                    'summary'     => $summary,
                ];
            }
        }

        // Sort by progress (incomplete first, then by overdue count)
        usort($onboardingData, function ($a, $b) {
            if ($a['summary']['is_complete'] !== $b['summary']['is_complete']) {
                return $a['summary']['is_complete'] ? 1 : -1;
            }

            return $b['summary']['overdue'] <=> $a['summary']['overdue'];
        });

        return $this->render('onboarding/team.html.twig', [
            'onboarding_data' => $onboardingData,
        ]);
    }
}
