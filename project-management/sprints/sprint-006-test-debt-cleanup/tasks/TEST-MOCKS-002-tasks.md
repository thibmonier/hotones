# Tâches — TEST-MOCKS-002

## Informations

- **Story Points** : 8
- **MoSCoW** : Must
- **Nature** : refactor
- **Origine** : sprint-005 review (TEST-MOCKS-001 a fait 5 classes pures-stubs ; reste les classes mixtes)
- **Total estimé** : 8h

## Résumé

31 classes ont encore `#[AllowMockObjectsWithoutExpectations]`. Cet attribut a été ajouté en sprint-004 (TEST-DEPRECATIONS-001) pour silence les notices PHPUnit 13 sans casser les tests. La conversion correcte est `createMock` → `createStub` quand le mock n'asserte rien.

Cas par cas :

- **Cas A** — pure stub (méthode mockée mais aucun `expects()`/`->method()->method()` chain consumé pour assertion) → conversion mécanique `createStub`.
- **Cas B** — mock avec assertion implicite via test result (return value path) → garder `createMock`, retirer l'attribut.
- **Cas C** — mock avec assertion explicite (`expects($this->once())`, `with()`) → garder `createMock`, retirer l'attribut.
- **Cas D** — pattern ambigu / hybride → escalation : ADR ou refactoring vers Mockery.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-TM2-01 | [TEST] | Audit liste 26 classes restantes (5 déjà faites en sprint-005) avec catégorie A/B/C/D | 1h | - | 🔲 |
| T-TM2-02 | [TEST] | Convertir lot 1 — 6 classes Cas A (pure stubs) | 1.5h | T-TM2-01 | 🔲 |
| T-TM2-03 | [TEST] | Convertir lot 2 — 8 classes Cas A | 1.5h | T-TM2-01 | 🔲 |
| T-TM2-04 | [TEST] | Lot 3 — 6 classes Cas B/C (retirer attribut, garder createMock) | 1.5h | T-TM2-01 | 🔲 |
| T-TM2-05 | [TEST] | Lot 4 — 6 classes Cas B/C ou Cas D ADR si bloqué | 1.5h | T-TM2-01 | 🔲 |
| T-TM2-06 | [DOC] | Mettre à jour CONTRIBUTING.md "createStub vs createMock" + post-mortem TEST-DEPRECATIONS-001 | 1h | T-TM2-05 | 🔲 |

## Détail

### T-TM2-01 — Audit

```bash
grep -lr "AllowMockObjectsWithoutExpectations" tests | sort > /tmp/mocks-002-audit.txt
# Pour chaque fichier : ouvrir, classer A/B/C/D dans un tableau markdown.
```

**DoD** : `tasks/TEST-MOCKS-002-audit.md` listant 26 classes avec colonne Cas + raison.

### T-TM2-02 → T-TM2-05 — Lots de conversion

Procéder par lot de 5–8 classes pour rester sous la barre <400 lignes diff par PR. 1 PR par lot ou regrouper les 4 lots dans 2 PRs si le diff total < 800 lignes.

Pattern conversion Cas A :

```php
// Avant
private \PHPUnit\Framework\MockObject\MockObject $repositoryMock;
$this->repositoryMock = $this->createMock(FooRepository::class);

// Après
private \PHPUnit\Framework\MockObject\Stub $repositoryMock;
$this->repositoryMock = $this->createStub(FooRepository::class);
```

Plus retirer `#[AllowMockObjectsWithoutExpectations]` de la classe + l'import si plus utilisé.

Pattern Cas B/C : ne change que `#[AllowMockObjectsWithoutExpectations]` (à retirer) — les `createMock` restent car le test consomme leur résultat dans une assertion.

### T-TM2-06 — Doc

Section dans CONTRIBUTING.md ou nouveau `docs/04-development/test-mocks.md` :

- Quand utiliser `createStub` (Cas A pure stub).
- Quand utiliser `createMock` (Cas B/C avec assertion).
- Pourquoi NE PAS utiliser `#[AllowMockObjectsWithoutExpectations]` comme contournement par défaut.
- Renvoyer vers PHPUnit 13 docs.

## DoD

- [ ] 26 classes converties ou ADR pour les Cas D survivants.
- [ ] `grep -rl AllowMockObjectsWithoutExpectations tests` → 0 ou explication ADR pour chaque survivant.
- [ ] CONTRIBUTING.md / `test-mocks.md` à jour.
- [ ] Suite test toujours verte (`make test` ou équivalent).
