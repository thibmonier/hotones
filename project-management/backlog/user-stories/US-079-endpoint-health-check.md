# US-079 — Endpoint health-check

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> INFERRED from `HealthCheckController` + `/health` PUBLIC_ACCESS.

- **Implements**: FR-OPS-01 — **Persona**: système — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** plateforme d'orchestration (load-balancer, monitoring)
**I want** appeler `GET /health` pour vérifier vitalité (DB, Redis, app)
**So that** je gère le routing et l'alerting infra.

### Acceptance Criteria
```
When GET /health
Then 200 + JSON {status, db, redis, version}
```
```
Given dépendance KO
Then 503 + détails
```

---

