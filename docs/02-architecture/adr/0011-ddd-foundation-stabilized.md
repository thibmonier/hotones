# ADR-0011 — Foundation DDD stabilisée : abandon du cherry-pick par PR

| Champ | Valeur |
|---|---|
| Statut | ✅ Accepté |
| Date | 2026-05-06 |
| Sprint | sprint-012-ddd-phase4-start-and-coverage |
| Story | FOUNDATION-STABILIZED (1 pt) |
| Auteur | Tech Lead |

---

## Contexte

Pendant les sprints 007 → 011, chaque PR DDD (Phase 1, Phase 2 ACL, Phase 3
controller) a dû dupliquer la même **foundation** (Shared kernel + Abstracts
+ ValueObjects partagés) parce que les PRs prédécesseurs étaient en review
parallèle, non encore mergées sur `main`.

**Pattern récurrent** : `git show <branch-précédente>:<file> | apply` →
8 cherry-picks par BC × 4 BCs = 32 duplications mécaniques.

Coût mesuré :
- ~15 min de cherry-pick par PR (overhead pure)
- Risque de drift entre branches sœurs
- Diff bruité (foundation re-appliquée à chaque PR)
- Pollution de la review (reviewers re-lisent du code déjà revu)

Identifié comme dette en rétrospective sprint-012 (Starfish — section "Less of").

---

## Décision

À partir de sprint-013, les PRs DDD partent toutes du même tronc `main`
**maintenant que** Phase 1 (4 BCs additifs) + Shared kernel sont mergés.

**Plus de branche commune intermédiaire nécessaire** — `main` lui-même fait
office de foundation stabilisée. Tout futur ajout au Shared kernel passe par
un commit isolé sur `main` (PR dédiée), pas par cherry-pick croisé.

### Critères de stabilité atteints sur `main`

| Composant | Sprint mergé | PR |
|---|---|---|
| `Shared/Trait/RecordsDomainEvents` | sprint-007 | #99 |
| `Shared/Interface/AggregateRootInterface` | sprint-007 | #99 |
| `Shared/Interface/DomainEventInterface` | sprint-007 | #99 |
| `Shared/ValueObject/Money` | sprint-007 | #99 |
| `Shared/ValueObject/Email` | sprint-007 | #99 |
| `Shared/ValueObject/TenantId` | sprint-008 | #117 |
| `Shared/Tenant/TenantAwareInterface` | sprint-008 | #117 |
| Domain Client (Entity + VOs + Events) | sprint-008 | #103 |
| Domain Project (Entity + VOs + Events) | sprint-008 | #112 |
| Domain Order (Entity + VOs + Events + Sections) | sprint-009 | #133 |
| Domain Invoice (Entity + VOs + Events) | sprint-011 | #155 |
| ACL Client / Project / Order | sprint-009..011 | #134, #146, #147 |

### Règle pour PRs futures

1. Une PR DDD = un branchement **direct depuis `main`** (jamais depuis branche sœur).
2. Si une nouvelle abstraction Shared est requise, **stop & extraire** dans une
   PR Foundation dédiée (même flux qu'un fix de bibliothèque interne).
3. Refus de review pour toute PR qui ré-introduit du code déjà sur `main`
   sous prétexte de "rebase difficile".

---

## Conséquences

### Positives

- Fin du cherry-pick mécanique → ~15 min/PR récupérées.
- Diff de PR resserré sur la valeur ajoutée fonctionnelle.
- Review focalisée (plus de code déjà-vu).
- Cohérence forte sur `main` (single source of truth Shared kernel).

### Négatives / À surveiller

- Si Shared kernel évolue souvent, les PRs DDD parallèles devront rebaser plus
  fréquemment. Mitigation : extraire chaque évolution Shared en PR séparée
  petite et vite mergée.
- Risque pour les BCs encore en buffer (Contributor, Vacation) qui devront
  partir de `main` à jour. Pas de réactivation du cherry-pick autorisée.

---

## Alternatives écartées

| Alternative | Raison du rejet |
|---|---|
| Branche `feat/ddd-foundation-stabilized` permanente | Doublon avec `main`. Crée confusion sur quoi rebaser. |
| Mono-PR mergeant toute la Phase 1 + Phase 2 d'un coup | Trop gros pour review. Vélocité bloquée 4+ sprints. |
| Sous-modules Git pour Shared kernel | Complexité disproportionnée pour un mono-repo. |

---

## Liens

- ADR-0008 : ACL Strangler Fig pattern
- ADR-0009 : Phase 3 controller migration pattern
- Rétrospective sprint-012 (Starfish — *Less of: cherry-pick mécanique*)
