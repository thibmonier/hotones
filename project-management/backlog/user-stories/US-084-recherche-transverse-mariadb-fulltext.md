# US-084 — Recherche transverse (MariaDB FULLTEXT)

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> INFERRED from `SearchController` + `GlobalSearchService`. Décision atelier 2026-05-15.

- **Implements**: FR-OPS-06 — **Persona**: tous authentifiés — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** utilisateur authentifié
**I want** chercher transversalement (clients, projets, contributeurs, factures, devis)
**So that** je retrouve vite l'objet utile.

### Acceptance Criteria
```
Given query length ≥ 2
When GET /search ou GET /api/search?q=...
Then résultats groupés par type {client, project, contributor, invoice, order}
And tenant-scoped via security context
And tri par pertinence (FULLTEXT MATCH ... AGAINST score)
```
```
Given query < 2 chars
Then 200 + résultats vides (UI message)
```
```
Given recherche partielle "soc" (préfixe)
Then matches MATCH ... AGAINST IN BOOLEAN MODE 'soc*'
```

### Technical Notes
- **Décision V1 (atelier 2026-05-15)**: MariaDB FULLTEXT (pas MeiliSearch/ES — pas de dette infra nouvelle).
- Migration Doctrine: `ALTER TABLE` pour ajouter index FULLTEXT sur:
  - `client.name`
  - `client_contact.first_name`, `last_name`, `email`
  - `project.name`, `project.description`
  - `contributor.first_name`, `last_name`, `email`
  - `invoice.reference`, `invoice.notes`
  - `order.reference`, `order.title`
- Repositories: `MATCH(col1, col2) AGAINST(:q IN BOOLEAN MODE)`.
- Limitation MariaDB: tokenisation française basique — surveiller pertinence avec accents.
- Cache Redis 5 min sur queries fréquentes.
- Tests: precision/recall sur jeu de test FR (accents, hyphens).

---

