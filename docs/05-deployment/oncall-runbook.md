# Runbook On-Call — Alerting & Incidents

> **US-094** (sprint-017 EPIC-002) — Procédure on-call pour alertes prod.

## Sources d'alertes

| Source | Type | Canal Slack |
|---|---|---|
| Sentry (errors / performance) | Auto via Sentry → Slack integration | `#alerts-prod` |
| Smoke test post-deploy (minimum + extended) | Manuel via failure GH Action | `#alerts-prod` (TODO US-094 OPS step) |
| Quota Sentry > 80 % | Auto via Sentry alert rule | `#alerts-prod` |
| App-side custom (batch failures, etc.) | Manuel via `SlackAlertingService` | `#alerts-prod` |

---

## Sentry → Slack integration (T-094-02)

### Setup Sentry alert rules

1. Sentry dashboard → Project HotOnes → Alerts → Create Alert Rule
2. Choisir trigger :
   - **Errors > 10 / hour** (issue alert)
   - **p95 latency > 2 sec** (metric alert)
   - **Quota transactions > 80 %** (organization alert)
3. Action : Slack workspace → channel `#alerts-prod`
4. Save & enable

### Setup Slack incoming webhook

1. Slack workspace → Apps → Incoming Webhooks → Add Configuration
2. Channel : `#alerts-prod`
3. Copier URL `https://hooks.slack.com/services/T.../B.../...`
4. Render dashboard prod : env var `SLACK_WEBHOOK_URL` = URL copiée
5. Render dashboard staging : idem

---

## Procédure on-call

### Niveau 1 — Tech Lead (réception alerte Slack)

**Délai d'ack** : 15 min ouvrables.

1. Ouvrir lien Sentry depuis message Slack
2. Identifier impact : nombre d'utilisateurs touchés ?
3. Trier sévérité :
   - **INFO / WARNING** : noter, traiter sous 24h
   - **ERROR** : investigation immédiate
   - **CRITICAL** : escalation Niveau 2 + rollback considéré

### Niveau 2 — Escalation (PO + Tech Lead)

Si Niveau 1 ne peut pas résoudre en 1h :

1. Slack : `@channel` dans `#alerts-prod` avec contexte
2. Décider rollback :
   - `git revert` du dernier merge problématique
   - `git push origin main` → déclenche Render redeploy
   - Vérifier smoke test post-deploy passe
3. Si rollback impossible : maintenance mode (page statique)

### Niveau 3 — Incident majeur (downtime > 30 min)

1. Communiquer aux clients (statut Render OU email blast)
2. Post-mortem obligatoire dans 48h
3. ADR si décision architecture

---

## Checks quotidiens (matin)

| Check | Source | Cible |
|---|---|---|
| Render status | dashboard.render.com | tous services « running » |
| Sentry quota transactions | sentry.io | < 70 % du quota mensuel |
| Sentry quota errors | sentry.io | < 70 % du quota mensuel |
| Last deploy status | GH Actions | smoke test green |

---

## App-side alerting (`SlackAlertingService`)

Pour alerter depuis l'app PHP (vs Sentry-side) :

```php
use App\Service\Alerting\SlackAlertingService;
use App\Service\Alerting\AlertSeverity;

final class MyBatchProcessor
{
    public function __construct(
        private readonly SlackAlertingService $slackAlerting,
    ) {}

    public function process(): void
    {
        try {
            // ... batch logic
        } catch (\Throwable $e) {
            $this->slackAlerting->sendAlert(
                title: 'Batch processing failed',
                body: $e->getMessage(),
                severity: AlertSeverity::ERROR,
            );

            throw $e;
        }
    }
}
```

**Usages typiques** :
- Batch jobs (cron) qui échouent silencieusement
- Quota d'API externe atteint
- Migration de données qui détecte incohérence
- Smoke test interne

**No-op si SLACK_WEBHOOK_URL vide** (dev / CI). Logs uniquement.

---

## Smoke test extended (sprint-018)

### Activation OPS

1. **Créer un user dédié smoke** sur prod :
   - Email `smoke@hotones.local` (no real inbox needed)
   - Mot de passe fort généré 1Password
   - Rôle `ROLE_USER` strict (read-only)
   - **2FA désactivé** (workflow auth simple form_login)
2. **Configurer GitHub repo secrets** :
   - `SMOKE_USER_EMAIL` = email user smoke
   - `SMOKE_USER_PASSWORD` = mot de passe user smoke
3. **Activer GitHub variable** :
   - `SMOKE_EXTENDED_ENABLED` = `true` (Settings → Variables → Actions)

### Assertions couvertes

| Step | Endpoint | Vérifie |
|---|---|---|
| Login | POST `/login` (form_login + CSRF) | Auth path opérationnel + session cookie |
| Dashboard business | GET `/admin/dashboard/business` | US-093 regression check (KPI markers présents) |
| API DDD Contributor | GET `/api/contributors/active` | Phase 3 strangler fig regression check (JSON shape valid) |

### Failure response

Si `smoke-extended` job fail → alert Slack `#alerts-prod` (config OPS US-094).
Indique typiquement :
- Login broken (changement firewall config / form fields renamed)
- Dashboard 500 ou markers KPI manquants (regression dashboard)
- API DDD endpoint cassé (regression Phase 3)

Niveau 2 escalation si bloque > 1h.

---

## Trigger upgrade Sentry Team plan

Cf ADR-0012 — déclencher upgrade quand :
1. Quota transactions > 80 % observé sur 2 mois consécutifs
2. > 5 errors critiques/jour qui méritent investigation profonde
3. > 30 users actifs prod (proxy traction commerciale)

Coût : ~$25/mois Team plan (vs $0 Developer free).

---

## Liens

- ADR-0012 : stack observabilité (Sentry free tier)
- US-091 : Sentry sampling 5 %
- US-092 : smoke test post-deploy
- Render runbook : `docs/05-deployment/render-runbook.md`
