# Sprint Retrospective — Sprint 018

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 018 — EPIC-002 finition + dette technique |
| Date | 2026-05-08 (clôture anticipée — sprint J1) |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Bundle dette technique + EPIC-002 finition même sprint**. Pas de stagnation features ni dette accumulée. Pattern à répéter. |
| K-2 | **Phase 3 strangler fig BC Contributor : controller dédié API DDD séparé** (vs refactor du controller flat existant). Rétro-compat 100 %, blast radius minimal. |
| K-3 | **`ReflectionProperty::isInitialized()` pour bypass property hook getter** PHP 8.4 typed property : pattern réutilisable pour bug similaire (Order, Project, Client si rencontré). |
| K-4 | **Smoke extended JSON shape validation via python3 inline** : assertion structurelle léger (vs intégrer outils externes type schemathesis). |
| K-5 | **Coverage push 55 % via 1 seul Aggregate Root (Invoice)** : ROI optimal vs disperser sur plusieurs petits VOs. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Docker Desktop crash mid-sprint** — bloque tests locaux + commit hook (PHPStan validation déférée CI sur PR #196). | Investiguer alternative WSL2 ou OrbStack pour Mac stability. Buffer time pour incidents env. |
| L-2 | **PHPStan local échoue (APCu manquant host)** — drift CI vs local. | Install pecl APCu local OR documenter limitation acceptée + workflow `--no-verify` quand Docker down. |
| L-3 | **Atelier PO EPIC-003 reporté** : sprint-019 dépendant input user. | Préparer brief atelier en avance (J0) pour user response asynchrone. |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Vélocité tracking moyenne mobile 6 sprints** (déjà identifié sprint-017 retro mais pas encore fait). | Capacity planning sprint-019 plus précis (signal moins bruité par sprints migration EPIC-001). |
| S-2 | **Brief atelier PO préparé J0 (avant kickoff)** pour stories nécessitant input. | Évite report sprint-019 type EPIC-003 atelier. |
| S-3 | **Coverage targeting Aggregate Roots prioritaires** (Order, Project entities après Invoice). | Continue escalator step 8 → 60 % via ROI Domain. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Bypass `--no-verify` git commit comme habitude.** | Acceptable exception Docker down, MAIS ne doit pas devenir norme. CI rattrape, pas raison de relâcher hooks locaux. |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Bundle stories liées dans 1 PR si scope cohérent** (ex : US-094 + TEST-COVERAGE-006 sprint-017). Sprint-018 = 6 PRs distinctes — pas de mauvais choix mais 1-2 bundles possibles. | Moins overhead review + déploiement. |
| M-2 | **Tests régression assertions explicites smoke** (US-090 raw `<?php` check, KPI markers dashboard, JSON shape API DDD). | Évite bugs latents type US-090. Pattern à généraliser routes critiques. |

---

## 🎯 Actions concrètes Sprint 019

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Configurer manuellement `SLACK_WEBHOOK_URL` Render prod + staging (héritage sprint-017 retro A-1) | Tech Lead | Sprint-019 J1 |
| A-2 | Créer alert rules Sentry → Slack `#alerts-prod` (héritage sprint-017 retro A-2) | Tech Lead | Sprint-019 J1 |
| A-3 | Configurer SMOKE-PROD-EXTENDED OPS (user smoke + GH secrets + GH var `SMOKE_EXTENDED_ENABLED`) | Tech Lead | Sprint-019 J1 |
| A-4 | Tenir atelier EPIC-003 scoping (PO + Tech Lead) — 5 questions arbitrées | PO + Tech Lead | Sprint-019 J1-J2 |
| A-5 | Investiguer alternatives Docker Desktop Mac (OrbStack ?) | Tech Lead | Sprint-019 backlog refinement |
| A-6 | Install APCu pecl local OU documenter workaround `--no-verify` | Tech Lead | Sprint-019 backlog refinement |

---

## 📊 Directive Fondamentale

> « Indépendamment de ce que nous découvrons aujourd'hui, nous comprenons et
> croyons sincèrement que chacun a fait du mieux qu'il pouvait, étant donné
> ce qui était connu à ce moment-là, ses compétences et capacités, les
> ressources disponibles et la situation rencontrée. »

---

## 🚀 Sprint-018 takeaway

Deuxième sprint consécutif au-dessus de l'engagement ferme (130 % puis 124 %).
Pattern : **EPIC-002 finition + dette technique + capacité libre absorbée
sans casser DoD**. Vélocité ferme tendance haussière → recalibrer engagement
sprint-019 vers 12 pts ferme (vs 8.5 sprint-018).

**Indicateur santé équipe** : 30 tests Unit Domain Invoice écrits sans Docker
(pure host PHP) — code domain-pure réellement isolé, validation d'architecture
hexagonale.

**Risk visible** : Docker Desktop fragile sur Mac. Action A-5 sprint-019.

---

## 🔗 Liens

- Sprint-018 review : `sprint-review.md`
- Sprint-017 retro : `../sprint-017-*/sprint-retro.md`
- Sprint-019 kickoff : `../sprint-019-*/sprint-goal.md`
