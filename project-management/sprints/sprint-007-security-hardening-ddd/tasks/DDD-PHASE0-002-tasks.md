# DDD-PHASE0-002 — Tasks

> Cherry-pick contrôlé Shared kernel (Email VO, Money VO, AggregateRootInterface, DomainEventInterface).
> 3 pts / 4 tasks / ~5-6h.

| ID | Type | Description | Estimate | Status |
|----|------|-------------|---------:|--------|
| T-DP02-01 | [BE] | Cherry-pick `src/Domain/Shared/Interface/{AggregateRootInterface, DomainEventInterface}.php` + `src/Domain/Shared/Trait/RecordsDomainEvents.php` + `src/Domain/Shared/Exception/DomainException.php` | 1h | 🔲 |
| T-DP02-02 | [BE] | Cherry-pick + adapter `src/Domain/Shared/ValueObject/Email.php` + `Money.php` | 1.5h | 🔲 |
| T-DP02-03 | [TEST] | Tests unitaires Email VO (validation format, equals) + Money VO (add/subtract/multiply, currency mismatch exception) | 2h | 🔲 |
| T-DP02-04 | [BE] | Cherry-pick `src/Infrastructure/Persistence/Doctrine/Type/AbstractStringType.php` + `AbstractEnumType.php` + `AbstractUuidType.php` + `EmailType.php` (custom Doctrine types) | 1h | 🔲 |

## Acceptance Criteria

- [ ] Worktree isolé pour le cherry-pick (`git worktree add ../hotones-ddd feature/...`).
- [ ] 4 fichiers Shared kernel cherry-picked sur main.
- [ ] 2 VOs (Email + Money) avec validation + tests > 90% coverage.
- [ ] 4 abstract Doctrine custom types cherry-picked + 1 concret (EmailType).
- [ ] PHPStan max OK sur tous les nouveaux fichiers.
- [ ] Deptrac vert (Domain ne dépend de rien, Infrastructure dépend de Domain).
- [ ] Tests unitaires Domain tournent en < 1s (pas de Symfony kernel).
- [ ] PR review : reviewer confirme conformité `.claude/rules/02-architecture-clean-ddd.md` + `04-value-objects.md`.

## Notes

- Cherry-pick **uniquement** les fichiers identifiés `KEEP` par DDD-PHASE0-001.
- Ne pas cherry-pick les entités BC-spécifiques (User, Order, etc.) — réécrire au cas par cas en sprints 008+.
- Le cherry-pick peut produire des conflits sur les imports — résoudre au cas par cas.

## Risques

| Risque | Mitigation |
|--------|------------|
| Conflits cherry-pick (main a divergé de 2218 fichiers) | Worktree isolé + bench 3 jours max ; abandon possible si trop coûteux |
| `AbstractEnumType` incompatible avec PHPUnit/PHPStan version actuelle | Adapter signatures (PHP 8.5 + Symfony 8.0 + Doctrine 3.6) |
| Tests Shared kernel ne tournent pas (dépendance cachée) | Bootstrap test minimaliste sans Symfony kernel pour ces tests |
