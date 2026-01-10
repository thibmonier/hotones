# Fix des migrations en production

## Contexte

La production a un historique de 76 migrations "Executed Unavailable" (fichiers supprimés) et refuse d'exécuter les 7 nouvelles migrations disponibles. La colonne `image_prompt` manque dans `blog_posts`.

## Solution

Nous avons une migration idempotente (`Version20260110081458`) qui peut être exécutée en toute sécurité pour ajouter les colonnes manquantes.

---

## Option 1 : Avec le script automatique (RECOMMANDÉ)

```bash
# Sur Railway (via railway run)
railway run bash fix-prod-migrations.sh

# OU si vous avez un shell direct sur le container
./fix-prod-migrations.sh
```

---

## Option 2 : Étape par étape (Manuel)

### 1️⃣ Connexion au container de production

```bash
# Via Railway CLI
railway shell

# OU via le dashboard Railway : Service > Shell
```

### 2️⃣ Sauvegarder l'état actuel

```bash
php bin/console doctrine:migrations:status > migrations-status-before.txt
cat migrations-status-before.txt
```

### 3️⃣ Nettoyer la table des migrations

```bash
php bin/console dbal:run-sql "TRUNCATE TABLE doctrine_migration_versions"
```

⚠️ **Important** : Cela supprime UNIQUEMENT l'historique des migrations, pas les données !

### 4️⃣ Exécuter la migration idempotente

```bash
php bin/console doctrine:migrations:execute --up DoctrineMigrations\\Version20260110081458
```

Cette migration ajoute les colonnes si elles n'existent pas :
- `image_prompt`
- `image_source`
- `image_generated_at`
- `image_model`

### 5️⃣ Marquer toutes les migrations comme exécutées

```bash
php bin/console doctrine:migrations:version --add --all --no-interaction
```

### 6️⃣ Vérifier l'état final

```bash
php bin/console doctrine:migrations:status
```

Vous devriez voir :
- **7 migrations exécutées** (toutes disponibles)
- **0 migration en attente**
- Plus d'erreur "Executed Unavailable"

---

## Vérification post-correction

### Tester le schéma de base de données

```bash
php bin/console doctrine:schema:validate
```

Devrait afficher :
```
[OK] The database schema is in sync with the mapping files.
```

### Tester la création d'un article

```bash
# Vérifier que la table blog_posts a bien toutes les colonnes
php bin/console dbal:run-sql "DESCRIBE blog_posts" | grep image
```

Devrait afficher :
```
image_prompt        | varchar(1000)
image_source        | varchar(20)
image_generated_at  | datetime
image_model         | varchar(50)
```

---

## Prochains déploiements

Les prochaines migrations fonctionneront normalement :

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Rollback (si problème)

Si quelque chose se passe mal, vous pouvez restaurer l'état avec :

```bash
# Restaurer uniquement l'historique (pas les données)
# (vous aurez besoin d'une sauvegarde de doctrine_migration_versions)
```

⚠️ **Note** : Les colonnes ajoutées par la migration idempotente resteront en place. Pour les supprimer :

```bash
php bin/console doctrine:migrations:execute --down DoctrineMigrations\\Version20260110081458
```

---

## Pourquoi cette situation ?

Cette situation arrive quand :
1. Des migrations ont été exécutées en production
2. Les fichiers de migration ont été supprimés du dépôt Git (probablement un squash)
3. De nouvelles migrations ont été créées
4. Doctrine refuse d'exécuter les nouvelles migrations tant qu'il y a des "Executed Unavailable"

## Solution à long terme

Pour éviter ce problème à l'avenir :
- ✅ **NE JAMAIS supprimer** les fichiers de migration du dépôt
- ✅ **Squash de migrations** uniquement AVANT le déploiement en prod
- ✅ Utiliser des migrations **idempotentes** pour les corrections
- ✅ Toujours vérifier `doctrine:migrations:status` avant de déployer
