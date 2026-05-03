# TEST-MOCKS-002 — Audit (T-TM2-01)

> Inventaire des 31 classes décorées `#[AllowMockObjectsWithoutExpectations]`, classées par cas pour orienter les lots de conversion.

## Méthodologie

Heuristique automatique sur chaque fichier :

| Signal | Ce qu'il indique |
|---|---|
| `expects(` | Mock avec assertion explicite (`->expects($this->once())`, etc.) — **Cas C**. |
| `->with(` | Mock avec assertion sur arguments — **Cas C**. |
| `willReturn` / `->method(` | Mock avec valeur de retour mais pas d'assertion — possible **Cas A/B**. |
| Aucun signal | Mock pur (pas d'usage) — **Cas A** strict. |

## Catégories

| Cas | Définition | Action |
|---|---|---|
| **A** | Pure stub (aucun usage mock) | `createMock` → `createStub` + retirer attribut. |
| **A/B** | Mock avec `willReturn` mais pas de `expects()` ; sprint-005 PR #85 a déjà converti certains | Vérifier que le type de propriété est `Stub` (pas `MockObject`), sinon corriger ; retirer attribut. |
| **B** | Mock avec assertion implicite via résultat (rare) | Garder `createMock`, retirer attribut. |
| **C** | Mock avec `expects()` ou `->with()` | Garder `createMock`, retirer attribut (en théorie l'attribut est superflu si expects existe). |
| **D** | Pattern ambigu / hybride | Escalation : ADR ou refactor vers Mockery. |

## Résultats — 31 classes

### Cas A (3 — pure stubs sans willReturn)

| Fichier | Note |
|---|---|
| `tests/Integration/Service/BoondManager/BoondManagerClientTest.php` | Ratio expects=0 with=0 ret=0. À vérifier visuellement (probable test avec MockHttpClient + Response). |
| `tests/Integration/Service/HubSpot/HubSpotClientTest.php` | idem. |
| `tests/Unit/Service/ProfitabilityServiceTest.php` | Sprint-005 PR #85 a déjà converti — vérifier si attribut résiduel à retirer. |

### Cas A/B (4 — sprint-005 partiel : convertis en createStub mais attribut résiduel)

| Fichier | expects | with | willReturn |
|---|---:|---:|---:|
| `tests/Unit/Service/ProfitabilityPredictorTest.php` | 0 | 0 | 25 |
| `tests/Unit/Domain/Vacation/Entity/VacationTest.php` | 0 | 0 | 3 |
| `tests/Unit/Service/BillingServiceTest.php` | 0 | 0 | 4 |
| `tests/Unit/Service/ProjectRiskAnalyzerTest.php` | 0 | 0 | 62 |

**Action** : juste retirer l'attribut + import si plus utilisé. Les `createMock` doivent déjà avoir été convertis en `createStub` par PR #85 — à vérifier.

### Cas C (24 — mocks avec expects/method explicites)

Classés par charge (`expects` count desc) :

| Fichier | expects | ret |
|---|---:|---:|
| `tests/Unit/Service/HrMetricsServiceTest.php` | 23 | 57 |
| `tests/Unit/Service/OnboardingServiceTest.php` | 20 | 25 |
| `tests/Unit/Service/Analytics/MetricsCalculationServiceTest.php` | 20 | 74 |
| `tests/Unit/Service/NotificationServiceTest.php` | 17 | 30 |
| `tests/Unit/Service/PerformanceReviewServiceTest.php` | 15 | 26 |
| `tests/Unit/Service/Analytics/DashboardReadServiceTest.php` | 12 | 63 |
| `tests/Unit/Service/SecureFileUploadServiceTest.php` | 11 | 31 |
| `tests/Unit/Command/AnalyticsCacheCommandTest.php` | 9 | 11 |
| `tests/Unit/Command/RecalculateClientServiceLevelCommandTest.php` | 6 | 27 |
| `tests/Unit/EventSubscriber/LoginSecuritySubscriberTest.php` | 6 | 15 |
| `tests/Unit/Command/CreateUserCommandTest.php` | 5 | 30 |
| `tests/Unit/Service/WorkloadPredictionServiceTest.php` | 5 | 6 |
| `tests/Unit/Security/Voter/CompanyVoterTest.php` | 4 | 24 |
| `tests/Unit/EventSubscriber/NotificationSubscriberTest.php` | 4 | 7 |
| `tests/Unit/Command/DispatchMetricsRecalculationCommandTest.php` | 3 | 14 |
| `tests/Unit/Service/Planning/TaceAnalyzerTest.php` | 3 | 28 |
| `tests/Unit/Application/Vacation/Query/GetContributorVacationsHandlerTest.php` | 3 | 6 |
| `tests/Unit/Command/NpsMarkExpiredCommandTest.php` | 3 | 7 |
| `tests/Unit/Application/Vacation/Query/GetPendingVacationsForManagerHandlerTest.php` | 3 | 8 |
| `tests/Unit/Command/CheckAlertsCommandTest.php` | 2 | 12 |
| `tests/Unit/Service/ForecastingServiceTest.php` | 2 | 14 |
| `tests/Unit/Application/Vacation/CancelVacationHandlerDispatchTest.php` | 2 | 4 |
| `tests/Unit/Service/PdfGeneratorServiceTest.php` | 1 | 16 |
| `tests/Unit/Application/Vacation/Query/CountApprovedDaysHandlerTest.php` | 1 | 3 |

**Action** : tester sur 1 classe Cas C que retirer l'attribut + garder `createMock` ne casse pas le test. Si OK → batch sweep sur les 24.

### Cas D (0 identifié pour l'instant)

Aucune classe ne tombe automatiquement en D ; vérification visuelle pendant les lots si pattern ambigu rencontré.

## Plan de lots révisé

Plan original `TEST-MOCKS-002-tasks.md` parlait de 4 lots de 6-8 classes. La répartition réelle suggère :

- **Lot 1** (T-TM2-02, ~1h) — 7 classes Cas A + A/B (les "vite faites") :
  - 3 Cas A (Boond, HubSpot, ProfitabilityService)
  - 4 Cas A/B (ProfitabilityPredictor, VacationTest, BillingService, ProjectRiskAnalyzer)
  - Vérifier `createMock` → `createStub` + supprimer attribut + import.

- **Lot 2** (T-TM2-03, ~2h) — 8 classes Cas C "lourdes" (expects ≥ 10) :
  - HrMetricsService, OnboardingService, MetricsCalculationService, NotificationService, PerformanceReviewService, DashboardReadService, SecureFileUploadService, AnalyticsCacheCommand.
  - Test pilote : retirer l'attribut sur HrMetricsService → run le test → si vert, batch sweep le reste du lot.

- **Lot 3** (T-TM2-04, ~2h) — 8 classes Cas C "moyennes" (expects 4-9) :
  - RecalculateClientServiceLevelCommand, LoginSecuritySubscriber, CreateUserCommand, WorkloadPredictionService, CompanyVoter, NotificationSubscriber, DispatchMetricsRecalculationCommand, TaceAnalyzer.

- **Lot 4** (T-TM2-05, ~2h) — 8 classes Cas C "légères" (expects 1-3) :
  - GetContributorVacationsHandler, NpsMarkExpiredCommand, GetPendingVacationsForManagerHandler, CheckAlertsCommand, ForecastingService, CancelVacationHandlerDispatch, PdfGeneratorService, CountApprovedDaysHandler.

## Hypothèse à valider en pilote (Lot 1)

L'attribut `#[AllowMockObjectsWithoutExpectations]` a été ajouté en sprint-004 (TEST-DEPRECATIONS-001) pour silence les notices PHPUnit 13. **Une fois les `createMock` qui n'avaient pas d'`expects()` convertis en `createStub`, l'attribut devient superflu** — y compris sur les Cas C qui utilisent `expects()` (ces tests n'ont jamais émis la deprecation visée par l'attribut).

Validation : retirer l'attribut sur 1 Cas A et 1 Cas C, run la suite. Si pas de notices, batch sweep tous les autres.

## Lien

Audit produit comme livrable de T-TM2-01 (sprint-006). Les lots 1-4 référenceront ce document en commit message.
