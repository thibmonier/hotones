# US-011 — Gérer les contacts client

> **BC**: CRM  |  **Source**: archived CRM.md (split 2026-05-11)

> INFERRED from `ClientContact` + `ClientContactController`.

- **Implements**: FR-CRM-02
- **Persona**: P-002, P-003
- **Estimate**: 3 pts
- **MoSCoW**: Must

### Card
**As** chef de projet
**I want** rattacher des contacts (interlocuteurs) à un client
**So that** je sais qui appeler/écrire pour chaque dossier.

### Acceptance Criteria
```
Given client existant
When POST /clients/{id}/contacts {nom, email, téléphone, fonction}
Then contact créé et associé
```
```
When email invalide
Then 422 avec violations Symfony Validator
```

---

