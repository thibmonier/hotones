# Sprint 3 — Lancement et analyse

**Date de début** : 2025-11-13  
**Durée estimée** : 2-3 semaines  
**Objectifs** : Compléter le Lot 2 (Saisie des Temps) et le Lot 3 (Dashboard Analytique)

---

## Lots du Sprint 3

### Lot 2 : Saisie des Temps (Priorité Haute)
### Lot 3 : Dashboard Analytique (Priorité Haute)

---

## État d'avancement actuel

### ✅ LOT 2.1 : Interface de saisie - **TERMINÉ (~100%)**

#### Fonctionnalités implémentées

**Grille de saisie hebdomadaire**
- ✅ Entity `Timesheet` avec relations projet/tâche/sous-tâche
- ✅ Affichage hebdomadaire avec navigation semaine précédente/suivante
- ✅ Grille projet × tâche × jour
- ✅ Sauvegarde en AJAX via route `/timesheet/save`
- ✅ Validation côté serveur
- ✅ Template `templates/timesheet/index.html.twig`

**Compteur de temps (Timer)**
- ✅ Entity `RunningTimer` avec relations
- ✅ Repository pour trouver le timer actif
- ✅ Routes API :
  - `POST /timesheet/timer/start` - Démarre un timer
  - `POST /timesheet/timer/stop` - Arrête le timer actif
  - `GET /timesheet/timer/active` - Récupère le timer actif
  - `GET /timesheet/timer/options` - Liste projets/tâches pour le timer
- ✅ Imputation automatique en heures (min 1h = 0,125j)
- ✅ Un seul timer actif à la fois
- ✅ Arrêt auto du timer précédent si nouveau démarré
- ✅ Intégration dans la topbar (voir `templates/layouts/_topbar.html.twig`)

**Sélection projet → tâche**
- ✅ Cascade via JavaScript
- ✅ Filtrage par tâches assignées au contributeur
- ✅ Support des sous-tâches

**Validation**
- ✅ Validation max 24h/jour (à vérifier si implémentée côté serveur)
- ✅ Conversion heures/jours (1j = 8h)
- ✅ Commentaires optionnels (champ `notes` dans Timesheet)

#### Fichiers clés

- `src/Entity/Timesheet.php` - Entité principale
- `src/Entity/RunningTimer.php` - Timer en cours
- `src/Controller/TimesheetController.php` - Contrôleur principal
- `src/Repository/TimesheetRepository.php` - Requêtes custom
- `src/Repository/RunningTimerRepository.php` - Gestion des timers
- `templates/timesheet/index.html.twig` - Grille hebdomadaire
- `templates/timesheet/my_time.html.twig` - Historique personnel
- `templates/timesheet/all.html.twig` - Vue admin tous les temps

---

### 🔲 LOT 2.2 : Vue calendrier - **À FAIRE**

#### Fonctionnalités à implémenter

- [ ] Calendrier mensuel avec saisie rapide
- [ ] Copie de semaine / duplication
- [ ] Import/Export CSV
- [ ] Vue alternative à la grille hebdomadaire

#### Proposition d'implémentation

**Routes à créer**
- `GET /timesheet/calendar` - Affichage du calendrier mensuel
- `POST /timesheet/calendar/save` - Sauvegarde rapide depuis calendrier
- `POST /timesheet/week/duplicate` - Dupliquer une semaine
- `GET /timesheet/export-csv` - Export CSV des temps
- `POST /timesheet/import-csv` - Import CSV des temps

**Templates à créer**
- `templates/timesheet/calendar.html.twig` - Vue calendrier
- Formulaire modal pour saisie rapide
- Interface de duplication de semaine

**Librairies suggérées**
- FullCalendar.js (déjà disponible dans assets)
- Papa Parse pour CSV parsing (à installer si nécessaire)

---

### 🔲 LOT 2.3 : Validation hiérarchique - **À FAIRE**

#### Fonctionnalités à implémenter

- [ ] Workflow approbation (brouillon / validé / approuvé)
- [ ] Commentaires de validation
- [ ] Historique des modifications
- [ ] Notifications aux managers

#### Proposition d'implémentation

**Ajout de champs à l'entité Timesheet**
```php
#[ORM\Column(type: 'string', length: 20)]
private string $status = 'draft'; // draft, validated, approved

#[ORM\Column(type: 'text', nullable: true)]
private ?string $validationComment = null;

#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(nullable: true)]
private ?User $validatedBy = null;

#[ORM\Column(type: 'datetime', nullable: true)]
private ?DateTimeInterface $validatedAt = null;
```

**Routes à créer**
- `GET /timesheet/pending-validation` - Liste des temps en attente (chef projet)
- `POST /timesheet/{id}/validate` - Valider un temps
- `POST /timesheet/{id}/reject` - Rejeter un temps avec commentaire
- `POST /timesheet/bulk-validate` - Validation en masse

**Événement existant**
- `src/Event/TimesheetPendingValidationEvent.php` (déjà créé !)

---

### 🔲 LOT 2.4 : Rapports - **À FAIRE**

#### Fonctionnalités à implémenter

- [ ] Récapitulatif mensuel par contributeur
- [ ] Récapitulatif par projet
- [ ] Export Excel/PDF
- [ ] Graphiques de temps passé

#### Proposition d'implémentation

**Routes à créer**
- `GET /reports/timesheet/contributor` - Rapport par contributeur
- `GET /reports/timesheet/project` - Rapport par projet
- `GET /reports/timesheet/export-excel` - Export Excel
- `GET /reports/timesheet/export-pdf` - Export PDF

**Services à créer**
- `src/Service/TimesheetReportService.php` - Génération des rapports
- `src/Service/ExcelExportService.php` - Export Excel (PhpSpreadsheet)
- `src/Service/PdfExportService.php` - Export PDF (DomPDF)

**Templates à créer**
- `templates/reports/timesheet/contributor.html.twig`
- `templates/reports/timesheet/project.html.twig`
- `templates/reports/timesheet/pdf.html.twig` (pour génération PDF)

---

## LOT 3 : Dashboard Analytique

### ✅ LOT 3.1 : Vues du dashboard - **~80% TERMINÉ**

#### Fonctionnalités implémentées

**Page principale**
- ✅ Route `/analytics/dashboard` (DashboardController)
- ✅ Template `templates/analytics/dashboard.html.twig`
- ✅ Sélection de période (aujourd'hui, semaine, mois, trimestre, année, custom)
- ✅ Persistance de la période en session

**Services de calcul**
- ✅ `MetricsCalculationService` - Calcul des KPIs
- ✅ Méthode `calculateKPIs()` - CA, marge, projets, etc.
- ✅ Méthode `calculateMonthlyEvolution()` - Évolution sur 12 mois

#### Fonctionnalités à compléter

- [ ] Vérifier que toutes les cartes KPIs sont affichées :
  - CA total / Marge / Taux de marge
  - Projets actifs / terminés
  - Devis en attente / gagnés
  - Taux d'occupation
- [ ] Graphiques d'évolution temporelle (Chart.js) - à vérifier dans le template
- [ ] Répartition par type de projet (camembert)
- [ ] Top contributeurs (Top 5)

---

### 🔲 LOT 3.2 : Filtres - **PARTIELLEMENT FAIT**

#### Fonctionnalités implémentées
- ✅ Filtre période (today, week, month, quarter, year, custom)

#### Fonctionnalités à implémenter
- [ ] Type de projet (forfait/régie, interne/client)
- [ ] Chef de projet
- [ ] Commercial
- [ ] Technologies

---

### 🔲 LOT 3.3 : Exports - **À FAIRE**

#### Fonctionnalités à implémenter
- [ ] Export PDF du dashboard
- [ ] Export Excel des données

---

### ✅ LOT 3.4 : Intégration Worker - **~90% TERMINÉ**

#### Fonctionnalités implémentées

**Infrastructure**
- ✅ Message `RecalculateMetricsMessage` (`src/Message/RecalculateMetricsMessage.php`)
- ✅ Handler `RecalculateMetricsMessageHandler` (`src/MessageHandler/RecalculateMetricsMessageHandler.php`)
- ✅ Service `MetricsCalculationService` (`src/Service/MetricsCalculationService.php`)
- ✅ Commande CLI `app:calculate-metrics` (`src/Command/CalculateMetricsCommand.php`)
- ✅ Route `/analytics/recalculate` pour déclencher le recalcul (POST)

**Modèle en étoile**
- ❓ À vérifier : Entités `DimTime`, `DimProject`, `FactProjectMetrics`, etc.
- ❓ À vérifier : Index unique sur `FactProjectMetrics`

#### Fonctionnalités à implémenter/vérifier
- [ ] Vérifier le modèle en étoile (dimensions + faits)
- [ ] Documentation worker
- [ ] Bouton "Recalculer" dans l'interface admin
- [ ] Cron automatique (quotidien) via Symfony Scheduler

---

## Dashboard Staffing (Bonus - déjà implémenté ✅)

D'après la roadmap, le dashboard staffing est déjà terminé :

- ✅ Modèle en étoile : DimProfile, DimTime, FactStaffingMetrics
- ✅ Service StaffingMetricsCalculationService
- ✅ Repository StaffingMetricsRepository
- ✅ Commande CLI app:calculate-staffing-metrics
- ✅ Controller et templates /staffing/dashboard
- ✅ Tableaux par profil et par contributeur

---

## Plan d'action pour le Sprint 3

### Semaine 1 : Complétion Lot 2

**Jour 1-2 : Lot 2.2 - Vue calendrier**
1. Créer le contrôleur et les routes pour la vue calendrier
2. Implémenter le template avec FullCalendar.js
3. Ajouter la fonctionnalité de duplication de semaine
4. Implémenter import/export CSV

**Jour 3-4 : Lot 2.3 - Validation hiérarchique**
1. Ajouter les champs de statut et validation à Timesheet
2. Créer les migrations de base de données
3. Implémenter les routes de validation
4. Créer l'interface de validation pour les managers
5. Configurer les notifications (event existant)

**Jour 5 : Lot 2.4 - Rapports**
1. Créer les services d'export (Excel, PDF)
2. Implémenter les routes et templates de rapports
3. Générer les graphiques de synthèse

### Semaine 2 : Complétion Lot 3

**Jour 1-2 : Lot 3.1 & 3.2 - Dashboard et filtres**
1. Vérifier et compléter les cartes KPIs dans le template
2. Ajouter les graphiques Chart.js manquants
3. Implémenter les filtres additionnels (projet, chef projet, commercial, technologies)
4. Tester les calculs de métriques

**Jour 3 : Lot 3.3 - Exports**
1. Implémenter l'export PDF du dashboard
2. Implémenter l'export Excel des données

**Jour 4-5 : Lot 3.4 - Worker et optimisations**
1. Vérifier le modèle en étoile
2. Ajouter le bouton "Recalculer" dans l'admin
3. Configurer le cron Symfony Scheduler
4. Documenter le système de worker

### Semaine 3 : Tests et finitions

**Jour 1-3 : Tests**
1. Tests unitaires pour les calculs (métriques, heures, validations)
2. Tests fonctionnels pour les contrôleurs
3. Tests E2E pour les parcours critiques

**Jour 4-5 : Documentation et revue**
1. Rédiger la documentation technique
2. Mettre à jour la roadmap
3. Créer le récapitulatif du Sprint 3
4. Revue de code et corrections

---

## Prérequis techniques

### Dépendances à vérifier
- [ ] PhpSpreadsheet pour exports Excel : `composer require phpoffice/phpspreadsheet`
- [ ] DomPDF pour exports PDF : `composer require dompdf/dompdf`
- [ ] Symfony Messenger configuré pour les workers
- [ ] Symfony Scheduler configuré pour les crons

### Configuration
- [ ] Vérifier la configuration de Messenger (`config/packages/messenger.yaml`)
- [ ] Vérifier la configuration du Scheduler (`config/packages/scheduler.yaml`)
- [ ] S'assurer que les workers tournent (`php bin/console messenger:consume async`)

---

## Fichiers clés à examiner

### Lot 2
- `src/Entity/Timesheet.php`
- `src/Entity/RunningTimer.php`
- `src/Controller/TimesheetController.php`
- `src/Repository/TimesheetRepository.php`
- `templates/timesheet/*.html.twig`

### Lot 3
- `src/Controller/Analytics/DashboardController.php`
- `src/Service/MetricsCalculationService.php`
- `src/Service/StaffingMetricsCalculationService.php`
- `src/MessageHandler/RecalculateMetricsMessageHandler.php`
- `src/Command/CalculateMetricsCommand.php`
- `templates/analytics/dashboard.html.twig`

---

## Checklist Sprint 3

### Lot 2 - Saisie des Temps
- [x] 2.1 Interface de saisie
- [ ] 2.2 Vue calendrier
- [ ] 2.3 Validation hiérarchique
- [ ] 2.4 Rapports

### Lot 3 - Dashboard Analytique
- [ ] 3.1 Vues du dashboard (compléter)
- [ ] 3.2 Filtres (compléter)
- [ ] 3.3 Exports
- [ ] 3.4 Worker (vérifier et finaliser)

### Général
- [ ] Tests unitaires
- [ ] Tests fonctionnels
- [ ] Tests E2E
- [ ] Documentation
- [ ] Récapitulatif Sprint 3

---

## Estimation

- **Lot 2 restant** : 3-4 jours
- **Lot 3 restant** : 4-5 jours
- **Tests** : 2-3 jours
- **Documentation** : 1 jour

**Total : 10-13 jours** (~2-3 semaines)
