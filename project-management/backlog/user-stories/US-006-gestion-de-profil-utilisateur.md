# US-006 — Gestion de profil utilisateur

> **BC**: IAM  |  **Source**: archived IAM.md (split 2026-05-11)

> INFERRED from `ProfileController`, `AvatarController`, `EmploymentPeriodController`.

- **Implements**: FR-IAM-06
- **Persona**: P-001..P-005
- **Estimate**: 3 pts
- **MoSCoW**: Must

### Card
**As** utilisateur authentifié
**I want** consulter et modifier mon profil (info, avatar, période d'emploi)
**So that** mes données restent à jour et reconnaissables.

### Acceptance Criteria
```
Given user authentifié
When GET /profile
Then voit ses données + formulaire de modification
```
```
When upload avatar PNG/JPG ≤ N MB
Then stocké S3, exposé via /avatars/{slug}
```
```
When upload fichier non-image ou >N MB
Then rejet avec message clair
```

### Technical Notes
- Stockage S3 via flysystem
- Image pipeline via liip/imagine
- API personnelle: `/api/profile/`

---

