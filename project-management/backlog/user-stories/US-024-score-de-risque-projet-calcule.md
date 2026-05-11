# US-024 — Score de risque projet (calculé)

> **BC**: PRJ  |  **Source**: archived PRJ.md (split 2026-05-11)

> INFERRED from `RiskController` + `ProjectRiskAnalyzer` + `AnalyzeProjectRisksMessage`.

- **Implements**: FR-PRJ-05
- **Persona**: P-002, P-003
- **Estimate**: 5 pts
- **MoSCoW**: Should

### Card
**As** chef de projet ou manager
**I want** un `riskLevel` calculé automatiquement par projet (critical / high / medium / low)
**So that** je priorise mes interventions sans saisie manuelle.

### Acceptance Criteria
```
Given projet actif
When ProjectRiskAnalyzer s'exécute (cron ou async via AnalyzeProjectRisksMessage)
Then riskLevel calculé à partir des 5 signaux:
  1. Budget consommé % (>80% = high, >100% = critical)
  2. Glissement planning (jours retard / jours initiaux > 20% = high, > 40% = critical)
  3. Marge projet % (<10% = high, <0 = critical)
  4. Score satisfaction contributeur (<5/10 = medium)
  5. Dépendances bloquées (≥1 dep critique = high)
And riskLevel = max() des niveaux des signaux atteints
```
```
When GET /risks/projects
Then liste atRiskProjects + stats {total, atRisk, critical, high, medium}
```
```
Given riskLevel passe à critical
Then notification + dispatch KPI_THRESHOLD_EXCEEDED
```

### Technical Notes
- Pas d'entité `Risk` dédiée: sortie = array `['analysis' => ['riskLevel' => ...]]` (computed).
- Seuils paramétrables côté `CompanySettings` (V2).
- Tests par signal + test combinatoire (max niveau).

---

