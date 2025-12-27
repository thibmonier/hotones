# 🚀 État d'avancement

> **Dernière mise à jour :** 27 décembre 2025

## Liens
- Roadmap unifiée: [ROADMAP.md](../ROADMAP.md)
- Exécution 2025: [docs/execution-plan-2025.md](./execution-plan-2025.md)
- Fonctionnalités: [docs/features.md](./features.md)

## Légende
- ✅ Terminé
- 🔄 En cours
- 🔲 À faire

## Définition de Done (DoD)
- Fonctionnalités validées métier
- Tests unitaires, fonctionnels et E2E au vert en CI
- Documentation mise à jour
- Revue de code effectuée

## ✅ Lots terminés (5/35)

### Infrastructure & Base
- Setup Docker (PHP 8.4, Nginx, MariaDB 11.4)
- Entities Doctrine + migrations (35+ migrations)
- Authentification Symfony Security avec hiérarchie de rôles
- 2FA Google Authenticator (scheb/2fa-bundle)
- Templates Bootstrap 5 (Skote theme) + Webpack Encore
- Tests automatisés: unitaires, intégration, fonctionnels et E2E (Panther)
- CI GitHub Actions: PHPUnit + qualité (PHPStan, PHP CS Fixer, PHPCS, Deptrac)
- Mutation testing (Infection)

### ✅ Lot 7: Pages d'Erreur Personnalisées
- Pages 404, 403, 500 avec design cohérent
- Mise en scène humoristique "Unit 404"
- Controller de test pour environnement dev

### ✅ Lot 2: Saisie des Temps
- TimesheetController complet avec grille hebdomadaire
- Compteur de temps start/stop (RunningTimer)
- Sélection projet → tâche → sous-tâche en cascade
- Vue calendrier mensuel
- Interface "Mes temps" personnalisée
- Export PDF des timesheets
- Auto-save et validation

### ✅ Lot 3: Dashboard Analytique
- Analytics/DashboardController avec DashboardReadService
- Cartes KPIs (CA, Marge, Taux de marge, Projets actifs)
- Graphiques d'évolution temporelle (Chart.js)
- Répartition par type de projet
- Top contributeurs
- Filtres dynamiques (période, année, mois, trimestre)
- Worker de recalcul asynchrone (RecalculateMetricsMessage)
- Scheduler automatique quotidien
- Export Excel (ExcelExportService)
- Prédictions analytiques (Analytics/PredictionsController)
- Modèle en étoile (FactProjectMetrics, FactStaffingMetrics, dimensions)

### ✅ Lot 11: Dashboard Commercial
- SalesDashboardController avec KPIs commerciaux
- Nombre de devis en attente
- CA signé sur période
- Taux de conversion (devis signés vs perdus)
- Graphique d'évolution du CA signé (mensuel)
- Filtres par année, utilisateur et rôle
- Export PDF des KPIs commerciaux

### ✅ Lot 12: Renommage Contributeur → Collaborateur
- Renommage complet dans tous les templates (158 occurrences)
- 0 occurrence restante de "contributeur"
- Labels de formulaires harmonisés
- Navigation et breadcrumbs mis à jour
- Entité Contributor conservée (pas de régression)

### Autres fonctionnalités implémentées
- CRUD complets pour entités de configuration (Technologies, Catégories, Profils)
- Dashboard staffing avec taux de staffing et TACE
- Gestion des périodes d'emploi avec relation profils
- Upload et gestion d'avatars
- Création automatique des tâches par défaut (AVV, Non-vendu)
- Listing projets avec filtres et badges (Type, Interne/Client)
- Pages d'erreur personnalisées (404, 403, 500)
- Components Twig réutilisables (page_header, breadcrumb, filter_panel, pagination, data_table, form_buttons)

## 🔄 En cours (1/35)

### 🔄 Lot 9: Cohérence UX/UI Globale (35% terminé)
**Terminé:**
- ✅ Sprint 1: Standardisation pages de liste (Client, Employment Period, Invoice)
- ✅ Composants réutilisables (page_header, breadcrumb, filter_panel, pagination, data_table, form_buttons, button, card_section)

**En cours:**
- 🔄 Phase 3: Standardisation formulaires (5/15 formulaires terminés)

**À faire:**
- ⏳ Phase 4: Création composants manquants (Status Badge, Empty State, Stats Card KPI)
- ⏳ Phase 5: Documentation Design System
- ⏳ Phase 6: Amélioration Filter Panel

---

## ⏳ Prochains lots prioritaires

Référence complète: [ROADMAP.md](../ROADMAP.md)

### Court terme (Q1 2025)
1. **Lot 9**: Finaliser Cohérence UX/UI (65% restant) - 7-8 jours
2. **Lot 6**: Conformité RGPD 🔴 (URGENT) - 35-37 jours
   - Obligation légale depuis 2018
   - Sanctions jusqu'à 20M€ ou 4% du CA

### Moyen terme (Q2 2025)
3. **Lot 1**: CRUD Entités Principales - 8-10 jours
4. **Lot 13**: Liste Projets - Filtres & KPIs Avancés - 3-4 jours
5. **Lot 14**: Détail Projet - Métriques & Graphiques - 4-5 jours
6. **Lot 5**: Module de Facturation - 10-12 jours

### Fonctionnalités additionnelles à implémenter
- Lot 4: Gestion Compte Utilisateur (sessions actives)
- Lot 8: Améliorations UX/UI de Base
- Lot 10: Notifications & Alertes (déclencheurs manquants)
- Lot 15: Rapports & Exports professionnels
- Lot 17: Signature Électronique (Yousign)
- Lot 18: API REST complète
