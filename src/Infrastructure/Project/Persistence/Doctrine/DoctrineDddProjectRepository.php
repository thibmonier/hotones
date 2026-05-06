<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project as DddProject;
use App\Domain\Project\Exception\ProjectNotFoundException;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Project\ValueObject\ProjectType;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Project\Translator\ProjectDddToFlatTranslator;
use App\Infrastructure\Project\Translator\ProjectFlatToDddTranslator;
use App\Repository\ProjectRepository as FlatProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Anti-Corruption Layer adapter — implements the DDD `ProjectRepositoryInterface`
 * by delegating to the legacy `ProjectRepository`.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0006 Project BC coexistence
 */
final readonly class DoctrineDddProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private FlatProjectRepository $flatRepository,
        private EntityManagerInterface $entityManager,
        private ProjectFlatToDddTranslator $flatToDdd,
        private ProjectDddToFlatTranslator $dddToFlat,
    ) {
    }

    public function findById(ProjectId $id): DddProject
    {
        $project = $this->findByIdOrNull($id);
        if ($project === null) {
            throw new ProjectNotFoundException(sprintf('Project %s not found', (string) $id));
        }

        return $project;
    }

    public function findByIdOrNull(ProjectId $id): ?DddProject
    {
        if (!$id->isLegacy()) {
            return null;
        }

        $flat = $this->flatRepository->find($id->toLegacyInt());

        return $flat !== null ? $this->flatToDdd->translate($flat) : null;
    }

    /**
     * @return array<DddProject>
     */
    public function findAll(): array
    {
        return array_map(
            fn (FlatProject $flat): DddProject => $this->flatToDdd->translate($flat),
            $this->flatRepository->findAll(),
        );
    }

    public function findByReference(string $reference): ?DddProject
    {
        // Phase 2 scope: out (flat doesn't expose findByReference)
        return null;
    }

    /**
     * @return array<DddProject>
     */
    public function findByClientId(ClientId $clientId): array
    {
        if (!$clientId->isLegacy()) {
            return [];
        }

        $flatClients = $this->flatRepository->findBy(['client' => $clientId->toLegacyInt()]);

        return array_map(
            fn (FlatProject $flat): DddProject => $this->flatToDdd->translate($flat),
            $flatClients,
        );
    }

    /**
     * @return array<DddProject>
     */
    public function findByStatus(ProjectStatus $status): array
    {
        $flatStatus = match ($status) {
            ProjectStatus::DRAFT, ProjectStatus::ACTIVE, ProjectStatus::ON_HOLD => 'active',
            ProjectStatus::COMPLETED => 'completed',
            ProjectStatus::CANCELLED => 'cancelled',
        };

        $flats = $this->flatRepository->findBy(['status' => $flatStatus]);

        return array_map(
            fn (FlatProject $flat): DddProject => $this->flatToDdd->translate($flat),
            $flats,
        );
    }

    /**
     * @return array<DddProject>
     */
    public function findByType(ProjectType $type): array
    {
        // Phase 2 scope: out (flat doesn't carry the FORFAIT/REGIE distinction directly on Project)
        return [];
    }

    /**
     * @return array<DddProject>
     */
    public function findActive(): array
    {
        return $this->findByStatus(ProjectStatus::ACTIVE);
    }

    /**
     * @return array<DddProject>
     */
    public function findInternal(): array
    {
        $flats = $this->flatRepository->findBy(['client' => null]);

        return array_map(
            fn (FlatProject $flat): DddProject => $this->flatToDdd->translate($flat),
            $flats,
        );
    }

    public function save(DddProject $project): void
    {
        $id = $project->getId();
        if (!$id->isLegacy()) {
            throw new RuntimeException('Saving DDD Project with pure UUID id is not yet supported during Phase 2.');
        }

        $flat = $this->flatRepository->find($id->toLegacyInt())
            ?? throw new ProjectNotFoundException(sprintf('Cannot update Project %s: not found', (string) $id));

        // Resolve flat client (if any). Phase 2 ACL: client must already exist
        // in legacy table. Internal projects (DDD isInternal()) keep null.
        $client = $flat->client;

        $this->dddToFlat->applyTo($project, $flat, $client);

        $this->entityManager->persist($flat);
        $this->entityManager->flush();
    }

    public function delete(DddProject $project): void
    {
        $id = $project->getId();
        if (!$id->isLegacy()) {
            throw new RuntimeException('Deleting DDD Project with pure UUID id not yet supported');
        }

        $flat = $this->flatRepository->find($id->toLegacyInt())
            ?? throw new ProjectNotFoundException(sprintf('Cannot delete Project %s: not found', (string) $id));

        $this->entityManager->remove($flat);
        $this->entityManager->flush();
    }
}
