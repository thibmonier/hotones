# Tâches — OPS-015

## Informations

- **Story Points** : 1
- **MoSCoW** : Must
- **Nature** : doc-only
- **Origine** : retro sprint-005 action #3
- **Total estimé** : 1h

## Résumé

`.github/PULL_REQUEST_TEMPLATE.md` doit forcer une checklist quand la PR introduit un workflow gated par `vars.X_ENABLED` ou nécessite un nouveau secret. Sprint-005 a vu PR #70 (TEST-006), PR #76 (OPS-009), PR #88 (contract-tests) mergées sans que les secrets soient provisionnés → workflows skipped en silence.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-OPS15-01 | [DOC] | Ajouter section "Workflow gated" au PR template | 0.5h | - | 🔲 |
| T-OPS15-02 | [DOC] | Cross-ref dans `CONTRIBUTING.md` (section "Pull Request") | 0.5h | T-OPS15-01 | 🔲 |

## Détail

### T-OPS15-01

Si `.github/PULL_REQUEST_TEMPLATE.md` n'existe pas, le créer. Section à ajouter :

```markdown
## Workflow gated (si applicable)

Cocher uniquement si la PR ajoute / modifie un workflow GitHub Actions
gated par `vars.X_ENABLED` ou nécessitant un nouveau `secrets.Y` :

- [ ] Variables / secrets provisionnés sur le repo **AVANT** merge
      (`Settings → Secrets and variables → Actions`)
- [ ] OU déficience documentée (workflow restera dormant tant que ops
      n'a pas provisionné — préciser pourquoi c'est acceptable)
- [ ] Test manuel `workflow_dispatch` planifié post-merge
```

### T-OPS15-02

Dans `CONTRIBUTING.md`, section "Pull Request" (autour ligne ~360), ajouter un point :

```markdown
8. ✅ **Si workflow gated** : checklist "Workflow gated" du PR template cochée.
```

## DoD

- [ ] `.github/PULL_REQUEST_TEMPLATE.md` contient la section.
- [ ] CONTRIBUTING.md référence la checklist.
- [ ] Vérification : ouvrir une PR test via GitHub web UI → la checklist apparaît dans la description.
