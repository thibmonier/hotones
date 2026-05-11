# US-090 — Render deploy fix

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> Source : observation thibmonier 2026-05-07. Déploiement Render KO.

- **Implements** : FR-OPS-12 — **Persona** : équipe dev, P-OPS — **Estimate** : 3 pts — **MoSCoW** : Must

### Card
**As** équipe dev
**I want** que le déploiement sur Render fonctionne à chaque push main
**So that** la prod (ou staging) reflète le code mergé.

### Acceptance Criteria
```
Given un push sur main
When Render déclenche build + deploy
Then déploiement réussit en < 10 min
And app répond 200 sur GET /health
```
```
Given erreur de build Render
When logs consultés
Then cause root identifiée et fixée
```

### Technical Notes
- Logs Render à analyser : build, runtime, healthcheck
- Causes courantes : env vars manquantes, build command obsolète, PHP version mismatch
- À synchroniser avec config Symfony 8 + PHP 8.5 récents
- Vérifier Dockerfile.prod si utilisé OU buildpack Render PHP
- DB connection : MariaDB managed sur Render OU externe — config DATABASE_URL

### Tasks
- [ ] T-090-01 [OPS] Analyser logs Render derniers échecs (1 h)
- [ ] T-090-02 [OPS] Identifier cause racine (env, PHP version, build cmd) (1 h)
- [ ] T-090-03 [OPS] Fix (config Render + Dockerfile / buildpack si nécessaire) (2 h)
- [ ] T-090-04 [TEST] Smoke test post-deploy (curl /health + login) (0,5 h)
- [ ] T-090-05 [DOC] Runbook déploiement Render (`docs/05-deployment/render.md`) (1 h)

---

## EPIC-002 — Observabilité & Performance (US-091..US-095)

> Source : atelier PO sprint-016 J1 (2026-05-07). Cf ADR-0012 stack
> observabilité (Sentry free tier — option C différer upgrade).

