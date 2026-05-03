# Tâches — OPS-014

## Informations

- **Story Points** : 2
- **MoSCoW** : Must
- **Nature** : infra
- **Origine** : retro sprint-005 action #2
- **Total estimé** : 4h

## Résumé

Hook `pre-commit` qui refuse les fichiers auto-générés stagés. Sprint-005 a montré que `config/reference.php` polluait les diffs avant qu'OPS-012 le gitignore. Le hook joue rôle de garde-fou en amont du commit.

Fichiers à bloquer :

- `config/reference.php` (Symfony Maker config introspection)
- `var/cache/**`
- `var/log/**`
- `.phpunit.cache`
- `.deptrac.cache`
- `.php-cs-fixer.cache`

Le hook doit aussi déclencher en avance (pre-commit, pas pre-push) pour que le développeur soit informé immédiatement.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-OPS14-01 | [OPS] | Écrire `.githooks/pre-commit-autogenfiles.sh` (script de check) | 1.5h | - | 🔲 |
| T-OPS14-02 | [OPS] | Brancher dans `.githooks/pre-commit` existant (chain after composer/lint check) | 0.5h | T-OPS14-01 | 🔲 |
| T-OPS14-03 | [TEST] | Test manuel : `git add config/reference.php && git commit -m test` doit fail avec hint clair | 1h | T-OPS14-02 | 🔲 |
| T-OPS14-04 | [DOC] | Section dans `CONTRIBUTING.md` (sous-section "Git hooks") | 1h | T-OPS14-03 | 🔲 |

## Détail

### T-OPS14-01 — Script

```bash
#!/usr/bin/env bash
# .githooks/pre-commit-autogenfiles.sh
# Refuse les fichiers auto-générés dans le diff staged.

set -euo pipefail

PATTERNS=(
  'config/reference\.php$'
  'var/cache/'
  'var/log/'
  '\.phpunit\.cache'
  '\.deptrac\.cache'
  '\.php-cs-fixer\.cache'
)

violations=$(git diff --cached --name-only --diff-filter=ACMR | \
  grep -E "$(IFS='|'; echo "${PATTERNS[*]}")" || true)

if [[ -n "$violations" ]]; then
  echo "❌ pre-commit: fichiers auto-générés détectés en staged :"
  echo "$violations" | sed 's/^/   - /'
  echo
  echo "Ces fichiers sont régénérés par Symfony / PHPUnit / Deptrac à chaque exécution."
  echo "Ils sont déjà dans .gitignore. Si tu vois ce message :"
  echo
  echo "  1. Si le fichier est tracké à tort (ex: ajouté avant le gitignore) :"
  echo "     git rm --cached <fichier>"
  echo "     git commit -m 'chore: untrack auto-generated <fichier>'"
  echo
  echo "  2. Si tu modifies intentionnellement (rare) : --no-verify avec justification."
  exit 1
fi
```

### T-OPS14-02 — Chain dans pre-commit

```bash
# .githooks/pre-commit (extrait, à la fin)
"$(dirname "$0")/pre-commit-autogenfiles.sh" || exit $?
```

### T-OPS14-03 — Test manuel

```bash
git add config/reference.php
git commit -m "test ops-014" 2>&1 | grep "auto-générés détectés" || echo "FAIL: hook didn't trigger"
git reset HEAD config/reference.php
```

### T-OPS14-04 — Doc

Sous-section `### Hook pre-commit auto-générés (OPS-014)` dans `CONTRIBUTING.md` avec :

- Liste des patterns bloqués.
- Comment résoudre une fausse alerte.
- Lien vers `.gitignore`.

## DoD

- [ ] Script bash bloque les 6 patterns.
- [ ] Hook chained après les checks existants (pas de break du flow normal).
- [ ] Test manuel reproduit `git commit` rejeté avec message clair.
- [ ] CONTRIBUTING.md section créée.
- [ ] PR ≤ 200 lignes diff.
