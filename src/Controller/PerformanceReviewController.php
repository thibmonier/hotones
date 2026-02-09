<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PerformanceReview;
use App\Repository\PerformanceReviewRepository;
use App\Service\PerformanceReviewService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/performance-reviews')]
#[IsGranted('ROLE_INTERVENANT')]
class PerformanceReviewController extends AbstractController
{
    public function __construct(
        private readonly PerformanceReviewService $reviewService,
        private readonly PerformanceReviewRepository $reviewRepository,
        private readonly \App\Repository\ContributorRepository $contributorRepository,
    ) {
    }

    #[Route('', name: 'performance_review_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $year   = (int) $request->query->get('year', date('Y'));
        $status = $request->query->get('status');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Get reviews based on user role
        if ($this->isGranted('ROLE_MANAGER')) {
            // Managers see reviews they manage
            $reviews = $this->reviewRepository->findByManager($user);
        } else {
            // Contributors see only their own reviews
            $contributor = $this->contributorRepository->findOneBy(['user' => $user]);
            $reviews     = null === $contributor ? [] : $this->reviewRepository->findByContributor($contributor);
        }

        // Apply filters
        if ($year) {
            $reviews = array_filter($reviews, fn ($review): bool => $review->getYear() === $year);
        }

        if ($status) {
            $reviews = array_filter($reviews, fn ($review): bool => $review->getStatus() === $status);
        }

        // Get statistics for admin/manager
        $statistics = null;
        if ($this->isGranted('ROLE_MANAGER')) {
            $statistics = $this->reviewService->getStatistics();
        }

        return $this->render('performance_review/index.html.twig', [
            'reviews'    => $reviews,
            'year'       => $year,
            'status'     => $status,
            'statistics' => $statistics,
        ]);
    }

    #[Route('/{id}', name: 'performance_review_show', methods: ['GET'])]
    public function show(PerformanceReview $review): Response
    {
        // Check access: contributor can see their own, manager can see their managed reviews
        $user = $this->getUser();
        if (
            $review->getContributor()->getUser() !== $user
            && $review->getManager()             !== $user
            && !$this->isGranted('ROLE_ADMIN')
        ) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette évaluation.');
        }

        return $this->render('performance_review/show.html.twig', [
            'review' => $review,
        ]);
    }

    #[Route('/{id}/self-evaluation', name: 'performance_review_self_evaluation', methods: ['GET', 'POST'])]
    public function selfEvaluation(Request $request, PerformanceReview $review): Response
    {
        $user = $this->getUser();

        // Check permission
        if (!$this->reviewService->canEditSelfEvaluation($review, $user)) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette auto-évaluation.');

            return $this->redirectToRoute('performance_review_show', ['id' => $review->getId()]);
        }

        if ($request->isMethod('POST')) {
            $achievements = $request->request->get('achievements');
            $strengths    = $request->request->get('strengths');
            $improvements = $request->request->get('improvements');

            if (!$this->isCsrfTokenValid('self-evaluation-'.$review->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');

                return $this->redirectToRoute('performance_review_self_evaluation', ['id' => $review->getId()]);
            }

            $this->reviewService->completeSelfEvaluation($review, $achievements, $strengths, $improvements);

            $this->addFlash('success', 'Votre auto-évaluation a été enregistrée avec succès.');

            return $this->redirectToRoute('performance_review_show', ['id' => $review->getId()]);
        }

        return $this->render('performance_review/self_evaluation.html.twig', [
            'review' => $review,
        ]);
    }

    #[Route('/{id}/manager-evaluation', name: 'performance_review_manager_evaluation', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function managerEvaluation(Request $request, PerformanceReview $review): Response
    {
        $user = $this->getUser();

        // Check permission
        if (!$this->reviewService->canEditManagerEvaluation($review, $user)) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette évaluation manager.');

            return $this->redirectToRoute('performance_review_show', ['id' => $review->getId()]);
        }

        if ($request->isMethod('POST')) {
            $achievements = $request->request->get('achievements');
            $strengths    = $request->request->get('strengths');
            $improvements = $request->request->get('improvements');
            $feedback     = $request->request->get('feedback');
            $rating       = $request->request->get('rating') ? (int) $request->request->get('rating') : null;

            if (!$this->isCsrfTokenValid('manager-evaluation-'.$review->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');

                return $this->redirectToRoute('performance_review_manager_evaluation', ['id' => $review->getId()]);
            }

            $this->reviewService->completeManagerEvaluation(
                $review,
                $achievements,
                $strengths,
                $improvements,
                $feedback,
                $rating,
            );

            $this->addFlash('success', 'L\'évaluation manager a été enregistrée avec succès.');

            return $this->redirectToRoute('performance_review_show', ['id' => $review->getId()]);
        }

        return $this->render('performance_review/manager_evaluation.html.twig', [
            'review' => $review,
        ]);
    }

    #[Route('/{id}/validate', name: 'performance_review_validate', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function validate(Request $request, PerformanceReview $review): Response
    {
        $user = $this->getUser();

        // Check permission
        if (!$this->reviewService->canValidateReview($review, $user)) {
            $this->addFlash('error', 'Vous ne pouvez pas valider cette évaluation.');

            return $this->redirectToRoute('performance_review_show', ['id' => $review->getId()]);
        }

        if ($request->isMethod('POST')) {
            $objectivesData   = $request->request->all('objectives');
            $interviewDateStr = $request->request->get('interview_date');
            $comments         = $request->request->get('comments');

            if (!$this->isCsrfTokenValid('validate-review-'.$review->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');

                return $this->redirectToRoute('performance_review_validate', ['id' => $review->getId()]);
            }

            // Parse objectives
            $objectives = [];
            foreach ($objectivesData as $objective) {
                if (!empty($objective['title'])) {
                    $objectives[] = [
                        'title'       => $objective['title'],
                        'description' => $objective['description'] ?? '',
                        'deadline'    => $objective['deadline']    ?? null,
                    ];
                }
            }

            $interviewDate = $interviewDateStr ? new DateTimeImmutable($interviewDateStr) : null;

            $this->reviewService->validateReview($review, $objectives, $interviewDate, $comments);

            $this->addFlash('success', 'L\'évaluation a été validée avec succès.');

            return $this->redirectToRoute('performance_review_show', ['id' => $review->getId()]);
        }

        return $this->render('performance_review/validate.html.twig', [
            'review' => $review,
        ]);
    }

    #[Route('/campaign/create', name: 'performance_review_campaign_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createCampaign(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $year = (int) $request->request->get('year', date('Y'));

            if (!$this->isCsrfTokenValid('create-campaign', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');

                return $this->redirectToRoute('performance_review_campaign_create');
            }

            $created = $this->reviewService->createCampaign($year);

            $this->addFlash('success', "{$created} évaluation(s) créée(s) pour l'année {$year}.");

            return $this->redirectToRoute('performance_review_index', ['year' => $year]);
        }

        return $this->render('performance_review/create_campaign.html.twig', [
            'current_year' => (int) date('Y'),
        ]);
    }
}
