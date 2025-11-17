# Guide de Déploiement en Production

Ce guide détaille les étapes pour déployer HotOnes en environnement de production.

## Table des matières

- [Prérequis](#prérequis)
- [Préparation du serveur](#préparation-du-serveur)
- [Installation](#installation)
- [Configuration](#configuration)
- [Build et optimisations](#build-et-optimisations)
- [Base de données](#base-de-données)
- [Serveur web](#serveur-web)
- [Workers Messenger](#workers-messenger)
- [Tâches planifiées (Cron)](#tâches-planifiées-cron)
- [Sauvegardes](#sauvegardes)
- [Mise à jour](#mise-à-jour)
- [Monitoring](#monitoring)
- [Sécurité](#sécurité)

---

## Prérequis

### Serveur recommandé

**Spécifications minimales:**
- OS: Ubuntu 22.04 LTS ou Debian 12
- CPU: 2 cores
- RAM: 4 GB
- Stockage: 40 GB SSD
- Réseau: IP publique, domaine configuré

**Spécifications recommandées (production):**
- CPU: 4+ cores
- RAM: 8+ GB
- Stockage: 100+ GB SSD
- Load balancer (si haute disponibilité)

---

### Logiciels requis

| Logiciel | Version minimale | Recommandée |
|----------|------------------|-------------|
| PHP | 8.4 | 8.4.x latest |
| MariaDB / MySQL | 11.4 / 8.0 | MariaDB 11.4 |
| Redis | 7.0 | 7.2 |
| Nginx | 1.24 | 1.27 |
| Composer | 2.0 | 2.7+ |
| Node.js | 18 | 20 LTS |
| Yarn | 1.22 | Latest |
| Supervisor | 4.0 | Latest |

---

## Préparation du serveur

### 1. Mise à jour du système

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common apt-transport-https
```

---

### 2. Installation de PHP 8.4

```bash
# Ajouter le dépôt Ondrej PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Installer PHP 8.4 et extensions requises
sudo apt install -y \
    php8.4-fpm \
    php8.4-cli \
    php8.4-mysql \
    php8.4-intl \
    php8.4-xml \
    php8.4-mbstring \
    php8.4-curl \
    php8.4-zip \
    php8.4-gd \
    php8.4-bcmath \
    php8.4-redis \
    php8.4-opcache \
    php8.4-apcu

# Vérifier l'installation
php -v
# Doit afficher: PHP 8.4.x
```

---

### 3. Installation de MariaDB

```bash
# Installer MariaDB 11.4
curl -LsS https://r.mariadb.com/downloads/mariadb_repo_setup | sudo bash -s -- --mariadb-server-version="mariadb-11.4"
sudo apt install -y mariadb-server mariadb-client

# Démarrer et activer au boot
sudo systemctl enable mariadb
sudo systemctl start mariadb

# Sécuriser l'installation
sudo mysql_secure_installation
```

---

### 4. Installation de Redis

```bash
sudo apt install -y redis-server

# Configuration de Redis pour production
sudo nano /etc/redis/redis.conf
```

**Modifications recommandées:**
```conf
# Bind sur localhost uniquement (sécurité)
bind 127.0.0.1

# Activer la persistence AOF
appendonly yes
appendfsync everysec

# Définir un mot de passe
requirepass YOUR_STRONG_REDIS_PASSWORD

# Limiter la mémoire (adapter selon vos besoins)
maxmemory 512mb
maxmemory-policy allkeys-lru
```

```bash
# Redémarrer Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

---

### 5. Installation de Nginx

```bash
sudo apt install -y nginx

# Démarrer et activer
sudo systemctl enable nginx
sudo systemctl start nginx
```

---

### 6. Installation de Composer

```bash
# Télécharger et installer Composer globalement
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Vérifier
composer --version
```

---

### 7. Installation de Node.js et Yarn

```bash
# Installer Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Installer Yarn
npm install -g yarn

# Vérifier
node -v
yarn -v
```

---

### 8. Installation de Supervisor

```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

---

## Installation

### 1. Créer un utilisateur dédié

```bash
# Créer l'utilisateur hotones
sudo useradd -m -s /bin/bash hotones

# Ajouter au groupe www-data
sudo usermod -a -G www-data hotones
```

---

### 2. Cloner le projet

```bash
# Passer en tant que hotones
sudo su - hotones

# Cloner le repository
cd /var/www
git clone https://github.com/votre-org/hotones.git
cd hotones

# Checkout de la branche de production
git checkout main
```

---

### 3. Installer les dépendances

```bash
# Dépendances PHP (production uniquement)
composer install --no-dev --optimize-autoloader --no-interaction

# Dépendances JavaScript
yarn install --frozen-lockfile --production
```

---

## Configuration

### 1. Variables d'environnement

```bash
# Créer le fichier .env.local (non committé)
nano .env.local
```

**Contenu `.env.local`:**

```bash
###> symfony/framework-bundle ###
APP_ENV=prod
APP_SECRET=GENERER_UN_SECRET_ALEATOIRE_SECURISE_32_CHARS_MIN
###< symfony/framework-bundle ###

###> database ###
DATABASE_URL="mysql://hotones_user:SECURE_DB_PASSWORD@localhost:3306/hotones_prod?serverVersion=mariadb-11.4.0&charset=utf8mb4"
###< database ###

###> symfony/messenger ###
MESSENGER_TRANSPORT_DSN=redis://:YOUR_REDIS_PASSWORD@localhost:6379/messages
###< symfony/messenger ###

###> symfony/mailer ###
MAILER_DSN=smtp://noreply@hotones.com:SMTP_PASSWORD@smtp.example.com:587?encryption=tls
###< symfony/mailer ###

###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=GENERER_PASSPHRASE_JWT_SECURISE
###< lexik/jwt-authentication-bundle ###

###> symfony/routing ###
DEFAULT_URI=https://hotones.example.com
###< symfony/routing ###
```

**Générer les secrets:**
```bash
# APP_SECRET
php -r "echo bin2hex(random_bytes(32));"

# JWT_PASSPHRASE
openssl rand -hex 32
```

**Permissions:**
```bash
chmod 600 .env.local
```

---

### 2. Générer les clés JWT

```bash
php bin/console lexik:jwt:generate-keypair

# Vérifier que les clés ont été créées
ls -la config/jwt/

# Sécuriser les permissions
chmod 600 config/jwt/*.pem
```

---

### 3. Créer la base de données

```bash
# Se connecter à MariaDB en tant que root
sudo mysql

# Dans le prompt MySQL
CREATE DATABASE hotones_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hotones_user'@'localhost' IDENTIFIED BY 'SECURE_DB_PASSWORD';
GRANT ALL PRIVILEGES ON hotones_prod.* TO 'hotones_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### 4. Exécuter les migrations

```bash
# Créer les tables
php bin/console doctrine:migrations:migrate --no-interaction

# Vérifier
php bin/console doctrine:schema:validate
```

---

## Build et optimisations

### 1. Compiler les assets

```bash
# Build production
yarn build

# Vérifier que les assets sont générés
ls -la public/build/
```

---

### 2. Optimiser l'autoloader Composer

```bash
composer dump-autoload --optimize --classmap-authoritative
```

---

### 3. Compiler les fichiers .env (optionnel mais recommandé)

```bash
composer dump-env prod

# Génère .env.local.php (optimisé)
```

---

### 4. Vider et warmer le cache

```bash
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod
```

---

### 5. Permissions sur les répertoires

```bash
# Propriétaire
sudo chown -R hotones:www-data /var/www/hotones

# Répertoires cache et logs en écriture
chmod -R 775 var/
sudo chown -R www-data:www-data var/

# Répertoire d'uploads
mkdir -p public/uploads/clients
chmod -R 775 public/uploads
sudo chown -R www-data:www-data public/uploads
```

---

## Serveur web

### Configuration Nginx

**Fichier:** `/etc/nginx/sites-available/hotones`

```nginx
server {
    listen 80;
    server_name hotones.example.com;

    # Redirection HTTPS (après configuration SSL)
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name hotones.example.com;

    root /var/www/hotones/public;
    index index.php;

    # SSL Certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/hotones.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/hotones.example.com/privkey.pem;

    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Upload limit
    client_max_body_size 20M;

    # Logs
    access_log /var/log/nginx/hotones_access.log;
    error_log /var/log/nginx/hotones_error.log;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Symfony routing
    location / {
        try_files $uri /index.php$is_args$args;
    }

    # PHP-FPM
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    # Cache static files
    location ~* \.(?:css|js|jpg|jpeg|gif|png|svg|ico|webp|woff|woff2|ttf|eot)$ {
        expires 1y;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known) {
        deny all;
    }

    # Return 404 for other PHP files
    location ~ \.php$ {
        return 404;
    }
}
```

**Activer le site:**
```bash
# Créer le lien symbolique
sudo ln -s /etc/nginx/sites-available/hotones /etc/nginx/sites-enabled/

# Tester la configuration
sudo nginx -t

# Recharger Nginx
sudo systemctl reload nginx
```

---

### SSL avec Let's Encrypt

```bash
# Installer Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtenir le certificat
sudo certbot --nginx -d hotones.example.com

# Auto-renouvellement (ajouté automatiquement en cron)
sudo certbot renew --dry-run
```

---

## Workers Messenger

### Configuration PHP-FPM

**Fichier:** `/etc/php/8.4/fpm/pool.d/www.conf`

**Modifications recommandées:**
```ini
; Augmenter les limites pour les workers
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

```bash
sudo systemctl restart php8.4-fpm
```

---

### Configuration Supervisor pour les Workers

**Fichier:** `/etc/supervisor/conf.d/hotones-messenger.conf`

```ini
[program:hotones-messenger]
command=/usr/bin/php /var/www/hotones/bin/console messenger:consume async --time-limit=3600 --memory-limit=256M -vv
user=hotones
numprocs=4
startsecs=0
autostart=true
autorestart=true
startretries=10
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=/var/www/hotones/var/log/messenger_%(process_num)02d.log
stderr_logfile=/var/www/hotones/var/log/messenger_%(process_num)02d_error.log
```

**Explications:**
- `numprocs=4`: 4 workers en parallèle
- `time-limit=3600`: Redémarrage automatique toutes les heures (évite memory leaks)
- `memory-limit=256M`: Limite mémoire par worker
- `autorestart=true`: Redémarrage automatique en cas de crash

**Activer:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start hotones-messenger:*

# Vérifier le statut
sudo supervisorctl status
```

**Commandes utiles:**
```bash
# Redémarrer les workers
sudo supervisorctl restart hotones-messenger:*

# Arrêter
sudo supervisorctl stop hotones-messenger:*

# Voir les logs
tail -f /var/www/hotones/var/log/messenger_*.log
```

---

## Tâches planifiées (Cron)

**Éditer le crontab de l'utilisateur hotones:**
```bash
sudo su - hotones
crontab -e
```

**Contenu:**
```cron
# Calcul des métriques analytics quotidien à 6h
0 6 * * * cd /var/www/hotones && php bin/console app:calculate-metrics --env=prod >> /var/www/hotones/var/log/cron.log 2>&1

# Calcul des métriques de staffing quotidien à 6h15
15 6 * * * cd /var/www/hotones && php bin/console app:calculate-staffing-metrics --range=12 --env=prod >> /var/www/hotones/var/log/cron.log 2>&1

# Rappel hebdomadaire de saisie des temps (vendredi 12h)
0 12 * * 5 cd /var/www/hotones && php bin/console app:notify:timesheets-weekly --env=prod >> /var/www/hotones/var/log/cron.log 2>&1

# Nettoyage du cache tous les jours à 3h
0 3 * * * cd /var/www/hotones && php bin/console cache:pool:clear cache.global_clearer --env=prod >> /var/www/hotones/var/log/cron.log 2>&1
```

---

## Sauvegardes

### Sauvegarde de la base de données

**Script:** `/var/www/hotones/scripts/backup-db.sh`

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/hotones/db"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="hotones_prod"
DB_USER="hotones_user"
DB_PASS="SECURE_DB_PASSWORD"

mkdir -p "$BACKUP_DIR"

# Dump de la base
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/hotones_$DATE.sql.gz"

# Garder seulement les 30 derniers jours
find "$BACKUP_DIR" -name "hotones_*.sql.gz" -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/hotones_$DATE.sql.gz"
```

**Permissions et cron:**
```bash
chmod +x /var/www/hotones/scripts/backup-db.sh

# Ajouter au crontab root
sudo crontab -e
```

```cron
# Backup quotidien à 2h du matin
0 2 * * * /var/www/hotones/scripts/backup-db.sh >> /var/log/hotones-backup.log 2>&1
```

---

### Sauvegarde des fichiers uploadés

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/hotones/files"
DATE=$(date +%Y%m%d)
UPLOAD_DIR="/var/www/hotones/public/uploads"

mkdir -p "$BACKUP_DIR"

# Tar + gzip des uploads
tar czf "$BACKUP_DIR/uploads_$DATE.tar.gz" -C "$UPLOAD_DIR" .

# Garder 30 jours
find "$BACKUP_DIR" -name "uploads_*.tar.gz" -mtime +30 -delete
```

---

## Mise à jour

### Procédure standard

```bash
# 1. Passer en mode maintenance (optionnel)
sudo nano /var/www/hotones/public/.maintenance.html
# Configurer Nginx pour servir cette page si elle existe

# 2. Se positionner dans le projet
cd /var/www/hotones

# 3. Récupérer les dernières modifications
git fetch origin
git pull origin main

# 4. Installer les dépendances
composer install --no-dev --optimize-autoloader --no-interaction
yarn install --frozen-lockfile --production

# 5. Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Recompiler les assets
yarn build

# 7. Vider le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# 8. Redémarrer les workers
sudo supervisorctl restart hotones-messenger:*

# 9. Recharger PHP-FPM
sudo systemctl reload php8.4-fpm

# 10. Retirer le mode maintenance
rm /var/www/hotones/public/.maintenance.html
```

---

## Monitoring

### Logs à surveiller

```bash
# Logs Symfony
tail -f /var/www/hotones/var/log/prod.log

# Logs Nginx
tail -f /var/log/nginx/hotones_error.log

# Logs Workers Messenger
tail -f /var/www/hotones/var/log/messenger_*.log

# Logs PHP-FPM
tail -f /var/log/php8.4-fpm.log
```

---

### Monitoring avec outils externes

**Recommandations:**
- **Sentry**: Monitoring d'erreurs PHP
- **New Relic / Blackfire**: Performance APM
- **Datadog / Prometheus**: Métriques serveur
- **UptimeRobot**: Uptime monitoring

**Installation Sentry (optionnel):**
```bash
composer require sentry/sentry-symfony
```

Dans `.env.local`:
```bash
SENTRY_DSN=https://your-key@sentry.io/project-id
```

---

## Sécurité

### 1. Firewall (UFW)

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

---

### 2. Fail2Ban (protection brute force)

```bash
sudo apt install -y fail2ban

# Configuration
sudo nano /etc/fail2ban/jail.local
```

```ini
[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
```

```bash
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

---

### 3. Permissions strictes

```bash
# Application en lecture seule pour www-data
find /var/www/hotones -type f -exec chmod 644 {} \;
find /var/www/hotones -type d -exec chmod 755 {} \;

# Var/ en écriture
chmod -R 775 /var/www/hotones/var
chown -R www-data:www-data /var/www/hotones/var

# Uploads en écriture
chmod -R 775 /var/www/hotones/public/uploads
chown -R www-data:www-data /var/www/hotones/public/uploads

# Secrets en lecture stricte
chmod 600 /var/www/hotones/.env.local
chmod 600 /var/www/hotones/config/jwt/*.pem
```

---

### 4. Désactiver les fonctions PHP dangereuses

**Fichier:** `/etc/php/8.4/fpm/php.ini`

```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

---

## Checklist finale

Avant de mettre en production:

- [ ] Variables d'environnement configurées (`.env.local`)
- [ ] Secrets générés aléatoirement (`APP_SECRET`, `JWT_PASSPHRASE`)
- [ ] Base de données créée et migrations exécutées
- [ ] Assets compilés (`yarn build`)
- [ ] Cache prod warmé
- [ ] SSL configuré (HTTPS)
- [ ] Nginx configuré et testé
- [ ] Workers Supervisor actifs
- [ ] Crons configurés
- [ ] Sauvegardes automatiques actives
- [ ] Monitoring en place
- [ ] Firewall activé
- [ ] Permissions correctes sur les fichiers
- [ ] Tests de smoke effectués

---

## Voir aussi

- [Environment Variables](environment-variables.md)
- [Docker](docker.md)
- [Commands](commands.md)
- [Worker Operations](worker-operations.md)
