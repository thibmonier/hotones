# Auto-comments OPS-008 sur PRs en CI rouge

> Sprint-004 / OPS-008 — extension d'OPS-004

Ce workflow automatise un commentaire de PR quand un workflow critique
(CI / Quality / SonarQube Analysis) finit en `failure` sur la HEAD d'une PR.

Fichier : [`.github/workflows/pr-ci-comment.yml`](../../.github/workflows/pr-ci-comment.yml)

## Comportement

1. Déclencheur : événement `workflow_run` (type `completed`) sur les workflows
   listés.
2. Garde : ne s'active que si `conclusion == 'failure'` et que le run a été
   déclenché par un événement `pull_request`.
3. Résolution de la PR : `workflow_run.head_sha` → endpoint
   `/repos/{owner}/{repo}/commits/{sha}/pulls` (la PR ouverte associée).
4. Liste des jobs en échec : appel à
   `/repos/{owner}/{repo}/actions/runs/{run_id}/jobs`, filtre
   `conclusion == failure`, plafonné à 5 entrées affichées (le reste est
   compté dans une note de bas).
5. Idempotence : recherche d'un commentaire portant le marqueur HTML
   `<!-- ops-008-pr-ci-comment -->`. S'il existe → `PATCH` du commentaire
   (mise à jour). Sinon → création d'un nouveau commentaire.

## Pourquoi un marqueur HTML

GitHub ne fournit pas d'API de "comment search" sur les PRs. Le marqueur
HTML invisible permet :

- d'identifier le commentaire OPS-008 sans dépendre du contenu (qui peut
  varier d'un échec à l'autre) ;
- d'éviter la pollution : pas de chaîne de commentaires « CI rouge » qui
  s'allonge à chaque push ;
- de garder la trace de l'historique via l'API `comments/{id}` (qui
  expose `created_at`/`updated_at`).

## Limitations connues

- Le `workflow_run` event s'exécute toujours dans le contexte de `main`.
  Le code du workflow vit donc sur main, pas sur la branche de la PR.
- Si plusieurs PRs partagent le même HEAD SHA (rare : merge queue,
  cherry-pick), la première PR ouverte trouvée par l'API reçoit le
  commentaire. C'est conforme à l'attente : l'auteur·rice du commit voit
  son CI rouge.
- Si le workflow lui-même crashe avant d'exposer ses jobs (étape
  setup-php KO par exemple), l'API ne renvoie aucun job en échec.
  Le commentaire affiche alors un fallback générique.

## Tests manuels

Le workflow ne tourne qu'à la suite d'un autre workflow ; on ne peut pas
le déclencher via `workflow_dispatch` (l'event ne fournit pas de payload
`workflow_run`).

Pour valider :

1. Ouvrir une PR sur ce repo.
2. Casser volontairement la CI (ex: `git commit -m 'force CI red' --allow-empty`,
   ou modifier un test pour qu'il échoue).
3. Attendre la fin du run CI.
4. Vérifier la PR : un commentaire avec le marqueur OPS-008 doit apparaître.
5. Pousser un fix → la CI redevient verte → le commentaire **n'est pas
   supprimé** (signal historique pour la review). Si une nouvelle régression
   survient, le **même commentaire** est mis à jour avec les nouveaux jobs.

## Voir aussi

- [`ci-monitoring.md`](./ci-monitoring.md) — OPS-004 (auto-issue sur main rouge > 24h)
- [`stacked-prs.md`](./stacked-prs.md) — OPS-007 (procédure des PRs empilées)
