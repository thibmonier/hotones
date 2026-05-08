# Sprint 019 — EPIC-003 scoping + EPIC-002 stragglers

| Champ | Valeur |
|---|---|
| Numéro | 019 |
| Début | 2026-06-05 |
| Fin | 2026-06-19 |
| Durée | 10 jours ouvrés |
| Capacité | 13 pts (recalibrage post sprints 017+018 130 %/124 %) |
| Engagement ferme | **9 pts** + 4 pts capacité libre |

---

## 🎯 Sprint Goal

> « Tenir atelier PO EPIC-003 (J1) pour scoper prochain bounded context business
> majeur, finir stragglers EPIC-002 (OPS Slack + Sentry alert rules + SMOKE
> activation), pousser escalator coverage 55 → 60 % via Order/Project
> aggregates Domain. »

---

## Backlog engagé (9 pts)

### Sub-epic A — EPIC-003 scoping (1 pt)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| EPIC-003-KICKOFF | Atelier PO 5 questions arbitrées + ADR-0013 (stack/scope/budget/MVP/trigger) | 1 | Pattern atelier sprint-016 + sprint-019 |

### Sub-epic B — EPIC-002 stragglers OPS (1 pt)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-094-OPS | Configurer Slack webhook Render + Sentry alert rules `#alerts-prod` | 0.5 | Héritage sprint-017 + 018 actions |
| SMOKE-OPS | Configurer user smoke prod + GH secrets + GH var `SMOKE_EXTENDED_ENABLED=true` + premier run validation | 0.5 | Héritage sprint-018 SMOKE-PROD-EXTENDED |

### Sub-epic C — Coverage escalator (4 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| TEST-COVERAGE-008 | Step 8 : push coverage 55 → 60 % via Domain Order Aggregate Root | 2 | OrderLine + OrderSection + state machine SUBMITTED → APPROVED → PAID |
| TEST-COVERAGE-009 | Step 9 : push coverage 60 → 62 % via Domain Project Aggregate Root | 2 | Project state machine + WorkItem coût + marge |

### Sub-epic D — Dette environnement (3 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| ENV-DOCKER-ALTERNATIVE | Investiguer + documenter alternative Docker Desktop Mac (OrbStack / Colima) | 2 | Sprint-018 retro A-5 |
| ENV-APCU-LOCAL | Install APCu pecl local OU documenter workflow PHPStan déféré CI | 1 | Sprint-018 retro A-6 |

---

## Capacité libre (4 pts)

À allouer J3-J5 selon avancement :
- **EPIC-003 première story** (selon scope atelier) — 2-4 pts
- **TEST-COVERAGE-010** step 10 push 62 → 65 % — 2 pts (ValueObjects partials)
- BUFFER : SMOKE-PROD-EXTENDED scope étendu (signup + invoice draft cycle) — 3 pts si traction PO

---

## 🎯 Atelier EPIC-003 — 5 questions PO J1

> Pattern réutilisé sprint-016 (ADR-0012). À tenir J1 sprint-019, output
> ADR-0013.

1. **Scope EPIC-003** : quel bounded context business attaquer après EPIC-002 ?
   Candidats backlog :
   - **Time tracking** (RunningTimer + Timesheet existants flat → DDD aggregate)
   - **WorkItem & Profitability** (cœur business métier agence)
   - **Invoicing automation** (devis signé → invoice draft auto-généré)
   - **Reporting & analytics avancé** (drill-down KPIs sprint-017 dashboard)

2. **MVP minimum** : quelles 2-3 stories absolument livrables ce trimestre ?

3. **Budget complexité** : EPIC-002 = 8 sprints (016-018 + 019 stragglers).
   EPIC-003 timeline cible : 4-6 sprints ?

4. **Stack tech change requis** ? Doctrine event listeners suffisent OU besoin
   workflow engine externe (Camunda / Temporal) ?

5. **Mesure succès** : KPI dashboard (sprint-017 US-093) à étendre ? Indicateur
   adoption nouvelle fonctionnalité ?

---

## Definition of Done

- ✅ Tests Unit + Integration passent
- ✅ PHPStan max 0 erreur (CI Docker)
- ✅ CS-Fixer + Rector + Deptrac + Mago OK
- ✅ Snyk Security clean
- ✅ Smoke test post-deploy green sur Render (minimum + extended si activé)
- ✅ Documentation à jour (runbook + ADR si nouvelle décision)
- ✅ PR review validée + merge linéaire main
- ✅ Vélocité tracking moyenne mobile 6 sprints (sprint-018 retro S-1 héritage)

---

## 🔗 Cérémonies

| Cérémonie | Date prévue |
|---|---|
| Sprint Planning P1 (PO scope) | 2026-06-05 09:00 |
| **Atelier EPIC-003 scoping** (PO + Tech Lead) | **2026-06-05 J1 14:00** (~2h) |
| Sprint Planning P2 (équipe technique tasks) | 2026-06-05 16:30 |
| Daily standup | Quotidien 09:30 |
| Sprint Review | 2026-06-19 14:00 |
| Rétrospective | 2026-06-19 16:30 |

---

## 🎯 Actions héritées sprint-017 + 018 retro

| ID | Action | Owner | Statut |
|---|---|---|---|
| Sprint-017 A-1 | Slack webhook Render prod + staging | Tech Lead | ✅ inclus sprint-019 sub-epic B |
| Sprint-017 A-2 | Sentry alert rules → Slack | Tech Lead | ✅ inclus sprint-019 sub-epic B |
| Sprint-018 A-3 | SMOKE OPS config | Tech Lead | ✅ inclus sprint-019 sub-epic B |
| Sprint-018 A-4 | Atelier EPIC-003 | PO + Tech Lead | ✅ inclus sprint-019 sub-epic A |
| Sprint-018 A-5 | Alternative Docker Desktop | Tech Lead | ✅ inclus sprint-019 sub-epic D |
| Sprint-018 A-6 | APCu local OU `--no-verify` doc | Tech Lead | ✅ inclus sprint-019 sub-epic D |

---

## 📊 Indicateurs cibles fin sprint

- Coverage 60-62 % (post sprint-018 step 7 = 55 %)
- EPIC-003 ADR-0013 publié
- Slack webhook Render opérationnel
- Sentry alert rules actives `#alerts-prod`
- SMOKE-PROD-EXTENDED en run automatique chaque deploy
- Alternative Docker Mac recommandée + ADR-0014 si décision

---

## 🔗 Liens

- Sprint-018 review : `../sprint-018-*/sprint-review.md`
- Sprint-018 retro : `../sprint-018-*/sprint-retro.md`
- ADR-0012 : stack observabilité (Sentry free tier)
- Runbook on-call : `docs/05-deployment/oncall-runbook.md`
- Logging conventions : `docs/05-deployment/logging-conventions.md`
