<?php

declare(strict_types=1);

namespace App\Application\WorkItem\UseCase\RecordWorkItem;

/**
 * EPIC-003 Phase 3 (sprint-021 US-099) — Command saisie WorkItem.
 *
 * Champs ADR-0016 Q1.2 :
 * - obligatoires : date, contributorIdLegacy, projectIdLegacy, hours
 * - optionnels : taskIdLegacy, comment
 *
 * Step heures Q1.3 = 0.25h (validation côté Domain VO WorkedHours).
 *
 * Override + role :
 * - userOverride (Q2.4) : false par défaut. Si true, override seuil journalier
 *   accepté + audit log (PSR-3 logger).
 * - authorIsManager (Q3.2) : true si user appelant a ROLE_MANAGER ou ROLE_ADMIN.
 *   Si true, WorkItem créé direct status VALIDATED (transition validate
 *   auto-déclenchée).
 *
 * Rates :
 * - costRateAmount, billedRateAmount : caller (Controller / form / API) résout
 *   depuis Contributor cjm/tjm OU EmploymentPeriod actif. Pas de coupling UC
 *   à logique rates resolution sprint-021. Sprint-022+ peut centraliser via
 *   Domain Service `RateResolver` si besoin.
 */
final readonly class RecordWorkItemCommand
{
    public function __construct(
        public int $contributorIdLegacy,
        public int $projectIdLegacy,
        public string $date,
        public float $hours,
        public float $costRateAmount,
        public float $billedRateAmount,
        public ?int $taskIdLegacy = null,
        public ?string $comment = null,
        public bool $userOverride = false,
        public bool $authorIsManager = false,
    ) {
    }
}
