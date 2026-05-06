# Sprint 008 — DDD Phase 1 + Tech Debt Resolution

## Informations

| Attribut | Valeur |
|---|---|
| Numéro | 008 |
| Début | 2026-05-06 |
| Fin | TBD (mode agentic accéléré, viser 2-3 jours) |
| Capacité estimée | 22-26 pts (vélocité moyenne 22-32) |
| Total engagé | **22 pts** |
| Animateur | Claude Opus 4.7 (1M context) |

## Sprint Goal

> "Démarrer Phase 1 de l'EPIC-001 en cherry-picking 3 Bounded Contexts DDD additifs (Client + Project + Order), résorber la dette test résiduelle de sprint-007 (TEST-MOCKS-004 + investigation functional failures), et finaliser le PRD post-atelier business avec les migrations DB associées."

### Mesurabilité

- ✅ 3 BCs DDD additifs en place avec mappings + types Doctrine fonctionnels
- ✅ PHPUnit Notices ≤ 50 (vs 208 actuel) après TEST-MOCKS-004
- ✅ 13 functional failures pre-existing → soit fix, soit skip-pre-push avec ADR
- ✅ PRD reflète atelier business (FR-OPS-08, fusion FR-MKT-03+CRM-03, ROLE_COMMERCIAL)
- ✅ DB migrations atelier business générées + appliquées (Order.winProbability, CompanySettings.aiKeys*, AiUsageLog, FULLTEXT)

---

## Definition of Done (rappel projet)

- [ ] Code review (auto-review agentic minimum)
- [ ] Tests unitaires sur nouveau code (couverture ≥ 80% par fichier)
- [ ] Tests d'intégration passants
- [ ] PHPStan max sur sub-paths livrés (0 erreur réelle)
- [ ] Deptrac valide (Domain n'a aucune dépendance vers Infrastructure)
- [ ] Documentation mise à jour si décision architecturale
- [ ] Tags caveman commits + co-authored
- [ ] PR créée vers main avec body structuré

---

## Sprint Backlog

### Sub-epic A — DDD Phase 1 (9 pts)

Objectif: cherry-picker les 3 BCs additifs prioritaires de la branche prototype (audit DDD-PHASE0-001) sans toucher aux Entity flat existantes.

| ID | Titre | Pts | Source | Risque |
|---|---|---:|---|---|
| DDD-PHASE1-CLIENT | Cherry-pick BC Client (Entity + Events + Repo + 3 VOs) | 3 | Audit PR #121 | 🟢 Low (additif, gap-analysis a flagué stub vide) |
| DDD-PHASE1-PROJECT | Cherry-pick BC Project (Entity + Events + Repo + 3 VOs) | 3 | Audit PR #121 | 🟡 Medium (BC stub présent dans main, vide) |
| DDD-PHASE1-ORDER | Cherry-pick BC Order (3 Entities + Events + Repo + VOs) | 3 | Audit PR #121 | 🟡 Medium (modèle commercial DDD complexe) |

### Sub-epic B — Tech Debt Resolution (5 pts)

| ID | Titre | Pts | Origine |
|---|---|---:|---|
| TEST-MOCKS-004 | Strate 3 (3 files helpers) + cas createPartialMock | 2 | Solde TEST-MOCKS-003 |
| INVESTIGATE-FUNCTIONAL-FAILURES | 13 erreurs functional Vacation/multi-tenant pre-existing | 2 | Push hook S-007 |
| TOOLING-MOCK-SCRIPT | Helper `tools/count-mocks.pl` + doc CONTRIBUTING.md | 1 | Action A-1 + A-2 retro |

### Sub-epic C — PRD Update + DB Migrations (3 pts)

| ID | Titre | Pts | Origine |
|---|---|---:|---|
| PRD-UPDATE-001 | FR-OPS-08 + fusion FR-MKT-03+CRM-03 + ROLE_COMMERCIAL decision dans PRD | 1 | Atelier business S-007 |
| DB-MIG-ATELIER | Migrations: Order.winProbability + CompanySettings.aiKeys* + AiUsageLog + FULLTEXT | 2 | Atelier business S-007 |

### Sub-epic D — Buffer / Opportunistic (5 pts buffer non-engagé)

Items secondaires si capacité disponible:
- **DDD-PHASE1-INVOICE** (3 pts) — BC Invoice si tempo OK
- **TEST-COVERAGE-002** (2-3 pts) — push step coverage 25→30% suite escalator
- Investigation autre dette si pop-up

---

## Sprint Backlog résumé

| Sub-epic | Pts | Stories |
|---|---:|---:|
| A — DDD Phase 1 | 9 | 3 |
| B — Tech Debt | 5 | 3 |
| C — PRD/DB | 3 | 2 |
| **Total engagé** | **17** | **8** |
| Buffer | 5 | 2 |
| **Capacité totale viable** | **22** | **10** |

> Note: 17 pts engagement ferme + 5 pts buffer = 22 pts capacité totale. Plus conservateur que sprint-007 (32 pts) pour absorber risques techniques (cherry-picks DDD = première fois ce pattern, investigations functional failures = imprévisibles).

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| DDD-PHASE1-CLIENT | DDD-PHASE0-002 (Shared kernel) PR #122 | 🟡 OPEN — bloque Phase 1 si pas mergé |
| DDD-PHASE1-PROJECT | Idem | 🟡 OPEN |
| DDD-PHASE1-ORDER | Idem | 🟡 OPEN |
| INVESTIGATE-FUNCTIONAL-FAILURES | Aucune | 🟢 |
| DB-MIG-ATELIER | Doctrine schema:diff fonctionnel | 🟢 |

---

## Risques identifiés

| # | Risque | Probabilité | Impact | Mitigation |
|---|---|:-:|:-:|---|
| R-1 | Cherry-pick DDD entrane mass test failures (entity flat conflicts) | Moyenne | Haut | Faire fichier-par-fichier; rollback per file possible |
| R-2 | DBAL 4 incompatibilités cachées dans abstract types | Faible | Moyen | Tests unitaires post cherry-pick chaque type |
| R-3 | Functional failures pre-existing prennent > 2 pts | Moyenne | Moyen | Time-box 2 pts; sinon ADR skip-pre-push + différer |
| R-4 | DB migrations atelier conflit avec migrations existantes | Faible | Moyen | Lecture migrations/ avant générer |
| R-5 | PR #122 (DDD-PHASE0-002) pas mergée avant Phase 1 | Faible | Haut | Travailler en parallèle, base sur main |

---

## Cérémonies (mode agentic)

| Cérémonie | Format mode agentic |
|---|---|
| Sprint Planning | Cette doc + decompose-tasks |
| Daily Scrum | N/A (continuous flow) |
| Refinement | À l'occasion sur stories sub-epic D si activées |
| Sprint Review | `/workflow:review 008` à clôture |
| Rétrospective | `/workflow:retro 008` Starfish à clôture |

---

## Prochaines étapes

1. ✅ Sprint Goal documenté (cette doc)
2. → Créer 8 stories backlog (`/project:add-story`)
3. → `/project:decompose-tasks 008`
4. → `/project:run-sprint 008 --auto`

## Notes planning

- Vélocité observée S-005=22, S-006=19, S-007=32 (moyenne 24, médiane 22)
- Engagement 17 pts (sub-epic A+B+C) = en-dessous médiane = **conservateur**
- Buffer 5 pts (sub-epic D) = activable selon avancement
- Mode agentic permet réactivité face risques R-1 et R-3 (rollback rapide)
