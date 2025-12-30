# Migration MySQL → PostgreSQL pour Render

Guide pour migrer HotOnes de MySQL/MariaDB vers PostgreSQL et profiter du plan gratuit Render.

## Pourquoi PostgreSQL ?

- ✅ **Plan gratuit Render** : PostgreSQL inclus gratuitement (90 jours puis $7/mois)
- ✅ **Supabase gratuit** : Alternative avec 500MB gratuit permanent
- ✅ **Neon gratuit** : Autre alternative avec 512MB gratuit
- ✅ **Performance** : PostgreSQL est excellent pour les analytics/KPIs
- ✅ **Compatibilité Symfony** : Support natif complet

## Modifications nécessaires

### 1. Composer dependencies

```bash
composer require symfony/orm-pack
composer require doctrine/dbal:^3.0
```

Aucun package supplémentaire nécessaire, Doctrine supporte PostgreSQL par défaut.

### 2. Modifications SQL mineures

PostgreSQL a quelques différences syntaxiques avec MySQL :

#### A. Types de colonnes

Dans vos entités, ces types sont déjà compatibles :
- `string`, `text`, `integer`, `decimal`, `datetime`, `boolean` → ✅ OK
- `json` → ✅ OK (PostgreSQL a un vrai type JSON natif)

#### B. Fonctions spécifiques MySQL à adapter

**Dans les repositories utilisant des fonctions MySQL :**

| MySQL | PostgreSQL | Fichier concerné |
|-------|------------|------------------|
| `DATE_FORMAT()` | `TO_CHAR()` | Repositories avec groupBy date |
| `YEAR()`, `MONTH()` | `EXTRACT(YEAR FROM ...)` | Repositories temporels |
| `IFNULL()` | `COALESCE()` | Queries conditionnelles |
| `CONCAT()` | `||` ou `CONCAT()` | ✅ Identique |

**Recherchez dans votre code :**

```bash
# Trouver les usages de fonctions MySQL
grep -r "DATE_FORMAT\|YEAR(\|MONTH(\|IFNULL" src/Repository/
```

### 3. Migrations à régénérer

Toutes vos migrations sont spécifiques à MySQL. Avec PostgreSQL :

```bash
# 1. Sauvegarder vos données MySQL actuelles (si besoin)
php bin/console app:export-data  # Créez cette commande si nécessaire

# 2. Changer DATABASE_URL vers PostgreSQL
# Dans .env.local :
DATABASE_URL="postgresql://user:password@localhost:5432/hotones?serverVersion=15&charset=utf8"

# 3. Supprimer les anciennes migrations
rm -rf migrations/*

# 4. Générer la migration initiale depuis vos entités
php bin/console doctrine:migrations:diff

# 5. Exécuter la migration
php bin/console doctrine:migrations:migrate

# 6. Réimporter les données (si besoin)
```

### 4. Configuration Docker locale (développement)

Remplacez MariaDB par PostgreSQL dans `docker-compose.yml` :

```yaml
  db:
    image: postgres:15-alpine
    container_name: hotones_db
    environment:
      POSTGRES_DB: hotones
      POSTGRES_USER: symfony
      POSTGRES_PASSWORD: symfony
    ports:
      - "5432:5432"
    volumes:
      - db-data:/var/lib/postgresql/data
```

### 5. Tests - Changement minimal

Dans `.env.test`, changez seulement la DATABASE_URL :

```ini
# Avant (SQLite)
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# Après (garde SQLite, c'est compatible)
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

SQLite peut rester pour les tests, ou utilisez PostgreSQL in-memory.

## Déploiement sur Render avec PostgreSQL

### render.yaml mis à jour

Remplacez la section `databases` :

```yaml
databases:
  - name: hotones-db
    databaseName: hotones
    region: frankfurt
    plan: free  # ou starter ($7/mois)
    # PostgreSQL 15+ par défaut
```

C'est tout ! Render créera automatiquement une base PostgreSQL.

### Alternative : Supabase (gratuit permanent)

1. Créez un compte sur https://supabase.com
2. Créez un nouveau projet
3. Dans "Settings" → "Database", récupérez la connection string :
   ```
   postgresql://postgres:[PASSWORD]@db.xxx.supabase.co:5432/postgres
   ```
4. Ajoutez-la dans les variables Render :
   ```
   DATABASE_URL=postgresql://postgres:password@db.xxx.supabase.co:5432/postgres
   ```

**Avantages Supabase :**
- ✅ Gratuit permanent (500MB, 2 CPU, 1GB RAM)
- ✅ Backups automatiques
- ✅ Dashboard web puissant
- ✅ API REST auto-générée (bonus)

## Étapes de migration complètes

### Étape 1 : Préparation

```bash
# 1. Créer une branche
git checkout -b feat/migrate-to-postgresql

# 2. Backup de la DB actuelle (si en production)
mysqldump -u root -p hotones > backup_mysql.sql

# 3. Installer PostgreSQL localement (si pas déjà fait)
# macOS :
brew install postgresql@15
brew services start postgresql@15

# Linux :
sudo apt install postgresql-15

# Docker :
# Voir docker-compose.yml modifié ci-dessus
```

### Étape 2 : Adapter le code

```bash
# 1. Chercher les fonctions MySQL spécifiques
grep -rn "DATE_FORMAT\|YEAR(\|MONTH(\|WEEK(\|IFNULL" src/

# 2. Remplacer si nécessaire (exemples) :
```

**Exemple de modification dans un Repository :**

```php
// Avant (MySQL)
$qb->select('YEAR(t.date) as year, MONTH(t.date) as month')
   ->where('IFNULL(t.deletedAt, "") = ""');

// Après (PostgreSQL)
$qb->select('EXTRACT(YEAR FROM t.date) as year, EXTRACT(MONTH FROM t.date) as month')
   ->where('t.deletedAt IS NULL');
```

### Étape 3 : Migrations

```bash
# 1. Changer DATABASE_URL
echo 'DATABASE_URL="postgresql://symfony:symfony@localhost:5432/hotones?serverVersion=15&charset=utf8"' > .env.local

# 2. Créer la base PostgreSQL
docker compose up -d db  # ou createdb hotones

# 3. Supprimer anciennes migrations
mv migrations migrations_mysql_backup

# 4. Générer nouvelle migration depuis entités
php bin/console doctrine:migrations:diff

# 5. Vérifier la migration générée
cat migrations/*.php

# 6. Exécuter
php bin/console doctrine:migrations:migrate
```

### Étape 4 : Tests

```bash
# 1. Lancer les tests
composer test

# 2. Vérifier les features manuellement
# - Login
# - CRUD projets
# - Saisie de temps
# - Analytics dashboards
# - Export PDF
```

### Étape 5 : Déploiement

```bash
# 1. Commit et push
git add .
git commit -m "feat: migrate from MySQL to PostgreSQL for Render deployment"
git push origin feat/migrate-to-postgresql

# 2. Merger dans main
git checkout main
git merge feat/migrate-to-postgresql
git push origin main

# 3. Déployer sur Render (voir docs/deployment-render.md)
```

## Comparaison des coûts

| Solution | Gratuit | Payant | Notes |
|----------|---------|--------|-------|
| **Render PostgreSQL** | 90 jours | $7/mois | Intégré, facile |
| **Supabase PostgreSQL** | ✅ Permanent (500MB) | $25/mois (Pro) | Excellent plan gratuit |
| **Neon PostgreSQL** | ✅ Permanent (512MB) | $19/mois | Serverless, innovant |
| **Railway MySQL** | $5 crédit/mois | ~$10/mois | Bon mais plus cher |
| **DigitalOcean MySQL** | ❌ | $15/mois | Fiable mais coûteux |

## Recommandation finale

🎯 **Pour démarrer : Supabase PostgreSQL gratuit**
- Gratuit permanent
- 500MB largement suffisant pour démarrer
- Excellente performance
- Backups inclus

🚀 **Pour production : Render PostgreSQL Starter**
- $7/mois
- Intégré à votre infra Render
- Scaling facile

## Besoin d'aide ?

La migration est simple et Doctrine gère 99% des différences. Les seules adaptations concernent quelques requêtes SQL custom dans les repositories.

Voulez-vous que je :
1. ✅ Fasse la migration automatiquement ?
2. ✅ Scanne votre code pour trouver les incompatibilités ?
3. ✅ Génère les nouvelles migrations ?

---

**Note** : Si vous préférez absolument rester sur MySQL, utilisez Railway avec $5 de crédit gratuit mensuel.
