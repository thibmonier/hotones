<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AchievementRepository;
use App\Repository\ContributorProgressRepository;
use App\Repository\ContributorRepository;
use App\Service\GamificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/leaderboard')]
#[IsGranted('ROLE_USER')]
class LeaderboardController extends AbstractController
{
    public function __construct(
        private readonly GamificationService $gamificationService,
        private readonly ContributorProgressRepository $progressRepository,
        private readonly AchievementRepository $achievementRepository,
        private readonly ContributorRepository $contributorRepository,
    ) {
    }

    /**
     * Affiche le leaderboard global.
     */
    #[Route('', name: 'leaderboard_index', methods: ['GET'])]
    public function index(): Response
    {
        $leaderboard        = $this->gamificationService->getLeaderboard(50);
        $globalStats        = $this->progressRepository->getGlobalStats();
        $recentAchievements = $this->achievementRepository->findRecentAchievements(10);

        // Récupérer la progression de l'utilisateur connecté
        $user        = $this->getUser();
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);
        $myProgress  = null;
        $myRank      = 0;

        if ($contributor) {
            $myProgress = $this->gamificationService->getContributorProgress($contributor);
            $myRank     = $this->gamificationService->getContributorRank($contributor);
        }

        return $this->render('leaderboard/index.html.twig', [
            'leaderboard'         => $leaderboard,
            'global_stats'        => $globalStats,
            'recent_achievements' => $recentAchievements,
            'my_progress'         => $myProgress,
            'my_rank'             => $myRank,
        ]);
    }

    /**
     * Affiche le profil gamification d'un contributeur.
     */
    #[Route('/profile/{id}', name: 'leaderboard_profile', methods: ['GET'])]
    public function profile(int $id): Response
    {
        $contributor = $this->contributorRepository->find($id);

        if (!$contributor) {
            throw $this->createNotFoundException('Contributeur non trouvé');
        }

        $progress     = $this->gamificationService->getContributorProgress($contributor);
        $achievements = $this->gamificationService->getContributorAchievements($contributor);
        $xpHistory    = $this->gamificationService->getContributorXpHistory($contributor, 100);
        $rank         = $this->gamificationService->getContributorRank($contributor);

        return $this->render('leaderboard/profile.html.twig', [
            'contributor'  => $contributor,
            'progress'     => $progress,
            'achievements' => $achievements,
            'xp_history'   => $xpHistory,
            'rank'         => $rank,
        ]);
    }

    /**
     * Affiche le profil gamification de l'utilisateur connecté.
     */
    #[Route('/me', name: 'leaderboard_me', methods: ['GET'])]
    public function me(): Response
    {
        $user        = $this->getUser();
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$contributor) {
            $this->addFlash('error', 'Aucun profil contributeur associé à votre compte');

            return $this->redirectToRoute('dashboard');
        }

        return $this->redirectToRoute('leaderboard_profile', ['id' => $contributor->getId()]);
    }
}
