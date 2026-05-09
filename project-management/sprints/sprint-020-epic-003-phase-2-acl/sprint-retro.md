# Sprint Retrospective — Sprint 020

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 020 — EPIC-003 Phase 2 ACL + OPS holdover |
| Date | 2026-05-09 (clôture anticipée — sprint J1) |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Atelier PO Phase 2 J1 tenu** (sprint-019 S-3 héritage) → ADR-0015 décisions task=NULL + doublons + invariant journalier livré AVANT US-098 implementation. Pas de blocage architecture en cours d'implémentation. |
| K-2 | **DDD Phase 2 ACL strangler fig pattern** appliqué (sprints 008-013 héritage) : translators flat↔DDD + Doctrine repo implémentent Repository interface Phase 1 sans casser legacy. Migration progressive possible. |
| K-3 | **Audit data systématique avant deploy** (sprint-019 retro M-1 héritage) : AUDIT-CONTRIBUTORS-CJM Risk Q3 mitigation script livré + plan correction admin AVANT DDD WorkItem prod. ROI préventif data integrity. |
| K-4 | **Coverage push 62 → 65 % via VOs partials** (Vacation + Company) — pattern continuité sprint-019 step 9 (Aggregate Roots majeurs). Atteint cible escalator step 10. |
| K-5 | **ADR-0015 avec invariant mesurable** (24h max travail journalier) — pattern sprint-019 ADR-0014 trigger réversibilité confirmé. Décision PO traçable + testable. |
| K-6 | **Recalibrage engagement ferme 10 pts atteint à 90 %** (9/10) — vélocité réaliste post-recalibrage sprint-019 (92 %). Holdover OPS structurel seul écart (1 pt). |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Sub-epic B OPS holdover 4ᵉ sprint consécutif** (017 → 018 → 019 → 020). Slack webhook + Sentry alerts + SMOKE config jamais débloqués. Pattern chronique. | Runbook OPS-PREP-J0 livré ce sprint (#A-8). Application **sprint-021 J0 obligatoire** : décision Out OR reallocation explicite (cf S-1). |
| L-2 | **TEST-COVERAGE-010 + OPS-PREP-J0 uncommitted en fin de sprint** (branche test + runbook standalone). Risk : oubli merge + rétro non synchro main. | Sprint-021 J0 : merger les 2 livrables AVANT kickoff (cf A-1). |
| L-3 | **Capacité libre 2 pts non consommée** : ni EPIC-003 Phase 3 démarrage anticipé ni BUFFER OrderTest extensions. Holdover OPS B aurait pu être réallouée. | Sprint-021 : capacité libre 2 pts pré-allouée Phase 3 démarrage (UC `RecordWorkItem` skeleton). |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Application stricte runbook OPS-PREP-J0 dès sprint-021 J0** : atelier 30 min J-2 screening backlog candidat → tickets OPS-tagged + access confirmed avant Sprint Planning P1. Si manquant → Out sprint. | Élimine holdover OPS B chronique (4 sprints). Métrique cible : 0 holdover OPS sprint-021+. |
| S-2 | **Décision PO Phase 3 EPIC-003 sprint-020 retro → sprint-021 kickoff** : UC `RecordWorkItem` skeleton + UI Twig saisie hebdo OU API JSON only OU les 2 ? | Évite ambiguïté Phase 3 scope sprint-021 J1 (cf A-3). |
| S-3 | **Pré-merge livrables fin sprint** : avant clôture sprint, vérifier 100 % livrables mergés main (pas de branches test orphelines, runbooks uncommitted). | Évite gap rétro vs main observable sprint-020. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Holdover OPS B sans propriétaire J0 fixé** : 4 sprints consécutifs avec « Tech Lead » owner mais pas d'access confirmation J0 → tickets meurent. | Runbook OPS-PREP-J0 §7 owners primaire+backup mappés. Sprint-021 : si Tech Lead absent J0 → backup PO active OU story Out. |
| ST-2 | **Capacité libre 2 pts allouée vague** (« EPIC-003 Phase 3 démarrage anticipé OR BUFFER »). Sprint-020 : 0 pt consommé. | Sprint-021 : capacité libre = pré-allocation explicite story candidate (pas « ou X ou Y »). |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Runbooks ops systématiques pour patterns récurrents** (sprint-020 OPS-PREP-J0 livré). Candidates sprint-021+ : runbook DDD strangler fig migration, runbook ADR avec trigger réversibilité, runbook coverage escalator step. | Capitalisation savoir-faire vs réinvention sprint après sprint. |
| M-2 | **Atelier PO décisions J1 systématique** (sprint-020 ATELIER-PHASE-2 succès) avant chaque story PO-dependant. Pattern à appliquer EPIC-003 Phase 3 (UC RecordWorkItem UX décisions). | Évite blocage implémentation milieu sprint pour clarification PO. |

---

## 🎯 Actions concrètes Sprint 021

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Pré-merge livrables sprint-020 : commit + PR runbook OPS-PREP-J0 + PR coverage-010 (branche test) | Tech Lead | Sprint-020 J1 fin |
| A-2 | Atelier OPS-PREP-J0 J-2 sprint-021 : screening backlog candidat, tagging OPS, access confirmation | PO + Tech Lead | Sprint-021 J-2 |
| A-3 | Atelier PO Phase 3 EPIC-003 décisions UC `RecordWorkItem` (UI Twig + API ? scope MVP ?) | PO + Tech Lead | Sprint-021 J0 |
| A-4 | Décision finale Sub-epic B OPS holdover : owner unique fixé OR Out backlog OR réallocation PO | PO + Tech Lead | Sprint-021 J0 atelier OPS-PREP |
| A-5 | Capacité libre sprint-021 = pré-allocation explicite (pas « OR ») | PO | Sprint-021 Planning P1 |
| A-6 | Métrique « 0 holdover OPS » suivie sprint-021 retro | Tech Lead | Sprint-021 retro |

---

## 📊 Directive Fondamentale

> « Indépendamment de ce que nous découvrons aujourd'hui, nous comprenons et
> croyons sincèrement que chacun a fait du mieux qu'il pouvait, étant donné
> ce qui était connu à ce moment-là, ses compétences et capacités, les
> ressources disponibles et la situation rencontrée. »

---

## 🚀 Sprint-020 takeaway

**Pattern recalibrage vélocité confirmé** : 10 pts engagement ferme = 9 pts
livrés (90 %). Aligné sprint-019 (92 %) — recalibrage post 017-018
explosifs (130 %/124 %) tient.

**EPIC-003 progresse** : Phase 1 (sprint-019) + Phase 2 ACL (sprint-020)
livrées. Phase 3 sprint-021 = UC RecordWorkItem (saisie hebdo). Roadmap
visible.

**Sub-epic B OPS débloqué structurellement** : runbook OPS-PREP-J0 livré
A-8 résout cause root holdover 4 sprints. Application sprint-021
critique — métrique cible « 0 holdover OPS » sera l'indicateur succès
runbook.

**Indicateur santé équipe** : audit data systématique + ADR avec
invariants mesurables = pattern qualité décisionnelle stable.
Coverage 65 % atteint cible step 10. Documentation patterns en
runbooks = capitalisation savoir-faire.

**Risk visible** : 2 livrables uncommitted fin sprint-020
(coverage-010 + runbook). Pré-merge sprint-021 J0 obligatoire (cf A-1
+ S-3).

---

## 🔗 Liens

- Sprint-020 review : `sprint-review.md`
- Sprint-019 retro : `../sprint-019-epic-003-scoping/sprint-retro.md`
- Sprint-021 kickoff : `../sprint-021-*/sprint-goal.md` (à créer)
- ADR-0015 — EPIC-003 Phase 2 décisions PO
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- Audit Contributors sans CJM : `../../../docs/02-architecture/epic-003-audit-existing-data.md`
