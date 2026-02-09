<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service de cache pour les données analytics
 * Implémente une stratégie de cache Redis avec TTL configurable.
 */
class AnalyticsCacheService
{
    private const int DEFAULT_TTL         = 3600; // 1 heure par défaut
    private const string CACHE_KEY_PREFIX = 'analytics_';

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Récupère ou calcule une métrique avec mise en cache.
     *
     * @template T
     *
     * @param string        $key      Clé unique de la métrique
     * @param callable(): T $callback Fonction de calcul de la métrique
     * @param int           $ttl      Durée de vie en cache (en secondes)
     *
     * @return T
     */
    public function getOrCompute(string $key, callable $callback, int $ttl = self::DEFAULT_TTL): mixed
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$key;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);

            return $callback();
        });
    }

    /**
     * Invalide une clé de cache spécifique.
     */
    public function invalidate(string $key): bool
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$key;

        return $this->cache->delete($cacheKey);
    }

    /**
     * Invalide tous les caches analytics.
     */
    public function invalidateAll(): void
    {
        // Utilise les tags si disponibles (nécessite Redis avec RedisAdapter)
        // Pour une invalidation globale, on peut utiliser un pattern
        $this->cache->delete(self::CACHE_KEY_PREFIX.'*');
    }

    /**
     * Génère une clé de cache basée sur des filtres.
     */
    public function generateKey(string $metricName, array $filters = []): string
    {
        ksort($filters); // Tri pour cohérence
        $filterHash = md5(json_encode($filters, JSON_THROW_ON_ERROR));

        return sprintf('%s_%s', $metricName, $filterHash);
    }

    /**
     * Préchauffe le cache avec des métriques courantes
     * À appeler via commande CLI ou après mise à jour de données.
     */
    public function warmup(array $metrics): void
    {
        foreach ($metrics as $key => $callback) {
            $this->getOrCompute($key, $callback);
        }
    }
}
