# EPIC-003 — Runbook audit Contributors sans CJM

> **Sprint-020 sub-epic D AUDIT-CONTRIBUTORS-CJM** — pré-requis bloquant
> US-098 EPIC-003 Phase 2 ACL deploy. Mitigation Risk Q3 critique.

## Contexte

Audit data sprint-019 a identifié **Risk Q3 critique** : `Contributor.cjm` /
`tjm` doubles nullable au niveau flat → coût/CA = 0 silencieusement → marge
projet faussée.

US-097 sprint-019 mitige côté DDD via `HourlyRate::fromDailyRate(null)` qui
throw. Mais avant deploy WorkItem en prod (US-098 sprint-020 Phase 2), il
faut **identifier + corriger les contributeurs sans rate** côté flat
existant.

## Commande

```bash
# Audit CJM uniquement, contributeurs actifs (par défaut)
make console CMD="app:audit:contributors-cjm"

# Audit CJM + TJM
make console CMD="app:audit:contributors-cjm --tjm"

# Audit incluant contributeurs inactifs
make console CMD="app:audit:contributors-cjm --include-inactive --tjm"
```

## Résolution rate (logique identique au property hook Contributor)

1. **EmploymentPeriod actif** (`startDate <= now <= endDate`) → si `cjm` / `tjm` non null + > 0
2. Sinon **fallback** sur `Contributor.cjm` / `tjm` direct si non null + > 0
3. Sinon **null → flagué missing**

## Output

### Cas success (0 manquant)

```
[OK] tous les 42 contributeurs ont un CJM résolu (Risk Q3 OK).
```

Exit code : `0`.

### Cas warning (manquants détectés)

```
+----+--------------------+--------------+---------+----------+--------------------+
| ID | Email              | Nom          | Company | CJM      | Période active ?   |
+----+--------------------+--------------+---------+----------+--------------------+
| 7  | alice@example.org  | Alice Wonder | 1       | ❌ NULL   | yes                |
| 11 | bob@example.org    | Bob Builder  | 1       | ❌ NULL   | no                 |
+----+--------------------+--------------+---------+----------+--------------------+

⚠️ Risk Q3 détecté : 2 / 42 contributeurs sans rate résolu.

Action requise avant US-098 Phase 2 ACL deploy :
 * 1. Corriger les CJM/TJM manquants côté admin (Contributor ou EmploymentPeriod)
 * 2. Re-exécuter cette commande pour valider 0 manquant
 * 3. Marquer Risk Q3 résolu dans audit doc + sprint-020 retro
```

Exit code : `1` (FAILURE) → bloque pipeline CI deploy si intégré.

## Workflow recommandé sprint-020

| Étape | Action | Owner |
|---|---|---|
| 1 | Exécuter audit local : `make console CMD="app:audit:contributors-cjm --tjm"` | Tech Lead |
| 2 | Si manquants : exporter table → admin BO Contributor pour correction | PO + admin |
| 3 | Corriger CJM/TJM manquants (Contributor ou EmploymentPeriod actif) | Admin / RH |
| 4 | Re-exécuter audit jusqu'à 0 manquant | Tech Lead |
| 5 | Marquer Risk Q3 résolu (commit doc + sprint-020 retro) | Tech Lead |
| 6 | Débloquer US-098 Phase 2 ACL deploy | Tech Lead |

## Exécution prod (Render)

```bash
# Connexion shell Render service hotones
render ssh hotones-web

# Exécuter audit prod
php bin/console app:audit:contributors-cjm --tjm

# Si manquants, BO admin (https://hotones.onrender.com/admin) pour corrections
```

## CI integration (futur sprint)

Ajouter step optionnel dans `post-deploy-smoke.yml` :

```yaml
- name: Audit Contributors CJM (Risk Q3)
  if: ${{ vars.AUDIT_CONTRIBUTORS_CJM_CI == 'true' }}
  run: |
    # Trigger audit via API admin OU shell render. Si exit code 1, alert Slack.
    # TODO sprint-021+
```

## Trigger résolu Risk Q3

Risk Q3 considéré **résolu** quand :
1. ✅ `app:audit:contributors-cjm --tjm --include-inactive` exit 0 prod
2. ✅ Commit doc `epic-003-audit-existing-data.md` mise à jour Q3 status
3. ✅ Mention sprint-020 retro KEEP

---

## Liens

- `src/Command/AuditContributorsCjmCommand.php`
- Audit data initial : `docs/02-architecture/epic-003-audit-existing-data.md`
- ADR-0013 EPIC-003 scope
- Sprint-019 retro A-7 héritage

---

**Date** : 2026-05-09
**Auteur** : Tech Lead (sprint-020 sub-epic D)
**Status** : ✅ Commande livrée — exécution OPS avant US-098
