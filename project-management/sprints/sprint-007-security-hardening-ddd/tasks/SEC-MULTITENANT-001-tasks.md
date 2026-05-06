# SEC-MULTITENANT-001 — Tasks

> Implémenter `TenantContext` + `TenantFilter` Doctrine SQLFilter + `TenantAwareTrait`.
> 8 pts / 7 tasks / ~12-15h.

## Tasks

| ID | Type | Description | Estimate | Depends on | Status |
|----|------|-------------|---------:|------------|--------|
| T-SMT1-01 | [BE] | Implémenter `App\Shared\Infrastructure\Multitenant\TenantContext` (set/get/clear current tenant) | 1h | — | 🔲 |
| T-SMT1-02 | [BE] | Implémenter `App\Shared\Infrastructure\Doctrine\TenantAwareTrait` avec champ `tenantId: string` (uuid) + getters | 1h | T-SMT1-01 | 🔲 |
| T-SMT1-03 | [BE] | Implémenter `App\Shared\Infrastructure\Doctrine\Filter\TenantFilter extends SQLFilter` (lookup `TenantAware` via `class_uses`) | 2-3h | T-SMT1-02 | 🔲 |
| T-SMT1-04 | [INFRA] | Configurer `doctrine.yaml` pour enregistrer le filter `tenant_filter` | 0.5h | T-SMT1-03 | 🔲 |
| T-SMT1-05 | [BE] | Implémenter `App\Shared\Infrastructure\Http\Middleware\TenantMiddleware` (extrait tenant depuis JWT/security token, set sur context) | 2h | T-SMT1-01 | 🔲 |
| T-SMT1-06 | [BE] | Implémenter `App\Shared\Infrastructure\Doctrine\Listener\TenantFilterSubscriber` (active filter à `kernel.request`) | 1.5h | T-SMT1-03, T-SMT1-05 | 🔲 |
| T-SMT1-07 | [TEST] | Tests unitaires TenantContext + TenantFilter (filter dialect SQL généré) | 2-3h | T-SMT1-06 | 🔲 |

## Acceptance Criteria

- [ ] `TenantContext::setCurrentTenant(TenantId $id)` + `getCurrentTenant()` + `clear()` + `hasTenant()` + `NoTenantContextException` si pas set.
- [ ] `TenantAwareTrait` ajoutable à n'importe quelle entité (sera consommé en SEC-MULTITENANT-002).
- [ ] `TenantFilter::addFilterConstraint()` retourne `'' (empty)` si entité ne `use TenantAwareTrait`, sinon `t.tenant_id = :tenantId`.
- [ ] `doctrine.yaml` enregistre filter avec `enabled: false` par défaut (activé runtime).
- [ ] `TenantMiddleware` lit `tenant_id` claim du JWT (Lexik) ou route param fallback (admin).
- [ ] `TenantFilterSubscriber` active filter via `$em->getFilters()->enable('tenant_filter')` + `setParameter('tenantId', $context->getCurrentTenant()->getValue())`.
- [ ] Tests : extraction JWT, lookup entity supports/skips, dialect SQL correct.
- [ ] PHPStan max OK.
- [ ] Couvre `.claude/rules/14-multitenant.md` patterns.

## Notes techniques

- Le filter utilise `getParameter('tenantId')` qui DOIT être quoté SQL (utilise `$this->getParameter()` standard Doctrine).
- Compatible avec entités legacy (`src/Entity/*` avec `company_id` direct) ET futures DDD entities.
- Listener doit être défini avec `kernel.priority` plus bas que la normale pour s'exécuter après l'authentification.

## Risques

| Risque | Mitigation |
|--------|------------|
| Performance impact sur queries (filter ajoute WHERE partout) | Bench Blackfire avant/après ; index `tenant_id` doit exister sur 50+ tables |
| Conflit avec queries qui doivent traverser tenants (ex superadmin reports) | `disableFilter()` explicite documenté pour ces cas spécifiques |
| Filter cassé si tenant_id pas peuplé sur certaines entités legacy | Audit obligatoire en SEC-MULTITENANT-002 avant déploiement |
