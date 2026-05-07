# Sprint 018 — EPIC-002 Finition + Dette technique

| Champ | Valeur |
|---|---|
| Numéro | 018 |
| Début | 2026-05-22 |
| Fin | 2026-06-05 |
| Durée | 10 jours ouvrés |
| Capacité | 12 pts (vélocité moyenne 6 sprints récents) |
| Engagement ferme | **8.5 pts** + 3.5 pts capacité libre |

---

## 🎯 Sprint Goal

> « Finir EPIC-002 (smoke prod étendu + correlation ID frontend) + nettoyer
> dette technique détectée sprints 016-017 (Invoice UC bug + ContributorController DDD Phase 3 + chore gitignore). EPIC-003 scoping atelier PO J3 pour prochain quarter. »

---

## Backlog engagé (8.5 pts)

### Sub-epic A — EPIC-002 finition (5 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| SMOKE-PROD-EXTENDED | Étendre smoke test post-deploy : login admin + create project (assertions business) | 3 | Héritage sprint-016 retro L-2 |
| US-096 | X-Request-Id response header (correlation ID exposée frontend) | 2 | Suite US-095 (`request_id` injecté dans logs). Pattern `X-Correlation-Id` côté headers. |

### Sub-epic B — Dette technique (3 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-INVOICE-DRAFT-UC-INVOICENUMBER-INIT-FIX | Fix `CreateInvoiceDraftUseCase` : `isset($flat->invoiceNumber)` sur typed property non-init → utiliser `??=` ou Reflection-safe check | 1 | Bug détecté sprint-017 TEST-COVERAGE-006 |
| Contributor DDD route Phase 3 | `ContributorController` route DDD active (héritage sprint-017 capacité libre option A) | 2 | Phase 4 strangler fig BC Contributor |

### Sub-epic C — Chores (0.5 pt)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| chore-gitignore-save-db | Push `.gitignore` `save-db/` sur main (PR isolée trivial) | 0.5 | Hook pre-commit auto-add répété sprints 016-017 |

---

## Capacité libre (3.5 pts)

À allouer J3-J5 selon avancement ferme :
- **EPIC-003 atelier PO** (1 pt) — J3 : 5 questions arbitrées scoping prochain EPIC (cf format atelier sprint-016 K-1)
- **TEST-COVERAGE-007** (2 pts) — escalator step 7 : push 50 → 55 % via tests Application/Domain BC moins couverts
- BUFFER : SMOKE-PROD-EXTENDED scope étendu (signup + invoice draft) si capacité reste

---

## Definition of Done

- ✅ Tests Unit + Integration passent
- ✅ PHPStan max 0 erreur
- ✅ CS-Fixer + Rector + Deptrac + Mago OK
- ✅ Snyk Security clean
- ✅ Smoke test post-deploy green sur Render
- ✅ Documentation à jour (runbook + ADR si nouvelle décision)
- ✅ PR review validée + merge linéaire main

---

## 🔗 Cérémonies

| Cérémonie | Date prévue |
|---|---|
| Sprint Planning P1 (PO scope) | 2026-05-22 09:00 |
| Sprint Planning P2 (équipe technique tasks) | 2026-05-22 14:00 |
| Atelier EPIC-003 scoping (PO + Tech Lead) | 2026-05-26 (J3) |
| Daily standup | Quotidien 09:30 |
| Sprint Review | 2026-06-05 14:00 |
| Rétrospective | 2026-06-05 16:30 |

---

## 🎯 Actions héritées sprint-017 retro

| ID | Action | Owner | Statut |
|---|---|---|---|
| A-1 | Configurer `SLACK_WEBHOOK_URL` Render prod + staging | Tech Lead | À faire J1 |
| A-2 | Créer alert rules Sentry → Slack `#alerts-prod` | Tech Lead | À faire J1 |
| A-3 | File story Invoice UC bug | PO | ✅ inclus sprint-018 sub-epic B |
| A-4 | Chore gitignore save-db | Tech Lead | ✅ inclus sprint-018 sub-epic C |

---

## 📊 Indicateurs cibles fin sprint

- Coverage 50 % (post sprint-017) → maintenu ou +1-2 % via TEST-COVERAGE-007
- Smoke test couvre 4 routes critiques (vs 2 actuellement : homepage + /health)
- Correlation ID `X-Request-Id` visible côté frontend (devtools network)
- Dette `Invoice::$invoiceNumber` non-init résolue → tests Unit happy path
  ré-activés (bonus +1 % coverage)
- ContributorController route DDD active (Phase 3 stranger fig BC complete)

---

## 🔗 Liens

- Sprint-017 review : `../sprint-017-*/sprint-review.md`
- Sprint-017 retro : `../sprint-017-*/sprint-retro.md`
- ADR-0012 : stack observabilité (Sentry free tier)
- Runbook on-call : `docs/05-deployment/oncall-runbook.md`
- Logging conventions : `docs/05-deployment/logging-conventions.md`
