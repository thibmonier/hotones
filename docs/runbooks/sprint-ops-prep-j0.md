# Sprint OPS Prep J0 — Runbook

> Pattern systématique : identifier J0 (avant kickoff) toute story du sprint
> requérant credentials, secrets, config admin externe ou accès console
> tierce. Bloquer ces tickets J0 si dépendance non satisfaite, ou réallouer
> hors sprint.

**Origine** : sprint-019 retro S-1 + A-8 (héritage sprint-017 + 018 OPS
holdover Slack/Sentry/SMOKE reporté 3 sprints consécutifs).

**Cible** : éliminer holdover OPS chronique → 100 % engagement honoré dès
J1.

---

## 1. Quand exécuter

| Moment | Action |
|---|---|
| **J-2 sprint kickoff** | PO + Tech Lead screening backlog candidat sprint suivant |
| **J0 sprint kickoff matin** | Atelier OPS prep (~30 min) AVANT Sprint Planning P1 |
| **J0 sprint kickoff après-midi** | Sprint Planning P1 + P2 avec tickets OPS pré-validés |

Si screening J-2 détecte > 2 pts OPS bloqués sur credentials manquants →
**re-scoper sprint** avant kickoff.

---

## 2. Checklist screening (par story candidate)

Pour chaque story candidate, répondre aux 6 questions :

### Q1 — Credentials externes ?

- [ ] Slack workspace incoming webhook ?
- [ ] Sentry org/project admin token ?
- [ ] Render API key + dashboard access ?
- [ ] GitHub repo secrets push (Settings → Secrets) ?
- [ ] GitHub repo variables (Settings → Variables) ?
- [ ] Cloud provider IAM (AWS, GCP, Azure) ?
- [ ] DNS provider (Cloudflare, Route53) ?
- [ ] Email provider (SendGrid, Mailgun) API key ?
- [ ] Payment provider (Stripe) restricted key ?
- [ ] OAuth provider client_id/secret (Google, GitHub) ?

**Si OUI à au moins 1** → story = **OPS-tagged** dans backlog. Owner
identifié + access confirmed avant Sprint Planning.

### Q2 — Console admin tierce ?

- [ ] Hosting dashboard (Render, Heroku, Railway, Vercel) ?
- [ ] Monitoring (Sentry, Datadog, New Relic) ?
- [ ] CDN (Cloudflare, Fastly) ?
- [ ] Email (SendGrid, Postmark) ?
- [ ] Analytics (Plausible, GA) ?

**Si OUI** → person avec access nominé. Si owner = absent sprint → re-allocate.

### Q3 — Données prod ?

- [ ] Audit data prod (queries SQL, exports) ?
- [ ] Migration data sensitive (RGPD, PII) ?
- [ ] Restore backup ?
- [ ] User credentials prod (test smoke, e2e) ?

**Si OUI** → DBA/Tech Lead access prod confirmé + backup avant any DDL.

### Q4 — Config infra ?

- [ ] Render env vars push ?
- [ ] Worker queue config (Symfony Messenger DSN) ?
- [ ] Cron schedule modification ?
- [ ] Redis / cache invalidation manuelle ?

**Si OUI** → Tech Lead approve + plan rollback documenté.

### Q5 — Coordination tierce ?

- [ ] Tier (legal, compliance, sécu) sign-off ?
- [ ] Stakeholder externe (client, fournisseur) accord ?
- [ ] Communication utilisateur (email broadcast, banner) ?

**Si OUI** → demande envoyée J-3 minimum, accord reçu J0.

### Q6 — Dépendances bloquantes ?

- [ ] Story dépend d'une autre story même sprint ?
- [ ] Story dépend d'un sprint antérieur non terminé ?
- [ ] Pré-requis CI/CD (GitHub Action existante, secret CI) ?

**Si OUI** → ordre d'exécution sprint figé + bloquants flagged blockedBy.

---

## 3. Tagging backlog

Ajouter au front-matter ou première ligne de la story le tag OPS :

```yaml
ops:
  required: true
  type: ["slack-webhook", "sentry-alert", "gh-secrets"]
  owner: "Tech Lead"
  blocker: "credentials access confirmed J0"
```

Ou en markdown plain :

```markdown
## ⚙️ OPS Prep
- **Required**: Slack webhook + Sentry alert rules
- **Owner**: Tech Lead
- **Confirmed J0**: ☐ webhook URL ☐ Sentry token ☐ #alerts-prod created
```

---

## 4. Décision J0

Pour chaque story OPS-tagged :

| État credentials/access | Décision |
|---|---|
| ✅ Tous confirmés | Story go sprint |
| 🟡 1-2 manquants, owner action immédiate possible | Story go sprint + risk flagged daily |
| 🔴 Blocked owner absent / access review en cours | Story **out** sprint → backlog refinement |

**Règle d'or** : aucune story OPS tagged ne rentre en sprint sans
confirmation J0 explicite. Pas de « on verra J1 ».

---

## 5. Exemples historiques

### Sprint-017 (EPIC-002 dashboard + alerting)

Holdover **Slack webhook + Sentry alerts** → reporté sprint-018 (US-094
0.5 pt).

**Cause root** : webhook URL non créé J0, owner Tech Lead absent J1.
Pattern actuel aurait flagged Q1 + Q2 = OUT sprint.

### Sprint-018 (EPIC-002 finition dette)

Holdover **SMOKE-PROD user creds + GH secrets** → reporté sprint-019.

**Cause root** : user smoke prod non créé (admin BDD), GH secrets push
nécessite repo Settings access. Q1 (GH secrets) + Q3 (user prod) = OUT
sprint si pattern appliqué.

### Sprint-019 (EPIC-003 scoping)

Holdover **Slack + Sentry + SMOKE config** (Sub-epic B 1 pt) → reporté
sprint-020.

**Cause root** : Tech Lead occupé EPIC-003 ADR-0013 + ENV-DOCKER. OPS
sub-epic B « jamais le bon moment ». Pattern aurait imposé décision
J0 explicite : OUT ou reallocation.

### Sprint-020 (EPIC-003 Phase 2 ACL)

Sub-epic B sprint-020 (US-094-OPS + SMOKE-OPS, 1 pt total) **toujours
holdover** au moment du runbook. Ce runbook est livrable A-8 sprint-019
retro. Application **immédiate sprint-021**.

---

## 6. Métriques succès

| Métrique | Baseline 017-019 | Cible 021+ |
|---|---|---|
| Stories OPS holdover / sprint | 1-3 | 0 |
| % engagement honoré J1 ready | ~85 % | 100 % |
| Holdover récurrent même story (sprint count) | 3 (Slack/Sentry/SMOKE) | 0 |
| Atelier J0 OPS prep tenu | 0 / 4 sprints | 1 / 1 |

**Seuil alarme rétro** : si > 1 story OPS holdover sprint → root cause
analysis obligatoire + correctif pattern J0 prep.

---

## 7. Owners & escalation

| Domaine OPS | Owner primaire | Backup |
|---|---|---|
| Slack workspace admin | PO | Tech Lead |
| Sentry org admin | Tech Lead | (none) |
| Render dashboard | Tech Lead | (none) |
| GitHub repo Settings | PO + Tech Lead | (none) |
| Production DB | Tech Lead | (none) |
| CI/CD secrets | Tech Lead | PO |
| OAuth providers | PO | Tech Lead |

**Escalation** : si owner indisponible J0 + backup absent → story **OUT**
sprint, refinement backlog.

---

## 8. Liens

- Sprint-019 retro S-1 + A-8 : `../../project-management/sprints/sprint-019-epic-003-scoping/sprint-retro.md`
- Sprint-018 retro A-2 + A-3 (OPS holdover origin) : `../../project-management/sprints/sprint-018-epic-002-finition-dette/sprint-retro.md`
- Sprint-020 sub-epic D OPS-PREP-J0 : `../../project-management/sprints/sprint-020-epic-003-phase-2-acl/sprint-goal.md`
- Runbook db-bootstrap (autre runbook OPS) : `db-bootstrap.md`
- Local env Mac : `../04-development/local-environment-mac.md`

---

**Auteur** : Tech Lead
**Date** : 2026-05-09
**Version** : 1.0.0
**Sprint origine** : 020 sub-epic D livrable A-8
