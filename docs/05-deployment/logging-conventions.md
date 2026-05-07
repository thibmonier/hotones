# Conventions Logging Structuré JSON

> **US-095** (sprint-017 EPIC-002) — format de logs prod & conventions appel.

## Format

Tous les logs prod sont émis en **JSON sur `php://stderr`** (Render capture stderr automatiquement et l'expose dans le dashboard service).

### Schéma type

```json
{
  "message": "Reservation confirmed for client alice@example.org",
  "context": {"reservation_id": 42, "amount_cents": 50000},
  "level": 200,
  "level_name": "INFO",
  "channel": "business",
  "datetime": "2026-05-07T15:32:11.123456+00:00",
  "extra": {
    "request_id": "20260507153211-a1b2c3d4",
    "request_method": "POST",
    "request_uri": "/api/reservations",
    "client_ip": "10.0.0.42",
    "user_email": "alice@example.org",
    "user_id": 7,
    "environment": "prod"
  }
}
```

## Champs `extra` (auto-injectés)

| Champ | Source | Présent en HTTP | Présent en CLI |
|---|---|---|---|
| `request_id` | `ContextProcessor` | ✅ | ❌ |
| `request_method` | `ContextProcessor` | ✅ | ❌ |
| `request_uri` | `ContextProcessor` | ✅ | ❌ |
| `client_ip` | `ContextProcessor` | ✅ | ❌ |
| `user_email` | `ContextProcessor` (si auth) | ✅ | ❌ |
| `user_id` | `ContextProcessor` (si auth) | ✅ | ❌ |
| `session_id` | `ContextProcessor` (8 premiers chars) | ✅ (si session) | ❌ |
| `environment` | `ContextProcessor` | ✅ | ✅ |
| `context: 'cli'` | `ContextProcessor` | ❌ | ✅ |
| `memory_usage_mb` | `PerformanceProcessor` | channel `performance` | channel `performance` |
| `memory_peak_mb` | `PerformanceProcessor` | channel `performance` | channel `performance` |
| `execution_time_ms` | `PerformanceProcessor` | channel `performance` | channel `performance` |

## Channels

| Channel | Niveau prod | Usage |
|---|---|---|
| `app` (défaut) | `error` (fingers_crossed buffer 50) | erreurs applicatives |
| `business` | `info` | événements métier (réservation, devis signé, facture émise) |
| `security` | `info` | login, 2FA, tentatives non-autorisées |
| `performance` | `warning` | requêtes lentes, cache misses |
| `deprecation` | `warning` | deprecations Symfony / vendor |

## Conventions appel

### ✅ Bon

```php
$this->logger->info('Reservation confirmed', [
    'reservation_id' => $reservationId,
    'client_id' => $clientId,
    'amount_cents' => $amountCents,
]);
```

PSR-3 interpolation OK (`process_psr_3_messages: true`) :

```php
$this->logger->info('Reservation {id} confirmed for {email}', [
    'id' => $reservationId,
    'email' => $clientEmail,
]);
```

### ❌ Mauvais

```php
// Concat → pas exploitable côté observabilité
$this->logger->info('Reservation '.$reservationId.' confirmed for '.$email);

// Données PII jamais loggées
$this->logger->info('Login attempt', ['password' => $plainPassword]);

// Doctrine entity dump (memory hog + lazy load)
$this->logger->info('Order created', ['order' => $orderEntity]);
```

## Correlation

Pour tracer une requête bout-en-bout :

1. `request_id` injecté auto par `ContextProcessor` dans `extra`
2. Passer aux jobs Messenger via stamp custom (TODO US-097 si besoin)
3. Exposer côté response header `X-Request-Id` (TODO US-096)

## Exploitation côté Render

```
# Filtrer logs erreur du dernier déploiement
level_name=ERROR

# Filtrer une requête spécifique
extra.request_id="20260507153211-a1b2c3d4"

# Filtrer un user
extra.user_email="alice@example.org"
```

## Liens

- US-095 : Logging structuré JSON
- ADR-0012 : observability stack (Sentry free tier — ces logs complètent Sentry)
- Runbook on-call : `docs/05-deployment/oncall-runbook.md`
