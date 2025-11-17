# Variables d'Environnement

Ce document liste toutes les variables d'environnement utilisées dans le projet HotOnes, avec leurs valeurs par défaut et des exemples pour chaque environnement.

## Table des matières

- [Fichiers .env](#fichiers-env)
- [Variables Core Symfony](#variables-core-symfony)
- [Base de données](#base-de-données)
- [Messenger (Files d'attente)](#messenger-files-dattente)
- [Mailer (Envoi d'emails)](#mailer-envoi-demails)
- [JWT Authentication](#jwt-authentication)
- [Routing](#routing)
- [Environnements](#environnements)
- [Bonnes pratiques](#bonnes-pratiques)

---

## Fichiers .env

Symfony utilise un système de fichiers .env en cascade pour la configuration:

| Fichier | Usage | Committed | Priorité |
|---------|-------|-----------|----------|
| `.env` | Valeurs par défaut pour tous les environnements | ✅ Oui | 1 (basse) |
| `.env.local` | Overrides locaux (dev personnel) | ❌ Non | 2 |
| `.env.$APP_ENV` | Valeurs spécifiques à un environnement (ex: `.env.prod`) | ✅ Oui | 3 |
| `.env.$APP_ENV.local` | Overrides locaux spécifiques (ex: `.env.prod.local`) | ❌ Non | 4 (haute) |

**Ordre de chargement:** Les fichiers sont chargés dans l'ordre ci-dessus, les derniers écrasant les précédents.

**Variables d'environnement réelles:** Les vraies variables d'environnement système ont la priorité la plus élevée.

**Documentation officielle:** https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables

---

## Variables Core Symfony

### APP_ENV

**Description:** Environnement d'exécution de l'application.

**Valeurs possibles:**
- `dev`: Développement (debug activé, profiler, erreurs détaillées)
- `prod`: Production (optimisations, erreurs masquées, cache)
- `test`: Tests automatisés (fixtures, isolation DB)

**Obligatoire:** ✅ Oui

**Exemples:**

```bash
# Développement
APP_ENV=dev

# Production
APP_ENV=prod

# Tests
APP_ENV=test
```

**Impact:**
- Active/désactive le mode debug
- Charge les configurations spécifiques: `config/packages/$APP_ENV/`
- Modifie le comportement du cache
- Change les logs et le profiler

---

### APP_SECRET

**Description:** Secret utilisé pour la génération de tokens CSRF, hashage de sessions, etc.

**Format:** Chaîne de caractères aléatoire (minimum 32 caractères recommandés)

**Obligatoire:** ✅ Oui (surtout en production)

**Génération:**
```bash
# Générer un secret aléatoire
php -r "echo bin2hex(random_bytes(32));"
# ou
openssl rand -hex 32
```

**Exemples:**

```bash
# Développement (peut être vide ou simple)
APP_SECRET=dev_secret_not_secure

# Production (DOIT être aléatoire et sécurisé)
APP_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
```

**Sécurité:**
- ⚠️ **Ne JAMAIS committer un secret de production**
- Utiliser `.env.local` ou `.env.prod.local` (non committé)
- Ou variables d'environnement système
- Ou Symfony Secrets Vault en production

---

## Base de données

### DATABASE_URL

**Description:** Chaîne de connexion à la base de données.

**Format:** DSN (Data Source Name)
```
mysql://user:password@host:port/database?serverVersion=X&charset=utf8mb4
```

**Obligatoire:** ✅ Oui

**Composants:**
- `user`: Utilisateur de la base de données
- `password`: Mot de passe
- `host`: Hôte (IP, nom de domaine ou nom de service Docker)
- `port`: Port (3306 pour MySQL/MariaDB)
- `database`: Nom de la base de données
- `serverVersion`: Version du serveur (important pour Doctrine)
- `charset`: Encodage des caractères (toujours `utf8mb4`)

**Exemples:**

```bash
# Docker Compose (dev)
# Service Docker nommé "db", MariaDB 11.4
DATABASE_URL="mysql://symfony:symfony@db:3306/hotones?serverVersion=mariadb-11.4.0&charset=utf8mb4"

# MySQL local (dev)
DATABASE_URL="mysql://root:root@127.0.0.1:3306/hotones?serverVersion=8.0.32&charset=utf8mb4"

# Production (credentials sécurisés)
DATABASE_URL="mysql://hotones_user:SuperSecurePass123@db.example.com:3306/hotones_prod?serverVersion=mariadb-11.4.0&charset=utf8mb4"

# PostgreSQL (alternative)
DATABASE_URL="postgresql://user:pass@db:5432/hotones?serverVersion=15&charset=utf8"
```

**Paramètres supplémentaires:**

```bash
# SSL/TLS
DATABASE_URL="mysql://user:pass@host:3306/db?serverVersion=mariadb-11.4.0&charset=utf8mb4&sslmode=require"

# Timezone
DATABASE_URL="mysql://user:pass@host:3306/db?serverVersion=mariadb-11.4.0&charset=utf8mb4&default_table_options[charset]=utf8mb4&default_table_options[collate]=utf8mb4_unicode_ci"
```

**Sécurité:**
- ⚠️ Ne jamais committer les credentials de production
- Utiliser des utilisateurs dédiés avec permissions limitées
- Activer SSL/TLS en production

**Voir aussi:** [Database Documentation](database.md), [DATABASE-CONNECTION.md](../DATABASE-CONNECTION.md)

---

## Messenger (Files d'attente)

### MESSENGER_TRANSPORT_DSN

**Description:** Configuration du transport pour les messages asynchrones (jobs en arrière-plan).

**Transports supportés:**
- Redis
- RabbitMQ (AMQP)
- Doctrine (base de données)

**Obligatoire:** Non (optionnel, utilisé pour asynchrone)

**Exemples:**

```bash
# Redis (recommandé pour production)
# Service Docker "redis"
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages

# Redis avec authentification
MESSENGER_TRANSPORT_DSN=redis://password@redis:6379/messages

# Redis local
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages

# RabbitMQ
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages

# Doctrine (base de données) - simple mais moins performant
MESSENGER_TRANSPORT_DSN=doctrine://default

# Désactiver (mode synchrone)
MESSENGER_TRANSPORT_DSN=sync://
```

**Configuration associée:** `config/packages/messenger.yaml`

**Workers:**
```bash
# Consommer les messages en arrière-plan
php bin/console messenger:consume async -vv
```

**Voir aussi:** [Worker Operations Documentation](worker-operations.md)

---

## Mailer (Envoi d'emails)

### MAILER_DSN

**Description:** Configuration du transport d'envoi d'emails.

**Transports supportés:**
- SMTP
- Sendmail
- Gmail
- Amazon SES
- Mailgun
- Postmark
- Null (dev, ne pas envoyer)

**Obligatoire:** Non (défaut: `null://null`)

**Exemples:**

```bash
# Développement - Ne pas envoyer (logs uniquement)
MAILER_DSN=null://null

# SMTP standard
MAILER_DSN=smtp://user:password@smtp.example.com:587

# SMTP avec TLS
MAILER_DSN=smtp://user:password@smtp.example.com:587?encryption=tls

# Gmail
MAILER_DSN=gmail://username:password@default

# Gmail avec App Password (recommandé)
MAILER_DSN=gmail://username:app_password@default

# Mailgun
MAILER_DSN=mailgun://KEY:DOMAIN@default?region=eu

# Amazon SES
MAILER_DSN=ses://ACCESS_KEY:SECRET_KEY@default?region=eu-west-1

# Postmark
MAILER_DSN=postmark://TOKEN@default

# Sendmail (local)
MAILER_DSN=sendmail://default
```

**Configuration du "From":**
Dans `config/packages/mailer.yaml`:
```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
        envelope:
            sender: 'noreply@hotones.example.com'
        headers:
            From: 'HotOnes <noreply@hotones.example.com>'
```

**Voir aussi:** [Notifications Documentation](notifications.md)

---

## JWT Authentication

Le projet utilise Lexik JWT Authentication Bundle pour l'authentification API.

### JWT_SECRET_KEY

**Description:** Chemin vers la clé privée pour signer les tokens JWT.

**Format:** Chemin de fichier absolu ou relatif avec `%kernel.project_dir%`

**Obligatoire:** ✅ Oui (si JWT utilisé)

**Exemples:**

```bash
# Relatif au projet
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem

# Absolu
JWT_SECRET_KEY=/var/www/hotones/config/jwt/private.pem
```

---

### JWT_PUBLIC_KEY

**Description:** Chemin vers la clé publique pour vérifier les tokens JWT.

**Format:** Chemin de fichier

**Obligatoire:** ✅ Oui (si JWT utilisé)

**Exemples:**

```bash
# Relatif au projet
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem

# Absolu
JWT_PUBLIC_KEY=/var/www/hotones/config/jwt/public.pem
```

---

### JWT_PASSPHRASE

**Description:** Passphrase pour déchiffrer la clé privée JWT.

**Format:** Chaîne de caractères (générée lors de la création des clés)

**Obligatoire:** ✅ Oui (si les clés sont protégées par passphrase)

**Exemples:**

```bash
# Passphrase aléatoire (générée)
JWT_PASSPHRASE=***REDACTED_JWT_PASSPHRASE***

# Production (différente, sécurisée)
JWT_PASSPHRASE=super_secure_production_passphrase_xyz789
```

**Génération des clés JWT:**

```bash
# Générer la paire de clés avec passphrase
php bin/console lexik:jwt:generate-keypair

# Les clés seront créées dans config/jwt/
# private.pem et public.pem
```

**Sécurité:**
- ⚠️ Ne jamais committer le `JWT_PASSPHRASE` de production
- Les clés `*.pem` sont déjà dans `.gitignore`
- Permissions strictes sur les fichiers: `chmod 600 config/jwt/*.pem`

**Voir aussi:** [API Documentation](api.md)

---

## Routing

### DEFAULT_URI

**Description:** URL de base pour la génération d'URLs absolues dans les contextes non-HTTP (commandes CLI, workers).

**Format:** URL complète avec schéma

**Obligatoire:** Non (mais recommandé si génération d'URLs dans les commandes)

**Exemples:**

```bash
# Développement
DEFAULT_URI=http://localhost:8080

# Production
DEFAULT_URI=https://hotones.example.com

# Environnement de staging
DEFAULT_URI=https://staging.hotones.example.com
```

**Usage typique:**
- Génération de liens dans les emails
- Webhooks
- Commandes CLI générant des URLs

**Voir aussi:** https://symfony.com/doc/current/routing.html#generating-urls-in-commands

---

## Environnements

### Configuration Développement (.env.local)

```bash
# .env.local (non committé)
APP_ENV=dev
APP_SECRET=dev_secret_change_in_prod

DATABASE_URL="mysql://symfony:symfony@db:3306/hotones?serverVersion=mariadb-11.4.0&charset=utf8mb4"
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages
MAILER_DSN=null://null

DEFAULT_URI=http://localhost:8080

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=local_dev_passphrase
```

---

### Configuration Production (.env.prod.local)

```bash
# .env.prod.local (non committé, sur le serveur uniquement)
APP_ENV=prod
APP_SECRET=1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t1u2v3w4x5y6z  # Généré aléatoirement

DATABASE_URL="mysql://hotones_prod:SECURE_PASSWORD@db.internal.example.com:3306/hotones_production?serverVersion=mariadb-11.4.0&charset=utf8mb4"
MESSENGER_TRANSPORT_DSN=redis://:REDIS_PASSWORD@redis.internal.example.com:6379/messages
MAILER_DSN=smtp://noreply@hotones.example.com:SMTP_PASSWORD@smtp.example.com:587?encryption=tls

DEFAULT_URI=https://hotones.example.com

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=PRODUCTION_JWT_PASSPHRASE_SUPER_SECURE
```

**Optimisations production:**

Compiler les fichiers .env pour de meilleures performances:
```bash
composer dump-env prod
```

Cela génère un fichier `.env.local.php` optimisé.

---

### Configuration Tests (.env.test)

```bash
# .env.test (committé)
APP_ENV=test
APP_SECRET=test_secret_not_important

# Base de données de test (séparée)
DATABASE_URL="mysql://symfony:symfony@db:3306/hotones_test?serverVersion=mariadb-11.4.0&charset=utf8mb4"

# Pas d'async en tests
MESSENGER_TRANSPORT_DSN=sync://

# Pas d'envoi d'emails en tests
MAILER_DSN=null://null

DEFAULT_URI=http://localhost
```

**Isolation des tests:** La base de données de test est séparée et reset entre chaque test (via DAMA Doctrine Test Bundle).

---

## Bonnes pratiques

### 1. Ne jamais committer de secrets

**Fichiers à ne JAMAIS committer:**
- `.env.local`
- `.env.prod.local`
- `.env.*.local`
- Fichiers contenant des mots de passe ou clés API

**Vérifier le .gitignore:**
```gitignore
.env.local
.env.*.local
.env.local.php
config/jwt/*.pem
```

---

### 2. Utiliser Symfony Secrets en production

Pour une sécurité maximale en production:

```bash
# Créer le vault de secrets
php bin/console secrets:set DATABASE_PASSWORD

# Lister les secrets
php bin/console secrets:list

# Utiliser dans .env.prod
DATABASE_URL="mysql://user:%env(DATABASE_PASSWORD)%@host:3306/db"
```

**Documentation:** https://symfony.com/doc/current/configuration/secrets.html

---

### 3. Variables obligatoires vs optionnelles

**Toujours définir:**
- `APP_ENV`
- `APP_SECRET`
- `DATABASE_URL`

**Définir si utilisé:**
- `MESSENGER_TRANSPORT_DSN` (si asynchrone)
- `MAILER_DSN` (si envoi d'emails)
- `JWT_*` (si authentification API)

**Optionnel:**
- `DEFAULT_URI` (génération d'URLs dans CLI)

---

### 4. Validation des variables

Créer un script de vérification:

```bash
#!/bin/bash
# scripts/check-env.sh

required_vars=("APP_ENV" "APP_SECRET" "DATABASE_URL")

for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ]; then
        echo "Error: $var is not set"
        exit 1
    fi
done

echo "All required environment variables are set"
```

---

### 5. Documentation pour l'équipe

Créer un `.env.example` documenté:

```bash
# .env.example
# Copier ce fichier vers .env.local et adapter les valeurs

###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=change_me_in_production
###< symfony/framework-bundle ###

###> database ###
# Format: mysql://user:password@host:port/database?serverVersion=X&charset=utf8mb4
DATABASE_URL="mysql://symfony:symfony@db:3306/hotones?serverVersion=mariadb-11.4.0&charset=utf8mb4"
###< database ###

# ... etc
```

---

### 6. Variables par environnement

Organiser les variables clairement:

```bash
# .env - Valeurs communes par défaut (committé)
APP_ENV=dev
DATABASE_URL="mysql://symfony:symfony@db:3306/hotones?serverVersion=mariadb-11.4.0&charset=utf8mb4"

# .env.local - Overrides locaux (non committé)
APP_SECRET=my_local_secret

# .env.prod - Valeurs production (committé)
APP_ENV=prod
MESSENGER_TRANSPORT_DSN=redis://:password@redis:6379/messages

# .env.prod.local - Secrets production (non committé, sur serveur)
APP_SECRET=super_secure_random_secret
DATABASE_URL="mysql://prod_user:prod_pass@prod_host:3306/prod_db?serverVersion=mariadb-11.4.0&charset=utf8mb4"
```

---

## Variables supplémentaires possibles

Selon les besoins du projet, vous pourriez ajouter:

```bash
# Upload de fichiers
MAX_UPLOAD_SIZE=10M

# Redis cache (séparé de Messenger)
REDIS_CACHE_DSN=redis://redis:6379/cache

# Sentry (monitoring d'erreurs)
SENTRY_DSN=https://key@sentry.io/project

# Feature flags
FEATURE_ANALYTICS_ENABLED=true
FEATURE_STAFFING_DASHBOARD_ENABLED=true

# External APIs
STRIPE_API_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Timezone
APP_TIMEZONE=Europe/Paris
```

---

## Debugging

### Afficher toutes les variables d'environnement

```bash
# Dans un contrôleur/commande
dump($_ENV);
dump($_SERVER);

# Via CLI
php bin/console debug:container --env-vars
```

### Vérifier une variable spécifique

```bash
php bin/console debug:container --env-var=DATABASE_URL
```

### Vérifier le fichier .env chargé

```bash
# Symfony affiche les fichiers .env chargés au démarrage en mode verbose
php bin/console about -vv
```

---

## Voir aussi

- [Installation](installation.md) - Setup initial et configuration
- [Deployment](deployment.md) - Configuration production
- [Docker](docker.md) - Variables dans Docker Compose
- [Security](security.md) - Gestion des secrets
- [DATABASE-CONNECTION.md](../DATABASE-CONNECTION.md) - Configuration DB détaillée
