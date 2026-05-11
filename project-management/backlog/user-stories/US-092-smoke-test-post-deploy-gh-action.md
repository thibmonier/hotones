# US-092 — Smoke test post-deploy GH Action

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

- **Implements** : EPIC-002 — **Persona** : équipe dev — **Estimate** : 3 pts — **MoSCoW** : Must

### Card
**As** équipe dev
**I want** smoke test automatique homepage + /health après chaque merge main
**So that** détection immédiate des régressions production (cf bug US-090 vécu 4 mois).

### Acceptance Criteria
```
Given push main mergé
When Render deploy complète (~5-10 min)
Then GH Action `post-deploy-smoke.yml` exécute :
  - GET / → 200 + body contient "HotOnes"
  - GET /health → 200 + Content-Type: application/json (pas octet-stream)
  - body NE CONTIENT PAS '<?php' (régression US-090)
And workflow échoue si dépassement 5 min wait
```

### Technical Notes
- Workflow `.github/workflows/post-deploy-smoke.yml`
- Trigger : push main + workflow_dispatch
- PROD_URL = `https://hotones.onrender.com` (statique, pas secret)
- MAX_WAIT_SECONDS = 300 (5 min) pour cold start free tier (mais starter activé donc cold start nul)

### Tasks
- [x] T-092-01 [OPS] Workflow post-deploy-smoke.yml (1,5 h) ✅
- [x] T-092-02 [OPS] Wait /health 200 + smoke / + smoke /health (1,5 h) ✅
- [ ] T-092-03 [OPS] (sprint-017) Slack webhook si fail
- [x] T-092-04 [DOC] runbook update : section smoke test post-deploy (0,5 h) ✅

---

