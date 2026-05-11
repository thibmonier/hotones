# US-031 — Prédiction charge (heuristique)

> **BC**: PLN  |  **Source**: archived PLN.md (split 2026-05-11)

> INFERRED from `WorkloadPredictionController` + `Service/Planning/{PlanningOptimizer,ProjectPlanningAssistant,TaceAnalyzer}`.

- **Implements**: FR-PLN-03 — **Persona**: P-002, P-003 — **Estimate**: 8 pts — **MoSCoW**: Should

### Card
**As** chef de projet ou manager
**I want** une prédiction de charge sur horizon 3 / 6 / 12 mois (filtrable par profil ou contributeur)
**So that** j'anticipe les sur/sous-charges et j'arbitre staffing.

### Acceptance Criteria
```
Given pipeline (devis + projets) + planning + capacité contributeur
When GET /staffing/prediction?months=3|6|12&profiles=[]&contributors=[]
Then analysePipeline() retourne {pipeline, workloadByMonth, alerts, totalPotentialDays}
And jours staffés pondérés par probabilité OrderStatus (cf US-062)
And alertes par contributeur:
  - warn: charge > 80% capacité hebdo
  - critical: charge > 100% capacité hebdo
```
```
Given absence de données
Then alerte vide + message UI explicite
```

### Technical Notes
- **Méthode V1 validée (atelier 2026-05-15)**: heuristique simple. Pas de ML.
- Formule: charge = somme (jours_staffés × proba_OrderStatus) / capacité_contributeur.
- Probabilités OrderStatus: cf US-062 (cohérence cross-features).
- Horizons proposés: 3, 6, 12 mois (whitelist).
- Cache Redis 15 min.

---

