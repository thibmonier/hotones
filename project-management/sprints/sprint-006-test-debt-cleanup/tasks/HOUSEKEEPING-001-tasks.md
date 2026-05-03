# Tâches — HOUSEKEEPING-001

## Informations

- **Story Points** : 2
- **MoSCoW** : Could
- **Nature** : doc-only
- **Origine** : retro sprint-005 action #4 (bundle Could doc-only)
- **Total estimé** : 3h

## Résumé

Story-parapluie qui regroupe les "petites" mises à jour doc qui auraient été des stories séparées de 1 pt chacune. Sprint-005 a montré que ce pattern génère du bruit board (2 stories → 2 PRs → 2 reviews). Sprint-006 expérimente le bundle.

## Sub-tasks (à compléter au fur et à mesure)

| ID | Type | Tâche | Estimation | Statut |
|---|---|---|---:|---|
| T-HK1-01 | [DOC] | CHANGELOG.md : entries pour sprint-005 (si pas encore fait) | 0.5h | 🔲 |
| T-HK1-02 | [DOC] | README.md : section "Statut sprint courant" mise à jour | 0.5h | 🔲 |
| T-HK1-03 | [DOC] | Vérifier liens cassés dans `docs/` (lychee ou simple grep) | 0.5h | 🔲 |
| T-HK1-04 | [DOC] | CONTRIBUTING.md : nettoyage typos / phrases obsolètes | 0.5h | 🔲 |
| T-HK1-05 | [DOC] | `project-management/sprints/README.md` : index sprints à jour | 0.5h | 🔲 |

Liste susceptible de grossir pendant le sprint si d'autres petits items doc émergent. Plafond : si la PR dépasse 200 lignes diff, splitter.

## DoD

- [ ] Toutes les sub-tasks faites OU explicitement déférées au sprint suivant.
- [ ] PR ≤ 300 lignes (limite tolérance bundle).
- [ ] Aucune sub-task ne génère sa propre PR.

## Note

Si une sub-task se révèle plus complexe que 1 pt (ex: ADR à écrire), elle sort du bundle et devient une story autonome.
