# DDD-PHASE0-001 — Tasks

> Audit branche `feature/sprint-001-clean-architecture-structure` (33 746 lignes scaffolding).
> 2 pts / 4 tasks / ~3-4h.

| ID | Type | Description | Estimate | Status |
|----|------|-------------|---------:|--------|
| T-DP01-01 | [DOC] | Lister fichiers branche prototype par BC (9 BCs : User, Company, Client, BusinessUnit, Contributor, Order, Invoice, Timesheet, Project + Shared) | 0.5h | 🔲 |
| T-DP01-02 | [DOC] | Pour chaque fichier : décider keep / rewrite / discard (basé sur évolution main 6 mois plus tard) | 1.5h | 🔲 |
| T-DP01-03 | [DOC] | Identifier conflits attendus (entités legacy actuelles vs DDD scaffolding) | 1h | 🔲 |
| T-DP01-04 | [DOC] | Rapport markdown : `project-management/analysis/epic-001-phase-0-audit.md` avec recommandation par fichier | 0.5-1h | 🔲 |

## Acceptance Criteria

- [ ] Liste exhaustive des 160 fichiers branche prototype.
- [ ] Décision keep/rewrite/discard documentée pour chaque fichier (sortie tableau markdown).
- [ ] Liste de fichiers prêts au cherry-pick sans conflit (utiles pour DDD-PHASE0-002).
- [ ] Estimation effort par BC (cherry-pick vs réécrire) pour planning sprints 008+.
- [ ] Rapport mergeable en main (PR doc-only).

## Sortie attendue

```
project-management/analysis/epic-001-phase-0-audit.md

| Fichier | BC | Status | Recommandation | Notes |
|---------|----|----|----------------|-------|
| src/Domain/Shared/ValueObject/Email.php | Shared | KEEP | Cherry-pick direct | aucun conflit |
| src/Domain/Shared/ValueObject/Money.php | Shared | KEEP | Cherry-pick direct | aucun conflit |
| src/Domain/User/Entity/User.php | User | REWRITE | Conflit avec src/Entity/User.php (main) | besoin migration mapping XML |
| src/Domain/Order/Entity/Order.php | Order | DISCARD | Order entity main a divergé (40+ champs ajoutés en 6 mois) | réécrire from scratch sprint-009 |
| ...
```

## Suite

Cherry-pick effectif → DDD-PHASE0-002 (3 pts).
