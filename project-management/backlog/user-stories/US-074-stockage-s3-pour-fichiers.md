# US-074 — Stockage S3 pour fichiers

> **BC**: INT  |  **Source**: archived INT.md (split 2026-05-11)

> INFERRED from `oneup/flysystem-bundle` + `flysystem-aws-s3-v3` + `AvatarController`.

- **Implements**: FR-INT-03 — **Persona**: tous authentifiés — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** stocker fichiers utilisateurs (avatars, PJ notes de frais, PDF) sur S3
**So that** la couche stockage scale et est externalisée.

### Acceptance Criteria
```
When upload de fichier
Then envoyé sur bucket S3 (FRA si compliance)
And URL signée à durée limitée pour téléchargement
```
```
Given suppression entité parente
Then fichier S3 supprimé (consistency)
```

### Technical Notes
- Region S3 / chiffrement at-rest à confirmer
- Coût et lifecycle policy à définir

---
