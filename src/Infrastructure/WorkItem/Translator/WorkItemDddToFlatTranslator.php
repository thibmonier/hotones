<?php

declare(strict_types=1);

namespace App\Infrastructure\WorkItem\Translator;

use App\Domain\WorkItem\Entity\WorkItem as DddWorkItem;
use App\Entity\Timesheet as FlatTimesheet;

/**
 * Anti-Corruption Layer translator (DDD WorkItem → flat Timesheet).
 *
 * Sprint-020 EPIC-003 Phase 2 (US-098).
 *
 * **Synchronisation partielle** : ne touche PAS les champs structuraux
 * (`id`, `contributor`, `project`, `task`, `date`) — ils sont fixés à la
 * création côté flat. Synchronise uniquement les champs mutables côté DDD :
 * - `hours` (révision)
 * - `notes`
 *
 * Les rates (`cjm`/`tjm`) ne sont PAS synchronisés vers le flat — côté DDD ils
 * sont des snapshots figés (Risk Q4 mitigation), côté flat ils sont résolus
 * dynamiquement via Contributor.cjm property hook. Pas de redondance source.
 *
 * @see ADR-0008 ACL pattern
 * @see ADR-0015 Phase 2 décisions
 */
final class WorkItemDddToFlatTranslator
{
    public function applyTo(DddWorkItem $ddd, FlatTimesheet $flat): void
    {
        $flat->setHours((string) $ddd->getHours()->getValue());
        $flat->setNotes($ddd->getNotes());
        $flat->status = $ddd->getStatus()->value;
    }
}
