# 🗺️ HotOnes - Roadmap Produit Unifiée

> **Version harmonisée** - Dernière mise à jour : 31 décembre 2025
>
> Cette roadmap consolidée remplace les versions précédentes et constitue **la référence unique** pour le planning produit HotOnes.

## 📊 Vue d'ensemble

### Statut global
- **Total de lots** : 36 lots
- **Terminés** : 5 lots (Lots 2, 3, 7, 11, 12)
- **En cours** : 2 lots (Lot 9 - 35%, Lot 11bis - 40%)
- **Planifiés** : 29 lots
- **Estimation totale** : ~360-455 jours de développement

### Légende
- ✅ Terminé et en production
- 🔄 En cours de développement
- ⏳ Planifié
- 🔴 Obligation légale
- ⭐ Stratégique

---

## 📑 Table des matières

- [🔥 Priorité Critique (Lots 1-10)](#-priorité-critique-lots-1-10)
- [🎯 Priorité Haute (Lots 11-18)](#-priorité-haute-lots-11-18)
- [🟡 Priorité Moyenne (Lots 19-28)](#-priorité-moyenne-lots-19-28)
- [🟢 Priorité Basse (Lots 29-35)](#-priorité-basse-lots-29-35)
- [📊 Tableau récapitulatif](#-tableau-récapitulatif)
- [🎯 Prochaines étapes recommandées](#-prochaines-étapes-recommandées)

---

## 🔥 Priorité Critique (Lots 1-10)

Fondations essentielles et obligations légales urgentes.

### Lot 1 : CRUD Entités Principales
**Estimation :** 8-10 jours | **Statut :** ⏳ Planifié

**Objectif :** Compléter les interfaces de gestion des entités principales.

**Fonctionnalités :**
- ✅ CRUD Contributeurs (complet avec avatar)
- ✅ CRUD Périodes d'emploi
- ✅ CRUD Projets (formulaire complet, onglets)
- ✅ CRUD Devis (sections, lignes, calculs)
- ⏳ Filtres avancés dans liste projets
- ⏳ Prévisualisation PDF des devis

**Impact :** Opérations quotidiennes, gestion administrative

---

### Lot 2 : Saisie des Temps ✅
**Estimation :** 5-7 jours | **Statut :** ✅ Terminé | **Réalisé :** Décembre 2025

**Objectif :** Interface complète de saisie et gestion des temps (timesheets).

**Réalisations :**
- ✅ Grille de saisie hebdomadaire avec navigation semaine
- ✅ Compteur de temps start/stop (RunningTimer)
- ✅ Sélection projet → tâche → sous-tâche en cascade
- ✅ Vue calendrier mensuel (templates/timesheet/calendar.html.twig)
- ✅ Interface "Mes temps" (templates/timesheet/my_time.html.twig)
- ✅ Export PDF des timesheets
- ✅ Consultation de tous les temps (templates/timesheet/all.html.twig)
- ✅ TimesheetController complet avec auto-save

**Impact :** Suivi temps réel, facturation précise, productivité

---

### Lot 3 : Dashboard Analytique ✅
**Estimation :** 7-10 jours | **Statut :** ✅ Terminé | **Réalisé :** Décembre 2025

**Objectif :** Tableau de bord KPIs complet avec worker de calcul.

**Réalisations :**
- ✅ Cartes KPIs principales (CA, Marge, Taux de marge, Projets actifs)
- ✅ Graphiques d'évolution temporelle (Chart.js)
- ✅ Répartition par type de projet (camembert)
- ✅ Top contributeurs (Top 5 par CA/marge)
- ✅ Filtres dynamiques (période personnalisée, année, mois, trimestre)
- ✅ Worker de recalcul asynchrone avec RecalculateMetricsMessage
- ✅ Scheduler automatique quotidien (AnalyticsScheduleProvider)
- ✅ Export Excel du dashboard (ExcelExportService)
- ✅ Analytics/DashboardController avec DashboardReadService
- ✅ Prédictions analytiques (Analytics/PredictionsController)

**Technique :**
- Modèle en étoile (FactProjectMetrics, FactStaffingMetrics, dimensions)
- Message `RecalculateMetricsMessage` + handler
- Service `DashboardReadService` avec fallback temps réel
- Commandes CLI : `app:calculate-metrics`, `app:metrics:dispatch`

**Impact :** Pilotage financier, aide à la décision stratégique

---

### Lot 4 : Gestion Compte Utilisateur
**Estimation :** 3-4 jours | **Statut :** ⏳ Planifié

**Objectif :** Permettre à chaque utilisateur de gérer ses informations personnelles et paramètres de sécurité.

**Fonctionnalités :**
- ✅ Page "Mon compte" avec onglets (Informations / Sécurité / Carrière)
- ✅ Informations personnelles (nom, prénom, email, téléphones, adresse)
- ✅ Upload avatar (affiché dans header)
- ✅ Changement de mot de passe
- ✅ Gestion 2FA (activer/désactiver, régénérer QR code)
- ⏳ Sessions actives (liste et révocation)
- ✅ Historique des périodes d'emploi (lecture seule)

**Impact :** Autonomie utilisateur, sécurité des comptes

---

### Lot 5 : Module de Facturation
**Estimation :** 10-12 jours | **Statut :** ⏳ Planifié

**Objectif :** Automatiser la génération et le suivi des factures.

**Fonctionnalités :**
- ⏳ Entité `Invoice` (numéro unique, statut, montants, échéances)
- ⏳ Génération automatique depuis devis signés (forfait) ou temps saisis (régie)
- ⏳ Échéancier de paiement (rappels automatiques)
- ⏳ Statuts : Brouillon, Envoyée, Payée, En retard, Annulée
- ⏳ Template PDF professionnel (mentions légales, TVA, IBAN)
- ⏳ Export comptable (CSV pour import logiciel compta)
- ⏳ Dashboard de trésorerie :
  - CA facturé vs CA encaissé
  - Prévisionnel de trésorerie (90j)
  - Factures en retard (alertes automatiques)
  - Délai moyen de paiement par client
- ⏳ Relances automatiques par email (J+30, J+45, J+60)

**Impact :** Trésorerie, automatisation administrative, suivi paiements

---

### Lot 6 : Conformité RGPD 🔴
**Estimation :** 35-37 jours | **Statut :** ⏳ Planifié | **Obligation légale depuis 2018**

**Objectif :** Mise en conformité avec le Règlement Général sur la Protection des Données.

**Contexte :**
- Obligation légale depuis mai 2018
- Sanctions jusqu'à 20M€ ou 4% du CA annuel mondial
- Différenciation concurrentielle

**Fonctionnalités :**

**27.1 Registre des activités de traitement (Art. 30)**
- ⏳ Entité `ProcessingActivity` (finalités, bases légales, durées)
- ⏳ Interface admin pour gérer le registre
- ⏳ Export PDF/Excel pour audit CNIL

**27.2 Droits des personnes (Art. 15-22)**
- ⏳ Droit d'accès (export JSON/PDF complet)
- ⏳ Droit de rectification (page "Mon compte")
- ⏳ Droit à l'effacement (suppression/anonymisation compte)
- ⏳ Droit à la portabilité (export JSON/CSV/XML)
- ⏳ Droit à la limitation (gel du traitement)
- ⏳ Droit d'opposition (opt-out analytics, cookies)
- ⏳ Formulaire de demande (`PrivacyRequest`)

**27.3 Politique de confidentialité (Art. 13-14)**
- ⏳ Page `/privacy` avec politique complète
- ⏳ Acceptation première connexion
- ⏳ Versionning et notification des mises à jour

**27.4 Gestion des consentements**
- ⏳ Entité `ConsentRecord`
- ⏳ Bannière de consentement (Tarteaucitron.js)
- ⏳ Opt-in par défaut pour cookies non essentiels

**27.5 Audit trail**
- ⏳ Entité `AuditLog` (actions sensibles)
- ⏳ Conservation 6 mois

**27.6 Violations de données (Art. 33-34)**
- ⏳ Entité `DataBreach`
- ⏳ Procédure notification CNIL sous 72h

**27.7 Purge automatique**
- ⏳ Commande `app:gdpr:purge` (quotidienne)
- ⏳ Suppression logs > 6 mois
- ⏳ Anonymisation comptes inactifs > 3 ans

**ROI :**
- Éviter sanctions CNIL (jusqu'à 20M€)
- Conformité pour appels d'offres
- Confiance clients et employés

**Documentation :** `docs/rgpd-compliance-feasibility.md`

---

### Lot 7 : Pages d'Erreur Personnalisées ✅
**Estimation :** 1 jour | **Statut :** ✅ Terminé | **Réalisé :** 23 décembre 2025

**Réalisations :**
- ✅ Pages d'erreur personnalisées (404, 403, 500, générique)
- ✅ Design cohérent avec le thème Skote
- ✅ Mise en scène humoristique de "Unit 404"
- ✅ Controller de test pour environnement dev (`/test-errors`)
- ✅ Documentation complète (`docs/error-pages.md`)
- ✅ Fallback générique pour toutes les autres erreurs

---

### Lot 8 : Améliorations UX/UI de Base
**Estimation :** 5-6 jours | **Statut :** ⏳ Planifié

**Objectif :** Améliorer l'expérience utilisateur globale.

**Fonctionnalités :**
- ⏳ Menu latéral adapté aux entités
- ⏳ Fil d'ariane sur toutes les pages
- ⏳ Recherche globale (projets, contributeurs, devis, clients)
- ⏳ Tableaux de données :
  - Pagination côté serveur
  - Tri multi-colonnes
  - Filtres avancés persistants
  - Actions en masse
  - Export CSV/Excel
- ⏳ Formulaires :
  - Validation temps réel (AJAX)
  - Champs dépendants (projet → tâches)
  - Sauvegarde automatique (brouillon)
- ⏳ Notifications in-app avec centre de notifications

**Impact :** Efficacité quotidienne, adoption utilisateurs

---

### Lot 9 : Cohérence UX/UI Globale 🔄
**Estimation :** 11.5 jours | **Statut :** 🔄 En cours (35%)

**Objectif :** Harmoniser l'expérience utilisateur sur toutes les pages.

**Avancement :**
- ✅ Sprint 1: Standardisation pages de liste (Client, Employment Period, Invoice)
- 🔄 Phase 3: Standardisation formulaires (5/15 formulaires terminés)
- ⏳ Phase 4: Création composants manquants (Status Badge, Empty State, Stats Card KPI)
- ⏳ Phase 5: Documentation Design System
- ⏳ Phase 6: Amélioration Filter Panel

**Fonctionnalités :**
- ⏳ Audit UX/UI complet
- ⏳ Standardisation des en-têtes (template réutilisable, breadcrumb)
- ⏳ Refonte menu latéral (retrait "Ajouter X", boutons dans listes uniquement)
- ⏳ Standardisation listes (filtres, actions, pagination)
- ⏳ Standardisation formulaires (layout, labels, boutons)
- ⏳ Components Twig réutilisables (`page_header`, `data_table`, `filter_panel`, `pagination`)
- ⏳ JavaScript actions en masse (`mass-actions.js`)
- ⏳ Documentation Design System

**Impact :** Cohérence visuelle, maintenabilité, productivité dev

---

### Lot 10 : Notifications & Alertes
**Estimation :** 4-5 jours | **Statut :** ⏳ Planifié

**Objectif :** Système de notifications complet pour les événements importants.

**Infrastructure :**
- ✅ Entités en place (`Notification`, `NotificationPreference`, `NotificationSetting`)
- ✅ Page d'index des notifications (lecture)
- ✅ Rappel hebdomadaire de saisie des temps (vendredi 12h)

**Déclencheurs à implémenter :**
- ⏳ Nouveau devis à signer
- ⏳ Devis gagné/perdu
- ⏳ Projet proche de son budget (80%, 90%, 100%, 110%)
- ⏳ Temps en attente de validation
- ⏳ Échéance de paiement proche
- ⏳ Seuil d'alerte KPI dépassé

**Canaux :**
- ⏳ Notifications in-app (base de données)
- ⏳ Emails (Symfony Mailer)
- ⏳ Optionnel : Webhook Slack/Discord

**Configuration :**
- ⏳ Préférences utilisateur (événements, canaux)
- ⏳ Configuration globale admin (seuils d'alerte)

**Impact :** Réactivité, prévention risques, communication équipe

---

## 🎯 Priorité Haute (Lots 11-18)

Dashboards, analytics et conformité future.

### Lot 11 : Dashboard Commercial & Analytics ✅
**Estimation :** 5-7 jours | **Statut :** ✅ Terminé | **Réalisé :** Décembre 2025

**Objectif :** Améliorer la visibilité sur les performances commerciales.

**Réalisations :**
- ✅ Taux de conversion commerciaux (devis signés vs perdus)
- ✅ KPIs : nombre de devis en attente, CA signé sur période
- ✅ Graphique d'évolution du CA signé (mensuelle)
- ✅ Filtres par année, utilisateur et rôle utilisateur
- ✅ SalesDashboardController complet (/sales-dashboard)
- ✅ Calculs de métriques via OrderRepository
- ✅ Export PDF des KPIs commerciaux
- ✅ Interface responsive avec graphiques Chart.js

**Impact :** Visibilité commerciale, aide à la décision

---

### Lot 11bis : Sprint Technique & Consolidation
**Estimation :** 10-14 jours | **Statut :** 🔄 En cours (40% - Phase 11bis.1 terminée)
**Début :** 28 décembre 2025

**Objectif :** Consolider les fondations techniques, améliorer la qualité et réduire la dette technique avant les gros chantiers.

**Contexte :**
- Dette technique accumulée lors des lots 1-11
- Risque d'accumulation de dette pendant les lots futurs
- Nécessité de consolider les bases avant RGPD et transformation SAAS

**Réalisations :**

**11bis.1 Audit & Documentation (2-3 jours)** ✅ **Terminé (100%)**
- ✅ Audit de la qualité du code actuel (PHPStan niveau 3 : 0 erreur, PHP CS Fixer : 0 violation)
- ✅ Correction violations Deptrac (9 → 0 violations)
- ✅ Identification des hotspots de dette technique (6 hotspots documentés)
- ✅ Audit de sécurité (OWASP Top 10 : score 6.5/10)
- ✅ Audit dépendances (composer audit : 0 vulnérabilité, npm audit : 0 vulnérabilité)
- ✅ Configuration CSP (Content Security Policy activée)
- ✅ Migration PHP 8.5 / Symfony 8.0 (projet déjà migré)
- ✅ Implémentation Symfony AI Bundle (3 sprints : sentiment, email, quote agents)
- 🔄 Documentation architecture (diagrammes à finaliser)
- ⏳ Revue des performances actuelles (profiling Blackfire - planifié)

**Documents générés :**
- `technical-audit-lot11bis-2025-12-28.md` (audit qualité/sécurité)
- `technical-debt-hotspots-2025-12-28.md` (6 hotspots priorisés)
- `security-audit-owasp-2025-12-27.md` (audit OWASP)
- `csp-configuration-2025-12-28.md` (configuration CSP)

**Travaux en cours :**

**11bis.2 Qualité & Tests (3-4 jours)** ⏳ **Planifié**
- ⏳ Augmentation couverture tests de 15% à 60% (focus entités critiques)
- ⏳ Tests unitaires Services métier (DashboardReadService, MetricsCalculationService)
- ⏳ Tests d'intégration Repositories (OrderRepository, ProjectRepository)
- ⏳ Tests fonctionnels Controllers critiques (timesheet, analytics)
- ⏳ Configuration Infection pour mutation testing

**11bis.3 Performance & Optimisation (2-3 jours)** ⏳ **Planifié**
- ⏳ Profiling Blackfire sur parcours critiques (dashboard, timesheet, projets)
- ⏳ Optimisation requêtes N+1 identifiées
- ⏳ Mise en place cache Redis (doctrine, HTTP)
- ⏳ Indexation base de données (analyse explain)
- ⏳ Optimisation chargement assets (lazy loading, code splitting)

**11bis.4 Sécurité (2-3 jours)** 🔄 **Partiellement complété**
- ✅ Audit OWASP Top 10 (score 6.5/10)
- ✅ Configuration CSP (Content-Security-Policy activée avec nelmio/security-bundle)
- ✅ Headers de sécurité configurés (X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
- ✅ Audit dépendances (0 vulnérabilité)
- ⏳ HSTS (à activer en production)
- ⏳ Rotation secrets et clés API (Symfony Secrets)
- ⏳ Revue permissions et voters
- ⏳ SRI (Subresource Integrity) pour CDN externes

**11bis.5 Infrastructure & DevOps (1-2 jours)** ⏳ **Planifié**
- ⏳ Optimisation images Docker (multi-stage builds)
- 🔄 Configuration CI/CD (GitHub Actions configuré, à optimiser)
- ⏳ Mise en place monitoring (logs centralisés, APM Sentry)
- ⏳ Documentation déploiement et rollback
- ⏳ Scripts de backup et restauration base de données

**État actuel (31 décembre 2025) :**
- **Progression globale** : 40% (~4j sur 10-14j estimés)
- **Phase 11bis.1** : ✅ 100% terminée
- **Phase 11bis.2** : ⏳ 0% (planifiée)
- **Phase 11bis.3** : ⏳ 0% (planifiée)
- **Phase 11bis.4** : 🔄 30% (CSP configurée, audit complété)
- **Phase 11bis.5** : ⏳ 0% (planifiée)

**Prochaines étapes :**
1. Finaliser documentation architecture (diagrammes Mermaid)
2. Profiling Blackfire des parcours critiques
3. Augmentation couverture tests à 60% (priorité #1)
4. Optimisations performance basées sur profiling
5. Activation HSTS en production

**ROI attendu :**
- Réduction incidents production : -30%
- Amélioration vélocité développement : +20%
- Réduction temps de réponse : -40%
- Confiance déploiements : +50%

**Dépendances :** Idéalement après Lots 2, 3, 7, 11, 12 (fondations en place) ✅

**Impact :** Réduction dette technique, qualité code, performances, sécurité

**Rapport détaillé :** `docs/11-reports/lot11bis-progress-2025-12-28.md`

---

### Lot 12 : Renommage Contributeur → Collaborateur ✅
**Estimation :** 1-2 jours | **Statut :** ✅ Terminé | **Réalisé :** Décembre 2025

**Objectif :** Harmoniser la terminologie dans toute l'application.

**Réalisations :**
- ✅ Renommage complet dans tous les templates (158 occurrences)
- ✅ 0 occurrence restante de "contributeur" dans les templates
- ✅ Labels de formulaires mis à jour
- ✅ Navigation et breadcrumbs harmonisés
- ✅ Entité `Contributor` conservée en base (pas de régression)
- ✅ URLs et routes conservées (contributeur_*)

**Impact :** Clarté terminologique, alignement métier

---

### Lot 13 : Liste des Projets - Filtres & KPIs Avancés
**Estimation :** 3-4 jours | **Statut :** ⏳ Planifié

**Objectif :** Enrichir la liste des projets avec des filtres avancés et des indicateurs financiers.

**Filtres supplémentaires :**
- ⏳ Projets ouverts et actifs entre 2 dates (année courante par défaut)
- ⏳ Type de projet (forfait/régie)
- ⏳ Statut (actif, terminé, archivé, en attente)
- ⏳ Technologie
- ⏳ Catégorie de service
- ⏳ Pagination: 20, 50, 100 résultats par page

**KPIs en entête de page (sur période filtrée) :**
- ⏳ Chiffre d'affaires total
- ⏳ Marge brute (€ et %) - Formule: `CA - (Achats + Dépenses)`
- ⏳ Taux journalier moyen réel (TJM réel)
- ⏳ Coût homme total
- ⏳ Marge nette (€ et %) - Formule: `(Marge brute - Coût homme) / CA * 100`
- ⏳ Somme totale des achats

**Impact :** Vision financière globale, filtrage avancé, aide à la décision

---

### Lot 14 : Détail Projet - Métriques & Graphiques
**Estimation :** 4-5 jours | **Statut :** ⏳ Planifié

**Objectif :** Enrichir la vue détaillée d'un projet avec des métriques et visualisations avancées.

**Encarts de chiffres :**
- ⏳ Temps passé / Temps total à passer (avec RAF - Reste À Faire)
- ⏳ Budget consommé / Budget total
- ⏳ Somme des coûts du projet
- ⏳ Marge brute en euros avec :
  - Tendance (↗ ↘)
  - Badge coloré selon performance :
    - 🟢 Vert: > 25%
    - 🟠 Orange: 15-25%
    - 🔴 Rouge: < 15%

**Graphiques :**

1. **Consommation du projet dans le temps** (semaines ou mois):
   - Ligne horizontale: Budget total
   - Courbe: Budget consommé (réel)
   - Courbe: Budget prévisionnel à consommer

2. **Répartition budgétaire** (donut):
   - Marge
   - Achats
   - Coût homme

**Impact :** Pilotage projet, anticipation dérapages, visibilité rentabilité

---

### Lot 15 : Rapports & Exports
**Estimation :** 6-7 jours | **Statut :** ⏳ Planifié

**Objectif :** Rapports professionnels pour direction et clients.

**Fonctionnalités :**
- ⏳ Rapport d'activité mensuel (par projet, client, BU)
- ⏳ Rapport financier (CA, marges, coûts, rentabilité)
- ⏳ Rapport contributeur (temps, projets, performance)
- ⏳ Rapport commercial (pipeline, taux de conversion)
- ⏳ Rapport devis actifs entre 2 dates
- ⏳ Templates personnalisables (logo, charte graphique)
- ⏳ Génération planifiée (cron) et envoi automatique
- ⏳ Export multi-format (PDF, Excel, CSV)

**Impact :** Communication direction, reporting clients

---

### Lot 16 : Facturation Électronique 🔴
**Estimation :** 25-27 jours | **Statut :** ⏳ Planifié | **Obligation légale septembre 2027**

**Objectif :** Conformité avec la réforme française de la facturation électronique.

**Contexte :**
- Obligation légale septembre 2027
- Format : Factur-X (PDF + XML structuré)
- PDP : Chorus Pro (gratuit)

**Fonctionnalités :**

**16.1 Génération de factures Factur-X**
- ⏳ Création automatique depuis devis signés (forfait) ou temps saisis (régie)
- ⏳ Génération PDF + XML CII (norme EN 16931)
- ⏳ Fusion hybride Factur-X (PDF lisible + données structurées)
- ⏳ Numérotation unique et chronologique (FAC-2025-001)

**16.2 Émission via Chorus Pro**
- ⏳ Intégration API Chorus Pro (PDP gratuite)
- ⏳ Envoi automatique au client et au PPF
- ⏳ Suivi du statut (émise, reçue, rejetée, acceptée)
- ⏳ Webhooks pour notifications temps réel

**16.3 Réception de factures fournisseurs**
- ⏳ Récupération automatique depuis Chorus Pro
- ⏳ Parsing XML et extraction des données
- ⏳ Enregistrement dans `Purchase` (achats)

**16.4 Archivage légal**
- ⏳ Conservation 10 ans (obligation fiscale)
- ⏳ Hash SHA-256 pour garantir l'intégrité
- ⏳ Export pour audit fiscal

**Technologies :**
- Bibliothèque : horstoeko/zugferd
- API : Chorus Pro (REST, certificat X.509)
- Formats : Factur-X (PDF + XML CII EN 16931)

**Coûts :** ~100€ HT/an (certificat X.509)

**Dépendances :** Lot 5 (Module de Facturation)

**Documentation :** `docs/esignature-einvoicing-feasibility.md`

---

### Lot 17 : Signature Électronique
**Estimation :** 10-11 jours | **Statut :** ⏳ Planifié

**Objectif :** Dématérialiser la signature des devis et contrats.

**Contexte :**
- Cadre légal : Règlement européen eIDAS
- Type : Signature Avancée (valeur juridique B2B)
- Fournisseur : Yousign (français, API complète)

**Fonctionnalités :**

**17.1 Signature de devis**
- ⏳ Envoi du devis au client par email avec lien sécurisé
- ⏳ Interface de signature en ligne (sans compte client)
- ⏳ Changement automatique du statut (`a_signer` → `signe`)
- ⏳ Archivage du PDF signé avec certificat
- ⏳ Notifications internes (commercial, chef de projet)

**17.2 Signature multi-parties (optionnel)**
- ⏳ Workflow d'approbation interne avant envoi
- ⏳ Signature côté client + signature côté agence

**17.3 Journal d'audit**
- ⏳ Traçabilité complète (IP, user-agent, timestamp)
- ⏳ Certificat de signature Yousign
- ⏳ Export du journal en cas de litige

**Workflow :**
1. Utilisateur clique sur "Envoyer pour signature"
2. Backend génère le PDF et appelle l'API Yousign
3. Yousign envoie un email au client
4. Client signe électroniquement
5. Yousign notifie HotOnes via webhook
6. Symfony met à jour le statut et télécharge le PDF signé
7. Génération automatique des tâches projet

**Sécurité :**
- Clé API Yousign dans `.env` (Symfony Secrets en prod)
- Validation HMAC des webhooks
- URL de signature à usage unique
- PDF signés hors web root

**Coûts :** ~27€ HT/mois (~324€ HT/an)

**ROI :**
- Gain de temps : 2-3h/mois
- Délai de signature : 3-5 jours → quelques heures
- Taux de conversion : +10-15%

**Dépendances :** Lot 1 (Prévisualisation PDF devis)

**Documentation :** `docs/esignature-einvoicing-feasibility.md`

---

### Lot 18 : API REST
**Estimation :** 8-10 jours | **Statut :** ⏳ Planifié

**Objectif :** Exposer une API REST pour intégrations externes.

**Endpoints :**
- ⏳ `/api/projects` (CRUD projets)
- ⏳ `/api/timesheets` (saisie/consultation temps)
- ⏳ `/api/contributors` (liste contributeurs)
- ⏳ `/api/orders` (devis)
- ⏳ `/api/metrics` (KPIs lecture seule)
- ⏳ `/api/users` (CRUD utilisateurs)
- ⏳ `/api/running-timer` (timer actif)
- ⏳ `/api/invoices` (factures)
- ⏳ `/api/clients` (clients)

**Sécurité :**
- ⏳ Authentification JWT (lexik/jwt-authentication-bundle)
- ⏳ Rate limiting (par client API)
- ⏳ Scopes/permissions par endpoint
- ⏳ Documentation OpenAPI/Swagger automatique

**SDKs :**
- ⏳ SDK JavaScript/TypeScript (npm package)
- ⏳ SDK Python (pip package)

**Impact :** Ouverture écosystème, intégrations tierces

---

## 🟡 Priorité Moyenne (Lots 19-28)

Analytics avancés, intégrations et transformation SAAS.

### Lot 19 : Analytics Prédictifs
**Estimation :** 12-15 jours | **Statut :** ⏳ Planifié

**Objectif :** Anticiper les risques et opportunités business.

**Fonctionnalités :**

**19.1 Forecasting CA**
- ⏳ Prédiction du CA sur 3/6/12 mois basée sur historique
- ⏳ Prise en compte de la saisonnalité
- ⏳ Comparaison prévisionnel vs réalisé

**19.2 Analyse des risques projet**
- ⏳ Détection automatique des projets à risque (dépassement budget, délais)
- ⏳ Score de santé par projet (vert/orange/rouge)
- ⏳ Alertes proactives pour les chefs de projet

**19.3 Prédiction de charge**
- ⏳ Anticipation des périodes de surcharge/sous-charge
- ⏳ Recommandations de recrutement basées sur le pipeline
- ⏳ Optimisation de l'allocation des ressources

**19.4 Analyse de rentabilité prédictive**
- ⏳ Estimation de la marge finale dès 30% de réalisation
- ⏳ Identification des dérives budgétaires précoces
- ⏳ Recommandations de correction (scope, staffing)

**Technologies :** Machine Learning (scikit-learn ou API externe)

**Dépendances :** Données historiques suffisantes (6-12 mois)

**Impact :** Anticipation risques, optimisation ressources

---

### Lot 20 : Dashboard RH & Talents
**Estimation :** 8-10 jours | **Statut :** ⏳ Planifié

**Objectif :** Piloter la gestion des ressources humaines.

**Fonctionnalités :**

**20.1 KPIs RH**
- ⏳ Turnover (taux de départ annuel)
- ⏳ Absentéisme (taux et évolution)
- ⏳ Ancienneté moyenne par profil
- ⏳ Pyramide des âges et des compétences

**20.2 Gestion des compétences**
- ⏳ Matrice compétences par contributeur (technologies + soft skills)
- ⏳ Niveaux : Débutant, Intermédiaire, Confirmé, Expert
- ⏳ Gap analysis (compétences requises vs disponibles)
- ⏳ Plan de formation automatique

**20.3 Revues annuelles**
- ⏳ Campagne d'évaluation (auto-évaluation + manager)
- ⏳ Objectifs individuels (SMART)
- ⏳ Historique des évaluations

**20.4 Onboarding**
- ⏳ Checklist d'intégration nouveau contributeur
- ⏳ Suivi des tâches d'onboarding
- ⏳ Formation initiale (parcours par profil)

**Impact :** Gestion talents, développement compétences

---

### Lot 21 : Intégrations Externes
**Estimation :** 15-20 jours | **Statut :** ⏳ Planifié

**Objectif :** Connecter HotOnes avec l'écosystème d'entreprise.

**Intégrations :**

**21.1 Jira / ClickUp / Notion**
- ⏳ Import automatique des tâches projet
- ⏳ Synchronisation bidirectionnelle (temps, statuts)
- ⏳ Mapping ProjectTask ↔ Issue

**21.2 Slack / Microsoft Teams**
- ⏳ Notifications d'événements (nouveau devis, validation temps)
- ⏳ Commandes slash (/hotones timesheet, /hotones stats)
- ⏳ Webhooks pour alertes personnalisées

**21.3 Google Calendar / Outlook**
- ⏳ Export planning → calendrier personnel
- ⏳ Import congés depuis calendrier RH
- ⏳ Synchronisation bidirectionnelle

**21.4 Logiciels comptables**
- ⏳ Export factures vers Sage, Cegid, QuickBooks
- ⏳ Format FEC (Fichier des Écritures Comptables)
- ⏳ Réconciliation automatique des paiements

**21.5 GitLab / GitHub**
- ⏳ Intégration commits → temps passés
- ⏳ Statistiques de productivité code
- ⏳ Lien projets HotOnes ↔ repositories

**Impact :** Productivité, centralisation données

---

### Lot 22 : Portail Client
**Estimation :** 12-15 jours | **Statut :** ⏳ Planifié

**Objectif :** Espace dédié pour les clients avec accès limité.

**Fonctionnalités :**

**22.1 Authentification séparée**
- ⏳ Compte client distinct des utilisateurs internes
- ⏳ Mot de passe + 2FA optionnel
- ⏳ Multi-utilisateurs par client (admin client)

**22.2 Dashboard client**
- ⏳ Projets en cours et terminés
- ⏳ Temps consommés vs budgetés
- ⏳ Factures et paiements
- ⏳ Documents partagés (livrables, rapports)

**22.3 Suivi de projet**
- ⏳ Avancement en temps réel
- ⏳ Timeline des jalons
- ⏳ Reporting automatique (hebdo/mensuel)

**22.4 Support & Tickets**
- ⏳ Création de tickets support
- ⏳ Suivi du statut (nouveau, en cours, résolu)
- ⏳ Base de connaissances (FAQ)

**22.5 Validation de livrables**
- ⏳ Upload de fichiers
- ⏳ Workflow d'approbation
- ⏳ Historique des versions

**Impact :** Satisfaction client, transparence, autonomie

---

### Lot 23 : Transformation SAAS Multi-Tenant ⭐
**Estimation :** 45-55 jours | **Statut :** ⏳ Planifié | **Stratégique**

**Objectif :** Transformer HotOnes en solution SAAS multi-sociétés avec isolation complète des données.

**Contexte stratégique :**
- Vision : Plusieurs sociétés sur la même instance avec isolation totale
- Modèle : 1 compte utilisateur = 1 société (Company)
- Organisation : Business Units au sein de chaque société
- Architecture : Single database avec tenant_id, isolation par Company

**Fonctionnalités principales :**

**23.1 Gestion multi-société (Company)**
- ⏳ Entité Company (slug unique, infos légales, configuration)
- ⏳ Authentification avec contexte Company (JWT avec claim `company_id`)
- ⏳ Isolation des données (ajout `company_id` sur 45 entités)
- ⏳ CompanyContext service pour scope automatique
- ⏳ Soft delete avec CASCADE pour isolation complète

**23.2 Business Units hiérarchiques**
- ⏳ Entité BusinessUnit (rattachement Company, hiérarchie parent/enfants)
- ⏳ Manager, objectifs annuels (CA, marge)
- ⏳ Rattachement contributeurs, projets, clients
- ⏳ Dashboards par BU
- ⏳ Permissions granulaires (Manager BU, Admin Company)

**23.3 Migration et compatibilité**
- ⏳ Création Company par défaut
- ⏳ Migration de toutes les données vers cette Company
- ⏳ Conservation de l'intégrité référentielle
- ⏳ Support multi-company optionnel (phase 2)

**23.4 Sécurité et isolation**
- ⏳ CompanyContext injecté dans tous les repositories
- ⏳ Protection au niveau base de données
- ⏳ Voters personnalisés (CompanyVoter, BusinessUnitVoter)
- ⏳ Tests d'isolation entre tenants

**Plan de migration (9 phases) :**
1. Préparation & Design (5-7j)
2. Database & Models (15-18j) - Modification des 45 entités
3. Authentication & Context (5-6j) - JWT, CompanyContext, Voters
4. Repository Scoping (10-12j) - Scoping de 36 repositories
5. Controllers & Services (8-10j)
6. API & Frontend (5-6j)
7. Business Units (4-5j)
8. Testing & Validation (7-8j)
9. Deployment & Monitoring (3-4j)

**Technologies :**
- Architecture : Single database + tenant_id
- Scoping : Explicit repository scoping
- Authentification : JWT avec company_id claim
- Isolation : CASCADE DELETE

**Impact :** Transformation business model, nouveau marché SAAS

**Documentation :** `docs/saas-multi-tenant-plan.md`

---

### Lot 24 : Business Units Post-SAAS
**Estimation :** 6-8 jours | **Statut :** ⏳ Planifié

**Objectif :** Fonctionnalités avancées des Business Units (post Lot 23).

**Note :** La structure de base des Business Units est créée dans le Lot 23. Ce lot couvre les fonctionnalités avancées.

**Fonctionnalités avancées :**

**24.1 Objectifs et suivi avancés**
- ⏳ Budget prévisionnel par BU (mensuel/trimestriel/annuel)
- ⏳ Alertes de dérive budgétaire automatiques
- ⏳ Comparaison performance entre BU similaires
- ⏳ Scoring de performance BU (vert/orange/rouge)

**24.2 Workflows inter-BU**
- ⏳ Transfert de contributeurs entre BU
- ⏳ Partage de ressources (contributeurs partagés)
- ⏳ Facturation inter-BU (si prestations internes)
- ⏳ Consolidation de projets multi-BU

**24.3 Analytics avancées**
- ⏳ Taux d'utilisation par BU
- ⏳ Rentabilité comparative
- ⏳ Évolution des effectifs par BU
- ⏳ Prédiction de charge par BU

**24.4 Gamification**
- ⏳ Classement des BU (CA, marge, satisfaction client)
- ⏳ Badges de performance (meilleure BU du mois)
- ⏳ Challenges inter-BU

**Dépendances :** Lot 23 (SAAS Multi-Tenant)

---

### Lot 25 : Workflow de Recrutement
**Estimation :** 10-12 jours | **Statut :** ⏳ Planifié

**Objectif :** Gérer le pipeline de recrutement des talents.

**Fonctionnalités :**

**25.1 Entité Candidate**
- ⏳ Coordonnées (nom, email, téléphone)
- ⏳ Upload CV et lettre de motivation
- ⏳ Profil métier cible (JobProfile)
- ⏳ Technologies et niveaux (self-assessment)
- ⏳ Prétentions salariales (k€/an)
- ⏳ Type de contrat (CDI, CDD, Alternance, Stage)
- ⏳ BU identifiée

**25.2 Pipeline de recrutement**
- ⏳ Étapes : Candidature, Présélection, Entretien RH, Entretien Technique, Entretien Direction, Offre, Embauché, Refusé
- ⏳ Vue Kanban avec drag & drop
- ⏳ Historique des interactions (appels, emails, entretiens)
- ⏳ Assignation des intervieweurs par étape

**25.3 Conversion en contributeur**
- ⏳ Bouton "Embaucher" crée un Contributor
- ⏳ Pré-remplissage depuis Candidate
- ⏳ Création automatique de la 1ère EmploymentPeriod
- ⏳ Conservation de l'historique de recrutement

**25.4 Statistiques recrutement**
- ⏳ Temps moyen par étape
- ⏳ Taux de conversion par étape
- ⏳ Sources de candidatures (LinkedIn, Cooptation, Indeed)
- ⏳ Coût du recrutement

**Impact :** Structuration recrutement, suivi candidats

---

### Lot 26 : Gestion Achats & Fournisseurs
**Estimation :** 6-8 jours | **Statut :** ⏳ Planifié

**Objectif :** Centraliser les achats et la relation fournisseurs.

**Fonctionnalités :**

**26.1 Entité Supplier (Fournisseur)**
- ⏳ Nom, coordonnées, SIRET, IBAN
- ⏳ Catégorie (Hébergement, Licences, Freelance, Matériel, Formation)
- ⏳ Conditions de paiement (30j, 45j, 60j)
- ⏳ Documents (contrats, factures)

**26.2 Entité Purchase (Achat)**
- ⏳ Rattachement projet/client (optionnel)
- ⏳ Fournisseur
- ⏳ Montant HT/TTC
- ⏳ Date achat et date paiement
- ⏳ Statut (À payer, Payé, En retard)
- ⏳ Catégorie et sous-catégorie

**26.3 Budgets d'achat**
- ⏳ Budget annuel par catégorie
- ⏳ Alertes de dépassement
- ⏳ Visualisation consommé vs budgeté

**26.4 Dashboard achats**
- ⏳ Répartition par catégorie (camembert)
- ⏳ Top 5 fournisseurs
- ⏳ Achats par projet
- ⏳ Prévisionnel de paiement (90j)

**Impact :** Contrôle des coûts, relation fournisseurs

---

### Lot 27 : Gestion Contrats Clients
**Estimation :** 8-10 jours | **Statut :** ⏳ Planifié

**Objectif :** Suivi avancé des contrats et engagements.

**Fonctionnalités :**

**27.1 Entité Contract**
- ⏳ Lien vers Order (contrat issu d'un devis signé)
- ⏳ Type : Forfait, Régie, Support, Maintenance, TMA
- ⏳ Dates début/fin, reconduction tacite
- ⏳ Conditions particulières (SLA, pénalités, bonus)
- ⏳ Documents attachés (contrat signé, avenants)

**27.2 SLA (Service Level Agreement)**
- ⏳ Temps de réponse garanti (ex: 4h ouvrées)
- ⏳ Taux de disponibilité (ex: 99.9%)
- ⏳ Pénalités en cas de non-respect
- ⏳ Suivi automatique et alertes

**27.3 Renouvellements**
- ⏳ Alertes avant échéance (J-90, J-60, J-30)
- ⏳ Workflow de renégociation
- ⏳ Historique des versions de contrat

**27.4 Dashboard contrats**
- ⏳ Contrats à renouveler (3 prochains mois)
- ⏳ Revenus récurrents (MRR, ARR)
- ⏳ Taux de rétention client
- ⏳ SLA compliance par client

**Impact :** Revenus récurrents, satisfaction client

---

### Lot 28 : Automatisation Avancée
**Estimation :** 6-8 jours | **Statut :** ⏳ Planifié

**Objectif :** Automatiser les tâches répétitives.

**Fonctionnalités :**

**28.1 Workflows automatisés**
- ⏳ Si projet > 90% budget → alerte chef de projet + manager
- ⏳ Si devis non signé après 30j → relance automatique
- ⏳ Si timesheet non validé après 7j → escalade manager
- ⏳ Si facture impayée > 45j → relance + alerte compta

**28.2 Templates de tâches**
- ⏳ Création automatique de tâches à la signature d'un devis
- ⏳ Duplication de structure de tâches entre projets similaires
- ⏳ Application de templates par type de projet (refonte, dev from scratch, TMA)

**28.3 Rapports automatiques**
- ⏳ Envoi hebdo du dashboard staffing aux managers
- ⏳ Rapport mensuel au directeur (CA, marge, projets à risque)
- ⏳ Rapport trimestriel aux clients (projets TMA/support)

**28.4 Actions planifiées**
- ⏳ Archivage automatique des projets terminés (après 1 an)
- ⏳ Nettoyage des brouillons non utilisés (après 3 mois)
- ⏳ Backup automatique de la base de données

**Technologies :** Symfony Messenger + Scheduler

**Impact :** Gain de temps, prévention risques

---

## 🟢 Priorité Basse (Lots 29-35)

Mobile, gamification et optimisations continues.

### Lot 29 : Application Mobile
**Estimation :** 20-25 jours | **Statut :** ⏳ Planifié

**Objectif :** Saisie de temps et consultation en mobilité.

**Fonctionnalités v1.0 (MVP) :**

**29.1 Authentification**
- ⏳ Login email/password
- ⏳ Biométrie (Face ID, Touch ID, empreinte digitale)
- ⏳ Session persistante

**29.2 Saisie de temps**
- ⏳ Interface simplifiée pour saisie rapide
- ⏳ Timer start/stop avec notifications
- ⏳ Saisie hors-ligne (synchronisation auto)
- ⏳ Historique de la semaine

**29.3 Consultation**
- ⏳ Planning personnel (vue semaine/mois)
- ⏳ Congés (solde, demande, approbation pour managers)
- ⏳ Notifications push (validation temps, nouveau projet)

**29.4 Scanner de notes de frais**
- ⏳ Photo de ticket
- ⏳ OCR pour extraction montant/date
- ⏳ Catégorisation automatique

**Technologies :**
- React Native (iOS + Android)
- Utilisation de l'API REST HotOnes
- Stockage local SQLite pour offline

**Impact :** Mobilité équipe, adoption accrue

---

### Lot 30 : PWA & Offline Mode
**Estimation :** 6-8 jours | **Statut :** ⏳ Planifié

**Objectif :** Version web progressive accessible hors-ligne.

**Fonctionnalités :**
- ⏳ Service Workers pour cache intelligent
- ⏳ Installation sur écran d'accueil (mobile & desktop)
- ⏳ Synchronisation en arrière-plan
- ⏳ Mode hors-ligne pour saisie de temps
- ⏳ Notifications push web
- ⏳ Responsive design optimisé mobile

**Impact :** Accessibilité, utilisation hors connexion

---

### Lot 31 : Gamification & Engagement
**Estimation :** 8-10 jours | **Statut :** ⏳ Planifié

**Objectif :** Motiver et engager les contributeurs.

**Fonctionnalités :**

**31.1 Système de badges**
- ⏳ Early Bird (1er à saisir ses temps de la semaine)
- ⏳ Perfectionist (saisie sans erreur pendant 1 mois)
- ⏳ Marathon Runner (3 mois sans absence)
- ⏳ Knowledge Sharer (5+ formations données)
- ⏳ Bug Hunter (signalement de bugs critiques)

**31.2 Classements**
- ⏳ Top contributeurs du mois (CA généré)
- ⏳ Top formateurs (heures de formation données)
- ⏳ Équipe la plus productive

**31.3 Progression de carrière**
- ⏳ Arbre de compétences visuel
- ⏳ Déblocage de nouveaux profils
- ⏳ Parcours de montée en compétence

**31.4 Récompenses**
- ⏳ Points d'expérience (XP)
- ⏳ Niveaux (Junior → Senior → Lead → Principal)
- ⏳ Récompenses déblocables (jours de télétravail bonus, formation payée)

**Impact :** Motivation équipe, engagement

---

### Lot 32 : Module Documentaire
**Estimation :** 10-12 jours | **Statut :** ⏳ Planifié

**Objectif :** Centraliser la documentation projet et entreprise.

**Fonctionnalités :**

**32.1 Bibliothèque documentaire**
- ⏳ Upload/download de fichiers
- ⏳ Organisation par projet/client
- ⏳ Gestion de versions
- ⏳ Recherche full-text dans les documents (PDF, Word, Excel)

**32.2 Templates de documents**
- ⏳ Cahier des charges type
- ⏳ Spécifications techniques
- ⏳ PV de réunion
- ⏳ Rapport de livraison

**32.3 Wiki interne**
- ⏳ Base de connaissances par technologie
- ⏳ Tutoriels et best practices
- ⏳ Onboarding docs
- ⏳ Changelog produit

**32.4 Gestion des accès**
- ⏳ Permissions par rôle
- ⏳ Documents confidentiels (compta, RH)
- ⏳ Partage externe sécurisé (lien temporaire)

**Technologies :** ElasticSearch pour recherche full-text

**Impact :** Partage connaissance, onboarding

---

### Lot 33 : Augmentation Couverture Tests
**Estimation :** 5-7 jours | **Statut :** ⏳ Planifié (progressif)

**Objectif :** Atteindre 80% de couverture de tests automatisés.

**Stratégie :**
- ⏳ Prioriser les tests sur les features critiques (facturation, timesheet, profitabilité)
- ⏳ Tests unitaires sur les services métier
- ⏳ Tests d'intégration sur les repositories
- ⏳ Tests fonctionnels sur les controllers critiques
- ⏳ Tests API sur les endpoints publics
- ⏳ Tests E2E sur les parcours critiques

**Outils :**
- PHPUnit pour tests unitaires/intégration
- Infection pour mutation testing
- Deptrac pour architecture
- Panther pour E2E

**Impact :** Qualité code, réduction bugs, confiance déploiements

---

### Lot 34 : Performance & Scalabilité
**Estimation :** 10-12 jours | **Statut :** ⏳ Planifié

**Objectif :** Optimiser les performances pour grosse volumétrie.

**Actions :**

**34.1 Cache stratégique**
- ⏳ Redis pour cache applicatif
- ⏳ Cache HTTP (Varnish ou Symfony HTTP Cache)
- ⏳ Cache de requêtes Doctrine

**34.2 Optimisation base de données**
- ⏳ Analyse et création d'index manquants
- ⏳ Partitionnement des tables de métriques
- ⏳ Archivage des données anciennes (> 3 ans)

**34.3 Pagination et lazy loading**
- ⏳ Pagination côté serveur sur tous les listings
- ⏳ Chargement lazy des graphiques (on-demand)
- ⏳ Infinite scroll sur timesheet

**34.4 Monitoring**
- ⏳ APM (Blackfire, New Relic, ou Datadog)
- ⏳ Alertes sur temps de réponse > 500ms
- ⏳ Dashboard de métriques techniques (CPU, RAM, queries/s)

**Impact :** Temps de réponse, capacité volumétrie

---

### Lot 35 : Migration PHP 8.5 / Symfony 8
**Estimation :** 8-12 jours | **Statut :** ⏳ Planifié

**Objectif :** Anticiper et préparer la migration vers les versions majeures.

**Contexte :**
- PHP 8.5 : Sortie prévue novembre 2025
- Symfony 8.0 : Sortie stable prévue novembre 2025
- Nécessité d'anticiper ces migrations

**Actions de préparation (en continu) :**

**35.1 Audit de compatibilité**
- ⏳ Revue des dépendances Composer et compatibilité PHP 8.5 / Symfony 8
- ⏳ Identification des features dépréciées dans Symfony 7.x
- ⏳ Test de l'application avec PHP 8.5 (mode strict_types)
- ⏳ Liste des breaking changes à anticiper

**35.2 Bonnes pratiques dès maintenant**
- ⏳ Éviter l'usage de fonctionnalités dépréciées de Symfony 7.x
- ⏳ Respecter les nouvelles conventions PHP 8.4+ (typed properties, readonly)
- ⏳ Tester régulièrement avec `composer outdated` et `symfony check:requirements`

**35.3 Migration PHP 8.5 (Q4 2025 / Q1 2026)**
- ⏳ Mise à jour de l'image Docker (PHP 8.5-fpm)
- ⏳ Tests de régression complets
- ⏳ Revue des extensions PHP
- ⏳ Mise à jour PHPStan, PHP CS Fixer, PHPUnit vers versions compatibles
- ⏳ Benchmark de performance (comparaison 8.4 vs 8.5)

**35.4 Migration Symfony 8.0 (Q1 2026)**
- ⏳ Mise à jour progressive : Symfony 7.3 → 7.4 (LTS) → 8.0 (stable)
- ⏳ Utilisation de l'outil `symfony upgrade`
- ⏳ Refactoring des deprecations Symfony 7.x
- ⏳ Mise à jour des bundles tiers (Doctrine, Twig)
- ⏳ Tests fonctionnels et E2E complets post-migration
- ⏳ Documentation des breaking changes rencontrés

**Planning recommandé :**
1. Maintenant - Q3 2025 : Veille et préparation (éviter les deprecations)
2. Q4 2025 : Tests avec versions RC de PHP 8.5 et Symfony 8
3. Q1 2026 : Migration effective après stabilisation des releases
4. Q2 2026 : Optimisations post-migration (nouvelles features PHP/Symfony)

**Risques identifiés :**
- ⚠️ Bundles tiers non compatibles immédiatement
- ⚠️ Breaking changes non documentés
- ⚠️ Régression de performance (rare mais possible)
- ⚠️ Incompatibilités d'extensions PHP (ex: Redis, APCu)

**Impact :** Modernité stack, sécurité, performances

---

## 📊 Tableau récapitulatif

### Par priorité

| Priorité | Lots | Nombre | Estimation totale |
|----------|------|--------|-------------------|
| 🔥 Critique | Lots 1-10 | 10 | 85-104 jours |
| 🎯 Haute | Lots 11-18 (incl. 11bis) | 9 | 81-101 jours |
| 🟡 Moyenne | Lots 19-28 | 10 | 128-160 jours |
| 🟢 Basse | Lots 29-35 | 7 | 67-88 jours |
| **TOTAL** | **36 lots** | **36** | **361-453 jours** |

### Lots par statut

| Statut | Nombre | Lots |
|--------|--------|------|
| ✅ Terminés | 5 | Lots 2, 3, 7, 11, 12 |
| 🔄 En cours | 1 | Lot 9 (35%) |
| ⏳ Planifiés | 29 | Tous les autres |

### Obligations légales 🔴

| Lot | Titre | Échéance | Estimation |
|-----|-------|----------|------------|
| Lot 6 | Conformité RGPD | **Depuis 2018** | 35-37 jours |
| Lot 16 | Facturation Électronique | **Sept 2027** | 25-27 jours |

### Lots stratégiques ⭐

| Lot | Titre | Impact | Estimation |
|-----|-------|--------|------------|
| Lot 23 | Transformation SAAS Multi-Tenant | Nouveau business model | 45-55 jours |
| Lot 19 | Analytics Prédictifs | Anticipation risques | 12-15 jours |

---

## 🎯 Prochaines étapes recommandées

### Court terme (1-3 mois)

**Phase 1 : Finaliser les fondations** ✅ **Terminée à 80%**
- ✅ **Lot 2** : Saisie des Temps - Terminé
- ✅ **Lot 3** : Dashboard Analytique - Terminé
- ✅ **Lot 11** : Dashboard Commercial - Terminé
- ✅ **Lot 12** : Renommage Collaborateur - Terminé
- 🔄 **Lot 9** : Finaliser Cohérence UX/UI (65% restant) - 7-8 jours

**Reste à faire Phase 1 :** 7-8 jours (~1-2 semaines)

**Phase 1bis : Consolidation technique (RECOMMANDÉ)**
1. **Lot 11bis** : Sprint Technique & Consolidation - 10-14 jours (~2-3 semaines)

**Pourquoi prioriser la consolidation technique ?**
- Réduire la dette technique accumulée pendant les lots 1-12
- Améliorer la qualité et les performances avant les gros chantiers (RGPD, SAAS)
- Sécuriser l'application (OWASP Top 10)
- Augmenter la couverture de tests (objectif 60%)
- ROI immédiat : -30% incidents, +20% vélocité, -40% temps de réponse

**Phase 2 : Obligations légales urgentes**
1. **Lot 6** : Conformité RGPD (URGENT) - 35-37 jours (~7-8 semaines)

**Pourquoi prioriser RGPD ?**
- Obligation légale depuis 2018 (risque de contrôle CNIL)
- Sanctions jusqu'à 20M€ ou 4% du CA
- Clause obligatoire dans de nombreux appels d'offres
- Différenciation concurrentielle

---

### Moyen terme (3-6 mois)

**Phase 3 : Dashboards & Analytics**
1. **Lot 11** : Dashboard Commercial - 5-7 jours
2. **Lot 13** : Liste Projets KPIs - 3-4 jours
3. **Lot 14** : Détail Projet Graphiques - 4-5 jours
4. **Lot 5** : Module Facturation - 10-12 jours

**Total Phase 3 :** 22-28 jours (~4-6 semaines)

**Phase 4 : Professionnalisation**
1. **Lot 15** : Rapports & Exports - 6-7 jours
2. **Lot 17** : Signature Électronique - 10-11 jours
3. **Lot 18** : API REST - 8-10 jours

**Total Phase 4 :** 24-28 jours (~5-6 semaines)

---

### Long terme (6-18 mois)

**Phase 5 : Conformité future & Prédictif**
1. **Lot 16** : Facturation Électronique (obligation sept 2027) - 25-27 jours
2. **Lot 19** : Analytics Prédictifs - 12-15 jours
3. **Lot 20** : Dashboard RH & Talents - 8-10 jours

**Phase 6 : Transformation SAAS (stratégique)**
1. **Lot 23** : Transformation SAAS Multi-Tenant - 45-55 jours (~9-11 semaines)
2. **Lot 24** : Business Units Post-SAAS - 6-8 jours

**Phase 7 : Intégrations & Mobile**
1. **Lot 21** : Intégrations Externes - 15-20 jours
2. **Lot 22** : Portail Client - 12-15 jours
3. **Lot 29** : Application Mobile - 20-25 jours
4. **Lot 30** : PWA & Offline - 6-8 jours

**Phase 8 : Qualité & Performance (continue)**
1. **Lot 33** : Augmentation Couverture Tests - 5-7 jours (progressif)
2. **Lot 34** : Performance & Scalabilité - 10-12 jours
3. **Lot 35** : Migration PHP 8.5 / Symfony 8 - 8-12 jours

---

## 📋 Planning prévisionnel 2025-2026

### Q1 2025 (Janvier - Mars)
- ✅ Lot 2 (Saisie des Temps) - Terminé
- ✅ Lot 3 (Dashboard Analytique) - Terminé
- ✅ Lot 11 (Dashboard Commercial) - Terminé
- ✅ Lot 12 (Renommage Collaborateur) - Terminé
- 🔄 Finaliser Lot 9 (UX/UI Globale) - En cours (35%)
- **Lot 11bis (Sprint Technique & Consolidation) - RECOMMANDÉ**
- **Démarrer Lot 6 (RGPD)**

### Q2 2025 (Avril - Juin)
- **Finaliser Lot 6 (RGPD)**
- Lot 1 (CRUD Entités Principales)
- Lot 13 (Liste Projets KPIs)
- Lot 14 (Détail Projet)
- Lot 5 (Module Facturation)

### Q3 2025 (Juillet - Septembre)
- Lot 15 (Rapports & Exports)
- Lot 17 (Signature Électronique)
- Lot 18 (API REST)
- Lot 19 (Analytics Prédictifs)

### Q4 2025 (Octobre - Décembre)
- Lot 20 (Dashboard RH)
- Lot 21 (Intégrations Externes)
- Lot 35 (Migration PHP 8.5 / Symfony 8)

### Q1 2026 (Janvier - Mars)
- **Lot 16 (Facturation Électronique)** - anticiper obligation sept 2027
- Lot 22 (Portail Client)

### Q2-Q4 2026
- **Lot 23 (Transformation SAAS Multi-Tenant)** - Stratégique
- Lot 24 (Business Units Post-SAAS)
- Lots 25-28 (Recrutement, Achats, Contrats, Automatisation)
- Lots 29-32 (Mobile, PWA, Gamification, Documentaire)
- Lots 33-34 (Tests, Performance)

---

## 🎯 Axes stratégiques prioritaires

1. **Consolidation technique** : Sprint technique (Lot 11bis) avant les gros chantiers
2. **Conformité légale** : RGPD (urgent), e-facturation (anticiper 2027)
3. **Fondations solides** : Saisie temps, analytics, facturation
4. **Professionnalisation** : Dashboards, rapports, signature électronique
5. **Transformation SAAS** : Multi-tenant pour nouveau business model
6. **Automatisation** : Réduire les tâches manuelles répétitives
7. **Analytics & Prédictif** : Anticiper les risques et opportunités
8. **Ouverture** : API REST, intégrations, portail client
9. **Qualité & Performance** : Tests, optimisations, modernisation stack

---

## 📝 Notes importantes

### Estimations
- Données pour **1 développeur full-stack Symfony expérimenté**
- Tests **inclus** dans les estimations
- Documentation technique à **maintenir au fil de l'eau**
- Prévoir **revues de code** et QA entre chaque lot

### Flexibilité
- Possibilité de **paralléliser** certains lots
- **Prioriser selon le ROI business** : conformité > fondations > analytics > mobile
- Collecter du **feedback utilisateur** après chaque phase pour ajuster

### Révisions
- **Prochaine revue** : Fin Q1 2025 (mars 2025)
- Ajuster les priorités selon l'évolution des besoins
- Réévaluer les estimations après chaque lot majeur

---

## 📚 Documentation de référence

### Documents archivés
- `docs/roadmap-2025.md` - Roadmap stratégique long terme (archive)
- `docs/roadmap-lots.md` - Documentation technique détaillée (archive)

### Documents actifs
- `docs/status.md` - État d'avancement détaillé
- `docs/features.md` - Fonctionnalités actuelles
- `docs/execution-plan-2025.md` - Plan d'exécution 2025
- `docs/rgpd-compliance-feasibility.md` - Étude RGPD
- `docs/esignature-einvoicing-feasibility.md` - Étude signature & e-facturation
- `docs/saas-multi-tenant-plan.md` - Plan transformation SAAS
- `WARP.md` - Index principal de la documentation

---

**Dernière mise à jour :** 27 décembre 2025
**Version :** 1.2 (ajout Lot 11bis - Sprint Technique & Consolidation)
**Prochaine revue :** Mars 2025
