# Tâches — TEST-CONNECTORS-CONTRACT-001

## Informations

- **Story Points** : 5
- **MoSCoW** : Should
- **Origine** : sprint-004 review candidate (TEST-009 mocked-only)
- **Total estimé** : 12h

## Résumé

TEST-009 sprint-004 a couvert `BoondManagerClient` et `HubSpotClient` via `MockHttpClient`. Reste à valider que les contrats avec les vraies API sont stables : un test contract qui hit les sandboxes une fois par semaine (cron) et alerte si l'API change.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-TCC-01 | [OPS] | Provisionner sandboxes Boond + HubSpot, secrets dans GitHub | 1h | - | 🔲 |
| T-TCC-02 | [TEST] | Contract test BoondManagerClient (testConnection + getDictionary réels) | 3h | T-TCC-01 | 🔲 |
| T-TCC-03 | [TEST] | Contract test HubSpotClient (testConnection + getAccountInfo réels) | 3h | T-TCC-01 | 🔲 |
| T-TCC-04 | [OPS] | `phpunit-contract.xml` + group `contract` exclus par défaut | 2h | T-TCC-02, T-TCC-03 | 🔲 |
| T-TCC-05 | [OPS] | Workflow GitHub Actions cron hebdo `contract-tests.yml` | 2h | T-TCC-04 | 🔲 |
| T-TCC-06 | [DOC] | `docs/04-development/contract-tests.md` | 1h | T-TCC-05 | 🔲 |

## Détail des tâches

### T-TCC-01 — Sandboxes + secrets

- Créer un compte HubSpot Developer + sandbox (gratuit)
- Demander un compte Boond sandbox (souvent payant, fallback : skipper)
- Stocker dans **Settings → Secrets → Actions** :
  - `BOOND_SANDBOX_BASE_URL`, `BOOND_SANDBOX_USERNAME`, `BOOND_SANDBOX_PASSWORD`
  - `HUBSPOT_SANDBOX_TOKEN`

Si Boond sandbox indisponible → down-scope vers HubSpot uniquement (3 pts au lieu de 5).

### T-TCC-02 — Contract Boond

```php
#[Group('contract')]
final class BoondManagerContractTest extends TestCase
{
    public function testConnectionAgainstRealSandbox(): void
    {
        $settings = new BoondManagerSettings();
        $settings->apiBaseUrl = $_ENV['BOOND_SANDBOX_BASE_URL'];
        $settings->authType = 'basic';
        $settings->apiUsername = $_ENV['BOOND_SANDBOX_USERNAME'];
        $settings->apiPassword = $_ENV['BOOND_SANDBOX_PASSWORD'];

        $client = new BoondManagerClient(HttpClient::create(), new NullLogger());

        self::assertTrue($client->testConnection($settings));
    }

    public function testGetDictionaryReturnsExpectedShape(): void
    {
        // Vérifie que la forme du dictionary n'a pas changé : keys 'statuses', 'types', etc.
    }
}
```

### T-TCC-03 — Contract HubSpot

Symétrique à T-TCC-02.

### T-TCC-04 — phpunit-contract.xml

```xml
<phpunit ...>
    <testsuites>
        <testsuite name="contract">
            <directory>tests/Contract</directory>
        </testsuite>
    </testsuites>
    <groups>
        <include>
            <group>contract</group>
        </include>
    </groups>
</phpunit>
```

`phpunit.xml.dist` (default) doit **exclure** le group `contract` :

```xml
<groups>
    <exclude>
        <group>contract</group>
    </exclude>
</groups>
```

Composer script :

```json
"test-contract": "phpunit -c phpunit-contract.xml.dist"
```

### T-TCC-05 — Workflow GitHub

```yaml
# .github/workflows/contract-tests.yml
name: Contract Tests

on:
  schedule:
    - cron: '23 4 * * 1'  # Lundi 04:23 UTC
  workflow_dispatch:

jobs:
  contract:
    runs-on: ubuntu-latest
    if: ${{ vars.CONTRACT_TESTS_ENABLED == 'true' }}
    env:
      BOOND_SANDBOX_BASE_URL: ${{ secrets.BOOND_SANDBOX_BASE_URL }}
      # ... autres secrets
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
      - run: composer install --no-progress
      - run: composer test-contract
      - name: Issue auto on failure
        if: failure()
        # ... ouvrir issue label `contract-failure`
```

### T-TCC-06 — Doc

`docs/04-development/contract-tests.md` :
- Comment lancer en local : `composer test-contract`
- Variables d'env requises
- Procédure si le contract casse (fix le client ou ouvrir issue avec le provider)

## DoD

- [ ] 2 tests contract verts en local avec credentials réels
- [ ] Workflow `contract-tests.yml` actif (en `vars.CONTRACT_TESTS_ENABLED=true`)
- [ ] Auto-issue ouverte si run échoue
- [ ] Doc rédigée
- [ ] PR <400 lignes

## Risque & down-scope

Si Boond sandbox inaccessible : ne livrer que la partie HubSpot. La story devient 3 pts.
