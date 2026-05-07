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
| US-087 | CI green (GitHub Actions) | FR-OPS-09 (new) | 5 | Must |
| US-088 | Snyk security upgrades | FR-OPS-10 (new) | 3 | Must |
| US-089 | Composer + npm update routine | FR-OPS-11 (new) | 2 | Should |
| US-090 | Render deploy fix | FR-OPS-12 (new) | 3 | Must |
| **Total** | | | **39** | |

---

## US-087 — CI green (GitHub Actions)

> Source : observation thibmonier 2026-05-07. Beaucoup de jobs CI échouent
> sur PRs (PHPStan, PHPUnit, E2E Panther, Mago, PHP_CodeSniffer, Snyk).

- **Implements** : FR-OPS-09 — **Persona** : équipe dev, P-OPS — **Estimate** : 5 pts — **MoSCoW** : Must

### Card
**As** développeur
**I want** que tous les jobs GitHub Actions passent vert sur main + PRs
**So that** la CI redevienne un signal fiable de santé du code (et débloque les merges).

### Acceptance Criteria
```
Given une PR contre main
When tous les workflows tournent
Then 0 job FAILURE (hors snyk advisory autorisée)
```
```
Given main HEAD
When tous les workflows tournent
Then conclusion = SUCCESS
```

### Technical Notes
- Audit jobs failing : PHPStan, PHPUnit, E2E Panther, Mago, PHP_CodeSniffer
- Triage par job : bloquant vs non-bloquant
- Pour chaque bloquant : fix code OU adjust workflow (skip-pre-push group, allowed failures)
- Convention : tout nouveau workflow doit passer green sur sa propre PR d'introduction

### Tasks
- [ ] T-087-01 [OPS] Audit complet jobs failing main + PRs récentes (1 h)
- [ ] T-087-02 [TEST] Fix PHPStan errors prioritaires (2 h)
- [ ] T-087-03 [TEST] Fix PHPUnit failures résiduels (2 h)
- [ ] T-087-04 [OPS] Fix E2E Panther (env Docker, drivers) (2 h)
- [ ] T-087-05 [OPS] Fix Mago + PHP_CodeSniffer (config OU dispense) (1 h)
- [ ] T-087-06 [DOC] CONTRIBUTING.md : section « jobs CI obligatoires » (0,5 h)

---

## US-088 — Snyk security upgrades (dependencies only)

> Source : observation thibmonier 2026-05-07. Snyk remonte plusieurs alertes
> sécurité sur dépendances Composer et npm.

- **Implements** : FR-OPS-10 — **Persona** : équipe dev, P-OPS — **Estimate** : 3 pts — **MoSCoW** : Must

### Card
**As** responsable sécurité
**I want** prendre en compte les alertes Snyk via montées de version de packages
**So that** la posture sécurité soit à jour sans dette interne.

### Acceptance Criteria
```
Given dashboard Snyk avec N alertes Open
When story livrée
Then alertes corrigées via update package (pas de fix custom interne)
And alertes restantes = uniquement celles sans fix upstream disponible
```
```
Given alerte Snyk avec fix upstream disponible
When package upgrade testé
Then aucun test régression sur main
```

### Technical Notes
- ⚠️ **Contrainte explicite** : pas de développement spécifique pour palier les
  packages incriminés. Si pas de fix upstream → noter en risque accepté
  (commenté dans `.snyk` policy).
- Composer audit + Snyk PHP scan = source de vérité
- npm audit + Snyk Node.js scan = source de vérité
- Scope : packages prod uniquement (dev deps acceptables si bloque release)

### Tasks
- [ ] T-088-01 [OPS] Inventaire alertes Snyk Composer + npm (0,5 h)
- [ ] T-088-02 [OPS] Triage : fix upstream disponible vs accepté (1 h)
- [ ] T-088-03 [OPS] Bump packages avec fix upstream (composer + npm) (2 h)
- [ ] T-088-04 [TEST] Validation suite Unit + E2E post-bump (1 h)
- [ ] T-088-05 [DOC] `.snyk` policy : alertes acceptées + justification (0,5 h)

---

## US-089 — Composer + npm update routine (Symfony fresh)

> Source : observation thibmonier 2026-05-07. Pas de routine de mise à jour
> régulière. Symfony et autres deps en retard sur upstream.

- **Implements** : FR-OPS-11 — **Persona** : équipe dev, P-OPS — **Estimate** : 2 pts — **MoSCoW** : Should

### Card
**As** développeur
**I want** une routine `composer update` + `composer bump` + `npm update` programmée
**So that** les dépendances Symfony / npm restent à jour sans dérive lourde.

### Acceptance Criteria
```
Given politique de mise à jour mensuelle définie
When composer update + composer bump + npm update exécutés
Then composer.lock + package-lock.json mis à jour
And tests Unit + E2E passent
And PR ouverte automatiquement (Dependabot OU script manuel)
```
```
Given Symfony LTS (currently 7.x → 8.0)
When upgrade major
Then ADR créé pour breaking changes connus
And migration guide consulté
```

### Technical Notes
- Évaluer Dependabot config (déjà actif ? GH Settings)
- Sinon script `bin/console app:deps-update` (composer outdated --strict)
- Cadence : mensuelle (1er lundi du mois)
- composer bump : prefer si après update tests verts
- npm : `npm update` + `npm audit fix` (sans --force)

### Tasks
- [ ] T-089-01 [OPS] Activer / configurer Dependabot (composer + npm) (1 h)
- [ ] T-089-02 [OPS] Workflow GH Action mensuel `deps-update.yml` si Dependabot insuffisant (1 h)
- [ ] T-089-03 [DOC] CONTRIBUTING.md : section « cadence updates dépendances » (0,5 h)

---

## US-090 — Render deploy fix

> Source : observation thibmonier 2026-05-07. Déploiement Render KO.

- **Implements** : FR-OPS-12 — **Persona** : équipe dev, P-OPS — **Estimate** : 3 pts — **MoSCoW** : Must

### Card
**As** équipe dev
**I want** que le déploiement sur Render fonctionne à chaque push main
**So that** la prod (ou staging) reflète le code mergé.

### Acceptance Criteria
```
Given un push sur main
When Render déclenche build + deploy
Then déploiement réussit en < 10 min
And app répond 200 sur GET /health
```
```
Given erreur de build Render
When logs consultés
Then cause root identifiée et fixée
```

### Technical Notes
- Logs Render à analyser : build, runtime, healthcheck
- Causes courantes : env vars manquantes, build command obsolète, PHP version mismatch
- À synchroniser avec config Symfony 8 + PHP 8.5 récents
- Vérifier Dockerfile.prod si utilisé OU buildpack Render PHP
- DB connection : MariaDB managed sur Render OU externe — config DATABASE_URL

### Tasks
- [ ] T-090-01 [OPS] Analyser logs Render derniers échecs (1 h)
- [ ] T-090-02 [OPS] Identifier cause racine (env, PHP version, build cmd) (1 h)
- [ ] T-090-03 [OPS] Fix (config Render + Dockerfile / buildpack si nécessaire) (2 h)
- [ ] T-090-04 [TEST] Smoke test post-deploy (curl /health + login) (0,5 h)
- [ ] T-090-05 [DOC] Runbook déploiement Render (`docs/05-deployment/render.md`) (1 h)
