# US-088 — Snyk security upgrades (dependencies only)

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> Source : observation thibmonier 2026-05-07. Snyk remonte plusieurs alertes
> sécurité sur dépendances Composer et npm.

- **Implements** : FR-OPS-10 — **Persona** : équipe dev, P-OPS — **Estimate** : 3 pts — **MoSCoW** : Must

### Card
**As** responsable sécurité
**I want** prendre en compte les alertes Snyk via montées de version de packages
**So that** la posture sécurité soit à jour sans dette interne.

### Acceptance Criteria
```
Given dashboard Snyk avec N alertes Open
When story livrée
Then alertes corrigées via update package (pas de fix custom interne)
And alertes restantes = uniquement celles sans fix upstream disponible
```
```
Given alerte Snyk avec fix upstream disponible
When package upgrade testé
Then aucun test régression sur main
```

### Technical Notes
- ⚠️ **Contrainte explicite** : pas de développement spécifique pour palier les
  packages incriminés. Si pas de fix upstream → noter en risque accepté
  (commenté dans `.snyk` policy).
- Composer audit + Snyk PHP scan = source de vérité
- npm audit + Snyk Node.js scan = source de vérité
- Scope : packages prod uniquement (dev deps acceptables si bloque release)

### Tasks
- [ ] T-088-01 [OPS] Inventaire alertes Snyk Composer + npm (0,5 h)
- [ ] T-088-02 [OPS] Triage : fix upstream disponible vs accepté (1 h)
- [ ] T-088-03 [OPS] Bump packages avec fix upstream (composer + npm) (2 h)
- [ ] T-088-04 [TEST] Validation suite Unit + E2E post-bump (1 h)
- [ ] T-088-05 [DOC] `.snyk` policy : alertes acceptées + justification (0,5 h)

---

