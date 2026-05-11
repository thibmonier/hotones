# OPS-PRE5-DECISION — ADR-0018 Render redeploy ou Out Backlog (pattern ADR-0017)

> **BC**: OPS  |  **Source**: Sprint-023 retro S-4 + L-2 (6ᵉ sprint consécutif holdover trigger réversibilité atteint)

- **Implements** : EPIC-002 (résiduel) — **Persona** : PO + user — **Estimate** : 2 pts — **MoSCoW** : Must — **Sprint** : 024

### Card
**As** PO + user
**I want** **trancher** le statut du holdover PRE-5 « Render prod redeploy + clear cache (image stale 2026-01-12) » via décision structurelle ADR formalisée
**So that** sortir du backlog implicite — 6 sprints consécutifs sans action = signal d'arrêt selon runbook OPS-PREP-J0 §3 + pattern ADR-0017.

### Acceptance Criteria

```
Given holdover PRE-5 trackable depuis sprint-018 (sprint-014 retro S-1 origine US-090)
When atelier OPS-PREP-J0 J-2 sprint-024 (2026-05-26)
Then décision PO matrix §3 runbook :
  - Option A : redeploy manuel Render prod immédiat (user execute) + ADR-0018 redeploy-done
  - Option B : ADR-0018 Out Backlog (pattern ADR-0017) avec triggers replan documentés
  - Option C : sprint dédié OPS replan sprint-025+ avec owner aligné + credentials
```

```
Given option A choisie
When user execute redeploy Render dashboard manual + clear build cache
Then smoke test post-deploy vert sur GH Action
And /health retourne JSON valide (pas raw PHP)
And ADR-0018 statut "accepted" avec date redeploy + commit smoke vert
```

```
Given option B choisie
When ADR-0018 rédigé pattern ADR-0017
Then triggers replan explicites (ex: "Render plan starter activé" OU "alternative déploiement Fly.io/Railway évaluée" OU "abandon /health route + autre healthcheck")
And holdover PRE-5 fermé définitivement
And smoke test red accepté comme bruit informatif (pas régression)
```

```
Given option C choisie
When sprint-025+ OPS replan planifié
Then prérequis listés : owner OPS aligné + credentials Render + 30 min budget atelier
And sprint OPS dédié programmé J-N
And holdover PRE-5 promu à story OPS officielle (US-XXX)
```

### Technical Notes

- **Contexte** : sprint-014 US-090 fix `/health` raw PHP source corrigé en code, mais image Docker prod stale 2026-01-12 — déploiement Render jamais re-build après le fix
- Smoke test post-deploy (US-092 EPIC-002) red chronique 30+ runs sans régression code
- Cost : redeploy Render starter plan ($7/mois) ou keep-alive UptimeRobot externe gratuit (cf EPIC-002 brief Q4)
- Pattern ADR-0017 référence : Sub-epic B OPS Out Backlog (sprint-022)
- **Réversibilité** : ADR-0018 Out Backlog doit lister triggers replan factuels et mesurables
- **Risk** : option B sans replan trigger = backlog dette indéfini. Préférer option A si user disponible 5 min.

### Tasks (à scoper sprint-024 Planning P2)

- [ ] T-PRE5-01 [DOC] Atelier OPS-PREP-J0 J-2 sprint-024 — matrix §3 décision PO (30 min)
- [ ] T-PRE5-02 [DOC] Rédaction ADR-0018 selon option choisie (1 h)
- [ ] T-PRE5-03 [OPS] Si option A : redeploy Render + smoke vérification (15 min user-tracked)
- [ ] T-PRE5-04 [OPS] Si option B : fermeture holdover PRE-5 + update runbook §3 (30 min)
- [ ] T-PRE5-05 [OPS] Si option C : création story OPS officielle US-XXX + dépendances credentials (1 h)

### Dépendances

- ✅ Sprint-014 US-090 fix `/health` code-level (résolu)
- ✅ EPIC-002 US-091 Sentry tracing (livré)
- ✅ EPIC-002 US-092 smoke test post-deploy (livré, red chronique)
- ✅ ADR-0017 Sub-epic B Out Backlog (pattern référence)
- 🔄 Runbook OPS-PREP-J0 §3 matrix décision

### Triggers / Signaux

- **Sprint-018** : 1er holdover identifié (US-090 fix livré, déploiement pas refait)
- **Sprint-019..022** : 2-5ᵉ sprints holdover user-tracked passifs
- **Sprint-023** : 6ᵉ sprint = trigger réversibilité atteint
- **Sprint-024** : décision structurelle obligatoire (cette story)

---
