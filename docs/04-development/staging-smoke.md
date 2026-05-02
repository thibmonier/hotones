# Smoke test staging automatique (OPS-009)

> Sprint-004 / OPS-009 — automatiser le smoke test post-deploy

Le script `docker/scripts/smoke-test-staging.sh` (US-070, sprint-003)
était jusqu'ici lancé à la main après chaque deploy. OPS-009 le branche
sur l'événement « CI verte sur main », ce qui couvre le cas
nominal Render auto-deploy → smoke automatique.

Fichier : [`.github/workflows/staging-smoke-test.yml`](../../.github/workflows/staging-smoke-test.yml)

## Comportement

1. **Déclencheurs** :
   - `workflow_run` sur le workflow `CI` avec `conclusion == success` et
     `branch == main` et `event == push`.
   - `workflow_dispatch` (déclenchement manuel via l'UI GitHub Actions
     avec override de `base_url` et `wait_seconds`).
2. **Attente déploiement** : `sleep 90s` par défaut. Render free tier a
   un cold-start de ~30s mais le déploiement complet (build + restart +
   migration) peut atteindre 60-80s. La variable `wait_seconds` est
   override-able pour les déploiements rapides ou les replays.
3. **Smoke test** : appel direct à `docker/scripts/smoke-test-staging.sh`
   avec l'URL cible.
4. **Issue auto en cas d'échec** : label `staging-smoke,ops`, marqueur
   idempotent via `gh issue list --label staging-smoke`. Si une issue
   existe déjà, on **commente** au lieu d'en créer une nouvelle.
5. **Fermeture auto** : si un run ultérieur **passe**, toutes les issues
   ouvertes avec le label `staging-smoke` sont commentées + fermées
   (modèle OPS-004).

## Variables et secrets

| Type | Nom | Usage |
|---|---|---|
| Variable repo | `STAGING_BASE_URL` | Override par défaut de l'URL staging. Falls back to `https://hotones-staging.onrender.com`. |
| (aucun secret) | — | Le smoke test ne nécessite pas d'authentification (endpoints publics `/health`, `/login`). |

## Test manuel

```bash
# Via l'UI : Actions → Staging Smoke Test → Run workflow
# Ou via CLI :
gh workflow run staging-smoke-test.yml \
  -f base_url=https://hotones-staging.onrender.com \
  -f wait_seconds=30
```

Le `wait_seconds=30` est utile quand on sait que le service est déjà
chaud (smoke immédiat post-fix manuel par exemple).

## Limites connues

- Le `workflow_run` event s'exécute **toujours dans le contexte de
  main**. Le code du workflow lui-même (donc cette doc et le script de
  smoke) doit déjà avoir atterri sur main pour que les changements
  prennent effet.
- Render n'expose pas de webhook d'achèvement de deploy via Blueprint
  free tier. Le `sleep 90s` est un estimation grossière. Si Render
  durcit (> 90s régulièrement), augmenter le défaut.
- Le smoke test est **strictement HTTP** : pas de check métier (lecture
  BDD, écriture, login JWT). Pour ça, la story candidate
  TEST-E2E-STAGING-001 (sprint-005) doit aller plus loin.

## Voir aussi

- [`ci-monitoring.md`](./ci-monitoring.md) — OPS-004 (incident main rouge >24h)
- [`ci-pr-comments.md`](./ci-pr-comments.md) — OPS-008 (commentaire auto sur PR rouge)
- [`../05-deployment/staging.md`](../05-deployment/staging.md) — Render Blueprint config
