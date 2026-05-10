<?php

declare(strict_types=1);

namespace App\Infrastructure\WorkItem\Persistence\Doctrine;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\WorkItem\Entity\WorkItem as DddWorkItem;
use App\Domain\WorkItem\Exception\WorkItemNotFoundException;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use App\Entity\Contributor as FlatContributor;
use App\Entity\Project as FlatProject;
use App\Entity\Timesheet as FlatTimesheet;
use App\Infrastructure\WorkItem\Translator\WorkItemDddToFlatTranslator;
use App\Infrastructure\WorkItem\Translator\WorkItemFlatToDddTranslator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Anti-Corruption Layer adapter — implements DDD `WorkItemRepositoryInterface`
 * by delegating to the legacy `Timesheet` flat entity via Doctrine.
 *
 * Sprint-020 EPIC-003 Phase 2 (US-098).
 *
 * **Hypothèse legacy bridge** : `WorkItemId::fromLegacyInt($timesheetFlatId)`.
 * Phase 4 future ajoutera UUID natif si `WorkItem` devient first-class.
 *
 * @see ADR-0008 ACL pattern
 * @see ADR-0013 EPIC-003 scope
 * @see ADR-0015 Phase 2 décisions
 */
final readonly class DoctrineDddWorkItemRepository implements WorkItemRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkItemFlatToDddTranslator $flatToDdd,
        private WorkItemDddToFlatTranslator $dddToFlat,
    ) {
    }

    public function findById(WorkItemId $id): DddWorkItem
    {
        $workItem = $this->findByIdOrNull($id);
        if ($workItem === null) {
            throw WorkItemNotFoundException::withId($id);
        }

        return $workItem;
    }

    public function findByIdOrNull(WorkItemId $id): ?DddWorkItem
    {
        if (!$id->isLegacy()) {
            return null;
        }

        $flat = $this->entityManager->find(FlatTimesheet::class, $id->toLegacyInt());

        return $flat instanceof FlatTimesheet ? $this->flatToDdd->translate($flat) : null;
    }

    /**
     * @return array<DddWorkItem>
     */
    public function findByProject(ProjectId $projectId): array
    {
        if (!$projectId->isLegacy()) {
            return [];
        }

        $project = $this->entityManager->find(FlatProject::class, $projectId->toLegacyInt());
        if (!$project instanceof FlatProject) {
            return [];
        }

        $timesheets = $this->entityManager
            ->getRepository(FlatTimesheet::class)
            ->findBy(['project' => $project]);

        return array_map(
            $this->flatToDdd->translate(...),
            $timesheets,
        );
    }

    /**
     * @return array<DddWorkItem>
     */
    public function findByContributorAndDateRange(
        ContributorId $contributorId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): array {
        if (!$contributorId->isLegacy()) {
            return [];
        }

        $contributor = $this->entityManager->find(FlatContributor::class, $contributorId->toLegacyInt());
        if (!$contributor instanceof FlatContributor) {
            return [];
        }

        $qb = $this->entityManager->getRepository(FlatTimesheet::class)
            ->createQueryBuilder('t')
            ->andWhere('t.contributor = :contributor')->setParameter('contributor', $contributor)
            ->andWhere('t.date >= :from')->setParameter('from', $from)
            ->andWhere('t.date <= :to')->setParameter('to', $to)
            ->orderBy('t.date', 'ASC');

        $timesheets = $qb->getQuery()->getResult();

        return array_map(
            $this->flatToDdd->translate(...),
            $timesheets,
        );
    }

    /**
     * Sprint-020 ADR-0015 Q2 invariant journalier — charge tous les WorkItems
     * d'un contributeur pour une date donnée.
     *
     * @return array<DddWorkItem>
     */
    public function findByContributorAndDate(
        ContributorId $contributorId,
        DateTimeImmutable $date,
    ): array {
        if (!$contributorId->isLegacy()) {
            return [];
        }

        $contributor = $this->entityManager->find(FlatContributor::class, $contributorId->toLegacyInt());
        if (!$contributor instanceof FlatContributor) {
            return [];
        }

        $timesheets = $this->entityManager
            ->getRepository(FlatTimesheet::class)
            ->findBy([
                'contributor' => $contributor,
                'date' => $date,
            ]);

        return array_map(
            $this->flatToDdd->translate(...),
            $timesheets,
        );
    }

    /**
     * Phase 2 ACL : la création d'un Timesheet flat reste de la responsabilité
     * du UC RecordWorkItem (sprint-021 Phase 3). Cette méthode synchronise
     * uniquement les champs mutables (hours + notes) si le flat existe déjà.
     *
     * Pour création, le UC doit instancier le flat Timesheet directement
     * (relations FK vers Contributor/Project/Task obligatoires) puis appeler
     * `applyTo()` du DddToFlatTranslator pour figer hours+notes.
     */
    public function save(DddWorkItem $workItem): void
    {
        if (!$workItem->getId()->isLegacy()) {
            throw new RuntimeException('WorkItem with non-legacy ID cannot be persisted Phase 2 — Phase 4 future feature');
        }

        $flat = $this->entityManager->find(FlatTimesheet::class, $workItem->getId()->toLegacyInt());
        if (!$flat instanceof FlatTimesheet) {
            throw new RuntimeException(sprintf('WorkItem %s not found in flat layer — création doit passer par UC RecordWorkItem (Phase 3)', $workItem->getId()->getValue()));
        }

        $this->dddToFlat->applyTo($workItem, $flat);
        $this->entityManager->persist($flat);
        $this->entityManager->flush();
    }
}
