# US-087 — CI green (GitHub Actions)

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> Source : observation thibmonier 2026-05-07. Beaucoup de jobs CI échouent
> sur PRs (PHPStan, PHPUnit, E2E Panther, Mago, PHP_CodeSniffer, Snyk).

- **Implements** : FR-OPS-09 — **Persona** : équipe dev, P-OPS — **Estimate** : 5 pts — **MoSCoW** : Must

### Card
**As** développeur
**I want** que tous les jobs GitHub Actions passent vert sur main + PRs
**So that** la CI redevienne un signal fiable de santé du code (et débloque les merges).

### Acceptance Criteria
```
Given une PR contre main
When tous les workflows tournent
Then 0 job FAILURE (hors snyk advisory autorisée)
```
```
Given main HEAD
When tous les workflows tournent
Then conclusion = SUCCESS
```

### Technical Notes
- Audit jobs failing : PHPStan, PHPUnit, E2E Panther, Mago, PHP_CodeSniffer
- Triage par job : bloquant vs non-bloquant
- Pour chaque bloquant : fix code OU adjust workflow (skip-pre-push group, allowed failures)
- Convention : tout nouveau workflow doit passer green sur sa propre PR d'introduction

### Tasks
- [ ] T-087-01 [OPS] Audit complet jobs failing main + PRs récentes (1 h)
- [ ] T-087-02 [TEST] Fix PHPStan errors prioritaires (2 h)
- [ ] T-087-03 [TEST] Fix PHPUnit failures résiduels (2 h)
- [ ] T-087-04 [OPS] Fix E2E Panther (env Docker, drivers) (2 h)
- [ ] T-087-05 [OPS] Fix Mago + PHP_CodeSniffer (config OU dispense) (1 h)
- [ ] T-087-06 [DOC] CONTRIBUTING.md : section « jobs CI obligatoires » (0,5 h)

---

