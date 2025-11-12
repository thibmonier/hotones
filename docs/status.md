# 🚀 État d'avancement

## Liens
- Roadmap: [docs/roadmap-lots.md](./roadmap-lots.md)
- Backlog: [docs/roadmap-lots.md#backlog](./roadmap-lots.md#backlog)

## Légende
- ✅ Terminé
- 🔄 En cours
- 🔲 À faire

## Définition de Done (DoD)
- Fonctionnalités validées métier
- Tests unitaires, fonctionnels et E2E au vert en CI
- Documentation mise à jour
- Revue de code effectuée

## ✅ Implémenté
- Setup Docker (PHP 8.4, Nginx, MariaDB)
- Entities Doctrine + migrations
- Authentification Symfony Security
- 2FA Google Authenticator (scheb/2fa-bundle)
- Templates Bootstrap 5 + Webpack Encore
- QR Code generation (endroid/qr-code-bundle)
- Command CLI création utilisateur
- Pages : login, 2fa, tableau de bord, config 2FA
- Repositories personnalisés (Contributor, EmploymentPeriod, Timesheet, Project, ProjectTask, Order)
- CRUD complets pour entités de configuration (Technologies, Catégories de service, Profils métier)
- Refactoring contrôleurs : logique métier déplacée vers repositories
- Création automatique des tâches par défaut (AVV, Non-vendu) à la création d'un projet
- Ajout du type et du statut de projet lors de la création/édition
- Relation optionnelle Timesheet → ProjectTask (modèle)
- Listing projets: colonne « Type » (Forfait/Régie) + badge « Interne/Client »
- Tests automatisés: unitaires, intégration, fonctionnels et E2E (Panther)
- CI GitHub Actions: PHPUnit (+ Chrome headless) et qualité (phpstan/phpmd/php-cs-fixer)

## 🔄 En cours / À faire

Référence: détails, périmètre et dépendances dans la Roadmap → [Lots](./roadmap-lots.md) et [Backlog](./roadmap-lots.md#backlog)
- CRUD complet des entités principales (Contributor, Project, Order, Timesheet, EmploymentPeriod)
- Interface de saisie des temps (Timesheet) avec sélection de tâche
- Dashboard analytique avec métriques et graphiques
- Système de suivi KPIs avec modèle en étoile
- ✅ Dashboard de suivi du staffing (taux de staffing et TACE)
- Gestion des périodes d'emploi (interface complète avec relation profils)
- Alimenter les listes des rôles projet (KAM, Chef de projet, Directeur, Commercial) dans le formulaire
- Upload et gestion d'avatars
- API REST pour intégrations externes
- Rapports et exports (PDF/Excel)
- Notifications et alertes
- Adapter le menu de navigation avec les entités de l'application
- Filtres avancés dans le listing des projets
