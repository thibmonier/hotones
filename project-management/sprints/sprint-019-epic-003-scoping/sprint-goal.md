# Sprint 019 — EPIC-003 WorkItem & Profitability kickoff + EPIC-002 stragglers

| Champ | Valeur |
|---|---|
| Numéro | 019 |
| Début | 2026-06-05 |
| Fin | 2026-06-19 |
| Durée | 10 jours ouvrés |
| Capacité | 13 pts (recalibrage post sprints 017+018 130 %/124 %) |
| Engagement ferme | **12 pts** + 1 pt capacité libre |

---

## 🎯 Sprint Goal

> « Démarrer EPIC-003 WorkItem & Profitability (Phase 1 DDD entity + audit
> données existantes), finir stragglers EPIC-002 (OPS Slack + Sentry alert
> rules + SMOKE activation), pousser escalator coverage 55 → 60 % via
> Order/Project aggregates Domain, nettoyer dette environnement (alternative
> Docker Mac + APCu local). »

**Atelier PO EPIC-003 effectué J0 (sprint-018)** — décisions tranchées,
ADR-0013 publié. Scope sprint-019 réorienté : **EPIC-003 Phase 1 démarre
immédiatement** (vs scoping initial reporté sprint-019).

---

## Backlog engagé (12 pts)

### Sub-epic A — EPIC-003 Phase 1 démarrage (4 pts)

> Décisions PO atelier (cf ADR-0013) :
> - Scope = WorkItem & Profitability (cœur métier agence)
> - MVP = calcul marge projet temps réel + alerte > seuil dépassement
> - Timeline = 4-6 sprints (sprints 020-024 ou 025)
> - Stack = State machine Symfony Workflow component
> - 3 KPIs nouveaux dashboard : DSO, temps facturation, % projets adoption

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-097 | EPIC-003 Phase 1 — DDD `WorkItem` entity + ValueObjects (`WorkItemId`, `HourlyRate`, `WorkedHours`) + interfaces | 3 | Pattern strangler fig sprints 008-013. Pas d'ACL Phase 2 encore (Phase 2 sprint-021). |
| AUDIT-WORKITEM-DATA | Audit qualité données existantes `WorkItem.cost` flat (irrégularités attendues) | 1 | Risk identifié ADR-0013 conséquences négatives. Output report + recommandations migration. |

### Sub-epic B — EPIC-002 stragglers OPS (1 pt)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-094-OPS | Configurer Slack webhook Render + Sentry alert rules `#alerts-prod` | 0.5 | Héritage sprint-017 + 018 actions |
| SMOKE-OPS | Configurer user smoke prod + GH secrets + GH var `SMOKE_EXTENDED_ENABLED=true` + premier run validation | 0.5 | Héritage sprint-018 SMOKE-PROD-EXTENDED |

### Sub-epic C — Coverage escalator (4 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| TEST-COVERAGE-008 | Step 8 : push coverage 55 → 60 % via Domain Order Aggregate Root | 2 | OrderLine + OrderSection + state machine SUBMITTED → APPROVED → PAID |
| TEST-COVERAGE-009 | Step 9 : push coverage 60 → 62 % via Domain Project Aggregate Root | 2 | Project state machine + WorkItem coût + marge (synergie sub-epic A audit) |

### Sub-epic D — Dette environnement (3 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| ENV-DOCKER-ALTERNATIVE | Investiguer + documenter alternative Docker Desktop Mac (OrbStack / Colima) | 2 | Sprint-018 retro A-5 |
| ENV-APCU-LOCAL | Install APCu pecl local OU documenter workflow PHPStan déféré CI | 1 | Sprint-018 retro A-6 |

---

## Capacité libre (1 pt)

À allouer J7-J8 selon avancement :
- **TEST-COVERAGE-010** step 10 push 62 → 65 % — 2 pts (Domain ValueObjects partials)
- BUFFER : EPIC-003 Phase 1+ (Repository interface + Domain Service `MarginCalculator` skeleton)

---

## ADR-0013 référence

Atelier PO EPIC-003 sprint-018 J0 (anticipé) — décisions formalisées
[ADR-0013 EPIC-003 WorkItem & Profitability](../../docs/02-architecture/adr/0013-epic-003-workitem-profitability-scope.md) :

| Décision | Valeur |
|---|---|
| Scope | B — WorkItem & Profitability |
| MVP | calcul marge projet temps réel + alerte > seuil dépassement |
| Timeline | 4-6 sprints |
| Stack | State machine Symfony Workflow |
| KPIs succès | DSO + temps facturation + % adoption marge temps réel |

Trigger abandon EPIC-003 (cf ADR-0013) :
1. > 6 sprints sans MVP livré → réduire scope ou pivot EPIC-004
2. < 3 utilisations alerte dépassement / mois post prod → fonctionnalité gadget
3. Bug data integrity > 5 % vs comptable → bloquer scaling

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
| Sprint Planning P2 (équipe technique tasks) | 2026-06-05 14:00 |
| Daily standup | Quotidien 09:30 |
| Sprint Review | 2026-06-19 14:00 |
| Rétrospective | 2026-06-19 16:30 |

> **Note** : pas d'atelier EPIC-003 J1 (déjà tenu sprint-018 J0 anticipé +
> ADR-0013 publié). Sprint-019 = exécution directe.

---

## 🎯 Actions héritées sprint-017 + 018 retro

| ID | Action | Owner | Statut |
|---|---|---|---|
| Sprint-017 A-1 | Slack webhook Render prod + staging | Tech Lead | ✅ inclus sprint-019 sub-epic B |
| Sprint-017 A-2 | Sentry alert rules → Slack | Tech Lead | ✅ inclus sprint-019 sub-epic B |
| Sprint-018 A-3 | SMOKE OPS config | Tech Lead | ✅ inclus sprint-019 sub-epic B |
| Sprint-018 A-4 | Atelier EPIC-003 | PO + Tech Lead | ✅ tenu J0 + ADR-0013 |
| Sprint-018 A-5 | Alternative Docker Desktop | Tech Lead | ✅ inclus sprint-019 sub-epic D |
| Sprint-018 A-6 | APCu local OU `--no-verify` doc | Tech Lead | ✅ inclus sprint-019 sub-epic D |

---

## 📊 Indicateurs cibles fin sprint

- Coverage 60-62 % (post sprint-018 step 7 = 55 %)
- ADR-0013 EPIC-003 publié ✅ (anticipé J0)
- US-097 DDD WorkItem entity Phase 1 livré
- AUDIT-WORKITEM-DATA report + recommandations migration
- Slack webhook Render opérationnel
- Sentry alert rules actives `#alerts-prod`
- SMOKE-PROD-EXTENDED en run automatique chaque deploy
- Alternative Docker Mac recommandée + ADR-0014 si décision

---

## 🔗 Liens

- ADR-0013 EPIC-003 scope : `docs/02-architecture/adr/0013-epic-003-workitem-profitability-scope.md`
- Sprint-018 review : `../sprint-018-*/sprint-review.md`
- Sprint-018 retro : `../sprint-018-*/sprint-retro.md`
- Runbook on-call : `docs/05-deployment/oncall-runbook.md`
- Logging conventions : `docs/05-deployment/logging-conventions.md`
