# US-094 — Alerting Sentry → Slack

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

- **Implements** : EPIC-002 — **Persona** : équipe dev — **Estimate** : 3 pts — **MoSCoW** : Should — **Sprint** : 017
- **Statut** : 🚫 **OUT BACKLOG** (sprint-022 ADR-0017 — 4ᵉ holdover signal arrêt)

### ⚠️ Décision AT-3 = B Out Backlog (2026-05-10)

Holdover sprint-017 → 020 (4 sprints consécutifs). Atelier OPS-PREP-J0
J-2 sprint-022 a appliqué runbook §3 décision matrix : owner non
confirmé J0 + 4 credentials simultanés non obtenus → **Out backlog**.

Replan sprint dédié OPS quand :
1. Owner unique fixé (Tech Lead OU PO backup activé)
2. 4 credentials/access confirmés simultanément :
   - Slack workspace incoming webhook URL
   - Sentry org admin token
   - GitHub repo Settings (push secrets `SMOKE_USER_*`)
   - DBA prod (création user smoke)

Voir ADR-0017 — `docs/02-architecture/adr/0017-ops-sub-epic-b-out-backlog.md`.

### Card
**As** équipe dev
**I want** alertes Sentry routées vers canal Slack `#alerts-prod`
**So that** détection < 5 min des erreurs critiques + quota Sentry approche limite.

### Acceptance Criteria
```
Given errors 500 prod > 10/heure OU quota Sentry > 80 %
When Sentry alert rule déclenche
Then message Slack `#alerts-prod` avec lien vers issue
```

### Tasks (sprint TBD post-replan)
- [ ] T-094-01 [OPS] Slack workspace incoming webhook créé
- [ ] T-094-02 [OPS] Sentry alert rules (errors / quota / slow transactions)
- [ ] T-094-03 [DOC] Runbook on-call (escalation + ack)

---

