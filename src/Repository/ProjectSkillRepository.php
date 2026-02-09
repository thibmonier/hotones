<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\ProjectSkill;
use App\Entity\Skill;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<ProjectSkill>
 */
class ProjectSkillRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, ProjectSkill::class, $companyContext);
    }

    /**
     * Trouve toutes les compétences requises pour un projet.
     *
     * @return ProjectSkill[]
     */
    public function findByProject(Project $project): array
    {
        return $this
            ->createCompanyQueryBuilder('ps')
            ->join('ps.skill', 's')
            ->andWhere('ps.project = :project')
            ->setParameter('project', $project)
            ->orderBy('ps.priority', 'DESC')
            ->addOrderBy('s.category', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les compétences critiques (priorité haute ou indispensable) d'un projet.
     *
     * @return ProjectSkill[]
     */
    public function findCriticalByProject(Project $project): array
    {
        return $this
            ->createCompanyQueryBuilder('ps')
            ->join('ps.skill', 's')
            ->andWhere('ps.project = :project')
            ->setParameter('project', $project)
            ->andWhere('ps.priority >= :minPriority')
            ->setParameter('minPriority', ProjectSkill::PRIORITY_HIGH)
            ->orderBy('ps.priority', 'DESC')
            ->addOrderBy('ps.requiredLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les projets nécessitant une compétence donnée.
     *
     * @return ProjectSkill[]
     */
    public function findBySkill(Skill $skill): array
    {
        return $this
            ->createCompanyQueryBuilder('ps')
            ->join('ps.project', 'p')
            ->andWhere('ps.skill = :skill')
            ->setParameter('skill', $skill)
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('ps.priority', 'DESC')
            ->addOrderBy('ps.requiredLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un contributeur satisfait toutes les compétences critiques d'un projet.
     */
    public function contributorMeetsCriticalSkills(Project $project, Contributor $contributor): bool
    {
        $criticalSkills = $this->findCriticalByProject($project);

        foreach ($criticalSkills as $projectSkill) {
            if (!$projectSkill->isMetByContributor($contributor)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Trouve les contributeurs pouvant travailler sur un projet (satisfont les compétences critiques).
     *
     * @param Contributor[] $candidates
     *
     * @return Contributor[]
     */
    public function findEligibleContributors(Project $project, array $candidates): array
    {
        $eligible = [];

        foreach ($candidates as $contributor) {
            if ($this->contributorMeetsCriticalSkills($project, $contributor)) {
                $eligible[] = $contributor;
            }
        }

        return $eligible;
    }

    /**
     * Calcule le score de compatibilité d'un contributeur avec un projet.
     * Retourne un score de 0 à 100.
     */
    public function getContributorCompatibilityScore(Project $project, Contributor $contributor): int
    {
        $projectSkills = $this->findByProject($project);

        if (empty($projectSkills)) {
            return 100;
        }

        $met   = 0;
        $total = 0;

        foreach ($projectSkills as $projectSkill) {
            $weight = match ($projectSkill->getPriority()) {
                ProjectSkill::PRIORITY_CRITICAL => 4,
                ProjectSkill::PRIORITY_HIGH     => 3,
                ProjectSkill::PRIORITY_MEDIUM   => 2,
                default                         => 1,
            };

            $total += $weight;

            if ($projectSkill->isMetByContributor($contributor)) {
                $met += $weight;
            }
        }

        return (int) round(($met / $total) * 100);
    }

    /**
     * Trouve les compétences manquantes pour un contributeur sur un projet.
     *
     * @return ProjectSkill[]
     */
    public function findMissingSkillsForContributor(Project $project, Contributor $contributor): array
    {
        $projectSkills = $this->findByProject($project);
        $missing       = [];

        foreach ($projectSkills as $projectSkill) {
            if (!$projectSkill->isMetByContributor($contributor)) {
                $missing[] = $projectSkill;
            }
        }

        return $missing;
    }
}
