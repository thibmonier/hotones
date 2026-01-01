<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\ContributorSkill;
use App\Entity\Skill;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<ContributorSkill>
 */
class ContributorSkillRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, ContributorSkill::class, $companyContext);
    }

    /**
     * Trouve toutes les compétences d'un contributeur.
     *
     * @return ContributorSkill[]
     */
    public function findByContributor(Contributor $contributor): array
    {
        return $this->createCompanyQueryBuilder('cs')
            ->join('cs.skill', 's')
            ->where('cs.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('s.category', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contributeurs possédant une compétence donnée.
     *
     * @return ContributorSkill[]
     */
    public function findBySkill(Skill $skill): array
    {
        return $this->createCompanyQueryBuilder('cs')
            ->join('cs.contributor', 'c')
            ->where('cs.skill = :skill')
            ->setParameter('skill', $skill)
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('cs.managerAssessmentLevel', 'DESC')
            ->addOrderBy('cs.selfAssessmentLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les compétences d'un contributeur par catégorie.
     *
     * @return array<string, ContributorSkill[]>
     */
    public function findByContributorGroupedByCategory(Contributor $contributor): array
    {
        $skills = $this->findByContributor($contributor);

        $byCategory = [];
        foreach ($skills as $contributorSkill) {
            $category = $contributorSkill->getSkill()->getCategory();
            if (!isset($byCategory[$category])) {
                $byCategory[$category] = [];
            }
            $byCategory[$category][] = $contributorSkill;
        }

        return $byCategory;
    }

    /**
     * Trouve les contributeurs experts dans une compétence donnée.
     *
     * @return ContributorSkill[]
     */
    public function findExpertsBySkill(Skill $skill): array
    {
        return $this->createCompanyQueryBuilder('cs')
            ->join('cs.contributor', 'c')
            ->where('cs.skill = :skill')
            ->setParameter('skill', $skill)
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere('cs.managerAssessmentLevel = :expert OR (cs.managerAssessmentLevel IS NULL AND cs.selfAssessmentLevel = :expert)')
            ->setParameter('expert', ContributorSkill::LEVEL_EXPERT)
            ->orderBy('cs.dateAcquired', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la distribution des niveaux pour une compétence.
     *
     * @return array<int, int> [level => count]
     */
    public function getLevelDistributionForSkill(Skill $skill): array
    {
        $results = $this->createCompanyQueryBuilder('cs')
            ->select(
                'CASE
                    WHEN cs.managerAssessmentLevel IS NOT NULL THEN cs.managerAssessmentLevel
                    ELSE cs.selfAssessmentLevel
                END as effectiveLevel',
                'COUNT(cs.id) as count',
            )
            ->where('cs.skill = :skill')
            ->setParameter('skill', $skill)
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
     * Trouve les écarts d'évaluation (self vs manager).
     *
     * @return ContributorSkill[]
     */
    public function findAssessmentGaps(): array
    {
        return $this->createCompanyQueryBuilder('cs')
            ->join('cs.contributor', 'c')
            ->join('cs.skill', 's')
            ->where('cs.managerAssessmentLevel IS NOT NULL')
            ->andWhere('cs.selfAssessmentLevel IS NOT NULL')
            ->andWhere('cs.managerAssessmentLevel != cs.selfAssessmentLevel')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('ABS(cs.managerAssessmentLevel - cs.selfAssessmentLevel)', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de compétences par niveau pour un contributeur.
     *
     * @return array<int, int> [level => count]
     */
    public function countByLevelForContributor(Contributor $contributor): array
    {
        $results = $this->createCompanyQueryBuilder('cs')
            ->select(
                'CASE
                    WHEN cs.managerAssessmentLevel IS NOT NULL THEN cs.managerAssessmentLevel
                    ELSE cs.selfAssessmentLevel
                END as effectiveLevel',
                'COUNT(cs.id) as count',
            )
            ->where('cs.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->groupBy('effectiveLevel')
            ->getQuery()
            ->getResult();

        $countByLevel = [];
        foreach ($results as $result) {
            $countByLevel[(int) $result['effectiveLevel']] = (int) $result['count'];
        }

        return $countByLevel;
    }

    /**
     * Trouve les contributeurs ayant une compétence à un niveau minimum.
     *
     * @return ContributorSkill[]
     */
    public function findBySkillAndMinLevel(Skill $skill, int $minLevel): array
    {
        return $this->createCompanyQueryBuilder('cs')
            ->join('cs.contributor', 'c')
            ->where('cs.skill = :skill')
            ->setParameter('skill', $skill)
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->andWhere(
                '(cs.managerAssessmentLevel >= :minLevel) OR
                (cs.managerAssessmentLevel IS NULL AND cs.selfAssessmentLevel >= :minLevel)',
            )
            ->setParameter('minLevel', $minLevel)
            ->orderBy('cs.managerAssessmentLevel', 'DESC')
            ->addOrderBy('cs.selfAssessmentLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
