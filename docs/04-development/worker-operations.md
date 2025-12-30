# Worker Messenger - Guide d'opérations

Documentation pour la gestion opérationnelle du worker Symfony Messenger.

## Vue d'ensemble

Le worker Messenger consomme les messages asynchrones (notamment le calcul des métriques analytics) depuis Redis.

**Transport configuré :** Redis (`redis://redis:6379/messages`)  
**Messages gérés :** `RecalculateMetricsMessage`

---

## Démarrage du worker

### En développement (Docker)

```bash
# Démarrer le worker pour tous les transports
docker compose exec app php bin/console messenger:consume async -vv

# Avec limite de temps (recommandé pour éviter les memory leaks)
docker compose exec app php bin/console messenger:consume async --time-limit=3600

# Avec limite de messages
docker compose exec app php bin/console messenger:consume async --limit=100
```

### En production

Le worker devrait être géré par un superviseur de processus comme **Supervisor** ou **systemd**.

#### Exemple avec Supervisor

Créer `/etc/supervisor/conf.d/messenger-worker.conf` :

```ini
[program:messenger-worker]
command=php /var/www/html/bin/console messenger:consume async --time-limit=3600
user=www-data
numprocs=2
autostart=true
autorestart=true
startsecs=0
process_name=%(program_name)s_%(process_num)02d
stderr_logfile=/var/log/messenger-worker.err.log
stdout_logfile=/var/log/messenger-worker.out.log
```

Puis :

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start messenger-worker:*
```

#### Exemple avec systemd

Créer `/etc/systemd/system/messenger-worker@.service` :

```ini
[Unit]
Description=Symfony Messenger Worker %i
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php bin/console messenger:consume async --time-limit=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Puis :

```bash
sudo systemctl daemon-reload
sudo systemctl enable messenger-worker@1.service messenger-worker@2.service
sudo systemctl start messenger-worker@1.service messenger-worker@2.service
```

---

## Restart du worker

### Graceful restart

Pour arrêter proprement le worker après le message en cours :

```bash
# Envoyer le signal SIGTERM
docker compose exec app kill -SIGTERM $(pidof php)

# Ou via supervisor
sudo supervisorctl restart messenger-worker:*
```

### Force restart

En cas de blocage :

```bash
# Kill forcé
docker compose exec app killall -9 php

# Relancer
docker compose exec app php bin/console messenger:consume async
```

---

## Monitoring

### Vérifier l'état du worker

```bash
# Voir les processus en cours
docker compose exec app ps aux | grep messenger:consume

# Via supervisor
sudo supervisorctl status messenger-worker:*

# Via systemd
sudo systemctl status messenger-worker@*.service
```

### Statistiques des messages

```bash
# Voir les messages en attente dans Redis
docker compose exec redis redis-cli LLEN messages

# Statistiques détaillées
docker compose exec app php bin/console messenger:stats
```

### Voir les échecs

```bash
# Lister les messages en échec
docker compose exec app php bin/console messenger:failed:show

# Voir les détails d'un message échoué
docker compose exec app php bin/console messenger:failed:show <id> -vv

# Réessayer les messages en échec
docker compose exec app php bin/console messenger:failed:retry

# Réessayer un message spécifique
docker compose exec app php bin/console messenger:failed:retry <id>

# Supprimer les messages en échec
docker compose exec app php bin/console messenger:failed:remove <id>
```

---

## Configuration des transports

Les transports sont définis dans `config/packages/messenger.yaml` :

```yaml
framework:
    messenger:
        failure_transport: failed
        
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'
            failed: 'doctrine://default?queue_name=failed'
        
        routing:
            'App\Message\RecalculateMetricsMessage': async
```

### Initialiser les transports

Après toute modification de la configuration :

```bash
docker compose exec app php bin/console messenger:setup-transports
```

---

## Métriques et KPIs à surveiller

### Santé du worker

- **Processus actifs** : Au moins 1-2 workers devraient tourner
- **Memory usage** : < 256 MB par worker (restart automatique si dépassé)
- **Restarts fréquents** : Indique un problème potentiel

### Performance

- **Messages traités/minute** : Variable selon la charge
- **Temps de traitement moyen** : 
  - Calcul mensuel : ~2-5 secondes
  - Calcul trimestriel : ~10-15 secondes
  - Calcul annuel : ~30-60 secondes
- **File d'attente Redis** : Devrait rester < 100 messages en temps normal

### Alertes à configurer

- Worker down pendant > 5 minutes
- File d'attente > 500 messages
- Taux d'échec > 10%
- Memory usage > 512 MB

---

## Débogage

### Mode verbose

```bash
# -v : verbose
# -vv : très verbose
# -vvv : debug complet
docker compose exec app php bin/console messenger:consume async -vvv
```

### Logs

```bash
# Logs du worker
docker compose logs -f app | grep messenger

# Logs Symfony
docker compose exec app tail -f var/log/dev.log

# Logs Redis
docker compose logs -f redis
```

### Tester l'envoi de messages

```bash
# Via commande CLI (à créer si besoin)
docker compose exec app php bin/console app:recalculate-metrics 2024-01-01 monthly

# Ou via code PHP temporaire
docker compose exec app php -r "
require 'vendor/autoload.php';
\$kernel = new App\Kernel('dev', true);
\$kernel->boot();
\$bus = \$kernel->getContainer()->get('messenger.default_bus');
\$bus->dispatch(new App\Message\RecalculateMetricsMessage('2024-01-01', 'monthly'));
echo 'Message dispatché\n';
"
```

---

## Bonnes pratiques

1. **Toujours utiliser `--time-limit`** en production pour éviter les memory leaks
2. **Multiplier les workers** (2-4) pour paralléliser le traitement
3. **Monitorer activement** les échecs et la file d'attente
4. **Logger les métriques** de traitement pour détecter les régressions
5. **Tester en staging** avant tout déploiement

---

## Troubleshooting courant

### Le worker ne démarre pas

```bash
# Vérifier la configuration
docker compose exec app php bin/console debug:messenger

# Vérifier Redis
docker compose exec redis redis-cli ping

# Vérifier les permissions
docker compose exec app ls -la var/
```

### Messages qui échouent systématiquement

```bash
# Voir l'erreur détaillée
docker compose exec app php bin/console messenger:failed:show <id> -vvv

# Vérifier les données
docker compose exec app php bin/console debug:container --parameters

# Tester manuellement
docker compose exec app php bin/console app:test-metrics-calculation
```

### File d'attente qui explose

```bash
# Augmenter le nombre de workers
# Ou limiter l'envoi de messages

# Purger la file si nécessaire (ATTENTION : perte de données)
docker compose exec redis redis-cli DEL messages
```

---

## Commandes utiles

```bash
# Voir tous les transports configurés
docker compose exec app php bin/console messenger:stats

# Debug configuration Messenger
docker compose exec app php bin/console debug:messenger

# Consommer seulement failed pour retry
docker compose exec app php bin/console messenger:consume failed

# Stop après N échecs consécutifs
docker compose exec app php bin/console messenger:consume async --failure-limit=3
```

---

## Références

- [Symfony Messenger Documentation](https://symfony.com/doc/current/messenger.html)
- [Deploying Messenger to Production](https://symfony.com/doc/current/messenger.html#deploying-to-production)
- [Redis Messenger Transport](https://symfony.com/doc/current/messenger.html#redis-transport)
