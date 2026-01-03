# Restauration de la BDD - Branche feature/lot-23-multi-tenant

## 📦 Backup créé le
Date du backup : voir le nom du fichier `.sql`

## 🔄 Pour restaurer la BDD quand vous revenez sur cette branche

### Option 1 : Script automatique (recommandé)

```bash
./backups/restore-db.sh
```

### Option 2 : Restauration manuelle

```bash
# 1. Identifier le fichier de backup
ls -lh backups/db-backup-feature-lot-23-*.sql

# 2. Restaurer la base de données
docker compose exec -T db sh -c 'mariadb -u symfony -psymfony hotones' < backups/db-backup-feature-lot-23-[DATE].sql

# 3. Vérifier la restauration
docker compose exec app php bin/console doctrine:schema:validate
```

## 📋 Workflow complet

### Quitter la branche multi-tenant pour aller sur main

```bash
# 1. S'assurer que tout est commité
git status

# 2. Passer sur main
git checkout main

# 3. Restaurer la BDD de main (si nécessaire)
docker compose exec app php bin/console doctrine:migrations:migrate

# 4. Vider le cache
docker compose exec app php bin/console cache:clear
```

### Revenir sur la branche multi-tenant

```bash
# 1. Passer sur la branche
git checkout feature/lot-23-multi-tenant

# 2. Restaurer la BDD avec le backup
./backups/restore-db.sh

# 3. Mettre à jour les dépendances si nécessaire
docker compose exec app composer install

# 4. Vider le cache
docker compose exec app php bin/console cache:clear

# 5. Vérifier l'état
docker compose exec app php bin/console doctrine:schema:validate
```

## ⚠️ Important

- **Ne pas supprimer** le fichier de backup tant que la branche n'est pas mergée
- Le backup contient l'état complet de la BDD au moment de la sauvegarde
- Si vous faites des changements sur main qui touchent la structure de la BDD,
  il faudra peut-être re-créer un nouveau backup après merge
