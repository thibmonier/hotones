# 🚀 Recommandations d'Optimisation Performance

**Date** : 2 décembre 2025
**Contexte** : Lot 23 - Performance & Scalabilité
**Objectif** : Optimiser les performances pour grosse volumétrie

---

## 📊 Audit Actuel

### Configuration Cache

#### ✅ Points Positifs
- Doctrine query cache configuré (pool: `doctrine.system_cache_pool`)
- Doctrine result cache configuré (pool: `doctrine.result_cache_pool`) en production
- API Platform metadata cache activé
- 22 pools de cache identifiés

#### ⚠️ Points d'Amélioration
- **Cache adapter** : Actuellement filesystem (`cache.app`, `cache.system`)
- **Redis disponible** mais non utilisé pour le cache (seulement Messenger)
- **Aucune configuration HTTP cache** (Varnish ou Symfony HTTP Cache)
- **Pas de cache APCu** pour opcache user data

### Index Base de Données

#### ✅ Index Existants Bien Configurés
- `contributors.UNIQ_72D26262A76ED395` sur `user_id`
- `dim_time.UNIQ_6F547BD9A787B0B8` sur `date_value`
- Contraintes unique sur tables dimensionnelles (Analytics)
- Index foreign keys sur la plupart des relations

#### 🔍 À Analyser
- Tables sans index : À vérifier (timesheets, projects, orders)
- Index composites manquants potentiels
- Requêtes lentes à identifier

---

## 🎯 Recommandations par Priorité

### 🔴 Priorité HAUTE (Impact immédiat)

#### 1. Activer Redis pour le cache applicatif

**Impact** : Performance x10 sur lectures répétées

**Configuration à ajouter dans `config/packages/cache.yaml`** :

```yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'

        pools:
            # Cache pour les métadonnées Doctrine
            doctrine.system_cache_pool:
                adapter: cache.adapter.redis
                default_lifetime: 86400  # 24h

            # Cache pour les résultats de requêtes Doctrine
            doctrine.result_cache_pool:
                adapter: cache.adapter.redis
                default_lifetime: 3600   # 1h

            # Cache custom pour Analytics (KPIs lourds)
            cache.analytics:
                adapter: cache.adapter.redis
                default_lifetime: 1800   # 30 minutes
```

**Variables d'environnement à ajouter** :

```bash
# .env
REDIS_URL=redis://redis:6379

# .env.render.example
REDIS_URL=redis://red-xxxxxxxxxxxxx:6379
```

**Gain estimé** : 60-80% réduction temps requêtes répétées

---

#### 2. Optimiser les requêtes Analytics

**Problème** : Requêtes lourdes sur `fact_project_metrics` et `fact_staffing_metrics`

**Solution** : Cache de résultats avec invalidation intelligente

**Fichier** : `src/Service/Analytics/DashboardReadService.php`

```php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DashboardReadService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $analyticsCache, // Injecter le pool cache.analytics
    ) {}

    public function getKPIs(DateTime $startDate, DateTime $endDate, array $filters = []): array
    {
        $cacheKey = sprintf(
            'analytics_kpis_%s_%s_%s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            md5(json_encode($filters))
        );

        return $this->analyticsCache->get($cacheKey, function (ItemInterface $item) use ($startDate, $endDate, $filters) {
            $item->expiresAfter(1800); // 30 minutes

            // Requête existante (lourde)
            return $this->calculateKPIs($startDate, $endDate, $filters);
        });
    }
}
```

**Configuration service dans `config/services.yaml`** :

```yaml
services:
    App\Service\Analytics\DashboardReadService:
        arguments:
            $analyticsCache: '@cache.analytics'
```

**Gain estimé** : Dashboard Analytics 10x plus rapide sur pages répétées

---

#### 3. Ajouter index manquants sur tables critiques

**À analyser en priorité** :
- `timesheets` : requêtes fréquentes par `contributor_id`, `project_id`, `date`
- `projects` : filtres sur `status`, `project_type`, `client_id`
- `orders` : filtres sur `status`, `client_id`, `created_at`

**Commande d'analyse** :

```bash
# Identifier les requêtes lentes
docker compose exec app php bin/console doctrine:query:sql "SHOW PROCESSLIST"

# Analyser plan d'exécution
docker compose exec app php bin/console doctrine:query:sql "EXPLAIN SELECT ..."
```

**Migration à créer après analyse** :

```bash
php bin/console make:migration
```

**Exemple d'index composite** :

```sql
-- Migration: Index pour recherche timesheets par contributeur et période
CREATE INDEX idx_timesheet_contributor_date ON timesheet (contributor_id, date);
CREATE INDEX idx_timesheet_project_date ON timesheet (project_id, date);

-- Index pour filtres projets
CREATE INDEX idx_project_status_type ON project (status, project_type);
CREATE INDEX idx_project_client_status ON project (client_id, status);
```

**Gain estimé** : 50-70% réduction temps requêtes filtrées

---

### 🟡 Priorité MOYENNE (Optimisation progressive)

#### 4. Résoudre problèmes N+1

**Outil** : Activer profiling Doctrine en dev

**Configuration `config/packages/doctrine.yaml` (dev uniquement)** :

```yaml
when@dev:
    doctrine:
        dbal:
            profiling_collect_backtrace: true
            logging: true
            profiling_collect_schema_errors: true
```

**Analyse avec Symfony Profiler** :
- Ouvrir Web Profiler après chaque page
- Onglet "Doctrine" → Voir le nombre de requêtes
- Identifier les boucles générant des requêtes répétées

**Exemple de fix N+1** :

```php
// ❌ AVANT (N+1)
$projects = $projectRepository->findAll();
foreach ($projects as $project) {
    echo $project->getClient()->getName(); // N requêtes
}

// ✅ APRÈS (1 requête)
$projects = $projectRepository->createQueryBuilder('p')
    ->addSelect('c')
    ->leftJoin('p.client', 'c')
    ->getQuery()
    ->getResult();
```

**Gain estimé** : Réduction 80-90% du nombre de requêtes sur listes

---

#### 5. Pagination côté serveur

**Problème** : Certains listings chargent tous les résultats en mémoire

**Solution** : KnpPaginatorBundle (déjà installé ✅)

**Exemple de controller optimisé** :

```php
use Knp\Component\Pager\PaginatorInterface;

public function index(Request $request, PaginatorInterface $paginator): Response
{
    $queryBuilder = $this->projectRepository->createQueryBuilder('p')
        ->addSelect('c')
        ->leftJoin('p.client', 'c')
        ->orderBy('p.createdAt', 'DESC');

    $pagination = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        25  // Items par page
    );

    return $this->render('project/index.html.twig', [
        'pagination' => $pagination,
    ]);
}
```

**Gain estimé** : Réduction mémoire 90% sur grandes tables

---

#### 6. Lazy loading images et graphiques

**Frontend** : Charger graphiques Chart.js à la demande

**JavaScript** :

```javascript
// Lazy load charts avec Intersection Observer
const chartContainers = document.querySelectorAll('.chart-container[data-chart-config]');

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const container = entry.target;
            const config = JSON.parse(container.dataset.chartConfig);
            renderChart(container, config);
            observer.unobserve(container);
        }
    });
});

chartContainers.forEach(container => observer.observe(container));
```

**Gain estimé** : Temps chargement initial -40%

---

### 🟢 Priorité BASSE (Nice to have)

#### 7. HTTP Cache avec Varnish ou Symfony HTTP Cache

**Pour** : Pages publiques, API endpoints read-only

**Configuration Symfony HTTP Cache** :

```yaml
# config/packages/framework.yaml
framework:
    http_cache:
        enabled: true
```

**Dans les controllers** :

```php
public function publicPage(): Response
{
    $response = $this->render('page/public.html.twig');
    $response->setSharedMaxAge(3600); // Cache 1h

    return $response;
}
```

**Gain estimé** : Pages publiques x100 plus rapides

---

#### 8. APCu pour cache local

**Configuration** :

```yaml
# config/packages/cache.yaml (production)
when@prod:
    framework:
        cache:
            app: cache.adapter.redis
            system: cache.adapter.apcu
```

**Note** : APCu déjà installé dans Docker ✅

**Gain estimé** : Métadonnées système 20-30% plus rapides

---

#### 9. Compression Gzip/Brotli

**Nginx** : Activer compression (déjà dans `docker/nginx/nginx.conf`)

```nginx
gzip on;
gzip_vary on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
```

**Gain estimé** : Bande passante -60-70%

---

#### 10. Monitoring & APM

**Outils recommandés** :
- **Blackfire.io** : Profiling PHP détaillé (gratuit pour dev)
- **New Relic** : APM complet (payant)
- **Sentry Performance** : Tracing des requêtes lentes

**Installation Blackfire (dev)** :

```bash
# Docker
docker compose exec app wget -qO- https://packages.blackfire.io/binaries/blackfire-php/2.30.0/blackfire-php-alpine_amd64-php-84.so > /usr/local/lib/php/extensions/no-debug-non-zts-20240924/blackfire.so
docker compose exec app echo "extension=blackfire.so" > /usr/local/etc/php/conf.d/blackfire.ini
```

---

## 📈 Gains Estimés Cumulés

| Optimisation | Gain | Priorité |
|--------------|------|----------|
| Redis cache | 60-80% requêtes répétées | 🔴 Haute |
| Analytics cache | 10x dashboard | 🔴 Haute |
| Index BDD | 50-70% requêtes filtrées | 🔴 Haute |
| Fix N+1 | 80-90% réduction requêtes | 🟡 Moyenne |
| Pagination | 90% réduction mémoire | 🟡 Moyenne |
| Lazy loading | 40% temps initial | 🟡 Moyenne |

**Objectif global** : Application 5-10x plus rapide sur volumétrie élevée

---

## 🎯 Plan d'Action Recommandé

### Semaine 1 (2-3 jours)
1. ✅ Activer Redis pour cache
2. ✅ Cacher résultats Analytics
3. ✅ Analyser et ajouter index manquants

### Semaine 2 (2-3 jours)
4. ✅ Identifier et fixer N+1 critiques
5. ✅ Vérifier pagination sur tous les listings
6. ✅ Tests de charge (Apache Bench)

### Semaine 3 (1-2 jours)
7. ✅ Lazy loading charts
8. ✅ APCu pour cache système
9. ✅ Monitoring Blackfire
10. ✅ Documentation

**Total estimé** : 5-8 jours

---

## 📚 Ressources

- [Symfony Performance Best Practices](https://symfony.com/doc/current/performance.html)
- [Doctrine Performance](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/improving-performance.html)
- [Redis Cache Adapter](https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html)
- [KnpPaginatorBundle](https://github.com/KnpLabs/KnpPaginatorBundle)

---

**Dernière mise à jour** : 2 décembre 2025
**Prochaine revue** : Après implémentation Semaine 1
