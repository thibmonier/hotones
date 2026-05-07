<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Translator;

use App\Domain\Project\Entity\Project as DddProject;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Entity\Client as FlatClient;
use App\Entity\Project as FlatProject;

/**
 * Anti-Corruption Layer translator (DDD → flat) for the Project BC.
 *
 * Stateless service.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 */
final class ProjectDddToFlatTranslator
{
    public function applyTo(DddProject $ddd, FlatProject $flat, ?FlatClient $client = null): void
    {
        $flat->name = $ddd->getName();
        $flat->status = $this->mapStatus($ddd->getStatus());
        $flat->description = $ddd->getDescription();

        // Internal projects have null client; otherwise inject the resolved one.
        $flat->client = $client;
    }

    /**
     * DDD status (5 cases) → flat status (3 cases) — lossy mapping:
     *   DRAFT, ACTIVE, ON_HOLD → 'active'
     *   COMPLETED              → 'completed'
     *   CANCELLED              → 'cancelled'
     *
     * Note: ON_HOLD and DRAFT collapse to 'active' on flat side. ADR-0006
     * documents this Phase 2 limitation.
     */
    private function mapStatus(ProjectStatus $status): string
    {
        return match ($status) {
            ProjectStatus::DRAFT, ProjectStatus::ACTIVE, ProjectStatus::ON_HOLD => 'active',
            ProjectStatus::COMPLETED => 'completed',
            ProjectStatus::CANCELLED => 'cancelled',
        };
    }
}
