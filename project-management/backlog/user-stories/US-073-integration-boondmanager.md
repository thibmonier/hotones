# US-073 — Intégration BoondManager

> **BC**: INT  |  **Source**: archived INT.md (split 2026-05-11)

> INFERRED from `BoondManagerSettings`, `Service/BoondManager/*`, `BoondManagerSettingsController`.

- **Implements**: FR-INT-02 — **Persona**: P-005 — **Estimate**: 8 pts — **MoSCoW**: Should

### Card
**As** admin
**I want** synchroniser staffing/projets avec BoondManager
**So that** HotOnes complète l'outil de staffing.

### Acceptance Criteria
```
Given API credentials BoondManager
When sync planifiée
Then projets/contributeurs/staffing alignés
```
```
Given conflit (modif des deux côtés)
Then politique de résolution explicite (last-write-wins ou règle métier)
```

---

