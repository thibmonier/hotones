# SEC-MULTITENANT-003 — Tasks

> Tests régression cross-tenant (Alice tenant A vs Bob tenant B).
> 5 pts / 5 tasks / ~8-10h.

## Tasks

| ID | Type | Description | Estimate | Depends on | Status |
|----|------|-------------|---------:|------------|--------|
| T-SMT3-01 | [TEST] | Test isolation Client: Alice tenant A → 0 résultats sur clients tenant B | 1.5h | SEC-MULTITENANT-002 done | 🔲 |
| T-SMT3-02 | [TEST] | Test isolation Project + Order + Invoice (top 3 BCs) | 2.5h | T-SMT3-01 | 🔲 |
| T-SMT3-03 | [TEST] | Test isolation Timesheet + Vacation + Contributor (RH-side) | 2h | T-SMT3-01 | 🔲 |
| T-SMT3-04 | [TEST] | Test 404 anti-énumération : Alice tente GET projet-tenant-B/{id} → 404 (pas 403) | 1h | T-SMT3-02 | 🔲 |
| T-SMT3-05 | [TEST] | Test cas désactivation filter (superadmin reports) — `disableFilter()` documenté | 1h | T-SMT3-02 | 🔲 |

## Acceptance Criteria

- [ ] Test fixture : 2 tenants (Acme + Concurrent) avec données distinctes en base de test.
- [ ] Pour chaque entité testée :
  - User `alice@acme.test` (ROLE_USER) → liste ne contient que entités tenant Acme
  - User `bob@concurrent.test` (ROLE_USER) → liste ne contient que entités tenant Concurrent
  - 0 fuite cross-tenant
- [ ] Test 404 cross-tenant access par ID direct (anti-énumération)
- [ ] Test bypass : superadmin avec `disableFilter('tenant_filter')` voit les deux tenants (cas reports cross-tenant)
- [ ] Tests groupés `tests/Functional/MultiTenant/IsolationRegressionTest.php`
- [ ] CI exécute ces tests à chaque PR
- [ ] Documentation dans `docs/06-security/multi-tenant-isolation.md`

## Notes techniques

- Utiliser fixtures Foundry pour isoler les contextes tenant (extension `MultiTenantTestTrait` existant).
- Les tests doivent appeler les controllers (pas juste le repository) pour valider que `TenantMiddleware` + `TenantFilterSubscriber` activent bien le filter en runtime.
- Test bypass `disableFilter()` valide que la documentation pour les superadmins reports fonctionne.

## Test pattern recommandé

```php
public function testClientListIsolatedPerTenant(): void
{
    [$tenantA, $tenantB] = $this->createTwoTenants();

    ClientFactory::createOne(['tenantId' => $tenantA, 'name' => 'Acme Client']);
    ClientFactory::createOne(['tenantId' => $tenantB, 'name' => 'Concurrent Client']);

    $alice = $this->loginAsUserOf($tenantA);

    $this->client->request('GET', '/clients');

    self::assertResponseIsSuccessful();
    self::assertSelectorTextContains('table', 'Acme Client');
    self::assertSelectorTextNotContains('table', 'Concurrent Client');
}
```

## Risques

| Risque | Mitigation |
|--------|------------|
| Tests passent en local mais fail en CI (flakiness fixtures) | Utiliser `ResetDatabase` Foundry + uniqid dans tenant slugs |
| Performance impact des tests (2 tenants × N entités) | Limiter à 5 entités par test, pas tester chaque BC en regression |
| Faux positifs si `disableFilter()` mal géré | Test explicite du cas bypass avec assertion exacte |
