# INVESTIGATE-FUNCTIONAL-FAILURES — Tasks

> Investigation 13 erreurs functional Vacation/multi-tenant pre-existing (2 pts). 4 tasks / ~8h.

## Story rappel

Le push hook sprint-007 a révélé 13 erreurs functional pre-existing sur la suite complète (`vendor/bin/phpunit` sans `--testsuite`). Tous les commits sprint-007 ont utilisé `--no-verify` avec justification. Sprint-008 doit investiguer ces erreurs et soit fixer, soit documenter via ADR + skip-pre-push.

## Failures observées

```
1) OnboardingTemplateControllerTest::testToggleChangesActiveStatus
2) OnboardingTemplateControllerTest::testDeleteRemovesTemplate
3-9) VacationApprovalControllerTest::* (7 tests)
10) VacationRequestControllerTest::testCancelOnPendingVacationFlashesSuccess
11) CancelNotificationFlowTest::testManagerCancelOfApprovedVacationEmailsTheContributor
12) CancelNotificationFlowTest::testContributorCancelOfPendingRequestEmailsTheManager
13) NotificationEventChainTest::testDispatchedEventPersistsNotificationsForAllRecipients
14) NotificationEventChainTest::testServiceFailureDoesNotBreakDispatchChain
15-16) VacationApprovalControllerTest::testIndexShows... + testPendingCountApi...
17) TenantFilterRegressionTest::testFilterIsolatesClientsByTenant
18) TenantFilterRegressionTest::testFilterDeniesCrossTenantAccessByPrimaryKey
```

## Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-IFF-01 | [TEST] | Audit catégorisation: data fixture missing? bootstrap state? real failure? Tableau par test → root cause | 2h | - | 🔲 |
| T-IFF-02 | [TEST] | Fix lot 1: erreurs liées à fixtures/bootstrap (probablement Vacation BC fixtures manquantes ou conflict avec sprint-006 cleanup) | 2.5h | T-IFF-01 | 🔲 |
| T-IFF-03 | [TEST] | TenantFilterRegressionTest 2 erreurs — investigate pourquoi seul 2/4 passent (ils étaient OK quand mergés en PR #118 — régression?) | 2h | T-IFF-01 | 🔲 |
| T-IFF-04 | [DOC] | ADR-0007 — décision finale par catégorie: fix immédiat / skip-pre-push avec marker / supprimer test obsolète | 1.5h | T-IFF-02, T-IFF-03 | 🔲 |

## Acceptance Criteria

- [ ] Catégorisation complète des 13+ failures avec root cause
- [ ] ≥50% des failures fixées (cible 8/13)
- [ ] Failures non fixées documentées via skip-pre-push marker `@group skip-pre-push` + ADR-0007
- [ ] Push hook sprint-008+ peut être utilisé sans `--no-verify` pour les nouvelles branches
- [ ] CONTRIBUTING.md mise à jour avec procédure skip-pre-push

## Notes

Hypothèses avant audit:
- Vacation tests: probablement fixtures cassées par sprint-006 cleanup (ROLE_COMMERCIAL wiring + BC stubs cleanup)
- TenantFilter regression: probablement état DB partagé entre tests (bootstrap issue)
- Onboarding/Notification: probablement Doctrine schema désynchronisé en env test

## Sortie

Branche: `fix/investigate-functional-failures-sprint008`. 1-2 PRs (audit puis fixes).
