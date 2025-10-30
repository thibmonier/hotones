# 📊 Système Analytics & KPIs

## Modèle en Étoile (Star Schema)
- dim_time : Dimension temporelle (année, trimestre, mois)
- dim_project_type : Types de projets (forfait/régie, catégorie, statut)
- dim_contributor : Contributeurs avec rôles (chef projet, commercial, directeur)
- fact_project_metrics : Métriques centralisées avec KPIs

## KPIs Suivis
### Financiers
- Chiffre d'affaires total, Coûts totaux, Marge brute, Pourcentage de marge
- CA potentiel, Valeur moyenne des devis

### Opérationnels
- Nombre de projets (total, actifs, terminés)
- Nombre de devis (en attente, gagnés, perdus)
- Nombre de contributeurs actifs
- Taux d'occupation, Jours vendus vs travaillés

## Dashboard Analytics
- URL : `/analytics/dashboard`
- Filtres : période, année/mois, type de projet, chef de projet, commercial
- Visualisations : cartes KPIs, graphiques d'évolution, répartition, table détaillée

## Calculs Automatisés
- Recalcul temps réel (admin)
- Agrégations par période et dimensions
- Variations saisonnières
- Coûts réels basés sur CJM × temps passé

## Commandes CLI
```bash
php bin/console app:calculate-metrics
php bin/console app:calculate-metrics 2024
php bin/console app:calculate-metrics 2024-03
php bin/console app:calculate-metrics 2024 --force-recalculate
php bin/console app:calculate-metrics --granularity=quarterly
```

### Génération de données de test
```bash
php bin/console app:generate-test-data
php bin/console app:generate-test-data --year=2024
php bin/console app:generate-test-data --force
```

## Automatisation
```bash
# Recalcul quotidien à 6h du matin
0 6 * * * cd /path/to/project && php bin/console app:calculate-metrics
```

## Performance
- Index optimisés, données dénormalisées, agrégations pré-calculées, support gros volumes
