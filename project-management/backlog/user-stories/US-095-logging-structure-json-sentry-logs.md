# US-095 — Logging structuré JSON + Sentry Logs

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

- **Implements** : EPIC-002 — **Persona** : équipe dev — **Estimate** : 3 pts — **MoSCoW** : Could — **Sprint** : 018

### Card
**As** équipe dev
**I want** logs Symfony en JSON structuré ingérés par Sentry Logs (free tier)
**So that** correlation logs ↔ traces ↔ errors unifiée dans Sentry.

### Tasks (sprint-018)
- [ ] T-095-01 [OPS] Monolog handler JSON formatter + sentry_logs handler
- [ ] T-095-02 [BE] ContextProcessor : tenant_id + user_id sur tous logs
- [ ] T-095-03 [TEST] Tests Integration log → Sentry capture
