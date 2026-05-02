# Stacked Pull Requests

> Sprint-004 / OPS-007 — procédure et helper pour empiler des PRs

Quand un changement dépasse les 400 lignes diff (politique OPS-006) ou se
décompose naturellement en couches (ADR → infra → feature), on l'éclate
en plusieurs PRs où chacune cible la précédente plutôt que `main`.

Cette page fait référence depuis [`CONTRIBUTING.md`](../../CONTRIBUTING.md#pr-empilées-stacked-prs--procédure)
et documente le helper `bin/stacked-pr`.

## Le helper `bin/stacked-pr`

Le script automatise les opérations répétitives :

```text
bin/stacked-pr start <story>           Crée feat/<story>-base depuis main et le pousse
bin/stacked-pr add <story> <step>      Crée feat/<story>-<step> depuis la précédente
bin/stacked-pr rebase                  Rebase la branche courante sur sa base + push --force-with-lease
bin/stacked-pr pr <title> [body]       Ouvre une PR ciblant la branche parente
bin/stacked-pr chain                   Affiche la chaîne d'ancêtres jusqu'à main
```

La convention de nommage est :

```
feat/<story>-base       -> PR vers main
feat/<story>-step1      -> PR vers feat/<story>-base
feat/<story>-step2      -> PR vers feat/<story>-step1
...
```

La parenté est inférée du suffixe : `-step<N>` cible `-step<N-1>` (et
`-step1` cible `-base`). Le helper refuse d'opérer si l'arbre de travail
est sale (commit ou stash d'abord).

## Workflow type

### Démarrage

```bash
bin/stacked-pr start refacto-pricing
# Crée et pousse feat/refacto-pricing-base
# ... commits ...
bin/stacked-pr pr "feat(pricing): extract interface" "Voir #X (story sprint-005)."
```

### Ajout d'une étape

```bash
bin/stacked-pr add refacto-pricing 1
# Crée feat/refacto-pricing-step1 depuis feat/refacto-pricing-base
# ... commits ...
git push -u origin feat/refacto-pricing-step1
bin/stacked-pr pr "feat(pricing): migrate consumers"
```

### Après le merge de la base

Quand `feat/refacto-pricing-base` est mergée vers `main` :

```bash
git checkout feat/refacto-pricing-step1
bin/stacked-pr rebase
# Rebase sur origin/feat/refacto-pricing-base mis à jour, push --force-with-lease
gh pr edit $(gh pr view --json number -q .number) --base main
# La step1 cible désormais main directement
```

GitHub fait parfois ce changement de base automatiquement après un
merge ; vérifier avec `gh pr view` avant de mergrer.

### Voir la chaîne

```bash
git checkout feat/refacto-pricing-step3
bin/stacked-pr chain
# feat/refacto-pricing-step3
# feat/refacto-pricing-step2
# feat/refacto-pricing-step1
# feat/refacto-pricing-base
# main
```

## Erreurs fréquentes (référence rapide)

| Symptôme | Cause | Fix |
|---|---|---|
| PR step1 affiche les commits de la base **plus** ses commits propres | Pas rebasé après merge de la base | `bin/stacked-pr rebase` puis `gh pr edit --base main` |
| Conflits récurrents sur `composer.lock` à chaque rebase | `composer.lock` régénéré indépendamment dans chaque step | Régénérer **une seule fois** sur la base, propager via rebase |
| Reviewer dit « je ne vois que les commits de la base » | Mauvaise base GitHub | `gh pr edit <num> --base feat/<story>-step<N-1>` |
| `git push --force-with-lease` refusé | Branch protection sur la base | Demander à un mainteneur ou attendre le merge de la base |

## Quand NE PAS empiler

- Fix simple de moins de 100 lignes : PR unique vers `main`.
- Plusieurs PRs sans dépendance forte entre elles : merger en parallèle.
- Refactor exploratoire incertain : un seul WIP sur une branche, on
  découpera à la fin une fois la cible stabilisée.

## Référence historique

| Sprint | Stack |
|---|---|
| sprint-002 | #32 → #39 → #40 → #43 |
| sprint-003 | #50 → #54 ; #56 → #57 |
| sprint-003 (deps) | #66 → #67 |

Origine : retro sprint-003 action #1 (« stacked PR merge procedure »).
