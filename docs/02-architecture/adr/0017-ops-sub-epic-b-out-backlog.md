# ADR-0017 — OPS Sub-Epic B Out Backlog (4ᵉ holdover signal arrêt)

| Champ | Valeur |
|---|---|
| Statut | ✅ Accepté |
| Date | 2026-05-10 |
| Sprint | sprint-022 atelier OPS-PREP-J0 J-2 décision AT-3 |
| Auteur | Tech Lead + PO |

---

## Contexte

Sub-Epic B EPIC-002 holdover « Slack webhook prod + Sentry alert rules +
SMOKE-PROD-EXTENDED config » a été reporté **4 sprints consécutifs** :
- Sprint-017 (kickoff) — non livré
- Sprint-018 — holdover
- Sprint-019 — holdover (sprint-019 retro L-1)
- Sprint-020 — holdover (sprint-020 retro L-1 — 4ᵉ holdover)

Sprint-021 a livré le runbook OPS-PREP-J0 (`docs/runbooks/sprint-ops-prep-j0.md`)
comme correctif structurel. Sprint-022 atelier OPS-PREP-J0 J-2 a appliqué
la matrice runbook §3 décision finale.

Critères runbook §3 :
- ✅ Tous credentials/access confirmés J0 → A go sprint-022
- 🟡 1-2 manquants, action immédiate possible → A go + risk flagged
- 🔴 Blocked owner / access non obtenu J0 → **B Out backlog**

---

## Décision

**AT-3 = B Out backlog** — Sub-epic B sortie EPIC-002 stragglers.

Replan futur sprint dédié OPS quand :
1. Owner unique fixé (Tech Lead OU PO backup activé)
2. 4 credentials/access confirmés simultanément :
   - Slack workspace incoming webhook URL
   - Sentry org admin token
   - GitHub repo Settings (push secrets `SMOKE_USER_*`)
   - DBA prod (création user smoke)

---

## Justification

**Signal d'arrêt** : 4ᵉ holdover consécutif = pattern défaillant non
résolvable par sprint normal. Continuer holdover bruite vélocité +
masque dette prioritaire.

**Runbook OPS-PREP-J0 §3** spécifie explicitement : « si owner pas
confirmé J0 + access review en cours → Out backlog (4ᵉ holdover = signal
arrêt — replan sprint dédié OPS quand owner aligné) ».

**Coût opportunité** : 1 pt sub-epic B sprint-022 réallouable à valeur
métier vs prolonger pattern holdover + dégrader vélocité.

---

## Conséquences

### Positives
- ✅ Métrique « 0 holdover OPS » sprint-022 atteinte sans compromis
- ✅ Vélocité sprint-022 nette (pas de pondération hors-scope)
- ✅ Pattern OPS-PREP-J0 runbook §3 appliqué strict — credibility
  process maintenue
- ✅ Décision documentée + traçable (vs holdover silent répété)
- ✅ Sprint-022 capacité libre 1 pt réallouable autre valeur métier

### Négatives
- ❌ Slack alerts prod toujours absentes — détection erreurs > 5 min
  reste manuelle (logs Render console + Sentry email)
- ❌ Sentry alert rules `#alerts-prod` toujours non configurées —
  quota Sentry > 80 % détectable seulement via dashboard Sentry
- ❌ SMOKE-PROD-EXTENDED reste skipped (`SMOKE_EXTENDED_ENABLED=false`
  effectif) — login + dashboard non vérifiés post-deploy

### Risques flagged
- **Detection erreurs prod ralentie** : email Sentry par défaut (pas
  Slack) + monitoring manuel logs Render
- **Quota Sentry** : sans alert rule, dépassement 5k errors/mois
  détecté seulement post-blocage (Sentry stop accepting events)

---

## Trigger réversibilité

Reconsidérer AT-3 = A (sprint dédié OPS planifié) si :
1. **Incident prod** non détecté dans les 30 min — forcera priorité OPS
2. **PO ou Tech Lead** disponible J0 confirme tous credentials
   simultanément (atelier OPS-PREP-J0 cycle suivant)
3. **Quota Sentry approche 80 %** — risk loss observability

---

## Action items

| ID | Action | Owner | Sprint |
|---|---|---|---|
| A-1 | Marquer US-094-OPS + SMOKE-OPS « Out backlog » dans `backlog/user-stories/OPS.md` | Tech Lead | sprint-022 (cette PR) |
| A-2 | Update sprint-022 sprint-goal sub-epic D = `OUT` | Tech Lead | sprint-022 (cette PR) |
| A-3 | Réallouer 1 pt capacité libre sprint-022 → autre story (WORKFLOW-YAML S-3 OR Mago lint OR coverage step 12) | PO | Sprint Planning P1 |
| A-4 | Tracker AT-3 = B dans sprint-022 review + retro | Tech Lead | sprint-022 retro |
| A-5 | Quand Sub-epic B replanifié futur sprint, créer atelier OPS-PREP J-2 dédié 4 credentials | PO + Tech Lead | sprint-N TBD |

---

## Alternatives écartées

### A — Sprint-022 owner Tech Lead disponible J0
**Écarté** : owner non confirmé J0 (atelier OPS-PREP-J0 sprint-022 J-2
constate access review en cours). Forcer livraison = risque holdover
sprint-023.

### C — Réallocation 1 pt sub-epic B sur 3 sprints (1 task / sprint)
**Écarté** : disperse risque holdover sur 3 sprints au lieu de 1. Pattern
runbook OPS-PREP-J0 §3 explicite : Out > dispersion.

---

## Liens

- Runbook OPS-PREP-J0 : `../../runbooks/sprint-ops-prep-j0.md`
- Sprint-019 retro L-1 : `../../../project-management/sprints/sprint-019-epic-003-scoping/sprint-retro.md`
- Sprint-020 retro L-1 : `../../../project-management/sprints/sprint-020-epic-003-phase-2-acl/sprint-retro.md`
- Sprint-021 retro K-1 (runbook OPS-PREP-J0 effectif 1ʳᵉ fois) : `../../../project-management/sprints/sprint-021-epic-003-phase-3/sprint-retro.md`
- ADR-0013 EPIC-003 scope (référence EPIC-002 closure context)
- ADR-0016 EPIC-003 Phase 3 décisions (Q6.3 + sprint-021 retro AT-3 héritage)
- Backlog OPS : `../../../project-management/backlog/user-stories/OPS.md`

---

**Date de dernière mise à jour :** 2026-05-10
**Version :** 1.0.0
