# ⚡ Performance

Recommandations techniques pour la vitesse de chargement et la gestion de gros volumes.

## Symfony/PHP
- OPcache activé (prod), `opcache.validate_timestamps=0`, preloading via script Symfony (`var/cache/prod/App_KernelProdContainer.preload.php`).
- Cache pools: `cache.app` et `cache.system` sur Redis/APCu; tags d’invalidation pour agrégats.
- HTTP cache: ETag/Last-Modified, Cache-Control; prévoir reverse proxy (ex: Varnish/Nginx) pour pages et APIs idempotentes.
- Monolog: niveau `warning` en prod; handlers asynchrones si volumétrie élevée.

## Doctrine/MariaDB
- Éviter le N+1: fetch joins avec parcimonie, `SELECT partial`/DTO.
- Pagination (Paginator) et requêtes projetées pour listes; `COUNT DISTINCT` optimisé.
- Index: colonnes de filtre/tri, composites et couvrants; vérifier avec `EXPLAIN`.
- Caches: query/result cache (Redis), second-level cache si pertinent.
- Tuning MariaDB: `innodb_buffer_pool_size` (50–70% RAM), `innodb_flush_log_at_trx_commit=2` (compromis), slow query log activé.

## Front/Assets
- Webpack Encore: minify, splitChunks, `asset()` versionné; purge CSS, tree-shaking.
- Images: WebP/AVIF, dimensions explicites, lazy-loading; SVG spriting pour icônes.
- JS: `defer`/`async`, éviter libs lourdes; CSS critique inline si nécessaire.
- HTTP/2 + compression (gzip/brotli), `preload` des ressources critiques.

## Données volumineuses
- Pagination/filtrage côté serveur, limites strictes; export CSV/Excel en streaming.
- Tâches lourdes asynchrones avec Messenger (batching, retry/backoff); éviter timeouts web.
- Modèle en étoile/agrégats matérialisés pour Analytics (voir `docs/analytics.md`).
- Archivage/partitionnement des données historiques.

## Opérations et infra
- Warmup du cache avant déploiement (`cache:clear --no-warmup` puis `cache:warmup`).
- PHP-FPM: `pm=dynamic`, `pm.max_children` dimensionné; Nginx: cache des assets statiques long TTL.
- Santé/observabilité: métriques, APM, budgets de perf; alertes sur lenteurs DB.
