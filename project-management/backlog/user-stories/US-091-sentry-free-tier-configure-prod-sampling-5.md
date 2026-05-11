# US-091 — Sentry free tier configuré prod (sampling 5 %)

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

- **Implements** : EPIC-002 — **Persona** : équipe dev / PO — **Estimate** : 5 pts — **MoSCoW** : Must

### Card
**As** équipe dev
**I want** Sentry instrumentation prod (errors + traces) avec sampling agressif
**So that** détection précoce des bugs sans dépasser quota free tier.

### Acceptance Criteria
```
Given push main mergé
When Render deploy complète
Then SENTRY_DSN injecté → errors + traces remontent dashboard Sentry
And traces_sample_rate = 0.05 (5 % transactions)
And profiles_sample_rate = 0.10 (10 % des transactions échantillonnées profilées)
And send_default_pii = false (RGPD)
```
```
Given Sentry quota transactions à 80 %
When alerte Sentry déclenche
Then mail PO + considération upgrade Team plan ($25/mois)
```

### Technical Notes
- `sentry/sentry-symfony ^5.8.3` déjà installé sprint-002
- `config/packages/sentry.yaml` : ajout sampling rates + send_default_pii
- `render.yaml` + `render.staging.yaml` : SENTRY_DSN env var sync false
- DSN dans Render dashboard manual

### Tasks
- [x] T-091-01 [OPS] sentry.yaml sampling + RGPD config (0,5 h) ✅ PR US-091/US-092
- [x] T-091-02 [OPS] render.yaml + staging SENTRY_DSN env var (0,5 h) ✅
- [x] T-091-03 [DOC] ADR-0012 décision stack + sampling strategy (1 h) ✅
- [ ] T-091-04 [OPS] DSN configuré dans Render dashboard prod + staging (0,5 h)
- [ ] T-091-05 [TEST] Verification post-deploy : provoquer erreur + vérifier dashboard Sentry (1 h)

---

