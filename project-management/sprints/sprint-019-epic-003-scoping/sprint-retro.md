# Sprint Retrospective — Sprint 019

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 019 — EPIC-003 WorkItem & Profitability kickoff |
| Date | 2026-05-09 (clôture anticipée — sprint J2) |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Audit data prod AVANT design DDD** : 7 risks identifiés (Q3 critique cjm/tjm nullable) → US-097 a livré VOs `HourlyRate` non-null par construction qui mitigent dès Phase 1. ROI immédiat audit. |
| K-2 | **DDD Phase 1 strict scope** (Domain pur, pas d'ACL Phase 2 ni UC) : permet de livrer fondations sans bloquer décisions PO Phase 2 (task=NULL, doublons). |
| K-3 | **Coverage push 55 → 62 % via 3 Aggregate Roots majeurs** (Invoice, Order, Project). ROI optimal vs disperser sur petits VOs. Pattern à répéter pour sprint-020 step 10. |
| K-4 | **ADR-0014 OrbStack publié AVEC trigger réversibilité explicite** (>2 incidents/mois, regression compose, perf >2x). Pas de décision irréversible. |
| K-5 | **Recalibrage engagement ferme 12 pts atteint** (vs 8.5 sprint-018 et 10 sprint-017). Vélocité réaliste = capacité libre absorption marginale (1 pt non consommé sprint-019). |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Sub-epic B OPS reporté sprint-020** : Slack webhook + Sentry alerts + SMOKE config nécessitent credentials user → bloque côté dev. | Pattern atelier J0 (sprint-018 retro S-2 héritage) à étendre : prep credentials/secrets J0 avant sprint kickoff. |
| L-2 | **Docker Desktop crash continu sprint-019** → 5 PRs commit `--no-verify`. | ADR-0014 livré (#203) — install OrbStack côté dev sprint-019 fin doit éliminer pour sprint-020. |
| L-3 | **PHPStan validation déférée 100 % CI Docker** sur 5 PRs sprint-019. Risk : régression silencieuse côté Domain pure si CI down. | APCu pecl install Option A à exécuter sprint-019 fin (cf sprint-019 sub-epic D). |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **OPS prep J0 systématique** : avant chaque sprint kickoff, identifier les stories qui requirent credentials/secrets/config admin → bloquer tickets ces derniers J0 ou réallouer. | Évite holdover Sub-epic B sprint-019 → sprint-020. |
| S-2 | **Tests Domain pure host PHP** sans Docker = architecture validation. Continuer sur sprint-020 EPIC-003 Phase 2 (DoctrineDddWorkItemRepository — tests Integration Docker DB minimum). | Réduit dépendance Docker pour Unit tests → résilience env. |
| S-3 | **Atelier décisions PO Phase 2 sprint-020 J1** sur questions audit héritage (Q1 task=NULL, Q6 doublons). | Évite blocage US-098 Phase 2 ACL implementation. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Multiplier petits PRs docs-only** quand bundle cohérent possible. Sprint-019 a 5 PRs (200, 201, 202, 203 + 199 audit). Sub-epic D ENV-DOCKER + ENV-APCU bundlés #203 = bon. Sub-epic C TEST-COVERAGE-008 + 009 séparés #201 + #202 — pourrait être 1 PR. | Réduit overhead review + déploiement (sprint-018 retro M-1 héritage déjà identifié). |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Audit data systématique avant design DDD** pour chaque BC EPIC-003+ : sprint-020 audit Contributors sans CJM (Risk Q3) → informe Phase 2 ACL. Pattern sprint-021+ pour autres BCs. | Prévient bugs data integrity post-migration. |
| M-2 | **ADR avec trigger réversibilité mesurable** (sprint-019 ADR-0014 :>2 incidents/mois, perf >2x). Pattern sprint-020+ pour décisions techniques majeures. | Sortie facile si décision se révèle mauvaise. |

---

## 🎯 Actions concrètes Sprint 020

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Self-install OrbStack côté dev (héritage ADR-0014 A-2) | Tech Lead | Sprint-020 J1 |
| A-2 | Install pecl APCu PHP 8.5 brew (Option A) | Tech Lead | Sprint-020 J1 |
| A-3 | Atelier décisions PO Phase 2 (Q1 task=NULL exclus marge ? Q6 doublons dédup ?) | PO + Tech Lead | Sprint-020 J1 |
| A-4 | Configurer Slack webhook Render prod + staging (héritage sprint-019 B) | Tech Lead | Sprint-020 J1 |
| A-5 | Créer alert rules Sentry → Slack `#alerts-prod` (héritage sprint-019 B) | Tech Lead | Sprint-020 J1 |
| A-6 | Configurer SMOKE-PROD-EXTENDED OPS (user smoke + GH secrets + GH var) | Tech Lead | Sprint-020 J1 |
| A-7 | Script audit prod Contributors sans CJM (Risk Q3 audit héritage) | Tech Lead | Sprint-020 J2 |
| A-8 | Pattern OPS prep J0 documenté pour sprints futurs | Tech Lead | Sprint-020 retro |

---

## 📊 Directive Fondamentale

> « Indépendamment de ce que nous découvrons aujourd'hui, nous comprenons et
> croyons sincèrement que chacun a fait du mieux qu'il pouvait, étant donné
> ce qui était connu à ce moment-là, ses compétences et capacités, les
> ressources disponibles et la situation rencontrée. »

---

## 🚀 Sprint-019 takeaway

**Pattern recalibrage vélocité réussi** : 12 pts engagement ferme = 11 pts
livrés (92 %). Plus réaliste que sprints 017-018 explosifs 130 %/124 %.
Capacité libre 1 pt absorbée marginale.

EPIC-003 démarré sur fondations solides : audit + DDD Phase 1 livrés.
Phase 2 ACL = sprint-020. Roadmap visible 5-6 sprints (cf ADR-0013).

**Indicateur santé équipe** : 47 tests Domain WorkItem livrés sans Docker
(architecture hexagonale validée + DX résiliente). Coverage 62 % atteint
escalator step 9.

**Risk visible** : OPS manuel B reporté → pattern atelier J0 prep
credentials à instaurer (sprint-020 retro S-1).

---

## 🔗 Liens

- Sprint-019 review : `sprint-review.md`
- Sprint-018 retro : `../sprint-018-*/sprint-retro.md`
- Sprint-020 kickoff : `../sprint-020-*/sprint-goal.md`
- ADR-0013 EPIC-003 scope
- ADR-0014 OrbStack Mac recommandation
