# 📊 Dashboard de Suivi Commercial

## Objectif

Le dashboard de suivi commercial permet aux commerciaux et chefs de projet de suivre en temps réel la performance commerciale de l'agence. Il offre une vue d'ensemble des devis, du chiffre d'affaires et de leur évolution sur une période donnée.

## Accès

- **URL**: `/sales-dashboard`
- **Route**: `sales_dashboard_index`
- **Menu**: Commerce → Performances commerciales
- **Rôle requis**: `ROLE_CHEF_PROJET`

## Fonctionnalités

### 1. KPIs Principaux

Le dashboard affiche 4 indicateurs clés de performance :

#### a) Devis en attente de signature
- **Description**: Nombre total de devis avec le statut "À signer" (`a_signer`)
- **Calcul**: `COUNT(*) WHERE status = 'a_signer'`
- **Utilité**: Identifier rapidement les devis qui nécessitent un suivi commercial

#### b) CA Signé sur l'année
- **Description**: Chiffre d'affaires total des devis signés sur l'année sélectionnée
- **Calcul**: `SUM(totalAmount) WHERE status IN ('signe', 'gagne', 'termine') AND validatedAt BETWEEN start_year AND end_year`
- **Utilité**: Mesurer la performance commerciale annuelle

#### c) CA Moyen par Mois
- **Description**: Moyenne mensuelle du CA signé
- **Calcul**: `CA Signé / 12`
- **Utilité**: Évaluer la régularité de l'activité commerciale

#### d) Nombre Total de Devis
- **Description**: Nombre total de devis tous statuts confondus
- **Calcul**: `COUNT(*)`
- **Utilité**: Vision globale du volume d'activité commerciale

### 2. Évolution du CA Signé

#### Graphique Mensuel
- **Type**: Graphique linéaire (Chart.js)
- **Période**: Année sélectionnée (12 mois)
- **Données**: CA signé par mois
- **Calcul**: `SUM(totalAmount) GROUP BY MONTH(validatedAt)`
- **Fonctionnalités**:
  - Affichage des valeurs au survol
  - Formatage des montants en euros
  - Zone remplie sous la courbe pour meilleure lisibilité

### 3. Devis Récents

#### Liste des 5 Derniers Devis
- **Affichage**:
  - Numéro du devis
  - Nom du projet associé
  - Statut (avec badge coloré)
  - Montant total
- **Lien**: Cliquable vers la fiche détaillée du devis
- **Bouton**: "Voir tous les devis" vers la liste complète

### 4. Répartition du CA par Statut

#### Tableau Détaillé
Affiche pour chaque statut de devis :
- **Statut**: Badge coloré avec le label
- **Nombre de devis**: Compteur
- **CA Total**: Somme des montants (en euros)
- **CA Moyen**: Montant moyen par devis

#### Statuts Disponibles
| Statut | Valeur | Couleur Badge | Description |
|--------|--------|---------------|-------------|
| À signer | `a_signer` | Warning (jaune) | Devis en attente de signature |
| Gagné | `gagne` | Success (vert) | Devis gagné, à signer |
| Signé | `signe` | Success (vert) | Devis signé par le client |
| Perdu | `perdu` | Danger (rouge) | Devis perdu |
| Terminé | `termine` | Success (vert) | Projet livré et terminé |
| Standby | `standby` | Secondary (gris) | En attente, suspendu |
| Abandonné | `abandonne` | Secondary (gris) | Abandonné par le client ou l'agence |

#### Total
- Ligne de total en bas du tableau avec agrégation de tous les statuts
- Calcul du CA moyen global

### 5. Filtre par Année

- **Sélecteur**: Liste déroulante des années disponibles
- **Années affichées**: Basées sur les dates de création des devis existants
- **Année par défaut**: Année en cours
- **Comportement**: Rechargement automatique du dashboard à la sélection

## Architecture Technique

### Controller
- **Classe**: `SalesDashboardController`
- **Namespace**: `App\Controller`
- **Route**: `/sales-dashboard`

### Repository
Les méthodes suivantes ont été ajoutées à `OrderRepository` :

```php
// Compte les devis par statut
public function countByStatus(string $status): int

// Calcule le CA total par statut
public function getTotalAmountByStatus(string $status): float

// Obtient les statistiques par statut (count + CA)
public function getStatsByStatus(): array

// Calcule le CA signé sur une période
public function getSignedRevenueForPeriod(\DateTimeInterface $startDate, \DateTimeInterface $endDate): float

// Obtient l'évolution mensuelle du CA signé
public function getRevenueEvolution(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array

// Obtient les devis récents
public function getRecentOrders(int $limit = 10): array
```

### Template
- **Fichier**: `templates/sales_dashboard/index.html.twig`
- **Layout**: Étend `layouts/base.html.twig`
- **Librairie graphique**: Chart.js 4.4.0 (CDN)

## Fonctionnalités Avancées

### Taux de Conversion (Implémenté ✅)
- **KPI ajouté**: Affiche le pourcentage de devis signés par rapport aux devis créés
- **Calcul**: (Devis signés / Total devis créés) × 100
- **Code couleur**:
  - Vert (≥ 50%)
  - Orange (≥ 30%)
  - Rouge (< 30%)

### Filtres par Commercial/Chef de Projet (Implémenté ✅)
- **Filtre par rôle**: Sélection entre Commercial et Chef de projet
- **Filtre par utilisateur**: Liste déroulante des utilisateurs
- **Bouton reset**: Réinitialisation de tous les filtres
- **Persistance**: Les filtres sont appliqués à tous les KPIs et statistiques

### Comparaison Annuelle (Implémenté ✅)
- **Affichage**: Comparaison automatique avec l'année précédente
- **Métriques comparées**:
  - Chiffre d'affaires (montant et %)
  - Nombre de devis (nombre et %)
  - Taux de conversion (points de différence)
- **Indicateurs visuels**: Flèches et couleurs (vert/rouge) selon l'évolution

### Export PDF (Implémenté ✅)
- **Bouton d'export**: Accessible depuis l'en-tête du dashboard
- **Contenu du PDF**:
  - KPIs principaux
  - Comparaison annuelle
  - Tableau de répartition par statut
- **Respect des filtres**: Le PDF exporte les données filtrées
- **Bibliothèque**: Dompdf

## Améliorations Futures

### Priorité Haute
- [x] Ajout de filtres par commercial/chef de projet ✅
- [x] Export PDF du dashboard ✅
- [x] Taux de conversion (devis signés / devis créés) ✅
- [x] Évolution comparative (année N vs N-1) ✅

### Priorité Moyenne
- [ ] Graphique de répartition par type de contrat (forfait/régie)
- [ ] Top 5 des projets par CA
- [ ] Prévisionnel du CA (pipeline)
- [ ] Durée moyenne de signature d'un devis

### Priorité Basse
- [ ] Notifications sur dépassement d'objectifs
- [ ] Comparaison multi-années
- [ ] Export Excel des données
- [ ] Graphiques interactifs avancés (drill-down)

## Données de Test

Pour générer des données de test variées, utiliser la commande :

```bash
php bin/console doctrine:fixtures:load --group=orders
```

## Notes Techniques

### Performance
- Les requêtes utilisent des agrégations SQL natives (GROUP BY, SUM, COUNT)
- Index existants sur `orders.status`, `orders.created_at` et `orders.validated_at`
- Migration Version20251117000000 : Ajout d'index sur `validated_at` pour améliorer les performances des requêtes de période
- Pas de cache pour l'instant (données temps réel)

### Sécurité
- Accès restreint au rôle `ROLE_CHEF_PROJET`
- Pas de données sensibles exposées
- Validation des paramètres d'année (liste blanche)

### Maintenance
- Les statuts sont définis dans l'enum `OrderStatus`
- Extension Twig `OrderExtension` pour centraliser la logique des badges de statut
  - Filtre `order_status_label` : Obtient le label d'un statut
  - Filtre `order_status_badge_class` : Obtient la classe CSS Bootstrap pour un badge
  - Fonction `order_status_badge` : Rend un badge HTML complet
- Template responsive (Bootstrap 5)

### Améliorations Récentes (2025-11-17)

#### Phase 1 - Optimisations de base
- ✅ Création de l'extension Twig `OrderExtension` pour gérer les badges de statut
- ✅ Refactoring du template pour utiliser les helpers Twig (réduction de duplication de code)
- ✅ Ajout d'index sur `orders.validated_at` pour améliorer les performances (Migration Version20251117000000)
- ✅ Code plus maintenable et DRY (Don't Repeat Yourself)

#### Phase 2 - Fonctionnalités prioritaires
- ✅ **Taux de conversion**: Nouveau KPI affichant le pourcentage de devis signés
- ✅ **Comparaison annuelle**: Section dédiée comparant l'année N avec N-1 (CA, nombre de devis, taux de conversion)
- ✅ **Filtres utilisateur**: Filtrage par commercial ou chef de projet avec liste déroulante des utilisateurs
- ✅ **Export PDF**: Génération de rapport PDF avec toutes les statistiques et respect des filtres
- ✅ Installation de Dompdf pour la génération de PDF
- ✅ Création du template PDF dédié (`templates/sales_dashboard/pdf.html.twig`)
- ✅ Mise à jour de toutes les méthodes du repository pour supporter les filtres
- ✅ Interface JavaScript pour la gestion des filtres avec bouton de réinitialisation
