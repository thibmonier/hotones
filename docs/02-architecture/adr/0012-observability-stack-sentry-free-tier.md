# ADR-0012 — Stack observabilité : Sentry free tier (option C — différer upgrade)

| Champ | Valeur |
|---|---|
| Statut | ✅ Accepté |
| Date | 2026-05-07 |
| Sprint | sprint-016-epic-002-observability |
| Story | EPIC-002-KICKOFF-WORKSHOP + US-091 |
| Auteur | Tech Lead + PO (atelier sprint-016 J1) |

---

## Contexte

EPIC-002 (Observabilité & Performance) démarre sprint-016. Atelier PO
sprint-016 J1 a tranché les 5 questions ouvertes :

| # | Question | Réponse |
|---|---|---|
| 1 | Budget mensuel | **C — Différer upgrade** : rester sur free tier jusqu'à dépassement quota observé |
| 2 | Stack | **Sentry free tier** (déjà installé : `sentry/sentry-symfony ^5.8.3`) |
| 3 | 5 KPIs business | DAU/MAU + projets/jour + devis signés/mois + factures émises + conversion devis→projet + revenu 30j + marge moyenne projet (7 KPIs) |
| 4 | Cold start Render | **Plan starter** ($7/mois) |
| 5 | Smoke test scope | **Minimum** (homepage + /health) |

---

## Décision

**Stack observabilité = Sentry plan Developer (free)**.

Configuration via `config/packages/sentry.yaml` (when@prod) :

```yaml
when@prod:
    sentry:
        dsn: '%env(SENTRY_DSN)%'
        options:
            traces_sample_rate: 0.05    # 5 % transactions
            profiles_sample_rate: 0.10  # 10 % des transactions échantillonnées profilées
            send_default_pii: false      # RGPD safe
            ignore_exceptions: [...]     # 4xx + auth (bruit)
```

### Quotas Sentry Developer (gratuit)

| Quota | Limite |
|---|---|
| Errors | 5 000 / mois |
| Transactions (performance) | 10 000 / mois |
| Replays | 50 / mois |
| Profiles | 1 000 / mois (si applicable) |
| Retention | 30 jours errors / 90 jours transactions |

### Volumétrie attendue HotOnes

- ~10k requests/jour si 50 users actifs × 200 req/user = ~300k req/mois
- Avec sampling 5 % : **~15k transactions/mois**
- Errors typique < 1k/mois (CI propre + ignore exceptions 4xx)

→ Léger dépassement transactions free tier (~15k vs 10k cap), Sentry
drop silencieusement. Acceptable selon option C.

### Trigger upgrade Team plan ($25/mois)

Upgrade automatique quand l'un des seuils est atteint :
1. Dépassement quota transactions > 80 % (alerte Sentry → email PO)
2. > 5 errors critiques/jour qui méritent investigation profonde
3. > 30 users actifs prod (proxy traction commerciale)

---

## Alternatives écartées

| Alternative | Raison du rejet |
|---|---|
| **Datadog** ($30/host/mois) | Coût premium injustifié au stade pré-revenue. Re-évaluer post-traction commerciale. |
| **OpenTelemetry native + Loki/Grafana self-hosted** | Coût ops élevé (un service de plus à maintenir). HotOnes = mono-équipe. À considérer si Sentry quotas insuffisants ET Sentry Team ne convient pas. |
| **Plan Sentry Team payant immédiat** ($25/mois) | Pas justifié sans traction commerciale prouvée. Option C PO = différer. |

---

## Conséquences

### Positives

- **0€ infra observability dépense court terme**.
- **Setup déjà fait** (`sentry/sentry-symfony` installé sprint-002, juste config sampling à ajuster).
- **Errors alerting** par défaut (Sentry Slack/email integration free).
- **DSN injectée via Render dashboard** (`SENTRY_DSN` env var dans render.yaml).

### Négatives / À surveiller

- Dépassement quota transactions probable mois 3-6 si traction. Mitigation : alerte Sentry sur 80 % quota.
- Pas de profiling pleinement exploitable (1k profiles/mois limite).
- Pas de Cron monitoring (feature Team plan). Mitigation : workflow GH Action smoke test post-deploy (US-092) prend le relais.

---

## Plan US-091..US-095 finalisé post-atelier

| ID | Titre | Pts | Sprint |
|---|---|---:|---|
| US-091 | Sentry free tier configuré prod (sampling 5 %) | 5 | 016 |
| US-092 | Smoke test post-deploy GH Action (homepage + /health) | 3 | 016 |
| US-093 | Dashboard 7 KPIs business (DAU/MAU + projets/devis/factures/conversion/revenu/marge) | 5 | 017 |
| US-094 | Alerting Sentry → Slack canal `#alerts-prod` (quota 80 % + errors critiques) | 3 | 017 |
| US-095 | Logging structuré JSON + ingestion Sentry Logs (free tier) | 3 | 018 |

**Total EPIC-002 : 19 pts** (~3 sprints).

---

## Liens

- ADR-0011 (Foundation stabilized — pattern parallèle)
- Sprint-016 sprint-goal.md
- Render runbook (`docs/05-deployment/render-runbook.md`)
- Atelier EPIC-002 brief : `project-management/backlog/epics/EPIC-002-observability-and-performance.md`
