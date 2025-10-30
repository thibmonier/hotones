# 🚀 État d'avancement

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

## 🔄 En cours / À faire
- CRUD complet des entités principales (Contributor, Project, Order, Timesheet, EmploymentPeriod)
- Interface de saisie des temps (Timesheet)
- Calculs de rentabilité par projet
- Dashboard analytique avec métriques et graphiques
- Système de suivi KPIs avec modèle en étoile
- Gestion des périodes d'emploi (interface complète avec relation profils)
- Création automatique des tâches par défaut (AVV, Non-vendu) à la création d'un projet
- Ajout du type et du statut de projet lors de la création/édition
- Relation optionnelle Timesheet → ProjectTask (modèle)
- Sélection de la tâche dans l'UI de saisie des temps (Timesheet)
- Alimenter les listes des rôles projet (KAM, Chef de projet, Directeur, Commercial) dans le formulaire
- Upload et gestion d'avatars
- API REST pour intégrations externes
- Rapports et exports (PDF/Excel)
- Notifications et alertes
- Adapter le menu de navigation avec les entités de l'application
- Mettre à jour project/new.html.twig avec les champs manquants
- Tests automatisés
- Filtres avancés dans le listing des projets
- Générer et exécuter la migration Doctrine pour Timesheet.task
