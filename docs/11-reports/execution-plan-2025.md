# 🎯 Plan d'Exécution 2025 - Phases Prioritaires

> Plan d'exécution pour les Phases 1, 2 et 5
>
> Dernière mise à jour : 23 novembre 2025

## Liens
- Roadmap complète : [docs/roadmap-2025.md](./roadmap-2025.md)
- État d'avancement : [docs/status.md](./status.md)

---

## 📊 Vue d'ensemble

### Phases sélectionnées
- **Phase 1** : Consolidation & Professionnalisation (Q1 2025)
- **Phase 2** : Analytics Avancés & Prédictif (Q2 2025)
- **Phase 5** : UX/UI & Gamification (Q2-Q3 2025)

### Estimations globales
- **Total** : ~76-100 jours de développement
- **Durée** : 4-5 mois (pour 1 développeur full-stack)
- **Dates cibles** : Janvier - Mai 2025

### Objectifs stratégiques
1. **Professionnaliser** : Finaliser les fondations (temps, analytics, facturation)
2. **Anticiper** : Mettre en place des analytics prédictifs
3. **Engager** : Améliorer drastiquement l'UX et motiver les utilisateurs

---

## 🗓️ Sprint Planning - 10 Sprints de 2 semaines

### Sprint 1-2 : Saisie des Temps (4 semaines) - Phase 1
**Objectif** : Interface de saisie des temps production-ready

#### Sprint 1 (S1-S2) : Saisie hebdomadaire
- ✅ Interface grille hebdomadaire (7 jours)
- ✅ Auto-save (debounce 2s)
- ✅ Sélection projet → tâche en cascade (AJAX)
- ✅ Validation : max 24h/jour, min 0.125j
- ✅ Conversion heures ↔ jours (1j = 8h)
- ✅ Commentaires optionnels par ligne
- ✅ Tests fonctionnels + E2E

**Livrables** :
- Controller `TimesheetController` avec route `/timesheets/week`
- Template Twig responsive avec JavaScript vanilla ou Stimulus
- Repository method `TimesheetRepository::findByContributorAndWeek()`
- Tests : `TimesheetControllerTest`, `TimesheetWeekE2ETest`

**Estimation** : 10 jours

---

#### Sprint 2 (S3-S4) : Validation & Rapports
- ✅ Amélioration du compteur de temps (persistance en session)
- Permettre de masquer les samedis et dimanches en mettant une option d'affichage des week-ends (comme pour passage en affichage par jour)
- l'écran de saisie dit maximum 24h par jour, attention, la conversion dit 1j=8h, ca peut etre plus (heures supplémentaires) mais la norme reste nb heures travaillées par semaine / nombre de jours travaillés par semaine (ex. pour qqun au 32h, le travail est étalé sur 4j soit 8h par jour ou pour quelqu'un au 35h sur 5j = 7h par jour)
- ✅ Workflow de validation hiérarchique :
  - Contributeur : Soumettre (brouillon → en attente)
  - Chef de projet : Valider/Rejeter (en attente → validé/rejeté)
  - Manager : Approuver (validé → approuvé)
  - approbabtion automatique au bout de 3j pour chaque phase
- ✅ Commentaires de validation
- ✅ Historique des modifications (audit trail)
- ✅ Vue calendrier mensuel (FullCalendar)
- ✅ Copie de semaine / duplication
- ✅ Récapitulatif mensuel par contributeur
- ✅ Récapitulatif mensuel par projet
- ✅ Export Excel (PhpSpreadsheet)

**Livrables** :
- Entité `TimesheetValidation` (commentaires, date, validateur)
- State machine ou workflow Symfony (optionnel)
- Routes : `/timesheets/validate/{id}`, `/timesheets/calendar`, `/timesheets/export`
- Templates : calendrier, rapports mensuels
- Tests : workflow complet de validation

**Estimation** : 10 jours

**Total Sprint 1-2** : 20 jours (4 semaines)

---

### Sprint 3-4 : Dashboard Analytique (4 semaines) - Phase 1
**Objectif** : Dashboard KPIs avec worker de calcul asynchrone

#### Sprint 3 (S5-S6) : KPIs & Graphiques
- ✅ Page `/analytics/dashboard`
- ✅ Cartes KPIs principales :
  - CA total (avec évolution % vs période précédente)
  - Marge totale et taux de marge
  - Projets actifs / terminés
  - Devis en attente / gagnés
  - Taux d'occupation global
- ✅ Graphiques Chart.js :
  - Évolution CA mensuel (ligne)
  - Répartition par type de projet (camembert)
  - Top 5 contributeurs par CA généré (barres)
  - Évolution marge mensuelle (ligne)
- ✅ Filtres dynamiques :
  - Période (aujourd'hui, semaine, mois, trimestre, année, personnalisée)
  - Type de projet (forfait/régie, interne/client)
  - Chef de projet (sélection multiple)
  - Commercial (sélection multiple)
  - Technologies (sélection multiple)
- ✅ Méthodes d'agrégation dans repositories

**Livrables** :
- Controller `AnalyticsDashboardController`
- Service `AnalyticsService` (calculs KPIs)
- Repository methods dans `ProjectRepository`, `OrderRepository`, `TimesheetRepository`
- Templates avec Chart.js
- Tests : calculs de métriques, filtres

**Estimation** : 10 jours

---

#### Sprint 4 (S7-S8) : Worker & Scheduler
- ✅ Finalisation du modèle en étoile (si nécessaire)
- ✅ Service `MetricsCalculationService` :
  - Calcul incrémental (par période)
  - Upsert dans `FactProjectMetrics`
  - Gestion de la granularité (daily, weekly, monthly, quarterly, yearly)
- ✅ Message `RecalculateMetricsMessage`
- ✅ Handler `RecalculateMetricsMessageHandler`
- ✅ Commande CLI `app:calculate-metrics [year] [--granularity=monthly]`
- ✅ Scheduler Symfony (cron quotidien 02:00)
- ✅ Bouton "Recalculer" dans l'interface admin (dispatch message)
- ✅ Page `/admin/scheduler` pour monitoring
- ✅ Export PDF/Excel du dashboard
- ✅ Tests de performance (agrégations sur 10K+ timesheets)

**Livrables** :
- Service `MetricsCalculationService`
- Handler dans `src/MessageHandler/`
- Commande dans `src/Command/`
- Configuration Scheduler dans `config/packages/scheduler.yaml`
- Export PDF avec DomPDF ou Snappy
- Export Excel avec PhpSpreadsheet
- Tests de charge

**Estimation** : 10 jours

**Total Sprint 3-4** : 20 jours (4 semaines)

---

### Sprint 5 : Projets & Devis (2 semaines) - Phase 1
**Objectif** : Améliorations projets et génération PDF devis

#### Sprint 5 (S9-S10) : Filtres & PDF
- ✅ **Lot 1.3 - Projets** :
  - Filtres avancés dans listing (statut, type, technologies, dates, contributeurs)
  - Recherche full-text (nom, client, description)
  - Actions en masse (export CSV, changement statut, archivage)
  - Timeline du projet (historique événements)
- ✅ **Lot 1.4 - Devis PDF** :
  - Template PDF professionnel (logo, couleurs)
  - Génération avec DomPDF ou Snappy
  - Sections et lignes détaillées
  - Totaux HT/TTC
  - Prévisualisation avant téléchargement
  - Historique des versions

**Livrables** :
- Amélioration `ProjectController::index()` avec filtres avancés
- Route `/projects/bulk-action` pour actions en masse
- Service `PdfGeneratorService`
- Template PDF `templates/pdf/order.html.twig`
- Route `/orders/{id}/preview-pdf`, `/orders/{id}/download-pdf`
- Tests : génération PDF, filtres

**Estimation** : 7 jours

**Total Sprint 5** : 7 jours (2 semaines)

---

### Sprint 6-7 : Module de Facturation (4 semaines) - Phase 1
**Objectif** : Génération automatique des factures et dashboard trésorerie

#### Sprint 6 (S11-S12) : Entité Invoice & Génération
- ✅ Entité `Invoice` :
  - Numéro unique : F[année][mois][incrément] (ex: F202501001)
  - Relation vers Order (Many-to-One)
  - Montant HT, TVA, TTC
  - Date émission, date échéance
  - Statut : brouillon, envoyée, payée, en_retard, annulée
  - Date de paiement effective
- ✅ Entité `InvoiceLine` (lignes de facturation)
- ✅ Migration + fixtures
- ✅ CRUD complet des factures :
  - Liste avec filtres (statut, client, période)
  - Création manuelle
  - Édition (si brouillon uniquement)
  - Suppression (si brouillon uniquement)
- ✅ Génération automatique :
  - Depuis devis forfait signé (bouton "Générer facture")
  - Depuis temps régie du mois (commande CLI mensuelle)
- ✅ Template PDF professionnel :
  - En-tête avec logo
  - Mentions légales (SIRET, TVA, IBAN)
  - Lignes détaillées
  - Totaux HT/TVA/TTC
- ✅ Repository `InvoiceRepository` avec méthodes d'agrégation

**Livrables** :
- Entities `Invoice`, `InvoiceLine`
- Migration
- Controller `InvoiceController`
- Service `InvoiceGeneratorService`
- Template PDF `templates/pdf/invoice.html.twig`
- Commande `app:invoice:generate-monthly-regie`
- Tests : génération auto, PDF

**Estimation** : 10 jours

---

#### Sprint 7 (S13-S14) : Dashboard Trésorerie & Relances
- ✅ Dashboard de trésorerie (`/treasury/dashboard`) :
  - KPIs :
    - CA facturé vs CA encaissé
    - Factures en attente de paiement (€)
    - Factures en retard (€ et nombre)
    - Délai moyen de paiement par client
  - Graphique prévisionnel de trésorerie (90 jours)
  - Tableau des factures à échéance proche (7j, 15j, 30j)
  - Graphique évolution CA facturé vs encaissé (mensuel)
- ✅ Export comptable :
  - CSV pour import logiciel compta
  - Format FEC optionnel (pour logiciels français)
- ✅ Relances automatiques :
  - Email J+30 (relance courtoise)
  - Email J+45 (relance ferme)
  - Email J+60 (mise en demeure)
  - Commande CLI `app:invoice:send-reminders` (cron quotidien)
  - Templates email personnalisables
- ✅ Notification manager pour factures > J+45
- ✅ Workflow de paiement :
  - Bouton "Marquer comme payée" (màj statut + date paiement)
  - Historique des paiements

**Livrables** :
- Controller `TreasuryDashboardController`
- Service `TreasuryService` (calculs et prédictions)
- Service `InvoiceReminderService` (envoi relances)
- Commande `app:invoice:send-reminders`
- Templates email (3 niveaux de relance)
- Export CSV/FEC
- Tests : calculs trésorerie, relances

**Estimation** : 12 jours

**Total Sprint 6-7** : 22 jours (4 semaines)

---

## 🎯 Checkpoint Phase 1 Terminée (10 semaines)

**Livrables majeurs** :
- ✅ Saisie des temps production-ready avec validation
- ✅ Dashboard analytique avec worker de calcul
- ✅ Projets et devis avec PDF
- ✅ Module de facturation complet avec trésorerie

**Total Phase 1** : ~69 jours (~14 semaines pour 1 dev)

---

### Sprint 8-9 : Analytics Prédictifs (4 semaines) - Phase 2
**Objectif** : Anticiper les risques et opportunités business

#### Sprint 8 (S15-S16) : Forecasting & Risques Projet
- ✅ **Forecasting CA** :
  - Service `ForecastingService` :
    - Algorithme de régression linéaire simple (moyenne mobile pondérée)
    - Prise en compte de la saisonnalité (moyenne des 3 dernières années sur même mois)
    - Prédiction 3/6/12 mois
  - Dashboard `/analytics/forecasting` :
    - Graphique prévisionnel vs réalisé
    - Intervalle de confiance (min/max)
    - Comparaison avec objectifs annuels
- ✅ **Analyse des risques projet** :
  - Service `ProjectRiskAnalyzer` :
    - Score de santé (0-100) basé sur :
      - Budget consommé vs avancement temporel
      - Vélocité d'équipe (heures/semaine)
      - Dépassement de scope
      - Taux de rejet en validation
    - Classification : vert (>80), orange (50-80), rouge (<50)
  - Intégration dans `/projects/{id}` :
    - Badge de santé
    - Alertes automatiques
    - Recommandations d'action
  - Dashboard `/projects/at-risk` :
    - Liste projets à risque avec score
    - Filtres par niveau de risque
    - Export pour direction

**Livrables** :
- Service `ForecastingService`
- Service `ProjectRiskAnalyzer`
- Controller `ForecastingController`
- Templates : dashboard forecasting, liste projets à risque
- Commande `app:analyze-project-risks` (cron quotidien)
- Tests : algorithmes de prédiction, calculs de score

**Estimation** : 10 jours

---

#### Sprint 9 (S17-S18) : Prédiction Charge & Rentabilité
- ✅ **Prédiction de charge** :
  - Service `WorkloadPredictionService` :
    - Analyse du pipeline (devis en attente de signature)
    - Probabilité de gain par devis (basé sur historique client/commercial)
    - Simulation de charge si signature (par contributeur/profil)
    - Détection périodes de surcharge/sous-charge futures (3 mois)
  - Dashboard `/staffing/prediction` :
    - Timeline de charge prédite (graphique)
    - Alertes recrutement (si charge > capacité + 20%)
    - Recommandations d'allocation
- ✅ **Analyse de rentabilité prédictive** :
  - Service `ProfitabilityPredictor` :
    - Dès 30% de réalisation : estimation marge finale
    - Détection dérives budgétaires précoces
    - Recommandations de correction :
      - Réduction scope
      - Réaffectation contributeurs (profil moins cher)
      - Négociation avenant client
  - Intégration dans `/projects/{id}/profitability` :
    - Estimation marge finale vs budgetée
    - Scénarios (optimiste, réaliste, pessimiste)
    - Actions recommandées

**Livrables** :
- Service `WorkloadPredictionService`
- Service `ProfitabilityPredictor`
- Controllers : `WorkloadPredictionController`
- Templates : dashboard prédiction charge, onglet rentabilité prédictive
- Tests : simulations, scénarios

**Estimation** : 12 jours

**Total Sprint 8-9** : 22 jours (4 semaines)

---

### Sprint 10-11 : Dashboard RH & Talents (4 semaines) - Phase 2
**Objectif** : Piloter les ressources humaines et compétences

#### Sprint 10 (S19-S20) : KPIs RH & Compétences
- ✅ **KPIs RH** :
  - Dashboard `/hr/dashboard` :
    - Turnover (taux de départ annuel = départs/effectif moyen)
    - Absentéisme (taux et évolution)
    - Ancienneté moyenne par profil
    - Pyramide des âges (graphique)
    - Pyramide des compétences (répartition par profil)
  - Graphiques d'évolution temporelle
  - Comparaison avec objectifs RH
- ✅ **Gestion des compétences** :
  - Entité `Skill` (nom, catégorie: technique/soft, description)
  - Entité `ContributorSkill` (relation Many-to-Many) :
    - Contributeur ↔ Skill
    - Niveau : débutant (1), intermédiaire (2), confirmé (3), expert (4)
    - Date d'acquisition
    - Auto-évaluation vs évaluation manager
  - CRUD des compétences (`/admin/skills`)
  - Page contributeur (`/contributors/{id}/skills`) :
    - Liste des compétences avec niveaux
    - Matrice visuelle (radar chart)
    - Ajout/modification de compétences
  - Gap analysis :
    - Service `SkillGapAnalyzer`
    - Comparaison compétences requises (projets) vs disponibles (contributeurs)
    - Identification des besoins de formation

**Livrables** :
- Entities `Skill`, `ContributorSkill`
- Migration + fixtures
- Controllers : `HrDashboardController`, `SkillController`
- Service `SkillGapAnalyzer`
- Templates : dashboard RH, gestion compétences contributeur
- Tests : calculs KPIs RH, gap analysis

**Estimation** : 10 jours

---

#### Sprint 11 (S21-S22) : Revues & Onboarding
- ✅ **Revues annuelles** :
  - Entité `PerformanceReview` :
    - Contributeur
    - Manager (évaluateur)
    - Année
    - Statut : en_cours, terminée, validée
    - Auto-évaluation (JSON: compétences, réalisations, points à améliorer)
    - Évaluation manager (JSON: idem)
    - Objectifs SMART pour l'année suivante
    - Note globale (optionnel)
    - Date de la revue
  - Workflow :
    1. Manager lance la campagne (création reviews pour ses contributeurs)
    2. Contributeur remplit auto-évaluation
    3. Manager remplit évaluation
    4. Entretien (offline)
    5. Manager valide et définit objectifs
  - Interface `/performance-reviews` :
    - Liste des reviews (filtres par année, statut, contributeur)
    - Formulaire auto-évaluation
    - Formulaire évaluation manager
    - Historique des reviews par contributeur
- ✅ **Onboarding** :
  - Entité `OnboardingChecklist` (template par profil)
  - Entité `OnboardingTask` :
    - Titre, description
    - Assigné à (contributeur ou manager)
    - Date limite
    - Statut : à_faire, en_cours, terminé
    - Ordre
  - Création automatique à l'embauche (trigger sur EmploymentPeriod)
  - Page `/onboarding/{contributorId}` :
    - Checklist avec progression (%)
    - Marquer tâche comme terminée
    - Commentaires par tâche
  - Templates d'onboarding par profil :
    - Développeur : accès repos, setup local, formation Symfony, etc.
    - Chef de projet : accès clients, formation outils PM, etc.

**Livrables** :
- Entities `PerformanceReview`, `OnboardingChecklist`, `OnboardingTask`
- Migration + fixtures (templates onboarding)
- Controllers : `PerformanceReviewController`, `OnboardingController`
- Service `OnboardingService` (création automatique)
- Templates : reviews, onboarding
- Tests : workflow review, création onboarding

**Estimation** : 10 jours

**Total Sprint 10-11** : 20 jours (4 semaines)

---

### Sprint 12 : Rapports & Exports (2 semaines) - Phase 2
**Objectif** : Rapports professionnels pour direction et clients

#### Sprint 12 (S23-S24) : Rapports Standards
- ✅ Service `ReportGeneratorService` (abstraction)
- ✅ **Rapports disponibles** :
  1. **Rapport d'activité mensuel** (`/reports/activity`) :
     - Par projet : temps passé, CA généré, marge
     - Par client : tous projets du client
     - Par BU (si implémenté)
     - Période sélectionnable
  2. **Rapport financier** (`/reports/financial`) :
     - CA par type de projet, par commercial
     - Marges par projet
     - Coûts par contributeur
     - Rentabilité globale
  3. **Rapport contributeur** (`/reports/contributor/{id}`) :
     - Temps saisi par projet
     - Projets réalisés
     - Performance (CA généré, marge)
     - Compétences et évolution
  4. **Rapport commercial** (`/reports/sales`) :
     - Pipeline (devis en cours)
     - Taux de conversion (devis signés / créés)
     - CA signé par commercial
     - Évolution mensuelle
  5. **Rapport devis actifs** (`/reports/active-orders`) :
     - Filtres : dates, client, statut
     - Tableau : client, projet, CA, commercial, rentabilité, statut
- ✅ **Formats** :
  - PDF (DomPDF ou Snappy)
  - Excel (PhpSpreadsheet)
  - CSV
- ✅ **Personnalisation** :
  - Page `/admin/report-templates` :
    - Upload logo
    - Couleurs (header, footer)
    - Mentions légales
  - Entité `ReportTemplate` (stockage config)
- ✅ **Automatisation** :
  - Commande `app:report:generate [type] [format] [--email=email@example.com]`
  - Scheduler : génération hebdo/mensuelle selon type
  - Envoi automatique par email (destinataires configurables)

**Livrables** :
- Service `ReportGeneratorService`
- Controllers : `ReportController`
- Templates PDF/Excel pour chaque type de rapport
- Entité `ReportTemplate`
- Commande CLI
- Configuration Scheduler
- Tests : génération chaque type, exports

**Estimation** : 12 jours

**Total Sprint 12** : 12 jours (2 semaines)

---

## 🎯 Checkpoint Phase 2 Terminée (10 semaines)

**Livrables majeurs** :
- ✅ Analytics prédictifs (forecasting, risques, charge)
- ✅ Dashboard RH complet (KPIs, compétences, reviews, onboarding)
- ✅ Suite de rapports professionnels

**Total Phase 2** : ~54 jours (~11 semaines pour 1 dev)

---

### Sprint 13-14 : Améliorations UX/UI (4 semaines) - Phase 5
**Objectif** : Améliorer drastiquement l'expérience utilisateur

#### Sprint 13 (S25-S26) : Navigation & Tableaux
- ✅ **Navigation** :
  - Refonte menu latéral :
    - Regroupement logique par domaine
    - Icônes cohérentes (FontAwesome ou Boxicons)
    - Badges de notification (temps en attente, factures en retard)
    - Menu collapsible par section
  - Fil d'ariane sur toutes les pages
  - Breadcrumb automatique basé sur route
- ✅ **Recherche globale** :
  - Barre de recherche dans header
  - Recherche full-text sur :
    - Projets (nom, client, description)
    - Contributeurs (nom, prénom, email)
    - Devis (numéro, client)
    - Clients (nom, SIRET)
  - Résultats groupés par type
  - Raccourci clavier (Ctrl+K ou Cmd+K)
  - Service `GlobalSearchService` avec ElasticSearch ou simple SQL LIKE
- ✅ **Tableaux de données** (composant réutilisable) :
  - Pagination côté serveur (50/100/200 par page)
  - Tri multi-colonnes (shift+click)
  - Filtres avancés :
    - Sauvegarde dans session
    - Réinitialisation en un clic
    - Filtres prédéfinis (ex: "Mes projets actifs")
  - Actions en masse :
    - Sélection checkbox (select all)
    - Export CSV/Excel
    - Changement statut batch
    - Archivage batch
  - Component Stimulus ou Twig Component

**Livrables** :
- Refonte `templates/base.html.twig` (menu latéral)
- Component `BreadcrumbComponent`
- Service `GlobalSearchService`
- Controller `SearchController` (route `/search`)
- Component `DataTableComponent` (Twig ou Stimulus)
- JavaScript pour interactions (tri, filtres, sélection)
- Tests E2E : navigation, recherche

**Estimation** : 10 jours

---

#### Sprint 14 (S27-S28) : Formulaires & Notifications
- ✅ **Formulaires** :
  - Validation temps réel (AJAX) :
    - Uniqueness (email, numéro devis)
    - Format (SIRET, email, téléphone)
    - Règles métier (budget > 0, dates cohérentes)
  - Champs dépendants :
    - Sélection projet → charge tâches (cascade)
    - Sélection client → charge projets
    - Auto-complétion (Select2 ou TomSelect)
  - Indicateurs de progression :
    - Formulaire multi-étapes (wizard)
    - Barre de progression
    - Navigation étapes (prev/next)
  - Sauvegarde automatique (brouillon) :
    - Auto-save toutes les 30s (debounce)
    - Restauration au rechargement
    - Indicateur "Enregistré" vs "Modification en cours"
- ✅ **Notifications in-app** :
  - Amélioration centre de notifications :
    - Groupement par type
    - Marquer comme lue
    - Marquer tout comme lu
    - Filtres (non lues, aujourd'hui, cette semaine)
    - Badge de compteur en temps réel (WebSocket ou polling 60s)
  - Types de notifications :
    - Info (bleu)
    - Succès (vert)
    - Warning (orange)
    - Erreur (rouge)
  - Toast notifications (Toastr ou Notyf) :
    - Position configurable
    - Auto-dismiss après 5s
    - Actions rapides (ex: "Voir", "Annuler")

**Livrables** :
- JavaScript pour validation AJAX
- Component `WizardFormComponent`
- Service `AutoSaveService` (JavaScript)
- Amélioration `NotificationController`
- Template `notifications/center.html.twig`
- JavaScript pour toast et polling
- Tests : validation AJAX, auto-save

**Estimation** : 10 jours

**Total Sprint 13-14** : 20 jours (4 semaines)

---

### Sprint 15 : Cohérence UX/UI Globale (2 semaines) - Phase 5
**Objectif** : Harmoniser l'expérience utilisateur sur toutes les pages

#### Sprint 15 (S29-S30) : Audit et Standardisation
- ✅ **Audit UX/UI complet** :
  - Recensement de toutes les pages CRUD (liste, création, édition)
  - Identification des incohérences (titres, boutons, filtres, actions)
  - Création d'un guide de style interne (design system light)

- ✅ **Standardisation des en-têtes de page** :
  - Template réutilisable pour en-têtes :
    - Titre de page (h4.page-title) cohérent
    - Fil d'ariane (breadcrumb) sur toutes les pages
    - Boutons d'action principaux alignés à droite
  - Application sur toutes les pages :
    - Projets, Contributeurs, Devis, Clients
    - Temps, Planning, Congés
    - Analytics, Rapports

- ✅ **Refonte du menu latéral** :
  - **Retrait des entrées "Ajouter"** du menu :
    - ❌ "Ajouter un projet", "Ajouter un contributeur", etc.
    - ✅ Conserver uniquement les pages de liste dans le menu
  - **Boutons "Ajouter"** déplacés dans les pages de liste :
    - Position : en-tête de page, à droite (côté breadcrumb)
    - Style : bouton primary avec icône `<i class="mdi mdi-plus"></i>`
    - Exemple : "Nouveau projet", "Nouveau contributeur"
  - Menu simplifié et plus logique

- ✅ **Standardisation des pages de liste** :
  - **Filtres cohérents** :
    - Position : au-dessus du tableau, carte dédiée
    - Style : formulaire inline avec boutons "Filtrer" et "Réinitialiser"
    - Badge compteur de filtres actifs
    - Sauvegarde des filtres en session
  - **Actions par ligne standardisées** :
    - Colonne "Actions" à droite du tableau
    - Boutons groupés dans un dropdown ou boutons inline :
      - 👁️ Voir (si applicable)
      - ✏️ Modifier
      - 🗑️ Supprimer (avec confirmation)
    - Permissions respectées (IsGranted)
  - **Actions en masse** :
    - Checkbox "Tout sélectionner" dans l'en-tête
    - Checkbox par ligne
    - Barre d'actions apparaît quand sélection :
      - Compteur de sélection ("3 éléments sélectionnés")
      - Bouton "Supprimer la sélection" (confirmation modale)
      - Bouton "Exporter la sélection" (CSV)
      - Autres actions selon entité (changer statut, archiver, etc.)
  - **Pagination standardisée** :
    - Sélecteur de nombre d'éléments par page (25, 50, 100)
    - Pagination Bootstrap en bas de tableau
    - Affichage "Affichage de X à Y sur Z éléments"

- ✅ **Standardisation des formulaires** :
  - Layout cohérent :
    - Largeur max 800px pour lisibilité
    - Labels alignés au-dessus des champs
    - Champs requis marqués avec astérisque (*)
    - Messages d'aide en texte gris sous les champs
  - Boutons d'action standardisés :
    - Position : pied de formulaire, alignés à droite
    - "Enregistrer" (primary) + "Annuler" (secondary)
    - "Enregistrer et continuer" (optionnel, outline-primary)
  - Validation inline (messages d'erreur sous le champ concerné)

- ✅ **Component Twig réutilisables** :
  - `components/page_header.html.twig` :
    - Paramètres : title, breadcrumb, actions
  - `components/data_table.html.twig` :
    - Paramètres : columns, data, actions, massActions
  - `components/filter_panel.html.twig` :
    - Paramètres : form, activeCount
  - `components/pagination.html.twig` :
    - Paramètres : currentPage, totalPages, itemsPerPage

- ✅ **Refonte pages prioritaires** :
  - **Projets** :
    - Liste : filtres (statut, type, client, technologies), actions ligne + masse
    - Formulaire : layout standardisé
    - Menu : retrait "Ajouter un projet", bouton dans liste
  - **Contributeurs** :
    - Liste : filtres (profil, statut actif/inactif), actions ligne + masse
    - Formulaire : layout standardisé
    - Menu : retrait "Ajouter un contributeur"
  - **Devis** :
    - Liste : filtres (statut, client, période), actions ligne + masse
    - Formulaire : layout standardisé
    - Menu : retrait "Ajouter un devis"
  - **Clients** :
    - Liste : filtres (niveau service, CA), actions ligne + masse
    - Formulaire : layout standardisé
    - Menu : retrait "Ajouter un client"
  - **Temps** :
    - Grille hebdomadaire : en-tête standardisé
    - Vue mensuelle : filtres standardisés
  - **Autres pages** :
    - Technologies, Profils métier, Catégories de service
    - Analytics (en-tête uniquement, pas de CRUD)

- ✅ **JavaScript pour actions en masse** :
  - Script réutilisable `assets/js/mass-actions.js`
  - Gestion sélection checkbox
  - Affichage/masquage barre d'actions
  - Confirmation suppression masse
  - Envoi requête AJAX pour suppression

- ✅ **Documentation Design System** :
  - Fichier `docs/ui-design-system.md` :
    - Couleurs (primary, secondary, danger, success, etc.)
    - Typographie (titres, textes, liens)
    - Boutons (variantes, tailles, icônes)
    - Formulaires (layout, validation)
    - Tableaux (structure, actions)
    - Composants réutilisables
  - Exemples de code pour développeurs

**Livrables** :
- Audit UX/UI complet (tableau Excel ou Notion)
- Components Twig : `page_header`, `data_table`, `filter_panel`, `pagination`
- JavaScript : `mass-actions.js`
- Refonte de 10+ pages (projets, contributeurs, devis, clients, etc.)
- Menu latéral simplifié (sans entrées "Ajouter")
- Documentation Design System
- Tests E2E : navigation, actions en masse

**Estimation** : 10 jours

**Total Sprint 15** : 10 jours (2 semaines)

---

### Sprint 16-17 : Gamification (4 semaines) - Phase 5
**Objectif** : Motiver et engager les contributeurs

#### Sprint 16 (S31-S32) : Badges & Classements
- ✅ **Système de badges** :
  - Entité `Badge` :
    - Nom, description, icône, couleur
    - Type : automatique, manuel
    - Condition (JSON) : ex: `{"type": "timesheet_streak", "days": 30}`
  - Entité `ContributorBadge` :
    - Contributeur ↔ Badge
    - Date d'obtention
    - Progression (ex: 15/30 jours)
  - Service `BadgeUnlocker` :
    - Vérification conditions (event listener)
    - Attribution automatique
    - Notification contributeur
  - **Badges implémentés** :
    - 🌅 Early Bird : 1er à saisir ses temps de la semaine (4 semaines consécutives)
    - ✨ Perfectionist : Saisie sans erreur/rejet pendant 1 mois
    - 🏃 Marathon Runner : 3 mois sans absence
    - 🎓 Knowledge Sharer : 5+ formations données
    - 🐛 Bug Hunter : Signalement de 3+ bugs critiques (via système de tickets)
    - 💰 Top Earner : Top 3 CA généré du mois
    - 🎯 Deadline Master : 5 projets terminés dans les délais
  - Page `/badges` :
    - Catalogue de tous les badges
    - Mes badges obtenus
    - Badges en cours (progression)
    - Contributeurs ayant ce badge (classement)
- ✅ **Classements** :
  - Page `/leaderboard` :
    - Top contributeurs du mois (CA généré)
    - Top formateurs (heures de formation données)
    - Équipe la plus productive (par BU si implémenté)
    - Filtres : période, profil
  - Podium visuel (Top 3)
  - Évolution position (↑↓)

**Livrables** :
- Entities `Badge`, `ContributorBadge`
- Migration + fixtures (badges initiaux)
- Service `BadgeUnlocker`
- Event Listeners (ex: `TimesheetSubmittedListener`)
- Controllers : `BadgeController`, `LeaderboardController`
- Templates : catalogue badges, leaderboard
- Tests : attribution badges, conditions

**Estimation** : 10 jours

---

#### Sprint 17 (S33-S34) : Progression & Récompenses
- ✅ **Progression de carrière** :
  - Entité `CareerPath` :
    - Profil de départ → Profil d'arrivée
    - Compétences requises (Many-to-Many avec Skill)
    - Expérience minimum (années)
    - Validateurs (managers)
  - Entité `ContributorLevel` :
    - Contributeur
    - Niveau : Junior (0), Confirmé (1), Senior (2), Lead (3), Principal (4)
    - XP (points d'expérience)
    - Date de passage au niveau
  - **Arbre de compétences visuel** :
    - Page `/my-career-path` :
      - Profil actuel
      - Profils accessibles (avec % de progression)
      - Compétences manquantes
      - Estimation temps (basée sur historique)
  - **Déblocage de nouveaux profils** :
    - Workflow de demande :
      1. Contributeur demande changement profil
      2. Manager valide compétences + expérience
      3. Création nouvelle EmploymentPeriod (si validé)
    - Notification manager
- ✅ **Récompenses** :
  - Entité `Reward` :
    - Nom, description, coût XP
    - Type : télétravail_bonus, formation_payée, jour_congé_bonus, matériel
    - Conditions d'éligibilité
  - **Points d'expérience (XP)** :
    - Gain XP :
      - Saisie temps à l'heure : +10 XP
      - Badge obtenu : +50 à +200 XP selon badge
      - Projet terminé dans les délais : +100 XP
      - Formation donnée : +50 XP
    - Service `XpCalculator`
    - Historique XP (transactions)
  - **Catalogue de récompenses** :
    - Page `/rewards` :
      - Récompenses disponibles
      - Mes XP actuels
      - Historique des récompenses obtenues
    - Bouton "Débloquer" (consomme XP)
    - Workflow de validation manager (pour récompenses à forte valeur)
  - **Niveaux** :
    - Junior : 0-999 XP
    - Confirmé : 1000-2499 XP
    - Senior : 2500-4999 XP
    - Lead : 5000-9999 XP
    - Principal : 10000+ XP

**Livrables** :
- Entities `CareerPath`, `ContributorLevel`, `Reward`, `ContributorReward`, `XpTransaction`
- Migration + fixtures
- Service `XpCalculator`
- Event Listeners pour attribution XP
- Controllers : `CareerPathController`, `RewardController`
- Templates : arbre carrière, catalogue récompenses
- Tests : calculs XP, déblocage récompenses

**Estimation** : 12 jours

**Total Sprint 16-17** : 22 jours (4 semaines)

---

### Sprint 18 : Module Documentaire (2 semaines) - Phase 5
**Objectif** : Centraliser la documentation projet et entreprise

#### Sprint 18 (S35-S36) : Bibliothèque & Wiki
- ✅ **Bibliothèque documentaire** :
  - Entité `Document` :
    - Nom, description
    - Type : cahier_charges, specs, pv_reunion, livrable, autre
    - Fichier (path dans filesystem ou stockage S3)
    - Taille, extension
    - Version (numéro)
    - Relation : Project (optionnel), Client (optionnel)
    - Confidentialité : public, interne, confidentiel
    - Téléchargements (compteur)
  - Upload de fichiers :
    - Multi-upload (drag & drop)
    - Validation taille max (50 Mo)
    - Validation extensions (PDF, DOCX, XLSX, PPTX, ZIP, PNG, JPG)
  - Gestion de versions :
    - Upload nouvelle version (incrémente numéro)
    - Historique des versions
    - Restauration version antérieure
    - Comparaison versions (optionnel)
  - **Recherche full-text** :
    - Service `DocumentSearchService` avec ElasticSearch (optionnel) ou simple SQL
    - Extraction texte des PDF (pdftotext)
    - Indexation contenu des DOCX (PHPWord ou antiword)
    - Résultats avec preview (extrait pertinent)
  - Organisation :
    - Arborescence par projet/client
    - Tags (Many-to-Many)
    - Favoris
- ✅ **Templates de documents** :
  - Entité `DocumentTemplate` :
    - Nom, description
    - Fichier template (DOCX avec placeholders)
    - Type de projet compatible
  - Génération document depuis template :
    - Remplacement variables : {{project.name}}, {{client.name}}, {{date}}, etc.
    - Service `DocumentTemplateProcessor` (PHPWord)
    - Download DOCX pré-rempli
  - Templates fournis :
    - Cahier des charges type
    - Spécifications techniques
    - PV de réunion
    - Rapport de livraison
- ✅ **Wiki interne** :
  - Entité `WikiPage` :
    - Titre, slug, contenu (Markdown)
    - Catégorie : technologie, tutoriel, best_practice, onboarding, changelog
    - Auteur (User)
    - Tags
    - Date création/modification
  - CRUD pages wiki (`/wiki`)
  - Éditeur Markdown (SimpleMDE ou EasyMDE)
  - Rendu HTML (League CommonMark)
  - Recherche full-text
  - Versioning (historique modifications)
  - Liens internes entre pages
- ✅ **Gestion des accès** :
  - Permissions par rôle sur documents confidentiels
  - Partage externe sécurisé :
    - Génération lien temporaire (expiration 7j/30j)
    - Protection par mot de passe (optionnel)
    - Tracking téléchargements

**Livrables** :
- Entities `Document`, `DocumentTemplate`, `WikiPage`, `DocumentShare`
- Migration + fixtures (templates de base, pages wiki initiales)
- Service `DocumentSearchService`, `DocumentTemplateProcessor`
- Controllers : `DocumentController`, `WikiController`
- Intégration EasyMDE pour Markdown
- Templates : bibliothèque, upload, wiki
- Tests : upload, versioning, recherche, génération templates

**Estimation** : 12 jours

**Total Sprint 18** : 12 jours (2 semaines)

---

## 🎯 Checkpoint Phase 5 Terminée (12 semaines)

**Livrables majeurs** :
- ✅ UX/UI professionnelle (navigation, recherche, tableaux, formulaires)
- ✅ Cohérence UX/UI globale (en-têtes, filtres, actions standardisées)
- ✅ Gamification complète (badges, XP, progression, récompenses)
- ✅ Module documentaire (bibliothèque, templates, wiki)

**Total Phase 5** : ~64 jours (~13 semaines pour 1 dev)

---

## 📊 Récapitulatif Global

| Phase | Sprints | Durée | Estimation | Dates indicatives |
|-------|---------|-------|------------|-------------------|
| **Phase 1 : Consolidation** | S1-S7 | 14 semaines | 69 jours | Janv-Avril 2025 |
| **Phase 2 : Analytics** | S8-S12 | 10 semaines | 54 jours | Avril-Juin 2025 |
| **Phase 5 : UX/UI** | S13-S18 | 12 semaines | 64 jours | Juin-Août 2025 |
| **TOTAL** | 18 sprints | **36 semaines** | **187 jours** | **Janv-Septembre 2025** |

**Notes** :
- Estimation pour **1 développeur full-stack Symfony expérimenté**
- 1 sprint = 2 semaines = 10 jours ouvrés
- 36 semaines ≈ **9 mois calendaires** (avec vacances et imprévus)
- Livraison cible : **Fin septembre 2025**

---

## 🚀 Quick Wins - Résultats rapides

### Fin Sprint 2 (S4 - Février 2025)
- ✅ Saisie des temps production-ready
- ✅ Validation hiérarchique opérationnelle
- ✅ Export Excel des timesheets
- **Impact** : Gain de productivité immédiat pour les contributeurs

### Fin Sprint 4 (S8 - Mars 2025)
- ✅ Dashboard analytique complet
- ✅ Worker de calcul automatique
- ✅ Exports PDF/Excel
- **Impact** : Visibilité temps réel sur les KPIs

### Fin Sprint 7 (S14 - Avril 2025)
- ✅ Module de facturation opérationnel
- ✅ Dashboard trésorerie
- ✅ Relances automatiques
- **Impact** : Amélioration cash-flow, réduction délais de paiement

### Fin Sprint 9 (S18 - Juin 2025)
- ✅ Analytics prédictifs (forecasting, risques)
- ✅ Prédiction de charge
- **Impact** : Anticipation des risques, optimisation recrutement

### Fin Sprint 14 (S28 - Juillet 2025)
- ✅ UX modernisée (recherche, tableaux, formulaires)
- ✅ Notifications temps réel
- **Impact** : Satisfaction utilisateurs, adoption accrue

### Fin Sprint 15 (S30 - Août 2025)
- ✅ Cohérence UX/UI sur toutes les pages
- ✅ Actions en masse opérationnelles
- ✅ Menu simplifié
- **Impact** : Application professionnelle et cohérente

---

## ⚠️ Risques & Mitigation

### Risques techniques

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Performance dashboard analytics (gros volumes) | Moyenne | Élevé | Implémenter cache Redis, optimiser index DB, pagination agressive |
| Complexité worker de calcul | Moyenne | Moyen | Tests de charge précoces, monitoring en production |
| Recherche full-text lente (ElasticSearch) | Faible | Moyen | Commencer par SQL LIKE simple, migrer vers ES si nécessaire |
| Prédictions ML peu fiables (manque de données) | Moyenne | Moyen | Algorithmes simples (régression linéaire), amélioration itérative |
| Upload documents volumineux | Faible | Faible | Limite 50 Mo, utiliser chunked upload (Uppy.js) |

### Risques projet

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Scope creep (demandes supplémentaires) | Élevée | Élevé | Backlog strict, validation avant chaque sprint |
| Dépendances bloquantes entre lots | Moyenne | Moyen | Identification précoce, parallélisation quand possible |
| Bugs en production | Moyenne | Élevé | Tests automatisés (cible 80% couverture), staging environment |
| Retard sur planning | Moyenne | Moyen | Buffer 20% sur estimations, revue hebdo avancement |
| Feedback utilisateurs tardif | Moyenne | Élevé | Démo en fin de sprint, bêta-testeurs internes |

### Mitigation globale
- **Revues de sprint** : Démo + rétrospective toutes les 2 semaines
- **Tests automatisés** : Objectif 80% de couverture code
- **CI/CD** : Déploiement automatique en staging
- **Monitoring** : Sentry pour erreurs, Grafana pour métriques
- **Documentation** : Mise à jour au fil de l'eau

---

## 🎯 Critères de Succès

### Phase 1 (Consolidation)
- ✅ 90%+ des contributeurs saisissent leurs temps chaque semaine
- ✅ Dashboard analytics utilisé quotidiennement par la direction
- ✅ Délai moyen de paiement des factures < 35 jours
- ✅ Temps de génération du dashboard < 2s (avec cache)

### Phase 2 (Analytics)
- ✅ Prévisions CA à +/- 10% de la réalité
- ✅ 80%+ des projets à risque identifiés avant dérive critique
- ✅ Turnover réduit de 20% grâce au suivi RH

### Phase 5 (UX/UI)
- ✅ Temps de saisie des temps réduit de 50%
- ✅ Satisfaction utilisateurs > 8/10
- ✅ 70%+ des contributeurs débloquent au moins 1 badge par mois
- ✅ Utilisation wiki interne : 50+ pages créées en 3 mois

---

## 📝 Prochaines Étapes

### Semaine prochaine
1. **Setup sprint 1** :
   - Créer les tickets dans backlog (GitHub Issues ou autre)
   - Définir la Definition of Done (DoD) pour Lot 2
   - Préparer les fixtures de test (contributeurs, projets, temps)
2. **Environnement** :
   - Configurer Symfony Messenger (transport async)
   - Setup Redis pour cache (optionnel sprint 1)
   - Configurer CI/CD pour déploiement staging
3. **Équipe** :
   - Briefing développeur(s) sur le plan
   - Identification des beta-testeurs internes
   - Communication roadmap aux utilisateurs finaux

### Suivi et ajustements
- **Revue hebdomadaire** : Point avancement, blocages, ajustements
- **Démo sprint** : Présentation des fonctionnalités toutes les 2 semaines
- **Rétrospective** : What went well / What to improve
- **Backlog grooming** : Affinage du sprint suivant (1 semaine à l'avance)

---

**Document créé le** : 23 novembre 2025
**Prochaine revue** : Fin sprint 2 (Février 2025)
**Contact** : [Votre nom/email pour questions]
