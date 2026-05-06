<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Translator;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project as DddProject;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Project\ValueObject\ProjectType;
use App\Entity\Project as FlatProject;
use DateTimeImmutable;

use const PHP_INT_MAX;

use RuntimeException;

/**
 * Anti-Corruption Layer translator (flat → DDD) for the Project BC.
 *
 * Stateless service.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0006 Project BC coexistence (status superset 5 vs 3)
 */
final class ProjectFlatToDddTranslator
{
    public function translate(FlatProject $flat): DddProject
    {
        $id = ProjectId::fromLegacyInt($flat->id ?? throw new RuntimeException('Cannot translate unsaved Project'));

        // Project flat may have null client (internal projects); fallback to legacy:0 marker.
        $clientId = $flat->client !== null && $flat->client->id !== null
            ? ClientId::fromLegacyInt($flat->client->id)
            : ClientId::fromLegacyInt(PHP_INT_MAX);

        $projectType = $this->mapProjectType($flat);
        $status = $this->mapStatus($flat->status);

        return DddProject::reconstitute(
            id: $id,
            name: $flat->name,
            clientId: $clientId,
            projectType: $projectType,
            isInternal: $flat->client === null,
            extra: [
                'status' => $status,
                'description' => $flat->description ?? null,
                'budget' => null, // Flat budget computed via tasks — out of scope ACL Phase 2
                'soldAmount' => null,
                'startDate' => null,
                'endDate' => null,
                'completedAt' => null,
                'repositoryUrl' => null,
                'documentationUrl' => null,
                'notes' => null,
                'createdAt' => new DateTimeImmutable(),
            ],
        );
    }

    private function mapProjectType(FlatProject $flat): ProjectType
    {
        // Flat exposes type via Order.contractType (forfait/regie). Project itself
        // has no direct type; default to FORFAIT for ACL Phase 2.
        return ProjectType::FORFAIT;
    }

    /**
     * Flat status (active/completed/cancelled) → DDD status.
     * DDD has 5 cases (DRAFT/ACTIVE/ON_HOLD/COMPLETED/CANCELLED). Flat is a
     * subset — map directly with active=ACTIVE.
     */
    private function mapStatus(string $flatStatus): ProjectStatus
    {
        return match ($flatStatus) {
            'active' => ProjectStatus::ACTIVE,
            'completed' => ProjectStatus::COMPLETED,
            'cancelled' => ProjectStatus::CANCELLED,
            default => ProjectStatus::ACTIVE,
        };
    }
}
