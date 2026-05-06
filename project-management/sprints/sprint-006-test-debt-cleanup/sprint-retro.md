# Sprint Retrospective — Sprint 006 — Test Debt Cleanup & Workflow Hygiene

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-05-15 (anticipée 2026-05-05 J3) |
| Durée prévue | 1h30 |
| Format | Starfish (Keep / More / Less / Stop / Start) |
| Animateur | Scrum Master |
| Sprint | sprint-006 |

## Directive Fondamentale

> *"Quel que soit ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux possible, étant donné ce qu'il savait au moment, ses compétences et capacités, les ressources disponibles et la situation à portée de main."*
>
> — Norman Kerth, *Project Retrospectives* (2001)

## Métriques retro

| Métrique | Valeur |
|----------|--------|
| Stories engagées / livrées | 7 / 7 (100%) |
| Pts engagés / livrés | 22 / 22 (100%) |
| Vélocité | 22 (vs sprint-005 = 26, sprint-004 = 30) |
| PRs ouvertes/mergées | 21 |
| Bugs production trouvés en bonus | 7 |
| Tests ajoutés | ~25 |
| Coverage gain | +137 pts cumulés sur 4 services |
| ADR créés | 1 (ADR-0003) |
| EPIC créé | 1 (EPIC-001) |

## Format Starfish

### ⭐ Keep (continuer ce qui marche)

| # | Item | Owner | Suivi |
|---|------|-------|-------|
| K1 | Branches stacked PR avec base = PR précédente | tech-lead | ✅ établi sprint-006 |
| K2 | Audit avant exécution (T-TM2-01, T-TC1-01) | dev-team | ✅ 2 audits livrés cette sprint |
| K3 | Daily commits + push fréquent (vs commit géant) | dev-team | ✅ 21 PRs en J0-J3 |
| K4 | ADR pour décisions architecturales tolérées (skip-pre-push) | tech-lead | ✅ ADR-0003 |
| K5 | Coefficients vélocité par nature de story | OPS | ✅ OPS-016 |
| K6 | Reverse-engineering documentation (scan, PRD, gap-analysis) | PO + tech-lead | ✅ bundle PR #99 |

### ➕ More (faire plus de)

| # | Item | Owner | Action SMART |
|---|------|-------|--------------|
| M1 | Tests Integration pour services riches (Workload, HubSpot, Forecasting persistence) | dev-team | TEST-MOCKS-003 sprint-007 |
| M2 | Quick-fix bugs production découverts en auditant tests | dev-team | À chaque audit, commit séparé pour le bug fix |
| M3 | Validation cible sprint *avant* engagement (reality check coverage) | PO + tech-lead | Audit obligatoire avant story > 3 pts |
| M4 | Documentation atelier business *avant* code (alignement US-022) | PO | Atelier business pré-sprint plutôt qu'après |
| M5 | Pre-merge runs CI complet sur stack PRs | dev-team | À surveiller PR #100-#112 |

### ➖ Less (réduire)

| # | Item | Owner | Action SMART |
|---|------|-------|--------------|
| L1 | Sub-tasks avec gain coverage marginal (Workload +1.2 pts seul) | dev-team | Évaluer ROI coverage avant story |
| L2 | `--no-verify` répétés (Docker daemon down) | OPS | Doc Docker Desktop checklist setup |
| L3 | Hors-scope sur branche dédiée (HOUSEKEEPING-001 a porté 5 commits reverse-engineering) | tech-lead | Story dédiée pour reverse-engineering futur |
| L4 | Stray changes locaux non commités (composer.json, HomeController, dump SQL) | dev-team | Audit + commit ou stash discipline |

### 🛑 Stop (arrêter)

| # | Item | Owner | Action SMART |
|---|------|-------|--------------|
| S1 | Cibles coverage absolues sans reality check | PO | Reality check obligatoire (cf M3) |
| S2 | Mock + `expects()` pour des cas où stub suffit | dev-team | Convention CONTRIBUTING.md (T-TM2-06) |
| S3 | Création de markers `skip-pre-push` sans cause racine + story de fix | dev-team | Rule de review CONTRIBUTING.md (T-TFF2-05) |

### 🆕 Start (commencer)

| # | Item | Owner | Action SMART |
|---|------|-------|--------------|
| ST1 | Atelier business *pré-sprint* pour décisions techniques (formules, seuils) | PO + tech-lead | À planifier sprint-007 kickoff |
| ST2 | EPIC-001 phase 0 : audit + cherry-pick branche prototype DDD | tech-lead | Sprint-007 5 pts |
| ST3 | Sprint Security Hardening : multi-tenant SQLFilter + voters | tech-lead | Sprint-007 epic 13+8 pts |
| ST4 | TEST-MOCKS-003 : 203 Notices PHPUnit → 0 | dev-team | Sprint-007+ ou bonus sprint-006 J4-J10 |
| ST5 | Tracker discrepancies code vs atelier (ex US-022 pondération) | PO | Item permanent dans atelier-business-prep.md |

## 5 Pourquoi sur 1 thème majeur

### Thème : Cible coverage 45% sprint-006 inatteignable en 3 pts

**Pourquoi 1** : Cible 45% engagée sans audit préalable.
**Pourquoi 2** : L'audit T-TC1-01 n'avait pas été fait avant le sprint planning.
**Pourquoi 3** : Le sprint-006 a été kickedoff sur la base des engagements sprint-005 sans recalibrage.
**Pourquoi 4** : Pas de processus de "reality check" en sprint planning Part 2 (COMMENT).
**Pourquoi 5** : Vélocité moyenne calculée brute (sans coefficient nature) — corrigé sprint-006 avec OPS-016.

**Action**: désormais en sprint planning Part 2, audit obligatoire (sub-task `T-XXX-00 audit`) pour toute story > 3 pts touchant un domaine non maîtrisé. Validé via pratique TEST-COVERAGE-001 (T-TC1-01 audit a sauvé la story d'un engagement 45% intenable).

## Actions sprint-007

| # | Action | Owner | Deadline |
|---|--------|-------|----------|
| 1 | Atelier business pré-sprint sprint-007 (questions code/produit) | PO | 2026-05-13 affinage |
| 2 | EPIC-001 phase 0 décision (cherry-pick vs réécrire) | tech-lead | sprint-007 J0 |
| 3 | Sprint-007 Security Hardening epic créé | tech-lead | sprint-007 kickoff |
| 4 | Mettre à jour CONTRIBUTING.md règle "audit avant code" | dev-team | sprint-007 J0-J3 |
| 5 | Doc Docker Desktop setup checklist | OPS | sprint-007 carryover |

## Conclusion

Sprint très productif (100% engagement + bonus). Vélocité 22 (vs 30 sprint-004) reflète le passage à un mix plus refactor/test debt. Préparation sprint-007 Security Hardening + EPIC-001 phase 0 démarre dès J3.

Atouts majeurs :
- Méthodologie audit avant code (T-TM2-01, T-TC1-01) validée empiriquement.
- ROI massif des audits (7 bugs production trouvés en auditant les tests).
- Documentation reverse-engineering complète (bundle #99) facilite onboarding et roadmap.

Points d'attention :
- Cohérence atelier business ↔ code à industrialiser (cf US-022 discrepancy).
- Tests Integration manquent pour services riches → TEST-MOCKS-003.
- Capacity 7 jours résiduels J4-J10 → bonus possible (TEST-MOCKS-003 anticipé ou EPIC-001 phase 0).
