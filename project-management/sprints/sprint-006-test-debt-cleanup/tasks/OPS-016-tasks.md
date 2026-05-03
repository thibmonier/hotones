# Tâches — OPS-016

## Informations

- **Story Points** : 1
- **MoSCoW** : Should
- **Nature** : doc-only
- **Origine** : retro sprint-005 action #1
- **Total estimé** : 1h

## Résumé

Documenter les **coefficients vélocité par nature de story** dans `project-management/README.md`. Sprint-005 a montré qu'une moyenne unique sous-estime massivement la vélocité sur les sprints à dominante doc/refactor/test. Calibrer par catégorie permet un planning réaliste.

Catégories initiales (calibrées sprint-004 + sprint-005) :

| Nature | Coefficient | Note |
|---|---:|---|
| `doc-only` | ×1.5 | doc + tableau + xref + ADR |
| `refactor` | ×1.0 | conversion mécanique sur classes existantes |
| `test` | ×0.8 | écrire test demande analyse cas + fixture |
| `infra` | ×0.7 | hook + workflow + secrets gating |
| `feature-be` | ×0.5 | backend nouveau, Doctrine + tests |
| `feature-fe` | ×0.4 | UI Symfony UX/Twig + tests fonctionnels |

Capacité projetée = `capacité_brute × moyenne_pondérée(coefficients × pts_par_nature)`.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-OPS16-01 | [DOC] | Ajouter section "Capacity planning" dans `project-management/README.md` | 0.5h | - | 🔲 |
| T-OPS16-02 | [DOC] | Mettre à jour le sprint-006 sprint-goal.md avec le calcul appliqué (déjà ébauché J0) | 0.5h | T-OPS16-01 | 🔲 |

## Détail

### T-OPS16-01

Section :

```markdown
## Capacity planning par nature de story

Plutôt qu'utiliser une vélocité moyenne unique, le projet calibre la capacité
en pondérant chaque story par sa nature.

### Coefficients

[tableau ci-dessus]

### Calcul

1. Identifier le mix par nature dans le sprint planifié (% pts par catégorie).
2. Calculer la moyenne pondérée des coefficients.
3. Capacité projetée = capacité brute × moyenne pondérée.

### Exemple sprint-006

- Mix : 27% test, 36% refactor, 9% infra, 18% doc, 9% test, 0% feature.
- Moyenne pondérée ≈ 0.95.
- Capacité brute 32 pts → projetée ~30 pts. Engagé 22 pts (marge 8 pts).

### Recalibrage

Après chaque sprint review, ajuster les coefficients si la livraison réelle
diverge significativement (≥ 25%) du plan.
```

### T-OPS16-02

Confirmer le calcul appliqué en J0 dans `sprint-goal.md` du sprint-006 et lier vers la section README.

## DoD

- [ ] `project-management/README.md` contient la section.
- [ ] Sprint-006 sprint-goal.md référence la section README.
- [ ] PR ≤ 100 lignes diff.
