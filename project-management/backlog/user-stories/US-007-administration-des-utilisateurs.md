# US-007 — Administration des utilisateurs

> **BC**: IAM  |  **Source**: archived IAM.md (split 2026-05-11)

> INFERRED from `AdminUserController`.

- **Implements**: FR-IAM-07
- **Persona**: P-005, P-006
- **Estimate**: 5 pts
- **MoSCoW**: Must

### Card
**As** admin (tenant) ou superadmin
**I want** créer, modifier, désactiver des utilisateurs et leur attribuer des rôles
**So that** je gère les accès de ma société.

### Acceptance Criteria
```
Given admin authentifié
When POST /admin/users avec email + rôles
Then user créé dans la société de l'admin (multi-tenant)
And email d'invitation envoyé
```
```
Given admin tente d'attribuer ROLE_SUPERADMIN
Then refusé (uniquement superadmin peut)
```
```
When admin désactive un user
Then user ne peut plus se connecter; sessions invalidées
```

### Technical Notes
- ⚠️ Voter manquant pour autorisation fine (R-01)
- Audit log à vérifier (Blameable / Loggable)

---

