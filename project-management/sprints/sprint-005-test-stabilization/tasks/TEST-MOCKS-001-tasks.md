# Tâches — TEST-MOCKS-001

## Informations

- **Story Points** : 3
- **MoSCoW** : Should
- **Origine** : sprint-004 review candidate (debt from TEST-DEPRECATIONS-001)
- **Total estimé** : 7h

## Résumé

TEST-DEPRECATIONS-001 (PR #74) a silencé 229 PHPUnit notices "No expectations were configured for the mock object" en ajoutant `#[AllowMockObjectsWithoutExpectations]` sur 28 classes. La résolution propre est de convertir `createMock` → `createStub` là où le mock ne sert que de stub (pas d'assertion sur l'invocation).

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-TM-01 | [TEST] | Audit des 28 classes : lister par site les `createMock` candidats à `createStub` | 1h | - | 🔲 |
| T-TM-02 | [TEST] | Conversion `createMock` → `createStub` (script + revues manuelles) | 4h | T-TM-01 | 🔲 |
| T-TM-03 | [TEST] | Retirer `#[AllowMockObjectsWithoutExpectations]` des classes désormais propres | 1h | T-TM-02 | 🔲 |
| T-TM-04 | [REV] | Code review | 1h | T-TM-03 | 🔲 |

## Détail des tâches

### T-TM-01 — Audit

Pour chaque classe avec l'attribut, lister les `createMock(...)` calls :
- **Stub** (candidat conversion) : pas de `->expects(self::once())`, juste des `->method(...)->willReturn(...)`
- **Mock** (garder) : `->expects(self::once())` ou `->expects(self::never())` présent

Output : tableau `class | total mocks | candidates stub | candidates mock`.

### T-TM-02 — Conversion

Pour chaque `createMock(X::class)` candidat :
- Remplacer par `createStub(X::class)` (signature identique, contrat plus restrictif)
- `createStub` ne permet pas `->expects(...)`, donc le compilateur PHP attrapera les usages mixtes

Script Python ou sed pour faire le sweep + relecture manuelle. Pour 28 fichiers, ~10-20 conversions par fichier en moyenne.

### T-TM-03 — Cleanup attribute

Pour chaque classe où **toutes** les conversions ont été faites :
- Retirer `#[AllowMockObjectsWithoutExpectations]`
- Retirer le `use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations`

Lancer `phpunit --display-all-issues` : 0 notice attendu.

### T-TM-04 — Review

Critères : 0 notice, suite verte, PR <400 lignes diff (peut nécessiter découpe en stack si > 400).

## DoD

- [ ] `phpunit --testsuite=unit --display-all-issues` : 0 notice "No expectations"
- [ ] `#[AllowMockObjectsWithoutExpectations]` ne reste que sur les classes où c'est intentionnel (justifié dans un commentaire)
- [ ] Suite unit reste verte (379 tests)
- [ ] PHPStan level 5 : 0 nouvelle erreur

## Risque

Si la conversion est plus large que 400 lignes diff (politique OPS-006), découper en stack `feat/test-mocks-001-base` → `feat/test-mocks-001-step1` ... selon les modules.
