# 🗺️ Roadmap HotOnes 2025

> Roadmap mise à jour le 23 novembre 2025
>
> Cette roadmap consolide l'état actuel du projet et présente les évolutions futures organisées par thématiques et priorités.

## Liens
- **Plan d'Exécution 2025 (Phases 1, 2, 5 prioritaires)** : [docs/execution-plan-2025.md](./execution-plan-2025.md)
- État d'avancement détaillé : [docs/status.md](./status.md)
- Roadmap historique (lots) : [docs/roadmap-lots.md](./roadmap-lots.md)
- Fonctionnalités actuelles : [docs/features.md](./features.md)

---

## 📊 Vue d'ensemble

### Légende
- ✅ Terminé et en production
- 🔄 En cours de développement
- 🎯 Prioritaire (Q1 2025)
- 📋 Planifié (Q2-Q3 2025)
- 💡 Idée / Backlog (Q4 2025+)

### Principaux axes stratégiques 2025
1. **Automatisation** : Réduire les tâches manuelles répétitives
2. **Analytics & Prédictif** : Anticiper les risques et opportunités
3. **Expérience Utilisateur** : Simplifier les workflows quotidiens
4. **Intégration** : Connecter HotOnes avec l'écosystème externe
5. **Mobile-First** : Accès mobile pour les contributeurs terrain

---

## 🎯 Phase 1 : Consolidation & Professionnalisation (Q1 2025)

### 🔲 Lot 2 : Saisie des Temps - Finalisation
**Objectif** : Interface complète de saisie et gestion des temps

#### Fonctionnalités restantes
- 🎯 Grille de saisie hebdomadaire avec auto-save
- 🎯 Amélioration du compteur de temps (start/stop avec persistance)
- 🎯 Sélection projet → tâche en cascade (UX optimisée)
- 🎯 Vue calendrier mensuel avec saisie rapide
- 🎯 Copie de semaine / duplication de temps
- 🎯 Workflow de validation hiérarchique (chef de projet → manager)
- 🎯 Récapitulatif mensuel par contributeur et par projet
- 🎯 Export Excel/PDF des timesheets

**Tests** : Tests E2E du parcours complet saisie → validation
**Estimation** : 5-7 jours

---

### 🔲 Lot 3 : Dashboard Analytique - Finalisation
**Objectif** : Tableau de bord KPIs complet avec worker de calcul

#### Fonctionnalités restantes
- 🎯 Cartes KPIs principales (CA, Marge, Taux de marge, Projets actifs)
- 🎯 Graphiques d'évolution temporelle (Chart.js)
- 🎯 Répartition par type de projet (camembert)
- 🎯 Top contributeurs (Top 5 par CA/marge)
- 🎯 Filtres dynamiques (période, type, chef de projet, commercial, technologies)
- 🎯 Worker de recalcul asynchrone (handler + commande CLI)
- 🎯 Scheduler automatique quotidien
- 🎯 Export PDF/Excel du dashboard

**Tests** : Tests de performance des agrégations
**Estimation** : 7-10 jours

---

### 📋 Lot 1.3 : Projets - Améliorations
**Objectif** : Compléter les fonctionnalités de gestion de projets

#### Fonctionnalités
- 📋 Filtres avancés dans le listing (statut, type, technologies, dates, contributeurs)
- 📋 Recherche full-text sur nom projet / client / description
- 📋 Actions en masse (export, changement statut, archivage)
- 📋 Timeline du projet (historique des événements clés)

**Estimation** : 2-3 jours

---

### 📋 Lot 1.4 : Devis - Prévisualisation PDF
**Objectif** : Générer des devis professionnels au format PDF

#### Fonctionnalités
- 📋 Template PDF personnalisable (logo, couleurs, mentions légales)
- 📋 Génération PDF avec sections et lignes détaillées
- 📋 Calcul automatique des totaux HT/TTC
- 📋 Prévisualisation avant envoi client
- 📋 Historique des versions de devis

**Dépendances** : DomPDF ou Snappy
**Estimation** : 3-4 jours

---

### 💡 Lot 9 : Module de Facturation 🆕
**Objectif** : Automatiser la génération et le suivi des factures

#### Fonctionnalités
- 💡 Entité `Invoice` (numéro unique, statut, montants, échéances)
- 💡 Génération automatique depuis devis signés (forfait) ou temps saisis (régie)
- 💡 Échéancier de paiement (rappels automatiques)
- 💡 Statuts : Brouillon, Envoyée, Payée, En retard, Annulée
- 💡 Template PDF professionnel (mentions légales, TVA, IBAN)
- 💡 Export comptable (CSV pour import logiciel compta)
- 💡 Dashboard de trésorerie :
  - CA facturé vs CA encaissé
  - Prévisionnel de trésorerie (90j)
  - Factures en retard (alertes automatiques)
  - Délai moyen de paiement par client
- 💡 Relances automatiques par email (J+30, J+45, J+60)

**Tests** : Tests de génération PDF et calculs de trésorerie
**Estimation** : 10-12 jours

---

### 💡 Lot 25 : Facturation Électronique 🆕 🔴 **Obligation Légale 2027**
**Objectif** : Conformité avec la réforme française de la facturation électronique

#### Contexte
- **Obligation légale** : Toutes les entreprises doivent émettre et recevoir des factures électroniques à partir de **septembre 2027**
- **Échéance anticipée recommandée** : Q1-Q2 2026 (anticiper 18 mois)
- **Format** : Factur-X (PDF + XML structuré, standard français)
- **PDP** : Chorus Pro (Portail Public de Facturation, gratuit)

#### Fonctionnalités
- 💡 **Génération de factures Factur-X** :
  - Création automatique depuis devis signés (forfait) ou temps saisis (régie)
  - Génération PDF + XML CII (norme EN 16931)
  - Fusion hybride Factur-X (PDF lisible + données structurées)
  - Numérotation unique et chronologique (FAC-2025-001)
- 💡 **Émission via Chorus Pro** :
  - Intégration API Chorus Pro (PDP gratuite de l'État)
  - Envoi automatique au client et au PPF
  - Suivi du statut (émise, reçue, rejetée, acceptée)
  - Webhooks pour notifications temps réel
- 💡 **Réception de factures fournisseurs** :
  - Récupération automatique depuis Chorus Pro
  - Parsing XML et extraction des données
  - Enregistrement dans `Purchase` (achats)
  - Rapprochement automatique avec les commandes
- 💡 **Archivage légal** :
  - Conservation 10 ans (obligation fiscale)
  - Hash SHA-256 pour garantir l'intégrité
  - Export pour audit fiscal
  - Horodatage qualifié (optionnel)

#### Entités
- `Invoice` (facture) : numéro unique, statut, montants, échéances, fichiers PDF/Factur-X
- `InvoiceLine` (ligne de facture) : description, quantité, prix unitaire, TVA
- `PdpLog` (audit) : traçabilité des échanges avec Chorus Pro

#### Technologies
- **Bibliothèque PHP** : horstoeko/zugferd (génération Factur-X)
- **API** : Chorus Pro (REST, authentification par certificat client X.509)
- **Formats** : Factur-X (PDF + XML CII EN 16931)

#### Sécurité et conformité
- Numérotation chronologique obligatoire (aucun trou)
- Mentions légales complètes (SIREN, TVA, conditions de paiement)
- Intégrité des factures (hash, horodatage)
- Archivage chiffré (AES-256)
- Certificat client X.509 pour Chorus Pro

#### Coûts
- **Chorus Pro** : Gratuit (plateforme publique)
- **Certificat client X.509** : ~50-100€ HT/an
- **Total** : ~100€ HT/an

#### Documentation complète
Voir [docs/esignature-einvoicing-feasibility.md](./esignature-einvoicing-feasibility.md) pour l'étude de faisabilité complète

**Dépendances** : Lot 9 (Module de Facturation)
**Tests** : Tests unitaires génération Factur-X, tests d'intégration API Chorus Pro, tests de conformité EN 16931
**Estimation** : 25-27 jours

---

### 💡 Lot 26 : Signature Électronique 🆕
**Objectif** : Dématérialiser la signature des devis et contrats

#### Contexte
- **Cadre légal** : Règlement européen eIDAS
- **Type de signature** : Avancée (conforme eIDAS, valeur juridique pour contrats B2B)
- **Fournisseur recommandé** : Yousign (français, API complète)

#### Fonctionnalités
- 💡 **Signature de devis** :
  - Envoi du devis au client par email avec lien sécurisé
  - Interface de signature en ligne (sans compte client)
  - Changement automatique du statut (`a_signer` → `signe`)
  - Archivage du PDF signé avec certificat de signature
  - Notifications internes (commercial, chef de projet)
- 💡 **Signature de contrats** (futurs) :
  - Contrats de prestation (TMA, support, maintenance)
  - Contrats de confidentialité (NDA)
  - Avenants
- 💡 **Signature multi-parties** (optionnel) :
  - Workflow d'approbation interne avant envoi
  - Signature côté client + signature côté agence
- 💡 **Journal d'audit** :
  - Traçabilité complète (IP, user-agent, timestamp)
  - Certificat de signature Yousign
  - Export du journal en cas de litige

#### Entités
- `Order` : ajout de `yousignProcedureId`, `yousignSignedFileUrl`, `signedAt`, `signerEmail`, etc.
- `SignatureAudit` : audit trail complet (procédure, statut, métadonnées)

#### Technologies
- **Fournisseur** : Yousign (API REST, webhooks)
- **Intégration** : Symfony HttpClient
- **Sécurité** : HMAC pour validation des webhooks

#### Workflow
1. Utilisateur clique sur "Envoyer pour signature" dans l'interface devis
2. Backend génère le PDF et appelle l'API Yousign
3. Yousign envoie un email au client avec lien sécurisé
4. Client signe électroniquement
5. Yousign notifie HotOnes via webhook
6. Symfony met à jour le statut du devis et télécharge le PDF signé
7. Génération automatique des tâches projet (workflow existant)

#### Sécurité
- Clé API Yousign dans `.env` (Symfony Secrets en production)
- Validation HMAC des webhooks Yousign
- URL de signature à usage unique
- PDF signés dans répertoire sécurisé (hors web root)
- Accès restreint (ROLE_ADMIN, ROLE_MANAGER, créateur du devis)

#### Coûts
- **Plan Start** : 9€ HT/mois + 1,80€ HT/signature
- **Estimation** : ~10 signatures/mois → 27€ HT/mois (324€ HT/an)

#### ROI
- Gain de temps : 2-3h/mois (plus d'impression/scan/envoi)
- Délai de signature : 3-5 jours → quelques heures
- Taux de conversion : +10-15% (facilité de signature)
- Sécurité juridique renforcée

#### Documentation complète
Voir [docs/esignature-einvoicing-feasibility.md](./esignature-einvoicing-feasibility.md) pour l'étude de faisabilité complète

**Dépendances** : Lot 1.4 (Prévisualisation PDF du devis)
**Tests** : Tests unitaires services, tests d'intégration API Yousign (mock), tests fonctionnels workflow complet, tests de sécurité webhook
**Estimation** : 10-11 jours

---

### 💡 Lot 27 : Conformité RGPD 🆕 🔴 **Obligation Légale**
**Objectif** : Mise en conformité avec le Règlement Général sur la Protection des Données

#### Contexte
- **Obligation légale** : RGPD en vigueur depuis le 25 mai 2018
- **Sanctions** : Jusqu'à **20 millions d'euros** ou **4% du CA annuel mondial**
- **Opportunité** : Différenciation concurrentielle, conformité pour appels d'offres

#### Fonctionnalités
- 💡 **Registre des activités de traitement** (Art. 30) :
  - Entité `ProcessingActivity` (finalités, bases légales, durées de conservation)
  - Interface admin pour gérer le registre
  - Export PDF/Excel pour audit
- 💡 **Droits des personnes** (Art. 15-22) :
  - Droit d'accès : Export JSON/PDF de toutes les données personnelles
  - Droit de rectification : Modification des données (page "Mon compte")
  - Droit à l'effacement : Suppression/anonymisation du compte
  - Droit à la portabilité : Export JSON/CSV/XML
  - Droit à la limitation : Gel du traitement (statut `dataProcessingLimited`)
  - Droit d'opposition : Opt-out analytics, cookies non essentiels
  - Formulaire de demande d'exercice de droits (`PrivacyRequest`)
- 💡 **Politique de confidentialité** (Art. 13-14) :
  - Page `/privacy` avec politique complète
  - Acceptation lors de la première connexion
  - Versionning et notification des mises à jour
- 💡 **Gestion des consentements** :
  - Entité `ConsentRecord` (analytics, cookies, newsletter)
  - Bannière de consentement (Tarteaucitron.js)
  - Opt-in par défaut pour cookies non essentiels
- 💡 **Audit trail** :
  - Entité `AuditLog` (journalisation des actions sensibles)
  - Qui, quoi, quand, IP, user-agent
  - Conservation 6 mois (recommandation CNIL)
- 💡 **Violations de données** (Art. 33-34) :
  - Entité `DataBreach` (déclaration, suivi, notifications)
  - Procédure de notification CNIL sous 72h
  - Documentation des violations
- 💡 **Durées de conservation et purge** :
  - Commande `app:gdpr:purge` (automatique quotidien)
  - Suppression logs > 6 mois
  - Anonymisation comptes inactifs > 3 ans
  - Suppression données RH après départ + 5 ans

#### Entités
- `ProcessingActivity` : Registre des traitements
- `PrivacyRequest` : Demandes d'exercice de droits
- `DataBreach` : Violations de données
- `AuditLog` : Journalisation des actions sensibles
- `ConsentRecord` : Consentements (cookies, analytics)

#### Services
- `GdprService` : Export, anonymisation, suppression, limitation
- `PrivacyRequestService` : Gestion des demandes de droits
- `AuditLogService` : Journalisation automatique
- `DataRetentionService` : Purge et anonymisation

#### Sécurité et conformité
- Chiffrement des données sensibles (salaires, etc.)
- Anonymisation / pseudonymisation
- Contrôle d'accès par rôles (déjà en place)
- 2FA (déjà disponible)
- HTTPS (déjà en place)
- Sauvegardes chiffrées
- Tests de sécurité (pentests recommandés annuellement)

#### Documentation et procédures
- Registre des activités de traitement
- Politique de confidentialité
- Procédure de gestion des violations
- Procédure de gestion des demandes de droits
- Désignation d'un référent RGPD interne

#### Coûts
- **Développement** : 35-37 jours
- **Audit RGPD externe** (optionnel) : 2 000 - 5 000€
- **DPO externe** (optionnel pour PME) : 1 000 - 3 000€/an
- **Pentest annuel** (recommandé) : 3 000 - 10 000€
- **Formation RGPD** : 500 - 1 500€
- **Total optionnel** : ~5 000 - 15 000€ (première année)

#### ROI
- Éviter les sanctions CNIL (jusqu'à 20M€ ou 4% du CA)
- Conformité pour appels d'offres (clause RGPD souvent obligatoire)
- Renforcer la confiance des clients et employés
- Différenciation concurrentielle
- Amélioration de la sécurité et de la gouvernance des données

#### Documentation complète
Voir [docs/rgpd-compliance-feasibility.md](./rgpd-compliance-feasibility.md) pour l'étude de faisabilité complète

**Dépendances** : Aucune (peut être développé en parallèle)
**Tests** : Tests unitaires services, tests fonctionnels workflows, tests de sécurité, tests de procédure de violation (simulation)
**Estimation** : 35-37 jours

---

## 📊 Phase 2 : Analytics Avancés & Prédictif (Q2 2025)

### 💡 Lot 10 : Analytics Prédictifs 🆕
**Objectif** : Anticiper les risques et opportunités business

#### Fonctionnalités
- 💡 **Forecasting CA** :
  - Prédiction du CA sur 3/6/12 mois basée sur historique
  - Prise en compte de la saisonnalité
  - Comparaison prévisionnel vs réalisé
- 💡 **Analyse des risques projet** :
  - Détection automatique des projets à risque (dépassement budget, délais)
  - Score de santé par projet (vert/orange/rouge)
  - Alertes proactives pour les chefs de projet
- 💡 **Prédiction de charge** :
  - Anticipation des périodes de surcharge/sous-charge
  - Recommandations de recrutement basées sur le pipeline
  - Optimisation de l'allocation des ressources
- 💡 **Analyse de rentabilité prédictive** :
  - Estimation de la marge finale dès 30% de réalisation
  - Identification des dérives budgétaires précoces
  - Recommandations de correction (scope, staffing)

**Dépendances** : Données historiques suffisantes (6-12 mois)
**Technologies** : Machine Learning (scikit-learn ou API externe)
**Estimation** : 12-15 jours

---

### 💡 Lot 11 : Dashboard RH & Talents 🆕
**Objectif** : Piloter la gestion des ressources humaines

#### Fonctionnalités
- 💡 **KPIs RH** :
  - Turnover (taux de départ annuel)
  - Absentéisme (taux et évolution)
  - Ancienneté moyenne par profil
  - Pyramide des âges et des compétences
- 💡 **Gestion des compétences** :
  - Matrice compétences par contributeur (technologies + soft skills)
  - Niveaux : Débutant, Intermédiaire, Confirmé, Expert
  - Gap analysis (compétences requises vs disponibles)
  - Plan de formation automatique
- 💡 **Revues annuelles** :
  - Campagne d'évaluation (auto-évaluation + manager)
  - Objectifs individuels (SMART)
  - Historique des évaluations
- 💡 **Onboarding** :
  - Checklist d'intégration nouveau contributeur
  - Suivi des tâches d'onboarding
  - Formation initiale (parcours par profil)

**Estimation** : 8-10 jours

---

### 📋 Lot 7 : Rapports & Exports - Complet
**Objectif** : Rapports professionnels pour direction et clients

#### Fonctionnalités
- 📋 Rapport d'activité mensuel (par projet, client, BU)
- 📋 Rapport financier (CA, marges, coûts, rentabilité)
- 📋 Rapport contributeur (temps, projets, performance)
- 📋 Rapport commercial (pipeline, taux de conversion)
- 📋 Rapport devis actifs entre 2 dates
- 📋 Templates personnalisables (logo, charte graphique)
- 📋 Génération planifiée (cron) et envoi automatique
- 📋 Export multi-format (PDF, Excel, CSV)

**Estimation** : 6-7 jours

---

## 🚀 Phase 3 : Ouverture & Intégrations (Q3 2025)

### 💡 Lot 8 : API REST - Finalisation
**Objectif** : API complète pour intégrations externes

#### Endpoints
- 📋 `/api/projects` (CRUD projets)
- 📋 `/api/timesheets` (saisie/consultation temps)
- 📋 `/api/contributors` (liste contributeurs)
- 📋 `/api/orders` (devis)
- 📋 `/api/metrics` (KPIs lecture seule)
- 📋 `/api/users` (CRUD utilisateurs)
- 📋 `/api/running-timer` (timer actif)
- 💡 `/api/invoices` (factures)
- 💡 `/api/clients` (clients)
- 💡 `/api/vacation-requests` (demandes de congés)

#### Sécurité
- 📋 Authentification JWT (lexik/jwt-authentication-bundle)
- 📋 Rate limiting (par client API)
- 📋 Scopes/permissions par endpoint
- 📋 Documentation OpenAPI/Swagger automatique

#### SDKs
- 💡 SDK JavaScript/TypeScript (npm package)
- 💡 SDK Python (pip package)

**Estimation** : 8-10 jours

---

### 💡 Lot 12 : Intégrations Externes 🆕
**Objectif** : Connecter HotOnes avec l'écosystème d'entreprise

#### Intégrations
- 💡 **Jira / ClickUp / Notion** :
  - Import automatique des tâches projet
  - Synchronisation bidirectionnelle (temps, statuts)
  - Mapping ProjectTask ↔ Issue
- 💡 **Slack / Microsoft Teams** :
  - Notifications d'événements (nouveau devis, validation temps)
  - Commandes slash (/hotones timesheet, /hotones stats)
  - Webhooks pour alertes personnalisées
- 💡 **Google Calendar / Outlook** :
  - Export planning → calendrier personnel
  - Import congés depuis calendrier RH
  - Synchronisation bidirectionnelle
- 💡 **Logiciels comptables** :
  - Export factures vers Sage, Cegid, QuickBooks
  - Format FEC (Fichier des Écritures Comptables)
  - Réconciliation automatique des paiements
- 💡 **GitLab / GitHub** :
  - Intégration commits → temps passés
  - Statistiques de productivité code
  - Lien projets HotOnes ↔ repositories

**Estimation** : 15-20 jours (selon nombre d'intégrations)

---

### 💡 Lot 13 : Portail Client 🆕
**Objectif** : Espace dédié pour les clients avec accès limité

#### Fonctionnalités
- 💡 **Authentification séparée** :
  - Compte client distinct des utilisateurs internes
  - Mot de passe + 2FA optionnel
  - Multi-utilisateurs par client (admin client)
- 💡 **Dashboard client** :
  - Projets en cours et terminés
  - Temps consommés vs budgetés
  - Factures et paiements
  - Documents partagés (livrables, rapports)
- 💡 **Suivi de projet** :
  - Avancement en temps réel
  - Timeline des jalons
  - Reporting automatique (hebdo/mensuel)
- 💡 **Support & Tickets** :
  - Création de tickets support
  - Suivi du statut (nouveau, en cours, résolu)
  - Base de connaissances (FAQ)
- 💡 **Validation de livrables** :
  - Upload de fichiers
  - Workflow d'approbation
  - Historique des versions

**Estimation** : 12-15 jours

---

## 📱 Phase 4 : Mobile & Expérience Terrain (Q4 2025)

### 💡 Lot 14 : Application Mobile 🆕
**Objectif** : Saisie de temps et consultation en mobilité

#### Fonctionnalités v1.0 (MVP)
- 💡 **Authentification** :
  - Login email/password
  - Biométrie (Face ID, Touch ID, empreinte digitale)
  - Session persistante
- 💡 **Saisie de temps** :
  - Interface simplifiée pour saisie rapide
  - Timer start/stop avec notifications
  - Saisie hors-ligne (synchronisation auto)
  - Historique de la semaine
- 💡 **Consultation** :
  - Planning personnel (vue semaine/mois)
  - Congés (solde, demande, approbation pour managers)
  - Notifications push (validation temps, nouveau projet)
- 💡 **Scanner de notes de frais** :
  - Photo de ticket
  - OCR pour extraction montant/date
  - Catégorisation automatique

#### Technologies
- 💡 React Native (iOS + Android)
- 💡 Utilisation de l'API REST HotOnes
- 💡 Stockage local SQLite pour offline

**Estimation** : 20-25 jours

---

### 💡 Lot 15 : PWA & Offline Mode 🆕
**Objectif** : Version web progressive accessible hors-ligne

#### Fonctionnalités
- 💡 Service Workers pour cache intelligent
- 💡 Installation sur écran d'accueil (mobile & desktop)
- 💡 Synchronisation en arrière-plan
- 💡 Mode hors-ligne pour saisie de temps
- 💡 Notifications push web
- 💡 Responsive design optimisé mobile

**Estimation** : 6-8 jours

---

## 🎨 Phase 5 : UX/UI & Gamification (Q4 2025)

### 📋 Lot 5 : Améliorations UX/UI - Complet
**Objectif** : Améliorer l'expérience utilisateur globale

#### Fonctionnalités
- 📋 Menu latéral adapté aux entités
- 📋 Fil d'ariane sur toutes les pages
- 📋 Recherche globale (projets, contributeurs, devis, clients)
- 📋 Tableaux de données :
  - Pagination côté serveur
  - Tri multi-colonnes
  - Filtres avancés persistants
  - Actions en masse
  - Export CSV/Excel
- 📋 Formulaires :
  - Validation temps réel (AJAX)
  - Champs dépendants (projet → tâches)
  - Sauvegarde automatique (brouillon)
- 📋 Notifications in-app avec centre de notifications

**Tests** : Tests E2E navigation, tests accessibilité WCAG
**Estimation** : 5-6 jours

---

### 💡 Lot 15.5 : Cohérence UX/UI Globale 🆕
**Objectif** : Harmoniser l'expérience utilisateur sur toutes les pages

#### Fonctionnalités
- 💡 **Audit UX/UI complet** :
  - Recensement de toutes les pages CRUD
  - Identification des incohérences (titres, boutons, filtres, actions)
  - Guide de style interne (design system light)
- 💡 **Standardisation des en-têtes** :
  - Template réutilisable pour en-têtes de page
  - Fil d'ariane (breadcrumb) sur toutes les pages
  - Boutons d'action alignés à droite
- 💡 **Refonte menu latéral** :
  - Retrait des entrées "Ajouter projet/contributeur/etc."
  - Boutons "Nouveau X" uniquement dans les pages de liste
  - Menu simplifié et logique
- 💡 **Standardisation listes** :
  - Filtres cohérents (position, style, sauvegarde session)
  - Actions par ligne : Voir, Modifier, Supprimer
  - Actions en masse : checkbox, suppression masse, export CSV
  - Pagination standardisée (25/50/100 par page)
- 💡 **Standardisation formulaires** :
  - Layout cohérent (largeur max 800px)
  - Labels au-dessus, champs requis marqués (*)
  - Boutons "Enregistrer" + "Annuler" alignés à droite
- 💡 **Components Twig réutilisables** :
  - `page_header.html.twig`
  - `data_table.html.twig`
  - `filter_panel.html.twig`
  - `pagination.html.twig`
- 💡 **JavaScript actions en masse** :
  - Script `mass-actions.js`
  - Sélection checkbox, confirmation suppression
- 💡 **Documentation Design System** :
  - Couleurs, typographie, boutons, formulaires
  - Exemples de code pour développeurs

**Dépendances** : Lot 5 (UX/UI de base)
**Estimation** : 10 jours

---

### 💡 Lot 16 : Gamification & Engagement 🆕
**Objectif** : Motiver et engager les contributeurs

#### Fonctionnalités
- 💡 **Système de badges** :
  - Early Bird (1er à saisir ses temps de la semaine)
  - Perfectionist (saisie sans erreur pendant 1 mois)
  - Marathon Runner (3 mois sans absence)
  - Knowledge Sharer (5+ formations données)
  - Bug Hunter (signalement de bugs critiques)
- 💡 **Classements** :
  - Top contributeurs du mois (CA généré)
  - Top formateurs (heures de formation données)
  - Équipe la plus productive
- 💡 **Progression de carrière** :
  - Arbre de compétences visuel
  - Déblocage de nouveaux profils
  - Parcours de montée en compétence
- 💡 **Récompenses** :
  - Points d'expérience (XP)
  - Niveaux (Junior → Senior → Lead → Principal)
  - Récompenses déblocables (jours de télétravail bonus, formation payée)

**Estimation** : 8-10 jours

---

### 💡 Lot 17 : Module Documentaire 🆕
**Objectif** : Centraliser la documentation projet et entreprise

#### Fonctionnalités
- 💡 **Bibliothèque documentaire** :
  - Upload/download de fichiers
  - Organisation par projet/client
  - Gestion de versions
  - Recherche full-text dans les documents (PDF, Word, Excel)
- 💡 **Templates de documents** :
  - Cahier des charges type
  - Spécifications techniques
  - PV de réunion
  - Rapport de livraison
- 💡 **Wiki interne** :
  - Base de connaissances par technologie
  - Tutoriels et best practices
  - Onboarding docs
  - Changelog produit
- 💡 **Gestion des accès** :
  - Permissions par rôle
  - Documents confidentiels (compta, RH)
  - Partage externe sécurisé (lien temporaire)

**Technologies** : ElasticSearch pour recherche full-text
**Estimation** : 10-12 jours

---

## 🏢 Phase 6 : Structuration Entreprise (2026)

### 💡 Lot 18 : Business Units (BU) 🆕
**Objectif** : Cloisonner plusieurs équipes au sein de la même société

#### Fonctionnalités
- 💡 **Entité BusinessUnit** :
  - Nom, manager, objectifs annuels (CA, marge, contributeurs)
  - Rattachement contributeurs (1 BU par contributeur)
  - Rattachement devis et clients (optionnel)
- 💡 **Dashboards isolés** :
  - Chaque BU voit uniquement ses chiffres
  - Comparaison inter-BU pour direction
  - Consolidation globale (vue SUPERADMIN)
- 💡 **Objectifs et suivi** :
  - CA signé vs objectif
  - Marge générée vs objectif
  - Effectif réel vs prévu
  - Graphiques d'évolution trimestrielle
- 💡 **Permissions** :
  - Manager BU : accès complet à sa BU
  - Contributeur : accès limité à sa BU
  - Direction : vue consolidée toutes BU

**Estimation** : 8-10 jours

---

### 💡 Lot 19 : Workflow de Recrutement 🆕
**Objectif** : Gérer le pipeline de recrutement des talents

#### Fonctionnalités
- 💡 **Entité Candidate** :
  - Coordonnées (nom, email, téléphone)
  - Upload CV et lettre de motivation
  - Profil métier cible (JobProfile)
  - Technologies et niveaux (self-assessment)
  - Prétentions salariales (k€/an)
  - Type de contrat (CDI, CDD, Alternance, Stage)
  - BU identifiée
- 💡 **Pipeline de recrutement** :
  - Étapes : Candidature, Présélection, Entretien RH, Entretien Technique, Entretien Direction, Offre, Embauché, Refusé
  - Vue Kanban avec drag & drop
  - Historique des interactions (appels, emails, entretiens)
  - Assignation des intervieweurs par étape
- 💡 **Conversion en contributeur** :
  - Bouton "Embaucher" crée un Contributor
  - Pré-remplissage depuis Candidate
  - Création automatique de la 1ère EmploymentPeriod
  - Conservation de l'historique de recrutement
- 💡 **Statistiques recrutement** :
  - Temps moyen par étape
  - Taux de conversion par étape
  - Sources de candidatures (LinkedIn, Cooptation, Indeed)
  - Coût du recrutement

**Estimation** : 10-12 jours

---

### 💡 Lot 20 : Gestion Achats & Fournisseurs 🆕
**Objectif** : Centraliser les achats et la relation fournisseurs

#### Fonctionnalités
- 💡 **Entité Supplier** (Fournisseur) :
  - Nom, coordonnées, SIRET, IBAN
  - Catégorie (Hébergement, Licences, Freelance, Matériel, Formation)
  - Conditions de paiement (30j, 45j, 60j)
  - Documents (contrats, factures)
- 💡 **Entité Purchase** (Achat) :
  - Rattachement projet/client (optionnel)
  - Fournisseur
  - Montant HT/TTC
  - Date achat et date paiement
  - Statut (À payer, Payé, En retard)
  - Catégorie et sous-catégorie
- 💡 **Budgets d'achat** :
  - Budget annuel par catégorie
  - Alertes de dépassement
  - Visualisation consommé vs budgeté
- 💡 **Dashboard achats** :
  - Répartition par catégorie (camembert)
  - Top 5 fournisseurs
  - Achats par projet
  - Prévisionnel de paiement (90j)

**Estimation** : 6-8 jours

---

### 💡 Lot 21 : Gestion des Contrats Clients 🆕
**Objectif** : Suivi avancé des contrats et engagements

#### Fonctionnalités
- 💡 **Entité Contract** :
  - Lien vers Order (contrat issu d'un devis signé)
  - Type : Forfait, Régie, Support, Maintenance, TMA
  - Dates début/fin, reconduction tacite
  - Conditions particulières (SLA, pénalités, bonus)
  - Documents attachés (contrat signé, avenants)
- 💡 **SLA (Service Level Agreement)** :
  - Temps de réponse garanti (ex: 4h ouvrées)
  - Taux de disponibilité (ex: 99.9%)
  - Pénalités en cas de non-respect
  - Suivi automatique et alertes
- 💡 **Renouvellements** :
  - Alertes avant échéance (J-90, J-60, J-30)
  - Workflow de renégociation
  - Historique des versions de contrat
- 💡 **Dashboard contrats** :
  - Contrats à renouveler (3 prochains mois)
  - Revenus récurrents (MRR, ARR)
  - Taux de rétention client
  - SLA compliance par client

**Estimation** : 8-10 jours

---

## 🔔 Phase 7 : Notifications & Automatisation (2026)

### 📋 Lot 6 : Notifications & Alertes - Finalisation
**Objectif** : Système de notifications complet

#### Fonctionnalités
- ✅ Infrastructure en place (entités, page d'index)
- 📋 Déclencheurs d'événements :
  - Nouveau devis à signer
  - Devis gagné/perdu
  - Projet proche de son budget (80%, 90%, 100%, 110%)
  - Temps en attente de validation
  - ✅ Rappel hebdomadaire de saisie des temps
  - Échéance de paiement proche
  - Seuil d'alerte KPI dépassé
- 📋 Canaux :
  - Notifications in-app (base de données)
  - Emails (Symfony Mailer)
  - Optionnel : Webhook Slack/Discord
- 📋 Préférences utilisateur (événements, canaux)
- 📋 Configuration globale admin (seuils d'alerte)

**Estimation** : 4-5 jours

---

### 💡 Lot 22 : Automatisation Avancée 🆕
**Objectif** : Automatiser les tâches répétitives

#### Fonctionnalités
- 💡 **Workflows automatisés** :
  - Si projet > 90% budget → alerte chef de projet + manager
  - Si devis non signé après 30j → relance automatique
  - Si timesheet non validé après 7j → escalade manager
  - Si facture impayée > 45j → relance + alerte compta
- 💡 **Templates de tâches** :
  - Création automatique de tâches à la signature d'un devis
  - Duplication de structure de tâches entre projets similaires
  - Application de templates par type de projet (refonte, dev from scratch, TMA)
- 💡 **Rapports automatiques** :
  - Envoi hebdo du dashboard staffing aux managers
  - Rapport mensuel au directeur (CA, marge, projets à risque)
  - Rapport trimestriel aux clients (projets TMA/support)
- 💡 **Actions planifiées** :
  - Archivage automatique des projets terminés (après 1 an)
  - Nettoyage des brouillons non utilisés (après 3 mois)
  - Backup automatique de la base de données

**Technologies** : Symfony Messenger + Scheduler
**Estimation** : 6-8 jours

---

## 🧪 Phase 8 : Qualité & Performance (Continue)

### 💡 Lot 22.5 : Migration PHP 8.5 / Symfony 8 🆕
**Objectif** : Anticiper et préparer la migration vers les versions majeures

#### Contexte
- **PHP 8.5** : Sortie prévue novembre 2025
- **Symfony 8.0** : Sortie stable prévue novembre 2025
- Nécessité d'anticiper ces migrations dans les développements futurs

#### Actions de préparation (en continu)
- 💡 **Audit de compatibilité** :
  - Revue des dépendances Composer et compatibilité avec PHP 8.5 / Symfony 8
  - Identification des features dépréciées dans Symfony 7.x
  - Test de l'application avec PHP 8.5 (mode strict_types)
  - Liste des breaking changes à anticiper

- 💡 **Bonnes pratiques dès maintenant** :
  - Éviter l'usage de fonctionnalités dépréciées de Symfony 7.x
  - Respecter les nouvelles conventions PHP 8.4+ (typed properties, readonly, etc.)
  - Tester régulièrement avec `composer outdated` et `symfony check:requirements`
  - Documenter les dépendances critiques à surveiller

- 💡 **Migration PHP 8.5** (Q4 2025 / Q1 2026) :
  - Mise à jour de l'image Docker (PHP 8.5-fpm)
  - Tests de régression complets
  - Revue des extensions PHP (éventuelles incompatibilités)
  - Mise à jour de PHPStan, PHP CS Fixer, PHPUnit vers versions compatibles
  - Benchmark de performance (comparaison 8.4 vs 8.5)

- 💡 **Migration Symfony 8.0** (Q1 2026) :
  - Mise à jour progressive : Symfony 7.3 → 7.4 (LTS) → 8.0 (stable)
  - Utilisation de l'outil `symfony upgrade` pour identifier les changements
  - Refactoring des deprecations Symfony 7.x
  - Mise à jour des bundles tiers (Doctrine, Twig, etc.)
  - Tests fonctionnels et E2E complets post-migration
  - Documentation des breaking changes rencontrés

#### Planning recommandé
1. **Maintenant - Q3 2025** : Veille et préparation (éviter les deprecations)
2. **Q4 2025** : Tests avec versions RC de PHP 8.5 et Symfony 8
3. **Q1 2026** : Migration effective après stabilisation des releases
4. **Q2 2026** : Optimisations post-migration (nouvelles features PHP/Symfony)

#### Risques identifiés
- ⚠️ Bundles tiers non compatibles immédiatement
- ⚠️ Breaking changes non documentés
- ⚠️ Régression de performance (rare mais possible)
- ⚠️ Incompatibilités d'extensions PHP (ex: Redis, APCu)

**Estimation** :
- Préparation continue : 1-2j répartis sur Q2-Q3 2025
- Migration PHP 8.5 : 2-3j (tests inclus)
- Migration Symfony 8.0 : 5-7j (tests et refactoring)
- **Total : 8-12j** (selon complexité des breaking changes)

---

### 💡 Lot 23 : Performance & Scalabilité 🆕
**Objectif** : Optimiser les performances pour grosse volumétrie

#### Actions
- 💡 **Cache stratégique** :
  - Redis pour cache applicatif
  - Cache HTTP (Varnish ou Symfony HTTP Cache)
  - Cache de requêtes Doctrine
- 💡 **Optimisation base de données** :
  - Analyse et création d'index manquants
  - Partitionnement des tables de métriques
  - Archivage des données anciennes (> 3 ans)
- 💡 **Pagination et lazy loading** :
  - Pagination côté serveur sur tous les listings
  - Chargement lazy des graphiques (on-demand)
  - Infinite scroll sur timesheet
- 💡 **Monitoring** :
  - APM (Application Performance Monitoring) : Blackfire, New Relic, ou Datadog
  - Alertes sur temps de réponse > 500ms
  - Dashboard de métriques techniques (CPU, RAM, queries/s)

**Estimation** : 10-12 jours

---

### 💡 Lot 24 : Tests & Qualité - Renforcement 🆕
**Objectif** : Augmenter la couverture de tests

#### Actions
- 💡 Tests unitaires :
  - Cible : 80% de couverture (actuellement ~60%)
  - Focus : Services métier, calculs de métriques
- 💡 Tests d'intégration :
  - Tous les repositories (requêtes complexes)
  - Workers et handlers de messages
- 💡 Tests fonctionnels :
  - Tous les controllers (CRUD complets)
  - Workflows métier (signature devis → génération tâches)
- 💡 Tests E2E :
  - Parcours critiques (saisie temps, création projet, génération facture)
  - Tests cross-browser (Chrome, Firefox, Safari)
- 💡 Tests de charge :
  - Simulation de 100+ utilisateurs concurrents
  - Identification des goulots d'étranglement

**Estimation** : 8-10 jours

---

## 📦 Backlog & Idées Futures

### 💡 Module de Formation Interne
- Catalogue de formations (internes & externes)
- Inscription et gestion des places
- Évaluation post-formation
- Suivi du budget formation par contributeur

### 💡 Gestion des Risques Projet
- Registre des risques par projet
- Probabilité × Impact = Criticité
- Plans de mitigation
- Suivi de l'évolution des risques

### 💡 Gestion des Incidents & Support
- Ticketing pour support client
- SLA tracking (temps de réponse, résolution)
- Base de connaissances
- Escalade automatique

### 💡 BI & Data Warehouse
- Export vers BI externe (Metabase, Tableau, Power BI)
- Data Warehouse pour analytics cross-applications
- Tableaux de bord exécutifs (direction générale)

### 💡 Module de Veille Technologique
- Flux RSS de veille par technologie
- Curation de ressources (articles, tutos, confs)
- Partage interne (Slack/Teams)
- Tableau de bord des tendances tech

### 💡 Certification & Habilitations
- Suivi des certifications contributeurs (AWS, Google Cloud, Scrum Master)
- Alertes d'expiration
- Budget de certification
- Catalogue de certifications recommandées par profil

---

## 📊 Récapitulatif & Estimation Globale

### Priorités 2025

| Phase | Lots | Priorité | Estimation | Trimestre |
|-------|------|----------|------------|-----------|
| Phase 1 : Consolidation & Conformité | Lots 2, 3, 1.3, 1.4, 9, 25, 26, 27 | 🔴 Haute | 107-121j | Q1 2025 - Q2 2026 |
| Phase 2 : Analytics | Lots 10, 11, 7 | 🟡 Moyenne | 26-32j | Q2 2025 |
| Phase 3 : Ouverture | Lots 8, 12, 13 | 🟡 Moyenne | 35-45j | Q3 2025 |
| Phase 4 : Mobile | Lots 14, 15 | 🟢 Basse | 26-33j | Q4 2025 |
| Phase 5 : UX/UI | Lots 5, 16, 17 | 🟡 Moyenne | 23-28j | Q4 2025 |
| Phase 6 : Structuration | Lots 18, 19, 20, 21 | 🟢 Basse | 32-40j | 2026 |
| Phase 7 : Automatisation | Lots 6, 22 | 🟢 Basse | 10-13j | 2026 |
| Phase 8 : Qualité | Lots 22.5, 23, 24 | 🟡 Continue | 26-34j | Continue |

**Total estimé 2025-2026** : ~250-272 jours (incluant conformité légale complète)
- **Facturation électronique (Lot 25)** : 25-27 jours (Q1 2026, **obligation légale septembre 2027**)
- **Signature électronique (Lot 26)** : 10-11 jours (Q3 2026)
- **Conformité RGPD (Lot 27)** : 35-37 jours (Q1-Q2 2026, **obligation légale depuis 2018**)

---

## 🎯 Recommandations

### Court terme (3 mois)
1. **Finaliser les fondations** : Lots 2, 3, 1.3, 1.4 (saisie temps + analytics + projets)
2. **Mettre en place la facturation** : Lot 9 (critique pour le business)
3. **Renforcer les tests** : Augmenter la couverture pour sécuriser les évolutions
4. **⚠️ NOUVEAU : Démarrer la conformité RGPD** : Lot 27 (**URGENT** - obligation légale depuis 2018, sanctions jusqu'à 20M€)
5. **⚠️ NOUVEAU : Anticiper la facturation électronique** : Lot 25 (obligation légale septembre 2027, à démarrer en Q1 2026)

### Moyen terme (6-9 mois)
1. **⚠️ NOUVEAU : Finaliser la conformité RGPD** : Lot 27 (registre, droits des personnes, audit trail, politique de confidentialité)
2. **⚠️ NOUVEAU : Signature électronique** : Lot 26 (gain de productivité, amélioration du taux de conversion)
3. **Analytics prédictifs** : Lot 10 (différenciation compétitive forte)
4. **API REST** : Lot 8 (ouvrir l'écosystème)
5. **Intégrations externes** : Lot 12 (gain de productivité)

### Long terme (12+ mois)
1. **Mobile App** : Lot 14 (usage terrain)
2. **Business Units** : Lot 18 (structuration entreprise)
3. **Portail Client** : Lot 13 (amélioration relation client)

### Axes stratégiques prioritaires
- **Automatisation** : Réduire le temps passé sur les tâches administratives
- **Données** : Exploiter la richesse des données pour anticiper et décider
- **UX** : Simplifier les workflows quotidiens pour améliorer l'adoption
- **Ouverture** : S'intégrer dans l'écosystème d'outils existant
- **Modernisation technique** : Anticiper les migrations PHP 8.5 / Symfony 8 dès maintenant en évitant les deprecations

---

## 📝 Notes importantes

- Les estimations sont données pour **1 développeur full-stack Symfony expérimenté**
- Les tests sont **inclus** dans les estimations
- La documentation technique est à **maintenir au fil de l'eau**
- Prévoir des **revues de code** et QA entre chaque lot
- Possibilité de **paralléliser** certains lots (ex: Lot 10 + Lot 11)
- **Prioriser selon le ROI business** : facturation > analytics > mobile
- Collecter du **feedback utilisateur** après chaque phase pour ajuster

---

**Dernière mise à jour** : 25 novembre 2025
**Prochaine revue** : Fin Q1 2025 (mars 2025)
