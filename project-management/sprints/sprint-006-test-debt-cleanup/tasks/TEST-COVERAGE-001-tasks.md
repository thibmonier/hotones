# Tâches — TEST-COVERAGE-001

## Informations

- **Story Points** : 3
- **MoSCoW** : Should
- **Nature** : test
- **Origine** : sprint-005 review candidate (cible coverage SonarCloud progressive)
- **Total estimé** : 4.5h

## Résumé

Pousser le coverage SonarCloud de l'état actuel (≈ 38-40%) vers 45%. Sprint-007 visera 50%, sprint-008 visera 60%. Approche : cibler les services/controllers à fort impact métier qui n'ont pas encore de tests unitaires.

## Cibles probables (à confirmer après audit SonarCloud)

- `App\Service\Forecasting\*` — services de prédiction utilisés par dashboard.
- `App\Service\Project\TacePerformanceCalculator` — calcul performance.
- `App\Service\Notification\*` — chain notification (en partie testée).
- `App\Controller\Analytics\*` — controllers GET avec logique métier.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-TC1-01 | [TEST] | Audit SonarCloud — top 10 fichiers à fort poids non couverts | 1h | - | 🔲 |
| T-TC1-02 | [TEST] | Lot 1 — 4 services Forecasting + 1 calculator | 1.5h | T-TC1-01 | 🔲 |
| T-TC1-03 | [TEST] | Lot 2 — 3 controllers Analytics ou complément Notification | 1.5h | T-TC1-01 | 🔲 |
| T-TC1-04 | [DOC] | Mettre à jour `docs/04-development/tests.md` avec target progressif | 0.5h | T-TC1-03 | 🔲 |

## Détail

### T-TC1-01 — Audit

Via SonarCloud UI ou `composer test-coverage` local :

```bash
composer test-coverage
# ouvre var/coverage/html/index.html → trier par "lines uncovered" desc
```

Sortir un tableau : fichier / lignes total / lignes uncovered / impact métier.

### T-TC1-02 / T-TC1-03 — Lots

Procéder par lot pour respecter <400 lignes diff. Chaque test :

- Mock minimal des dépendances.
- Couvrir le happy path + 1 cas d'erreur + 1 edge case.
- Assertions sur le résultat ET sur les side-effects (events dispatched, persistence calls).

### T-TC1-04 — Doc

Mettre à jour `docs/04-development/tests.md` :

- Cible coverage actuelle : 45%.
- Cible sprint-007 : 50%.
- Cible sprint-008 : 60%.
- Cible long-terme : 80%.

## DoD

- [ ] Coverage SonarCloud ≥ 45% (vs ~38-40% baseline).
- [ ] 7-8 fichiers nouvellement couverts.
- [ ] Tests rapides (< 1s chacun).
- [ ] PR ≤ 400 lignes par lot.
