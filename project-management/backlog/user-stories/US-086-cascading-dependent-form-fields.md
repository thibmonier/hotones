# US-086 — Cascading dependent form fields

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> INFERRED from `Controller/Api/DependentFieldsController`. Nouveau périmètre identifié atelier 2026-05-15.

- **Implements**: FR-OPS-08 (nouveau) — **Persona**: tous authentifiés — **Estimate**: 3 pts — **MoSCoW**: Should

### Card
**As** front-end (formulaire avec dépendances Client → Projects → Tasks → SubTasks)
**I want** charger dynamiquement les options du select N+1 dès qu'on choisit le select N
**So that** UX cohérente sans pré-charger toutes les combinaisons.

### Acceptance Criteria
```
Given client sélectionné
When GET /api/clients/{id}/projects
Then liste projets actifs du client (tenant-scoped)
```
```
Given projet sélectionné
When GET /api/projects/{id}/tasks
Then liste tasks active=true triées par position
```
```
Given task sélectionnée
When GET /api/tasks/{id}/subtasks
Then liste sub-tasks
```
```
Given client/project/task d'un autre tenant
Then 404 (anti-énumération multi-tenant)
```

### Technical Notes
- Endpoints actuellement sans IsGranted entité (R-01 voters). À sécuriser quand voters généralisés.
- ⚠️ Filtrage tenant repose sur repository — vérifier après US-005 (TenantFilter SQLFilter).

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-079 | Health-check | FR-OPS-01 | 2 | Must |
| US-080 | Status public | FR-OPS-02 | 2 | Should |
| US-081 | Scheduler cron | FR-OPS-03 | 5 | Must |
| US-082 | Messagerie async | FR-OPS-04 | 5 | Must |
| US-083 | Reporting CSP | FR-OPS-05 | 2 | Should |
| US-084 | Recherche transverse FULLTEXT | FR-OPS-06 | 5 | Should |
| US-085 | Validation live champs | FR-OPS-07 | 2 | Could |
| US-086 | Cascading form fields | FR-OPS-08 (new) | 3 | Should |
| US-087 | CI green (GitHub Actions) | FR-OPS-09 (new) | 5 | Must |
| US-088 | Snyk security upgrades | FR-OPS-10 (new) | 3 | Must |
| US-089 | Composer + npm update routine | FR-OPS-11 (new) | 2 | Should |
| US-090 | Render deploy fix | FR-OPS-12 (new) | 3 | Must |
| **Total** | | | **39** | |

---

