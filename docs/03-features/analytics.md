# 📊 Système Analytics & KPIs

## Modèle en Étoile (Star Schema)
- dim_time : Dimension temporelle (année, trimestre, mois)
- dim_project_type : Types de projets (forfait/régie, catégorie, statut)
- dim_contributor : Contributeurs avec rôles (chef projet, commercial, directeur)
- dim_profile : Profils métier (dev, lead, chef projet) avec flag productif
- fact_project_metrics : Métriques centralisées avec KPIs
- fact_staffing_metrics : Métriques de staffing et TACE

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
- Export Excel : `/analytics/export-excel` (5 feuilles : KPIs, Évolution mensuelle, Types, Catégories, Top contributeurs)
- Filtres : période, année/mois, type de projet, chef de projet, commercial
- Visualisations : cartes KPIs, graphiques d'évolution, répartition, table détaillée

### Architecture
- **DashboardReadService** : Lecture depuis le modèle en étoile (FactProjectMetrics)
  - Fallback automatique vers MetricsCalculationService (temps réel) si données manquantes
  - Optimisé pour performance avec données pré-agrégées
- **ExcelExportService** : Export dashboard complet avec PhpSpreadsheet
- **MetricsCalculationService** : Service temps réel (déprécié, utilisé comme fallback uniquement)

## Dashboard de Staffing
- URL : `/staffing/dashboard`
- Filtres : contributeur, profil, granularité (weekly/monthly/quarterly)
- Graphiques : Taux de staffing et TACE sur période -6 mois à aujourd'hui
- Tableaux : Métriques par profil et top 10 contributeurs
- KPIs staffing :
  - Taux de staffing : (Temps staffé / Temps disponible) × 100
  - TACE : (Jours produits / Jours travaillés hors congés) × 100
  - Jours disponibles, travaillés, staffés, congés

## Calculs Automatisés
- Recalcul temps réel (admin)
- Agrégations par période et dimensions
- Variations saisonnières
- Coûts réels basés sur CJM × temps passé

## Commandes CLI
```bash
# Métriques projets
php bin/console app:calculate-metrics
php bin/console app:calculate-metrics 2024
php bin/console app:calculate-metrics 2024-03
php bin/console app:calculate-metrics 2024 --force-recalculate
php bin/console app:calculate-metrics --granularity=quarterly

# Métriques staffing
php bin/console app:calculate-staffing-metrics
php bin/console app:calculate-staffing-metrics 2024
php bin/console app:calculate-staffing-metrics --range=12
php bin/console app:calculate-staffing-metrics --granularity=weekly
php bin/console app:calculate-staffing-metrics 2024 --force-recalculate
```

### Génération de données de test
```bash
php bin/console app:generate-test-data
php bin/console app:generate-test-data --year=2024
php bin/console app:generate-test-data --force
```

## Automatisation

### Symfony Scheduler
Configuré via `AnalyticsScheduleProvider` (`src/Scheduler/AnalyticsScheduleProvider.php`)

**Schedules configurés** :
- **Quotidien** : 6:00 AM - Recalcul des métriques mensuelles
- **Trimestriel** : 1er jour de Q1,Q2,Q3,Q4 à 7:00 AM
- **Annuel** : 1er janvier à 8:00 AM

**Lancement du worker** :
```bash
# Lister les schedules
php bin/console debug:scheduler

# Lancer le scheduler worker
php bin/console messenger:consume scheduler_default

# En production (systemd/supervisord)
php bin/console messenger:consume scheduler_default --time-limit=3600
```

**Message dispatch** : Les tâches sont envoyées via `RecalculateMetricsMessage` au message bus Symfony.

## Performance
- Index optimisés, données dénormalisées, agrégations pré-calculées, support gros volumes
