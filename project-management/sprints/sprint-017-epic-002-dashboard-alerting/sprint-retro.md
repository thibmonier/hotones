# Sprint Retrospective — Sprint 017

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 017 — EPIC-002 Dashboard + Alerting |
| Date | 2026-05-07 |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **PRs incrémentales par axe** (US-094 + TEST-COVERAGE-006 bundlés #189 ; US-095 séparée #190). CI rapide + reviewers ciblés. |
| K-2 | **Capacité libre consommée par anticipation sprint suivant** (US-095 sprint-018 anticipée). Vélocité explosive sans dette. |
| K-3 | **Detection bug `Invoice::$invoiceNumber` non-init en Unit** : tests dévoilent défaut design UC (isset() sur typed property). Documenté inline + Integration couvre happy path. |
| K-4 | **Runbook on-call rédigé en même temps que `SlackAlertingService`** : pas de doc dette. Niveau 1/2/3 + setup Sentry alerts + checks quotidiens. |
| K-5 | **Cache Redis dédié `cache.analytics`** : isolation pool TTL 300s vs cache `app` général. Prévient invalidation croisée. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Hook pre-commit auto-add `save-db/` (DB dump PII)** : amend manuel à 2 reprises (#189 + #190). | `.gitignore` push initial sur main aurait évité re-stage. À porter via PR isolée chore. |
| L-2 | **Bug `Invoice::$invoiceNumber` non-init en Unit pas fixé** : tests skipped happy path → couverture Integration only. | Story dédiée à filer `US-INVOICE-DRAFT-UC-INVOICENUMBER-INIT-FIX` (1 pt) sprint-018 ou backlog. |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Vélocité tracking moyenne mobile 6 sprints** au lieu de 10 (variance EPIC-001 → EPIC-002 dilue signal). | Capacity planning plus précis sprint-018+ (différence vélo Migration vs Feature). |
| S-2 | **Tests régression assertion `<?php` raw source** désormais routine smoke test. À étendre `application/octet-stream` Content-Type pour autres routes critiques. | US-090 post-mortem : éviter bug similaire latent. |
| S-3 | **ADR pour décision technique majeure dans CHAQUE PR** (pas seulement décisions process). Ex : choix `cache.analytics` pool dédié, choix `process_psr_3_messages` prod. | Audit décisions historiques onboarding. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Tests Unit qui simulent flot complet UC traversant lifecycle Doctrine.** | Path happy = Integration. Unit = paths erreur + DTO + logique pure. Bug Invoice révèle limitation Unit isolation. |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Bundle 2 stories liées dans 1 PR si scope cohérent** (US-094 + TEST-COVERAGE-006 = même context observabilité + tests). | Réduit overhead review + déploiement. |
| M-2 | **Capacité libre = anticipation sprint suivant** plutôt que stories speculatives. | Lissage charge inter-sprints. |

---

## 🎯 Actions concrètes Sprint 018

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Configurer manuellement `SLACK_WEBHOOK_URL` Render dashboard prod + staging (T-094 OPS héritée) | Tech Lead | Sprint-018 J1 |
| A-2 | Créer alert rules Sentry → Slack `#alerts-prod` (errors > 10/h, p95 > 2s, quota > 80 %) | Tech Lead | Sprint-018 J1 |
| A-3 | File story `US-INVOICE-DRAFT-UC-INVOICENUMBER-INIT-FIX` (1 pt) backlog | PO | Sprint-018 backlog refinement |
| A-4 | Story `chore` pour `.gitignore` `save-db/` push sur main (orphan branch isolé) | Tech Lead | Sprint-018 J1 (trivial) |

---

## 📊 Directive Fondamentale

> « Indépendamment de ce que nous découvrons aujourd'hui, nous comprenons et
> croyons sincèrement que chacun a fait du mieux qu'il pouvait, étant donné
> ce qui était connu à ce moment-là, ses compétences et capacités, les
> ressources disponibles et la situation rencontrée. »

---

## 🚀 Sprint-017 takeaway

Sprint le plus productif du quarter (130 % engagement ferme livré). EPIC-002
focus business value (dashboard + alerting + observabilité) → effet immédiat
côté pilotage agence. Capacité libre absorbée sans casser DoD.

**Indicateur santé équipe** : explosion de vélocité **sans dette technique
introduite** (PHPStan max, CS-Fixer, 835 tests Unit pass continu).

---

## 🔗 Liens

- Sprint-017 review : `sprint-review.md`
- Sprint-016 retro : `../sprint-016-*/sprint-retro.md`
- Sprint-018 kickoff : `../sprint-018-*/sprint-goal.md` (à venir)
