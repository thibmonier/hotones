<?php

declare(strict_types=1);

namespace App\Infrastructure\WorkItem\Translator;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectTaskId;
use App\Domain\WorkItem\Entity\WorkItem as DddWorkItem;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use App\Entity\Timesheet as FlatTimesheet;
use DateTimeImmutable;
use RuntimeException;

/**
 * Anti-Corruption Layer translator (flat Timesheet → DDD WorkItem).
 *
 * Sprint-020 EPIC-003 Phase 2 (US-098).
 *
 * Stateless. Résout les rates `cjm`/`tjm` côté Contributor (property hook
 * suit EmploymentPeriod actif → Contributor direct → throw si null car
 * `HourlyRate` non-null par construction — Risk Q3 audit mitigé Phase 1).
 *
 * Conséquence design ADR-0015 Q1 : `taskId` nullable, allocation niveau
 * projet si Timesheet.task = null.
 *
 * @see ADR-0008 ACL pattern
 * @see ADR-0013 EPIC-003 scope
 * @see ADR-0015 Phase 2 décisions
 * @see docs/02-architecture/epic-003-audit-existing-data.md
 */
final class WorkItemFlatToDddTranslator
{
    public function translate(FlatTimesheet $flat): DddWorkItem
    {
        $id = WorkItemId::fromLegacyInt(
            $flat->getId() ?? throw new RuntimeException('Cannot translate unsaved Timesheet'),
        );

        $projectId = ProjectId::fromLegacyInt(
            $flat->getProject()->getId() ?? throw new RuntimeException('Timesheet has no project'),
        );

        $contributor = $flat->getContributor();
        $contributorId = ContributorId::fromLegacyInt(
            $contributor->getId() ?? throw new RuntimeException('Timesheet has no contributor'),
        );

        $taskId = null;
        $task = $flat->getTask();
        if ($task !== null && $task->getId() !== null) {
            $taskId = ProjectTaskId::fromLegacyInt($task->getId());
        }

        $workedOn = DateTimeImmutable::createFromInterface($flat->getDate());
        $hours = WorkedHours::fromDecimalString($flat->getHours());

        // Risk Q3 mitigation : property hook Contributor::$cjm/tjm résout
        // EmploymentPeriod actif puis fallback Contributor direct. Si null
        // → HourlyRate::fromDailyRateDecimalString throw explicite (vs coût
        // 0 silencieux côté flat). OPS doit corriger via app:audit:contributors-cjm
        // avant deploy.
        $costRate = HourlyRate::fromDailyRateDecimalString($contributor->cjm);
        $billedRate = HourlyRate::fromDailyRateDecimalString($contributor->tjm);

        return DddWorkItem::reconstitute(
            id: $id,
            projectId: $projectId,
            contributorId: $contributorId,
            workedOn: $workedOn,
            hours: $hours,
            costRate: $costRate,
            billedRate: $billedRate,
            extra: [
                'taskId' => $taskId,
                'notes' => $flat->getNotes(),
                'status' => WorkItemStatus::tryFrom($flat->status) ?? WorkItemStatus::DRAFT,
                'createdAt' => new DateTimeImmutable(),
                'updatedAt' => null,
            ],
        );
    }
}
