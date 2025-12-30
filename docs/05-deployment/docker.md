# Configuration Docker

Ce document détaille l'architecture Docker du projet HotOnes et explique la configuration de chaque service.

## Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Architecture](#architecture)
- [Services](#services)
- [Dockerfile Multi-stage](#dockerfile-multi-stage)
- [Configuration Nginx](#configuration-nginx)
- [Volumes](#volumes)
- [Réseau](#réseau)
- [Commandes Docker](#commandes-docker)
- [Optimisations](#optimisations)
- [Troubleshooting](#troubleshooting)

---

## Vue d'ensemble

Le projet utilise **Docker Compose** pour orchestrer 4 services:

| Service | Image | Rôle | Port exposé |
|---------|-------|------|-------------|
| **app** | Custom (PHP 8.4 FPM) | Application Symfony | - |
| **web** | nginx:1.27-alpine | Serveur web (reverse proxy) | 8080 → 80 |
| **db** | mariadb:11.4 | Base de données | 3307 → 3306 |
| **redis** | redis:7-alpine | Cache & Queue (Messenger) | 6379 |

**Stack complète:**
```
[Client] → [Nginx:8080] → [PHP-FPM] ↔ [MariaDB:3307]
                                    ↔ [Redis:6379]
```

---

## Architecture

### Schéma de communication

```
┌─────────────────────────────────────────────────────────┐
│                  Docker Network (default)                │
│                                                           │
│  ┌──────────┐      ┌──────────┐      ┌──────────┐       │
│  │   web    │─────▶│   app    │─────▶│    db    │       │
│  │  Nginx   │      │ PHP-FPM  │      │ MariaDB  │       │
│  │  :80     │      │  :9000   │      │  :3306   │       │
│  └────┬─────┘      └─────┬────┘      └──────────┘       │
│       │                  │                                │
│    Port 8080             │            ┌──────────┐       │
│    (exposé)              └───────────▶│  redis   │       │
│                                       │  :6379   │       │
│                                       └──────────┘       │
└───────────────────────────────────────────────────────────┘
         │
    [Host Machine]
```

### Flux de requête

1. Client → `localhost:8080` (nginx)
2. Nginx → Reverse proxy vers `app:9000` (PHP-FPM)
3. PHP-FPM → Exécute Symfony
4. Symfony → Connecte à `db:3306` et `redis:6379`
5. Réponse inversée jusqu'au client

---

## Services

### Service: app (PHP-FPM)

**Rôle:** Exécute l'application Symfony via PHP-FPM.

**Configuration (`docker-compose.yml`):**
```yaml
app:
  build:
    context: .
    dockerfile: Dockerfile
  container_name: hotones_app
  working_dir: /var/www/html
  volumes:
    - ./:/var/www/html:cached
  environment:
    APP_ENV: dev
  depends_on:
    - db
    - redis
```

**Caractéristiques:**
- Image custom construite depuis `Dockerfile`
- Volume bind-mount du code source (`:cached` pour performances macOS/Windows)
- Working directory: `/var/www/html`
- Démarre après `db` et `redis`

**Ports:**
- Interne: 9000 (PHP-FPM, non exposé à l'hôte)

**Variables d'environnement:**
- `APP_ENV=dev`: Mode développement
- Autres variables lues depuis `.env`

---

### Service: web (Nginx)

**Rôle:** Serveur web servant les fichiers statiques et proxy FastCGI vers PHP-FPM.

**Configuration:**
```yaml
web:
  image: nginx:1.27-alpine
  container_name: hotones_web
  ports:
    - "8080:80"
  volumes:
    - ./:/var/www/html:cached
    - ./docker/nginx/conf.d/default.conf:/etc/nginx/conf.d/default.conf:ro
  depends_on:
    - app
```

**Caractéristiques:**
- Image officielle Nginx Alpine (légère)
- Accès à l'application via `http://localhost:8080`
- Configuration custom dans `docker/nginx/conf.d/default.conf` (read-only)
- Accès au code source pour servir les assets statiques

**Ports:**
- `8080:80`: Port 80 du container mappé sur 8080 de l'hôte

**Configuration Nginx détaillée:** Voir [section Configuration Nginx](#configuration-nginx)

---

### Service: db (MariaDB)

**Rôle:** Base de données relationnelle.

**Configuration:**
```yaml
db:
  image: mariadb:11.4
  container_name: hotones_db
  environment:
    MYSQL_DATABASE: hotones
    MYSQL_USER: symfony
    MYSQL_PASSWORD: symfony
    MYSQL_ROOT_PASSWORD: root
  ports:
    - "3307:3306"
  volumes:
    - db-data:/var/lib/mysql
```

**Caractéristiques:**
- MariaDB 11.4 (compatible MySQL)
- Données persistées dans le volume nommé `db-data`
- Utilisateur applicatif: `symfony` / `symfony`
- Utilisateur root: `root` / `root`

**Ports:**
- `3307:3306`: Port 3306 mappé sur 3307 de l'hôte (évite conflit avec MySQL local)

**Connexion depuis l'hôte:**
```bash
mysql -h 127.0.0.1 -P 3307 -u symfony -psymfony hotones
```

**Connexion depuis le container app:**
```bash
# Via le nom de service DNS (automatique)
mysql -h db -P 3306 -u symfony -psymfony hotones
```

**Variables d'environnement:**
- `MYSQL_DATABASE`: Nom de la base créée au démarrage
- `MYSQL_USER` / `MYSQL_PASSWORD`: Credentials utilisateur
- `MYSQL_ROOT_PASSWORD`: Mot de passe root

---

### Service: redis (Cache & Queue)

**Rôle:** Cache et file d'attente pour Symfony Messenger.

**Configuration:**
```yaml
redis:
  image: redis:7-alpine
  container_name: hotones_redis
  ports:
    - "6379:6379"
  command: ["redis-server", "--appendonly", "yes"]
```

**Caractéristiques:**
- Redis 7 Alpine (léger)
- Mode AOF (Append Only File) activé pour la persistence
- Port standard 6379 exposé

**Ports:**
- `6379:6379`: Accès direct depuis l'hôte

**Persistence:**
- `--appendonly yes`: Active la persistence sur disque (AOF)
- Données sauvegardées même après redémarrage du container

**Connexion depuis l'hôte:**
```bash
redis-cli -h localhost -p 6379
```

**Connexion depuis le container app:**
```bash
# Via le nom de service DNS
redis-cli -h redis -p 6379
```

**Usage dans Symfony:**
- Cache: Doctrine metadata, application cache
- Messenger: File d'attente asynchrone (voir `MESSENGER_TRANSPORT_DSN`)

---

## Dockerfile Multi-stage

Le `Dockerfile` utilise une approche **multi-stage** pour optimiser la taille de l'image finale.

### Stage 1: Build Assets (Node.js)

```dockerfile
FROM node:18-alpine AS assets
WORKDIR /app
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile
COPY assets/ assets/
COPY webpack.config.js ./
RUN yarn build
```

**Rôle:**
- Compile les assets frontend (JS, SCSS) avec Webpack Encore
- Génération dans `/app/public/assets/`

**Avantages:**
- Isolation du build frontend
- Image finale ne contient pas Node.js ni les node_modules

---

### Stage 2: PHP Application

```dockerfile
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash git unzip \
    libzip-dev icu-dev oniguruma-dev \
    mariadb-client tzdata shadow \
    freetype-dev libjpeg-turbo-dev libpng-dev \
  && docker-php-ext-configure intl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && pecl install apcu redis \
  && docker-php-ext-enable apcu redis \
  && docker-php-ext-install \
    pdo pdo_mysql intl opcache gd bcmath zip \
  && rm -rf /var/cache/apk/*
```

**Extensions PHP installées:**

| Extension | Usage |
|-----------|-------|
| **pdo, pdo_mysql** | Connexion MariaDB/MySQL |
| **intl** | Internationalisation (dates, formats) |
| **opcache** | Cache d'opcode (performance) |
| **gd** | Manipulation d'images (logos clients, QR codes) |
| **bcmath** | Calculs décimaux précis (finances) |
| **zip** | Compression (exports) |
| **apcu** | Cache en mémoire (APCu) |
| **redis** | Client Redis (cache & Messenger) |

**Dépendances système:**
- `bash`: Shell (scripts)
- `git`: Versionning (composer require)
- `unzip`: Extraction (composer install)
- `mariadb-client`: CLI MySQL (debug)
- `tzdata`: Fuseaux horaires

---

### Installation de Composer

```dockerfile
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
```

**Utilise l'image officielle Composer 2** via multi-stage copy.

---

### Configuration PHP

```dockerfile
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/php.ini
```

**Fichier `docker/php/php.ini`:** Configuration PHP personnalisée (limites d'upload, timezone, etc.)

**Exemple de contenu:**
```ini
; Memory
memory_limit = 256M

; Uploads
upload_max_filesize = 20M
post_max_size = 20M

; Timezone
date.timezone = Europe/Paris

; OPcache (production)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
```

---

### Copy des assets compilés

```dockerfile
COPY --from=assets /app/public/assets/ public/assets/
```

**Récupère les assets compilés depuis le stage 1.**

---

### Permissions et utilisateur

```dockerfile
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data || true
RUN chown -R www-data:www-data /var/www/html
USER www-data
```

**Optimisation pour le développement local:**
- UID/GID 1000 correspond généralement à l'utilisateur principal Linux/macOS
- Évite les problèmes de permissions sur les fichiers bind-mountés
- Conteneur s'exécute en tant que `www-data` (non-root, sécurité)

---

## Configuration Nginx

**Fichier:** `docker/nginx/conf.d/default.conf`

### Paramètres globaux

```nginx
client_max_body_size 20m;
```

**Limite de taille d'upload:** 20 MB (logos clients, fichiers joints)

---

### Virtual host

```nginx
server {
    listen 80 default_server;
    server_name _;

    root /var/www/html/public;
    index index.php;

    access_log /var/log/nginx/access.log;
    error_log  /var/log/nginx/error.log;
```

**Configuration:**
- Écoute sur port 80 (interne au container)
- Document root: `/var/www/html/public` (répertoire public Symfony)
- Logs dans `/var/log/nginx/` (accessibles via `docker logs`)

---

### Routing principal

```nginx
location / {
    try_files $uri /index.php$is_args$args;
}
```

**Comportement:**
1. Essaie de servir le fichier statique directement (`$uri`)
2. Sinon, redirige vers `/index.php` avec les query parameters

**Résultat:** Toutes les routes Symfony passent par le front controller `public/index.php`

---

### Proxy FastCGI (PHP)

```nginx
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    fastcgi_param DOCUMENT_ROOT $realpath_root;
    fastcgi_pass app:9000;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    internal;
}
```

**Explication:**
- `fastcgi_pass app:9000`: Envoie à PHP-FPM sur le service `app` port 9000
- `internal`: Cette location n'est accessible que via `try_files`, pas directement
- Buffers augmentés pour les réponses volumineuses

---

### Cache des assets statiques

```nginx
location ~* \.(?:css|js|jpg|jpeg|gif|png|svg|ico|webp)$ {
    try_files $uri /index.php$is_args$args;
    expires 1w;
    access_log off;
    add_header Cache-Control "public";
}

location ~* \.(?:ttf|ttc|otf|eot|woff|woff2)$ {
    try_files $uri /index.php$is_args$args;
    expires 1w;
    access_log off;
    add_header Cache-Control "public";
}
```

**Optimisations:**
- Cache navigateur de 1 semaine (`expires 1w`)
- Pas de logs d'accès pour les assets (performance)
- Header `Cache-Control: public` (cache par CDN/proxies)

---

### Sécurité: Fichiers cachés

```nginx
location ~ /\.[^/]+ { deny all; }
```

**Bloque l'accès aux fichiers commençant par `.`:**
- `.env` (secrets)
- `.git/` (code source)
- `.dockerignore`, `.gitignore`, etc.

**Sécurité critique en production.**

---

## Volumes

### Volume bind-mount (code source)

```yaml
volumes:
  - ./:/var/www/html:cached
```

**Type:** Bind mount
**Source:** Répertoire courant de l'hôte (`./`)
**Destination:** `/var/www/html` dans les containers `app` et `web`
**Option `cached`:** Optimisation performances macOS/Windows (légère latence acceptable)

**Avantages:**
- Modifications du code instantanément visibles dans les containers
- Hot-reload pour le développement

**Alternatives pour production:**
- Volume Docker nommé
- COPY dans le Dockerfile (image auto-suffisante)

---

### Volume bind-mount (Nginx config)

```yaml
volumes:
  - ./docker/nginx/conf.d/default.conf:/etc/nginx/conf.d/default.conf:ro
```

**Option `:ro`:** Read-only (sécurité)

---

### Volume nommé (données MariaDB)

```yaml
volumes:
  db-data:

services:
  db:
    volumes:
      - db-data:/var/lib/mysql
```

**Type:** Volume Docker nommé (géré par Docker)
**Persistence:** Les données survivent aux `docker-compose down`

**Commandes utiles:**

```bash
# Lister les volumes
docker volume ls

# Inspecter le volume
docker volume inspect hotones_db-data

# Backup du volume
docker run --rm -v hotones_db-data:/data -v $(pwd):/backup alpine tar czf /backup/db-backup.tar.gz -C /data .

# Restore
docker run --rm -v hotones_db-data:/data -v $(pwd):/backup alpine sh -c "cd /data && tar xzf /backup/db-backup.tar.gz"

# Supprimer le volume (⚠️ perte de données)
docker volume rm hotones_db-data
```

---

## Réseau

### Réseau par défaut

Docker Compose crée automatiquement un réseau bridge nommé `hotones_default`.

**Communication inter-services:**
- Résolution DNS automatique par nom de service
- `app` peut atteindre `db` via `db:3306`
- `app` peut atteindre `redis` via `redis:6379`

**Isolation:**
- Les containers ne sont accessibles de l'extérieur que via les ports exposés
- `app` (PHP-FPM) n'est pas exposé, uniquement accessible via `web` (Nginx)

---

## Commandes Docker

### Démarrage

```bash
# Démarrer tous les services
docker-compose up -d

# Démarrer et rebuild
docker-compose up -d --build

# Voir les logs en temps réel
docker-compose logs -f

# Logs d'un service spécifique
docker-compose logs -f app
```

---

### Arrêt

```bash
# Arrêter sans supprimer les containers
docker-compose stop

# Arrêter et supprimer les containers
docker-compose down

# Arrêter, supprimer containers ET volumes (⚠️ perte de données)
docker-compose down -v
```

---

### Exécution de commandes

```bash
# Ouvrir un shell dans le container app
docker-compose exec app bash

# Exécuter une commande Symfony
docker-compose exec app php bin/console cache:clear

# Exécuter Composer
docker-compose exec app composer install

# Exécuter les migrations
docker-compose exec app php bin/console doctrine:migrations:migrate

# Se connecter à MariaDB
docker-compose exec db mysql -u symfony -psymfony hotones

# Se connecter à Redis
docker-compose exec redis redis-cli
```

---

### Rebuild

```bash
# Rebuild complet de l'image app
docker-compose build app

# Rebuild sans cache
docker-compose build --no-cache app

# Rebuild et redémarrer
docker-compose up -d --build
```

---

### Inspection

```bash
# Lister les containers actifs
docker-compose ps

# Statistiques en temps réel
docker stats

# Inspecter un service
docker-compose config

# Voir les variables d'environnement
docker-compose exec app env
```

---

## Optimisations

### Développement

**1. Éviter les rebuilds constants:**
```yaml
# Utiliser un volume pour vendor/
volumes:
  - ./:/var/www/html:cached
  - /var/www/html/vendor  # Exclure vendor du bind-mount
```

**2. Désactiver Xdebug si non utilisé:**
```dockerfile
# Dans Dockerfile, commenter pecl install xdebug
```

**3. Augmenter les ressources Docker Desktop:**
- CPU: 4+ cores
- RAM: 4+ GB
- Swap: 2+ GB

---

### Production

**1. Multi-stage build optimisé:**
```dockerfile
# Stage final: seulement les fichiers nécessaires
COPY --from=builder /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative
```

**2. Variables d'environnement:**
```yaml
environment:
  APP_ENV: prod
  APP_DEBUG: 0
```

**3. OPcache activé:**
```ini
opcache.enable=1
opcache.validate_timestamps=0  # Ne jamais revalider en prod
```

**4. Pas de bind-mount en production:**
```dockerfile
COPY . /var/www/html
```

**5. Utiliser des secrets Docker:**
```yaml
secrets:
  db_password:
    file: ./secrets/db_password.txt

services:
  app:
    secrets:
      - db_password
```

---

## Troubleshooting

### Problème: Port déjà utilisé

**Erreur:**
```
Error starting userland proxy: listen tcp4 0.0.0.0:8080: bind: address already in use
```

**Solutions:**
```bash
# Changer le port dans docker-compose.yml
ports:
  - "8081:80"  # Utiliser 8081 au lieu de 8080

# Ou arrêter le service utilisant le port
lsof -ti:8080 | xargs kill -9
```

---

### Problème: Permissions denied

**Erreur:**
```
Warning: file_put_contents(/var/www/html/var/cache/...): failed to open stream: Permission denied
```

**Solutions:**
```bash
# Depuis l'hôte
sudo chown -R 1000:1000 var/

# Depuis le container
docker-compose exec app chmod -R 777 var/
```

---

### Problème: Database connection refused

**Erreur:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Vérifications:**
```bash
# Vérifier que le service db est démarré
docker-compose ps

# Vérifier les logs de MariaDB
docker-compose logs db

# Vérifier la connectivité depuis app
docker-compose exec app ping db

# Tester la connexion MySQL
docker-compose exec app mysql -h db -u symfony -psymfony -e "SELECT 1"
```

**Cause fréquente:** Le service `app` démarre avant que MariaDB soit prêt.

**Solution:** Ajouter un healthcheck ou attendre manuellement:
```bash
docker-compose up -d db
sleep 10
docker-compose up -d
```

---

### Problème: Redis connection failed

**Vérifications:**
```bash
# Tester Redis
docker-compose exec app sh -c 'echo "PING" | redis-cli -h redis'

# Vérifier MESSENGER_TRANSPORT_DSN
docker-compose exec app env | grep MESSENGER
```

---

### Problème: Assets non compilés

**Solution:**
```bash
# Rebuild le stage assets
docker-compose build --no-cache app

# Ou compiler manuellement
docker-compose exec app yarn install
docker-compose exec app yarn build
```

---

### Logs complets

```bash
# Tous les services
docker-compose logs -f --tail=100

# Service spécifique
docker-compose logs -f --tail=100 app
docker-compose logs -f --tail=100 web
docker-compose logs -f --tail=100 db
```

---

## Voir aussi

- [Installation](installation.md) - Guide d'installation initial
- [Environment Variables](environment-variables.md) - Variables d'environnement
- [DATABASE-CONNECTION.md](../DATABASE-CONNECTION.md) - Connexion base de données
- [Deployment](deployment.md) - Déploiement en production
