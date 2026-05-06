# TEST-COVERAGE-001 — Audit (T-TC1-01)

> Inventaire des classes sous-couvertes pour orienter les lots de tests T-TC1-02 et T-TC1-03.
>
> **Source**: `composer test-coverage-text` (PCOV) sur main, 2026-05-05.

## Coverage global actuel

| Métrique | Couvert | Total | % |
|----------|--------:|------:|--:|
| Classes | 57 | 436 | **13.07%** |
| Methods | 955 | 4077 | **23.42%** |
| **Lines** | **7 123** | **31 992** | **22.26%** |

**Cible sprint-006**: ≥ 45% (sprint-007 = 50%, sprint-008+ = 60%).

**Gap**: +22.74 points → ~7 300 lignes à couvrir pour atteindre 45%.

## Réalité de la cible 45%

Trop ambitieux pour 3 pts (~5h) en l'état. Le coverage 22% reflète :
- 379 classes sans aucun test (87% du repo).
- Code legacy massif sous `src/Controller/**` (80 controllers, peu de couverture fonctionnelle directe — la couverture vient des tests fonctionnels qui touchent les services indirectement).
- Présentation `src/Presentation/**`, `src/EventListener/**`, `src/Twig/**` largement non couverts.

**Ajustement réaliste sprint-006** : viser **27-30%** (gain +5/+8 points = ~1 600 à ~2 500 lignes). Cible 45% reste valide en projection, mais sur **5-7 sprints** au rythme +5 points/sprint.

## Top 15 services sous-couverts (priorité ROI)

Critères de priorisation :
1. **Lignes restantes à couvrir** = `lines_total - lines_covered` (impact max).
2. **Complexité métier** = service stratégique (analytics, forecasting, risk) > utility.
3. **Effort estimé** = nombre de méthodes non couvertes × complexité moyenne.

| # | Classe | Cov. lignes | Cov. méthodes | Lines restantes | Priorité | Lot proposé |
|---|--------|------------:|---------------:|----------------:|----------|-------------|
| 1 | `Service/ProjectRiskAnalyzer` | 44.88% (149/332) | 23.53% (4/17) | **183** | 🔴 Must | Lot 1 — formule scoring 5 signaux + multi-projets |
| 2 | `Service/HubSpot/HubSpotClient` | 36.09% (96/266) | 38.46% (5/13) | **170** | 🟠 Should | Lot 2 — sync edge cases (retry, error, mapping) |
| 3 | `Service/WorkloadPredictionService` | 36.48% (89/244) | 8.33% (1/12) | **155** | 🔴 Must | Lot 1 — heuristique somme jours × proba |
| 4 | `Service/ForecastingService` | 50.00% (116/232) | 37.50% (6/16) | **116** | 🔴 Must | Lot 1 — MA6 × pondération devis (cf US-062) |
| 5 | `Service/Timesheet/TimesheetExportService` | **0.88% (1/114)** | 25.00% (1/4) | **113** | 🟠 Should | Lot 2 — export CSV/Excel par mois |
| 6 | `Service/ProfitabilityService` | 69.28% (230/332) | 70.00% (14/20) | **102** | 🟡 Could | sprint-007 |
| 7 | `Service/BoondManager/BoondManagerClient` | 68.75% (88/128) | 45.45% (5/11) | **40** | 🟡 Could | Lot 2 si reste budget |
| 8 | `Service/SecureFileUploadService` | 60.19% (62/103) | 40.00% (4/10) | **41** | 🟡 Could | sprint-007 (lié sécurité) |
| 9 | `Service/MetricsCalculationService` | 78.80% (145/184) | 50.00% (5/10) | **39** | 🟡 Could | sprint-007 |
| 10 | `Service/ProfitabilityPredictor` | 76.41% (149/195) | 60.00% (3/5) | **46** | 🟡 Could | sprint-007 |
| 11 | `Twig/CronExtension` | 68.24% (101/148) | 37.50% (3/8) | **47** | 🟢 Won't | low priority — extension Twig |
| 12 | `Service/PerformanceReviewService` | 88.89% (96/108) | 71.43% (10/14) | 12 | ✅ déjà bon | — |
| 13 | `Service/OnboardingService` | 94.06% (95/101) | 80.00% (12/15) | 6 | ✅ déjà bon | — |
| 14 | `Twig/OrderExtension` | 31.82% (7/22) | 40.00% (2/5) | 15 | 🟢 Won't | low ROI |
| 15 | `Twig/FileStorageExtension` | 38.46% (5/13) | 50.00% (2/4) | 8 | 🟢 Won't | low ROI |

**Total opportunité top 5 (Must) = ~727 lignes** = +2.27% sur global = passage de 22.26% à ~24.5%.
**Top 5 + lot 2 = ~1 050 lignes** = +3.3% = ~25.6%.

## Lots proposés

### Lot 1 (T-TC1-02) — Forecasting + Risk + Workload — ~4h

Priorité Must — services métier critiques (rentabilité). Tests unitaires pour les formules.

| Classe | Lines à couvrir | Cas tests recommandés |
|--------|----------------:|----------------------|
| `ProjectRiskAnalyzer` | ~183 | 5 signaux (budget, glissement, marge, satisfaction, deps) × seuils warn/critical, combinatoire `riskLevel = max()` |
| `WorkloadPredictionService` | ~155 | `analyzePipeline()` — pondération `OrderStatus`, alertes 80%/100%, horizons 3/6/12 mois |
| `ForecastingService` | ~116 | MA6 × pondération devis, scénarios realistic/optimistic/pessimistic, accuracy tracking |

**Gain estimé**: +454 lignes = +1.4% global.

### Lot 2 (T-TC1-03) — TimesheetExport + HubSpot/BoondManager — ~3h

Priorité Should — modules périphériques mais ROI élevé (TimesheetExport quasi non testé).

| Classe | Lines à couvrir | Cas tests recommandés |
|--------|----------------:|----------------------|
| `TimesheetExportService` | ~113 | export CSV/Excel par mois, agrégation par contributeur/projet, edge cases mois vide |
| `HubSpot/HubSpotClient` | ~170 | retry exponentiel, error mapping, idempotency tokens, rate limit |
| `BoondManager/BoondManagerClient` | ~40 | (si budget) sync staffing edge cases |

**Gain estimé**: +323 lignes = +1.0% global.

### Cumul lots T-TC1-02 + T-TC1-03

| Métrique | Avant | Après | Δ |
|----------|------:|------:|--:|
| Lines couvertes | 7 123 | 7 900 | +777 |
| **Coverage global** | **22.26%** | **24.7%** | **+2.4%** |

**Gap restant vers 45%**: 20.3 points. Réaliste en sprint-007/008/009 si rythme de +5 points/sprint maintenu.

## Recommandation cible révisée sprint-006

**Cible sprint-006**: passer de 22.26% à **24.5-25%** (+2.3 à +2.7 points). Réaliste pour 3 pts story.
**Cible sprint-007**: 30% (+5 points).
**Cible sprint-008**: 35% (+5 points).
**Cible sprint-009**: 40% (+5 points).
**Cible sprint-010**: 45% (+5 points) — original sprint-006 cible atteinte avec 4 sprints de retard.

À discuter en sprint review.

## Anti-cibles

Classes à **ne pas** prioriser (low ROI ou changement futur prévu) :

- `src/Controller/**` (80 fichiers) — tester en fonctionnel pas en unit.
- `src/Entity/**` (63 entités) — getters/setters Doctrine, peu de logique.
- `src/Twig/**` extensions — coverage gain faible, snapshot tests possibles mais marginal.
- Classes **scaffolding DDD** (`Domain/Vacation/**`) — déjà couvertes 100% via tests Application/Integration.

## Suite

- T-TC1-02 — Lot 1 (Forecasting + Risk + Workload).
- T-TC1-03 — Lot 2 (TimesheetExport + HubSpot).
- T-TC1-04 — Doc `tests.md` cible progressive (45% → revisé en escalier 25/30/35/40/45 sur 5 sprints).
