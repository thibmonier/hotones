# TEST-MOCKS-004 — Tasks

> Solde Strate 3 + cas createPartialMock (2 pts). 3 tasks / ~6h.

## Story rappel

TEST-MOCKS-003 a converti 22 files (Strates 1+2) mais a déféré 3 files Strate 3 et les cas createPartialMock. Réduction 251→208 notices (-17%). Cible TEST-MOCKS-004: réduire à ≤50 notices.

## Files Strate 3 (helpers retournent &MockObject)

- `tests/Unit/Command/CreateUserCommandTest.php`
- `tests/Unit/Security/Voter/CompanyVoterTest.php`
- `tests/Unit/Service/AlertDetectionServiceTest.php`

## Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-TM4-01 | [TEST] | Refactor 3 files Strate 3 — changer signatures helpers `: TYPE&MockObject` → `: TYPE&Stub` + adapter callers + convertir createMock | 3h | TOOLING-MOCK-SCRIPT (utiliser count-mocks.pl) | 🔲 |
| T-TM4-02 | [TEST] | Cas createPartialMock (TimesheetVoterTest 5 notices) — refactor pour utiliser Reflection ou real Timesheet | 1.5h | - | 🔲 |
| T-TM4-03 | [REV] | Run suite Unit complète + verify notices ≤ 50 + commit + PR | 1.5h | T-TM4-01, T-TM4-02 | 🔲 |

## Acceptance Criteria

- [ ] 3 files Strate 3 refactorisés sans régression
- [ ] TimesheetVoterTest createPartialMock résolu (Reflection ou real instance)
- [ ] PHPUnit Notices ≤ 50 (vs 208 actuel)
- [ ] Suite Unit 466/466 PASS, 0 régression
- [ ] CONTRIBUTING.md mise à jour avec pattern createStub définitif

## Notes

Si T-TM4-01 trop ambitieux pour 3h: scoper à 1 file (CreateUserCommandTest le plus simple) et déférer 2 autres en TEST-MOCKS-005.

## Sortie

Branche: `feat/test-mocks-004`. PR base main.
