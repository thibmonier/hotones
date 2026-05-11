# Sprint 024 — EPIC-003 Phase 4 Kickoff (KPIs business)

| Champ | Valeur |
|---|---|
| Numéro | 024 |
| Début | 2026-05-27 (kickoff prévu) |
| Fin | 2026-06-10 (clôture cible) |
| Durée | 10 jours ouvrés |
| Capacité | **12 pts ferme + 1-2 pts libre** (4ᵉ confirmation recalibrage durable) |
| Engagement ferme | **TBD** (Sprint Planning P1) |
| Statut backlog | **Provisoire** — atelier OPS-PREP-J0 J-2 |

---

## 🎯 Sprint Goal (provisoire)

> « EPIC-003 Phase 4 kickoff : KPIs business (DSO + temps facturation +
> adoption marge temps réel). Démarrage US-110..US-112. Décision PO
> PRE-5 Render redeploy (6ᵉ sprint consécutif holdover → trigger
> réversibilité ADR-0017 pattern). Pré-allocation explicite cap libre
> (vs slot reserved vide sprint-023 retro ST-1). »

---

## ⚠️ Pré-requis J0 obligatoires

| ID | Action | Owner | Deadline | Statut |
|---|---|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 J-2 sprint-024 (runbook §2 + matrix §3) | PO + Tech Lead | Sprint-024 J-2 | ⏳ A-1 héritage |
| PRE-2 | Mesurer coverage actuel post sprint-023 (CI report → 70 % vérifié) | Tech Lead | Sprint-024 J-1 | ⏳ A-2 héritage |
| PRE-3 | Décision PO scope sprint-024 (Phase 4 KPIs / Mago cleanup / OPS replan) | PO | Sprint-024 Planning P1 | ⏳ A-3 héritage |
| PRE-4 | Décision PO PRE-5 Render redeploy (6ᵉ sprint → action obligatoire) | PO + user | Sprint-024 J-2 | ❌ trigger atteint |
| PRE-5 | Stories sprint-024 spécifiées 3C + Gherkin (US-110..US-113 si Phase 4) | PO | Sprint-024 J0 fin | ⏳ |
| PRE-6 | Pré-allocation explicite cap libre 1-2 pts (vs slot reserved) | PO | Sprint-024 P1 | ⏳ héritage ST-1 |

---

## Backlog provisoire — pré-Sprint Planning P1

### Sub-epic A — EPIC-003 Phase 4 KPIs business (5-7 pts)

| ID | Titre | Pts (estim) | Notes |
|---|---|---:|---|
| US-110 | KPI DSO (Days Sales Outstanding) calcul + exposition dashboard EPIC-002 | 3 | Délai paiement moyen factures émises. ADR-0013 KPI #1. |
| US-111 | KPI temps de facturation (lead time devis signé → facture émise) | 3 | ADR-0013 KPI #2. |
| US-112 | KPI % projets adoption marge temps réel | 2 | ADR-0013 KPI #3 — adoption MVP. |

### Sub-epic B — Audit data + migration legacy (3 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-113 | Migration historique `WorkItem.cost` legacy → DDD aggregate | 3 | AUDIT-WORKITEM-DATA Phase 1 conclusions. Data quality > 95 %. |

### Sub-epic C — Décision PRE-5 Render redeploy (0-2 pts selon décision PO)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| OPS-PRE5-DECISION | ADR-0018 Render redeploy ou ADR Out Backlog (pattern ADR-0017) | 0-2 | 6ᵉ sprint consécutif holdover. Trigger réversibilité runbook §3. |

### Sub-epic D — Cap libre 1-2 pts pré-allouée explicite

Candidats (PO décision P1) :
- **Mago lint cleanup batch initial** (sprint-022 retro top-5 #3, sprint-023 retro A-6) — estimation 2 pts pour 100-150 errors removed
- **Audit VacationRepository Deptrac violation** (sprint-023 retro L-4)
- **TEST-COVERAGE-013 anticipation** (70 → 72 %)

---

## Definition of Done

- ✅ Tests Unit + Integration + Functional + Api passent (Docker)
- ✅ PHPStan max 0 erreur
- ✅ CS-Fixer + Rector + Deptrac OK (192 violations objectif réduction)
- ✅ Snyk Security clean
- ✅ Smoke test post-deploy green sur Render (**post décision PRE-4**)
- ✅ Documentation à jour (runbook + ADR si décision Render/PRE-5)
- ✅ PR review validée + merge linéaire main
- ✅ **0 commit `--no-verify`** sprint-024
- ✅ **0 holdover OPS sub-epic** sprint-024 (4ᵉ sprint consécutif cible)
- ✅ EPIC-003 Phase 4 démarrée (au moins 1 KPI US-110/111/112 livré)
- ✅ Engagement ferme respecté (12 pts max — 4ᵉ confirmation)
- ✅ Capacité libre pré-allouée à story concrète (vs slot reserved vide)

---

## 🔗 Cérémonies

| Cérémonie | Date prévue |
|---|---|
| **Atelier OPS-PREP-J0 J-2** | 2026-05-26 ~30 min (runbook §2) |
| Sprint Planning P1 (PO scope figé) | 2026-05-27 09:00 |
| Sprint Planning P2 (équipe technique tasks) | 2026-05-27 14:00 |
| Daily standup | Quotidien 09:30 |
| Sprint Review | 2026-06-10 14:00 |
| Rétrospective | 2026-06-10 16:30 |

---

## 🎯 Actions héritées sprint-023 retro

| ID | Action | Statut sprint-024 |
|---|---|---|
| A-1 | Atelier OPS-PREP-J0 J-2 sprint-024 | ⏳ PRE-1 |
| A-2 | Mesurer coverage actuel post sprint-023 | ⏳ PRE-2 |
| A-3 | Décision PO scope sprint-024 | ⏳ PRE-3 |
| A-4 | Décision PO PRE-5 Render redeploy | ⏳ PRE-4 OBLIGATOIRE |
| A-5 | Stories spécifiées 3C + Gherkin (US-110..US-113) | ⏳ PRE-5 |
| A-6 | Maintenir baseline 12 pts ferme (4ᵉ confirmation) | ⏳ acté kickoff |
| A-7 | Pré-allocation cap libre 1-2 pts explicite | ⏳ PRE-6 |
| A-8 | Audit ciblé VacationRepository Deptrac violation | ⏳ Sub-epic D candidate |

---

## ⚠️ Issues prod connues hors sprint

| Issue | Sprint affecté | Action sprint-024 |
|---|---|---|
| **Render image stale 2026-01-12** : `/health` raw octet-stream | **6ᵉ sprint consécutif** | **Décision PO obligatoire PRE-4 + Sub-epic C** |
| **Sub-epic B OPS** ADR-0017 Out Backlog | Replan dédié | Hors sprint-024. Re-évaluation sprint-025+ owner + 4 credentials |
| **Mago lint** 626 errors stable | Sprint-024 Sub-epic D candidate | Cleanup batch initial 100-150 errors |
| **Deptrac VacationRepository violation** | Sprint-024 Sub-epic D candidate | Audit + Domain pure migration |

---

## 🔗 Liens

- Sprint-023 review : `../sprint-023-epic-003-phase-3-finition/sprint-review.md`
- Sprint-023 retro : `../sprint-023-epic-003-phase-3-finition/sprint-retro.md`
- ADR-0013 — EPIC-003 scope
- ADR-0016 — Phase 3 décisions
- ADR-0017 — Sub-epic B Out Backlog
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- EPIC-003 file : `../../backlog/epics/EPIC-003-workitem-and-profitability.md`
