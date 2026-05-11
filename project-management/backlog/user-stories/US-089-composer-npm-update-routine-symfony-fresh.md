# US-089 — Composer + npm update routine (Symfony fresh)

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> Source : observation thibmonier 2026-05-07. Pas de routine de mise à jour
> régulière. Symfony et autres deps en retard sur upstream.

- **Implements** : FR-OPS-11 — **Persona** : équipe dev, P-OPS — **Estimate** : 2 pts — **MoSCoW** : Should

### Card
**As** développeur
**I want** une routine `composer update` + `composer bump` + `npm update` programmée
**So that** les dépendances Symfony / npm restent à jour sans dérive lourde.

### Acceptance Criteria
```
Given politique de mise à jour mensuelle définie
When composer update + composer bump + npm update exécutés
Then composer.lock + package-lock.json mis à jour
And tests Unit + E2E passent
And PR ouverte automatiquement (Dependabot OU script manuel)
```
```
Given Symfony LTS (currently 7.x → 8.0)
When upgrade major
Then ADR créé pour breaking changes connus
And migration guide consulté
```

### Technical Notes
- Évaluer Dependabot config (déjà actif ? GH Settings)
- Sinon script `bin/console app:deps-update` (composer outdated --strict)
- Cadence : mensuelle (1er lundi du mois)
- composer bump : prefer si après update tests verts
- npm : `npm update` + `npm audit fix` (sans --force)

### Tasks
- [ ] T-089-01 [OPS] Activer / configurer Dependabot (composer + npm) (1 h)
- [ ] T-089-02 [OPS] Workflow GH Action mensuel `deps-update.yml` si Dependabot insuffisant (1 h)
- [ ] T-089-03 [DOC] CONTRIBUTING.md : section « cadence updates dépendances » (0,5 h)

---

