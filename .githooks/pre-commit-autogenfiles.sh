#!/usr/bin/env bash
# OPS-014 (sprint-006) — refuse les fichiers auto-générés stagés.
#
# Sprint-005 a vu config/reference.php polluer chaque diff avant qu'OPS-012
# le gitignore. Ce hook agit comme garde-fou : si un fichier auto-généré est
# stagé, le commit est rejeté avec un message expliquant comment résoudre.
#
# Patterns refusés (alignés avec .gitignore) :
#   - config/reference.php           (Symfony Maker config introspection)
#   - var/cache/**, var/log/**       (caches et logs Symfony)
#   - .phpunit.cache                 (PHPUnit 13 result cache)
#   - .deptrac.cache                 (Deptrac analysis cache)
#   - .php-cs-fixer.cache            (CS-Fixer cache)
#
# Bypass intentionnel : `git commit --no-verify`. Justifier dans le message.

set -euo pipefail

PATTERNS=(
  '^config/reference\.php$'
  '^var/cache/'
  '^var/log/'
  '^\.phpunit\.cache$'
  '^\.deptrac\.cache$'
  '^\.php-cs-fixer\.cache$'
)

violations=$(
  git diff --cached --name-only --diff-filter=ACMR \
    | grep -E "$(IFS='|'; echo "${PATTERNS[*]}")" \
    || true
)

if [[ -n "$violations" ]]; then
  printf '\n❌ pre-commit: fichiers auto-générés détectés en staged :\n\n' >&2
  printf '%s\n' "$violations" | sed 's/^/   - /' >&2
  printf '\n' >&2
  printf 'Ces fichiers sont régénérés par Symfony / PHPUnit / Deptrac à chaque\n' >&2
  printf 'exécution et ne devraient jamais être committés. Ils sont déjà dans\n' >&2
  printf '.gitignore (voir section "Fichiers auto-générés" du CONTRIBUTING.md).\n\n' >&2
  printf 'Résolution :\n\n' >&2
  printf '  1. Si tracké à tort (ex: ajouté avant le gitignore) :\n' >&2
  printf '       git rm --cached <fichier>\n' >&2
  printf '       git commit -m "chore: untrack auto-generated <fichier>"\n\n' >&2
  printf '  2. Si modification volontaire (rare) :\n' >&2
  printf '       git commit --no-verify  # avec justification dans le message\n\n' >&2
  exit 1
fi

exit 0
