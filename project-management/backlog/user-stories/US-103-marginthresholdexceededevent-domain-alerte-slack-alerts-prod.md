# US-103 — `MarginThresholdExceededEvent` Domain + alerte Slack `#alerts-prod`

> **BC**: TIM  |  **Source**: archived TIM.md (split 2026-05-11)

- **Implements**: EPIC-003 Phase 3 capacité libre — **Persona**: P-002 manager + P-003 directeur — **Estimate**: 2-3 pts — **MoSCoW**: Should — **Sprint**: 021

### Card
**As** manager (P-002) ou directeur (P-003)
**I want** une alerte Slack `#alerts-prod` automatique dès qu'un projet dépasse le seuil de marge négative (< 10 %)
**So that** je détecte les dérives projet précocement (vs audit post-mortem fin projet) et peux ajuster scope/staffing.

### Acceptance Criteria

```
Given Project marge calculée < 10 % (seuil défaut MARGIN_ALERT_THRESHOLD=0.10)
When Project recalcule marge (event WorkItemRecorded handler)
Then MarginThresholdExceededEvent dispatché
And handler async consume event
```

```
Given MarginThresholdExceededEvent reçu par handler
When SlackAlertingService::send (réutilisé US-094)
Then message Slack #alerts-prod posté
And message contient : projet nom + marge % + coût total + facturé total + lien dashboard
```

```
Given MarginThresholdExceededEvent dispatché 2x consécutivement même projet
When 2ème handler exécution
Then dedup logique : alerte non re-postée si dernière alerte < 24h pour ce projet
```

```
Given Slack webhook URL non configuré (SLACK_WEBHOOK_URL vide)
When MarginThresholdExceededEvent dispatché
Then handler log warning local (pas d'exception)
And tests Unit valident comportement degraded (sans webhook)
```

### Technical Notes
- ADR-0016 Q4.x + Q5.2 seuil 10 % défaut + Q6.4 capacité libre
- ADR-0016 A-7
- Réutilise US-094 `SlackAlertingService` (sprint-017 #189)
- Configurabilité hiérarchique seuil (Q5.1 D) reportée sprint-022+ (OQ-3 default)
- **AT-3.3 acté** : nouveau Domain Event `MarginThresholdExceededEvent` (`src/Domain/Project/Event/`) co-existe avec legacy `App\Event\LowMarginAlertEvent`. Legacy event marqué `@deprecated` PHPDoc dès sprint-021 (annotation : « Deprecated since EPIC-003 Phase 3 — use `App\Domain\Project\Event\MarginThresholdExceededEvent`. Removal planned sprint-022+ after `AlertDetectionService` refactor. »). Pas de break consumers actuels.
- Strangler fig : `AlertDetectionService` legacy continue dispatcher `LowMarginAlertEvent` sprint-021. Refactor sprint-022+ pour dispatcher `MarginThresholdExceededEvent` à la place + suppression legacy event.
- ⚠️ OPS-PREP-J0 sprint-021 PRE-1 : Slack webhook URL `#alerts-prod` configuré prod J0 ?
  - 🟢 A : webhook configuré → US-103 testable end-to-end prod (✅ go)
  - 🟡 B : webhook non configuré → US-103 livré tests Unit + staging only (livraison partielle)
  - 🔴 C : US-103 OUT capacité libre, reallocate TEST-COVERAGE-011
- ⚠️ OQ-4 ADR-0016 : sprint-021 = event + alerting (UC `CalculateProjectMargin` complet sprint-022)
- Tests Integration Docker DB + Symfony Messenger transport `async_margin`

---
