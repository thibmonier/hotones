# 🎯 Plan d'Exécution Phase 2 - Analytics Avancés & Prédictif

> **Période** : Janvier - Mars 2025
> **Durée** : 10 semaines (5 sprints de 2 semaines)
> **Estimation** : 54 jours de développement
> **Objectif** : Anticiper les risques et opportunités business via analytics prédictifs

---

## 📊 Vue d'ensemble

### Contexte
La Phase 1 (Consolidation) est terminée avec :
- ✅ Saisie des temps opérationnelle
- ✅ Dashboard analytics avec worker de calcul
- ✅ Module de facturation et trésorerie
- ✅ Notes de frais
- ✅ Rate limiting API

### Objectifs Phase 2
1. **Anticiper** : Prévoir le CA, détecter les risques projet avant qu'ils ne deviennent critiques
2. **Optimiser** : Prédire la charge de travail et optimiser l'allocation des ressources
3. **Piloter les RH** : Dashboard RH complet avec gestion des compétences et onboarding
4. **Professionnaliser** : Suite de rapports professionnels pour direction et clients

---

## 🗓️ Planning - 5 Sprints de 2 semaines

### Vue d'ensemble
| Sprint | Dates | Lots | Estimation | Objectif |
|--------|-------|------|------------|----------|
| **Sprint 1** | S1-S2 | Lot 10.1 : Forecasting & Risques | 10j | Prédiction CA et détection projets à risque |
| **Sprint 2** | S3-S4 | Lot 10.2 : Charge & Rentabilité | 12j | Prédiction charge équipe et rentabilité projets |
| **Sprint 3** | S5-S6 | Lot 11.1 : KPIs RH & Compétences | 10j | Dashboard RH et matrice compétences |
| **Sprint 4** | S7-S8 | Lot 11.2 : Revues & Onboarding | 10j | Évaluations annuelles et parcours d'intégration |
| **Sprint 5** | S9-S10 | Lot 7 : Rapports & Exports | 12j | Suite de rapports professionnels |

**Total** : 54 jours (10 semaines)

---

## 🚀 Sprint 1 : Forecasting & Risques Projet (S1-S2)

### Objectif
Anticiper le chiffre d'affaires et identifier les projets à risque avant dérive critique.

### User Stories

#### US-1.1 : Forecasting du CA
**En tant que** Directeur
**Je veux** voir une prédiction du CA sur 3/6/12 mois
**Afin de** anticiper les besoins de trésorerie et ajuster la stratégie commerciale

**Critères d'acceptation :**
- Dashboard `/analytics/forecasting` accessible
- Graphique d'évolution CA réalisé vs prédit (Chart.js)
- Algorithme prenant en compte :
  - Historique des 12-24 derniers mois
  - Saisonnalité (moyenne des 3 dernières années sur même période)
  - Pipeline commercial (devis en cours × probabilité de signature)
- 3 scénarios : optimiste, réaliste, pessimiste
- Intervalle de confiance affiché
- Comparaison avec objectifs annuels
- Export Excel du forecast

**Acceptance criteria techniques :**
- Service `ForecastingService` avec méthode `predictRevenue(int $months, string $scenario)`
- Algorithme : régression linéaire pondérée + saisonnalité
- Données stockées dans `FactForecast` (table dédiée)
- Commande CLI `app:forecast:calculate --months=12 --scenario=realistic`
- Tests unitaires sur l'algorithme (fixtures avec données connues)

---

#### US-1.2 : Score de santé des projets
**En tant que** Chef de projet
**Je veux** voir un score de santé pour chaque projet
**Afin de** détecter rapidement les projets à risque

**Critères d'acceptation :**
- Badge de santé sur `/projects/{id}` : 🟢 Vert (>80), 🟠 Orange (50-80), 🔴 Rouge (<50)
- Score calculé sur :
  - **Budget** : heures consommées vs budget (poids 40%)
  - **Délais** : avancement temporel vs avancement réel (poids 30%)
  - **Vélocité** : heures/semaine vs moyenne projet (poids 20%)
  - **Qualité** : taux de rejet en validation (poids 10%)
- Alertes automatiques si score < 60 (email chef de projet + manager)
- Historique de l'évolution du score (graphique)
- Dashboard `/projects/at-risk` avec liste des projets à risque
- Filtres par niveau de risque (critique, élevé, moyen)
- Recommandations d'action (scope, staffing, rallonge budget)

**Acceptance criteria techniques :**
- Service `ProjectRiskAnalyzer` avec méthode `calculateHealthScore(Project $project): int`
- Entité `ProjectHealthScore` (historique quotidien)
- Commande CLI `app:project:analyze-risks` (cron quotidien)
- Event listener sur création Timesheet → recalcul score si projet impacté
- Tests : scénarios de projets (sain, à risque, critique)

---

### Livrables Sprint 1
- [ ] Service `ForecastingService` avec tests
- [ ] Service `ProjectRiskAnalyzer` avec tests
- [ ] Controller `ForecastingController`
- [ ] Dashboard `/analytics/forecasting`
- [ ] Dashboard `/projects/at-risk`
- [ ] Badge de santé dans `/projects/{id}`
- [ ] Commandes CLI (forecast, analyze-risks)
- [ ] Migration pour tables `FactForecast`, `ProjectHealthScore`
- [ ] Tests E2E : navigation forecasting, alerte projet à risque
- [ ] Documentation : algorithmes de prédiction

**Estimation** : 10 jours

**Dépendances** :
- ✅ Dashboard analytics opérationnel (Phase 1)
- ✅ Calcul de métriques en place (MetricsCalculationService)

---

## 🚀 Sprint 2 : Prédiction Charge & Rentabilité (S3-S4)

### Objectif
Anticiper les besoins en recrutement et détecter les dérives budgétaires précocement.

### User Stories

#### US-2.1 : Prédiction de charge de travail
**En tant que** Responsable Staffing
**Je veux** anticiper les périodes de surcharge/sous-charge
**Afin de** planifier les recrutements et optimiser l'allocation

**Critères d'acceptation :**
- Dashboard `/staffing/prediction` accessible
- Timeline de charge prédite sur 3 mois (graphique)
- Analyse du pipeline commercial :
  - Devis en cours avec probabilité de signature (historique client/commercial)
  - Estimation charge si projet signé (par profil)
  - Date probable de démarrage
- Détection automatique :
  - Surcharge : capacité disponible < charge prévue - 20%
  - Sous-charge : capacité disponible > charge prévue + 30%
- Alertes recrutement avec recommandations :
  - Profil à recruter
  - Date idéale d'arrivée
  - Nombre de postes
- Simulation "What-if" : impact signature d'un devis sur la charge
- Export Excel des prévisions

**Acceptance criteria techniques :**
- Service `WorkloadPredictionService`
  - `predictWorkload(DateTime $startDate, int $months): array`
  - `analyzeOrdersPipeline(): array`
- Entité `WorkloadForecast` (historique des prédictions)
- Algorithme :
  - Charge actuelle : plannings + temps saisis
  - Charge future : pipeline × probabilité × durée estimée
  - Capacité : contributeurs actifs × disponibilité
- Commande CLI `app:workload:predict --months=3`
- Tests : scénarios surcharge, sous-charge, équilibré

---

#### US-2.2 : Analyse de rentabilité prédictive
**En tant que** Directeur financier
**Je veux** estimer la marge finale d'un projet dès 30% de réalisation
**Afin de** détecter les dérives budgétaires et corriger rapidement

**Critères d'acceptation :**
- Onglet "Rentabilité prédictive" dans `/projects/{id}/profitability`
- Estimation marge finale basée sur :
  - Vélocité réelle (heures consommées / avancement)
  - Extrapolation linéaire jusqu'à 100%
  - Facteur de risque (complexité, retards, turnover équipe)
- 3 scénarios : optimiste (+10%), réaliste, pessimiste (-15%)
- Comparaison marge prédite vs marge budgetée
- Graphique d'évolution de la prédiction (hebdomadaire)
- Détection de dérive :
  - Alerte si marge prédite < marge budgetée - 10%
  - Notification chef de projet + manager
- Recommandations de correction :
  - **Réduction scope** : features à descoper pour revenir dans le budget
  - **Réallocation** : remplacer senior par confirmé (exemple avec impact €)
  - **Avenant client** : montant supplémentaire à facturer
- Actions suivies : acceptée, refusée, en cours
- Export PDF du rapport de rentabilité prédictive

**Acceptance criteria techniques :**
- Service `ProfitabilityPredictor`
  - `predictFinalMargin(Project $project, string $scenario): array`
  - `detectDrift(Project $project): bool`
  - `generateRecommendations(Project $project): array`
- Entité `ProfitabilityForecast` (historique hebdomadaire)
- Commande CLI `app:profitability:predict {projectId}`
- Event listener : calcul automatique chaque semaine (Scheduler)
- Tests : projets en dérive, projets sains, recommandations

---

### Livrables Sprint 2
- [ ] Service `WorkloadPredictionService` avec tests
- [ ] Service `ProfitabilityPredictor` avec tests
- [ ] Dashboard `/staffing/prediction`
- [ ] Onglet `/projects/{id}/profitability`
- [ ] Entités `WorkloadForecast`, `ProfitabilityForecast`
- [ ] Commandes CLI (workload predict, profitability predict)
- [ ] Scheduler pour calculs automatiques hebdomadaires
- [ ] Tests E2E : prédiction charge, détection dérive
- [ ] Documentation : formules et algorithmes

**Estimation** : 12 jours

**Dépendances** :
- ✅ Dashboard staffing (Phase 1)
- ✅ Planning et TACE opérationnels
- ✅ Sprint 1 terminé (algorithmes de prédiction)

---

## 🚀 Sprint 3 : KPIs RH & Compétences (S5-S6)

### Objectif
Piloter les ressources humaines avec un dashboard RH complet et une matrice de compétences.

### User Stories

#### US-3.1 : Dashboard RH avec KPIs
**En tant que** Responsable RH
**Je veux** suivre les indicateurs clés RH
**Afin de** piloter la stratégie RH et détecter les signaux faibles

**Critères d'acceptation :**
- Dashboard `/hr/dashboard` accessible
- **KPIs affichés** :
  - **Turnover** : taux de départ annuel = (départs / effectif moyen) × 100
  - **Absentéisme** : jours d'absence / jours travaillés × 100
  - **Ancienneté moyenne** : par profil métier
  - **Pyramide des âges** : graphique (tranches 20-25, 25-30, 30-35, etc.)
  - **Pyramide des profils** : répartition par profil métier
  - **Effectif** : évolution mensuelle (entrées, sorties, total)
- Graphiques d'évolution temporelle (12 derniers mois)
- Comparaison avec objectifs RH annuels
- Filtres : période, BU (si applicable)
- Export Excel du dashboard RH

**Acceptance criteria techniques :**
- Service `HrMetricsCalculator`
  - `calculateTurnover(DateTime $startDate, DateTime $endDate): float`
  - `calculateAbsenteeism(DateTime $startDate, DateTime $endDate): float`
  - `getAgeDistribution(): array`
- Repository methods dans `ContributorRepository`, `EmploymentPeriodRepository`
- Controller `HrDashboardController`
- Tests : calculs KPIs avec fixtures

---

#### US-3.2 : Gestion des compétences
**En tant que** Manager
**Je veux** visualiser et gérer les compétences de mon équipe
**Afin de** identifier les besoins de formation et optimiser les affectations

**Critères d'acceptation :**
- Entité `Skill` (nom, catégorie: technique/soft, description)
- Entité `ContributorSkill` :
  - Niveau : 1 (Débutant), 2 (Intermédiaire), 3 (Confirmé), 4 (Expert)
  - Auto-évaluation vs Évaluation manager
  - Date d'acquisition, date dernière utilisation
- CRUD compétences dans `/admin/skills`
- Page contributeur `/contributors/{id}/skills` :
  - Liste des compétences avec niveaux
  - Matrice visuelle (radar chart avec Chart.js)
  - Ajout/modification/suppression de compétences
  - Comparaison auto-évaluation vs manager
- **Gap analysis** :
  - Service `SkillGapAnalyzer`
  - Comparaison compétences requises (projets actifs) vs disponibles (équipe)
  - Dashboard `/hr/skill-gaps` :
    - Technologies manquantes (aucun expert)
    - Compétences faibles (1 seul expert, risque)
    - Recommandations de formation
- Import CSV de compétences en masse
- Export Excel de la matrice compétences

**Acceptance criteria techniques :**
- Entities `Skill`, `ContributorSkill`
- Migration + fixtures (50 compétences techniques + 20 soft skills)
- Service `SkillGapAnalyzer`
  - `analyzeGaps(): array`
  - `getRecommendations(): array`
- Controllers : `SkillController`, `ContributorSkillController`
- Tests : gap analysis avec différents scénarios

---

### Livrables Sprint 3
- [ ] Service `HrMetricsCalculator` avec tests
- [ ] Service `SkillGapAnalyzer` avec tests
- [ ] Dashboard `/hr/dashboard`
- [ ] Dashboard `/hr/skill-gaps`
- [ ] CRUD `/admin/skills`
- [ ] Page `/contributors/{id}/skills` avec radar chart
- [ ] Entités `Skill`, `ContributorSkill`
- [ ] Migration + fixtures (compétences)
- [ ] Import CSV compétences
- [ ] Tests E2E : gestion compétences, gap analysis
- [ ] Documentation : modèle de compétences

**Estimation** : 10 jours

**Dépendances** :
- ✅ CRUD Contributeurs (Phase 1)
- ✅ EmploymentPeriod opérationnel

---

## 🚀 Sprint 4 : Revues Annuelles & Onboarding (S7-S8)

### Objectif
Structurer les évaluations annuelles et automatiser l'onboarding des nouveaux contributeurs.

### User Stories

#### US-4.1 : Campagne d'évaluation annuelle
**En tant que** Manager
**Je veux** mener les évaluations annuelles de façon structurée
**Afin de** suivre la progression de mes contributeurs et fixer des objectifs

**Critères d'acceptation :**
- Entité `PerformanceReview` :
  - Année, contributeur, manager évaluateur
  - Statut : en_attente, auto_eval_faite, eval_manager_faite, validée
  - Auto-évaluation (JSON) : réalisations, points forts, axes d'amélioration
  - Évaluation manager (JSON) : idem + feedback détaillé
  - Objectifs SMART pour l'année suivante (JSON array)
  - Note globale optionnelle (1-5)
  - Date entretien, commentaires
- Workflow :
  1. Manager lance campagne → crée reviews pour son équipe
  2. Contributeur notifié → remplit auto-évaluation
  3. Manager notifié → remplit évaluation
  4. Entretien en présentiel (hors système)
  5. Manager valide → définit objectifs année N+1
- Interface `/performance-reviews` :
  - Liste des reviews (filtres : année, statut, contributeur)
  - Formulaire auto-évaluation (questions structurées)
  - Formulaire évaluation manager
  - Historique des reviews par contributeur (timeline)
- Page `/performance-reviews/{id}` :
  - Vue détaillée (lecture seule si validée)
  - Comparaison auto-évaluation vs manager
  - Suivi des objectifs année précédente (si applicable)
- Campagne globale :
  - Route `/performance-reviews/campaign/create` (ROLE_ADMIN)
  - Création en masse des reviews pour une année
  - Emails de notification automatiques
- Export PDF de la review (pour archivage)

**Acceptance criteria techniques :**
- Entité `PerformanceReview`
- Migration + fixtures
- Service `PerformanceReviewService`
  - `createCampaign(int $year, array $managers): int` (retourne nb reviews créées)
  - `sendNotifications(PerformanceReview $review, string $step): void`
- Controller `PerformanceReviewController`
- Templates : liste, formulaires auto-eval, eval manager, vue détaillée
- Event listener : envoi emails aux étapes clés
- Tests : workflow complet, création campagne

---

#### US-4.2 : Parcours d'onboarding automatisé
**En tant que** Nouveau contributeur
**Je veux** avoir un parcours d'intégration clair
**Afin de** être opérationnel rapidement

**Critères d'acceptation :**
- Entité `OnboardingTemplate` (modèle par profil) :
  - Profil métier (Developer, Chef de projet, etc.)
  - Liste de tâches types (JSON array)
- Entité `OnboardingTask` :
  - Contributeur, template source
  - Titre, description, ordre
  - Assigné à : contributeur ou manager
  - Type : action, lecture, formation, meeting
  - Date limite (relative à date embauche : J+3, J+7, J+30)
  - Statut : à_faire, en_cours, terminé
  - Date de completion, commentaires
- Création automatique :
  - Event listener sur création EmploymentPeriod
  - Duplication du template selon profil
  - Calcul dates limites automatiques
- Page `/onboarding/{contributorId}` :
  - Checklist interactive avec progression (%)
  - Groupement par semaine (Semaine 1, Semaine 2, etc.)
  - Checkbox pour marquer tâche terminée
  - Champ commentaire par tâche
  - Timeline d'avancement
- Dashboard manager `/onboarding/team` :
  - Liste des onboarding en cours
  - Taux de complétion par contributeur
  - Tâches en retard (alertes)
- Templates par défaut :
  - **Développeur** : accès repos, setup local, formation framework, premiers commits
  - **Chef de projet** : accès clients, formation outils PM, shadow projet en cours
  - **Commercial** : formation produits, accès CRM, accompagnement senior
- CRUD templates dans `/admin/onboarding-templates`
- Export Excel du suivi onboarding

**Acceptance criteria techniques :**
- Entités `OnboardingTemplate`, `OnboardingTask`
- Migration + fixtures (3 templates par défaut)
- Service `OnboardingService`
  - `createOnboardingFromTemplate(Contributor $contributor): void`
  - `calculateProgress(Contributor $contributor): int`
- Event listener `EmploymentPeriodCreatedListener`
- Controllers : `OnboardingController`, `OnboardingTemplateController`
- Tests : création auto, calcul progression, templates

---

### Livrables Sprint 4
- [ ] Entité `PerformanceReview` avec migration
- [ ] Entité `OnboardingTemplate`, `OnboardingTask` avec migration
- [ ] Service `PerformanceReviewService` avec tests
- [ ] Service `OnboardingService` avec tests
- [ ] Interface `/performance-reviews` (liste, formulaires)
- [ ] Page `/onboarding/{contributorId}`
- [ ] Dashboard `/onboarding/team`
- [ ] CRUD `/admin/onboarding-templates`
- [ ] Event listeners (notifications, création auto)
- [ ] Fixtures (templates onboarding)
- [ ] Export PDF reviews
- [ ] Tests E2E : workflow review, onboarding automatique
- [ ] Documentation : process RH

**Estimation** : 10 jours

**Dépendances** :
- ✅ Gestion contributeurs et périodes d'emploi
- Sprint 3 terminé (compétences pour lien avec reviews)

---

## 🚀 Sprint 5 : Rapports & Exports Professionnels (S9-S10)

### Objectif
Générer des rapports professionnels pour la direction et les clients.

### User Stories

#### US-5.1 : Rapports standardisés
**En tant que** Directeur
**Je veux** générer des rapports professionnels
**Afin de** piloter l'activité et communiquer avec les clients

**Critères d'acceptation :**
- Page `/reports` avec menu des rapports disponibles
- **5 types de rapports** :

1. **Rapport d'activité mensuel** (`/reports/activity`)
   - Période sélectionnable
   - Filtres : projet, client, BU
   - Contenu :
     - Temps passé par projet
     - CA généré par projet
     - Marge par projet
     - Synthèse globale
   - Graphiques : camembert temps par projet, barres CA

2. **Rapport financier** (`/reports/financial`)
   - Période sélectionnable
   - Contenu :
     - CA par type de projet (forfait vs régie)
     - CA par commercial
     - Marges par projet (top 10 + bottom 10)
     - Coûts par contributeur
     - Rentabilité globale
   - Graphiques : évolution CA mensuel, marges par catégorie

3. **Rapport contributeur** (`/reports/contributor/{id}`)
   - Contributeur sélectionnable
   - Période sélectionnable
   - Contenu :
     - Temps saisi par projet
     - Projets réalisés (liste)
     - CA généré (si données disponibles)
     - Performance (vs objectifs)
     - Compétences et évolution
   - Graphiques : temps par projet (camembert), évolution mensuelle

4. **Rapport commercial** (`/reports/sales`)
   - Période sélectionnable
   - Contenu :
     - Pipeline (devis en cours par commercial)
     - Taux de conversion (devis signés / créés)
     - CA signé par commercial
     - Évolution mensuelle du pipeline
     - Top 10 clients par CA
   - Graphiques : funnel conversion, barres CA par commercial

5. **Rapport devis actifs** (`/reports/active-orders`)
   - Filtres : dates, client, statut, commercial
   - Tableau : client, projet, CA, commercial, rentabilité estimée, statut
   - Tri par colonne
   - Total CA pipeline

**Formats d'export :**
- PDF (DomPDF ou Snappy + Wkhtmltopdf)
- Excel (PhpSpreadsheet) avec multiple sheets
- CSV (simple)

**Personnalisation :**
- Page `/admin/report-settings` :
  - Upload logo société
  - Couleurs (header, footer)
  - Mentions légales (footer)
- Entité `ReportSettings` (singleton)

**Planification :**
- Génération planifiée (hebdo/mensuelle)
- Commande CLI `app:report:generate {type} --format=pdf --email=john@example.com --period=2025-01`
- Scheduler Symfony :
  - Rapport activité mensuel → 1er du mois à 08:00
  - Rapport financier → 5 du mois à 09:00
- Envoi automatique par email (destinataires configurables)

**Acceptance criteria techniques :**
- Service `ReportGeneratorService` (abstraction)
  - `generateActivityReport(DateTime $start, DateTime $end, string $format): string` (retourne path)
  - `generateFinancialReport(...)`
  - `generateContributorReport(...)`
  - `generateSalesReport(...)`
  - `generateActiveOrdersReport(...)`
- Service `PdfExportService` (génération PDF)
- Service `ExcelExportService` (génération Excel)
- Entité `ReportSettings`
- Controller `ReportController`
- Templates PDF/Excel pour chaque type
- Commande CLI `app:report:generate`
- Configuration Scheduler
- Tests : génération de chaque type, exports

---

#### US-5.2 : Bibliothèque de rapports générés
**En tant que** Utilisateur
**Je veux** accéder à l'historique des rapports générés
**Afin de** retrouver facilement un rapport précédent

**Critères d'acceptation :**
- Entité `GeneratedReport` :
  - Type de rapport
  - Format (PDF, Excel, CSV)
  - Période couverte
  - Paramètres (JSON)
  - Fichier généré (path)
  - Généré par (User)
  - Date de génération
  - Taille fichier
- Page `/reports/history` :
  - Liste des rapports générés
  - Filtres : type, format, période, générateur
  - Tri par date (DESC)
  - Actions : télécharger, supprimer (si propriétaire ou admin)
  - Pagination
- Nettoyage automatique :
  - Commande `app:report:cleanup --days=90` (supprime rapports > 90j)
  - Scheduler : exécution mensuelle
- Export/download sécurisé (vérification permissions)

**Acceptance criteria techniques :**
- Entité `GeneratedReport`
- Migration
- Controller `ReportHistoryController`
- Service `ReportCleanupService`
- Commande CLI `app:report:cleanup`
- Stockage fichiers : `var/reports/{year}/{month}/{filename}`
- Tests : génération, historique, cleanup

---

### Livrables Sprint 5
- [ ] Service `ReportGeneratorService` avec tests
- [ ] Service `PdfExportService` avec tests
- [ ] Service `ExcelExportService` avec tests
- [ ] Service `ReportCleanupService`
- [ ] 5 types de rapports implémentés
- [ ] Templates PDF pour chaque rapport
- [ ] Templates Excel pour chaque rapport
- [ ] Entité `ReportSettings`, `GeneratedReport`
- [ ] Page `/reports` (menu rapports)
- [ ] Page `/reports/history`
- [ ] Page `/admin/report-settings`
- [ ] Commande CLI `app:report:generate`
- [ ] Commande CLI `app:report:cleanup`
- [ ] Configuration Scheduler (génération auto)
- [ ] Tests E2E : génération rapports, download
- [ ] Documentation : guide rapports

**Estimation** : 12 jours

**Dépendances** :
- ✅ Tous les dashboards opérationnels (données disponibles)
- Sprints 1-4 terminés (données analytics, RH disponibles)

---

## 📋 Récapitulatif et Checklist Globale

### Estimation totale
- **Sprint 1** : 10 jours
- **Sprint 2** : 12 jours
- **Sprint 3** : 10 jours
- **Sprint 4** : 10 jours
- **Sprint 5** : 12 jours
- **TOTAL** : 54 jours (10 semaines)

### Services à créer
- [ ] `ForecastingService`
- [ ] `ProjectRiskAnalyzer`
- [ ] `WorkloadPredictionService`
- [ ] `ProfitabilityPredictor`
- [ ] `HrMetricsCalculator`
- [ ] `SkillGapAnalyzer`
- [ ] `PerformanceReviewService`
- [ ] `OnboardingService`
- [ ] `ReportGeneratorService`
- [ ] `PdfExportService`
- [ ] `ExcelExportService`
- [ ] `ReportCleanupService`

### Entités à créer
- [ ] `FactForecast`
- [ ] `ProjectHealthScore`
- [ ] `WorkloadForecast`
- [ ] `ProfitabilityForecast`
- [ ] `Skill`
- [ ] `ContributorSkill`
- [ ] `PerformanceReview`
- [ ] `OnboardingTemplate`
- [ ] `OnboardingTask`
- [ ] `ReportSettings`
- [ ] `GeneratedReport`

### Controllers à créer
- [ ] `ForecastingController`
- [ ] `WorkloadPredictionController`
- [ ] `HrDashboardController`
- [ ] `SkillController`
- [ ] `PerformanceReviewController`
- [ ] `OnboardingController`
- [ ] `ReportController`
- [ ] `ReportHistoryController`

### Commandes CLI à créer
- [ ] `app:forecast:calculate`
- [ ] `app:project:analyze-risks`
- [ ] `app:workload:predict`
- [ ] `app:profitability:predict`
- [ ] `app:report:generate`
- [ ] `app:report:cleanup`

### Pages à créer
- [ ] `/analytics/forecasting`
- [ ] `/projects/at-risk`
- [ ] `/staffing/prediction`
- [ ] `/projects/{id}/profitability`
- [ ] `/hr/dashboard`
- [ ] `/hr/skill-gaps`
- [ ] `/admin/skills`
- [ ] `/contributors/{id}/skills`
- [ ] `/performance-reviews`
- [ ] `/performance-reviews/{id}`
- [ ] `/onboarding/{contributorId}`
- [ ] `/onboarding/team`
- [ ] `/admin/onboarding-templates`
- [ ] `/reports`
- [ ] `/reports/activity`
- [ ] `/reports/financial`
- [ ] `/reports/contributor/{id}`
- [ ] `/reports/sales`
- [ ] `/reports/active-orders`
- [ ] `/reports/history`
- [ ] `/admin/report-settings`

---

## 🎯 Critères de Succès Phase 2

### Métriques de réussite

#### Lot 10 : Analytics Prédictifs
- ✅ Prévisions CA à +/- 10% de la réalité (validation après 3 mois)
- ✅ 80%+ des projets à risque identifiés avant dérive critique (>20% budget)
- ✅ Alertes prédictives envoyées au moins 2 semaines avant seuil critique
- ✅ Recommandations de staffing suivies dans 70%+ des cas

#### Lot 11 : Dashboard RH
- ✅ Dashboard RH consulté hebdomadairement par RH et direction
- ✅ Matrice de compétences complétée pour 90%+ des contributeurs
- ✅ Gap analysis identifie 100% des compétences critiques manquantes
- ✅ 80%+ des reviews annuelles complétées dans les 2 mois de la campagne
- ✅ 100% des nouveaux contributeurs ont un onboarding actif

#### Lot 7 : Rapports
- ✅ Rapports générés automatiquement chaque mois sans intervention
- ✅ Délai de génération < 30s pour rapports standards
- ✅ Rapports utilisés lors de 100% des COMEX/CODIR
- ✅ Satisfaction utilisateurs sur qualité rapports > 8/10

### Tests de validation

#### Tests unitaires
- Couverture > 80% pour tous les services de calcul
- Tests sur algorithmes de prédiction avec fixtures connues
- Tests sur formules de calcul (KPIs RH, scores santé)

#### Tests d'intégration
- Génération de rapports complets (avec vraies données de test)
- Calcul de forecasting sur 24 mois d'historique
- Workflow complet d'évaluation annuelle

#### Tests E2E
- Parcours utilisateur : consultation forecasting → alerte projet → action
- Parcours manager : lancement campagne review → validation
- Génération et téléchargement de chaque type de rapport

#### Tests de performance
- Dashboard forecasting : < 2s avec 3 ans d'historique
- Calcul risques sur 100 projets : < 5s
- Génération rapport Excel : < 10s pour 1 an de données

---

## ⚠️ Risques et Mitigation

### Risques techniques

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Algorithmes prédictifs peu fiables (manque données) | Moyenne | Moyen | Commencer simple (régression linéaire), itérer avec feedback |
| Performance calculs prédictifs (gros volumes) | Moyenne | Moyen | Cache Redis, calculs asynchrones (Messenger) |
| Complexité gap analysis compétences | Faible | Moyen | Version MVP d'abord (simple matching), enrichir ensuite |
| Génération PDF lente (rapports lourds) | Faible | Faible | Async generation, queue, notification email quand prêt |

### Risques métier

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Mauvaise adoption dashboard RH | Moyenne | Élevé | Formation utilisateurs, démo hebdo, collect feedback |
| Prédictions CA jugées inutiles par direction | Faible | Moyen | Validation algorithme avec données passées, ajustement |
| Reviews annuelles non remplies (contributeurs) | Moyenne | Moyen | Relances auto, deadline stricte, implication managers |
| Templates onboarding inadaptés | Moyenne | Faible | Co-construction avec RH, feedback nouveaux arrivants |

### Mitigation globale
- **Démo hebdomadaire** : Validation features avec utilisateurs finaux
- **Feedback loop** : Questionnaire après chaque sprint
- **Documentation** : Guide utilisateur pour chaque feature
- **Formation** : Session de formation avant mise en prod
- **Rollout progressif** : Beta test avec 2-3 managers pilotes

---

## 🚀 Prochaines Étapes Immédiates

### Cette semaine (Semaine 0)
1. **Validation du plan** :
   - Review avec Product Owner / Direction
   - Ajustements si nécessaire
   - Communication aux équipes

2. **Setup technique** :
   - Créer branche `feat/phase2-analytics`
   - Setup structure fichiers (Services, Controllers, Entities)
   - Préparer fixtures de test (données historiques suffisantes)

3. **Sprint 1 - Préparation** :
   - Créer tickets GitHub Issues (US-1.1, US-1.2)
   - Définir Definition of Done pour Sprint 1
   - Préparer données de test pour forecasting (24 mois historique)

### Semaine prochaine (Sprint 1 - Démarrage)
1. **Jour 1-2** : Service `ForecastingService`
   - Algorithme de régression linéaire
   - Prise en compte saisonnalité
   - Tests unitaires

2. **Jour 3-4** : Dashboard `/analytics/forecasting`
   - Controller et templates
   - Graphiques Chart.js
   - Filtres et scénarios

3. **Jour 5-7** : Service `ProjectRiskAnalyzer`
   - Calcul score de santé
   - Détection projets à risque
   - Génération recommandations

4. **Jour 8-9** : Dashboard `/projects/at-risk` et intégrations
   - Liste projets à risque
   - Badge santé dans `/projects/{id}`
   - Alertes automatiques

5. **Jour 10** : Tests E2E et documentation

---

## 📝 Notes Importantes

- **Priorisation** : Si contraintes de temps, prioriser Lot 10 (Analytics prédictifs) > Lot 7 (Rapports) > Lot 11 (RH)
- **Itérations** : Les algorithmes de prédiction seront affinés au fil du temps avec feedback utilisateurs
- **Données** : Vérifier que suffisamment de données historiques (12-24 mois) pour prédictions fiables
- **Performance** : Tous les calculs lourds doivent être asynchrones (Messenger) et cachés (Redis)
- **Documentation** : Documenter les formules et algorithmes pour transparence et maintenance

---

**Document créé le** : 9 décembre 2024
**Début prévu** : Janvier 2025
**Fin prévue** : Mars 2025
**Prochaine revue** : Fin Sprint 1 (mi-janvier 2025)
