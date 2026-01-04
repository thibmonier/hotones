# Lot 23 - Multi-Tenant Migration Guide

**Branch:** `feature/lot-23-multi-tenant`
**Date:** 2025-12-31
**Status:** Phase 2.6 - Database Migrations

---

## ⚠️ Important: Reversible Migrations

Toutes les migrations sont **complètement réversibles** avec des méthodes `down()` fonctionnelles. Vous pouvez revenir sur la branche `main` à tout moment.

---

## 🔄 Workflow: Basculer entre feature/lot-23 et main

### Option A: Travailler sur lot-23 puis revenir sur main

```bash
# 1. Sur feature/lot-23-multi-tenant: Sauvegarder la BDD
./scripts/backup-database.sh lot23_work

# 2. Exécuter les migrations
docker compose exec app php bin/console doctrine:migrations:migrate -n

# 3. Travailler sur le lot 23...

# 4. Pour revenir sur main:
git checkout main

# 5. Restaurer la BDD de main
./scripts/restore-database.sh backups/lot23_work_TIMESTAMP.sql

# 6. Vérifier les migrations
docker compose exec app php bin/console doctrine:migrations:status
```

### Option B: Rollback des migrations (méthode down)

```bash
# 1. Sur feature/lot-23-multi-tenant
docker compose exec app php bin/console doctrine:migrations:status

# 2. Identifier la dernière migration de main
# Exemple: Version20241231120000 (dernière migration de main)

# 3. Rollback vers cette version
docker compose exec app php bin/console doctrine:migrations:migrate Version20241231120000

# 4. Basculer sur main
git checkout main

# 5. Vérifier l'état
docker compose exec app php bin/console doctrine:migrations:status
```

---

## 📦 Scripts de Sauvegarde/Restauration

### Backup Database

```bash
./scripts/backup-database.sh [nom_optionnel]
```

**Ce que ça fait:**
- Crée un dump MySQL dans `backups/`
- Nomme le fichier avec timestamp: `nom_YYYYMMDD_HHMMSS.sql`
- Garde les 10 derniers backups automatiquement
- Affiche les instructions de restauration

**Exemple:**
```bash
./scripts/backup-database.sh before_lot23_migrations
# Crée: backups/before_lot23_migrations_20250101_143022.sql
```

### Restore Database

```bash
./scripts/restore-database.sh <fichier_backup>
```

**Ce que ça fait:**
- Demande confirmation (car DROP DATABASE)
- Supprime et recrée la base
- Restaure le backup
- Synchronise les métadonnées de migrations

**Exemple:**
```bash
./scripts/restore-database.sh backups/before_lot23_migrations_20250101_143022.sql
```

---

## 🗂️ Plan de Migration (Phase 2.6)

### Migration 1: Create `companies` table
**Fichier:** `VersionYYYYMMDD_001_CreateCompaniesTable.php`

**up():**
- Crée la table `companies` avec tous les champs
- Indexes: slug, status, subscription_tier
- Contrainte unique: slug

**down():**
- DROP TABLE companies

**Réversibilité:** ✅ Totale (pas de données initiales)

---

### Migration 2: Create `business_units` table
**Fichier:** `VersionYYYYMMDD_002_CreateBusinessUnitsTable.php`

**up():**
- Crée la table `business_units`
- Foreign keys: company_id, parent_id
- Indexes: company_id, parent_id, active

**down():**
- DROP TABLE business_units

**Réversibilité:** ✅ Totale

---

### Migration 3: Add `company_id` to `users`
**Fichier:** `VersionYYYYMMDD_003_AddCompanyIdToUsers.php`

**up():**
1. Ajoute colonne `company_id INT NULL`
2. Crée une company par défaut: "HotOnes Default Company"
3. Assigne tous les users existants à cette company (UPDATE)
4. Change colonne en NOT NULL
5. Supprime contrainte unique sur `email`
6. Ajoute contrainte unique composite `(email, company_id)`
7. Ajoute FK company_id → companies(id) ON DELETE CASCADE

**down():**
1. Supprime FK company_id
2. Supprime contrainte unique `(email, company_id)`
3. Re-crée contrainte unique sur `email` seul
4. Supprime colonne `company_id`
5. **Note:** La default company reste (pas de suppression)

**Réversibilité:** ✅ Complète
- Les users retrouvent leur état d'origine
- La default company reste (inerte, pas de problème)

---

### Migration 4: Add `company_id` to Batch 1 (Contributors)
**Fichier:** `VersionYYYYMMDD_004_AddCompanyIdToBatch1.php`

**Tables modifiées:**
- contributors
- employment_periods
- profiles
- contributor_skills

**up() pour chaque table:**
1. ADD COLUMN company_id INT NULL
2. UPDATE: copie company_id depuis user associé (via relation)
3. ALTER COLUMN company_id NOT NULL
4. CREATE INDEX
5. ADD FOREIGN KEY ON DELETE CASCADE

**down() pour chaque table:**
1. DROP FOREIGN KEY
2. DROP INDEX
3. DROP COLUMN company_id

**Réversibilité:** ✅ Complète

---

### Migration 5: Add `company_id` to Batch 2 (Projects)
**Fichier:** `VersionYYYYMMDD_005_AddCompanyIdToBatch2.php`

**Tables modifiées:**
- projects
- clients
- client_contacts
- project_tasks
- project_sub_tasks

**Logique identique à Migration 4**

**Réversibilité:** ✅ Complète

---

### Migration 6: Add `company_id` to Batch 3 (Orders)
**Fichier:** `VersionYYYYMMDD_006_AddCompanyIdToBatch3.php`

**Tables modifiées:**
- orders (+ modification contrainte order_number)
- order_sections
- order_lines
- order_payment_schedules

**up() spécial pour orders:**
1. ADD COLUMN company_id
2. UPDATE company_id
3. ALTER NOT NULL
4. **DROP** UNIQUE constraint sur `order_number`
5. **ADD** UNIQUE constraint sur `(order_number, company_id)`
6. INDEX + FK

**down() spécial pour orders:**
1. DROP FK
2. DROP INDEX
3. DROP UNIQUE `(order_number, company_id)`
4. **RE-CREATE** UNIQUE sur `order_number` seul
5. DROP COLUMN company_id

**Réversibilité:** ✅ Complète (la contrainte order_number unique globale est restaurée)

---

### Migrations 7-10: Remaining Batches
**Batches restants:**
- Batch 4: timesheets, planning
- Batch 5: technologies, service_categories, skills (+ unique par company)
- Batch 6: analytics (fact_*, dim_*)
- Batch 7: notifications, HR, finance

**Réversibilité:** ✅ Complète pour toutes

---

## 🧪 Testing Migrations

### Test Complet up/down

```bash
# 1. Backup initial
./scripts/backup-database.sh before_migration_test

# 2. Vérifier l'état actuel
docker compose exec app php bin/console doctrine:migrations:status

# 3. Exécuter TOUTES les migrations
docker compose exec app php bin/console doctrine:migrations:migrate -n

# 4. Vérifier que tout est migré
docker compose exec app php bin/console doctrine:schema:validate

# 5. ROLLBACK complet vers la version de main
docker compose exec app php bin/console doctrine:migrations:migrate prev -n

# 6. Vérifier chaque étape individuellement
docker compose exec app php bin/console doctrine:migrations:migrate next
docker compose exec app php bin/console doctrine:migrations:migrate prev

# 7. Si problème: restaurer le backup
./scripts/restore-database.sh backups/before_migration_test_TIMESTAMP.sql
```

### Test d'une Seule Migration

```bash
# Exécuter une migration spécifique
docker compose exec app php bin/console doctrine:migrations:execute VersionYYYYMMDD_003 --up

# Rollback de cette migration
docker compose exec app php bin/console doctrine:migrations:execute VersionYYYYMMDD_003 --down

# Vérifier les données
docker compose exec db mysql -u symfony -psymfony hotones -e "DESCRIBE users;"
```

---

## ⚠️ Points d'Attention

### 1. Default Company
La migration 3 crée une company "HotOnes Default Company" qui:
- Reçoit TOUS les users/données existants
- Reste dans la BDD même après rollback (pas de problème)
- Peut être supprimée manuellement si besoin après rollback complet

### 2. Contraintes Uniques
Certaines tables ont des contraintes uniques modifiées:
- `users.email`: unique → unique(email, company_id)
- `orders.order_number`: unique → unique(order_number, company_id)
- `technologies.name`: unique → unique(name, company_id)

Le rollback **restaure les contraintes d'origine**.

### 3. Cascading Deletes
Toutes les FK vers `companies(id)` sont en `ON DELETE CASCADE`.
⚠️ Supprimer une company supprime TOUTES ses données associées.

### 4. Performance
Les migrations 4-10 peuvent être longues si beaucoup de données.
Prévoir ~1-2 minutes par batch sur une BDD de taille moyenne.

---

## 📋 Checklist avant Migration

- [ ] Backup de la BDD créé: `./scripts/backup-database.sh`
- [ ] Tests passent sur `main`: `docker compose exec app composer test`
- [ ] Branch `feature/lot-23-multi-tenant` à jour
- [ ] Docker containers up: `docker compose ps`
- [ ] Espace disque suffisant pour backups (>500MB recommandé)

---

## 📋 Checklist après Migration

- [ ] Schema validé: `php bin/console doctrine:schema:validate`
- [ ] Tests passent: `docker compose exec app composer test`
- [ ] Application démarre: http://localhost:8080
- [ ] Test de rollback d'une migration: `doctrine:migrations:migrate prev`
- [ ] Rollback testé avec succès
- [ ] Re-migration testée: `doctrine:migrations:migrate`

---

## 🆘 Troubleshooting

### Erreur: Foreign Key Constraint Fails
```bash
# Vérifier l'ordre des migrations
docker compose exec app php bin/console doctrine:migrations:status

# Rollback étape par étape
docker compose exec app php bin/console doctrine:migrations:migrate prev
```

### Erreur: Duplicate Entry
```bash
# Restaurer le backup
./scripts/restore-database.sh backups/LATEST_BACKUP.sql

# Vérifier l'intégrité
docker compose exec db mysql -u symfony -psymfony hotones -e "CHECK TABLE users, projects, orders;"
```

### Application ne démarre pas
```bash
# 1. Rollback complet
docker compose exec app php bin/console doctrine:migrations:migrate first

# 2. Restaurer backup
./scripts/restore-database.sh backups/BACKUP_FILE.sql

# 3. Retour sur main
git checkout main

# 4. Clear cache
docker compose exec app php bin/console cache:clear
```

---

## 📞 Support

En cas de problème:
1. Créer un backup: `./scripts/backup-database.sh emergency_backup`
2. Noter le message d'erreur complet
3. Noter la dernière migration exécutée: `doctrine:migrations:status`
4. Documenter dans `docs/11-reports/migration-issues.md`

---

**Dernière mise à jour:** 2025-12-31
**Responsable:** Claude Code (Lot 23 - Phase 2.6)
