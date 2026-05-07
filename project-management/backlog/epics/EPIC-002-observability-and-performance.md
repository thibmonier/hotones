# EPIC-002 : Observabilité & Performance Production

## Métadonnées

- **ID** : EPIC-002
- **Statut** : 🟡 Brief (atelier PO requis pour validation finale)
- **Priorité** : **High**
- **MMF** : « Toute requête utilisateur en prod tracée bout-en-bout (request → controller → DB → response) avec p95 < 800ms et alerting automatique sur erreurs critiques »
- **Créé le** : 2026-05-07 (sprint-015 brief)
- **Auteur** : Tech Lead

---

## Contexte

EPIC-001 (DDD strangler fig) a livré la base architecturale propre. EPIC-002
exploite cette fondation pour faire mûrir la **chaîne de production** :
observabilité, performance, fiabilité runtime.

**Déclencheurs PO** :

1. Bug US-090 (sprint-014) : `/health` raw PHP source servi pendant **4 mois**
   sans détection. Faute d'observabilité prod, on ne sait pas ce qui casse
   silencieusement.
2. Sprint-014 retro action S-1 (« Smoke test post-deploy automatique sur
   fixtures Render via GitHub Action ») — à élever en story EPIC-002.
3. Cold start free tier Render 50s = mauvaise UX premier visiteur.
4. Pas de métriques business (Active users / DAU, projets créés/jour, etc.)
   exploitables par le PO pour piloter.

---

## Objectifs Business

- **Détection précoce des incidents** : alertes sur p95/p99 latence, erreurs
  500, /health KO, DB connection failed.
- **Visibilité business** : dashboard avec métriques DAU, projets créés,
  devis signés, factures émises (exposition stakeholders).
- **Performance** : p95 < 800ms en prod (homepage + dashboard analytics).
- **Fiabilité runtime** : 99.5 % uptime mesuré (cible plan starter Render).
- **Compétitivité** : observabilité = pré-requis pour pitch entreprise B2B.

---

## Critères de succès (MMF)

| Critère | Cible | Mesure |
|---|---|---|
| Tracing distribué bout-en-bout | 100 % requêtes prod tracées | OpenTelemetry / Sentry / Datadog dashboard |
| p95 latence homepage | < 800ms | RUM Sentry / NewRelic |
| Alerting erreurs critiques | < 5 min détection | PagerDuty / Slack webhook |
| `/health` smoke post-deploy | automatique CI | GitHub Action post-merge main |
| Dashboard métriques business | 5 KPIs minimum live | Render service / API admin |
| Cold start élimination | plan starter actif OU keep-alive | render.yaml validé prod |

---

## Bounded contexts impactés

EPIC-002 transverse : touche tous les BCs DDD existants pour ajouter
instrumentation (tracing, metrics, logging structuré). Pas de nouveau BC.

---

## User Stories candidates (5 max post-atelier)

⚠️ **À affiner** lors de l'atelier kickoff sprint-016 (sprint-015 capacité
absorbée par Contributor ACL + Coverage step 5 + tests Application Vacation).

| ID | Titre | Pts | Type |
|---|---|---:|---|
| US-091 | OpenTelemetry tracing intégré (Sentry / Datadog) | 5 | Tech |
| US-092 | `/health` smoke test post-deploy automatique (GitHub Action sur main) | 3 | Ops |
| US-093 | Dashboard métriques business (5 KPIs prod) | 5 | Feature |
| US-094 | Alerting Slack/PagerDuty erreurs 500 + p95 latence | 3 | Ops |
| US-095 | Logging structuré JSON + Loki/Grafana ingestion | 3 | Tech |

**Total brief** : 19 pts (≈ 1,5 sprints).

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| OpenTelemetry intégration | Symfony 8 / PHP 8.5 SDK compatible | 🟡 à valider |
| Smoke test post-deploy | GitHub Actions secret (URL prod + token) | 🟢 |
| Dashboard métriques | Render plan starter (résolution cold start) | 🟡 PO décision |
| Alerting | Compte Slack workspace + webhook | 🟡 à provisionner |

---

## Risques

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| OpenTelemetry overhead > 50ms p95 | Moyenne | Moyen | Sampling 10 % + benchmark avant rollout |
| Dépassement budget infra (Sentry/Datadog) | Faible | Faible | Plan free Sentry suffit < 10k events/mois |
| Cold start Render starter insuffisant | Faible | Moyen | Fallback keep-alive UptimeRobot |
| Logging JSON casse outils existants | Faible | Faible | Adapter progressif via channels Monolog |

---

## Plan macro (3 sprints estimés)

| Sprint | Stories | Livrables |
|---|---|---|
| Sprint-016 | US-091 + US-092 (8 pts) | OpenTelemetry instrumentation + smoke test post-deploy |
| Sprint-017 | US-093 + US-094 (8 pts) | Dashboard métriques + alerting |
| Sprint-018 | US-095 (3 pts) + buffer | Logging structuré + ajustements |

---

## Liens

- Sprint-014 retro S-1 (smoke prod fixtures)
- Bug US-090 (PR #178 Render /health)
- ADR à venir : ADR-0012 (choix stack observabilité Sentry vs Datadog vs OTel native)

---

## Validation kickoff (atelier sprint-016)

**À discuter PO** :
1. Budget mensuel acceptable observabilité ($0 / $25 / $50 / $100+) ?
2. Stack préférée : Sentry (free tier généreux) vs Datadog (premium) vs OTel + Loki/Grafana self-hosted ?
3. Priorité dashboard métriques business : 5 KPIs essentiels à arrêter ?
4. Cold start résolution : payer plan starter Render ($7/mois) OU keep-alive externe gratuit ?
5. Smoke test post-deploy : scope minimum (homepage + /health) OU étendu (login + create project) ?

**Output attendu** : 5 user stories US-091..US-095 finalisées + sprint-016 sprint-goal.md.
