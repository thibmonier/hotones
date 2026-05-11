# US-062 — Forecasting CA (moyenne mobile pondérée)

> **BC**: AN  |  **Source**: archived AN.md (split 2026-05-11)

> INFERRED from `Controller/Analytics/*`, `ForecastingController`, `PredictionsController`, `FactForecast` + `GenerateForecastsMessage`.

- **Implements**: FR-AN-03 — **Persona**: P-003, P-005 — **Estimate**: 8 pts — **MoSCoW**: Should

### Card
**As** manager / admin
**I want** des prévisions CA sur 3/6/12 mois avec 3 scénarios (réaliste, optimiste, pessimiste) et intervalles de confiance
**So that** je planifie embauches, achats SaaS, trésorerie.

### Acceptance Criteria
```
Given historique CA réel ≥ 6 mois + pipeline devis avec OrderStatus
When job GenerateForecastsMessage s'exécute (cron)
Then FactForecast persisté pour chaque période × scénario × signal
And formule:
  forecast(mois N) = MA6(CA réel) × ajustement_pipeline(mois N)
  où ajustement_pipeline(mois N) = Σ(montant_devis × proba_OrderStatus) sur fenêtre [N-1, N+2]
And confidenceMin/Max calculés par écart-type sur 6 mois
```
```
Probabilités OrderStatus (V1, validées atelier 2026-05-15):
  - PENDING (a_signer): défini par le commercial du compte (champ dédié sur le devis, défaut 50%)
  - WON: 100%
  - SIGNED: 100%
  - LOST: 0%
  - STANDBY: 0%
  - ABANDONED: 0%
  - COMPLETED: 100% (déjà facturé)
```
```
When GET /analytics/forecasting?months=3|6|12
Then 3 séries chartées (realistic / optimistic / pessimistic) + intervalle confiance
And accuracy moyenne sur 6 derniers mois affichée (calculateAverageAccuracy('realistic', 6))
```
```
Given < 6 mois d'historique
Then message UI "données insuffisantes" et seul le scénario réaliste affiché
```

### Technical Notes
- **Méthode V1 validée**: moyenne mobile 6 mois × pondération devis par `OrderStatus`.
- Probabilité PENDING configurable par devis: ajouter champ `Order.winProbability` (int 0-100, défaut 50, modifiable par CP / commercial).
- 3 scénarios = facteurs ±X% sur ajustement_pipeline (à calibrer atelier).
- Cron via `Schedule.php` + `GenerateForecastsMessage`.
- Tests: backtest sur N mois historiques, mesure accuracy.

---

