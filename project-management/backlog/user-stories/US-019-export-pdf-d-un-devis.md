# US-019 — Export PDF d'un devis

> **BC**: ORD  |  **Source**: archived ORD.md (split 2026-05-11)

> INFERRED from `dompdf/dompdf` dependency + `Service/Order/*`.

- **Implements**: FR-ORD-05
- **Persona**: P-002, P-005
- **Estimate**: 5 pts
- **MoSCoW**: Must

### Card
**As** chef de projet
**I want** générer un PDF du devis pour l'envoyer au client
**So that** je formalise l'offre commerciale.

### Acceptance Criteria
```
Given devis composé (sections + lignes)
When GET /orders/{id}/pdf
Then PDF retourné avec en-tête société, lignes, totaux, conditions, signature
```
```
Given PDF lourd (>50 pages)
When génération
Then offloaded en async (messenger), URL téléchargement notifiée
```

### Technical Notes
- Symfony AssetMapper / Webpack pour assets PDF
- Branding société depuis `CompanySettings`

---
