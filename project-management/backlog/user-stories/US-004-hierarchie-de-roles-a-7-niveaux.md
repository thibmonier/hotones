# US-004 — Hiérarchie de rôles à 7 niveaux

> **BC**: IAM  |  **Source**: archived IAM.md (split 2026-05-11)

> INFERRED from `security.yaml` role_hierarchy.

- **Implements**: FR-IAM-04
- **Source**: `config/packages/security.yaml`
- **Persona**: tous
- **Estimate**: 2 pts (couvert; surface = vérification)
- **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** propager les rôles via une hiérarchie SUPERADMIN → ADMIN → MANAGER → CHEF_PROJET → INTERVENANT → USER (avec branche COMPTA)
**So that** chaque permission est héritée sans duplication.

### Acceptance Criteria
```
Given user with ROLE_MANAGER
Then user has ROLE_CHEF_PROJET, ROLE_INTERVENANT, ROLE_USER
```
```
Given user with ROLE_COMPTA
Then user has ROLE_MANAGER and below (héritage simplifié documenté en yaml)
```
```
Given ROLE_COMMERCIAL referenced in code
Then ⚠️ vérifier: rôle absent de role_hierarchy → DECISION REQUIRED (R-02)
```

### Technical Notes
- ⚠️ R-02: `ROLE_COMMERCIAL` orphelin
- Couverture voters faible (R-01) → s'appuie sur access_control + IsGranted

---

