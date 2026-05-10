<?php

declare(strict_types=1);

namespace App\Service\Alerting;

/**
 * EPIC-003 Phase 3 (sprint-021 US-103) — interface extraite pour
 * découplage tests Application Layer (Listeners) sans avoir à mocker
 * l'implémentation `final readonly`.
 *
 * Pattern strangler fig — sprint-022+ peut introduire d'autres
 * implémentations (Discord, MS Teams, no-op test stub) sans toucher les
 * consumers Application Layer.
 */
interface SlackAlertingInterface
{
    /**
     * Envoie une alerte Slack `#alerts-prod` (ou canal configuré).
     *
     * Comportement dégradé silent si webhook URL non configuré (logger
     * debug + retourne false).
     */
    public function sendAlert(string $title, string $body, AlertSeverity $severity = AlertSeverity::INFO): bool;
}
