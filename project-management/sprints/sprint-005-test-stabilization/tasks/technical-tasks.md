# Tâches techniques transverses — Sprint 005

Aucune tâche transverse identifiée pour ce sprint.

Sprint-005 est dominé par la dette technique (test stabilization + cleanup ops). Toutes les tâches sont rattachées à une story spécifique.

## Si nouvelle dépendance émerge

Format à suivre :

### T-TECH-XX : [Titre]
- **Type** : [OPS] / [BE] / [TEST]
- **Estimation** : Xh
- **Origine** : [contexte]

**Description** : ...

**Critères** : ...

## Catégories candidates pour sprint-006

(à reporter en planning sprint-006 si découvertes pendant sprint-005)

- Mise à jour des dépendances majeures non couvertes (composer + npm)
- Audit `roave/security-advisories` post-merge sprint-004
- Refonte de `phpunit.xml.dist` pour profiler/structurer les groups (`@group sandbox`, `@group skip-pre-push`, `@group contract`, `@group integration`)
