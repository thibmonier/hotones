# Contract Tests

Tests qui hit les **vraies API** Boond + HubSpot pour détecter les changements
de contrat (auth, shape JSON, codes HTTP) avant qu'ils ne cassent la prod.

> Story : `TEST-CONNECTORS-CONTRACT-001` (sprint-005). Complète les mocks
> `MockHttpClient` de `TEST-009` (sprint-004), qui gardent uniquement la
> logique côté client.

## Quand ils tournent

| Trigger | Détails |
|---|---|
| Cron hebdomadaire | Lundi `04:23 UTC` — `.github/workflows/contract-tests.yml` |
| Déclenchement manuel | `Actions → Contract Tests → Run workflow` |
| Local (dev) | `composer test-contract` (skip auto si secrets absents) |

Le workflow ne tourne **que si** le repo a la variable
`CONTRACT_TESTS_ENABLED=true` (Settings → Secrets and variables → Actions →
Variables). C'est le kill-switch : on peut désactiver instantanément sans
toucher au code.

## Provisionner les sandboxes

### HubSpot (gratuit)

1. Créer un compte Developer : <https://developers.hubspot.com/get-started>.
2. Dans le dashboard Developer, créer un **Test Account** (≠ production).
3. Dans le test account, `Settings → Integrations → Private Apps → Create
   private app`.
4. Scopes minimums : `crm.objects.contacts.read`, `crm.objects.deals.read`,
   `crm.schemas.deals.read`.
5. Copier le token affiché.

### Boond Manager (sandbox payante)

Boond ne propose pas de sandbox publique gratuite. Trois options :

- Demander à l'éditeur un environnement de qualif (souvent payant).
- Utiliser un compte de production secondaire dédié aux tests, sur des données
  factices.
- Si rien n'est disponible : **down-scope** la story à HubSpot uniquement. Le
  test Boond sera `markTestSkipped` automatiquement.

## Configurer les secrets

Repo → `Settings → Secrets and variables → Actions → Secrets`, ajouter :

| Secret | Pour |
|---|---|
| `BOOND_SANDBOX_BASE_URL` | URL de l'instance Boond sandbox (ex. `https://ui.boondmanager.com`) |
| `BOOND_SANDBOX_USERNAME` | Login basic auth Boond |
| `BOOND_SANDBOX_PASSWORD` | Password basic auth Boond |
| `HUBSPOT_SANDBOX_TOKEN` | Private app access token HubSpot |

Puis dans `Variables` :

| Variable | Valeur |
|---|---|
| `CONTRACT_TESTS_ENABLED` | `true` (mettre `false` pour mettre en pause) |

## Lancer en local

Sur ton poste, exporter les mêmes variables (jamais commit) :

```bash
export BOOND_SANDBOX_BASE_URL='https://ui.boondmanager.com'
export BOOND_SANDBOX_USERNAME='…'
export BOOND_SANDBOX_PASSWORD='…'
export HUBSPOT_SANDBOX_TOKEN='pat-eu1-…'

composer test-contract
```

Sortie attendue (avec credentials) :

```
PHPUnit 13.x
....                                                    4 / 4 (100%)
OK (4 tests, 8 assertions)
```

Sans credentials :

```
SSSSSS                                                  6 / 6 (100%)
OK, but there were issues!
Tests: 6, Assertions: 0, Skipped: 6.
```

Les tests `markTestSkipped` quand les variables ne sont pas là — c'est voulu,
ça permet de pousser le workflow à plat sans bloquer une PR.

## Ce que les tests vérifient

### Boond (`tests/Contract/BoondManagerContractTest.php`)

- `testConnection()` retourne `true` (200 sur `/api/application/dictionary`).
- `getDictionary()` renvoie un tableau non-vide.
- `getTimes()` accepte un range de 7 jours sans crash et renvoie un tableau.

### HubSpot (`tests/Contract/HubSpotContractTest.php`)

- `testConnection()` retourne `true` (200 sur `/crm/v3/objects/contacts`).
- `getAccountInfo()` expose la clé `portalId`.
- `getDealPipelines()` renvoie un tableau.

Ces assertions sont volontairement minces : on cherche à détecter
**changement de shape** ou **rupture d'auth**, pas à valider la logique
métier des sync services (couverte par `BoondManagerSyncServiceTest` et
`HubSpotSyncServiceTest`).

## Si un run échoue

Le workflow ouvre automatiquement une issue avec le label
`contract-failure`. Si une issue avec ce label existe déjà, il commente
dessus. Quand un run repasse vert, l'issue se ferme toute seule.

Pistes courantes :

- **Sandbox temporairement KO** → relancer manuellement le workflow dans 1h.
- **Token expiré ou révoqué** → régénérer côté Boond/HubSpot, mettre à jour
  le secret.
- **API contract a bougé** → adapter le client (`src/Service/BoondManager/`
  ou `src/Service/HubSpot/`) puis re-run. Documenter le changement dans le
  commit.

## Pourquoi pas dans la CI standard

- **Latence** : 4–8 sec par test à cause du round-trip réseau.
- **Quota** : HubSpot rate-limit à 100 req/10s pour un private app, on ne
  veut pas le manger sur chaque PR.
- **Flakiness** : sandbox externe = pannes ponctuelles. Sur CI principal ça
  bloquerait des merges sans rapport.

Cron hebdo + workflow_dispatch couvre 95% du besoin (drift d'API détecté en
≤ 7 jours).
