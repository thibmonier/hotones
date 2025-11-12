# 🗓️ Roadmap - Lots de développement

Ce document liste les lots de fonctionnalités à mettre en œuvre par ordre de priorité.

## Liens
- État d'avancement: [docs/status.md](./status.md)

---

## Légende
- ✅ Terminé
- 🔄 En cours
- 🔲 À faire

## Définition de Done (DoD)
- Fonctionnalités implémentées et validées métier
- Tests unitaires, fonctionnels et E2E au vert en CI
- Documentation mise à jour
- Revue de code effectuée

---

## 🎯 Lot 1 : CRUD Entités Principales (Priorité Haute)

### Objectif
Compléter les interfaces de gestion des entités principales pour permettre une utilisation opérationnelle de l'application.

### Fonctionnalités

#### 1.1 Gestion des Contributeurs
- ✅ Entity `Contributor` et repository existants
- ✅ CRUD complet (liste, création, édition, suppression)
- ✅ Recherche et filtres (nom, profil actuel, statut actif/inactif)
- ✅ Affichage des périodes d'emploi associées
- 🔲 Upload et gestion d'avatar
- ✅ Vue détaillée avec historique (emplois, projets, temps saisis)

#### 1.2 Gestion des Périodes d'Emploi
- ✅ Entity `EmploymentPeriod` existante
- ✅ Interface complète de gestion des périodes
- ✅ Association avec les profils métier (`JobProfile`)
- ✅ Validation des chevauchements de dates
- ✅ Calcul automatique CJM à partir du salaire et temps de travail
- ✅ Controller avec FormType existant

#### 1.3 Gestion des Projets
- ✅ Entity `Project` et CRUD de base existants
- ✅ Compléter le formulaire avec tous les champs métier
- ✅ Alimenter les listes déroulantes pour les rôles :
  - KAM (Key Account Manager)
  - Chef de projet
  - Directeur de projet
  - Commercial
- ✅ Formulaire ProjectType complet avec EntityType
- ✅ Templates new/edit modernisés avec form_widget
- ✅ Gestion des tâches du projet (ProjectTaskController + CRUD complet)
- ✅ Vue consolidée multi-devis (onglet Devis avec tableau agrégé)
- ✅ Onglets : Aperçu, Devis, Tâches, Planning, Temps, Rentabilité, Fiche technique
- ✅ Génération automatique des tâches depuis les lignes budgétaires
- ✅ Onglet "Fiche d’identité technique" (technologies avec versions, liens dépôts/env., accès BDD/SSH/FTP)
- ✅ Relation OrderLine → ProjectTask → ProjectSubTask
- ✅ Calculs agrégés cohérents (temps révisés et passés)
- 🔲 Filtres avancés dans le listing (statut, type, technologies, dates, contributeurs)

#### 1.4 Gestion des Devis
- ✅ Entity `Order` existante
- ✅ CRUD complet des devis
- ✅ Formulaire OrderType pour informations principales
- ✅ Templates new/edit modernisés avec form_widget
- ✅ Gestion des sections et lignes (via routes existantes)
- ✅ Calcul automatique des totaux
- ✅ Validation échéancier forfait (100%)
- ✅ Mise à jour rapide du statut (depuis liste et fiche)
- 🔲 Prévisualisation PDF du devis

### Tests
- 🔲 Tests unitaires pour les calculs (CJM, totaux devis)
- 🔲 Tests fonctionnels pour les CRUD
- 🔲 Tests E2E pour les parcours principaux

### Estimation
**8-10 jours** de développement

---

## 🕐 Lot 2 : Saisie des Temps (Priorité Haute)

### Objectif
Interface complète de saisie et gestion des temps (timesheets) avec liaison aux tâches de projet.

### Fonctionnalités

#### 2.1 Interface de saisie
- ✅ Entity `Timesheet` existante avec relation optionnelle vers `ProjectTask`
- 🔲 Grille de saisie hebdomadaire (auto-save)
- 🔲 Compteur de temps (start/stop, un seul actif, imputation min 0,125j)
- 🔲 Sélection projet → tâche (cascade)
- 🔲 Validation : max 24h/jour
- 🔲 Saisie en heures ou jours (conversion auto 1j = 8h)
- 🔲 Commentaires optionnels
- 🔲 Statut : brouillon / validé / approuvé

#### 2.2 Vue calendrier
- 🔲 Calendrier mensuel avec saisie rapide
- 🔲 Copie de semaine / duplication
- 🔲 Import/Export CSV

#### 2.3 Validation hiérarchique
- 🔲 Workflow approbation (chef de projet → manager)
- 🔲 Commentaires de validation
- 🔲 Historique des modifications

#### 2.4 Rapports
- 🔲 Récapitulatif mensuel par contributeur
- 🔲 Récapitulatif par projet
- 🔲 Export Excel/PDF

### Tests
- 🔲 Tests unitaires validation heures
- 🔲 Tests fonctionnels saisie/modification
- 🔲 Tests E2E parcours complet saisie → validation

### Estimation
**5-7 jours** de développement

---

## 📊 Lot 3 : Dashboard Analytique (Priorité Haute) 🔄 En cours

### Objectif
Interface de visualisation des KPIs et métriques avec filtres dynamiques.

### Fonctionnalités

#### 3.1 Vues du dashboard
- 🔲 Page principale `/analytics/dashboard`
- 🔲 Cartes KPIs principales :
  - CA total / Marge / Taux de marge
  - Projets actifs / terminés
  - Devis en attente / gagnés
  - Taux d'occupation
- 🔲 Graphiques d'évolution temporelle (Chart.js)
- 🔲 Répartition par type de projet (camembert)
- 🔲 Top contributeurs (Top 5)

#### 3.2 Filtres
- 🔲 Période (aujourd'hui, semaine, mois, trimestre, année)
- 🔲 Type de projet (forfait/régie, interne/client)
- 🔲 Chef de projet
- 🔲 Commercial
- 🔲 Technologies

#### 3.3 Exports
- 🔲 Export PDF du dashboard
- 🔲 Export Excel des données

#### 3.4 Intégration Worker
- 🔲 Modèle en étoile créé (dimensions + faits)
- 🔲 Message `RecalculateMetricsMessage` créé
- 🔲 Index unique sur `FactProjectMetrics`
- 🔲 Documentation worker
- 🔲 Service `MetricsCalculationService` (calcul des KPIs)
- 🔲 Handler `RecalculateMetricsMessageHandler` (traitement asynchrone)
- 🔲 Commande CLI `app:calculate-metrics`
- 🔲 Bouton "Recalculer" dans l'interface admin
- 🔲 Cron automatique (quotidien) via Symfony Scheduler (providers DB + métriques, admin `/admin/scheduler`)

### Tests
- 🔲 Tests unitaires calculs métriques
- 🔲 Tests fonctionnels dashboard
- 🔲 Tests performance agrégations

### Estimation
**7-10 jours** de développement

---

## 👤 Lot 4 : Gestion de Compte Utilisateur (Priorité Moyenne)

### Objectif
Permettre à chaque utilisateur de gérer ses informations personnelles et paramètres de sécurité.

### Fonctionnalités

#### 4.1 Page "Mon compte"
- 🔲 Route `/me` accessible depuis header
- 🔲 Onglets : Informations / Sécurité / Carrière
- 🔲 Informations personnelles :
  - Nom, prénom, email
  - Téléphones (pro optionnel, perso)
  - Adresse personnelle
- 🔲 Upload avatar
- 🔲 Affichage avatar dans header (remplace avatar par défaut)
- 🔲 Affichage prénom dans header

#### 4.2 Sécurité
- 🔲 Changement de mot de passe
- 🔲 Gestion 2FA (activer/désactiver, régénérer QR code)
- 🔲 Sessions actives (liste et révocation)

#### 4.3 Carrière (lecture seule)
- 🔲 Historique des périodes d'emploi
- 🔲 Profils occupés
- 🔲 Statistiques personnelles (projets, heures)

#### 4.4 Menu header
- 🔲 Retirer : "My wallet", "Settings", "Lock screen"
- 🔲 Renommer "Profile" → "Mon compte"
- 🔲 Renommer "Logout" → "Déconnexion"
- 🔲 Retirer section "mon compte" du menu vertical

### Tests
- 🔲 Tests fonctionnels modification profil
- 🔲 Tests sécurité changement mot de passe

### Estimation
**3-4 jours** de développement

---

## 🎨 Lot 5 : Améliorations UX/UI (Priorité Moyenne)

### Objectif
Améliorer l'expérience utilisateur et adapter l'interface aux besoins métier.

### Fonctionnalités

#### 5.1 Navigation
- 🔲 Menu latéral adapté aux entités de l'application
- 🔲 Fil d'ariane sur toutes les pages
- 🔲 Recherche globale (projets, contributeurs, devis)

#### 5.2 Tableaux de données
- 🔲 Pagination côté serveur
- 🔲 Tri multi-colonnes
- 🔲 Filtres avancés persistants (session)
- 🔲 Actions en masse (sélection multiple)
- 🔲 Export CSV/Excel

#### 5.3 Formulaires
- 🔲 Validation temps réel (AJAX)
- 🔲 Champs dépendants (ex: projet → tâches)
- 🔲 Indicateurs de progression
- 🔲 Sauvegarde automatique (brouillon)

#### 5.4 Notifications
- 🔲 Système de notifications in-app
- 🔲 Notifications email (configurable)
- 🔲 Centre de notifications (header)
- 🔲 Types : info, succès, warning, erreur

### Tests
- 🔲 Tests E2E navigation
- 🔲 Tests accessibilité (WCAG)

### Estimation
**5-6 jours** de développement

---

## 🔔 Lot 6 : Notifications & Alertes (Priorité Basse)

### Objectif
Système de notifications pour les événements importants.

### Fonctionnalités

#### 6.0 Infrastructure
- ✅ Entités et schéma en place (`Notification`, `NotificationPreference`, `NotificationSetting`) + migrations
- ✅ Page d’index des notifications (lecture)
- 🔲 Déclencheurs d’événements (création, budget, échéances, validations) et routage des notifications

#### 6.1 Types d'événements
- 🔲 Nouveau devis à signer
- 🔲 Devis gagné/perdu
- 🔲 Projet proche de son budget
- 🔲 Temps en attente de validation
- ✅ Rappel hebdomadaire de saisie des temps (vendredi 12h, tolérance configurable, email si autorisé)
- 🔲 Échéance de paiement proche
- 🔲 Seuil d'alerte KPI dépassé

#### 6.2 Canaux
- 🔲 Notifications in-app (base de données)
- 🔲 Emails (Symfony Mailer)
- 🔲 Optionnel : Webhook Slack/Discord

#### 6.3 Configuration
- 🔲 Préférences utilisateur (quels événements, quels canaux)
- 🔲 Configuration globale admin (seuils d'alerte)

### Tests
- 🔲 Tests unitaires déclencheurs
- 🔲 Tests fonctionnels envoi notifications

### Estimation
**4-5 jours** de développement

---

## 📄 Lot 7 : Rapports & Exports (Priorité Basse)

### Objectif
Génération de rapports et exports pour la direction et les clients.

### Fonctionnalités

#### 7.1 Rapports standards
- 🔲 Rapport d'activité mensuel (par projet)
- 🔲 Rapport financier (CA, marges, coûts)
- 🔲 Rapport contributeur (temps, projets, performance)
- 🔲 Rapport commercial (pipeline, taux de conversion)
- 🔲 Rapport devis actifs entre 2 dates (client, projet, CA, commercial, achats sur projet, rentabilité, statut)

#### 7.2 Formats
- 🔲 PDF (DomPDF ou Snappy)
- 🔲 Excel (PhpSpreadsheet)
- 🔲 CSV

#### 7.3 Personnalisation
- 🔲 Templates éditables
- 🔲 Logo et charte graphique
- 🔲 Sélection des sections à inclure

#### 7.4 Automatisation
- 🔲 Génération planifiée (cron)
- 🔲 Envoi automatique par email

### Tests
- 🔲 Tests génération PDF/Excel
- 🔲 Tests contenu rapports

### Estimation
**6-7 jours** de développement

---

## 🔌 Lot 8 : API REST (Priorité Basse)

### Objectif
Exposer une API REST pour intégrations externes et applications tierces en utilisant apiplatform.

### Fonctionnalités

#### 8.1 Endpoints
- 🔲 `/api/projects` (CRUD projets)
- 🔲 `/api/timesheets` (saisie/consultation temps)
- 🔲 `/api/contributors` (liste contributeurs)
- 🔲 `/api/orders` (devis)
- 🔲 `/api/metrics` (KPIs lecture seule)
- 🔲 `/api/users` (CRUD utilisateurs)
- 🔲 `/api/running-timer` (utilisation du système de timer en dehors de l'interface)

#### 8.2 Sécurité
- 🔲 Authentification JWT (lexik/jwt-authentication-bundle)
- 🔲 Rate limiting
- 🔲 Scopes/permissions par endpoint

#### 8.3 Documentation
- 🔲 OpenAPI/Swagger (apiplatform)
- 🔲 Exemples d'utilisation
- 🔲 SDKs (JavaScript, Python)

### Tests
- 🔲 Tests API (PHPUnit + API Platform Test Client)
- 🔲 Tests sécurité (JWT, permissions)

### Estimation
**8-10 jours** de développement

---

## 📊 Récapitulatif des priorités

| Lot | Priorité | Estimation | Dépendances |
|-----|----------|-----------|-------------|
| Lot 1 : CRUD Entités | 🔴 Haute | 8-10j | - |
| Lot 2 : Saisie Temps | 🔴 Haute | 5-7j | Lot 1 (projets/tâches) |
| Lot 3 : Dashboard Analytics | 🔴 Haute | 7-10j | Lot 1 + Lot 2 |
| Lot 4 : Gestion Compte | 🟡 Moyenne | 3-4j | - |
| Lot 5 : UX/UI | 🟡 Moyenne | 5-6j | - |
| Lot 6 : Notifications | 🟢 Basse | 4-5j | Lot 1 |
| Lot 7 : Rapports | 🟢 Basse | 6-7j | Lot 3 |
| Lot 8 : API REST | 🟢 Basse | 8-10j | Lots 1-3 |

**Total estimé : 46-59 jours** de développement

---

## 🎯 Sprint Planning suggéré

### Sprint 1 (2 semaines) : Fondations
- Lot 1.1 : Contributeurs (CRUD)
- Lot 1.2 : Périodes d'emploi
- Lot 4 : Gestion compte utilisateur

### Sprint 2 (2 semaines) : Projets & Devis
- Lot 1.3 : Projets (complet)
- Lot 1.4 : Devis (complet)

### Sprint 3 (2 semaines) : Temps & Analytics
- Lot 2 : Saisie des temps
- Lot 3 : Dashboard analytics (partie 1)

### Sprint 4 (2 semaines) : Analytics & UX
- Lot 3 : Dashboard analytics (partie 2)
- Lot 5 : Améliorations UX/UI

### Sprint 5+ (selon besoins) : Fonctionnalités avancées
- Lot 6 : Notifications
- Lot 7 : Rapports
- Lot 8 : API REST

## Backlog

### Fiche d’identité technique — Projets
Cet onglet permettra de donner les informations techniques principales du projet
- Un tableau donnant le détail des technologies utilisées, avec les versions 
- Les liens vers le ou les gestionnaires de sources (gitlab, GitHub, etc.)
- Les liens vers les différents environnements de tests ou production
- Les informations d’accès (BDD, SSH, FTP, etc.)
- Dépendances : Lot 1.3 (Projets), Lot 5 (UX/UI)

### KPIs — récapitulatif des achats par période
- Ajouter un récapitulatif des achats par période (tout type)
- Dépendances : Lot 3 (modèle de métriques/worker), entités Achats/Orders

### Tableau récapitulatif des dépenses
- Dépendances : Lot 3 (agrégations), Lot 1.3 (Projets/Orders)

### Symfony Scheduler — page d’administration
- S’inspirer de la documentation de Symfony Scheduler et utiliser des expressions crontab dans le formulaire (avec exemples/aide)
- Dépendances : Base Admin, premiers jobs (ex: recalcul KPIs du Lot 3)

### Business Units (BU)
- Les business units sont aussi appelées BU
- Les business units permettent de cloisonner plusieurs équipes au sein de la même société
- Les business Units sont manager par un des contributeurs
- Les contibuteurs sont attachés  à une Business Unit uniquement
- Les dashboards doivent pouvoir être visibles par les membres de la Business Unit avec les chiffres de la business unit uniquement.
- les devis sont attachés à une BU
- Tous les contributeurs peuvent intervenir sur les projets, peu importe leur business unit
- les clients peuvent être attachés à une business unit préférentielle
- Chaque BU a des objectifs de CA signé, de Marge générée et de contributeurs à l'année
- les objectifs doivent pouvoir être visibles dans les KPIs 
- Dépendances : Lots 1 (Contributors/Projects/Orders), 3 (KPIs), 4 (Gestion compte)

### Workflow de recrutement
- lors d'un recrutement, un candidat doit etre défini par ses coordonnées, son CV, ses technologies de préférences avec son niveau, ses prétentions salariales (en k€ par an), son type de contrat visé (CDI, CDD, Alternance, stage), la BU identifiée pour le poste.
- le candidat doit etre attaché à un poste ou profil type.
- Lors de l'embauche, le candidat devient un contributeur et on doit pouvoir revoir les informations saisies lors de son process de recrutement.
- lors d'un process de recrutement, on doit pouvoir définir qui le contacte ou le rencontre, et qui doit encore le rencontrer lors des prochaines étapes (la derniere étant l'embauche ou le refus d'embauche)
- Dépendances : Lot 1.1 (Contributors), 1.2 (EmploymentPeriod), 4 (Mon compte)

### ✅ Dashboard de suivi du staffing
Ce dashboard devra montrer :
- ✅ Une courbe avec le taux de staffing (pourcentage d’occupation des personnes productives). Cette courbe devra être filtrable sur des profils, des contributeurs, des BU. Si nécessaire préparer des données dans un modèle de données étoiles pour conserver des performances acceptables. Le Graph devra montrer l’évolution sur des périodes de temps longues (-6mois à +6mois par rapport à la date actuelle)
- ✅ Une courbe montrant le TACE (Taux d’activité Congés Exclus) des personnes productives (contributeurs avec des profils identifiés comme productifs). Le Graph devra montrer l’évolution sur des périodes de temps longues (-6mois à +6mois par rapport à la date actuelle)
- ✅ Modèle en étoile : DimProfile, DimTime, FactStaffingMetrics
- ✅ Service StaffingMetricsCalculationService pour les calculs
- ✅ Repository StaffingMetricsRepository avec méthodes d'agrégation
- ✅ Commande CLI app:calculate-staffing-metrics
- ✅ Controller et templates /staffing/dashboard avec graphiques Chart.js
- ✅ Tableaux par profil et par contributeur

Définition :
- Taux de staffing : Le taux de staffing est un indicateur de pilotage des ressources. Il représente le pourcentage du temps où une équipe ou un collaborateur est affecté à des missions (souvent facturables) par rapport à son temps total disponible sur une période. Formule courante: Temps staffé (missions, projets, production) ÷ Temps disponible total (hors absences) × 100. Exemples d’interprétations:
    - 85%: bonne utilisation, marge pour formation/projets internes.
    - 100%: utilisation maximale, risque de surcharge.
    - <70%: sous-utilisation, besoin d’affectations supplémentaires.
      Variantes:
    - Taux de staffing facturable: ne compte que les heures vendues.
    - Taux de staffing global: inclut projets internes, support, formation.
- TACE : Le Taux d'Activité Congés Exclus est un indicateur qui permet de mesurer le nombre de jours produits par les collaborateurs (activités clients et internes) par rapport au nombre de jours travaillés en entreprise, hors congés.

---

## 📝 Notes

- Les estimations sont données pour 1 développeur full-stack Symfony
- Les tests sont inclus dans les estimations
- La documentation technique est à maintenir au fil des développements
- Prévoir des revues de code et QA entre chaque lot
- Possibilité de paralléliser certains lots (ex: Lot 4 + Lot 5)
    
