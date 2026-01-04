<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Achievement;
use App\Entity\Badge;
use App\Entity\Contributor;
use App\Entity\ContributorProgress;
use App\Entity\XpHistory;
use App\Repository\AchievementRepository;
use App\Repository\BadgeRepository;
use App\Repository\ContributorProgressRepository;
use App\Repository\XpHistoryRepository;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class GamificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContributorProgressRepository $progressRepository,
        private readonly BadgeRepository $badgeRepository,
        private readonly AchievementRepository $achievementRepository,
        private readonly XpHistoryRepository $xpHistoryRepository,
        private readonly CompanyContext $companyContext,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Ajoute de l'XP à un contributeur et gère les level-ups automatiquement.
     *
     * @return array{xp_gained: int, level_up: bool, new_level: int, badges_unlocked: Badge[]}
     */
    public function addXp(
        Contributor $contributor,
        int $xpAmount,
        string $source,
        ?string $description = null,
        ?array $metadata = null
    ): array {
        if ($xpAmount <= 0) {
            throw new InvalidArgumentException('XP amount must be positive');
        }

        // Récupérer ou créer la progression
        $progress = $this->progressRepository->findOrCreateForContributor($contributor);

        // Sauvegarder le niveau avant
        $oldLevel = $progress->getLevel();

        // Ajouter l'XP
        $progress->addXp($xpAmount);

        // Créer l'entrée d'historique
        $history = new XpHistory();
        $history->setCompany($this->companyContext->getCurrentCompany());
        $history->setContributor($contributor);
        $history->setXpAmount($xpAmount);
        $history->setSource($source);
        $history->setDescription($description);
        $history->setMetadata($metadata);

        $this->entityManager->persist($progress);
        $this->entityManager->persist($history);

        // Gérer les level-ups
        $leveledUp = false;
        while ($progress->levelUp()) {
            $leveledUp = true;
            $this->logger->info('Contributor leveled up', [
                'contributor' => $contributor->getFullName(),
                'new_level'   => $progress->getLevel(),
            ]);
        }

        // Flush avant de vérifier les badges
        $this->entityManager->flush();

        // Vérifier et débloquer les badges
        $unlockedBadges = $this->checkAndUnlockBadges($contributor, $progress);

        return [
            'xp_gained'       => $xpAmount,
            'level_up'        => $leveledUp,
            'new_level'       => $progress->getLevel(),
            'old_level'       => $oldLevel,
            'badges_unlocked' => $unlockedBadges,
        ];
    }

    /**
     * Vérifie et débloque automatiquement les badges éligibles.
     *
     * @return Badge[]
     */
    public function checkAndUnlockBadges(Contributor $contributor, ?ContributorProgress $progress = null): array
    {
        if (!$progress) {
            $progress = $this->progressRepository->findOrCreateForContributor($contributor);
        }

        $unlockedBadges = [];
        $allBadges      = $this->badgeRepository->findAllActive();

        foreach ($allBadges as $badge) {
            // Vérifier si déjà unlocked
            if ($this->achievementRepository->hasAchievement($contributor, $badge)) {
                continue;
            }

            // Vérifier les critères
            if ($this->checkBadgeCriteria($contributor, $badge, $progress)) {
                $unlockedBadges[] = $this->unlockBadge($contributor, $badge);
            }
        }

        if (!empty($unlockedBadges)) {
            $this->entityManager->flush();
        }

        return $unlockedBadges;
    }

    /**
     * Débloque un badge pour un contributeur.
     */
    public function unlockBadge(Contributor $contributor, Badge $badge): Badge
    {
        // Vérifier si déjà unlocked
        if ($this->achievementRepository->hasAchievement($contributor, $badge)) {
            throw new RuntimeException('Badge already unlocked for this contributor');
        }

        $achievement = new Achievement();
        $achievement->setCompany($this->companyContext->getCurrentCompany());
        $achievement->setContributor($contributor);
        $achievement->setBadge($badge);

        $this->entityManager->persist($achievement);

        // Ajouter l'XP du badge
        $this->addXp(
            $contributor,
            $badge->getXpReward(),
            'badge_unlocked',
            sprintf('Badge débloqué: %s', $badge->getName()),
            ['badge_id' => $badge->getId()],
        );

        $this->logger->info('Badge unlocked', [
            'contributor' => $contributor->getFullName(),
            'badge'       => $badge->getName(),
            'xp_reward'   => $badge->getXpReward(),
        ]);

        return $badge;
    }

    /**
     * Vérifie si un contributeur remplit les critères d'un badge.
     */
    private function checkBadgeCriteria(Contributor $contributor, Badge $badge, ContributorProgress $progress): bool
    {
        $criteria = $badge->getCriteria();
        if (!$criteria) {
            return false;
        }

        // Vérifier les critères de niveau
        if (isset($criteria['level']) && $progress->getLevel() < $criteria['level']) {
            return false;
        }

        // Vérifier les critères d'XP total
        if (isset($criteria['total_xp']) && $progress->getTotalXp() < $criteria['total_xp']) {
            return false;
        }

        // Vérifier les critères par source d'XP
        if (isset($criteria['xp_from_source'])) {
            foreach ($criteria['xp_from_source'] as $source => $requiredXp) {
                $xpStats = $this->xpHistoryRepository->getXpBySource($contributor);
                if (!isset($xpStats[$source]) || $xpStats[$source]['total'] < $requiredXp) {
                    return false;
                }
            }
        }

        // Vérifier le nombre d'actions d'un certain type
        if (isset($criteria['action_count'])) {
            foreach ($criteria['action_count'] as $source => $requiredCount) {
                $xpStats = $this->xpHistoryRepository->getXpBySource($contributor);
                if (!isset($xpStats[$source]) || $xpStats[$source]['count'] < $requiredCount) {
                    return false;
                }
            }
        }

        // Tous les critères sont remplis
        return true;
    }

    /**
     * Récupère la progression complète d'un contributeur.
     */
    public function getContributorProgress(Contributor $contributor): ContributorProgress
    {
        return $this->progressRepository->findOrCreateForContributor($contributor);
    }

    /**
     * Récupère tous les badges débloqués par un contributeur.
     *
     * @return Achievement[]
     */
    public function getContributorAchievements(Contributor $contributor): array
    {
        return $this->achievementRepository->findByContributor($contributor);
    }

    /**
     * Récupère l'historique XP d'un contributeur.
     *
     * @return XpHistory[]
     */
    public function getContributorXpHistory(Contributor $contributor, int $limit = 50): array
    {
        return $this->xpHistoryRepository->findByContributor($contributor, $limit);
    }

    /**
     * Récupère le rang d'un contributeur dans le leaderboard.
     */
    public function getContributorRank(Contributor $contributor): int
    {
        return $this->progressRepository->getRank($contributor);
    }

    /**
     * Récupère le leaderboard.
     *
     * @return ContributorProgress[]
     */
    public function getLeaderboard(int $limit = 10): array
    {
        return $this->progressRepository->getLeaderboard($limit);
    }
}
