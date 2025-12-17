# 🗓️ Roadmap HotOnes 2025-2026 - Organisation en Sprints

> Document créé le 17 décembre 2025
>
> Ce document consolide la roadmap complète en sprints de 2 semaines avec estimations de temps et planning détaillé.

## 📊 État des lieux

### ✅ Ce qui est terminé (Sprint 1 - Fondations)
- **Lot 1.1** : Gestion des Contributeurs (CRUD, avatar, historique)
- **Lot 1.2** : Gestion des Périodes d'Emploi (avec validation)
- **Lot 1.3** : Gestion des Projets (90% - manque filtres avancés)
- **Lot 1.4** : Gestion des Devis (90% - manque prévisualisation PDF)
- **Lot 4** : Gestion de Compte Utilisateur (95% - manque sessions actives)
- **Dashboard Commercial** : Suivi des ventes et CA
- **Dashboard Staffing** : Taux de staffing et TACE

### 🔲 Ce qui reste à faire

#### Priorité CRITIQUE (Obligations légales)
- **Lot 27** : Conformité RGPD (35-37j) 🔴 **URGENT** - Obligation légale depuis 2018
- **Lot 25** : Facturation Électronique (25-27j) 🔴 Obligation légale septembre 2027

#### Priorité HAUTE (Fonctionnalités critiques business)
- **Lot 2** : Saisie des Temps (5-7j) - Bloque rentabilité réelle
- **Lot 3** : Dashboard Analytique (7-10j) - Worker + KPIs
- **Lot 9** : Module de Facturation (10-12j) - Critique pour trésorerie
- **Lot 1.3** : Projets - Filtres avancés (2-3j)
- **Lot 1.4** : Devis - Prévisualisation PDF (3-4j)
- **Lot 26** : Signature Électronique (10-11j)

#### Priorité MOYENNE
- **Lot 5** : Améliorations UX/UI (5-6j)
- **Lot 7** : Rapports & Exports (6-7j)
- **Lot 8** : API REST (8-10j)
- **Lot 6** : Notifications (4-5j)

#### Priorité TRANSFORMATIONNELLE
- **Lot 17.5** : Transformation SAAS Multi-Tenant (45-55j) 🔴 **STRATÉGIQUE**

---

## 🎯 Organisation en Sprints (2025-2026)

### 📅 Planning global

**Hypothèses** :
- 1 développeur full-stack Symfony expérimenté
- Sprints de 2 semaines (10 jours ouvrés)
- Vélocité cible : 8-9 jours de dev par sprint (réserve pour imprévus)
- Tests inclus dans les estimations

---

## Phase 1 : Consolidation & Opérationnel (Q1 2025) - 6 sprints

### Sprint 2 : Finalisation Projets & Devis (2 semaines)
**Objectif** : Compléter les fonctionnalités de base pour projets et devis

**Tâches** :
- [ ] **Lot 1.3** : Filtres avancés dans le listing projets (2j)
  - Filtres : statut, type, technologies, dates, contributeurs
  - Recherche full-text sur nom/client/description
  - Sauvegarde des filtres en session
- [ ] **Lot 1.4** : Prévisualisation PDF du devis (3-4j)
  - Template PDF personnalisable (logo, charte graphique)
  - Génération avec DomPDF ou Snappy
  - Calcul automatique HT/TTC
  - Prévisualisation avant envoi
  - Historique des versions
- [ ] **Tests** : Tests fonctionnels des filtres et génération PDF (1j)
- [ ] Documentation mise à jour (0.5j)

**Estimation** : 6.5-7.5 jours
**Période** : S1-S2 janvier 2025

---

### Sprint 3 : Saisie des Temps (2 semaines)
**Objectif** : Interface complète de saisie et gestion des temps

**Tâches** :
- [ ] Grille de saisie hebdomadaire avec auto-save (1.5j)
- [ ] Amélioration du compteur de temps (start/stop, persistance) (1j)
- [ ] Sélection projet → tâche en cascade (UX optimisée) (0.5j)
- [ ] Vue calendrier mensuel avec saisie rapide (1j)
- [ ] Copie de semaine / duplication de temps (0.5j)
- [ ] Workflow de validation hiérarchique (chef de projet → manager) (1.5j)
- [ ] Récapitulatif mensuel par contributeur et par projet (1j)
- [ ] Export Excel/PDF des timesheets (0.5j)
- [ ] Tests E2E du parcours complet saisie → validation (1j)

**Estimation** : 8.5 jours
**Période** : S3-S4 janvier 2025

---

### Sprint 4 : Dashboard Analytique - Partie 1 (2 semaines)
**Objectif** : Interface KPIs avec filtres dynamiques

**Tâches** :
- [ ] Page principale `/analytics/dashboard` (0.5j)
- [ ] Cartes KPIs principales (CA, Marge, Taux, Projets actifs) (1.5j)
- [ ] Graphiques d'évolution temporelle (Chart.js) (2j)
  - Évolution mensuelle du CA
  - Évolution de la marge
  - Projets actifs/terminés
- [ ] Répartition par type de projet (camembert) (1j)
- [ ] Top contributeurs (Top 5 par CA/marge) (1j)
- [ ] Filtres dynamiques (période, type, chef de projet, commercial, technologies) (2j)
- [ ] Tests fonctionnels dashboard (1j)

**Estimation** : 9 jours
**Période** : S5-S6 février 2025

---

### Sprint 5 : Dashboard Analytique - Partie 2 (Worker) (2 semaines)
**Objectif** : Worker de recalcul asynchrone et exports

**Tâches** :
- [ ] Service `MetricsCalculationService` (calcul des KPIs) (2j)
- [ ] Handler `RecalculateMetricsMessageHandler` (traitement asynchrone) (1j)
- [ ] Commande CLI `app:calculate-metrics` (0.5j)
- [ ] Bouton "Recalculer" dans l'interface admin (0.5j)
- [ ] Configuration Symfony Scheduler (cron automatique quotidien) (1j)
- [ ] Page admin `/admin/scheduler` pour gérer les tâches planifiées (1j)
- [ ] Export PDF du dashboard (1.5j)
- [ ] Export Excel des données (1.5j)
- [ ] Tests de performance des agrégations (1j)

**Estimation** : 10 jours
**Période** : S7-S8 février 2025

---

### Sprint 6 : Module de Facturation - Partie 1 (2 semaines)
**Objectif** : Entités, génération automatique, templates PDF

**Tâches** :
- [ ] Entité `Invoice` et `InvoiceLine` (1j)
- [ ] Migration et repository (0.5j)
- [ ] Génération automatique depuis devis signés (forfait) (1.5j)
- [ ] Génération automatique depuis temps saisis (régie) (1.5j)
- [ ] Échéancier de paiement (rappels automatiques) (1j)
- [ ] Statuts : Brouillon, Envoyée, Payée, En retard, Annulée (0.5j)
- [ ] Template PDF professionnel (mentions légales, TVA, IBAN) (2j)
- [ ] Tests unitaires et fonctionnels (1j)

**Estimation** : 9 jours
**Période** : S9-S10 mars 2025

---

### Sprint 7 : Module de Facturation - Partie 2 (2 semaines)
**Objectif** : Dashboard de trésorerie et export comptable

**Tâches** :
- [ ] Dashboard de trésorerie (2j)
  - CA facturé vs CA encaissé
  - Prévisionnel de trésorerie (90j)
  - Factures en retard (alertes automatiques)
  - Délai moyen de paiement par client
- [ ] Relances automatiques par email (J+30, J+45, J+60) (1.5j)
- [ ] Export comptable (CSV pour import logiciel compta) (1j)
- [ ] CRUD complet des factures (liste, création, édition, suppression) (2j)
- [ ] Interface de suivi des paiements (1j)
- [ ] Tests de génération PDF et calculs de trésorerie (1j)
- [ ] Documentation (0.5j)

**Estimation** : 9 jours
**Période** : S11-S12 mars 2025

---

## Phase 2 : Conformité Légale CRITIQUE (Q2 2025) - 8 sprints

### Sprint 8-11 : Conformité RGPD (8 semaines) 🔴 **URGENT**
**Objectif** : Mise en conformité complète avec le RGPD

#### Sprint 8 : Registre des traitements & Droits des personnes - Partie 1 (2 semaines)
**Tâches** :
- [ ] Entité `ProcessingActivity` (registre des traitements) (1j)
- [ ] Interface admin `/admin/gdpr/register` pour gérer le registre (1.5j)
- [ ] Export PDF/Excel du registre pour audit CNIL (1j)
- [ ] Entité `PrivacyRequest` (demandes d'exercice de droits) (0.5j)
- [ ] Formulaire `/privacy/request` pour demandes (1j)
- [ ] Workflow de traitement (pending, in_progress, completed, rejected) (1j)
- [ ] Service `GdprService` - Export des données utilisateur (JSON/PDF) (2j)
- [ ] Tests unitaires (1j)

**Estimation** : 9 jours
**Période** : S13-S14 avril 2025

---

#### Sprint 9 : Droits des personnes - Partie 2 (2 semaines)
**Tâches** :
- [ ] Droit d'accès : Bouton "Télécharger mes données" (1j)
- [ ] Droit de rectification : Modification dans "Mon compte" (déjà fait, validation) (0.5j)
- [ ] Droit à l'effacement : Bouton "Supprimer mon compte" avec anonymisation (2j)
- [ ] Droit à la portabilité : Export JSON/CSV/XML (1j)
- [ ] Droit à la limitation : Statut `User.dataProcessingLimited` (1j)
- [ ] Droit d'opposition : Opt-out analytics, cookies (1.5j)
- [ ] Interface admin de gestion des demandes de droits (1.5j)
- [ ] Tests fonctionnels workflows (1j)

**Estimation** : 9.5 jours
**Période** : S15-S16 avril 2025

---

#### Sprint 10 : Politique de confidentialité & Consentements (2 semaines)
**Tâches** :
- [ ] Page `/privacy` avec politique de confidentialité complète (1.5j)
  - Finalités, bases légales, durées de conservation, droits, contact RGPD
- [ ] Acceptation lors de la première connexion (checkbox) (0.5j)
- [ ] Versionning de la politique (notification mise à jour) (1j)
- [ ] Entité `ConsentRecord` (analytics, cookies, newsletter) (0.5j)
- [ ] Bannière de consentement (Tarteaucitron.js, open-source français) (2j)
- [ ] Opt-in par défaut pour cookies non essentiels (1j)
- [ ] Page de gestion des consentements dans "Mon compte" (1j)
- [ ] Remplacement Google Analytics par Matomo (auto-hébergé) (2j)
- [ ] Tests fonctionnels (0.5j)

**Estimation** : 10 jours
**Période** : S17-S18 mai 2025

---

#### Sprint 11 : Audit trail, Violations & Purge automatique (2 semaines)
**Tâches** :
- [ ] Entité `AuditLog` (journalisation des actions sensibles) (0.5j)
- [ ] Service `AuditLogService` avec listeners Doctrine (2j)
- [ ] Interface admin `/admin/gdpr/audit` pour consultation (1.5j)
- [ ] Filtres : utilisateur, action, date, entité (0.5j)
- [ ] Entité `DataBreach` (violations de données) (0.5j)
- [ ] Formulaire de déclaration de violation (admin) (1j)
- [ ] Procédure de notification CNIL sous 72h (workflow) (1.5j)
- [ ] Commande `app:gdpr:purge` (purge automatique quotidienne) (2j)
  - Suppression logs > 6 mois
  - Anonymisation comptes inactifs > 3 ans
  - Suppression données RH après départ + 5 ans
- [ ] Tests de sécurité et purge (1j)

**Estimation** : 10.5 jours
**Période** : S19-S20 mai 2025

---

### Sprint 12 : Améliorations UX/UI (2 semaines)
**Objectif** : Améliorer l'expérience utilisateur globale

**Tâches** :
- [ ] Menu latéral adapté aux entités (retrait "Ajouter X") (1j)
- [ ] Fil d'ariane sur toutes les pages (1j)
- [ ] Recherche globale (projets, contributeurs, devis, clients) (2j)
- [ ] Tableaux de données standardisés (1.5j)
  - Pagination côté serveur
  - Tri multi-colonnes
  - Filtres avancés persistants
- [ ] Actions en masse (sélection multiple, suppression) (1.5j)
- [ ] Export CSV/Excel sur tous les tableaux (1j)
- [ ] Formulaires avec validation temps réel (AJAX) (1j)
- [ ] Tests E2E navigation, tests accessibilité WCAG (1j)

**Estimation** : 10 jours
**Période** : S21-S22 juin 2025

---

### Sprint 13 : Notifications & Alertes (2 semaines)
**Objectif** : Système de notifications complet

**Tâches** :
- [ ] Déclencheurs d'événements (2j)
  - Nouveau devis à signer
  - Devis gagné/perdu
  - Projet proche de son budget (80%, 90%, 100%, 110%)
  - Temps en attente de validation
  - Échéance de paiement proche
  - Seuil d'alerte KPI dépassé
- [ ] Routage des notifications (in-app, email, webhook) (1.5j)
- [ ] Notifications in-app (base de données) (1j)
- [ ] Emails (Symfony Mailer avec templates) (1.5j)
- [ ] Webhook Slack/Discord (optionnel) (1j)
- [ ] Préférences utilisateur (quels événements, quels canaux) (1.5j)
- [ ] Configuration globale admin (seuils d'alerte) (1j)
- [ ] Tests fonctionnels envoi notifications (0.5j)

**Estimation** : 10 jours
**Période** : S23-S24 juin 2025

---

### Sprint 14-15 : Signature Électronique (4 semaines)
**Objectif** : Dématérialiser la signature des devis et contrats

#### Sprint 14 : Intégration Yousign & Workflow de base (2 semaines)
**Tâches** :
- [ ] Étude de l'API Yousign et setup du compte (0.5j)
- [ ] Configuration Symfony HttpClient pour Yousign (0.5j)
- [ ] Service `YousignProvider` (API client) (1.5j)
- [ ] Service `OrderSignatureService` (orchestration) (1j)
- [ ] Modification entité `Order` (yousignProcedureId, signedAt, etc.) (0.5j)
- [ ] Entité `SignatureAudit` (audit trail complet) (0.5j)
- [ ] Workflow de signature de devis (2j)
  - Bouton "Envoyer pour signature"
  - Génération PDF et appel API Yousign
  - Envoi email au client
- [ ] Webhook handler pour notifications Yousign (1.5j)
- [ ] Validation HMAC des webhooks (0.5j)
- [ ] Tests d'intégration API Yousign (mock) (1j)

**Estimation** : 9.5 jours
**Période** : S25-S26 juillet 2025

---

#### Sprint 15 : Fonctionnalités avancées & Sécurité (2 semaines)
**Tâches** :
- [ ] Téléchargement automatique du PDF signé (1j)
- [ ] Mise à jour automatique du statut devis (`a_signer` → `signe`) (0.5j)
- [ ] Génération automatique des tâches projet (workflow existant, validation) (0.5j)
- [ ] Interface admin de gestion des signatures (liste, statuts, audit) (1.5j)
- [ ] Accès restreint aux PDF signés (ROLE_ADMIN, ROLE_MANAGER, créateur) (1j)
- [ ] Stockage sécurisé (hors web root) (0.5j)
- [ ] Journal d'audit complet (IP, user-agent, timestamp) (0.5j)
- [ ] Interface de signature multi-parties (optionnel, phase 2) (1j)
- [ ] Tests fonctionnels workflow complet (1j)
- [ ] Tests de sécurité webhook et accès documents (1j)
- [ ] Documentation complète (0.5j)

**Estimation** : 9 jours
**Période** : S27-S28 juillet 2025

---

## Phase 3 : Fonctionnalités Avancées (Q3-Q4 2025) - 4 sprints

### Sprint 16 : Rapports & Exports (2 semaines)
**Objectif** : Rapports professionnels pour direction et clients

**Tâches** :
- [ ] Service de génération de rapports (base) (1j)
- [ ] Rapport d'activité mensuel (par projet, client, BU) (1.5j)
- [ ] Rapport financier (CA, marges, coûts, rentabilité) (1.5j)
- [ ] Rapport contributeur (temps, projets, performance) (1j)
- [ ] Rapport commercial (pipeline, taux de conversion) (1j)
- [ ] Rapport devis actifs entre 2 dates (0.5j)
- [ ] Templates personnalisables (logo, charte graphique) (1.5j)
- [ ] Export multi-format (PDF avec DomPDF, Excel avec PhpSpreadsheet) (1.5j)
- [ ] Tests génération PDF/Excel et contenu rapports (0.5j)

**Estimation** : 10 jours
**Période** : S29-S30 août 2025

---

### Sprint 17-18 : API REST (4 semaines)
**Objectif** : API complète pour intégrations externes

#### Sprint 17 : Setup API Platform & Endpoints de base (2 semaines)
**Tâches** :
- [ ] Installation et configuration API Platform (0.5j)
- [ ] Configuration JWT (lexik/jwt-authentication-bundle) (1j)
- [ ] Rate limiting (configuration) (0.5j)
- [ ] Endpoint `/api/projects` (CRUD projets) (1.5j)
- [ ] Endpoint `/api/timesheets` (saisie/consultation temps) (1.5j)
- [ ] Endpoint `/api/contributors` (liste contributeurs) (1j)
- [ ] Endpoint `/api/orders` (devis) (1j)
- [ ] Tests API (PHPUnit + API Platform Test Client) (1.5j)
- [ ] Documentation OpenAPI/Swagger automatique (0.5j)

**Estimation** : 9 jours
**Période** : S31-S32 septembre 2025

---

#### Sprint 18 : Endpoints avancés & Sécurité (2 semaines)
**Tâches** :
- [ ] Endpoint `/api/metrics` (KPIs lecture seule) (1j)
- [ ] Endpoint `/api/users` (CRUD utilisateurs) (1j)
- [ ] Endpoint `/api/running-timer` (timer actif) (1j)
- [ ] Endpoint `/api/invoices` (factures) (1j)
- [ ] Endpoint `/api/clients` (clients) (0.5j)
- [ ] Endpoint `/api/vacation-requests` (demandes de congés) (1j)
- [ ] Scopes/permissions par endpoint (API Platform voters) (2j)
- [ ] Tests de sécurité (JWT, permissions, rate limiting) (1.5j)
- [ ] Documentation complète avec exemples (0.5j)

**Estimation** : 9.5 jours
**Période** : S33-S34 septembre 2025

---

## Phase 4 : Conformité Légale - Facturation Électronique (Q1 2026) - 6 sprints

### Sprint 19-24 : Facturation Électronique Factur-X / Chorus Pro (12 semaines) 🔴
**Objectif** : Conformité avec la réforme française de la facturation électronique

#### Sprint 19 : Setup & Génération Factur-X (2 semaines)
**Tâches** :
- [ ] Installation bibliothèque horstoeko/zugferd (0.5j)
- [ ] Étude de la norme EN 16931 (1j)
- [ ] Service `FacturXGeneratorService` (génération PDF + XML CII) (2.5j)
- [ ] Fusion hybride Factur-X (PDF lisible + données structurées) (1.5j)
- [ ] Numérotation unique et chronologique (FAC-2025-001) (1j)
- [ ] Mentions légales complètes (SIREN, TVA, conditions de paiement) (1j)
- [ ] Modification entité `Invoice` (ajout champs Factur-X) (0.5j)
- [ ] Tests unitaires génération Factur-X (1j)
- [ ] Tests de conformité EN 16931 (validation XML) (1j)

**Estimation** : 10 jours
**Période** : S1-S2 janvier 2026

---

#### Sprint 20 : Intégration API Chorus Pro (2 semaines)
**Tâches** :
- [ ] Étude de l'API Chorus Pro (1j)
- [ ] Obtention certificat client X.509 (setup) (0.5j)
- [ ] Service `ChorusProProvider` (API client) (2j)
- [ ] Entité `PdpLog` (traçabilité des échanges) (0.5j)
- [ ] Envoi automatique au client et au PPF (2j)
- [ ] Suivi du statut (émise, reçue, rejetée, acceptée) (1.5j)
- [ ] Gestion des erreurs et rejets (1j)
- [ ] Tests d'intégration API Chorus Pro (mock) (1.5j)

**Estimation** : 10 jours
**Période** : S3-S4 janvier 2026

---

#### Sprint 21 : Webhooks Chorus Pro & Interface (2 semaines)
**Tâches** :
- [ ] Webhook handler pour notifications Chorus Pro (1.5j)
- [ ] Validation sécurité des webhooks (certificat) (0.5j)
- [ ] Mise à jour automatique du statut des factures (1j)
- [ ] Interface admin de gestion des factures Factur-X (2j)
  - Liste, statuts, PDF/XML, logs PDP
- [ ] Bouton "Émettre sur Chorus Pro" (0.5j)
- [ ] Visualisation des logs PDP (1j)
- [ ] Gestion manuelle des rejets (1.5j)
- [ ] Tests fonctionnels workflow complet (1j)

**Estimation** : 9 jours
**Période** : S5-S6 février 2026

---

#### Sprint 22 : Réception factures fournisseurs (2 semaines)
**Tâches** :
- [ ] Récupération automatique depuis Chorus Pro (polling ou webhook) (1.5j)
- [ ] Parsing XML CII et extraction des données (2j)
- [ ] Enregistrement dans `Purchase` (achats) (1j)
- [ ] Rapprochement automatique avec les commandes (1.5j)
- [ ] Interface de validation des factures fournisseurs (2j)
- [ ] Workflow d'approbation (comptable → manager) (1j)
- [ ] Tests d'intégration réception et parsing (1j)

**Estimation** : 10 jours
**Période** : S7-S8 février 2026

---

#### Sprint 23 : Archivage légal (2 semaines)
**Tâches** :
- [ ] Système de conservation 10 ans (obligation fiscale) (1.5j)
- [ ] Hash SHA-256 pour garantir l'intégrité (1j)
- [ ] Horodatage qualifié (optionnel, intégration) (1.5j)
- [ ] Archivage chiffré (AES-256) (1.5j)
- [ ] Export pour audit fiscal (format structuré) (1j)
- [ ] Interface admin d'archivage (consultation, export) (1.5j)
- [ ] Commande CLI de vérification d'intégrité (1j)
- [ ] Tests de sécurité (chiffrement, intégrité) (1j)

**Estimation** : 10 jours
**Période** : S9-S10 mars 2026

---

#### Sprint 24 : Documentation & Formation (2 semaines)
**Tâches** :
- [ ] Documentation complète du module (2j)
  - Guide d'utilisation
  - Procédures de gestion des rejets
  - FAQ
- [ ] Guide de mise en conformité (checklist) (1j)
- [ ] Documentation technique (architecture, API) (1j)
- [ ] Formation utilisateurs (support, admin) (2j)
- [ ] Tests de bout en bout (simulation complète) (2j)
- [ ] Préparation go-live (checklist, monitoring) (1j)
- [ ] Réserve pour corrections et ajustements (1j)

**Estimation** : 10 jours
**Période** : S11-S12 mars 2026

---

## Phase 5 : Transformation SAAS Multi-Tenant (Q3-Q4 2026) - 12 sprints

### Sprint 25-36 : Transformation SAAS Multi-Tenant (24 semaines) 🔴 **STRATÉGIQUE**
**Objectif** : Transformer HotOnes en solution SAAS multi-sociétés avec isolation complète des données

> **Note** : Cette phase est détaillée dans le document [docs/saas-multi-tenant-plan.md](./saas-multi-tenant-plan.md)

#### Sprint 25-26 : Préparation & Design (4 semaines)
**Tâches** :
- [ ] Architecture cible complète (2j)
- [ ] Design des entités Company et BusinessUnit (1j)
- [ ] Stratégie de migration des données (2j)
- [ ] Design du scoping explicite (repositories) (2j)
- [ ] Design de l'authentification multi-tenant (JWT) (1j)
- [ ] Design des voters et permissions (1j)
- [ ] Prototype sur 3 entités pilotes (3j)
- [ ] Validation de l'approche avec tests (2j)
- [ ] Documentation architecture (1j)

**Estimation** : 15 jours sur 4 semaines
**Période** : S13-S16 avril 2026

---

#### Sprint 27-31 : Database & Models (10 semaines)
**Tâches** :
- [ ] Création entités Company et BusinessUnit (1j)
- [ ] Migration de 45 entités existantes (ajout company_id) (18j)
  - Modification des entités (propriété + relation)
  - Création des migrations Doctrine
  - Tests de cohérence des données
- [ ] Index sur company_id pour toutes les entités (1j)
- [ ] Contraintes d'intégrité référentielle (CASCADE) (1j)
- [ ] Migration des données existantes vers Company par défaut (2j)
- [ ] Tests de migration (rollback, intégrité) (2j)

**Estimation** : 25 jours sur 10 semaines
**Période** : S17-S26 avril-juin 2026

---

#### Sprint 32-33 : Authentication & Context (4 semaines)
**Tâches** :
- [ ] Service CompanyContext (scoping automatique) (2j)
- [ ] Modification JWT avec claim company_id (1.5j)
- [ ] Middleware de vérification du tenant (1j)
- [ ] CompanyVoter pour vérifier l'appartenance (1.5j)
- [ ] BusinessUnitVoter pour permissions hiérarchiques (1.5j)
- [ ] AdminVoter pour super-admins SAAS (0.5j)
- [ ] Tests de sécurité (isolation, voters) (2j)

**Estimation** : 10 jours sur 4 semaines
**Période** : S27-S30 juillet 2026

---

#### Sprint 34-35 : Repository Scoping (4 semaines)
**Tâches** :
- [ ] Modification de 36 repositories (scoping explicite) (15j)
  - Injection CompanyContext
  - Modification de toutes les requêtes
  - Validation du scoping
- [ ] Tests d'isolation entre tenants (2j)
- [ ] Tests de fuites de données (1j)
- [ ] Audit de sécurité complet (2j)

**Estimation** : 20 jours sur 4 semaines
**Période** : S31-S34 juillet-août 2026

---

#### Sprint 36 : Finalisation & Déploiement (2 semaines)
**Tâches** :
- [ ] Interface d'administration SAAS (3j)
  - Gestion des Companies (CRUD)
  - Monitoring par tenant (usage, limites)
  - Statistiques globales
- [ ] Dashboards par BU (consolidation hiérarchique) (2j)
- [ ] Tests de performance (1j)
- [ ] Documentation complète (1.5j)
- [ ] Formation équipe (1j)
- [ ] Déploiement et monitoring (1j)
- [ ] Réserve pour imprévus (0.5j)

**Estimation** : 10 jours
**Période** : S35-S36 septembre 2026

---

## 📊 Récapitulatif & Planning Global

### Vue d'ensemble des phases

| Phase | Sprints | Période | Jours estimés | Focus |
|-------|---------|---------|---------------|-------|
| **Phase 1 : Consolidation & Opérationnel** | Sprint 2-7 | Jan-Mar 2025 | **53-56j** | Temps, Analytics, Facturation |
| **Phase 2 : Conformité RGPD** | Sprint 8-13 | Avr-Juin 2025 | **59j** | 🔴 **URGENT** - Obligation légale |
| **Phase 3 : Fonctionnalités Avancées** | Sprint 14-18 | Juil-Sep 2025 | **47j** | Signature, Rapports, API |
| **Phase 4 : Facturation Électronique** | Sprint 19-24 | Jan-Mar 2026 | **59j** | 🔴 Obligation légale 2027 |
| **Phase 5 : Transformation SAAS** | Sprint 25-36 | Avr-Sep 2026 | **80j** | 🔴 **STRATÉGIQUE** |
| **TOTAL** | **35 sprints** | **Jan 2025 - Sep 2026** | **~298j** | **21 mois** |

### Jalons clés (Milestones)

| Date | Jalon | Description |
|------|-------|-------------|
| **Mars 2025** | ✅ Opérationnel complet | Temps, Analytics, Facturation opérationnels |
| **Juin 2025** | ✅ Conformité RGPD | Application 100% conforme RGPD |
| **Septembre 2025** | ✅ Ouverture | API REST, Signature électronique, Rapports |
| **Mars 2026** | ✅ Facturation électronique | Conformité anticipée (18 mois avant obligation) |
| **Septembre 2026** | ✅ SAAS Multi-Tenant | Transformation complète en SAAS |

### Répartition du temps par priorité

| Priorité | Jours | % |
|----------|-------|---|
| 🔴 **Critique (Obligations légales)** | **118j** | **40%** |
| 🔴 **Haute (Business critical)** | **100j** | **34%** |
| 🟡 **Moyenne (Amélioration)** | **47j** | **16%** |
| 🔴 **Stratégique (SAAS)** | **80j** | **27%** |

---

## 🎯 Recommandations & Stratégie

### Court terme (Q1 2025) - Sprints 2-7
**Objectif** : Rendre l'application opérationnelle pour le quotidien

**Priorités** :
1. ✅ Finaliser Projets & Devis (PDF)
2. ✅ Saisie des Temps complète (critique pour rentabilité)
3. ✅ Dashboard Analytique (worker + KPIs)
4. ✅ Module de Facturation (trésorerie)

**Livrable Q1** : Application opérationnelle pour gestion quotidienne

---

### Moyen terme (Q2 2025) - Sprints 8-13
**Objectif** : Conformité RGPD URGENTE

**Priorités** :
1. 🔴 **RGPD complet** (35-37j) - Obligation légale depuis 2018
2. ✅ UX/UI améliorée
3. ✅ Notifications & Alertes

**Livrable Q2** : Application conforme RGPD + expérience utilisateur optimisée

**⚠️ RISQUE** : Non-conformité RGPD = sanctions jusqu'à 20M€ ou 4% du CA

---

### Long terme (Q3-Q4 2025) - Sprints 14-18
**Objectif** : Ouverture et intégrations

**Priorités** :
1. ✅ Signature Électronique (gain de productivité, taux de conversion)
2. ✅ Rapports & Exports professionnels
3. ✅ API REST (écosystème d'intégrations)

**Livrable Q3-Q4** : Application ouverte et intégrée

---

### Très long terme (Q1-Q4 2026) - Sprints 19-36
**Objectif** : Conformité légale avancée + Transformation SAAS

**Priorités** :
1. 🔴 **Facturation Électronique** (Q1 2026, 59j) - Anticiper obligation 2027
2. 🔴 **Transformation SAAS Multi-Tenant** (Q3-Q4 2026, 80j) - Transformation stratégique majeure

**Livrable 2026** : Application conforme 100% + architecture SAAS multi-sociétés

**⚠️ OPPORTUNITÉ** : SAAS Multi-Tenant ouvre de nouveaux marchés

---

## 📈 Métriques de Suivi

### KPIs de développement (à suivre par sprint)

- **Vélocité** : Jours effectifs complétés / Jours prévus (cible : 90%+)
- **Qualité** : Couverture de tests (cible : 80%+)
- **Dette technique** : Issues PHPStan/CS Fixer (cible : 0)
- **Bugs critiques** : Nombre de bugs bloquants (cible : 0)

### KPIs business (à suivre après chaque phase)

- **Adoption utilisateurs** : % d'utilisateurs actifs quotidiens
- **Temps de saisie** : Temps moyen de saisie hebdomadaire (cible : <10 min)
- **Taux de signature** : % devis signés (cible : +15% après signature électronique)
- **Conformité** : % de conformité RGPD (cible : 100% fin Q2 2025)

---

## ⚠️ Risques & Mitigation

### Risques identifiés

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| **Non-conformité RGPD prolongée** | 🔴 Très élevé | Moyenne | Prioriser Phase 2 (Q2 2025) |
| **Dépassement délais SAAS** | 🟡 Moyen | Élevée | Découpage en sprints courts, ajustements réguliers |
| **Complexité technique SAAS** | 🔴 Élevé | Moyenne | Prototype sur 3 entités, validation avant scaling |
| **Retard facturation électronique** | 🟡 Moyen | Faible | Anticiper 18 mois (Q1 2026 au lieu de 2027) |
| **Qualité insuffisante** | 🟡 Moyen | Moyenne | Tests inclus dans chaque sprint, revue de code |
| **Burnout développeur** | 🔴 Élevé | Moyenne | Sprints de 8-9j sur 10, buffer pour imprévus |

### Plan de contingence

- **Retard > 2 sprints** : Reprioriser les lots, reporter fonctionnalités non critiques
- **Problème technique bloquant** : Buffer de 1-2j par sprint pour résolution
- **Changement de priorités business** : Revue roadmap tous les 3 mois (fin de phase)

---

## 📝 Backlog additionnel (post-2026)

### Lots non planifiés (à prioriser selon besoins)

- **Lot 10** : Analytics Prédictifs (12-15j) - Machine Learning
- **Lot 11** : Dashboard RH & Talents (8-10j) - Gestion des compétences
- **Lot 12** : Intégrations Externes (15-20j) - Jira, Slack, GitLab
- **Lot 13** : Portail Client (12-15j) - Espace dédié clients
- **Lot 14** : Application Mobile (20-25j) - React Native iOS/Android
- **Lot 15** : PWA & Offline Mode (6-8j) - Progressive Web App
- **Lot 16** : Gamification (8-10j) - Badges et engagement
- **Lot 17** : Module Documentaire (10-12j) - Wiki et GED
- **Lot 18** : Business Units avancées (6-8j) - Post-SAAS
- **Lot 19** : Workflow de Recrutement (10-12j) - Pipeline candidats
- **Lot 20** : Gestion Achats & Fournisseurs (6-8j)
- **Lot 21** : Gestion des Contrats Clients (8-10j) - SLA, renouvellements
- **Lot 22** : Automatisation Avancée (6-8j) - Workflows automatisés
- **Lot 23** : Performance & Scalabilité (10-12j) - Redis, cache, monitoring
- **Lot 24** : Tests & Qualité (8-10j) - Renforcement couverture

**Total backlog** : ~180-220 jours additionnels

---

## 🎓 Formation & Montée en compétence

### Compétences requises par phase

| Phase | Compétences clés |
|-------|------------------|
| Phase 1 | Symfony Forms, Doctrine ORM, Chart.js, Messenger |
| Phase 2 | Sécurité, RGPD, Doctrine Listeners, Symfony Mailer |
| Phase 3 | API Platform, JWT, DomPDF, PhpSpreadsheet, Webhooks |
| Phase 4 | horstoeko/zugferd, API REST externes, Certificats X.509 |
| Phase 5 | Architecture multi-tenant, Voters, Performance, Sécurité avancée |

### Ressources recommandées

- **RGPD** : Formation CNIL (gratuite), Guide RGPD développeurs (CNIL)
- **Factur-X** : Documentation EN 16931, Guides Chorus Pro (AIFE)
- **SAAS Multi-Tenant** : "Multi-Tenancy with Symfony" (SymfonyCasts), articles SaaS architecture
- **API Platform** : Documentation officielle, Tutoriels SymfonyCasts
- **Tests** : "Testing Symfony Applications" (SymfonyCasts)

---

## 📅 Calendrier 2025-2026

### Q1 2025 (Janvier - Mars)
- **Sprints 2-7** : Consolidation & Opérationnel
- **Livrable** : Application opérationnelle (Temps, Analytics, Facturation)

### Q2 2025 (Avril - Juin)
- **Sprints 8-13** : Conformité RGPD + UX/UI + Notifications
- **Livrable** : Application conforme RGPD

### Q3 2025 (Juillet - Septembre)
- **Sprints 14-18** : Signature Électronique, Rapports, API REST
- **Livrable** : Application ouverte et intégrée

### Q4 2025 (Octobre - Décembre)
- **Repos et préparation** : Phase de stabilisation, bug fixes, feedback utilisateurs

### Q1 2026 (Janvier - Mars)
- **Sprints 19-24** : Facturation Électronique (Factur-X / Chorus Pro)
- **Livrable** : Conformité facturation électronique (anticipée)

### Q2 2026 (Avril - Juin)
- **Sprints 25-31** : Transformation SAAS (Préparation, Database & Models, Authentication)
- **Livrable** : Fondations SAAS multi-tenant

### Q3-Q4 2026 (Juillet - Septembre)
- **Sprints 32-36** : Transformation SAAS (Repository Scoping, Finalisation)
- **Livrable** : Application SAAS multi-tenant complète

---

## ✅ Conclusion & Next Steps

### Synthèse

Cette roadmap consolide **298 jours de développement** sur **21 mois** (35 sprints de 2 semaines).

**Priorités absolues** :
1. 🔴 **RGPD** (35-37j) - Obligation légale URGENTE
2. 🔴 **Facturation Électronique** (25-27j) - Anticiper obligation 2027
3. 🔴 **Transformation SAAS** (45-55j) - Opportunité stratégique majeure
4. ✅ **Opérationnel** (53-56j) - Temps, Analytics, Facturation

**ROI attendu** :
- **Éviter sanctions** : RGPD (jusqu'à 20M€), Facturation électronique (pénalités)
- **Gain de productivité** : -50% temps admin (signature électronique, automatisation)
- **Nouveaux revenus** : SAAS multi-tenant (vente en SAAS, nouveaux marchés)
- **Différenciation** : Conformité légale complète (peu d'agences conformes)

### Actions immédiates (Semaine prochaine)

1. ✅ **Valider cette roadmap** avec la direction et les parties prenantes
2. ✅ **Démarrer Sprint 2** (Finalisation Projets & Devis)
3. ✅ **Recruter/allouer** : 1 développeur full-stack Symfony expérimenté
4. ✅ **Préparer la conformité RGPD** : Désigner un référent RGPD, créer email rgpd@
5. ✅ **Anticiper la facturation électronique** : Veille réglementaire, contact Chorus Pro

### Revues trimestrielles

- **Fin Q1 2025** (Mars) : Bilan Phase 1, ajustement Phase 2
- **Fin Q2 2025** (Juin) : Bilan RGPD, ajustement Phase 3
- **Fin Q3 2025** (Septembre) : Bilan API/Signature, préparation 2026
- **Fin Q1 2026** (Mars) : Bilan Facturation électronique, préparation SAAS
- **Fin Q4 2026** (Septembre) : Bilan SAAS, roadmap 2027

---

**Document maintenu par** : Équipe HotOnes
**Dernière mise à jour** : 17 décembre 2025
**Prochaine revue** : Fin mars 2025

**Références** :
- [docs/roadmap-2025.md](./roadmap-2025.md) - Roadmap détaillée par lots
- [docs/roadmap-lots.md](./roadmap-lots.md) - Roadmap historique
- [docs/saas-multi-tenant-plan.md](./saas-multi-tenant-plan.md) - Plan détaillé SAAS
- [docs/execution-plan-2025.md](./execution-plan-2025.md) - Plan d'exécution 2025
- [docs/status.md](./status.md) - État d'avancement détaillé
