# Module: External Integrations

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.13 (FR-INT-01..03). Generated 2026-05-04.

---

## US-072 — Intégration HubSpot

> INFERRED from `HubSpotSettings`, `Service/HubSpot/*`, `HubSpotSettingsController`.

- **Implements**: FR-INT-01 — **Persona**: P-005 — **Estimate**: 8 pts — **MoSCoW**: Should

### Card
**As** admin
**I want** intégrer HubSpot pour synchroniser leads, contacts, deals
**So that** mes données CRM externes alimentent HotOnes.

### Acceptance Criteria
```
When admin POST /admin/hubspot {api_key}
Then settings chiffrés stockés + test connexion
```
```
Given sync planifiée
When job s'exécute
Then leads/contacts/deals reflétés HotOnes (idempotent)
```
```
Given erreur API HubSpot
Then retry exponentiel via messenger; alerte admin si N échecs consécutifs
```

### Technical Notes
- Cf. US-013 (recouvrement: FR-CRM-04 et FR-INT-01 sont jumelles — fusionner ou différencier "settings" vs "sync runtime")
- ⚠️ Doublon potentiel à arbitrer

---

## US-073 — Intégration BoondManager

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

## US-074 — Stockage S3 pour fichiers

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

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-072 | HubSpot | FR-INT-01 | 8 | Should |
| US-073 | BoondManager | FR-INT-02 | 8 | Should |
| US-074 | Stockage S3 | FR-INT-03 | 3 | Must |
| **Total** | | | **19** | |
