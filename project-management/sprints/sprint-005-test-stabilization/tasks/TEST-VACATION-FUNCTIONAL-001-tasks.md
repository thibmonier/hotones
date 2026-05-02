# Tâches — TEST-VACATION-FUNCTIONAL-001

## Informations

- **Story Points** : 5
- **MoSCoW** : Must
- **Origine** : retro sprint-004 action #2 + sprint-004 review candidate
- **Total estimé** : 12h

## Résumé

Les 11 tests fonctionnels Vacation (`tests/Functional/Controller/Vacation/` + `tests/Functional/Vacation/`) sont cassés depuis la migration DDD sprint-003. Sprint-004 / REFACTOR-001 a fixé une partie (route loader manquant) ; reste le défaut session/CSRF : `CsrfTokenManager::getToken()` est appelé avant `$client->request()`, donc avant qu'une session n'existe.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-TVF-01 | [TEST] | Audit + classification des 11 failures (root cause par test) | 1h | - | 🔲 |
| T-TVF-02 | [TEST] | `VacationFunctionalTrait::generateCsrfToken` warmup session via initial GET | 2h | T-TVF-01 | 🔲 |
| T-TVF-03 | [TEST] | Helper `extractCsrfTokenFromForm($crawler, $name)` pour cas form-only | 2h | T-TVF-01 | 🔲 |
| T-TVF-04 | [TEST] | Migrer 11 tests vers nouveaux helpers + asserts | 3h | T-TVF-02, T-TVF-03 | 🔲 |
| T-TVF-05 | [TEST] | `RouteCountSmokeTest` : asserter ≥4 routes `mes-conges` + ≥6 routes `manager/conges` | 2h | - | 🔲 |
| T-TVF-06 | [DOC] | Section "Tests fonctionnels" dans `CONTRIBUTING.md` (warmup session pattern) | 1h | T-TVF-04 | 🔲 |
| T-TVF-07 | [REV] | Code review | 1h | T-TVF-06 | 🔲 |

## Détail des tâches

### T-TVF-01 — Audit des 11 failures

Lancer `phpunit tests/Functional/Controller/Vacation/ tests/Functional/Vacation/ --testdox`, classer chaque ✘ par cause :
- (a) session/CSRF (génération avant 1er request)
- (b) firewall sans cookie de session
- (c) collision email `test@test.com` réutilisé
- (d) autre

Documenter le tableau dans le commit message de T-TVF-04.

### T-TVF-02 — Warmup session

Étendre `VacationFunctionalTrait::generateCsrfToken` : si aucune `$client->request()` n'a encore été lancée, faire un GET no-op (`/mes-conges`) pour bootstrap la session, **puis** appeler `CsrfTokenManager::getToken()`. Vérifié en setUp.

```php
protected function generateCsrfToken(string $id): string
{
    if ($this->client !== null && !$this->hasIssuedRequest()) {
        $this->client->request('GET', '/mes-conges');
    }
    return static::getContainer()->get('security.csrf.token_manager')->getToken($id)->getValue();
}
```

### T-TVF-03 — extractCsrfTokenFromForm

Pour les tests qui submittent un formulaire, extraire le token directement du DOM rendu — plus robuste que de re-générer.

```php
protected function extractCsrfTokenFromForm(Crawler $crawler, string $formName): string
{
    return $crawler->filter("input[name='{$formName}[_token]']")->attr('value');
}
```

### T-TVF-04 — Migration des 11 tests

Pour chaque test, choisir T-TVF-02 (warmup) ou T-TVF-03 (extract from form) selon le cas. Cibler 100% vert en local + CI.

### T-TVF-05 — RouteCountSmokeTest

```php
final class RouteCountSmokeTest extends KernelTestCase
{
    public function testVacationRoutesAreRegistered(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get('router');
        $routes = array_keys($router->getRouteCollection()->all());

        $myConges = array_filter($routes, fn($r) => str_starts_with($r, 'vacation_request_'));
        $managerConges = array_filter($routes, fn($r) => str_starts_with($r, 'vacation_approval_'));

        self::assertGreaterThanOrEqual(4, count($myConges));
        self::assertGreaterThanOrEqual(6, count($managerConges));
    }
}
```

Catch immediately tout futur loader manquant (regression sprint-003 → sprint-004).

### T-TVF-06 — Doc warmup pattern

Section "Tests fonctionnels" dans `CONTRIBUTING.md` :
- Pattern d'auth via `$client->loginUser()`
- Pattern de session warmup
- Pattern d'extraction CSRF

### T-TVF-07 — Code review

Critères : 11 tests ✅, route smoke ✅, doc ✅, PR <400 lignes diff.

## DoD

- [ ] 11 tests fonctionnels Vacation passent (`phpunit tests/Functional/Controller/Vacation/ tests/Functional/Vacation/`)
- [ ] `RouteCountSmokeTest` ajouté + vert
- [ ] `VacationFunctionalTrait` documenté
- [ ] CONTRIBUTING.md section ajoutée
- [ ] Code review approuvée

## Critères Gherkin

```gherkin
Scenario: Manager peut approuver une demande de congé
  Given un manager authentifié dans le test client
  And une demande de congé pendante de son intervenant
  When le test soumet POST /manager/conges/{id}/approuver avec CSRF
  Then la demande passe à APPROVED
  And le test passe sans SessionNotFoundException

Scenario: Le router charge les routes Vacation au boot kernel
  Given un kernel test bootstrappé
  When on inspecte le routerCollection
  Then on voit au moins 4 routes vacation_request_*
  And on voit au moins 6 routes vacation_approval_*
```
