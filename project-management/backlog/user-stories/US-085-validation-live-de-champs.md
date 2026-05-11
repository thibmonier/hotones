# US-085 — Validation live de champs

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> INFERRED from `ValidationController` `/api/validate`. Décision atelier 2026-05-15: scope distinct du cascading (cf US-086).

- **Implements**: FR-OPS-07 — **Persona**: tous authentifiés — **Estimate**: 2 pts — **MoSCoW**: Could

### Card
**As** front-end (Stimulus / Live Components)
**I want** valider en temps réel un champ unique (unicité, format, regex métier)
**So that** UX fluide sans soumission complète, feedback immédiat.

### Acceptance Criteria
```
When POST /api/validate {type, value, field, exclude_id?}
Then 200 + {valid: bool, message?: string}
```
```
Types supportés (V1):
  - client_name_unique (vérif unicité scope tenant)
  - email (RFC + DNS optional)
  - siret (algorithme Luhn)
  - phone (E.164)
  - url (RFC)
```
```
Given type inconnu
Then 400 + "Type de validation inconnu"
```

### Technical Notes
- Limité à validations atomiques. Pour cascading selects (client → projects → tasks), cf US-086.
- Pas de side-effect persistance.

---

