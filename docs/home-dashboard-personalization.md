# Page d'accueil personnalisée par rôle

## Vue d'ensemble

La page d'accueil (`/`) de HotOnes affiche désormais un dashboard personnalisé selon le rôle de l'utilisateur connecté. Cette fonctionnalité permet à chaque utilisateur de voir immédiatement les informations les plus pertinentes pour son rôle.

## Branche de développement

- **Branche** : `feat/role-based-home-dashboard`
- **Statut** : En développement / À affiner
- **Objectif** : Travailler en parallèle du projet principal pour affiner les widgets

## Architecture

### Controller : HomeController

Le controller détermine le rôle principal de l'utilisateur et charge les données appropriées :

```php
$userRole = $this->getUserPrimaryRole();

$data = match ($userRole) {
    'admin'        => $this->getAdminData($em, $contributor),
    'compta'       => $this->getComptaData($em, $contributor),
    'manager'      => $this->getManagerData($em, $contributor),
    'chef_projet'  => $this->getChefProjetData($em, $contributor),
    'intervenant'  => $this->getIntervenantData($em, $contributor),
    default        => $this->getDefaultData($em, $contributor),
};
```

### Hiérarchie des rôles

Le rôle est déterminé par ordre de priorité (du plus élevé au plus bas) :

1. **ROLE_ADMIN** → `admin`
2. **ROLE_COMPTA** → `compta`
3. **ROLE_MANAGER** → `manager`
4. **ROLE_CHEF_PROJET** → `chef_projet`
5. **ROLE_INTERVENANT** → `intervenant`
6. **ROLE_USER** → `user` (par défaut)

### Templates modulaires

Les widgets sont organisés dans `templates/home/_widgets/` :

- `intervenant.html.twig` - Dashboard pour les contributeurs
- `chef_projet.html.twig` - Dashboard commercial pour les chefs de projet
- `manager.html.twig` - Dashboard management/RH
- `compta.html.twig` - À créer (Phase 3)
- `admin.html.twig` - À créer (Phase 3)

## Dashboards par rôle

### ROLE_INTERVENANT (Contributeur)

**KPIs affichés :**
- Mes heures cette semaine
- Mes projets actifs (avec tâches assignées)
- Tâches en cours

**Widgets :**
- **Mes projets et tâches** : Liste des projets avec tâches assignées, jusqu'à 3 tâches par projet
- **Mes temps récents** : 5 derniers temps saisis
- **Actions rapides** : 
  - Saisir mes temps
  - Voir mes tâches
  - Mes projets

**Données chargées :**
- `weeklyTimesheets` : Temps de la semaine en cours
- `weeklyHours` : Total heures cette semaine
- `recentTimesheets` : 5 derniers temps
- `projectsWithTasks` : Projets avec tâches assignées

### ROLE_CHEF_PROJET (Commercial, Chef de projet)

**KPIs affichés :**
- Devis en attente (à signer)
- CA signé ce mois
- Mes projets actifs

**Widgets :**
- **Devis en attente** : Tableau d'alerte avec devis à signer
- **Mes projets actifs** : Liste des projets en cours avec CA
- **Devis récents** : 5 derniers devis créés
- **Actions rapides** : 
  - Nouveau devis
  - Nouveau projet
  - Dashboard commercial
  - Analytics

**Données chargées :**
- `pendingOrders` : Devis avec statut `a_signer`
- `monthlySignedRevenue` : CA signé ce mois
- `myProjects` : Projets actifs (10 max)
- `recentOrders` : 5 devis récents

### ROLE_MANAGER (Manager)

**KPIs affichés :**
- Congés en attente de validation
- Contributeurs actifs
- Projets actifs
- Satisfaction (NPS moyen)

**Widgets :**
- **Demandes de congés en attente** : Tableau d'alerte avec demandes à valider
- **Projets en cours** : Liste avec chef de projet et CA
- **Métriques RH** : Satisfaction, turnover
- **Actions rapides** : 
  - Valider congés (avec badge de nombre)
  - Dashboard RH
  - Contributeurs
  - Analytics

**Données chargées :**
- `pendingVacations` : Demandes de congés en attente
- `activeContributors` : Nombre de contributeurs actifs
- `activeProjects` : Projets actifs (10 max)
- `hrMetrics` : Métriques RH (si service disponible)

### ROLE_COMPTA (Comptabilité)

**À implémenter (Phase 3)**

**KPIs prévus :**
- Factures en attente
- Trésorerie du mois
- Paiements à venir
- CA facturé ce mois

### ROLE_ADMIN (Delivery, Direction)

**À implémenter (Phase 3)**

**KPIs prévus :**
- Vue d'ensemble de tous les KPIs
- CA global (mois + année)
- Projets actifs
- Contributeurs actifs
- Marge globale
- Alertes multiples (congés, staffing, facturation)

## Services utilisés

### DashboardReadService
- Fournit les KPIs du dashboard Analytics
- Méthode : `getKPIs($startDate, $endDate, $filters)`

### HrMetricsService (optionnel)
- Fournit les métriques RH
- Méthode : `getAllMetrics($startDate, $endDate)`

### Repositories
- `OrderRepository` : Devis et CA commercial
- `ProjectRepository` : Projets
- `TimesheetRepository` : Temps saisis
- `ContributorRepository` : Contributeurs et tâches
- `VacationRepository` : Demandes de congés
- `InvoiceRepository` : Facturation (pour compta)

## Plan d'implémentation

### ✅ Phase 1 : Structure et ROLE_INTERVENANT (Complété)
- [x] Refactorer HomeController pour détecter le rôle
- [x] Créer la structure de templates modulaires
- [x] Implémenter widget ROLE_INTERVENANT

### ✅ Phase 2 : ROLE_CHEF_PROJET et ROLE_MANAGER (Complété)
- [x] Réutiliser les données du SalesDashboard pour ROLE_CHEF_PROJET
- [x] Réutiliser les données du HrDashboard pour ROLE_MANAGER
- [x] Widget chef_projet.html.twig
- [x] Widget manager.html.twig

### 🚧 Phase 3 : ROLE_COMPTA et ROLE_ADMIN (En attente)
- [ ] Implémenter widget ROLE_COMPTA
- [ ] Implémenter widget ROLE_ADMIN
- [ ] Récupérer les données de facturation/trésorerie

### 🚧 Phase 4 : Optimisation (En attente)
- [ ] Mise en cache des KPIs (Redis ou cache Symfony)
- [ ] Tests unitaires et fonctionnels
- [ ] Affinage des widgets selon retours utilisateurs
- [ ] Documentation complète

## Points d'attention

### Données manquantes potentielles

Certaines méthodes de repository sont appelées mais peuvent ne pas exister :

#### OrderRepository
- `getSignedRevenueForPeriod($startDate, $endDate)` : Calculer le CA signé sur une période
- `getRecentOrders($limit)` : Récupérer les devis récents

#### InvoiceRepository
- `getTotalRevenueForPeriod($startDate, $endDate)` : CA facturé sur une période

Ces méthodes doivent être créées si elles n'existent pas.

### Gestion des erreurs

Le HrMetricsService est injecté comme optionnel (`?HrMetricsService`) pour éviter les erreurs si le service n'est pas disponible :

```php
public function __construct(
    private DashboardReadService $dashboardReadService,
    private ?HrMetricsService $hrMetricsService = null
) {}
```

### Performance

Pour optimiser les performances, envisager :
- Mise en cache des KPIs (durée : 5-15 minutes)
- Lazy loading des widgets via AJAX
- Indexation des requêtes fréquentes

## Personnalisation future

### Par utilisateur
Permettre à chaque utilisateur de :
- Masquer/afficher certains widgets
- Réorganiser l'ordre des widgets
- Choisir la période des KPIs (semaine/mois/trimestre)

### Filtres
Ajouter des filtres contextuels :
- Filtrer les projets par statut
- Filtrer les temps par projet
- Période personnalisée pour les KPIs

## Tests

### Tests fonctionnels à créer

```bash
# Test du routing par rôle
bin/phpunit tests/Functional/Controller/HomeControllerTest.php

# Test des widgets
bin/phpunit tests/Functional/Widget/IntervenantWidgetTest.php
bin/phpunit tests/Functional/Widget/ChefProjetWidgetTest.php
bin/phpunit tests/Functional/Widget/ManagerWidgetTest.php
```

### Scénarios de test

1. **Intervenant sans projet** : Affichage du message "Aucun projet assigné"
2. **Chef de projet sans devis** : Affichage du message "Aucun devis"
3. **Manager sans congés en attente** : Pas d'alerte congés
4. **Utilisateur avec plusieurs rôles** : Affichage selon le rôle le plus élevé

## Notes de développement

### Affinage des widgets

Les widgets peuvent être affinés selon les retours :
- Ajuster le nombre d'éléments affichés (actuellement 5-10)
- Modifier les KPIs selon les besoins métier
- Ajouter des graphiques (Chart.js)
- Améliorer le responsive mobile

### Intégration continue

Cette branche doit être maintenue à jour avec la branche principale pour éviter les conflits :

```bash
git checkout feat/role-based-home-dashboard
git merge main
# Résoudre les conflits si nécessaire
```

## Contributeurs

- Développement initial : Warp AI Agent
- Maintenance : À définir
- Retours utilisateurs : À recueillir après déploiement
