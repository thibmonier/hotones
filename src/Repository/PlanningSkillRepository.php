<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Planning;
use App\Entity\PlanningSkill;
use App\Entity\Project;
use App\Entity\Skill;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<PlanningSkill>
 */
class PlanningSkillRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, PlanningSkill::class, $companyContext);
    }

    /**
     * Trouve toutes les compétences requises pour une affectation.
     *
     * @return PlanningSkill[]
     */
    public function findByPlanning(Planning $planning): array
    {
        return $this
            ->createCompanyQueryBuilder('ps')
            ->join('ps.skill', 's')
            ->andWhere('ps.planning = :planning')
            ->setParameter('planning', $planning)
            ->orderBy('ps.mandatory', 'DESC')
            ->addOrderBy('ps.requiredLevel', 'DESC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les compétences obligatoires d'une affectation.
     *
     * @return PlanningSkill[]
     */
    public function findMandatoryByPlanning(Planning $planning): array
    {
        return $this
            ->createCompanyQueryBuilder('ps')
            ->join('ps.skill', 's')
            ->andWhere('ps.planning = :planning')
            ->setParameter('planning', $planning)
            ->andWhere('ps.mandatory = :mandatory')
            ->setParameter('mandatory', true)
            ->orderBy('ps.requiredLevel', 'DESC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les affectations nécessitant une compétence donnée.
     *
     * @return PlanningSkill[]
     */
    public function findBySkill(Skill $skill): array
    {
        return $this
            ->createCompanyQueryBuilder('ps')
            ->join('ps.planning', 'p')
            ->andWhere('ps.skill = :skill')
            ->setParameter('skill', $skill)
            ->andWhere('p.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->orderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les affectations d'un projet avec leurs compétences requises.
     *
     * @return PlanningSkill[]
     */
    public function findByProject(Project $project): array
    {
        return $this
            ->createCompanyQueryBuilder('ps')
            ->join('ps.planning', 'p')
            ->join('ps.skill', 's')
            ->andWhere('p.project = :project')
            ->setParameter('project', $project)
            ->orderBy('p.startDate', 'ASC')
            ->addOrderBy('ps.mandatory', 'DESC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les compétences non satisfaites pour une affectation.
     *
     * @return PlanningSkill[]
     */
    public function findUnmetByPlanning(Planning $planning): array
    {
        $planningSkills = $this->findByPlanning($planning);
        $unmet          = [];

        foreach ($planningSkills as $planningSkill) {
            if (!$planningSkill->isMetByAssignedContributor()) {
                $unmet[] = $planningSkill;
            }
        }

        return $unmet;
    }

    /**
     * Trouve les compétences obligatoires non satisfaites.
     *
     * @return PlanningSkill[]
     */
    public function findUnmetMandatoryByPlanning(Planning $planning): array
    {
        $mandatorySkills = $this->findMandatoryByPlanning($planning);
        $unmet           = [];

        foreach ($mandatorySkills as $planningSkill) {
            if (!$planningSkill->isMetByAssignedContributor()) {
                $unmet[] = $planningSkill;
            }
        }

        return $unmet;
    }

    /**
     * Vérifie si un contributeur satisfait toutes les compétences obligatoires d'une affectation.
     */
    public function contributorMeetsMandatorySkills(Planning $planning, Contributor $contributor): bool
    {
        $mandatorySkills = $this->findMandatoryByPlanning($planning);

        foreach ($mandatorySkills as $planningSkill) {
            $met = false;

            foreach ($contributor->getContributorSkills() as $contributorSkill) {
                if ($contributorSkill->getSkill()?->getId() === $planningSkill->getSkill()?->getId()) {
                    if ($contributorSkill->getEffectiveLevel() >= $planningSkill->getRequiredLevel()) {
                        $met = true;

                        break;
                    }
                }
            }

            if (!$met) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcule le score de compatibilité d'un contributeur avec une affectation.
     */
    public function getContributorCompatibilityScore(Planning $planning, Contributor $contributor): int
    {
        $planningSkills = $this->findByPlanning($planning);

        if (empty($planningSkills)) {
            return 100;
        }

        $met   = 0;
        $total = 0;

        foreach ($planningSkills as $planningSkill) {
            $weight = $planningSkill->isMandatory() ? 2 : 1;
            $total += $weight;

            foreach ($contributor->getContributorSkills() as $contributorSkill) {
                if ($contributorSkill->getSkill()?->getId() === $planningSkill->getSkill()?->getId()) {
                    if ($contributorSkill->getEffectiveLevel() >= $planningSkill->getRequiredLevel()) {
                        $met += $weight;

                        break;
                    }
                }
            }
        }

        return (int) round(($met / $total) * 100);
    }

    /**
     * Trouve les affectations où le collaborateur assigné ne satisfait pas les compétences obligatoires.
     *
     * @return Planning[]
     */
    public function findPlanningsWithSkillMismatch(): array
    {
        $plannings = $this
            ->getEntityManager()
            ->getRepository(Planning::class)
            ->createQueryBuilder('p')
            ->andWhere('p.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getResult();

        $mismatched = [];

        foreach ($plannings as $planning) {
            if (!$this->contributorMeetsMandatorySkills($planning, $planning->getContributor())) {
                $mismatched[] = $planning;
            }
        }

        return $mismatched;
    }
}
