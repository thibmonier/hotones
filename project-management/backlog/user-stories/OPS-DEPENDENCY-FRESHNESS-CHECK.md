# OPS-DEPENDENCY-FRESHNESS-CHECK — Vérif périodique fraîcheur composer + yarn

> **BC**: OPS  |  **Source**: Sprint-026 retro (à formaliser) — gap process identifié

- **Implements** : TECH Sub-epic D — **Persona** : Tech Lead — **Estimate** : 1 pt — **MoSCoW** : Should — **Sprint** : 027 (candidate)

### Card

**As** Tech Lead
**I want** vérifier régulièrement (hebdo / fin de sprint) que les dépendances composer et yarn sont à jour (security advisories + outdated minor/patch + EOL major)
**So that** éviter dette de dépendances cumulée → CVE non patchées + drift majeur impossible à rattraper

### Acceptance Criteria

```
Given fin de sprint (avant clôture sprint-review)
When Tech Lead exécute `make deps-audit`
Then output liste :
  - Composer : packages outdated (minor / patch / major distingués)
  - Composer : security advisories actives (composer audit)
  - Yarn : packages outdated (npm outdated / yarn outdated)
  - Yarn : security advisories (npm audit / yarn audit)
  - Score global rouge / orange / vert
```

```
Given score rouge (CVE Critical/High présente)
When checkpoint sprint closure
Then bloquer transition tant que CVE patchée OU justification documentée
And ticket sprint suivant créé pour patch
```

```
Given score orange (outdated minor > 30 jours)
When sprint-retro
Then capture action plan (1 PR upgrade par sprint suivant)
```

```
Given hebdomadaire (lundi matin via cron)
When CI lance `make deps-audit`
Then publication Slack #tech-lead-digest (résumé score + delta vs semaine précédente)
And dashboard interne (optionnel : MAJ dashboard business KPI 11ᵉ widget "Dette deps")
```

### Tasks

| ID | Type | Tâche | Estimation |
|---|---|---|---:|
| T-OPS-DEP-01 | [OPS] | Script `bin/deps-audit` (composer audit + outdated + yarn audit + outdated) | 2h |
| T-OPS-DEP-02 | [OPS] | Cible Makefile `make deps-audit` + format output JSON + texte | 0.5h |
| T-OPS-DEP-03 | [OPS] | Hook sprint closure (ajout template retro + check obligatoire) | 0.5h |
| T-OPS-DEP-04 | [OPS] | Cron hebdo Slack #tech-lead-digest (delta semaine précédente) | 1h |

**Total** : 4h (≈ 1 pt)

### Risques

| Risque | Mitigation |
|---|---|
| Bruit excessif sur petits patches | Filtrer alerte par severity (Critical/High only) + outdated > 30j |
| Faux positifs Snyk vs composer audit (sources différentes) | Unifier via `composer audit --format=json` + dedupe |
| Migration majeure non triviale (Symfony 8 → 9) | Tag major upgrade hors automatic — capture ticket dédié |
| Dépendances dev-only flagées | Distinguer prod / dev dans output |

### Trigger origine

Sprint-026 — MAGO-LINT-BATCH-002 a baselined 1431 issues mais a aussi signalé `allow-possibly-undefined-array-keys = false is deprecated` (mago 1.26 deprecation) → exemple de drift que cette story éviterait. Snyk déjà actif sur PRs (cf checks #298) mais pas de vue globale périodique.

### Métriques succès

- 0 CVE Critical/High > 7 jours non patchée
- < 30 packages outdated minor cumulés
- Sprint-retro inclut systématiquement section "Deps audit : ✅ / ❌"
- Réduction CVE backlog -50 % sur 3 sprints

### Périmètre

- ✅ Composer (PHP packages)
- ✅ Yarn / npm (JS packages — assets compilés)
- ❌ Docker base images (couvert Snyk container scan séparé)
- ❌ Migrations PHP majeures (couvert ADR + sprint dédié)

### Liens

- Composer audit : `docker compose exec app composer audit --format=json`
- Yarn audit : `docker compose exec app yarn audit --json` (ou `npm audit`)
- CONTRIBUTING.md : section déjà existante "Snyk" — étendre avec workflow audit local
- Sprint-026 retro (à venir) : capture gap process
- Related : OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK (même pattern process fin sprint)

---

**Date** : 2026-05-16
**Auteur** : Tech Lead (capture autopilote)
**Version** : 1.0.0
