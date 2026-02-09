<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\ContributorTechnology;
use App\Entity\Project;
use App\Entity\Technology;
use App\Security\CompanyContext;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<ContributorTechnology>
 */
class ContributorTechnologyRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, ContributorTechnology::class, $companyContext);
    }

    /**
     * Trouve toutes les technologies d'un contributeur.
     *
     * @return ContributorTechnology[]
     */
    public function findByContributor(Contributor $contributor): array
    {
        return $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.technology', 't')
            ->andWhere('ct.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('t.category', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contributeurs maîtrisant une technologie donnée.
     *
     * @return ContributorTechnology[]
     */
    public function findByTechnology(Technology $technology): array
    {
        return $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.contributor', 'c')
            ->andWhere('ct.technology = :technology')
            ->setParameter('technology', $technology)
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('ct.managerAssessmentLevel', 'DESC')
            ->addOrderBy('ct.selfAssessmentLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les technologies d'un contributeur par catégorie.
     *
     * @return array<string, ContributorTechnology[]>
     */
    public function findByContributorGroupedByCategory(Contributor $contributor): array
    {
        $technologies = $this->findByContributor($contributor);

        $byCategory = [];
        foreach ($technologies as $ct) {
            $category = $ct->getTechnology()?->getCategory() ?? 'Autre';
            if (!isset($byCategory[$category])) {
                $byCategory[$category] = [];
            }
            $byCategory[$category][] = $ct;
        }

        return $byCategory;
    }

    /**
     * Trouve les contributeurs experts sur une technologie donnée.
     *
     * @return ContributorTechnology[]
     */
    public function findExpertsByTechnology(Technology $technology): array
    {
        return $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.contributor', 'c')
            ->andWhere('ct.technology = :technology')
            ->setParameter('technology', $technology)
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere('ct.managerAssessmentLevel = :expert OR
                (ct.managerAssessmentLevel IS NULL AND ct.selfAssessmentLevel = :expert)')
            ->setParameter('expert', ContributorTechnology::LEVEL_EXPERT)
            ->orderBy('ct.yearsOfExperience', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contributeurs récemment actifs sur une technologie.
     *
     * @return ContributorTechnology[]
     */
    public function findRecentByTechnology(Technology $technology): array
    {
        $sixMonthsAgo = new DateTime('-6 months');

        return $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.contributor', 'c')
            ->andWhere('ct.technology = :technology')
            ->setParameter('technology', $technology)
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere('ct.lastUsedDate >= :sixMonthsAgo')
            ->setParameter('sixMonthsAgo', $sixMonthsAgo)
            ->orderBy('ct.lastUsedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contributeurs ayant une technologie à un niveau minimum.
     *
     * @return ContributorTechnology[]
     */
    public function findByTechnologyAndMinLevel(Technology $technology, int $minLevel): array
    {
        return $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.contributor', 'c')
            ->andWhere('ct.technology = :technology')
            ->setParameter('technology', $technology)
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere('(ct.managerAssessmentLevel >= :minLevel) OR
                (ct.managerAssessmentLevel IS NULL AND ct.selfAssessmentLevel >= :minLevel)')
            ->setParameter('minLevel', $minLevel)
            ->orderBy('ct.managerAssessmentLevel', 'DESC')
            ->addOrderBy('ct.selfAssessmentLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contributeurs souhaitant monter en compétence sur une technologie.
     *
     * @return ContributorTechnology[]
     */
    public function findWantingToImprove(Technology $technology): array
    {
        return $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.contributor', 'c')
            ->andWhere('ct.technology = :technology')
            ->setParameter('technology', $technology)
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere('ct.wantsToImprove = :wantsToImprove')
            ->setParameter('wantsToImprove', true)
            ->orderBy('ct.selfAssessmentLevel', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contributeurs adaptés aux technologies d'un projet.
     *
     * @return array<int, array{contributor: Contributor, score: int, matching: int, total: int}>
     */
    public function findContributorsForProjectTechnologies(Project $project): array
    {
        $projectTechnologies = $project->getTechnologies();

        if ($projectTechnologies->isEmpty()) {
            return [];
        }

        $contributors  = [];
        $technologyIds = [];

        foreach ($projectTechnologies as $tech) {
            $technologyIds[] = $tech->getId();
        }

        // Récupérer toutes les associations pour ces technologies
        $results = $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.contributor', 'c')
            ->join('ct.technology', 't')
            ->andWhere('ct.technology IN (:technologies)')
            ->setParameter('technologies', $projectTechnologies->toArray())
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere('ct.wantsToUse = :wantsToUse')
            ->setParameter('wantsToUse', true)
            ->getQuery()
            ->getResult();

        // Regrouper par contributeur
        $byContributor = [];
        foreach ($results as $ct) {
            $contributorId = $ct->getContributor()->getId();
            if (!isset($byContributor[$contributorId])) {
                $byContributor[$contributorId] = [
                    'contributor'  => $ct->getContributor(),
                    'technologies' => [],
                ];
            }
            $byContributor[$contributorId]['technologies'][] = $ct;
        }

        // Calculer le score pour chaque contributeur
        $totalTechnologies = count($technologyIds);

        foreach ($byContributor as $contributorId => $data) {
            $matchingCount = count($data['technologies']);
            $totalScore    = 0;

            foreach ($data['technologies'] as $ct) {
                $totalScore += $ct->getStaffingScore();
            }

            $averageScore  = $matchingCount > 0 ? $totalScore / $matchingCount : 0;
            $coverageBonus = ($matchingCount / $totalTechnologies) * 50;

            $contributors[] = [
                'contributor' => $data['contributor'],
                'score'       => (int) round($averageScore + $coverageBonus),
                'matching'    => $matchingCount,
                'total'       => $totalTechnologies,
            ];
        }

        // Trier par score décroissant
        usort($contributors, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $contributors;
    }

    /**
     * Calcule la distribution des niveaux pour une technologie.
     *
     * @return array<int, int> [level => count]
     */
    public function getLevelDistributionForTechnology(Technology $technology): array
    {
        $results = $this
            ->createCompanyQueryBuilder('ct')
            ->select('CASE
                    WHEN ct.managerAssessmentLevel IS NOT NULL THEN ct.managerAssessmentLevel
                    ELSE ct.selfAssessmentLevel
                END as effectiveLevel', 'COUNT(ct.id) as count')
            ->andWhere('ct.technology = :technology')
            ->setParameter('technology', $technology)
            ->groupBy('effectiveLevel')
            ->getQuery()
            ->getResult();

        $distribution = [];
        foreach ($results as $result) {
            $distribution[(int) $result['effectiveLevel']] = (int) $result['count'];
        }

        return $distribution;
    }

    /**
     * Trouve les écarts d'évaluation (self vs manager) sur les technologies.
     *
     * @return ContributorTechnology[]
     */
    public function findAssessmentGaps(): array
    {
        return $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.contributor', 'c')
            ->join('ct.technology', 't')
            ->andWhere('ct.managerAssessmentLevel IS NOT NULL')
            ->andWhere('ct.selfAssessmentLevel IS NOT NULL')
            ->andWhere('ct.managerAssessmentLevel != ct.selfAssessmentLevel')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('ABS(ct.managerAssessmentLevel - ct.selfAssessmentLevel)', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les technologies obsolètes (non utilisées depuis > 2 ans).
     *
     * @return ContributorTechnology[]
     */
    public function findObsoleteTechnologies(): array
    {
        $twoYearsAgo = new DateTime('-2 years');

        return $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.contributor', 'c')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere('ct.lastUsedDate < :twoYearsAgo')
            ->setParameter('twoYearsAgo', $twoYearsAgo)
            ->orderBy('ct.lastUsedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les besoins en formation (veut s'améliorer mais niveau < confirmé).
     *
     * @return ContributorTechnology[]
     */
    public function findTrainingNeeds(): array
    {
        return $this
            ->createCompanyQueryBuilder('ct')
            ->join('ct.contributor', 'c')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere('ct.wantsToImprove = :wantsToImprove')
            ->setParameter('wantsToImprove', true)
            ->andWhere(
                '(ct.managerAssessmentLevel IS NOT NULL AND ct.managerAssessmentLevel < :confirmedLevel) OR
                (ct.managerAssessmentLevel IS NULL AND ct.selfAssessmentLevel < :confirmedLevel)',
            )
            ->setParameter('confirmedLevel', ContributorTechnology::LEVEL_CONFIRMED)
            ->orderBy('ct.selfAssessmentLevel', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
