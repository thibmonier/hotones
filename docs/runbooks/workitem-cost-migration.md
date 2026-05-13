# Runbook — Migration WorkItem cost legacy (US-113)

> Procédure prod pour `app:workitem:migrate-legacy-cost`.
> ⚠️ **Risk daily** : backup BDD obligatoire + fenêtre maintenance + dry-run gating.

**Origine** : sprint-024 US-113 (EPIC-003 Phase 4) — AUDIT-WORKITEM-DATA Phase 1 conclusions.

---

## 1. Quand exécuter

- Première exécution post-déploiement sprint-024 (snapshot baseline cost legacy)
- Re-runs périodiques (audit drift suite changements HourlyRate retroactifs)
- Maintenance window obligatoire si volume > 5000 timesheets

## 2. Pré-requis (J-1)

### 2.1 Backup BDD

```bash
# Local snapshot pre-migration (via Render dashboard ou pg_dump)
pg_dump -h <host> -U <user> -d <db> -F c -f /backups/hotones-pre-us113-$(date +%Y-%m-%d).pgc

# Vérifier taille + intégrité
ls -lh /backups/hotones-pre-us113-*.pgc
pg_restore --list /backups/hotones-pre-us113-*.pgc | head -20
```

### 2.2 Migration DB cols appliquée

```bash
# Vérifier Version20260513090000 statut migrated
docker compose exec app bin/console doctrine:migrations:status --env=prod
```

Statut attendu : `DoctrineMigrations\Version20260513090000 ... migrated`.

### 2.3 Communication

- ✅ PO + Tech Lead informés window
- ✅ Slack `#alerts-prod` actif (réception alertes ADR-0013 cas 3)
- ✅ Backup confirmé par DBA (ou Tech Lead si self-managed)

### 2.4 Volume estimé

```bash
docker compose exec app bin/console dbal:run-sql \
  "SELECT COUNT(*) FROM timesheets" --env=prod
```

- < 2000 → batch 100, exec < 5 min
- 2000-10000 → batch 100, exec 10-30 min
- &gt; 10000 → split exec via `--limit` par tranche 5000

## 3. Exécution

### 3.1 Dry-run obligatoire

```bash
docker compose exec app bin/console app:workitem:migrate-legacy-cost \
  --dry-run --csv-report=auto --env=prod
```

**Output attendu** :

```
Total timesheets to process : N
N/N [██████████████████████████████] 100%

Résumé migration
| Métrique                | Valeur   |
| Mode                    | DRY-RUN  |
| Total processed         | N        |
| Migrated                | M        |
| Already migrated (skip) | 0        |
| Missing rate            | X        |
| Drifts > 1 cent         | D        |
| Drift ratio             | R %      |
```

### 3.2 Revue rapport drift

```bash
# CSV exporté à var/migration/workitem-cost-drift-{Y-m-d-His}.csv
ls -lh var/migration/workitem-cost-drift-*.csv | tail -1
head -5 var/migration/workitem-cost-drift-*.csv | tail -4
```

**Gate décision** :
- `Drift ratio < 5 %` → ✅ proceed exec
- `Drift ratio > 5 %` → ❌ **STOP** → trigger abandon ADR-0013 cas 3 → décision PO + Tech Lead requise
- `Missing rate > 0` → audit OPS Contributors sans `cjm`/`tjm` (cf Risk Q3) avant exec

### 3.3 Exécution write

```bash
docker compose exec app bin/console app:workitem:migrate-legacy-cost \
  --csv-report=auto --batch-size=100 --env=prod
```

**Exit codes** :
- `0` = success, drift < 5 %
- `1` = trigger abandon ADR-0013 cas 3 (drift > 5 %)

### 3.4 Vérification post-migration

```bash
# Cols populated
docker compose exec app bin/console dbal:run-sql \
  "SELECT COUNT(*) FROM timesheets WHERE migrated_at IS NOT NULL" --env=prod

# Drifts flagged
docker compose exec app bin/console dbal:run-sql \
  "SELECT COUNT(*) FROM timesheets WHERE legacy_cost_drift = 1" --env=prod

# Distribution legacy_cost_cents
docker compose exec app bin/console dbal:run-sql \
  "SELECT MIN(legacy_cost_cents), MAX(legacy_cost_cents), AVG(legacy_cost_cents) FROM timesheets WHERE legacy_cost_cents IS NOT NULL" --env=prod
```

## 4. Rollback

### 4.1 Rollback données seules (préservation cols)

```bash
# Reset cols sans down() migration
docker compose exec app bin/console dbal:run-sql \
  "UPDATE timesheets SET migrated_at = NULL, legacy_cost_drift = 0, legacy_cost_cents = NULL" \
  --env=prod
```

### 4.2 Rollback structurel (DROP cols)

```bash
# Rollback Version20260513090000 down()
docker compose exec app bin/console doctrine:migrations:execute \
  --down "DoctrineMigrations\\Version20260513090000" \
  --no-interaction --env=prod
```

### 4.3 Rollback complet (restore backup)

⚠️ **Destructive** — perte toute donnée écrite après backup.

```bash
# Stop app
docker compose stop app

# Drop + restore
psql -h <host> -U <user> -d <db> -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;"
pg_restore -h <host> -U <user> -d <db> -j 4 /backups/hotones-pre-us113-*.pgc

# Restart
docker compose start app
docker compose exec app bin/console cache:clear --env=prod
```

## 5. Trigger abandon ADR-0013 cas 3

Si dry-run rapport `Drift ratio > 5 %` :

1. **STOP** exec write
2. Archiver CSV drift (`var/migration/workitem-cost-drift-*.csv`) + share PO
3. Décision PO + Tech Lead :
   - Continuer migration et accepter drift (audit comptable manuel)
   - Rollback structurel (4.2) et investiguer (rate provider config, EmploymentPeriod history)
   - Abandon scaling : EPIC-003 cas 3 ADR-0013 — reconsidérer
4. Documenter décision dans sprint-024 review

## 6. Métriques succès

| Métrique | Cible | Mesure |
|---|---|---|
| Migrated / total | 100 % | `WHERE migrated_at IS NOT NULL` |
| Missing rate | 0 % | `WHERE migrated_at IS NULL` ÷ total |
| Drift ratio | < 5 % | command output `Drift ratio` |
| Idempotent re-run | ✅ | re-exec → `Already migrated (skip) = total` |
| Exec time / 1000 items | < 60 s | timestamps log |

## 7. Owners

| Domaine | Owner | Backup |
|---|---|---|
| Backup BDD prod | Tech Lead | DBA externe |
| Exec migration | Tech Lead | PO (peut lancer dry-run) |
| Audit drift CSV | PO + Compta | Tech Lead |
| Décision abandon (cas 3) | PO + Tech Lead | (escalation board) |

**Escalation** : si exec échoue mid-run (timeout, OOM) → rollback section 4 + investigation.

## 8. Liens

- ADR-0013 — EPIC-003 scope (KPI #3 trigger abandon cas 3)
- AUDIT-WORKITEM-DATA Phase 1 sprint-019
- Migration DB : `migrations/Version20260513090000.php`
- Code source : `src/Domain/WorkItem/Migration/`, `src/Infrastructure/WorkItem/Migration/`
- Command : `src/Command/MigrateWorkItemLegacyCostCommand.php`
- Integration tests : `tests/Integration/Application/WorkItem/Migration/`
- Runbook OPS-PREP-J0 : `sprint-ops-prep-j0.md` (pattern J0 préparation)

---

**Auteur** : Tech Lead
**Date** : 2026-05-13
**Version** : 1.0.0
**Sprint origine** : 024 US-113 T-113-06
