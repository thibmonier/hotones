<?php

namespace App\Repository;

use App\Entity\NpsSurvey;
use App\Entity\Project;
use App\Security\CompanyContext;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<NpsSurvey>
 */
class NpsSurveyRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, NpsSurvey::class, $companyContext);
    }

    /**
     * Récupère toutes les enquêtes pour un projet, triées par date d'envoi.
     */
    public function findByProject(Project $project): array
    {
        return $this->createCompanyQueryBuilder('n')
            ->where('n.project = :project')
            ->setParameter('project', $project)
            ->orderBy('n.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une enquête par son token.
     */
    public function findByToken(string $token): ?NpsSurvey
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Récupère les enquêtes en attente de réponse pour un projet.
     */
    public function findPendingByProject(Project $project): array
    {
        return $this->createCompanyQueryBuilder('n')
            ->where('n.project = :project')
            ->andWhere('n.status = :status')
            ->setParameter('project', $project)
            ->setParameter('status', NpsSurvey::STATUS_PENDING)
            ->orderBy('n.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le score NPS moyen pour un projet.
     * NPS = % Promoteurs - % Détracteurs.
     */
    public function calculateNpsScore(Project $project): ?float
    {
        $surveys = $this->createCompanyQueryBuilder('n')
            ->where('n.project = :project')
            ->andWhere('n.status = :status')
            ->andWhere('n.score IS NOT NULL')
            ->setParameter('project', $project)
            ->setParameter('status', NpsSurvey::STATUS_COMPLETED)
            ->getQuery()
            ->getResult();

        if (empty($surveys)) {
            return null;
        }

        $total      = count($surveys);
        $promoters  = 0;
        $detractors = 0;

        foreach ($surveys as $survey) {
            $category = $survey->getCategory();
            if ($category === 'promoter') {
                ++$promoters;
            } elseif ($category === 'detractor') {
                ++$detractors;
            }
        }

        // NPS = (% promoteurs - % détracteurs)
        return (($promoters / $total) - ($detractors / $total)) * 100;
    }

    /**
     * Récupère les statistiques NPS pour un projet.
     */
    public function getStatsByProject(Project $project): array
    {
        $surveys = $this->createCompanyQueryBuilder('n')
            ->where('n.project = :project')
            ->andWhere('n.status = :status')
            ->andWhere('n.score IS NOT NULL')
            ->setParameter('project', $project)
            ->setParameter('status', NpsSurvey::STATUS_COMPLETED)
            ->getQuery()
            ->getResult();

        $total = count($surveys);
        if ($total === 0) {
            return [
                'total'         => 0,
                'promoters'     => 0,
                'passives'      => 0,
                'detractors'    => 0,
                'nps_score'     => null,
                'average_score' => null,
            ];
        }

        $promoters  = 0;
        $passives   = 0;
        $detractors = 0;
        $totalScore = 0;

        foreach ($surveys as $survey) {
            $totalScore += $survey->getScore();
            $category = $survey->getCategory();
            if ($category === 'promoter') {
                ++$promoters;
            } elseif ($category === 'passive') {
                ++$passives;
            } else {
                ++$detractors;
            }
        }

        $npsScore = (($promoters / $total) - ($detractors / $total)) * 100;

        return [
            'total'         => $total,
            'promoters'     => $promoters,
            'passives'      => $passives,
            'detractors'    => $detractors,
            'nps_score'     => round($npsScore, 1),
            'average_score' => round($totalScore / $total, 1),
        ];
    }

    /**
     * Marque les enquêtes expirées comme expirées.
     */
    public function markExpiredSurveysAsExpired(): int
    {
        return $this->createCompanyQueryBuilder('n')
            ->update()
            ->set('n.status', ':expiredStatus')
            ->where('n.status = :pendingStatus')
            ->andWhere('n.expiresAt < :now')
            ->setParameter('expiredStatus', NpsSurvey::STATUS_EXPIRED)
            ->setParameter('pendingStatus', NpsSurvey::STATUS_PENDING)
            ->setParameter('now', new DateTime())
            ->getQuery()
            ->execute();
    }
}
