# OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK — Vérif migrations Doctrine fin de sprint

> **BC**: OPS  |  **Source**: Sprint-026 retro (à formaliser) — gap process identifié

- **Implements** : TECH Sub-epic D — **Persona** : Tech Lead — **Estimate** : 1 pt — **MoSCoW** : Should — **Sprint** : 027 (candidate)

### Card

**As** Tech Lead
**I want** vérifier automatiquement que toutes les migrations Doctrine générées dans le sprint ont été appliquées sur prod (et staging) avant clôture sprint
**So that** éviter dérive schéma → code prod (cf risques DDD entities ajoutées sans migration exécutée, références cassées au démarrage)

### Acceptance Criteria

```
Given fin de sprint (clôture sprint-review/retro)
When Tech Lead exécute commande `make check-migrations-applied --env=prod`
Then output liste :
  - Migrations présentes dans migrations/ (filesystem)
  - Migrations exécutées en prod (table `doctrine_migration_versions`)
  - Delta : migrations non exécutées prod (signal rouge si > 0)
  - Delta : migrations exécutées prod absentes filesystem (signal rouge orphelines)
```

```
Given delta non-vide
When checkpoint sprint closure
Then bloquer transition sprint suivant tant que delta non-zéro
And documenter migrations non appliquées + plan exec dans sprint-retro
```

```
Given hook CI auto sur main post-merge
When PR mergée contenant nouvelle migration
Then warning Slack #alerts-prod si migration non appliquée prod > 24h
```

### Tasks

| ID | Type | Tâche | Estimation |
|---|---|---|---:|
| T-OPS-MIG-01 | [OPS] | Script `bin/check-migrations-applied` (filesystem vs DB diff) | 2h |
| T-OPS-MIG-02 | [OPS] | Cible Makefile `make check-migrations-applied` (prod + staging via env) | 0.5h |
| T-OPS-MIG-03 | [OPS] | Hook sprint closure (ajout sprint-retro template + check obligatoire) | 0.5h |
| T-OPS-MIG-04 | [OPS] | Alerte Slack 24h post-merge migration non appliquée (CI hook) | 1h |

**Total** : 4h (≈ 1 pt)

### Risques

| Risque | Mitigation |
|---|---|
| Accès DB prod restreint (credentials Tech Lead only) | Script SSH-tunnel ou read-only via `doctrine:migrations:status --env=prod` |
| False positive sur migrations skip-prod (env conditionnel) | Whitelist via comment `// SKIP_PROD` ou pattern naming |
| Bruit Slack si migration prévue mais pas urgente | Délai 24h + opt-in label PR `migration-deferred` |

### Trigger origine

Sprint-026 (sprint-024 US-113 livré : migration `Version20260513090000` ajoutée WorkItem.cost cols). T-113-07 dry-run prod sprint-027 = première application réelle du pattern → besoin contrôle systématique pour sprints futurs.

### Métriques succès

- 0 dérive schéma prod/filesystem en clôture sprint
- < 24h délai d'exécution migration après merge prod
- Sprint-retro template inclut systématiquement section "Migrations appliquées prod : ✅ / ❌"

### Liens

- Runbook : `docs/runbooks/workitem-cost-migration.md` (pattern reference T-113-06)
- CONTRIBUTING.md : section pre-commit (ajouter check local optionnel)
- Sprint-026 retro (à venir) : capture gap process

---

**Date** : 2026-05-16
**Auteur** : Tech Lead (capture autopilote)
**Version** : 1.0.0
