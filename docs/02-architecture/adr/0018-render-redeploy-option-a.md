# ADR-0018 — Render redeploy Option A (réversibilité ADR-0017)

| Champ | Valeur |
|---|---|
| Statut | ✅ Accepté |
| Date | 2026-05-11 |
| Sprint | sprint-024 atelier OPS-PREP-J0 J-16 anticipé |
| Auteur | Tech Lead + PO (décision user) |
| Référence pattern | ADR-0017 (out backlog) — trigger réversibilité satisfait |

---

## Contexte

Sub-epic B EPIC-002 `/health raw octet-stream` (image Render stale
2026-01-12) holdover **6 sprints consécutifs** :

- Sprint-019 → holdover
- Sprint-020 → holdover
- Sprint-021 → holdover
- Sprint-022 → **ADR-0017 OUT backlog** (4ᵉ holdover, signal arrêt
  pattern défaillant)
- Sprint-023 → holdover hérité (PRE-5 trigger atteint)
- Sprint-024 → **réversibilité ADR-0017** déclenchée (6ᵉ sprint)

ADR-0017 §Trigger réversibilité spécifiait 3 critères dont :

> « PO ou Tech Lead disponible J0 confirme tous credentials simultanément
> (atelier OPS-PREP-J0 cycle suivant) »

Atelier OPS-PREP-J0 sprint-024 (J-16 anticipé, 2026-05-11) constate :

- ✅ PO disponible — décision actée user 2026-05-11
- ✅ Tech Lead disponible — atelier conduit
- ✅ Render API key confirmée
- ✅ Render dashboard access confirmé

→ Critère 2 réversibilité satisfait. ADR-0017 = OUT backlog → ADR-0018 ouvert.

---

## Décision

**Option A — Redeploy Render manuel + smoke test post-deploy.**

Story `OPS-PRE5-DECISION` sprint-024, 1 pt :

1. **T-PRE5-03** : Tech Lead déclenche redeploy Render dashboard (manual
   trigger latest commit `main` HEAD)
2. **Smoke post-redeploy** : `GET /health` → JSON `{"status":"ok",...}` (vs
   raw octet-stream pré-redeploy)
3. **Validation** : Sentry events flux normal + Render logs deploy success
4. **Documentation** : sprint-024 review section OPS

### Alternatives rejetées

#### Option B — Fermeture holdover définitive (pattern ADR-0017 répétée)

**Écarté** : trigger réversibilité ADR-0017 atteint J-16. Réappliquer
Out backlog masque dette `/health` corrompue 6 sprints. Détection
erreurs prod déjà dégradée (ADR-0017 §Conséquences négatives).

#### Option C — Création story OPS dédiée sprint-025+

**Écarté** : owner disponible J0 sprint-024 (vs sp-022 access review en
cours). Différer encore = 7ᵉ sprint holdover = pattern défaillant.
Option A 1 pt sprint-024 = clôture immédiate.

---

## Justification

### Trigger réversibilité ADR-0017 §Trigger satisfait

| Critère ADR-0017 | État sprint-024 atelier |
|---|---|
| 1. Incident prod non détecté < 30 min | n/a (préventif vs réactif) |
| 2. **PO + Tech Lead disponibles J0 confirment credentials** | ✅ **SATISFAIT** |
| 3. Quota Sentry approche 80 % | n/a (free tier surveillé) |

### Coût opportunité

- 1 pt sprint-024 ferme (vs 1 pt sub-epic B sp-022 réalloué Mago lint)
- ROI : `/health` JSON valide → SMOKE-PROD post-deploy possible +
  monitoring Render `/health` endpoint fiable
- Débloque détection erreurs prod < 5 min (vs manuel logs Render
  console + Sentry email)

### Pattern OPS-PREP-J0 §3 strict

Runbook §3 : « ✅ Tous credentials confirmés J0 → A go sprint ».
Critères atelier sprint-024 conformes :

| Q runbook §2 | OPS-PRE5 |
|---|---|
| Q1 credentials | ✅ Render API key + dashboard |
| Q2 console tierce | ✅ Render dashboard owner Tech Lead |
| Q4 config infra | 🔴 OUI redeploy — plan rollback : Render dashboard rollback dernier deploy stable |
| **Décision** | **A go sprint** |

---

## Conséquences

### Positives

- ✅ `/health` endpoint JSON valide → SMOKE-PROD post-deploy ré-activable
- ✅ Détection erreurs prod < 5 min (monitoring Render `/health` fiable)
- ✅ 6 sprints holdover clôturé — pattern réversibilité validé
- ✅ Métrique OPS-PREP-J0 §6 « holdover récurrent même story » = 6 → 0
- ✅ Sub-epic B EPIC-002 partiellement réhabilité (out backlog → done)
- ✅ Capacité sprint-024 ferme 12 pts atteinte avec OPS-PRE5 intégré

### Négatives

- ❌ 1 pt sprint-024 ferme alloué OPS (vs valeur métier US-114+)
- ❌ Si redeploy échec : risk holdover 7ᵉ sprint (mitigation : rollback Render dashboard immédiat)
- ❌ Smoke test post-deploy dépendance Tech Lead window (mitigation : T-PRE5-03 estimé 0.25h)

### Risques flagged

- **Render image cache stale persiste** si redeploy ne force pas rebuild
  Docker → mitigation : `Clear build cache & deploy` dashboard option
- **Sentry quota** non couvert ADR-0018 (orthogonal ADR-0017 §Trigger 3)
  → reste à monitorer free tier

---

## Action items

| ID | Action | Owner | Sprint |
|---|---|---|---|
| A-1 | Atelier OPS-PREP-J0 J-16 conduit + matrix décision | Tech Lead | sprint-024 (cette PR) |
| A-2 | ADR-0018 rédigé + commit | Tech Lead | sprint-024 (cette PR) |
| A-3 | Sync sprint-status.yaml PRE-4 status → ✅ + OPS-PRE5 status → ready-for-dev | Tech Lead | sprint-024 (cette PR) |
| A-4 | Update sprint-goal.md PRE-4 statut + Sub-epic C décision | Tech Lead | sprint-024 (cette PR) |
| A-5 | Tech Lead exécute redeploy Render dashboard (manual trigger) | Tech Lead + user | sprint-024 J0 window |
| A-6 | Smoke test `GET /health` JSON valide post-redeploy | Tech Lead | post-A-5 |
| A-7 | Validation Sentry events normal + Render logs deploy success | Tech Lead | post-A-5 |
| A-8 | Si redeploy KO : rollback Render dashboard dernier deploy stable + escalation | Tech Lead | post-A-5 si KO |
| A-9 | Documentation review sprint-024 section OPS (succès / KO) | Tech Lead | sprint-024 review 2026-06-10 |

---

## Liens

- Runbook OPS-PREP-J0 : `../../runbooks/sprint-ops-prep-j0.md`
- ADR-0017 sub-epic B out backlog (réversibilité satisfaite) : `0017-ops-sub-epic-b-out-backlog.md`
- Atelier sprint-024 minutes : `../../../project-management/sprints/sprint-024-epic-003-phase-4-kickoff/atelier-ops-prep-j0.md`
- Sprint-024 goal : `../../../project-management/sprints/sprint-024-epic-003-phase-4-kickoff/sprint-goal.md`
- Sprint-023 retro A-4 PRE-4 héritage : `../../../project-management/sprints/sprint-023-epic-003-phase-3-finition/sprint-retro.md`

---

**Date de dernière mise à jour :** 2026-05-11
**Version :** 1.0.0
