# US-080 — Endpoint status public

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> INFERRED from `StatusController`, `/status`.

- **Implements**: FR-OPS-02 — **Persona**: système, P-007 — **Estimate**: 2 pts — **MoSCoW**: Should

### Card
**As** visiteur ou client
**I want** consulter `/status`
**So that** je connais l'état du service en cas d'incident perçu.

### Acceptance Criteria
```
When GET /status
Then page publique listant services + uptime récent
```

---

