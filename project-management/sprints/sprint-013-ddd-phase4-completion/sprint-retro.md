# Sprint Retrospective — Sprint 013

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 013 — DDD Phase 4 Completion + Coverage Step 3 |
| Date | 2026-05-07 |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Décommissions parallèles dans 1 PR groupée** (PR #165 = Project + Order + Invoice 9 pts). Pattern symmetric clair, review overhead divisé par 3 vs PRs séparées. |
| K-2 | **Tests Application Use Case avec mocks EM** (CreateClient/Project/OrderQuote). ROI imbattable : pas d'infra, run < 100 ms, +28 tests pour 2 pts. |
| K-3 | **Réactivité fix infra CI imprévu** (OPS-008 PR #166). Bug bloquant détecté + diagnostiqué + fixé + mergé en 30 min sans dérailler le sprint. |
| K-4 | **Pattern hybrid Phase 4 pour Invoice** : UC pour skeleton + side-effects legacy post-UC. Évite refactor lourd du form rich (issuedAt/dueDate/amountHt/tvaRate). |
| K-5 | **ADR systématique sur process** : ADR-0011 foundation-stabilized appliqué dès sprint-013 (PRs DDD direct depuis main, plus de cherry-pick). Tenu. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Bug latent OrderFlatToDddTranslator** non détecté avant tests (protected createdAt). Probablement jamais exercé en prod. | Sprint-014 : story ORDER-TRANSLATOR-FLAT-TO-DDD-FIX (1 pt). Inclure « write E2E test for every translator » dans Definition of Done sprints DDD. |
| L-2 | **PR #163 stale checks** : merge déclenché alors que branch 5 commits derrière main → CI rouge. Découvert visuellement par PO. | Convention : avant merge, rebase systématique sur main pour tout PR > 1h ouvert. À documenter dans CONTRIBUTING.md. |
| L-3 | **Buffer Vacation/Contributor ACL non activé** sprints-011 + 012 + 013. | Sprint-014 : promotion en commitment ferme (8 pts), plus de status « buffer ». |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Smoke production post-merge automatique** sur fixtures via GitHub Action (création + listing pour chaque BC après chaque merge `main`). | Critère 3 ADR-0009 (« smoke production fixtures ») actuellement manuel. Automatisation = filet de sécurité Phase 4 décommissions futures. |
| S-2 | **Coverage step 4 (40 %) ciblage Repositories ACL** (DoctrineDddClientRepository + Project + Order + Invoice). Mock EM via PHPUnit + assertions sur queries DQL. | 4 repositories = 4 pts si pattern reproductible. Boost coverage critique (chemin tenant filter + reconstitute). |
| S-3 | **EPIC-002 kickoff** : définition avec PO. Maintenant qu'EPIC-001 est fini, prochaine initiative à scoper. | Évite « stagnation » entre 2 epics. Sprint-014 doit avoir sprint goal lié à EPIC-002 même partiellement. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Considérer le buffer comme variable d'ajustement permanente**. | 3 sprints consécutifs sans activation = ce n'est plus du buffer, c'est du backlog dette. Sprint-014 promu commitment ferme. |
| ST-2 | **Cherry-pick foundation entre PRs DDD**. | EPIC-001 fini, plus de PRs DDD parallèles. ADR-0011 obsolète sauf reprise EPIC-002 avec pattern similaire. |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **PR groupée pour stories homogènes** (ex 3 décommissions Phase 4 = 1 PR). | Review unique vs 3, déploiement atomique, rollback simple. |
| M-2 | **Documentation découvertes hors-scope dans PR description** (cf PR #164 mentionnant bug OrderFlatToDddTranslator). Crée backlog item directement. | Pas de perte de signal, traçabilité dans git history. |

---

## 🎯 Actions concrètes Sprint 014

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Sprint-014 commitment ferme : Contributor ACL + Vacation ACL + bug fix translator (9 pts) + escalator step 4 (2 pts) = 11 pts | Tech Lead | Sprint-014 J1 |
| A-2 | Documenter convention rebase-before-merge dans CONTRIBUTING.md | Dev | Sprint-014 J3 |
| A-3 | Story SMOKE-PROD-FIXTURES-ON-MERGE (S-1) à scoper avec PO sprint-015 | PO + Tech Lead | Sprint-015 affinage |
| A-4 | EPIC-002 kickoff atelier scope : 1 h avec PO | PO + Tech Lead | Sprint-014 J5 |

---

## 📈 Trends 6 sprints

| Sprint | Engagé | Livré | Phase EPIC-001 | Coverage step |
|---|---:|---:|---|---:|
| 008 | 26 | 26 | Phase 1 (Client + Project) | 25 % |
| 009 | 22 | 22 | Phase 1 (Order) + Phase 2 ACL Client | 25 % |
| 010 | 18 | 18 | Phase 2 ACL Project | 25 % |
| 011 | 14 | 14 | Phase 2 ACL Order + Phase 3 Client/Project/Order | 25 % |
| 012 | 15 | 15 | Phase 4 (Client) + Phase 2/3 Invoice | en route 30 % |
| **013** | **11** | **11** | **Phase 4 complète (3 décommissions)** | **35 %** |

EPIC-001 = **fini en 6 sprints d'engagement DDD** (sprint-008 → sprint-013).

---

## Directive Fondamentale Norm Kerth

> « Quel que soit ce que nous avons découvert, nous comprenons et croyons
> sincèrement que chacun a fait du mieux qu'il pouvait, étant donné ce qu'il
> savait à ce moment-là, ses compétences et capacités, les ressources
> disponibles, et la situation. »

---

## Conclusion

Sprint-013 = **100 % livré, EPIC-001 strangler fig COMPLET, 0 régression
utilisateur, escalator coverage step 3/5 atteint**.

Étape historique pour le projet : la migration legacy → DDD est terminée
côté code. Reste maintenant la consolidation tech-debt (translators bug,
buffer ACL) et le démarrage d'EPIC-002 à scoper avec le PO.

Sprint-014 vise **buffer activé en commitment ferme** + tech debt + EPIC-002
kickoff.
