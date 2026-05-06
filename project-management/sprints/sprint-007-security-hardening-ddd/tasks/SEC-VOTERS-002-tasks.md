# SEC-VOTERS-002 — Tasks

> Voters entité pour `Vacation`, `Client`, `ExpenseReport`, `Contributor`.
> 3 pts / 4 tasks / ~5-7h.

| ID | Type | Description | Estimate | Status |
|----|------|-------------|---------:|--------|
| T-SV2-01 | [BE] | `VacationVoter` (VIEW/REQUEST/APPROVE/REJECT/CANCEL) — owner + manager hierarchy | 1.5h | 🔲 |
| T-SV2-02 | [BE] | `ClientVoter` + `ContributorVoter` (VIEW/EDIT) — tenant + role | 2h | 🔲 |
| T-SV2-03 | [BE] | `ExpenseReportVoter` (VIEW/EDIT/SUBMIT/APPROVE) — owner + manager | 1h | 🔲 |
| T-SV2-04 | [TEST] | Tests cross-tenant + cross-role pour les 4 voters | 1.5-2h | 🔲 |

## Acceptance Criteria

- [ ] Chaque voter applique le pattern triplet (tenant + role + ownership) de SEC-VOTERS-001
- [ ] `VacationVoter::APPROVE` vérifie que le manager est bien le manager direct du contributeur (pas n'importe quel ROLE_MANAGER)
- [ ] `ExpenseReportVoter::SUBMIT` réservé à l'owner ; `APPROVE` au manager + ROLE_COMPTA
- [ ] Tests : 8+ scénarios couvrant les permissions (matrice rôle × action × ownership)
- [ ] PHPStan max OK

## Notes

Cette story est **Should** (déférable sprint-008). Sprint-007 vise une couverture des 8 BCs prioritaires en 2 stories séparées :
- SEC-VOTERS-001 (Must) : Project + Order + Invoice + Timesheet (cœur métier rentabilité)
- SEC-VOTERS-002 (Should) : Vacation + Client + ExpenseReport + Contributor (HR-side)

Si capacité pression J5-J7, défer à sprint-008.
