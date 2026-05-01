# Backup & Restore SQL

> Sprint-004 / TEST-006 — stratégie de sauvegarde et restauration de la base de données

## Objectifs

1. Pouvoir produire un dump cohérent de la base à tout moment (`app:backup:dump`).
2. Pouvoir le rejouer sur une base vierge ou existante (`app:backup:restore`).
3. Disposer d'une sauvegarde automatique quotidienne du staging.
4. Documenter la procédure de restauration en production (préventif).

## Vue d'ensemble

| Composant | Fichier |
|---|---|
| Dump | `src/Command/BackupDumpCommand.php` |
| Restore | `src/Command/BackupRestoreCommand.php` |
| Test cycle | `tests/Integration/Command/BackupRestoreCycleTest.php` |
| Cron staging | `.github/workflows/staging-backup.yml` |

Drivers supportés : **MariaDB / MySQL** (via `mysqldump` / `mysql`), **PostgreSQL** (via `pg_dump` / `psql`), **SQLite** (PDO pur, pas de dépendance shell).

## Commandes

### Dump

```bash
# Sortie par défaut : var/backups/backup-<timestamp>.sql
php bin/console app:backup:dump

# Sortie + compression gzip (mysql/pgsql uniquement)
php bin/console app:backup:dump --output=/tmp/backup.sql.gz --compress
```

Variables prises en compte automatiquement (depuis `DATABASE_URL`) : `host`, `port`, `user`, `password`, `dbname`, `path` (sqlite). Aucun mot de passe n'est passé en CLI : il transite par variable d'environnement (`MYSQL_PWD` / `PGPASSWORD`).

### Restore

```bash
php bin/console app:backup:restore /tmp/backup.sql.gz

# En prod, --force est requis (garde-fou)
APP_ENV=prod php bin/console app:backup:restore /tmp/backup.sql.gz --force
```

Le fichier peut être en `.sql` ou `.sql.gz`. Le format est détecté à l'extension.

## Sauvegarde automatique du staging

Le workflow `staging-backup.yml` :

- s'exécute **chaque jour à 02:17 UTC** ;
- est gardé par la variable `vars.STAGING_BACKUP_ENABLED == 'true'` (à activer dans **Settings → Variables → Repository variables**) ;
- pousse l'artifact gzipped sur GitHub Actions avec rétention par défaut **14 jours** ;
- ouvre une issue automatique étiquetée `ops,backup` en cas d'échec.

Secrets requis :

| Nom | Description |
|---|---|
| `STAGING_DATABASE_URL` | DSN Doctrine pointant vers la base staging |
| `STAGING_APP_SECRET` | `APP_SECRET` du staging (nécessaire pour bootloader Symfony) |

## Cycle de test

`BackupRestoreCycleTest` (intégration) :

1. crée une table fixture, insère 3 lignes (dont une avec quote échappée et un nombre négatif) ;
2. lance `app:backup:dump` ;
3. corrompt les données (DELETE + UPDATE) ;
4. lance `app:backup:restore` sur le dump produit ;
5. relit la table et compare aux lignes initiales.

```bash
make test-integration FILTER=BackupRestoreCycleTest
# ou
docker compose exec app php vendor/bin/phpunit tests/Integration/Command/BackupRestoreCycleTest.php
```

## Procédure de restauration prod (runbook)

1. **Geler les écritures** : si possible, mettre l'application en mode lecture seule (variable d'environnement `APP_READ_ONLY=true` côté Render).
2. **Identifier le dump cible** :
   - si dump quotidien : télécharger l'artifact `staging-backup-<run_id>` depuis GitHub Actions ;
   - si snapshot fournisseur (Render PostgreSQL) : restaurer via l'interface Render, puis ignorer le reste de cette procédure.
3. **Provisionner une base vierge** (recommandé) plutôt que d'écraser la prod existante.
4. **Lancer la restauration** :
   ```bash
   gunzip -k backup.sql.gz
   APP_ENV=prod php bin/console app:backup:restore backup.sql --force
   ```
5. **Vérifications post-restauration** :
   - `php bin/console doctrine:migrations:status` (migrations toutes appliquées) ;
   - `php bin/console doctrine:schema:validate` (mapping cohérent) ;
   - smoke test fonctionnel (login + 1 lecture + 1 écriture).
6. **Réouverture des écritures** et communication aux utilisateurs.

## Points d'attention

- **Transactions imbriquées** : sur SQLite + DAMA Test Bundle, le dump contient `BEGIN/COMMIT`. La commande de restore les filtre quand une transaction est déjà active (cas du test d'intégration). Sur prod, ce comportement est neutre.
- **Tables de migration** : le dump inclut `doctrine_migration_versions`. Après restauration, ne **pas** relancer `doctrine:migrations:migrate` sans contrôle ; vérifier d'abord `migrations:status`.
- **Cycle CI** : ne pas exécuter le dump dans la CI principale (consommation IO + temps). C'est volontairement isolé dans le workflow `staging-backup.yml`.
- **PII / RGPD** : les dumps contiennent des données personnelles. Ne les stockez pas en dehors de GitHub Artifacts (rétention 14 jours par défaut) sans contrat DPA approprié.

## Roadmap (TEST-006 → TEST-006-bis ?)

- Ajouter une rotation S3 / R2 pour conserver des dumps > 14 jours.
- Tester `app:backup:restore` sur un environnement éphémère (Render preview) en CI.
- Couvrir les drivers MySQL / PgSQL via un test d'intégration optionnel (matrice de service dans la CI).
