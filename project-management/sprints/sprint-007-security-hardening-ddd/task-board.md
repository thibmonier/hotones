# Task Board — Sprint 007

Légende : 🔲 À faire | 🔄 En cours | 👀 En review | ✅ Terminé | 🚫 Bloqué

## 🔲 À Faire (35 tasks, 32 pts engagés)

### SEC-MULTITENANT-001 (8 pts)

| ID | Story | Tâche | Estimate |
|---|---|---|---:|
| T-SMT1-01 | SEC-MULTITENANT-001 | TenantContext service | 1h |
| T-SMT1-02 | SEC-MULTITENANT-001 | TenantAwareTrait | 1h |
| T-SMT1-03 | SEC-MULTITENANT-001 | TenantFilter SQLFilter | 2-3h |
| T-SMT1-04 | SEC-MULTITENANT-001 | doctrine.yaml registration | 0.5h |
| T-SMT1-05 | SEC-MULTITENANT-001 | TenantMiddleware HTTP | 2h |
| T-SMT1-06 | SEC-MULTITENANT-001 | TenantFilterSubscriber | 1.5h |
| T-SMT1-07 | SEC-MULTITENANT-001 | Tests unitaires TenantContext + Filter | 2-3h |

### SEC-MULTITENANT-002 (5 pts)

| ID | Story | Tâche | Estimate |
|---|---|---|---:|
| T-SMT2-01 | SEC-MULTITENANT-002 | Audit 63 entités tenant-scoped | 1h |
| T-SMT2-02 | SEC-MULTITENANT-002 | Backfill TenantAwareTrait sur 50+ | 3-4h |
| T-SMT2-03 | SEC-MULTITENANT-002 | Migration DB si nécessaire | 1h |
| T-SMT2-04 | SEC-MULTITENANT-002 | PHPUnit + PHPStan + Deptrac | 1-2h |

### SEC-MULTITENANT-003 (5 pts)

| ID | Story | Tâche | Estimate |
|---|---|---|---:|
| T-SMT3-01 | SEC-MULTITENANT-003 | Test isolation Client | 1.5h |
| T-SMT3-02 | SEC-MULTITENANT-003 | Test isolation Project + Order + Invoice | 2.5h |
| T-SMT3-03 | SEC-MULTITENANT-003 | Test isolation Timesheet + Vacation + Contributor | 2h |
| T-SMT3-04 | SEC-MULTITENANT-003 | Test 404 anti-énumération | 1h |
| T-SMT3-05 | SEC-MULTITENANT-003 | Test bypass disableFilter | 1h |

### SEC-VOTERS-001 (5 pts)

| ID | Story | Tâche | Estimate |
|---|---|---|---:|
| T-SV1-01 | SEC-VOTERS-001 | ProjectVoter | 2h |
| T-SV1-02 | SEC-VOTERS-001 | OrderVoter (state machine) | 2h |
| T-SV1-03 | SEC-VOTERS-001 | InvoiceVoter (verrou SENT) | 1.5h |
| T-SV1-04 | SEC-VOTERS-001 | TimesheetVoter (validation manager) | 1.5h |
| T-SV1-05 | SEC-VOTERS-001 | Tests cross-tenant + cross-role 4 voters | 2-3h |

### SEC-VOTERS-002 (3 pts) — déférable

| ID | Story | Tâche | Estimate |
|---|---|---|---:|
| T-SV2-01 | SEC-VOTERS-002 | VacationVoter | 1.5h |
| T-SV2-02 | SEC-VOTERS-002 | ClientVoter + ContributorVoter | 2h |
| T-SV2-03 | SEC-VOTERS-002 | ExpenseReportVoter | 1h |
| T-SV2-04 | SEC-VOTERS-002 | Tests cross-tenant + cross-role 4 voters | 1.5-2h |

### DDD-PHASE0-001 (2 pts)

| ID | Story | Tâche | Estimate |
|---|---|---|---:|
| T-DP01-01 | DDD-PHASE0-001 | Lister 160 fichiers branche par BC | 0.5h |
| T-DP01-02 | DDD-PHASE0-001 | Décision keep/rewrite/discard | 1.5h |
| T-DP01-03 | DDD-PHASE0-001 | Identifier conflits attendus | 1h |
| T-DP01-04 | DDD-PHASE0-001 | Rapport markdown | 0.5-1h |

### DDD-PHASE0-002 (3 pts)

| ID | Story | Tâche | Estimate |
|---|---|---|---:|
| T-DP02-01 | DDD-PHASE0-002 | Cherry-pick interfaces + traits Shared | 1h |
| T-DP02-02 | DDD-PHASE0-002 | Cherry-pick + adapter Email + Money VOs | 1.5h |
| T-DP02-03 | DDD-PHASE0-002 | Tests unitaires Email + Money | 2h |
| T-DP02-04 | DDD-PHASE0-002 | Cherry-pick custom Doctrine Types | 1h |

### TEST-MOCKS-003 (1 pt)

| ID | Story | Tâche | Estimate |
|---|---|---|---:|
| T-TM3-01 | TEST-MOCKS-003 | Audit conversion sûre | 0.5-1h |
| T-TM3-02 | TEST-MOCKS-003 | Conversions par batch | 0.5-1h |

## 🔄 En Cours

_(vide — sprint pas démarré)_

## 👀 En Review

_(vide)_

## ✅ Terminé

_(vide)_

## 🚫 Bloqué

_(vide)_

## Estimation totale

| Métrique | Valeur |
|----------|-------:|
| Stories engagées | 8 |
| Tasks total | 35 |
| Heures estimées | 39-50h (chemin critique) |
| Capacité brute | 64h |
| Capacité projetée (coefs) | 22 pts |
| Engagement | 32 pts (stretch 145%) |
| Marge | -10 pts (deferred fallback : SEC-VOTERS-002 ou DDD-PHASE0-002) |
