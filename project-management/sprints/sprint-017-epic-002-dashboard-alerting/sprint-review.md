# Sprint Review — Sprint 017

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 017 — EPIC-002 Dashboard + Alerting |
| Date | 2026-05-07 |
| Sprint Goal | Dashboard 7 KPIs business + alerting Sentry → Slack + escalator coverage 50 % |
| Capacité | 16 pts |
| Engagement ferme | 10 pts |
| Capacité libre | 6 pts |
| Livré | **13 pts (130 % engagement ferme)** |

---

## 🎯 Sprint Goal — Atteint ✅

**Goal :** « Exposer la valeur EPIC-002 au PO via dashboard 7 KPIs business prod
+ alerting Sentry/Slack pour erreurs critiques. Pousser escalator coverage
post-EPIC-001 à 50 % (step 6). »

**Résultat :**
- Dashboard prod `/admin/dashboard/business` avec 7 KPIs cachés Redis 5 min
- `SlackAlertingService` stateless POST webhook + runbook on-call
- `CreateInvoiceDraftUseCaseTest` ajouté (escalator step 6 → 50 %)
- Bonus capacité libre : US-095 logging structuré JSON anticipée sprint-018

---

## 📦 User Stories Livrées

| Story | Pts | PR | Statut |
|---|---:|---|---|
| US-093 Dashboard 7 KPIs business prod | 5 | #188 | ✅ mergée |
| US-094 Alerting Sentry → Slack | 3 | #189 | ✅ mergée |
| TEST-COVERAGE-006 escalator step 6 (45→50 %) | 2 | #189 | ✅ mergée |
| US-095 Logging structuré JSON (capacité libre) | 3 | #190 | ✅ mergée |
| **Total** | **13** | | **13/13 (100 %)** |

---

## 📈 Métriques

### Dashboard `/admin/dashboard/business`

7 KPIs business calculés via `BusinessKpiService` :
1. DAU / MAU (User table — login activity)
2. Projets créés par jour (Project, agrégation startDate)
3. Devis signés / mois (Order avec statut signé)
4. Factures émises (Invoice + montant total)
5. Taux conversion devis → projet
6. Revenu trail 30j (Invoice payée)
7. Marge moyenne par projet (Project + WorkItem coût)

Cache Redis dédié `cache.analytics` TTL 300s (5 min).

### Alerting Sentry → Slack

```php
// SlackAlertingService stateless — POST sur incoming webhook
$slackAlerting->sendAlert(
    title: 'Batch processing failed',
    body: $exception->getMessage(),
    severity: AlertSeverity::ERROR,
);
```

- No-op si `SLACK_WEBHOOK_URL` vide (dev/CI safe)
- Niveau Niveau 1 (Tech Lead 15min ack) / Niveau 2 (PO + rollback) / Niveau 3 (downtime > 30min)
- Channel défaut `#alerts-prod` configurable env var

### Logging structuré JSON

- `process_psr_3_messages: true` sur prod (interpolation `{var}`)
- Tests Unit `ContextProcessor` (5) + `PerformanceProcessor` (3)
- Doc `docs/05-deployment/logging-conventions.md` : schéma JSON, conventions appel, exploitation Render dashboard

### Coverage post-EPIC-001 escalator

| Step | Cible | Atteint |
|---|---:|---:|
| Step 5 (sprint-016) | 45 % | ✅ 45 % |
| **Step 6 (sprint-017)** | **50 %** | ✅ via `CreateInvoiceDraftUseCaseTest` (4 tests Unit) |

Note : happy path UC bloqué par bug typed property `Invoice::$invoiceNumber` non-init en Unit (PrePersist Doctrine listener absent). Couverture happy path via Integration Docker DB existante.

---

## 🚀 Vélocité 10 derniers sprints

| Sprint | Engagement | Livré | % |
|---|---:|---:|---:|
| 008 | 8 | 8 | 100 % |
| 009 | 13 | 13 | 100 % |
| 010 | 13 | 13 | 100 % |
| 011 | 13 | 13 | 100 % |
| 012 | 13 | 13 | 100 % |
| 013 | 9 | 9 | 100 % |
| 014 | 12 | 12 | 100 % |
| 015 | 13 | 13 | 100 % |
| 016 | 11 | 11 | 100 % |
| **017** | **10 (ferme) + 6 (libre)** | **13** | **130 % ferme** |

Vélocité moyenne 10 sprints : ~11.6 pts. Sprint-017 = 13 pts livrés, légèrement au-dessus moyenne.

---

## 🎯 Démonstration

### Dashboard prod
URL : `https://hotones.onrender.com/admin/dashboard/business`
- 7 KPIs affichés avec valeurs courantes
- Refresh manuel disponible (cache 5 min)

### Alerting
- Sentry alert rules configurés (errors > 10/h, p95 > 2s, quota > 80 %)
- Slack channel `#alerts-prod` reçoit notifications
- App-side : `SlackAlertingService` injectable dans batch jobs

### Logging
- Logs prod Render dashboard format JSON
- `extra.request_id` permet correlation requête bout-en-bout
- PSR-3 placeholders interpolés (lisibilité humaine + parsing facile)

---

## 💬 Feedback PO

(À recueillir lors de la revue présentielle)

Questions à poser :
1. Dashboard 7 KPIs : champs corrects vs besoin métier réel ?
2. Marge moyenne projet : calcul WorkItem coût vs définition agence ?
3. Slack channel : `#alerts-prod` OK ou créer canaux par sévérité ?
4. Sprint-018 : continuer EPIC-002 (US-096 X-Request-Id + smoke prod extended) ou pivot EPIC-003 (à scoper) ?

---

## 🔗 Liens

- PR #188 — Dashboard 7 KPIs
- PR #189 — Slack alerting + Invoice UC tests
- PR #190 — Logging JSON coverage + PSR-3
- ADR-0012 — Stack observabilité (Sentry free tier)
- Runbook on-call : `docs/05-deployment/oncall-runbook.md`
- Logging conventions : `docs/05-deployment/logging-conventions.md`
