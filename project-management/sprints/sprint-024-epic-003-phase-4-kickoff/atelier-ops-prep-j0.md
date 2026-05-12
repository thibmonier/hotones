# Atelier OPS-PREP-J0 — sprint-024

| Champ | Valeur |
|---|---|
| Date | 2026-05-11 (J-16 anticipation vs deadline 2026-05-26) |
| Owners | PO + Tech Lead |
| Sprint cible | sprint-024 (EPIC-003 Phase 4 KPIs business) |
| Runbook source | `docs/runbooks/sprint-ops-prep-j0.md` |
| Origine pré-requis | sprint-023 retro A-1 + A-4 héritage |

---

## 1. Objectifs atelier

1. Appliquer runbook §2 (screening Q1–Q6) sur stories sprint-024 candidates
2. Appliquer runbook §3 (matrix décision) sur OPS-PRE5-DECISION
3. Valider PRE-1..PRE-6 héritage retro sp-023
4. Produire ADR-0018 (décision PRE-4 Render redeploy)

---

## 2. Screening Q1–Q6 par story

### US-110 — KPI DSO (3 pts Must)

| Question | Réponse | Détail |
|---|---|---|
| Q1 credentials | ✅ Slack webhook prod | Déjà configuré sp-022 (US-103 / US-104 alertes marge) — réutilisable |
| Q2 console tierce | ✅ Render dashboard r/o | Pas de modif Render requise — KPI calculé in-app |
| Q3 données prod | 🟢 Non | Lecture invoices BDD applicative — pas d'export PII |
| Q4 config infra | 🟡 Invalidation cache | Symfony cache pool `cache.kpi` (similar US-107 margin snapshot) |
| Q5 coordination tierce | 🟢 Non | — |
| Q6 dépendances | ⚠️ US-107 margin snapshot persistence (sp-023 ✅ done) | Pré-requis livré |
| **Décision** | ✅ **Go sprint** | — |

### US-111 — KPI temps facturation (3 pts Must)

| Question | Réponse | Détail |
|---|---|---|
| Q1 credentials | ✅ Slack webhook prod | Réutilisé sp-022 |
| Q2 console tierce | ✅ Render r/o | — |
| Q3 données prod | 🟢 Non | Lecture quotes + invoices BDD |
| Q4 config infra | 🟡 Invalidation cache | idem US-110 |
| Q5 coordination tierce | 🟢 Non | — |
| Q6 dépendances | ⚠️ US-110 pattern KpiCalculator (anticipation T-110-01 PR #240 ✅) | OK |
| **Décision** | ✅ **Go sprint** | — |

### US-112 — KPI adoption marge (2 pts Should)

| Question | Réponse | Détail |
|---|---|---|
| Q1 credentials | ✅ Slack webhook prod | Réutilisé |
| Q2 console tierce | ✅ Render r/o | — |
| Q3 données prod | 🟢 Non | Lecture margin snapshots projects (US-107) |
| Q4 config infra | 🟡 Invalidation cache | idem |
| Q5 coordination tierce | 🟢 Non | — |
| Q6 dépendances | ⚠️ US-107 margin snapshot (sp-023 ✅), US-110/111 pattern | OK |
| **Décision** | ✅ **Go sprint** | — |

### US-113 — Migration WorkItem.cost legacy (3 pts Should)

| Question | Réponse | Détail |
|---|---|---|
| Q1 credentials | 🟢 Non | — |
| Q2 console tierce | 🟢 Non | — |
| Q3 données prod | 🔴 **OUI migration DDL** | Cols `migrated_at` + `legacy_cost_drift` + `legacy_cost_cents` ; idempotence up/down obligatoire (T-113-05) |
| Q4 config infra | 🔴 **OUI DDL prod** | Tech Lead approve + plan rollback documenté (T-113-06 runbook) |
| Q5 coordination tierce | 🟢 Non | — |
| Q6 dépendances | ⚠️ AUDIT-WORKITEM-DATA Phase 1 (sp-019 ✅), backup prod J-1 obligatoire | OK conditional |
| **Décision** | 🟡 **Go sprint + risk flagged daily** | Risk = drift > 5 % trigger abandon ADR-0013 cas 3 (Gherkin AC-3) |
| Mitigation | Backup prod J-1, dry-run T-113-07 user-tracked, runbook T-113-06 obligatoire avant prod | — |

### OPS-PRE5-DECISION — Render redeploy (0-2 pts Must)

| Question | Réponse | Détail |
|---|---|---|
| Q1 credentials | ✅ Render API key + dashboard | Tech Lead access confirmé (sp-022 atelier référence) |
| Q2 console tierce | ✅ Render dashboard | Tech Lead owner primaire |
| Q3 données prod | 🟢 Non | — |
| Q4 config infra | 🔴 **Redeploy Render** | Image stale 2026-01-12 (`/health` raw octet-stream) |
| Q5 coordination tierce | 🟢 Non | — |
| Q6 dépendances | 6ᵉ sprint consécutif holdover — trigger réversibilité ADR-0017 atteint | — |
| **Décision** | ✅ **Option A redeploy** | Cf ADR-0018 §Décision |

---

## 3. Matrix décision PRE-4 (runbook §3)

| Critère runbook §3 | État sprint-024 |
|---|---|
| ✅ Tous credentials confirmés J0 | Render API + dashboard Tech Lead OK |
| 🟡 1-2 manquants action immédiate possible | n/a |
| 🔴 Blocked owner / access review en cours | n/a (vs sp-022 = blocked → ADR-0017) |
| **Verdict** | ✅ **A go sprint** — Tech Lead disponible + access confirmé |

### Décision PO

> **Option A — Redeploy Render**. Décision actée user 2026-05-11.
> ADR-0018 rédigé. Trigger réversibilité ADR-0017 satisfait (PO + Tech Lead
> disponibles J0 + credentials confirmés simultanément).

### Trigger réversibilité ADR-0017 satisfait

ADR-0017 §Trigger réversibilité critère 2 :
> « PO ou Tech Lead disponible J0 confirme tous credentials simultanément
> (atelier OPS-PREP-J0 cycle suivant) »

→ Atelier sprint-024 J-16 anticipé constate :
- ✅ PO disponible (décision actée user)
- ✅ Tech Lead disponible (atelier conduit)
- ✅ Render API key confirmée
- ✅ Render dashboard access confirmé

**Réversibilité ADR-0017 → ADR-0018 ouvert.**

---

## 4. Validation PRE-1..PRE-6

| ID | Action | Statut atelier 2026-05-11 |
|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 J-2 | ✅ **Conduit J-16 anticipé** (deadline 2026-05-26 vacated) |
| PRE-2 | Mesurer coverage post sp-023 | ✅ **70 % vérifié CI report** (clover local stale 22.62 % hors source de vérité) |
| PRE-3 | Décision PO scope sprint-024 | ✅ **Scope = Phase 4 KPIs (US-110..US-112) + Migration (US-113) + OPS-PRE5** |
| PRE-4 | Décision PO Render redeploy | ✅ **Option A redeploy** — ADR-0018 |
| PRE-5 | Stories 3C + Gherkin specs | ✅ **Specs PR #237** (Gherkin US-110..US-113 ✅) — sync sprint-status.yaml requise |
| PRE-6 | Pré-allocation cap libre 1-2 pts | 🟡 **À décider** P1 — candidats : Mago lint batch 2 pts / VacationRepository audit 1 pt / TEST-COVERAGE-013 1 pt |

---

## 5. Backlog confirmé sprint-024

| Story | Pts | Pri | Décision | Tasks |
|---|---:|---|---|---|
| US-110 KPI DSO | 3 | Must | ✅ Go | 6 (1 ✅ done PR #240, 5 to do) |
| US-111 KPI temps facturation | 3 | Must | ✅ Go | 6 to do |
| US-112 KPI adoption marge | 2 | Should | ✅ Go | 5 to do |
| US-113 Migration WorkItem.cost legacy | 3 | Should | 🟡 Go + risk daily | 7 (1 user-tracked hors sprint) |
| OPS-PRE5-DECISION (Option A) | 1 | Must | ✅ Go | T-PRE5-01 ✅ (cet atelier), T-PRE5-02 ✅ ADR-0018, T-PRE5-03 redeploy à exécuter |
| **Total ferme** | **12 pts** | — | — | — |
| Capacité libre (PRE-6 PO) | 1-2 | — | TBD | — |

---

## 6. Décisions atelier — actions

| ID | Action | Owner | Sprint | Statut |
|---|---|---|---|---|
| AT-1 | Atelier conduit J-16 anticipé | Tech Lead | sprint-024 (atelier) | ✅ this doc |
| AT-2 | ADR-0018 rédigé | Tech Lead | sprint-024 (atelier) | ✅ `0018-render-redeploy-option-a.md` |
| AT-3 | Sync sprint-status.yaml PRE-1..PRE-6 statut | Tech Lead | sprint-024 (atelier) | ✅ committed |
| AT-4 | Sync sprint-status.yaml stories pending → ready-for-dev | Tech Lead | sprint-024 (atelier) | ✅ committed |
| AT-5 | Exécution redeploy Render dashboard | Tech Lead + user | sprint-024 J0 redeploy window | ✅ exécuté 2026-05-12 |
| AT-6 | Smoke test post-redeploy (`/health` raw → JSON valide) | Tech Lead | post-AT-5 | ✅ JSON valide 2026-05-12 07:36 UTC |
| AT-7 | Update OPS-PRE5-DECISION estimation 1 pt (vs 0-2 bracket) | Tech Lead | sprint-024 (atelier) | ✅ committed |
| AT-8 | PO décision PRE-6 cap libre 1-2 pts | PO | Sprint Planning P1 2026-05-27 | ⏳ |
| AT-9 | Sentry events + Render logs deploy success validation | Tech Lead | post-AT-5 | ✅ Render logs OK (rndr-id served, server: cloudflare) |

---

## 7. Conséquences sprint-024

### Capacité

- **Ferme : 12 pts** (US-110 3 + US-111 3 + US-112 2 + US-113 3 + OPS-PRE5 1)
- **Libre : 1-2 pts** (PRE-6 PO P1)
- **Vélocité cible : 12-14 pts** (cohérent avg 14 sprints = 11)

### Métriques OPS-PREP-J0 (runbook §6) — clôturées post-redeploy

| Métrique | Cible sp-021+ | sprint-024 |
|---|---|---|
| Stories OPS holdover / sprint | 0 | ✅ 0 (Option A redeploy 2026-05-12 validé) |
| % engagement honoré J1 ready | 100 % | ✅ 100 % |
| Holdover récurrent même story | 0 | ✅ 6 → 0 (Option A clôturée) |
| Atelier J0 OPS prep tenu | 1 / 1 | ✅ atelier J-16 anticipé |

### Validation redeploy 2026-05-12

```
GET https://hotones.onrender.com/health
HTTP/2 200
content-type: application/json
{"status":"healthy","timestamp":"2026-05-12T07:36:14+00:00","checks":{
  "database":{"status":"healthy","message":"Database connection successful"},
  "cache":{"status":"healthy","message":"Cache system ..."}}}
```

Pré-redeploy (image stale 2026-01-12) : `/health` raw octet-stream → résolu.
Post-redeploy : JSON valide, checks BDD + cache healthy.

### Sprint statut

- **kickoff_pending → ready** post sync yaml (AT-3, AT-4)
- Stories `pending → ready-for-dev`

---

## 8. Liens

- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- ADR-0017 sub-epic B out backlog (référence pattern) : `../../../docs/02-architecture/adr/0017-ops-sub-epic-b-out-backlog.md`
- ADR-0018 Render redeploy Option A : `../../../docs/02-architecture/adr/0018-render-redeploy-option-a.md`
- Sprint-024 goal : `sprint-goal.md`
- Sprint-023 retro A-1 + A-4 : `../sprint-023-epic-003-phase-3-finition/sprint-retro.md`
- EPIC-003 Phase 4 : `../../backlog/epics/EPIC-003-workitem-and-profitability.md`

---

**Auteur** : Tech Lead
**Date** : 2026-05-11
**Version** : 1.0.0
**Sprint** : 024 atelier OPS-PREP-J0
