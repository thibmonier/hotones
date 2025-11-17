# Guide de Dépannage

Ce guide recense les problèmes courants rencontrés avec HotOnes et leurs solutions.

## Table des matières

- [Problèmes Docker](#problèmes-docker)
- [Problèmes de base de données](#problèmes-de-base-de-données)
- [Problèmes PHP](#problèmes-php)
- [Problèmes Symfony](#problèmes-symfony)
- [Problèmes Messenger](#problèmes-messenger)
- [Problèmes de cache](#problèmes-de-cache)
- [Problèmes d'assets](#problèmes-dassets)
- [Problèmes de permissions](#problèmes-de-permissions)
- [Problèmes de performance](#problèmes-de-performance)
- [Problèmes d'authentification](#problèmes-dauthentification)
- [Logs et debug](#logs-et-debug)

---

## Problèmes Docker

### Port déjà utilisé

**Symptôme:**
```
Error starting userland proxy: listen tcp4 0.0.0.0:8080: bind: address already in use
```

**Cause:** Un autre service utilise déjà le port 8080.

**Solutions:**

1. **Identifier le processus:**
```bash
# macOS/Linux
lsof -ti:8080

# Ou voir les détails
lsof -i:8080
```

2. **Arrêter le processus:**
```bash
# Tuer le processus
lsof -ti:8080 | xargs kill -9
```

3. **Ou changer le port dans docker-compose.yml:**
```yaml
services:
  web:
    ports:
      - "8081:80"  # Utiliser 8081 au lieu de 8080
```

---

### Containers ne démarrent pas

**Symptôme:**
```bash
docker-compose ps
# Montre des containers en status "Exit 1" ou "Restarting"
```

**Solutions:**

1. **Voir les logs:**
```bash
docker-compose logs app
docker-compose logs db
```

2. **Problème courant: DB pas prête avant app:**
```bash
# Démarrer DB d'abord
docker-compose up -d db
sleep 10
docker-compose up -d
```

3. **Rebuild complet:**
```bash
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

---

### Performances lentes (macOS/Windows)

**Symptôme:** Temps de chargement très lents en développement.

**Cause:** Bind mounts sont lents sur macOS/Windows avec Docker Desktop.

**Solutions:**

1. **Option :cached déjà utilisée:**
```yaml
volumes:
  - ./:/var/www/html:cached  # Déjà configuré
```

2. **Augmenter les ressources Docker Desktop:**
   - Docker Desktop → Preferences → Resources
   - CPU: 4+ cores
   - RAM: 4+ GB
   - Swap: 2+ GB

3. **Utiliser un volume nommé pour vendor/ (optionnel):**
```yaml
volumes:
  - ./:/var/www/html:cached
  - vendor:/var/www/html/vendor  # Exclure vendor du bind-mount

volumes:
  vendor:
```

---

## Problèmes de base de données

### Connection refused

**Symptôme:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Cause:** MariaDB n'est pas démarré ou pas encore prêt.

**Solutions:**

1. **Vérifier que le container DB est démarré:**
```bash
docker-compose ps db
# Doit afficher "Up"
```

2. **Vérifier les logs MariaDB:**
```bash
docker-compose logs db
# Chercher "ready for connections"
```

3. **Tester la connexion:**
```bash
docker-compose exec app mysql -h db -u symfony -psymfony -e "SELECT 1"
```

4. **Redémarrer MariaDB:**
```bash
docker-compose restart db
```

---

### Access denied for user

**Symptôme:**
```
SQLSTATE[HY000] [1045] Access denied for user 'symfony'@'%'
```

**Cause:** Credentials incorrects dans DATABASE_URL.

**Solutions:**

1. **Vérifier .env.local:**
```bash
# Doit correspondre à docker-compose.yml
DATABASE_URL="mysql://symfony:symfony@db:3306/hotones?serverVersion=mariadb-11.4.0&charset=utf8mb4"
```

2. **Vérifier docker-compose.yml:**
```yaml
db:
  environment:
    MYSQL_USER: symfony
    MYSQL_PASSWORD: symfony
```

3. **Recréer le container DB:**
```bash
docker-compose down -v  # ⚠️ Supprime les données
docker-compose up -d db
```

---

### Database does not exist

**Symptôme:**
```
SQLSTATE[HY000] [1049] Unknown database 'hotones'
```

**Cause:** Base de données non créée.

**Solutions:**

1. **Créer la base:**
```bash
docker-compose exec app php bin/console doctrine:database:create
```

2. **Exécuter les migrations:**
```bash
docker-compose exec app php bin/console doctrine:migrations:migrate
```

3. **Ou se connecter manuellement:**
```bash
docker-compose exec db mysql -u root -proot -e "CREATE DATABASE hotones CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

---

### Migration failed

**Symptôme:**
```
SQLSTATE[42S01]: Base table or view already exists
```

**Cause:** Migration déjà exécutée ou conflit de schéma.

**Solutions:**

1. **Voir les migrations exécutées:**
```bash
docker-compose exec app php bin/console doctrine:migrations:status
```

2. **Marquer une migration comme exécutée sans l'exécuter:**
```bash
docker-compose exec app php bin/console doctrine:migrations:version --add DoctrineMigrations\\Version20240101120000
```

3. **Reset complet (⚠️ perte de données):**
```bash
docker-compose exec app php bin/console doctrine:database:drop --force
docker-compose exec app php bin/console doctrine:database:create
docker-compose exec app php bin/console doctrine:migrations:migrate
```

---

## Problèmes PHP

### Memory limit exceeded

**Symptôme:**
```
Fatal error: Allowed memory size of 134217728 bytes exhausted
```

**Solutions:**

1. **Augmenter dans docker/php/php.ini:**
```ini
memory_limit = 512M
```

2. **Rebuild l'image:**
```bash
docker-compose build app
docker-compose up -d
```

3. **Pour une commande ponctuelle:**
```bash
docker-compose exec app php -d memory_limit=1G bin/console ma:commande
```

---

### Extension not loaded

**Symptôme:**
```
extension 'redis' is not loaded
```

**Cause:** Extension PHP manquante.

**Solutions:**

1. **Vérifier les extensions installées:**
```bash
docker-compose exec app php -m
```

2. **Si manquante, ajouter dans Dockerfile:**
```dockerfile
RUN pecl install redis \
  && docker-php-ext-enable redis
```

3. **Rebuild:**
```bash
docker-compose build --no-cache app
docker-compose up -d
```

---

## Problèmes Symfony

### Route not found

**Symptôme:**
```
No route found for "GET /ma-route"
```

**Solutions:**

1. **Lister toutes les routes:**
```bash
docker-compose exec app php bin/console debug:router
```

2. **Chercher une route spécifique:**
```bash
docker-compose exec app php bin/console debug:router ma_route
```

3. **Vider le cache:**
```bash
docker-compose exec app php bin/console cache:clear
```

---

### Service not found

**Symptôme:**
```
Service "App\Service\MonService" not found
```

**Causes courantes:**
- Namespace incorrect
- Service non public et non injecté
- Autowiring désactivé

**Solutions:**

1. **Vérifier que le service est bien déclaré:**
```bash
docker-compose exec app php bin/console debug:container MonService
```

2. **Vérifier l'autowiring dans services.yaml:**
```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
```

3. **Si le service doit être public:**
```yaml
App\Service\MonService:
    public: true
```

---

### Environment variables not loaded

**Symptôme:** Variables d'environnement undefined ou null.

**Solutions:**

1. **Vérifier que .env.local existe:**
```bash
ls -la .env.local
```

2. **Recharger les variables (dev):**
```bash
docker-compose exec app php bin/console cache:clear
```

3. **Debug les variables:**
```bash
docker-compose exec app php bin/console debug:container --env-vars
```

4. **En production, compiler les .env:**
```bash
composer dump-env prod
```

---

## Problèmes Messenger

### Workers ne consomment pas les messages

**Symptôme:** Jobs dans la queue mais jamais traités.

**Solutions:**

1. **Vérifier que les workers tournent:**
```bash
# Docker
docker-compose exec app ps aux | grep messenger

# Production (Supervisor)
sudo supervisorctl status
```

2. **Démarrer un worker manuellement:**
```bash
docker-compose exec app php bin/console messenger:consume async -vv
```

3. **Vérifier le routing dans messenger.yaml:**
```yaml
framework:
    messenger:
        routing:
            'App\Message\RecalculateMetricsMessage': async
```

4. **Redémarrer Supervisor (production):**
```bash
sudo supervisorctl restart hotones-messenger:*
```

---

### Failed messages s'accumulent

**Symptôme:** Beaucoup de messages en failed transport.

**Solutions:**

1. **Voir les messages failed:**
```bash
docker-compose exec app php bin/console messenger:failed:show
```

2. **Retry un message:**
```bash
docker-compose exec app php bin/console messenger:failed:retry
```

3. **Retry tous les messages:**
```bash
docker-compose exec app php bin/console messenger:failed:retry --force
```

4. **Supprimer les messages failed:**
```bash
docker-compose exec app php bin/console messenger:failed:remove
```

---

### Redis connection failed

**Symptôme:**
```
Connection refused [tcp://redis:6379]
```

**Solutions:**

1. **Vérifier que Redis est démarré:**
```bash
docker-compose ps redis
```

2. **Tester la connexion:**
```bash
docker-compose exec app sh -c 'echo "PING" | redis-cli -h redis'
# Doit retourner: PONG
```

3. **Vérifier MESSENGER_TRANSPORT_DSN:**
```bash
docker-compose exec app env | grep MESSENGER
```

---

## Problèmes de cache

### Changes not reflected

**Symptôme:** Modifications du code non prises en compte.

**Solutions:**

1. **Vider le cache:**
```bash
docker-compose exec app php bin/console cache:clear
```

2. **En mode dev, le cache devrait se rafraîchir automatiquement. Vérifier APP_ENV:**
```bash
docker-compose exec app env | grep APP_ENV
# Doit afficher: APP_ENV=dev
```

3. **Supprimer le cache manuellement:**
```bash
docker-compose exec app rm -rf var/cache/*
```

4. **Warmup du cache:**
```bash
docker-compose exec app php bin/console cache:warmup
```

---

### OPcache issues (production)

**Symptôme:** Modifications non visibles malgré déploiement.

**Solutions:**

1. **Recharger PHP-FPM:**
```bash
sudo systemctl reload php8.4-fpm
```

2. **Désactiver opcache.validate_timestamps en prod (normal):**
```ini
; docker/php/php.ini
opcache.validate_timestamps = 0  # Ne jamais revalider en prod
```

3. **Après déploiement, toujours recharger PHP-FPM.**

---

## Problèmes d'assets

### Assets not found (404)

**Symptôme:**
```
GET /build/app.js 404 Not Found
```

**Cause:** Assets non compilés.

**Solutions:**

1. **Compiler les assets:**
```bash
docker-compose exec app yarn build
```

2. **Ou en mode watch (dev):**
```bash
docker-compose exec app yarn watch
```

3. **Vérifier que les fichiers existent:**
```bash
ls -la public/build/
```

4. **En production, vérifier manifest.json:**
```bash
cat public/build/manifest.json
```

---

### Webpack encore errors

**Symptôme:** Erreurs lors de `yarn build`.

**Solutions:**

1. **Réinstaller les dépendances:**
```bash
docker-compose exec app yarn install --frozen-lockfile
```

2. **Supprimer node_modules et réinstaller:**
```bash
docker-compose exec app rm -rf node_modules
docker-compose exec app yarn install
```

3. **Vérifier la config webpack.config.js:**
```bash
docker-compose exec app node_modules/.bin/encore --help
```

---

## Problèmes de permissions

### Permission denied on var/

**Symptôme:**
```
Warning: file_put_contents(/var/www/html/var/cache/...): Permission denied
```

**Causes:** Permissions incorrectes sur var/ ou public/uploads/.

**Solutions:**

1. **Depuis l'hôte (Docker):**
```bash
sudo chown -R 1000:1000 var/ public/uploads/
chmod -R 775 var/ public/uploads/
```

2. **Depuis le container:**
```bash
docker-compose exec app chown -R www-data:www-data var/ public/uploads/
docker-compose exec app chmod -R 775 var/ public/uploads/
```

3. **En production:**
```bash
sudo chown -R www-data:www-data /var/www/hotones/var
sudo chmod -R 775 /var/www/hotones/var
```

---

### Cannot write to public/uploads/

**Symptôme:** Upload de fichiers échoue.

**Solutions:**

1. **Vérifier que le dossier existe:**
```bash
mkdir -p public/uploads/clients
```

2. **Permissions:**
```bash
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads  # Production
```

3. **Dans Docker (dev):**
```bash
docker-compose exec app chmod -R 777 public/uploads  # Dev seulement
```

---

## Problèmes de performance

### Slow page load

**Symptôme:** Pages qui mettent plusieurs secondes à charger.

**Solutions:**

1. **Activer le profiler (dev uniquement):**
   - Accéder à `/_profiler` après une requête
   - Analyser les requêtes DB (problème N+1?)
   - Analyser le temps d'exécution

2. **Problème N+1 queries:**
```php
// Avant (N+1)
$projects = $projectRepo->findAll();
foreach ($projects as $project) {
    echo $project->getClient()->getName();  // 1 query par projet
}

// Après (1 query)
$qb = $projectRepo->createQueryBuilder('p')
    ->leftJoin('p.client', 'c')
    ->addSelect('c');
```

3. **Activer OPcache (production):**
```ini
opcache.enable=1
opcache.memory_consumption=128
```

4. **Activer APCu pour Doctrine metadata:**
```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        metadata_cache_driver:
            type: apcu
```

---

### Out of memory

**Symptôme:**
```
Fatal error: Allowed memory size exhausted
```

**Solutions:**

1. **Augmenter memory_limit:**
```ini
memory_limit = 512M
```

2. **Identifier les requêtes gourmandes:**
```bash
docker-compose exec app php bin/console debug:container --env-vars
```

3. **Batch processing pour les gros datasets:**
```php
// Au lieu de
$projects = $projectRepo->findAll();  // Charge tout en mémoire

// Utiliser
foreach ($projectRepo->findBy([], null, 100, 0) as $project) {
    // Traiter par batch de 100
}
```

---

## Problèmes d'authentification

### JWT token invalid

**Symptôme:**
```
Invalid JWT Token
```

**Solutions:**

1. **Vérifier que les clés JWT existent:**
```bash
ls -la config/jwt/
```

2. **Régénérer les clés:**
```bash
docker-compose exec app php bin/console lexik:jwt:generate-keypair --overwrite
```

3. **Vérifier le JWT_PASSPHRASE dans .env.local:**
```bash
grep JWT_PASSPHRASE .env.local
```

4. **Tester la génération d'un token:**
```bash
docker-compose exec app php bin/console lexik:jwt:generate-token user@example.com
```

---

### 2FA not working

**Symptôme:** QR code ne fonctionne pas ou code 2FA rejeté.

**Causes possibles:**
- Horloge désynchronisée
- Secret 2FA incorrect

**Solutions:**

1. **Vérifier l'heure du serveur:**
```bash
date
```

2. **Synchroniser l'heure (si décalée):**
```bash
sudo ntpdate -s time.nist.gov
```

3. **Régénérer le secret 2FA pour l'utilisateur:**
```sql
UPDATE user SET totp_secret = NULL WHERE email = 'user@example.com';
```

---

## Logs et debug

### Où trouver les logs

**Docker (dev):**
```bash
# Logs Symfony
docker-compose exec app tail -f var/log/dev.log

# Logs PHP-FPM
docker-compose logs -f app

# Logs Nginx
docker-compose logs -f web

# Logs MariaDB
docker-compose logs -f db

# Logs Workers Messenger
tail -f var/log/messenger_*.log
```

**Production:**
```bash
# Logs Symfony
tail -f /var/www/hotones/var/log/prod.log

# Logs PHP-FPM
tail -f /var/log/php8.4-fpm.log

# Logs Nginx
tail -f /var/log/nginx/hotones_error.log

# Logs Supervisor (workers)
tail -f /var/www/hotones/var/log/messenger_*.log

# Logs système
journalctl -u nginx -f
journalctl -u php8.4-fpm -f
```

---

### Activer le mode debug

**Développement:**
```bash
# .env.local
APP_ENV=dev
APP_DEBUG=1
```

**Voir les erreurs détaillées:**
```bash
# Dans le navigateur
http://localhost:8080/_profiler

# Logs détaillés
docker-compose exec app php bin/console about
```

---

### Symfony var-dumper

**Utiliser dump() et dd():**
```php
// Dans un contrôleur ou service
dump($variable);  // Affiche dans le profiler
dd($variable);    // Dump and die
```

---

### Désactiver le cache en dev

**Si vraiment nécessaire:**
```bash
# Supprimer var/cache/
rm -rf var/cache/

# Désactiver OPcache
docker-compose exec app sh -c 'echo "opcache.enable=0" > /usr/local/etc/php/conf.d/opcache.ini'
docker-compose restart app
```

---

## Commandes utiles de diagnostic

### État général

```bash
# Infos Symfony
docker-compose exec app php bin/console about

# Services disponibles
docker-compose exec app php bin/console debug:container

# Routes
docker-compose exec app php bin/console debug:router

# Events
docker-compose exec app php bin/console debug:event-dispatcher

# Config
docker-compose exec app php bin/console debug:config
```

---

### État de la base de données

```bash
# Vérifier la connexion
docker-compose exec app php bin/console doctrine:query:sql "SELECT 1"

# Schéma en sync?
docker-compose exec app php bin/console doctrine:schema:validate

# Migrations en attente?
docker-compose exec app php bin/console doctrine:migrations:status
```

---

### État des workers

```bash
# Docker
docker-compose exec app ps aux | grep console

# Production
sudo supervisorctl status

# Stats Messenger
docker-compose exec app php bin/console messenger:stats
```

---

## Checklist de debug générale

Lorsque vous rencontrez un problème:

1. **Vérifier les logs** (voir section Logs et debug)
2. **Vérifier APP_ENV** (dev/prod/test)
3. **Vider le cache** (`cache:clear`)
4. **Vérifier les services Docker** (`docker-compose ps`)
5. **Vérifier la connexion DB** (`doctrine:query:sql "SELECT 1"`)
6. **Vérifier les permissions** (var/, public/uploads/)
7. **Consulter le profiler** (`/_profiler`)
8. **Tester en mode verbose** (`-vvv`)

---

## Obtenir de l'aide

Si le problème persiste:

1. **Consulter les docs Symfony:** https://symfony.com/doc/current/
2. **Stack Overflow:** Rechercher l'erreur exacte
3. **GitHub Issues du projet:** Vérifier si le problème est connu
4. **Logs complets:** Toujours fournir les logs complets lors d'une demande d'aide

---

## Voir aussi

- [Docker](docker.md) - Troubleshooting Docker spécifique
- [Deployment](deployment.md) - Problèmes en production
- [Commands](commands.md) - Commandes utiles
- [Environment Variables](environment-variables.md) - Configuration
