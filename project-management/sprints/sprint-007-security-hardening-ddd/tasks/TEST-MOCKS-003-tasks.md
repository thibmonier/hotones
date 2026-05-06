# TEST-MOCKS-003 — Tasks

> Conversion `createMock` → `createStub` sur 22 classes Cas C (203 Notices PHPUnit → 0).
> 1 pt / 2 tasks / ~1-2h.

| ID | Type | Description | Estimate | Status |
|----|------|-------------|---------:|--------|
| T-TM3-01 | [TEST] | Audit fichier par fichier : pour chaque mock sans `expects()`, identifier si conversion `createMock → createStub` est sûre (pas de `with()`/`expects()` sur ce mock spécifique) | 0.5-1h | 🔲 |
| T-TM3-02 | [TEST] | Conversions par batch + run suite full PHPUnit | 0.5-1h | 🔲 |

## Acceptance Criteria

- [ ] 0 PHPUnit Notice résiduel après conversion (`vendor/bin/phpunit --testsuite unit`).
- [ ] Aucune régression sur les 397 tests Unit.
- [ ] Convention CONTRIBUTING.md (T-TM2-06) respectée : `createStub` quand pas d'assertion d'appel.
- [ ] Mise à jour finale du tableau audit `project-management/sprints/sprint-006-test-debt-cleanup/tasks/TEST-MOCKS-002-audit.md` : passage des classes Cas C en "fully clean".

## Notes

- Audit T-TM2-01 (sprint-006) avait identifié 24 classes Cas C. PR #105 a retiré l'attribut `#[AllowMockObjectsWithoutExpectations]` (sans toucher au pattern createMock).
- Les 203 Notices résultantes correspondent à des mocks **dans des classes Cas C** où certains mocks ne sont pas asserted (ex : un mock injecté dans le constructeur mais pas vérifié dans tous les tests).
- Conversion `createMock → createStub` parfois sûre (le mock n'est jamais asserted), parfois non (mock asserted dans 1 test mais pas dans les autres).
- Approche conservatrice : ne pas casser de tests existants ; convertir uniquement les mocks dont **aucune** méthode est appelée avec `expects()` ou `with()` dans le test.

## Risques

| Risque | Mitigation |
|--------|------------|
| Conversion casse un test qui implicitement vérifie l'absence d'appel | Run suite après chaque batch ; rollback si fail |
| Story trop optimiste (1 pt) si beaucoup de cas hybrides | Audit T-TM3-01 décide ; déférer en sprint-008 si volume dépasse |
