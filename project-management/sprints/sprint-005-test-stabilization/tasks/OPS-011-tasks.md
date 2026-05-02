# Tâches — OPS-011

## Informations

- **Story Points** : 3
- **MoSCoW** : Must
- **Origine** : retro sprint-004 action #3
- **Total estimé** : 7h

## Résumé

`git push --no-verify` utilisé 9 fois sprint-004 à cause de 47 failures + 27 errors **pré-existants** sur la suite full. OPS-011 traite cette baseline soit en fixant, soit en isolant via un mécanisme `@group skip-pre-push`.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-OPS11-01 | [OPS] | Inventaire des 74 failures (catégorisation : trivial / brittle / sandbox-only) | 2h | - | 🔲 |
| T-OPS11-02 | [TEST] | Fixer les failures triviales (≤30 min chacune) | 3h | T-OPS11-01 | 🔲 |
| T-OPS11-03 | [OPS] | Marquer brittle avec `#[Group('skip-pre-push')]` + ajuster `.githooks/pre-push` (`--exclude-group=skip-pre-push`) | 1h | T-OPS11-02 | 🔲 |
| T-OPS11-04 | [DOC] | Section "Pre-push baseline" dans `CONTRIBUTING.md` (comment ajouter / retirer) | 1h | T-OPS11-03 | 🔲 |

## Détail des tâches

### T-OPS11-01 — Inventaire

Lancer `docker compose exec -T app php vendor/bin/phpunit 2>&1 | tee /tmp/full.log`, extraire la liste des failures + errors. Pour chaque, classer :

| Catégorie | Critère | Action |
|---|---|---|
| **Trivial** | < 30 min de fix (typo, deprecation, mock signature) | T-OPS11-02 |
| **Brittle** | Dépend d'un état externe ou d'une config flaky | T-OPS11-03 (group skip) |
| **Sandbox-only** | Nécessite une API externe | T-OPS11-03 (group sandbox, déjà couvert par TEST-CONNECTORS-CONTRACT-001) |
| **Bug réel** | Régression à fixer | Story dédiée sprint-006 |

Livrable : `/tmp/inventory-OPS-011.csv` (test, fichier, catégorie, action).

### T-OPS11-02 — Fixer les triviales

Estimation : ~12 fixes triviaux à 15 min = 3h. Si le nombre dépasse, prioriser par risque (bug réel d'abord).

### T-OPS11-03 — Group skip-pre-push

Ajouter l'attribut PHPUnit `#[Group('skip-pre-push')]` sur les classes brittle. Mettre à jour `.githooks/pre-push` :

```bash
docker compose exec -T app php vendor/bin/phpunit --exclude-group=skip-pre-push
```

Configurer `phpunit.xml.dist` pour que le group existe :

```xml
<groups>
    <exclude>
        <group>skip-pre-push</group>
        <group>sandbox</group>
    </exclude>
</groups>
```

Le but est que `phpunit` par défaut **inclut** ces groups (CI complète), mais le pre-push hook les **exclut** (rapide + green).

### T-OPS11-04 — Doc

Ajouter à `CONTRIBUTING.md` :
- Comment lancer la suite complète vs la suite pre-push
- Quand utiliser `#[Group('skip-pre-push')]`
- Procédure pour retirer un test du skip (fix puis retirer le marker)

## DoD

- [ ] Inventaire publié dans la PR description
- [ ] `git push` réussit sans `--no-verify` sur une branche `feat/*` propre
- [ ] Suite CI complète (sans `--exclude-group`) reste verte ou avec failures explicitement marqués
- [ ] CONTRIBUTING.md mis à jour
