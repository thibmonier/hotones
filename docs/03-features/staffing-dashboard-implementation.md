# 📊 Implémentation du Dashboard de Suivi du Staffing

## 🎯 Objectif

Mise en place d'un dashboard permettant de suivre le taux de staffing et le TACE (Taux d’Activité Congés Exclus) des contributeurs sur des périodes longues (-6 mois à +6 mois par rapport à la date actuelle).

## 🗃️ Accès

Le dashboard est accessible via :
- **URL** : `/staffing/dashboard` ou `http://localhost:8080/staffing/dashboard`
- **Menu** : Administration > Analyses & Rapports > 📈 Staffing & TACE
- **Rôle requis** : `ROLE_USER` (tous les utilisateurs connectés)

## ✅ Ce qui a été implémenté

### 1. Modèle de données (Modèle en étoile)

#### Entités créées

- **`DimProfile`** (`src/Entity/Analytics/DimProfile.php`)
  - Dimension pour les profils métier
  - Attribut `isProductive` pour identifier les profils productifs
  - Clé composite pour éviter les doublons

- **`FactStaffingMetrics`** (`src/Entity/Analytics/FactStaffingMetrics.php`)
  - Table de faits pour les métriques de staffing
  - Métriques : availableDays, workedDays, staffedDays, vacationDays, plannedDays
  - KPIs calculés : staffingRate, TACE
  - Relations avec DimTime, DimProfile, Contributor
  - Méthode `calculateMetrics()` pour calculer automatiquement les KPIs

### 2. Repository

**`StaffingMetricsRepository`** (`src/Repository/StaffingMetricsRepository.php`)
- `findByPeriod()` : Récupération des métriques sur une période avec filtres
- `getAggregatedMetricsByPeriod()` : Agrégation pour les graphiques
- `getMetricsByProfile()` : Métriques moyennes par profil
- `getMetricsByContributor()` : Métriques moyennes par contributeur
- `deleteForPeriod()` : Suppression pour recalcul
- `existsForPeriod()` : Vérification d'existence

### 3. Service de calcul

**`StaffingMetricsCalculationService`** (`src/Service/StaffingMetricsCalculationService.php`)
- Calcul et enregistrement des métriques pour une période donnée
- **Traite tous les contributeurs actifs**, même sans période d'emploi
- Génération des périodes selon la granularité (weekly/monthly/quarterly)
- Calcul des jours ouvrés (hors week-ends)
- Calcul des jours de congés approuvés
- Calcul des jours staffés (temps passé sur missions)
- Calcul des jours planifiés (depuis l'entité Planning)
- Création automatique des dimensions (DimTime, DimProfile)

#### Formules implémentées

**Taux de staffing** :
```
(Temps staffé / Temps disponible) × 100
```

**TACE (Taux d'Activité Congés Exclus)** :
```
(Jours produits / Jours travaillés hors congés) × 100
```

### 4. Commande CLI

**`CalculateStaffingMetricsCommand`** (`src/Command/CalculateStaffingMetricsCommand.php`)

Exemples d'utilisation :
```bash
# Calcule pour l'année courante
php bin/console app:calculate-staffing-metrics

# Calcule pour une année spécifique
php bin/console app:calculate-staffing-metrics 2024

# Calcule pour un mois spécifique
php bin/console app:calculate-staffing-metrics 2024-03

# Calcule les 12 derniers mois
php bin/console app:calculate-staffing-metrics --range=12

# Granularité hebdomadaire
php bin/console app:calculate-staffing-metrics --granularity=weekly

# Force le recalcul
php bin/console app:calculate-staffing-metrics 2024 --force-recalculate
```

### 5. Controller et Templates

**`StaffingDashboardController`** (`src/Controller/Staffing/StaffingDashboardController.php`)
- Route : `/staffing/dashboard`
- Filtres : contributeur, profil, granularité
- Préparation des données pour Chart.js

**Template** (`templates/staffing/dashboard.html.twig`)
- Filtres dynamiques (contributeur, granularité)
- 2 graphiques Chart.js :
  - Courbe du taux de staffing
  - Courbe du TACE
- Tableaux de métriques :
  - Par profil avec codes couleur (vert/orange/rouge selon le taux)
  - Top 10 contributeurs
- Section informations avec explications des KPIs

### 6. Tests

**`StaffingMetricsCalculationServiceTest`** (`tests/Unit/Service/StaffingMetricsCalculationServiceTest.php`)
- Tests unitaires pour les calculs de staffingRate et TACE
- Cas de test avec valeurs à zéro
- Tests avec différentes configurations de jours

### 7. Documentation

Mise à jour des documents suivants :
- `docs/status.md` : Ajout du dashboard dans les fonctionnalités implémentées
- `docs/roadmap-lots.md` : Marquage du backlog item comme terminé
- `docs/features.md` : Section complète sur le Dashboard de staffing
- `docs/entities.md` : Ajout de DimProfile et FactStaffingMetrics
- `docs/analytics.md` : Section Dashboard de Staffing et commandes CLI
- `docs/repositories.md` : Ajout de StaffingMetricsRepository

## 🔧 Prochaines étapes

### Étapes nécessaires avant utilisation

1. **Créer les migrations Doctrine** :
   ```bash
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```

2. **Calculer les métriques initiales** :
   ```bash
   # Pour les 12 derniers mois
   php bin/console app:calculate-staffing-metrics --range=12
   ```

3. **Configurer un cron pour le calcul automatique** :
   ```bash
   # Tous les jours à 6h du matin
   0 6 * * * cd /path/to/project && php bin/console app:calculate-staffing-metrics --range=12
   ```

### Améliorations possibles

#### Fonctionnalités additionnelles
- [ ] Ajouter un filtre par Business Unit (BU)
- [x] **Implémenté** : Intégration de l'entité Planning pour les jours planifiés futurs (voir [docs/planning-staffing-integration.md](./planning-staffing-integration.md))
- [ ] Ajouter un export PDF/Excel du dashboard
- [ ] Créer des alertes automatiques quand le taux de staffing est < 70%
- [ ] Ajouter un graphique de comparaison entre différents profils

#### Performance
- [ ] Ajouter un système de cache pour les métriques fréquemment consultées
- [ ] Créer des index sur les colonnes de recherche fréquentes
- [ ] Implémenter une vue matérialisée pour les agrégations complexes

#### Tests
- [ ] Ajouter des tests d'intégration pour le repository
- [ ] Créer des tests fonctionnels pour le controller
- [ ] Implémenter des tests E2E avec Panther pour le dashboard

#### UI/UX
- [ ] Ajouter des tooltips explicatifs sur les graphiques
- [ ] Implémenter un zoom sur les graphiques Chart.js
- [ ] Ajouter des filtres de date personnalisés (date picker)
- [ ] Créer une version mobile responsive

## 📚 Références

### Définitions

**Taux de staffing** :
Le taux de staffing est un indicateur de pilotage des ressources. Il représente le pourcentage du temps où une équipe ou un collaborateur est affecté à des missions (souvent facturables) par rapport à son temps total disponible sur une période.

Interprétations :
- **85%+** : Bonne utilisation, marge pour formation/projets internes
- **70-84%** : Utilisation correcte
- **<70%** : Sous-utilisation, besoin d'affectations supplémentaires

**TACE (Taux d'Activité Congés Exclus)** :
Indicateur qui mesure le nombre de jours produits par les collaborateurs par rapport au nombre de jours travaillés en entreprise, hors congés.

### URLs utiles

- Dashboard : `http://localhost/staffing/dashboard`
- Documentation Analytics : `docs/analytics.md`
- Architecture : `docs/architecture.md`

### Entités liées

- `Contributor` : Intervenants sur les projets
- `EmploymentPeriod` : Historique RH (périodes d'emploi)
- `Profile` : Profils métier (dev, lead, chef projet)
- `Timesheet` : Temps passés par les contributeurs
- `Vacation` : Congés approuvés
- `Planning` : Planification future (à implémenter)

## 🎉 Conclusion

Le Dashboard de suivi du staffing est maintenant fonctionnel et permet de visualiser l'évolution du taux de staffing et du TACE sur des périodes longues. Les filtres permettent d'analyser les données par profil ou par contributeur. Le système est conçu pour être performant grâce au modèle en étoile et permet un recalcul facile via la commande CLI.
