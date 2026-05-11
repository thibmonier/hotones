# US-083 — Reporting CSP

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> INFERRED from `CspReportController`, `/csp/report`.

- **Implements**: FR-OPS-05 — **Persona**: système — **Estimate**: 2 pts — **MoSCoW**: Should

### Card
**As** plateforme
**I want** collecter les violations Content-Security-Policy
**So that** je détecte les contenus tiers ou injections.

### Acceptance Criteria
```
Given navigateur détecte violation
When POST /csp/report
Then violation persistée (rate-limited)
And tableau de bord dev `/csp/violations` (en dev) accessible
```

---

