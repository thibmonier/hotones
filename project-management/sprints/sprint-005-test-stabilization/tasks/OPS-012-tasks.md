# Tâches — OPS-012

## Informations

- **Story Points** : 2
- **MoSCoW** : Should
- **Origine** : retro sprint-004 action #1
- **Total estimé** : 5h

## Résumé

Deux sources de bruit dans le repo :
1. `config/reference.php` regenerated par `cache:clear` à chaque story sprint-004 → 3+ stashes, plusieurs commits "noise". Le fichier est auto-généré par Symfony Maker, pas une source de vérité humaine.
2. `phpstan-baseline.neon` contient des entries qui pointent vers des fichiers déplacés (cas `VacationRequestController` → `Presentation/Vacation/Controller/`). Sprint-004 / TEST-006 a corrigé une entry à la main, mais d'autres restent obsolètes (12 patterns "ignored not matched" lors d'un run scopé).

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-OPS12-01 | [OPS] | Ajouter `config/reference.php` à `.gitignore` + `git rm --cached` | 1h | - | 🔲 |
| T-OPS12-02 | [OPS] | Régénérer `phpstan-baseline.neon` from scratch (delete + `phpstan analyse --generate-baseline`) | 2h | T-OPS12-01 | 🔲 |
| T-OPS12-03 | [DOC] | Section "Fichiers générés" dans `CONTRIBUTING.md` (quoi est git-ignored et pourquoi) | 1h | T-OPS12-02 | 🔲 |
| T-OPS12-04 | [REV] | Code review | 1h | T-OPS12-03 | 🔲 |

## Détail des tâches

### T-OPS12-01 — gitignore reference

```bash
echo 'config/reference.php' >> .gitignore
git rm --cached config/reference.php
git commit -m "chore: stop tracking config/reference.php (auto-generated)"
```

Vérifier que `cache:clear` ne rejoute pas le fichier dans le tracking.

**Note** : si `config/reference.php` est référencé ailleurs (ex: composer scripts, Makefile) → vérifier qu'aucun `require_once` n'en dépend en runtime. C'est normalement un fichier de meta pour les annotations Symfony, pas inclus en runtime.

### T-OPS12-02 — Regen baseline

```bash
docker compose exec -T app vendor/bin/phpstan clear-result-cache
rm phpstan-baseline.neon
docker compose exec -T app composer phpstan -- --generate-baseline
```

Le résultat doit avoir **0 patterns "ignored not matched"** lors d'un run scopé. La taille du baseline peut augmenter ou diminuer.

Comparer le nombre d'entries avant/après — si grosse différence, documenter dans la PR.

### T-OPS12-03 — Doc

Section "Fichiers générés" dans `CONTRIBUTING.md` :

```markdown
## Fichiers auto-générés (gitignore)

Les fichiers suivants sont **gitignored** car régénérés par les outils :

- `config/reference.php` — généré par Symfony Maker à chaque `cache:clear`. Pas inclus en runtime.
- `var/coverage/`, `var/cache/`, `var/log/` — sortie des outils dev.

Si vous voyez l'un d'eux dans `git status`, c'est probablement un cas où `git rm --cached` a été oublié. Reporter une issue.

## phpstan-baseline.neon

La baseline accumule des erreurs tolérées. **Toute nouvelle erreur doit être fixée**, pas ajoutée à la baseline. La baseline est régénérée from scratch quand elle dérive (cf OPS-012).
```

### T-OPS12-04 — Review

Critères : `git status` clean après `cache:clear`, baseline régénérée sans patterns non-matchés.

## DoD

- [ ] `config/reference.php` n'apparaît plus dans `git status` après `cache:clear`
- [ ] `phpstan` ne signale plus de pattern `ignoreErrors not matched`
- [ ] CONTRIBUTING.md documente les fichiers auto-générés
- [ ] PR review approuvée

## Risques

Si `config/reference.php` se révèle être inclus en runtime (improbable mais possible), la PR doit reverter cette partie et le tracker dans une story différente.
