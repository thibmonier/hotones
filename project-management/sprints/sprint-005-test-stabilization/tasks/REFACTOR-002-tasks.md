# Tâches — REFACTOR-002

## Informations

- **Story Points** : 1
- **MoSCoW** : Could
- **Origine** : retro sprint-004 (scope ambigu OPS-010)
- **Total estimé** : 2h

## Résumé

Sprint-004 PR #69 a réinjecté "OPS-010 review cascade" comme story de 2 pts pour réutiliser les pts libérés par DEPS-001/2/3. Lors du status check sprint-004, l'utilisateur a indiqué "rien n'a été fait" et que le scope était flou.

Cette story décide : soit clarifier OPS-010 (et le mettre dans sprint-006), soit le supprimer du backlog.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-RF2-01 | [DOC] | Décision documentée + cleanup backlog | 2h | - | 🔲 |

## Détail

### T-RF2-01

Trois options :

#### Option A — Supprimer

Si "review cascade" n'a pas de définition claire et n'est pas critique :
- Retirer toute référence à OPS-010 dans `project-management/sprints/sprint-004-quality-foundation/sprint-goal.md`
- Mention dans CHANGELOG : "OPS-010 retiré sans implémentation"

#### Option B — Redéfinir

Si l'idée mérite d'être préservée (ex: workflow GitHub Actions pour déclencher une chaîne de reviews) :
- Créer `project-management/backlog/user-stories/OPS-010-review-cascade.md` avec critères Gherkin
- Ajouter au backlog sprint-006

#### Option C — Fusionner

Si la fonctionnalité est partiellement couverte par OPS-008 (auto-comment CI rouge sur PR) :
- Étendre la doc OPS-008 pour couvrir le cas
- Marquer OPS-010 "absorbed by OPS-008"

**Recommandation par défaut** : Option A (supprimer). Sans définition claire, autant ne pas accumuler de dette de scope. Si le besoin réémerge, créer une nouvelle story.

## DoD

- [ ] Décision documentée dans `project-management/sprints/sprint-004-quality-foundation/sprint-goal.md` (note de bas de page ou section "OPS-010 status")
- [ ] Si Option B : story créée dans le backlog avec Gherkin + estimation
- [ ] Si Option A ou C : référence retirée des artefacts sprint-004
