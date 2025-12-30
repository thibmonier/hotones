# 📚 Architecture des Repositories

L'application suit le pattern Repository pour séparer la logique métier des contrôleurs.

## Repositories personnalisés (extraits)

### EmploymentPeriodRepository
- findWithOptionalContributorFilter() : Filtrage par contributeur
- hasOverlappingPeriods() : Vérification des chevauchements
- findActivePeriods() : Périodes actives
- findCurrentPeriodForContributor() : Période actuelle d'un contributeur
- calculatePeriodCost() : Calcul du coût d'une période
- calculateWorkingDays() : Calcul des jours ouvrés
- getStatistics() : Statistiques des périodes

### ContributorRepository
- findActiveContributors() : Contributeurs actifs
- findWithProfiles() : Contributeurs avec profils
- searchByName() : Recherche par nom
- findWithHoursForPeriod() : Contributeurs avec heures sur période
- findProjectsWithAssignedTasks() : Projets où le contributeur a des tâches assignées
- findProjectsWithTasksForContributor() : Projets + tâches assignées par contributeur

### TimesheetRepository
- findByContributorAndDateRange() : Temps d'un contributeur sur une période
- findRecentByContributor() : Derniers temps saisis
- findForPeriodWithProject() : Temps d'une période (option projet)
- getTotalHoursForMonth() : Total mensuel d'heures
- getHoursGroupedByProjectForContributor() : Totaux groupés par projet pour un contributeur
- findExistingTimesheet()/findExistingTimesheetWithTask() : Détection de doublons
- getStatsPerContributor() : Statistiques par contributeur

### ProjectRepository
- findAllOrderedByName(), findActiveOrderedByName()
- findRecentProjects()
- countActiveProjects()
- getProjectsByStatus() : Agrégats par statut
- findActiveBetweenDates() : Projets actifs intersectant une période
- findBetweenDatesFiltered()/countBetweenDatesFiltered() : Sélection paginée + filtres
- getDistinctProjectTypes()/getDistinctStatuses() : Options de filtres

### StaffingMetricsRepository
- findByPeriod() : Métriques de staffing sur une période (avec filtres profil/contributeur)
- getAggregatedMetricsByPeriod() : Métriques agrégées pour graphiques (staffingRate, TACE)
- getMetricsByProfile() : Métriques moyennes par profil
- getMetricsByContributor() : Métriques moyennes par contributeur
- deleteForPeriod() : Suppression pour recalcul
- existsForPeriod() : Vérification existence métriques

## Avantages
- Séparation claire des responsabilités
- Réutilisabilité de la logique métier
- Testabilité améliorée (tests d'intégration fournis)
- Contrôleurs plus légers et focalisés sur HTTP
- Optimisation possible des requêtes dans les repositories
