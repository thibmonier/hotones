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

## US-084 — Recherche transverse

> INFERRED from `SearchController`, route `/api/search`.

- **Implements**: FR-OPS-06 — **Persona**: tous authentifiés — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** utilisateur
**I want** chercher transversalement (clients, projets, contributeurs, factures)
**So that** je retrouve vite l'objet utile.

### Acceptance Criteria
```
When GET /api/search?q=...
Then résultats agrégés multi-types tenant-scoped
```
```
Given query <2 chars
Then refusé (perf)
```

### Technical Notes
- Backend de recherche à expliciter (BDD LIKE, MeiliSearch, ElasticSearch?)

---

## US-085 — Endpoint validation

> INFERRED from `ValidationController`, `/api/validate`.

- **Implements**: FR-OPS-07 — **Persona**: tous authentifiés — **Estimate**: 2 pts — **MoSCoW**: Could

### Card
**As** front-end (Stimulus / Live Components)
**I want** valider en temps réel un payload (formulaire dépendant)
**So that** UX fluide sans soumission complète.

### Acceptance Criteria
```
When POST /api/validate {entity, data}
Then 200 si OK ou 422 + violations Symfony Validator
```

### Technical Notes
- Cf. `DependentFieldsController` (live form components)

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-079 | Health-check | FR-OPS-01 | 2 | Must |
| US-080 | Status public | FR-OPS-02 | 2 | Should |
| US-081 | Scheduler cron | FR-OPS-03 | 5 | Must |
| US-082 | Messagerie async | FR-OPS-04 | 5 | Must |
| US-083 | Reporting CSP | FR-OPS-05 | 2 | Should |
| US-084 | Recherche transverse | FR-OPS-06 | 5 | Should |
| US-085 | Endpoint validation | FR-OPS-07 | 2 | Could |
| **Total** | | | **23** | |
