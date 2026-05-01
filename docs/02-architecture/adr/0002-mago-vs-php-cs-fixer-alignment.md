# ADR-0002 — Conflit d'alignement Mago vs PHP-CS-Fixer

## Statut

Accepté — 2026-05-01

## Contexte

Sprint-002 a observé un conflit récurrent entre :

- **PHP-CS-Fixer** (`friendsofphp/php-cs-fixer` 3.93.x) avec la règle `binary_operator_spaces` réglée sur `default: 'align_single_space_minimal'`. Cette règle aligne verticalement les opérateurs `=>` dans un même bloc (assignations, tableaux associatifs).
- **Mago** (`carthage-software/mago` 1.6.x) qui formate les mêmes blocs sans alignement vertical et considère les espaces additionnels comme du bruit.

Conséquences observées sprint-002 :

- Sur PR #36 (TEST-001), CS-Fixer a produit `$ctx['type']                                              === 'quote_won'` (75 espaces) pour aligner avec un autre champ ; le code restait fonctionnel mais la lisibilité a chuté.
- La CI `Mago Code Quality` (workflow `.github/workflows/ci.yml`) a été marquée `FAILURE` en permanence sur les PRs sprint-002 alors que `Code Quality Checks (Mago, mago)` (workflow `quality.yml`) passait — divergence due au fait que les deux jobs n'ont pas la même base de fichiers analysés.
- Tentative de rouler `composer mago-format-fix` après `composer phpcsfixer-fix` produit un diff inverse : un cycle de re-formatage indéfini en cas de pre-commit hook.

## Options évaluées

### Option A — Garder l'alignement CS-Fixer, désactiver la règle équivalente côté Mago

- ✅ Pas de churn initial sur tout `src/`
- ✅ L'équipe est habituée au style aligné depuis le démarrage du projet
- ❌ Mago perd l'argument "formateur opinioné moderne"
- ❌ Lectures de code en environnement sans alignement (web GitHub mobile, par exemple) restent désagréables

### Option B — Désactiver `binary_operator_spaces` alignment côté CS-Fixer, laisser Mago piloter

- ✅ Output cohérent avec ce que Mago produit
- ✅ Formatage moderne sans whitespace cosmétique
- ✅ Un seul outil de référence (Mago) pour le formatage agressif
- ❌ Diff initial massif sur tout `src/` et `tests/` (estimation > 1500 lignes — confirmé par sprint-002 PR #36 et OPS-005 qui ont touché 9 fichiers en passe pre-commit)
- ❌ Reviewers humains habitués à voir l'alignement perdent un repère

### Option C — Garder les deux et désactiver `Mago Code Quality` en `continue-on-error: true`

- ✅ Aucun churn
- ❌ Dette technique permanente, signal CI dilué (déjà appliqué sprint-002, c'est ce qu'on cherche à fermer)
- ❌ Mago devient inutile : si rien n'échoue, autant le retirer

## Décision

**Option B retenue.**

- `binary_operator_spaces` passe de `'default' => 'align_single_space_minimal'` à `'default' => 'single_space'` (pas d'alignement vertical, un seul espace autour de l'opérateur).
- Mago reste actif sans changement de configuration.
- PHP-CS-Fixer tourne **avant** Mago dans la chaîne pre-commit (déjà le cas).
- Le diff de migration est encapsulé dans **TECH-DEBT-002** (sprint-003) : 1 PR dédiée applique `composer phpcsfixer-fix` sur tout le repo et stage le résultat. Cette PR ne change pas de logique métier ; sa review est une validation visuelle « est-ce que le diff est bien purement whitespace ? ».

## Conséquences

### Positives

- Output unique et stable côté CS-Fixer + Mago.
- CI checks `Mago Code Quality` deviennent verts sur les nouvelles PRs sans `continue-on-error`.
- Suppression d'un cycle de re-formatage potentiel.
- Lisibilité GitHub web mobile / diff `gh pr diff` retrouvée sur les blocs avec assignations multiples.

### Négatives

- Une PR de migration unique (TECH-DEBT-002) avec un diff repo-wide important. Encadrée par la politique OPS-006 PR<400 lignes : justification écrite acceptée parce que migration formattage massive.
- Pendant la transition, certaines PRs sprint-003 ouvertes avant la migration auront un diff supplémentaire au moment du rebase — accepté.

### Suivi

- TECH-DEBT-002 doit être mergée **avant** US-070 / TEST-005 pour éviter les conflits.
- Si l'équipe décide après 2 sprints que l'absence d'alignement nuit à la lisibilité dans `tests/Unit/Service/*Test.php` (data providers volumineux), une révision peut activer `binary_operator_spaces.operators.=>=align_single_space_minimal` ciblé sur ces fichiers via PHP-CS-Fixer scoped finder, sans réintroduire la règle globale.

## Références

- Sprint-002 retro action 2 : « ADR sur le conflit Mago / PHP-CS-Fixer »
- PR sprint-002 où l'alignement bizarre est apparu : #36, #43
- Discussion 5 Pourquoi : sprint-002 retro thème A
