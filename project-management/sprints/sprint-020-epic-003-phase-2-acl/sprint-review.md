# Sprint Review — Sprint 020

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 020 — EPIC-003 Phase 2 ACL + OPS holdover |
| Date | 2026-05-09 (clôture anticipée — sprint J1) |
| Sprint Goal | EPIC-003 Phase 2 ACL (translators + Doctrine repo) + OPS holdover sprint-019 (Slack + Sentry + SMOKE) + coverage 62 → 65 % via VOs partials |
| Capacité | 12 pts |
| Engagement ferme | 10 pts |
| Capacité libre | 2 pts (non consommés) |
| Livré | **9 pts (90 % engagement ferme)** + 1 pt holdover OPS Sub-epic B |

---

## 🎯 Sprint Goal — Atteint partiellement ✅

**Goal** : « EPIC-003 Phase 2 ACL : translators flat↔DDD +
DoctrineDddWorkItemRepository pour mettre WorkItem en production
lecture/écriture. Finir OPS holdover sprint-019 (Slack + Sentry alerts
+ SMOKE config). Pousser escalator coverage 62 → 65 % via ValueObjects
partials. »

**Résultat** :
- ✅ EPIC-003 Phase 2 ACL livrée : `WorkItemFlatToDddTranslator` +
  `WorkItemDddToFlatTranslator` + `DoctrineDddWorkItemRepository`
- ✅ Atelier PO Phase 2 J1 tenu → ADR-0015 décisions task=NULL +
  doublons + invariant journalier
- ✅ Audit Contributors sans CJM (Risk Q3 héritage sprint-019) — script
  + sortie + plan correction admin avant déploiement
- ✅ Coverage push 62 → 65 % via ValueObjects partials (Vacation +
  Company)
- ✅ Pattern OPS prep J0 documenté (livrable A-8 sprint-019 retro)
- ⏳ **Sub-epic B OPS reporté sprint-021** : Slack webhook + Sentry
  alerts + SMOKE user creds nécessitent credentials côté user (1 pt
  holdover récurrent 4ᵉ sprint consécutif — voir retro)

---

## 📦 User Stories Livrées

| Story | Pts | PR | Statut |
|---|---:|---|---|
| AUDIT-CONTRIBUTORS-CJM (sub-epic D) | 1 | #205 | ✅ mergée |
| ATELIER-PHASE-2 + ADR-0015 (sub-epic A) | 1 | #206 | ✅ mergée |
| US-098 ACL translators + Doctrine repo (sub-epic A) | 4 | #207 | ✅ mergée |
| TEST-COVERAGE-010 ValueObjects partials (sub-epic C) | 2 | (test branch faa6b134) | ⏳ PR à ouvrir |
| OPS-PREP-J0 runbook (sub-epic D) | 1 | uncommitted | ⏳ commit + PR à ouvrir |
| **Total** | **9** | | **9/10 ferme = 90 %** |

### Reporté sprint-021

| Story | Pts | Raison |
|---|---:|---|
| US-094-OPS Slack webhook + Sentry alerts (sub-epic B) | 0.5 | OPS manuel — credentials user (Slack workspace + Sentry org admin) |
| SMOKE-OPS user smoke + GH secrets/var (sub-epic B) | 0.5 | OPS manuel — credentials user (DBA prod + GH repo Settings) |

**Holdover récurrent 4ᵉ sprint consécutif** (017 → 018 → 019 → 020).
Pattern OPS-PREP-J0 instauré ce sprint (runbook livré) doit éliminer
holdover sprint-021+.

---

## 📈 Métriques

| Métrique | Valeur | Tendance |
|---|---|---|
| Points engagés ferme | 10 | recalibrage continu |
| Points livrés | 9 | -2 vs sprint-019 (11) |
| Vélocité | 9 | aligné capacité 10 pts |
| Taux complétion ferme | 90 % | -2 pts vs sprint-019 (92 %) |
| Bugs découverts | 0 | stable |
| Bugs corrigés | 0 | stable |
| Coverage | 65 % | +3 pts (62 → 65 %) |
| ADR publiés | 1 (ADR-0015) | +1 |
| Runbooks publiés | 1 (sprint-ops-prep-j0) | +1 |

### Vélocité historique 11 sprints

| Sprint | Engagement | Livré | Taux |
|---|---:|---:|---:|
| 010 | 8 | 8 | 100 % |
| 011 | 8 | 8 | 100 % |
| 012 | 9 | 9 | 100 % |
| 013 | 9 | 9 | 100 % |
| 014 | 12 | 12 | 100 % |
| 015 | 13 | 13 | 100 % |
| 016 | 11 | 11 | 100 % |
| 017 | 10 | 13 | 130 % |
| 018 | 8.5 | 10.5 | 124 % |
| 019 | 12 | 11 | 92 % |
| **020** | **10** | **9** | **90 %** |

Vélocité moyenne 11 sprints : ~10.4 pts. Sprint-020 = 9 pts livrés
(holdover OPS B 1 pt → sprint-021). Pattern recalibrage post 017-018
explosifs (130 %/124 %) confirmé : engagement réaliste, holdover OPS
seul écart structurel à éliminer via runbook OPS-PREP-J0 livré.

---

## 🎯 Démonstration

### EPIC-003 Phase 2 ACL livré
1. ADR-0015 décisions PO Phase 2 (task=NULL exclus marge / doublons
   dédup transparente / invariant journalier 24h max)
2. `WorkItemFlatToDddTranslator` + `WorkItemDddToFlatTranslator`
3. `DoctrineDddWorkItemRepository` impl Repository interface livrée
   Phase 1 (sprint-019)
4. Tests Integration Docker DB minimum (architecture hexagonale validée)

### Audit Contributors sans CJM
- Script SQL audit prod livré (Risk Q3 héritage sprint-019)
- Output liste contributors flagged
- Plan correction admin avant tout deploy DDD

### Coverage 65 %
- VOs partials Domain : Vacation + Company
- Pattern continuité sprint-019 step 9 (Aggregate Roots majeurs)

### OPS-PREP-J0 runbook
- 6 questions screening credentials/secrets/admin externe
- Décision J0 explicite (go/risk/out)
- Owners + escalation mappés
- Métriques succès (cible : 0 holdover OPS sprint-021+)

---

## 💬 Feedback PO (à recueillir)

Questions atelier sprint-021 J1 :
1. Sub-epic B OPS holdover récurrent (4 sprints) : décision finale
   Slack webhook + Sentry alerts + SMOKE config — owner unique J0
   fixé OU réallocation PO ?
2. EPIC-003 Phase 3 démarrage sprint-021 (UC `RecordWorkItem` skeleton
   + UI Twig saisie hebdo) — capacité ?
3. Coverage step 11 sprint-021 cible 65 → 68 % via Domain Notification
   + Settings BCs OU Notification only ?
4. Application OPS-PREP-J0 runbook sprint-021 J0 — atelier prep tenu
   J-2 sprint-021 ?

---

## 🔗 Liens

- PR #205 — AUDIT-CONTRIBUTORS-CJM
- PR #206 — ADR-0015 EPIC-003 Phase 2 décisions
- PR #207 — US-098 EPIC-003 Phase 2 ACL
- Branche `test/coverage-010-vacation-value-objects` (faa6b134) — PR à ouvrir
- Runbook `docs/runbooks/sprint-ops-prep-j0.md` — uncommitted
- ADR-0015 — EPIC-003 Phase 2 décisions PO
- Sprint-019 retro : `../sprint-019-epic-003-scoping/sprint-retro.md`
