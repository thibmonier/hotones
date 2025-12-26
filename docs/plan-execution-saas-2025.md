# 🚀 Plan d'Exécution 2025 - Fast-Track to SAAS

> **Objectif stratégique :** Transformer HotOnes en plateforme SAAS Multi-Tenant (Lot 23)
>
> **Dernière mise à jour :** 26 décembre 2025

## 🎯 Vision stratégique

### Pourquoi le SAAS est prioritaire ?

La **Transformation SAAS Multi-Tenant** (Lot 23) est l'objectif stratégique #1 car elle permet :

✅ **Nouveau business model** : Vente de HotOnes en mode SAAS à d'autres agences
✅ **Scalabilité** : Une instance, plusieurs sociétés clientes
✅ **Revenus récurrents** : Abonnements mensuels/annuels (MRR/ARR)
✅ **Différenciation marché** : Solution unique pour agences web
✅ **Valorisation entreprise** : Modèle SAAS = valorisation x5-10

### Contraintes identifiées

⚠️ **Complexité technique** : 45-55 jours de dev, 45 entités à modifier
⚠️ **Risque de régression** : Transformation profonde de l'architecture
⚠️ **Besoin de fondations solides** : Code stable + tests robustes avant transformation

---

## 📋 Stratégie d'exécution

### Principe : Fast-Track to SAAS

1. **Phase Préparation** : Consolider les fondations (3-4 mois)
2. **Phase SAAS** : Transformation SAAS Multi-Tenant (2-3 mois)
3. **Phase Enrichissement** : Features avancées post-SAAS (3-6 mois)
4. **Phase Conformité** : Obligations légales (RGPD, e-facturation)

### Arbitrages clés

| Décision | Justification |
|----------|---------------|
| ✅ **SAAS avant RGPD** | RGPD peut être fait après (obligation depuis 2018 mais risque contrôle faible) |
| ✅ **Tests avant SAAS** | Sécuriser la transformation avec bonne couverture de tests |
| ✅ **UX/UI avant SAAS** | Base propre et cohérente pour faciliter la transformation |
| ❌ **Mobile après SAAS** | Lots 29-32 reportés après SAAS (non bloquants) |
| ❌ **Analytics prédictif après SAAS** | Lot 19 reporté (nécessite données historiques) |

---

## 🗓️ Planning détaillé

### 📊 Vue d'ensemble

| Phase | Duration | Période | Objectif |
|-------|----------|---------|----------|
| **Phase 0** | 2 sem | Jan 2025 | Finalisation en cours |
| **Phase 1** | 8 sem | Fév-Mars 2025 | Fondations techniques |
| **Phase 2** | 3 sem | Avril 2025 | Sécurisation (Tests) |
| **🚀 Phase 3** | **11 sem** | **Mai-Juil 2025** | **TRANSFORMATION SAAS** ⭐ |
| **Phase 4** | 4 sem | Août 2025 | Post-SAAS & Stabilisation |
| **Phase 5** | 12 sem | Sept-Nov 2025 | Enrichissement SAAS |
| **Phase 6** | 16 sem | Déc 2025-Mars 2026 | Conformité & Professionnalisation |

**Objectif : SAAS opérationnel en Juillet 2025** (7 mois)

---

## 📅 Phase 0 : Finalisation en cours (2 semaines)

**Période :** 06-17 janvier 2025

### Objectif
Finaliser le Lot 9 (Cohérence UX/UI Globale) déjà en cours à 35%.

### Lots
- **Lot 9** : Cohérence UX/UI Globale 🔄 (finaliser 65% restant) - **7-8 jours**

### Livrables
- ✅ Standardisation de tous les formulaires (15/15)
- ✅ Composants Twig réutilisables (`page_header`, `data_table`, `filter_panel`, `pagination`)
- ✅ JavaScript actions en masse (`mass-actions.js`)
- ✅ Documentation Design System
- ✅ Filter Panel amélioré

### Jalon
🎯 **Base UX/UI cohérente et maintenable** - 17 janvier 2025

---

## 📅 Phase 1 : Fondations techniques (8 semaines)

**Période :** 20 janvier - 14 mars 2025

### Objectif
Consolider les fondations techniques nécessaires avant la transformation SAAS.

### Lots (dans l'ordre)

#### 1.1 Quick Win terminologique (1-2 jours)
- **Lot 12** : Renommage Contributeur → Collaborateur

**Justification :** Quick win, clarté métier, prépare les templates pour SAAS

#### 1.2 Fondation métier (5-7 jours)
- **Lot 2** : Saisie des Temps (interface complète)

**Justification :** Feature critique pour valorisation SAAS, utilisée quotidiennement

#### 1.3 Analytics de base (7-10 jours)
- **Lot 3** : Dashboard Analytique (KPIs + worker)

**Justification :** Modèle en étoile déjà créé, nécessaire pour dashboards par BU post-SAAS

#### 1.4 Finitions CRUD (3-4 jours)
- **Lot 1** : CRUD Entités Principales (finalisation)
  - Filtres avancés liste projets
  - Prévisualisation PDF des devis

**Justification :** Compléter les entités de base avant transformation SAAS

#### 1.5 Facturation de base (10-12 jours)
- **Lot 5** : Module de Facturation

**Justification :** Feature critique pour SAAS (facturation clients SAAS), prépare entité Invoice pour e-facturation

#### 1.6 Dashboards avancés (12-16 jours)
- **Lot 11** : Dashboard Commercial (5-7j)
- **Lot 13** : Liste Projets - Filtres & KPIs (3-4j)
- **Lot 14** : Détail Projet - Métriques & Graphiques (4-5j)

**Justification :** Enrichir l'offre SAAS avec dashboards professionnels

### Durée totale Phase 1
**38-51 jours** (~8-10 semaines)

### Livrables Phase 1
- ✅ Terminologie harmonisée ("collaborateur")
- ✅ Saisie de temps complète et ergonomique
- ✅ Dashboard analytique avec worker de calcul
- ✅ Module de facturation fonctionnel
- ✅ Dashboards avancés (commercial, projets)
- ✅ CRUD complet et finalisé

### Jalon
🎯 **Fondations techniques solides** - 14 mars 2025

---

## 📅 Phase 2 : Sécurisation (3 semaines)

**Période :** 17 mars - 4 avril 2025

### Objectif
Sécuriser le code existant avec une bonne couverture de tests AVANT la transformation SAAS.

### Lots

#### 2.1 Augmentation couverture tests (15-20 jours - partiel)
- **Lot 33** : Augmentation Couverture Tests (focus features critiques)

**Périmètre prioritaire :**
- Tests unitaires sur services métier (MetricsCalculationService, etc.)
- Tests d'intégration sur repositories critiques (ProjectRepository, OrderRepository, etc.)
- Tests fonctionnels sur controllers CRUD
- Tests E2E sur parcours principaux (création projet, saisie temps)

**Objectif de couverture :** 60-70% (suffisant pour sécuriser SAAS)

### Durée totale Phase 2
**15-20 jours** (~3-4 semaines)

### Livrables Phase 2
- ✅ Couverture tests unitaires : 60%+
- ✅ Couverture tests intégration : 50%+
- ✅ Couverture tests fonctionnels : 40%+
- ✅ Tests E2E sur parcours critiques
- ✅ CI/CD avec tests automatiques

### Jalon
🎯 **Code sécurisé par tests robustes** - 4 avril 2025

---

## 📅 Phase 3 : TRANSFORMATION SAAS ⭐ (11 semaines)

**Période :** 7 avril - 27 juin 2025

### Objectif stratégique
🚀 **Transformer HotOnes en plateforme SAAS Multi-Tenant**

### Lot unique
- **Lot 23** : Transformation SAAS Multi-Tenant ⭐ (45-55 jours)

### Plan de migration (9 sous-phases)

#### 3.1 Préparation & Design (5-7 jours)
- Architecture cible détaillée
- Design entités (Company, BusinessUnit)
- Stratégie de migration données
- Documentation technique

**Livrable :** Spécifications techniques complètes

#### 3.2 Database & Models (15-18 jours)
- Modification des 45 entités (ajout `company_id`)
- Migrations Doctrine
- Entités Company et BusinessUnit
- Soft delete avec CASCADE

**Livrable :** Schéma BDD multi-tenant

#### 3.3 Authentication & Context (5-6 jours)
- JWT avec claim `company_id`
- Service CompanyContext
- Voters (CompanyVoter, BusinessUnitVoter, AdminVoter)
- Middleware de vérification tenant

**Livrable :** Authentification multi-tenant sécurisée

#### 3.4 Repository Scoping (10-12 jours)
- Scoping explicite sur 36 repositories
- Méthodes findBy* avec company_id
- Protection contre fuites de données

**Livrable :** Repositories isolés par tenant

#### 3.5 Controllers & Services (8-10 jours)
- Injection CompanyContext dans controllers
- Adaptation des services métier
- Validation company_id systématique

**Livrable :** Application isolée par tenant

#### 3.6 API & Frontend (5-6 jours)
- API multi-tenant (JWT avec company_id)
- Interface de gestion Company (CRUD)
- Sélection Company au login (si multi-company)

**Livrable :** API et UI multi-tenant

#### 3.7 Business Units (4-5 jours)
- Hiérarchie BusinessUnit
- Rattachement contributeurs/projets/clients
- Dashboards par BU (isolation)

**Livrable :** Structure BU hiérarchique

#### 3.8 Testing & Validation (7-8 jours)
- Tests d'isolation entre tenants (critique)
- Tests de sécurité (fuites de données)
- Tests de performance (index company_id)
- Tests de migration (rollback plan)

**Livrable :** SAAS sécurisé et validé

#### 3.9 Deployment & Monitoring (3-4 jours)
- Migration données production
- Création Company par défaut
- Monitoring multi-tenant
- Documentation déploiement

**Livrable :** SAAS en production

### Durée totale Phase 3
**45-55 jours** (~9-11 semaines)

### Livrables Phase 3
- ✅ Architecture SAAS Multi-Tenant opérationnelle
- ✅ Isolation complète des données par Company
- ✅ Business Units hiérarchiques
- ✅ Dashboards par BU
- ✅ API multi-tenant sécurisée
- ✅ Tests d'isolation complets
- ✅ Migration données production
- ✅ Documentation complète

### Jalon MAJEUR
🚀 **HOTONES SAAS OPÉRATIONNEL** - 27 juin 2025

---

## 📅 Phase 4 : Post-SAAS & Stabilisation (4 semaines)

**Période :** 30 juin - 25 juillet 2025

### Objectif
Stabiliser le SAAS, corriger bugs post-migration, enrichir fonctionnalités BU.

### Lots

#### 4.1 Business Units avancées (6-8 jours)
- **Lot 24** : Business Units Post-SAAS
  - Objectifs et suivi avancés (budget, alertes, scoring)
  - Workflows inter-BU (transfert, partage, facturation)
  - Analytics avancées (taux utilisation, rentabilité)
  - Gamification (classement BU, badges)

#### 4.2 Notifications & Alertes (4-5 jours)
- **Lot 10** : Notifications & Alertes
  - Déclencheurs automatiques (budget, validation, paiement)
  - Canaux (in-app, email, Slack/Discord)
  - Préférences utilisateur

#### 4.3 Améliorations UX/UI (5-6 jours)
- **Lot 8** : Améliorations UX/UI de Base
  - Menu latéral adapté
  - Recherche globale
  - Tableaux de données améliorés
  - Formulaires optimisés

### Durée totale Phase 4
**15-19 jours** (~3-4 semaines)

### Livrables Phase 4
- ✅ SAAS stabilisé et sans bugs critiques
- ✅ Business Units avec features avancées
- ✅ Notifications automatiques
- ✅ UX/UI optimisée

### Jalon
🎯 **SAAS stable et enrichi** - 25 juillet 2025

---

## 📅 Phase 5 : Enrichissement SAAS (12 semaines)

**Période :** 28 juillet - 17 octobre 2025

### Objectif
Enrichir l'offre SAAS avec features différenciantes et APIs.

### Lots

#### 5.1 Rapports & Exports (6-7 jours)
- **Lot 15** : Rapports & Exports
  - Rapports multi-format (PDF, Excel, CSV)
  - Templates personnalisables
  - Génération planifiée et envoi auto

#### 5.2 API REST (8-10 jours)
- **Lot 18** : API REST
  - Endpoints complets (projets, timesheets, orders, metrics, etc.)
  - Authentification JWT multi-tenant
  - Documentation OpenAPI/Swagger
  - SDKs (JS, Python)

#### 5.3 Signature Électronique (10-11 jours)
- **Lot 17** : Signature Électronique (Yousign)
  - Signature devis/contrats
  - Workflow automatisé
  - Journal d'audit

#### 5.4 Intégrations Externes (15-20 jours)
- **Lot 21** : Intégrations Externes
  - Jira / ClickUp / Notion
  - Slack / Microsoft Teams
  - Google Calendar / Outlook
  - Logiciels comptables
  - GitLab / GitHub

#### 5.5 Portail Client (12-15 jours)
- **Lot 22** : Portail Client
  - Dashboard client
  - Suivi projets temps réel
  - Support & tickets
  - Validation livrables

### Durée totale Phase 5
**51-63 jours** (~10-13 semaines)

### Livrables Phase 5
- ✅ Rapports professionnels
- ✅ API REST complète et documentée
- ✅ Signature électronique des devis
- ✅ Intégrations écosystème (Jira, Slack, etc.)
- ✅ Portail client autonome

### Jalon
🎯 **SAAS enrichi et différenciant** - 17 octobre 2025

---

## 📅 Phase 6 : Conformité & Professionnalisation (16 semaines)

**Période :** 20 octobre 2025 - 6 février 2026

### Objectif
Conformité légale et professionnalisation de l'offre SAAS.

### Lots

#### 6.1 Conformité RGPD 🔴 (35-37 jours)
- **Lot 6** : Conformité RGPD (obligation légale)
  - Registre des traitements
  - Droits des personnes (accès, rectification, effacement, etc.)
  - Politique de confidentialité
  - Gestion consentements
  - Audit trail
  - Purge automatique

**Justification :** Fait après SAAS car :
- Obligation depuis 2018 mais risque contrôle CNIL faible court terme
- SAAS prioritaire pour business model
- RGPD plus facile à implémenter sur architecture SAAS stabilisée

#### 6.2 Facturation Électronique 🔴 (25-27 jours)
- **Lot 16** : Facturation Électronique (obligation sept 2027)
  - Génération Factur-X (PDF + XML)
  - Intégration Chorus Pro
  - Réception factures fournisseurs
  - Archivage légal 10 ans

**Justification :** Anticiper obligation 2027 (18 mois d'avance)

#### 6.3 Analytics Prédictifs (12-15 jours)
- **Lot 19** : Analytics Prédictifs
  - Forecasting CA
  - Analyse risques projet
  - Prédiction de charge
  - Rentabilité prédictive

#### 6.4 Dashboard RH & Talents (8-10 jours)
- **Lot 20** : Dashboard RH & Talents
  - KPIs RH (turnover, absentéisme)
  - Gestion compétences
  - Revues annuelles
  - Onboarding

### Durée totale Phase 6
**80-89 jours** (~16-18 semaines)

### Livrables Phase 6
- ✅ Conformité RGPD complète
- ✅ Facturation électronique (Chorus Pro)
- ✅ Analytics prédictifs opérationnels
- ✅ Dashboard RH complet

### Jalon
🎯 **SAAS conforme et mature** - 6 février 2026

---

## 🎯 Jalons clés et dates butoirs

| Jalon | Date cible | Description |
|-------|------------|-------------|
| 🏁 **J1** | 17 janvier 2025 | UX/UI cohérente (Lot 9 finalisé) |
| 🏁 **J2** | 14 mars 2025 | Fondations techniques solides |
| 🏁 **J3** | 4 avril 2025 | Code sécurisé par tests (60%+ couverture) |
| 🚀 **J4** | **27 juin 2025** | **HOTONES SAAS OPÉRATIONNEL** ⭐ |
| 🏁 **J5** | 25 juillet 2025 | SAAS stable et enrichi (BU avancées) |
| 🏁 **J6** | 17 octobre 2025 | SAAS différenciant (API, intégrations) |
| 🏁 **J7** | 6 février 2026 | SAAS conforme et mature (RGPD, e-facturation) |

**Objectif stratégique atteint en 6 mois** : SAAS opérationnel le **27 juin 2025** 🚀

---

## 📊 Récapitulatif planning

### Par phase

| Phase | Lots | Durée | Période | Jalon |
|-------|------|-------|---------|-------|
| **Phase 0** | Lot 9 | 2 sem | Jan 2025 | UX/UI cohérente |
| **Phase 1** | Lots 12,2,3,1,5,11,13,14 | 8 sem | Fév-Mars 2025 | Fondations solides |
| **Phase 2** | Lot 33 (partiel) | 3 sem | Mars-Avr 2025 | Code sécurisé |
| **🚀 Phase 3** | **Lot 23 SAAS** ⭐ | **11 sem** | **Avr-Juin 2025** | **SAAS opérationnel** |
| **Phase 4** | Lots 24,10,8 | 4 sem | Juil 2025 | SAAS stable |
| **Phase 5** | Lots 15,18,17,21,22 | 12 sem | Juil-Oct 2025 | SAAS enrichi |
| **Phase 6** | Lots 6,16,19,20 | 16 sem | Oct 2025-Fév 2026 | SAAS conforme |

### Total estimé
- **Développement :** ~44 semaines (11 mois)
- **SAAS opérationnel :** Fin juin 2025 (6 mois)
- **SAAS mature :** Début février 2026 (14 mois)

---

## 🚧 Risques et mitigation

### Risques identifiés

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| **Tests insuffisants avant SAAS** | 🔴 Élevé | Moyenne | Phase 2 dédiée aux tests (3 sem) |
| **Dérive planning Phase 3 (SAAS)** | 🟠 Moyen | Moyenne | Buffer de 10j dans estimation (45-55j) |
| **Bugs post-migration SAAS** | 🟠 Moyen | Élevée | Phase 4 dédiée stabilisation (4 sem) |
| **Fondations instables** | 🔴 Élevé | Faible | Phase 1 complète (8 sem) avant SAAS |
| **Régression fonctionnelle** | 🟠 Moyen | Moyenne | Tests E2E + CI/CD automatique |
| **Fuite de données entre tenants** | 🔴 Élevé | Faible | Tests d'isolation exhaustifs (Phase 3.8) |
| **Performance dégradée** | 🟠 Moyen | Moyenne | Index company_id, monitoring, tests perf |

### Plan de contingence

**Si Phase 3 (SAAS) dépasse 11 semaines :**
1. Reporter Phase 5 (enrichissement) de 2-4 semaines
2. Prioriser stabilisation (Phase 4) avant enrichissement
3. Reporter lots non critiques (Lot 19, 20) après février 2026

**Si bugs critiques post-SAAS :**
1. Étendre Phase 4 (stabilisation) de 2 semaines
2. Mobiliser ressources additionnelles si besoin
3. Rollback plan documenté (retour version pre-SAAS)

---

## 💰 Investissement estimé

### Par phase (en jours de développement)

| Phase | Durée (jours) | Durée (semaines) | % du total |
|-------|---------------|------------------|------------|
| Phase 0 | 8 | 2 | 4% |
| Phase 1 | 44 | 9 | 20% |
| Phase 2 | 18 | 4 | 8% |
| **Phase 3 SAAS** | **50** | **10** | **23%** |
| Phase 4 | 17 | 3 | 8% |
| Phase 5 | 57 | 11 | 26% |
| Phase 6 | 84 | 17 | 38% |
| **TOTAL** | **220** | **44** | **100%** |

**Total investissement :** ~220 jours de développement (11 mois calendaires)

**Ventilation :**
- **Fondations + Préparation** : 70j (32%)
- **SAAS (Lot 23)** : 50j (23%)
- **Post-SAAS & Enrichissement** : 74j (34%)
- **Conformité** : 62j (28%)

---

## 🎯 KPIs de suivi

### KPIs techniques

| KPI | Cible | Mesure |
|-----|-------|--------|
| Couverture tests | 60%+ avant SAAS, 80%+ après | PHPUnit coverage |
| Temps de réponse | < 200ms (95th percentile) | APM monitoring |
| Bugs critiques | 0 en production | Sentry |
| Disponibilité | 99.9% | Uptime monitoring |

### KPIs business (post-SAAS)

| KPI | Cible 2025 | Mesure |
|-----|------------|--------|
| Clients SAAS pilotes | 2-3 | Abonnements actifs |
| MRR (Monthly Recurring Revenue) | 2 000-5 000€ | Facturation récurrente |
| Taux de rétention | > 90% | Renouvellements |
| NPS (Net Promoter Score) | > 50 | Enquête satisfaction |

### KPIs projet

| KPI | Cible | Mesure |
|-----|-------|--------|
| Respect planning | ±2 semaines | Suivi hebdomadaire |
| Vélocité | 10j/semaine (2j buffer) | Sprints |
| Qualité code | PHPStan level 3 clean | CI/CD |

---

## 📝 Notes importantes

### Hypothèses

- **1 développeur full-stack Symfony expérimenté** à temps plein
- Tests inclus dans les estimations
- Pas de congés/vacances significatifs durant Phase 3 (SAAS)
- Accès à expertise externe si besoin (architecture SAAS, sécurité)

### Revues et ajustements

- **Revue hebdomadaire** : Avancement, blocages, ajustements
- **Revue fin de phase** : Validation livrables, go/no-go phase suivante
- **Revue trimestrielle** : Ajustement roadmap, priorisation

### Décisions à valider

| Décision | Recommandation | À valider par |
|----------|----------------|---------------|
| SAAS avant RGPD | ✅ Oui (stratégique) | Direction |
| Couverture tests 60% suffisante | ✅ Oui (compromis vitesse/qualité) | Tech Lead |
| Reporter mobile après SAAS | ✅ Oui (non bloquant) | Direction |
| Budget externe si dépassement | ⚠️ À décider | Direction |

---

## 🚀 Actions immédiates (Janvier 2025)

### Semaine 1-2 (6-17 janvier)
1. ✅ Finaliser Lot 9 (Cohérence UX/UI) - 65% restant
2. ✅ Valider ce plan d'exécution avec la direction
3. ✅ Préparer environnement de tests (CI/CD, PHPUnit, Panther)

### Semaine 3 (20-24 janvier)
1. 🚀 Démarrer Lot 12 (Renommage Collaborateur) - Quick Win
2. 🚀 Démarrer Lot 2 (Saisie des Temps) immédiatement après

### Suivi
- **Weekly standup** : Lundi matin (15min)
- **Sprint review** : Fin de chaque lot
- **Monthly review** : Fin de chaque mois
- **Dashboard projet** : Notion, Jira ou Trello

---

## 📚 Documentation de référence

- **Roadmap unifiée :** [ROADMAP.md](../ROADMAP.md)
- **Plan transformation SAAS :** [docs/saas-multi-tenant-plan.md](./saas-multi-tenant-plan.md)
- **État d'avancement :** [docs/status.md](./status.md)
- **Conformité RGPD :** [docs/rgpd-compliance-feasibility.md](./rgpd-compliance-feasibility.md)
- **E-facturation & E-signature :** [docs/esignature-einvoicing-feasibility.md](./esignature-einvoicing-feasibility.md)

---

**Document créé :** 26 décembre 2025
**Objectif stratégique :** SAAS opérationnel le **27 juin 2025** 🚀
**Prochaine revue :** 31 janvier 2025
