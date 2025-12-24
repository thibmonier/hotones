# 🗺️ HotOnes - Roadmap Produit

## 📋 Statut des Lots en Cours

### Lot 15.5 - Cohérence UX/UI Globale (EN COURS)
**Estimation:** 11.5 jours | **Avancement:** 35%

- ✅ Sprint 1: Standardisation pages de liste (Client, Employment Period, Invoice)
- 🔄 Phase 3: Standardisation formulaires (5/15 formulaires terminés)
- ⏳ Phase 4: Création composants manquants (Status Badge, Empty State, Stats Card KPI)
- ⏳ Phase 5: Documentation Design System
- ⏳ Phase 6: Amélioration Filter Panel

---

## 🔥 Priorité Haute

### Lot 16 - Dashboard Commercial & Analytics
**Estimation:** 5-7 jours

**Objectif:** Améliorer la visibilité sur les performances commerciales avec des indicateurs clés.

**Fonctionnalités:**
- Taux de conversion commerciaux (devis signés vs devis perdus)
- Graphique multi-axes :
  - Axe X: Temps sur l'année (mois par mois)
  - Axe Y1: Évolution du CA signé (courbe, k€)
  - Axe Y2: Volume de devis créés par mois (histogramme, k€)
- Correction du bloc "Devis en attente" sur le dashboard direction (alignement avec dashboard commercial)

**Impact:** Visibilité commerciale, aide à la décision

---

### Lot 17 - Renommage Contributeur → Collaborateur
**Estimation:** 1-2 jours

**Objectif:** Harmoniser la terminologie dans toute l'application.

**Tâches:**
- Renommer "contributeur" par "collaborateur" dans tous les templates
- Mettre à jour les labels de formulaires
- Mettre à jour la navigation et les breadcrumbs
- Garder l'entité `Contributor` en base pour éviter les régressions

**Impact:** Clarté terminologique, alignement métier

---

### Lot 18 - Liste des Projets - Filtres & KPIs Avancés
**Estimation:** 3-4 jours

**Objectif:** Enrichir la liste des projets avec des filtres avancés et des indicateurs financiers.

**Fonctionnalités:**

**Filtres supplémentaires:**
- Projets ouverts et actifs entre 2 dates (année courante par défaut)
- Type de projet (forfait/régie)
- Statut (actif, terminé, archivé, en attente)
- Technologie
- Catégorie de service
- Pagination: 20, 50, 100 résultats par page

**KPIs en entête de page (sur période filtrée):**
- Chiffre d'affaires total
- Marge brute (€ et %)
  - Formule: `CA - (Achats + Dépenses)`
- Taux journalier moyen réel (TJM réel)
- Coût homme total
- Marge nette (€ et %)
  - Formule: `(Marge brute - Coût homme) / CA * 100`
- Somme totale des achats

**Impact:** Vision financière globale, filtrage avancé, aide à la décision

---

## 🎯 Priorité Moyenne

### Lot 19 - Détail Projet - Métriques & Graphiques
**Estimation:** 4-5 jours

**Objectif:** Enrichir la vue détaillée d'un projet avec des métriques et visualisations avancées.

**Encarts de chiffres:**
- Temps passé / Temps total à passer (avec RAF - Reste À Faire)
- Budget consommé / Budget total
- Somme des coûts du projet
- Marge brute en euros avec :
  - Tendance (↗ ↘)
  - Badge coloré selon performance :
    - 🟢 Vert: > 25%
    - 🟠 Orange: 15-25%
    - 🔴 Rouge: < 15%

**Graphiques:**

1. **Consommation du projet dans le temps** (semaines ou mois):
   - Ligne horizontale: Budget total
   - Courbe: Budget consommé (réel)
   - Courbe: Budget prévisionnel à consommer

2. **Répartition budgétaire** (donut):
   - Marge
   - Achats
   - Coût homme

**Impact:** Pilotage projet, anticipation dérapages, visibilité rentabilité

---

## 🔧 Technique & Qualité

### Lot 20 - Pages d'Erreur Personnalisées ✅ TERMINÉ
**Estimation:** 1 jour | **Réalisé:** 23 décembre 2025

**Réalisations:**
- ✅ Pages d'erreur personnalisées (404, 403, 500, générique)
- ✅ Design cohérent avec le thème Skote
- ✅ Mise en scène humoristique de "Unit 404"
- ✅ Controller de test pour environnement dev (`/test-errors`)
- ✅ Documentation complète (`docs/error-pages.md`)
- ✅ Fallback générique pour toutes les autres erreurs

---

### Lot 21 - Augmentation Couverture Tests
**Estimation:** 5-7 jours (progressif)

**Objectif:** Atteindre 80% de couverture de tests automatisés

**Stratégie:**
- Prioriser les tests sur les features critiques (facturation, timesheet, profitabilité)
- Tests unitaires sur les services métier
- Tests d'intégration sur les repositories
- Tests fonctionnels sur les controllers critiques
- Tests API sur les endpoints publics

**Outils:**
- PHPUnit pour tests unitaires/intégration
- Infection pour mutation testing
- Deptrac pour architecture

---

### Lot 22 - Intégration CRM
**Estimation:** 8-10 jours

**Objectif:** Synchroniser les données clients/contacts avec des CRM du marché.

**Périmètre:**
- Définir les CRM cibles (HubSpot, Salesforce, Pipedrive, etc.)
- API de synchronisation bidirectionnelle
- Mapping des champs Client/Contact
- Gestion des conflits de synchronisation
- Interface de configuration CRM dans l'admin

**Impact:** Centralisation données, réduction saisie manuelle

---

## 🚀 Priorité Basse

### Lot 23 - Application Mobile PWA
**Estimation:** 10-15 jours

**Objectif:** Créer une PWA pour la saisie de temps en mobilité.

**MVP - Fonctionnalités:**
- Authentification avec compte HotOnes existant
- Saisie de temps
- Visualisation des temps passés sur la semaine
- Visualisation des temps restants à passer (RAF)
- Mode hors-ligne avec synchronisation

**Évolutions futures:**
- Notifications push
- Validation de temps
- Demandes de congés
- Consultation de planning

**Stack technique:**
- Progressive Web App (PWA)
- Service Workers pour mode hors-ligne
- API REST existante de HotOnes
- Framework: à définir (React, Vue, ou Twig SSR)

**Impact:** Mobilité équipe, saisie temps facilitée, adoption accrue

---

## 📊 Vue d'ensemble

| Lot | Priorité | Estimation | Statut |
|-----|----------|------------|--------|
| Lot 15.5 - UX/UI Globale | Haute | 11.5j | 🔄 35% |
| Lot 16 - Dashboard Commercial | Haute | 5-7j | ⏳ À planifier |
| Lot 17 - Renommage Collaborateur | Haute | 1-2j | ⏳ À planifier |
| Lot 18 - Liste Projets KPIs | Haute | 3-4j | ⏳ À planifier |
| Lot 19 - Détail Projet Graphiques | Moyenne | 4-5j | ⏳ À planifier |
| Lot 20 - Pages Erreur | Technique | 1j | ✅ Terminé |
| Lot 21 - Couverture Tests | Technique | 5-7j | ⏳ À planifier |
| Lot 22 - Intégration CRM | Technique | 8-10j | ⏳ À planifier |
| Lot 23 - App Mobile PWA | Basse | 10-15j | ⏳ À planifier |

**Total estimé:** ~50-65 jours
**Réalisé:** 1 jour (Lot 20)

---

## 🎯 Prochaines Étapes Recommandées

1. **Court terme (1-2 semaines):**
   - Finaliser Lot 15.5 (Phase 3-6)
   - Lancer Lot 17 (Renommage Collaborateur) - Quick Win
   - Démarrer Lot 16 (Dashboard Commercial) - Haute valeur métier

2. **Moyen terme (1 mois):**
   - Lot 18 (Liste Projets KPIs)
   - Lot 19 (Détail Projet Graphiques)

3. **Long terme (2-3 mois):**
   - Lot 21 (Tests - progressif)
   - Lot 22 (CRM)
   - Lot 23 (PWA)

---

**Dernière mise à jour:** 2025-12-23
