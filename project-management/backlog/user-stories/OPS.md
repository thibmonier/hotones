# Module: Operations & Platform

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.15 (FR-OPS-01..07). Generated 2026-05-04.

---

## US-079 — Endpoint health-check

> INFERRED from `HealthCheckController` + `/health` PUBLIC_ACCESS.

- **Implements**: FR-OPS-01 — **Persona**: système — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** plateforme d'orchestration (load-balancer, monitoring)
**I want** appeler `GET /health` pour vérifier vitalité (DB, Redis, app)
**So that** je gère le routing et l'alerting infra.

### Acceptance Criteria
```
When GET /health
Then 200 + JSON {status, db, redis, version}
```
```
Given dépendance KO
Then 503 + détails
```

---

## US-080 — Endpoint status public

> INFERRED from `StatusController`, `/status`.

- **Implements**: FR-OPS-02 — **Persona**: système, P-007 — **Estimate**: 2 pts — **MoSCoW**: Should

### Card
**As** visiteur ou client
**I want** consulter `/status`
**So that** je connais l'état du service en cas d'incident perçu.

### Acceptance Criteria
```
When GET /status
Then page publique listant services + uptime récent
```

---

## US-081 — Scheduler cron

> INFERRED from `Schedule.php`, `Scheduler/*`, `SchedulerEntry`, `SchedulerEntryCrudController`.

- **Implements**: FR-OPS-03 — **Persona**: P-006 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** superadmin
**I want** définir et superviser les jobs planifiés (sync HubSpot/Boond, recalcul KPI, rappels timesheet)
**So that** la plateforme tourne en autonomie.

### Acceptance Criteria
```
When admin POST /admin/scheduler-entry {expression, command}
Then SchedulerEntry persisté
And cron-expression validée
```
```
Given exécution périodique
Then trace + sortie + statut accessibles
```

### Technical Notes
- symfony/scheduler + dragonmantank/cron-expression

---

## US-082 — Messagerie asynchrone

> INFERRED from `messenger.yaml`, MessageHandlers x7.

- **Implements**: FR-OPS-04 — **Persona**: système — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** offload des opérations longues sur des workers (Redis transport)
**So that** les requêtes HTTP restent rapides et la résilience monte.

### Acceptance Criteria
```
Given handler async
When message dispatché
Then traité par worker, retry sur échec, queue failed pour replay
```
```
Given queue saturée
Then back-pressure mesurée (alertes)
```

---

## US-083 — Reporting CSP

> INFERRED from `CspReportController`, `/csp/report`.

- **Implements**: FR-OPS-05 — **Persona**: système — **Estimate**: 2 pts — **MoSCoW**: Should

### Card
**As** plateforme
**I want** collecter les violations Content-Security-Policy
**So that** je détecte les contenus tiers ou injections.

### Acceptance Criteria
```
Given navigateur détecte violation
When POST /csp/report
Then violation persistée (rate-limited)
And tableau de bord dev `/csp/violations` (en dev) accessible
```

---

## US-084 — Recherche transverse (MariaDB FULLTEXT)

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

## US-085 — Validation live de champs

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

## US-086 — Cascading dependent form fields

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
| **Total** | | | **26** | |
