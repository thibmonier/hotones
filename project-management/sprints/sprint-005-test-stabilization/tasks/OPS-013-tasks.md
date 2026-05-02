# Tâches — OPS-013

## Informations

- **Story Points** : 1
- **MoSCoW** : Could
- **Origine** : retro sprint-004 action #4
- **Total estimé** : 2h

## Résumé

Sprint-004 a fini avec 10 PRs ouvertes simultanément, ingérables côté review humaine. Documenter une politique informelle "max 4 PRs ouvertes par dev en parallèle, au-delà = draft".

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-OPS13-01 | [DOC] | Section "PRs ouvertes simultanées" dans `CONTRIBUTING.md` | 2h | - | 🔲 |

## Détail

### T-OPS13-01

Ajouter dans `CONTRIBUTING.md` :

```markdown
## PRs ouvertes simultanées

Pour garder la file de review viable, **chaque développeur ne doit pas
avoir plus de 4 PRs en review active simultanément**.

Au-delà :
- Mettre les nouvelles PRs en **draft** (`gh pr ready --undo` ou created with `--draft`)
- Sortir du draft uniquement quand une des PRs en review est mergée

### Pourquoi 4

- 4 = nombre raisonnable pour un reviewer humain à garder en tête
- Sprint-004 a tenté 10 PRs en parallèle → file ingérable, certaines PRs ont attendu des jours

### Exception

Stack PR (cf [stacked-prs.md](docs/04-development/stacked-prs.md)) :
les PRs d'une même chaîne comptent pour **1 seule** dans le quota.

### Vérification rapide

`gh pr list --author=@me --state=open --json number,title,isDraft`
```

## DoD

- [ ] Section ajoutée à `CONTRIBUTING.md`
- [ ] Mention dans la section "Processus de contribution"
