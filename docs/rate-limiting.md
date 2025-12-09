# Rate Limiting

## Vue d'ensemble

Le rate limiting (limitation de débit) est configuré sur toutes les routes API pour prévenir les abus et garantir une utilisation équitable des ressources.

## Configuration

Les limiters sont définis dans `config/packages/rate_limiter.yaml` :

### API Limiter
- **Politique** : Token Bucket
- **Capacité** : 100 requêtes
- **Recharge** : 60 tokens/minute (1 req/seconde en moyenne)
- **Routes couvertes** : `/api/*`, `/tasks/api/*`

Cette configuration permet des burst courts (jusqu'à 100 requêtes d'un coup) tout en limitant le débit moyen à 1 requête par seconde.

### Autres Limiters

#### Login
- **Politique** : Sliding Window
- **Limite** : 5 tentatives
- **Intervalle** : 15 minutes
- **Usage** : Protection brute force sur l'authentification

#### Exports
- **Politique** : Fixed Window
- **Limite** : 10 exports
- **Intervalle** : 1 heure
- **Usage** : Exports PDF, Excel, CSV

#### Uploads
- **Politique** : Sliding Window
- **Limite** : 20 uploads
- **Intervalle** : 1 heure
- **Usage** : Upload de fichiers (avatars, documents)

#### Admin Actions
- **Politique** : Fixed Window
- **Limite** : 50 actions
- **Intervalle** : 1 heure
- **Usage** : Actions admin critiques

## Implémentation

Le rate limiting API est appliqué automatiquement via `ApiRateLimiterSubscriber` qui :
1. Intercepte toutes les requêtes (événement `kernel.request`)
2. Détecte les routes API
3. Applique le limiter approprié
4. Retourne une erreur 429 si la limite est dépassée

### Clés de limitation

Le limiter identifie les clients par :
1. **ID utilisateur** si authentifié (priorité)
2. **Adresse IP** sinon

Cela signifie que chaque utilisateur authentifié a sa propre limite, indépendamment de l'IP.

## Headers HTTP

Les réponses API incluent les headers suivants :

```
X-RateLimit-Limit: 100          # Limite totale
X-RateLimit-Remaining: 95       # Tokens restants
X-RateLimit-Reset: 1734512400   # Timestamp de réinitialisation
```

En cas de dépassement (HTTP 429) :

```json
{
  "error": "Too Many Requests",
  "message": "Rate limit exceeded. Please try again later.",
  "retry_after": 1734512400
}
```

## Storage

Par défaut, Symfony utilise un storage en mémoire (dev) ou en cache (prod).

Pour utiliser Redis (recommandé en production), ajoutez dans `rate_limiter.yaml` :

```yaml
framework:
    rate_limiter:
        api:
            storage_service: cache.adapter.redis
```

## Tests

### Tester le rate limiter localement

```bash
# Faire 101 requêtes rapides sur une route API
for i in {1..101}; do
    curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/api/search
done
```

Les 100 premières devraient retourner 200, la 101e devrait retourner 429.

### Désactiver temporairement (dev)

Pour désactiver le rate limiting en développement, commentez l'enregistrement du subscriber dans `config/services.yaml` :

```yaml
services:
    # App\EventSubscriber\ApiRateLimiterSubscriber: ~
```

Ou définissez des limites très élevées dans `rate_limiter.yaml` :

```yaml
api:
    limit: 999999
```

## Monitoring

### Voir les limiters configurés

```bash
docker compose exec app php bin/console debug:container --tag=limiter
```

### Vérifier les événements

```bash
docker compose exec app php bin/console debug:event-dispatcher kernel.request
```

Vous devriez voir `ApiRateLimiterSubscriber::onKernelRequest()` dans la liste.

## Bonnes pratiques

1. **Clients API externes** : Fournissez les headers de rate limiting dans la documentation
2. **Monitoring** : Suivez les erreurs 429 dans Sentry pour détecter des patterns d'abus
3. **Ajustement** : Adaptez les limites selon l'usage réel (logs, metrics)
4. **Cache** : Évitez les requêtes répétitives côté client (utilisez le cache HTTP)

## Références

- [Symfony Rate Limiter Component](https://symfony.com/doc/current/rate_limiter.html)
- [RFC 6585 - HTTP 429 Too Many Requests](https://tools.ietf.org/html/rfc6585#section-4)
