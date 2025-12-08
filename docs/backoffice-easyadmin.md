# Backoffice EasyAdmin

## 📋 Vue d'ensemble

Le backoffice HotOnes utilise EasyAdmin pour gérer les entités de configuration de manière simple et efficace. Cette approche remplace les contrôleurs CRUD manuels par une interface d'administration standardisée.

## 🎯 Objectifs

- **Simplifier la maintenance** : Réduire le code répétitif pour les CRUD simples
- **Standardiser l'interface** : Look & feel cohérent pour tous les écrans d'administration
- **Accélérer le développement** : Fonctionnalités gratuites (filtres, exports, recherche, batch actions)
- **Séparer les préoccupations** : Backoffice technique vs. application métier

## 🚀 Installation

```bash
composer require easycorp/easyadmin-bundle
```

## 📦 Entités gérées

### Actuellement dans EasyAdmin

1. **Technologies** (`TechnologyCrudController`)
   - Nom, catégorie, couleur
   - Filtres : catégorie, actif/inactif
   - Affichage du nombre de projets associés

2. **Catégories de service** (`ServiceCategoryCrudController`)
   - Nom, description, couleur
   - Filtres : actif/inactif
   - Affichage du nombre de projets associés

3. **Profils métier** (`ProfileCrudController`)
   - Nom, description
   - TJM par défaut, CJM, coefficient de marge
   - Couleur, statut actif/inactif

4. **Compétences** (`SkillCrudController`)
   - Nom, catégorie, description
   - Filtres : catégorie, actif/inactif
   - Affichage du nombre de contributeurs

### À migrer ultérieurement

- Scheduler
- Notifications
- Paramètres généraux

## 🔗 Accès

- **URL** : `/backoffice`
- **Permissions** : `ROLE_ADMIN` requis
- **Menu** : Nouvelle entrée "Backoffice" dans le menu de gauche

## 📁 Structure des fichiers

```
src/Controller/Admin/
├── BackofficeDashboardController.php  # Dashboard principal
├── TechnologyCrudController.php       # CRUD Technologies
├── ServiceCategoryCrudController.php  # CRUD Catégories de service
├── ProfileCrudController.php          # CRUD Profils métier
└── SkillCrudController.php            # CRUD Compétences
```

## 🎨 Fonctionnalités EasyAdmin

### Incluses par défaut
- ✅ Recherche full-text
- ✅ Filtres avancés
- ✅ Tri des colonnes
- ✅ Pagination (25 éléments par page)
- ✅ Actions CRUD (Create, Read, Update, Delete)
- ✅ Gestion des permissions par action
- ✅ Support multilingue (français)

### Personnalisations possibles
- Exports CSV/Excel (via packages additionnels)
- Actions batch personnalisées
- Widgets dashboard
- Thème personnalisé

## 🔄 Prochaines étapes

### Phase 2 - Migration complète
1. Migrer les écrans restants (Scheduler, Notifications)
2. Supprimer les anciens contrôleurs CRUD
3. Supprimer les anciens templates Twig
4. Mettre à jour les tests

### Phase 3 - Améliorations
1. Ajouter les exports CSV natifs dans EasyAdmin
2. Créer un vrai dashboard avec widgets
3. Ajouter des actions batch (activation/désactivation multiple)
4. Intégrer le thème Skote (optionnel)

## 📝 Notes techniques

- **Version EasyAdmin** : v4.27.4
- **Compatibilité** : Symfony 8.0+
- **Problème résolu** : Limite mémoire PHP augmentée à 512M pour le cache clear
- **Conflit évité** : `BackofficeDashboardController` au lieu de `DashboardController` (déjà existant dans `Analytics/`)

## 🔗 Références

- [Documentation EasyAdmin](https://symfony.com/bundles/EasyAdminBundle/current/index.html)
- [Cookbook EasyAdmin](https://symfony.com/bundles/EasyAdminBundle/current/crud.html)
