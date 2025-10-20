# HotOnes - Gestion de rentabilité des projets d'agence web

## 🎯 Objectif du projet

**HotOnes** est une application de gestion de projets, du suivi de la rentabilité des projets pour une agence web digitale. Elle permet d'analyser la rentabilité en croisant :
- Ce qui est vendu aux clients (jours vendus, TJM de vente par tâches par profil).
- Les temps passés réels par les intervenants (les utilisateurs de l'application avec leur profil d'expertise et leur coût moyen associé).
- Leur coût journalier moyen (CJM) et tarif journalier moyen (TJM) défini pour chaque client
- l'application aura pour but de centraliser les projets au moyen de différents devis qui, additionnés, constituerons le projet dans sa globalité.
- L'application aura pour objectif de montrer les chiffres consolidés de l'ensemble de l'activité de l'agence pour chaque mois, visible sur une année civile ou glissante (date à date).
- L'application pourra être accessible pour chaque salarié de l'agence digitale qui pourra se créer un compte et qui pourra saisir le temps passé chaque jour sur l'ensemble des projets sur lesquels il est vendu.
- l'application sera en mesure de planifier les tâches à venir pour chaque contributeur de projet.
- l'application devra montrer l'évolution des KPIs dans le temps.

## 📋 Fonctionnalités principales

### 🔐 Authentification & Sécurité
- **Connexion sécurisée** avec email/mot de passe
- **2FA obligatoire** via Google Authenticator (TOTP)
- **Gestion des profils utilisateurs** (nom, prénom, adresse, téléphone, avatar)

### 👥 Gestion des utilisateurs & intervenants
- **User** : Compte utilisateur avec authentification 2FA
- **Contributor** : Intervenants sur les projets (peut être lié à un User)
- **EmploymentPeriod** : Historique des données RH par période
  - Salaire mensuel brut
  - CJM (Coût Journalier Moyen)
  - Temps de travail hebdomadaire (défaut : 35h pouvant aller à 39h hebdomadaires). Attention, certains contributeurs travaillent à temps partiel (90%, 80%)
  - Dates de début/fin de période
  - profil (pouvant être multiple. exemple : développeur, lead developer, chef de projet, product owner)

### 📊 Gestion des projets
- **Project** : Projets client
  - Nom du projet et client
  - Nombre de jours vendus (total des jours pour chaque tâche/profil)
  - TJM de vente (peut différer du TJM standard)
  - Dates de début/fin
  - achats sur le projet (fournitures ou renfort par des ressources externes à l'agence)
  - Chaque interface de projet devra montrer l'ensemble des devis constituant le projet, l'avancement de la consommation dans chaque devis et la rentabilité de chaque devis ainsi qu'une vision consolidée de ces chiffres pour le projet.
  - Une contingence (retenue d'argent sur le volume de marge générée) doit pouvoir être retenue lors de la vente de chaque devis. Cette retenue fait baisser le pourcentage de rentabilité du projet sans toucher au prix de vente et pourra être utilisée en cas de problème de dépassement de charges durant la vie du projet.
  - Chaque projet peut être un projet interne (et dont la saisie des temps ne rentre pas dans le calcul de marge de l'agence) ou externe.
  - Les projets affichent les données de temps en jours (conversion des temps saisis en heures en jours 1j=8h pour faciliter l'affichage) et dans la devise principale (ici euro).
  - Chaque projet doit avoir 2 tâches par défaut (AVV ou avant-vente et non-vendu), ces temps peuvent être saisis en tant que temps passés sur le projet et ne comptent pas dans les calculs de rentabilité du projet.
  - Chaque projet doit avoir un ensemble d'informations qui le décrivent : 
    - les technologies principales utilisées dans le projet (Symfony, Laravel, VueJS, NuxtJS, Wordpress, Drupal, Ionic, Tailwind, Varnish, CloudFlare, etc.)
    - L'offre à laquelle on doit le rattacher pour un suivi statistique (Brand, E-commerce, application métier, Maintenance, SEO/SEA, Hébergement, Licences).
  - Chaque projet doit être associé à plusieurs personnes de l'agence :
    - un Key Account Manager (ou commercial) en charge de la signature du projet, des aspects contractuels et du suivi commercial du client
    - un chef de projet en charge du pilotage du projet et de l'équipe associée au projet
    - un directeur de projet responsable des aspects financiers du projet et du bon pilotage du chef de projet
    - un commercial ayant identifié le projet en 1er
  - Un projet peut etre de 2 types :
    - soit du type "forfait" ou engagement de résultat qui défini un périmètre fixe, un échéancier de facturation et un budget fixe
    - soit du type "régie" permettant de garantir la présence d'une équipe pour produire, l'engagement est lui facturé au temps passé mensuellement, le budget et le périmètre du projet pouvant évoluer.

### Gestion des devis d'un projet
- **Order** : Devis d'un projet
  - Numéro unique du devis (basée sur un principe incrémental suivant la logique suivante : D[année][mois][numéro incrémental])
  - status du devis (A signer, Gagné, Signé, Perdu, Terminé, StandBy, Abandonné)
  - Un ensemble de section regroupant des lignes de devis et montrant la somme en euros de chaque ligne la constituant
  - chaque ligne de devis comprend :
    - le profil attendu, son TJM de vente, le nombre de jours vendus, le total en euros (nombre de jours * TJM)
    - si c'est un achat l'attachement à la valeur de l'achat (ex. : je vends 10j à 500 € auquel j'attache un achat de 4000€ de prestation externe, je montre 1000€ en bout de ligne)

### ⏱️ Suivi du temps
- **Timesheet** : Saisie des temps passés
  - Date et durée en heures (ex : 7.5h)
  - Lien Contributor ↔ Project
  - Notes optionnelles

### Planification
- **Planning** : positionnement de taches ou temps de travail du projet dans le futur : donne la projection d'utilisation du budget dans le futur (différent du temps passé qui lui est le temps réellement passé par le contributeur)
    - Date et durée en heure (peut être positionné sur plusieurs jours, semaines, mois)
    - Lien Contributeur ↔ Project
    - Notes optionnelles

### Congés
- **Vacation** : détermine des dates impossibles à utiliser pour un contributeur dans le planning
  - Date et durée
  - type (congés payés, repos compensateur, absence exceptionnelle, arrêt maladie)


## 🏢 Architecture technique

### Stack
- **Backend** : Symfony 7.3 + PHP 8.4
- **Base de données** : MariaDB 11.4
- **Frontend** : Twig + Bootstrap 5 (thème "Skote", les templates de références sont à la racine du répertoire "templates")
- **Assets** : Webpack Encore + Sass
- **Conteneurisation** : Docker Compose
- **Bundles Symfony** : [ajouter ici l'ensemble des bundles symfony utilisés]

### 📚 Architecture des Repositories

L'application suit le pattern Repository pour séparer la logique métier des contrôleurs :

#### Repositories personnalisés implémentés

**`EmploymentPeriodRepository`**
- `findWithOptionalContributorFilter()` : Filtrage par contributeur
- `hasOverlappingPeriods()` : Vérification des chevauchements
- `findActivePeriods()` : Périodes actives
- `findCurrentPeriodForContributor()` : Période actuelle d'un contributeur
- `calculatePeriodCost()` : Calcul du coût d'une période
- `calculateWorkingDays()` : Calcul des jours ouvrés
- `getStatistics()` : Statistiques des périodes

**`ContributorRepository`**
- `findActiveContributors()` : Contributeurs actifs
- `findWithProfiles()` : Contributeurs avec profils
- `searchByName()` : Recherche par nom
- `findWithHoursForPeriod()` : Contributeurs avec heures sur période

**Avantages de cette architecture :**
- ✅ Séparation claire des responsabilités
- ✅ Réutilisabilité de la logique métier
- ✅ Testabilité améliorée
- ✅ Contrôleurs plus légers et focalisés sur HTTP
- ✅ Optimisation possible des requêtes dans les repositories
### 📦 Entités principales

```php
// Authentification & Gestion utilisateurs
User (authentification)
├── email, password, roles
├── firstName, lastName, phone, address, avatar
└── totpSecret, totpEnabled (2FA)

EmploymentPeriod (historique RH)
├── contributor_id → Contributor
├── salary, cjm, tjm, weeklyHours, workTimePercentage
├── startDate, endDate, notes
└── profiles[] → Profile (ManyToMany)

Contributor (intervenants)
├── name, email, phone, cjm, tjm, active
├── user_id → User (optionnel)
├── profiles[] → Profile (dev, lead, chef projet...)
└── employmentPeriods[], timesheets[]

Profile (profils métier)
├── name, description, defaultTjm
└── contributors[] (ManyToMany)

// Projets & Devis
Project (projets client)
├── name, client, description
├── purchasesAmount, purchasesDescription
├── startDate, endDate, status, isInternal
├── projectType (forfait/régie)
├── keyAccountManager, projectManager, projectDirector, salesPerson → User
├── serviceCategory → ServiceCategory
├── technologies[] → Technology
└── orders[] → Order

Order (devis)
├── name, orderNumber, notes
├── totalAmount, contingenceAmount, contingenceReason
├── createdAt, validatedAt, status
├── project → Project
├── sections[] → OrderSection
└── tasks[] → OrderTask (ancienne structure)

OrderSection (sections de devis)
├── name, description, position
├── order → Order
└── lines[] → OrderLine

OrderLine (lignes de devis)
├── profile → Profile, quantity, unitPrice
├── totalPrice, purchaseAmount
└── section → OrderSection

// Temps & Planification
Timesheet (temps passés)
├── contributor_id → Contributor
├── project_id → Project
├── date, hours, notes
└── task → ProjectTask (optionnel)

Planning (planification future)
├── contributor → Contributor
├── project → Project
├── startDate, endDate, estimatedHours
└── notes, status

Vacation (congés)
├── contributor → Contributor
├── startDate, endDate, type
└── notes, status

// Configuration
Technology (technologies)
├── name, category, color, active
└── projects[] (ManyToMany)

ServiceCategory (catégories service)
├── name, description
└── projects[]

ProjectTask (tâches par défaut)
├── name, isDefault, excludeFromProfitability
└── project → Project

// Analytics (Modèle en étoile)
DimTime (dimension temporelle)
├── date, year, quarter, month
├── yearMonth, yearQuarter
└── monthName, quarterName

DimProjectType (dimension types projet)
├── projectType, serviceCategory, status, isInternal
└── compositeKey (unicité)

DimContributor (dimension contributeurs)
├── user → User, name, role, isActive
└── compositeKey (unicité)

FactProjectMetrics (table de faits)
├── dimTime, dimProjectType, dimProjectManager...
├── projectCount, activeProjectCount, orderCount...
├── totalRevenue, totalCosts, grossMargin, marginPercentage
├── totalSoldDays, totalWorkedDays, utilizationRate
└── calculatedAt, granularity
```
```

## 🚀 État d'avancement

### ✅ Implémenté
- [x] Setup Docker (PHP 8.4, Nginx, MariaDB)
- [x] Entities Doctrine + migrations
- [x] Authentification Symfony Security
- [x] 2FA Google Authenticator (scheb/2fa-bundle)
- [x] Templates Bootstrap 5 + Webpack Encore
- [x] QR Code generation (endroid/qr-code-bundle)
- [x] Command CLI création utilisateur
- [x] Pages : login, 2fa, tableau de bord, config 2FA
- [x] **Repositories personnalisés** :
  - [x] `ContributorRepository` avec méthodes métier
  - [x] `EmploymentPeriodRepository` avec logique de gestion des périodes
  - [x] `TimesheetRepository` avec calculs de temps
  - [x] `ProjectRepository` avec requêtes de rentabilité
  - [x] `ProjectTaskRepository` avec gestion des tâches
  - [x] `OrderRepository` avec calculs financiers
- [x] **CRUD complets** pour entités de configuration :
  - [x] Technologies (`/admin/technologies`)
  - [x] Catégories de service (`/admin/service-categories`)
  - [x] Profils métier (`/admin/job-profiles`)
- [x] **Refactoring contrôleurs** : logique métier déplacée vers repositories

### 🔄 En cours / À faire
- [x] CRUD complet des entités principales (Contributor, Project, Order, Timesheet, EmploymentPeriod)
- [x] Interface de saisie des temps (Timesheet)
- [x] Calculs de rentabilité par projet
- [x] Dashboard analytique avec métriques et graphiques
- [x] Système de suivi KPIs avec modèle en étoile
- [x] Gestion des périodes d'emploi (interface complète avec relation profils)
- [ ] Upload et gestion d'avatars
- [ ] API REST pour intégrations externes
- [ ] Rapports et exports (PDF/Excel)
- [ ] Notifications et alertes
- [x] Adapter le menu de navigation avec les entités de l'application
- [x] Mettre à jour project/new.html.twig avec les champs manquants
- [ ] Tests automatisés
- [ ] Filtres avancés dans le listing des projets

## 🔧 Installation & Usage

### Prérequis
- Docker & Docker Compose
- Node.js + npm (pour les assets)

### Démarrage
```bash
# Clone et démarrage
docker compose up -d --build

# Installation assets
npm install
npm run dev

# Création d'un utilisateur
docker compose exec app php bin/console app:user:create email@example.com password Prénom Nom
```

### URLs
- **Application** : http://localhost:8080
- **Base de données** : localhost:3307 (hotones/symfony/symfony)
- **Administration** :
  - Technologies : http://localhost:8080/admin/technologies
  - Catégories de service : http://localhost:8080/admin/service-categories
  - Profils métier : http://localhost:8080/admin/job-profiles
  - Périodes d'emploi : http://localhost:8080/employment-periods

### Compte de test
- **Email** : thibaut.monier@gmail.com
- **Mot de passe** : password
- **2FA** : À configurer via /me/2fa/enable

## 💡 Calculs de rentabilité

### Formules clés
```
Coût réel projet = Σ(heures_passées × CJM_intervenant / 8)
Chiffre d'affaires = jours_vendus × TJM_vente
Marge brute = CA - Coût_réel
Taux de marge = (Marge / CA) × 100
```

### Métriques à implémenter
- Rentabilité par projet
- Performance par intervenant
- Évolution temporelle des marges
- Comparaison vendu vs réalisé
- Alertes sur dépassements

## 🎨 Interface utilisateur

### Pages principales à créer
- Dashboard avec KPIs
- Liste des projets avec rentabilité
- Pour chaque projet une page de détail reprenant les principales informations de rentabilité du projet, la liste des temps saisis (dans une page à part), et la possibilité de modifier les informations du projet.
- Formulaire de saisie des temps
- Gestion des intervenants
- Rapports et analyses
- Administration (users, périodes)

### UX/UI
- Design responsive Bootstrap 5
- Thème "Skote" (admin dashboard)
- Formulaires avec validation
- Tableaux interactifs
- Graphiques (Chart.js ou similaire)

---

## 📊 Système Analytics & KPIs

### Modèle en Étoile (Star Schema)

Le système analytics utilise un modèle en étoile optimisé pour les requêtes OLAP :

#### Tables de Dimension
- **`dim_time`** : Dimension temporelle (année, trimestre, mois)
- **`dim_project_type`** : Types de projets (forfait/régie, catégorie, statut)
- **`dim_contributor`** : Contributeurs avec rôles (chef projet, commercial, directeur)

#### Table de Faits
- **`fact_project_metrics`** : Métriques centralisées avec tous les KPIs

### KPIs Suivis

#### Métriques Financières
- 💰 **Chiffre d'affaires total** : CA réalisé
- 💸 **Coûts totaux** : Coûts de production
- 📊 **Marge brute** : CA - Coûts
- 📈 **Pourcentage de marge** : (Marge / CA) × 100
- 🎯 **CA potentiel** : Montant des devis en attente
- 💵 **Valeur moyenne des devis** : CA moyen par devis

#### Métriques Opérationnelles
- 🏗️ **Nombre de projets** (total, actifs, terminés)
- 📋 **Nombre de devis** (en attente, gagnés, perdus)
- 👥 **Nombre de contributeurs** actifs
- ⏱️ **Taux d'occupation** : Temps travaillé / Temps vendu
- 📅 **Jours vendus vs travaillés**

### Dashboard Analytics

**URL** : `/analytics/dashboard`

#### Fonctionnalités
- **Filtres multidimensionnels** :
  - Période (mensuel, trimestriel, annuel)
  - Année et mois
  - Type de projet (forfait/régie)
  - Chef de projet
  - Commercial

- **Visualisations** :
  - 📊 Cartes KPIs avec codes couleur
  - 📈 Graphiques d'évolution (CA, marge, projets)
  - 🥧 Répartition par type de projet
  - 📋 Table détaillée avec métriques

#### Calculs Automatisés
- **Recalcul temps réel** via interface admin
- **Métriques agrégées** par période et dimensions
- **Variations saisonnières** prises en compte
- **Coûts réels** basés sur CJM × temps passé

### Commandes CLI

#### Calcul des métriques
```bash
# Calculer pour l'année courante
php bin/console app:calculate-metrics

# Calculer pour une année spécifique
php bin/console app:calculate-metrics 2024

# Calculer pour un mois spécifique
php bin/console app:calculate-metrics 2024-03

# Recalcul complet forcé
php bin/console app:calculate-metrics 2024 --force-recalculate

# Granularité spécifique
php bin/console app:calculate-metrics --granularity=quarterly
```

#### Génération de données de test
```bash
# Générer des données de test
php bin/console app:generate-test-data

# Pour une année spécifique
php bin/console app:generate-test-data --year=2024

# Forcer la régénération
php bin/console app:generate-test-data --force
```

### Automatisation

#### Tâche Cron recommandée
```bash
# Recalcul quotidien à 6h du matin
0 6 * * * cd /path/to/project && php bin/console app:calculate-metrics
```

### Performance

- **Index optimisés** pour requêtes OLAP
- **Données dénormalisées** pour rapidité
- **Agrégations pré-calculées**
- **Support gros volumes** grâce au modèle en étoile

## 📝 Notes pour la suite

### 🎨 Bonnes pratiques implémentées

#### Architecture et Code
- **Pattern Repository** : Logique métier séparée des contrôleurs
- **Injection de dépendances** : Utilisation native de Symfony DI
- **Entités Doctrine** : Relations bien définies avec annotations
- **Sécurité** : Contrôle d'accès par rôles (`ROLE_MANAGER`)
- **Validation** : Token CSRF sur suppressions et formulaires

#### Interface utilisateur
- **Feedback utilisateur** : Messages flash pour les opérations
- **Navigation intuitive** : Breadcrumbs et liens cohérents
- **Filtrage** : Possibilité de filtrer par contributeur
- **Responsivité** : Bootstrap 5 avec thème Skote
- **Accessibilité** : Statuts visuels avec couleurs et icônes

#### Gestion des données
- **Validation métier** : Vérification des chevauchements de périodes
- **Flexibilité** : Gestion du temps partiel et des différents profils
- **Tracçabilité** : Historique complet des périodes d'emploi
- **Calculs automatiques** : Coûts et durées calculés automatiquement

## Spécifications détaillées

### En tant qu'utilisateur standard (intervenant), je peux :
- Je dois pouvoir saisir des temps sur des projets même si aucune tâche ne leur a été assignée 
- Je dois pouvoir voir les projets sur lesquels je travaille facilement (et en cherchant pour pouvoir voir l'ensemble des projets).

### En tant que chef de projet (utilisateur avec pouvoir et intervenant dans le projet), je peux :
- voir les éléments d'un intervenant
- pouvoir créer de nouveaux projets, de nouveaux devis
- pouvoir modifier les projets
- pouvoir voir les temps saisis par l'ensemble des utilisateurs

### En tant que manager (administrateur frontoffice), je peux : 
- faire les mêmes actions que le chef de projet
- pouvoir voir les statistiques, KPI de l'ensemble de l'agence
- pouvoir modifier les informations financières des utilisateurs (TJM, CJM, salaire, horaires hebdomadaires)

### En tant que superadministrateur (administrateur global), je peux :
- tout faire, sans limitation de droits d'accès
