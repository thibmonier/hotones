# HotOnes

## Badges

### License & Documentation
[![License: CC BY-NC-SA 4.0](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-lightgrey.svg?style=for-the-badge)](LICENSE)
[![WARP Index](https://img.shields.io/badge/WARP-Index-0A84FF?style=for-the-badge)](WARP.md)
[![Agents Guide](https://img.shields.io/badge/Agents-Guide-2EA043?style=for-the-badge)](AGENTS.md)
[![CI](https://github.com/thibmonier/hotones/actions/workflows/ci.yml/badge.svg)](https://github.com/thibmonier/hotones/actions/workflows/ci.yml)

### Code Quality (SonarCloud)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=alert_status&token=9ebe0aa73ad9ade642d0ec52a9c9bfa7436a8a12)](https://sonarcloud.io/summary/new_code?id=thibmonier_hotones)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=coverage&token=9ebe0aa73ad9ade642d0ec52a9c9bfa7436a8a12)](https://sonarcloud.io/summary/new_code?id=thibmonier_hotones)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=security_rating&token=9ebe0aa73ad9ade642d0ec52a9c9bfa7436a8a12)](https://sonarcloud.io/summary/new_code?id=thibmonier_hotones)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=sqale_rating&token=9ebe0aa73ad9ade642d0ec52a9c9bfa7436a8a12)](https://sonarcloud.io/summary/new_code?id=thibmonier_hotones)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=bugs&token=9ebe0aa73ad9ade642d0ec52a9c9bfa7436a8a12)](https://sonarcloud.io/summary/new_code?id=thibmonier_hotones)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=vulnerabilities&token=9ebe0aa73ad9ade642d0ec52a9c9bfa7436a8a12)](https://sonarcloud.io/summary/new_code?id=thibmonier_hotones)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=code_smells&token=9ebe0aa73ad9ade642d0ec52a9c9bfa7436a8a12)](https://sonarcloud.io/summary/new_code?id=thibmonier_hotones)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=sqale_index&token=9ebe0aa73ad9ade642d0ec52a9c9bfa7436a8a12)](https://sonarcloud.io/summary/new_code?id=thibmonier_hotones)

### Stack
[![PHP Version](https://img.shields.io/badge/PHP-8.5-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/Symfony-8.0-000000?style=flat&logo=symfony&logoColor=white)](https://symfony.com/)
[![MariaDB Version](https://img.shields.io/badge/MariaDB-11.4-003545?style=flat&logo=mariadb&logoColor=white)](https://mariadb.org/)

Plateforme SaaS multi-tenant de gestion de rentabilité pour agences web. Analyse de la rentabilité en croisant ventes (jours/TJM), temps passés, coûts (CJM), achats et KPIs consolidés. Support multi-sociétés avec isolation complète des données.

## 🚀 Guide Warp/Agents
- WARP index: WARP.md
- Guide agents: AGENTS.md
- Performance: docs/performance.md

## Stack
- Backend: Symfony 8.0.1, PHP 8.5
- DB: MariaDB 11.4 (Docker)
- Frontend: Twig + Bootstrap 5 (thème Skote)
- Assets: Webpack Encore + Sass
- Admin: EasyAdmin 4.27
- 2FA: scheb/2fa-bundle (TOTP)
- API: ApiPlatform 4.2 (REST)
- AI: Symfony AI (Anthropic Claude, OpenAI GPT)

## Prérequis
- Docker + Docker Compose
- Node.js + Yarn (si build assets en local)

## Démarrage rapide (Docker)
```bash
# 1) Lancer l'environnement
docker compose up -d --build

# 2) Installer les dépendances PHP (dans le conteneur)
docker compose exec app composer install

# 3) Créer/update le schéma et exécuter les migrations
docker compose exec app php bin/console doctrine:migrations:migrate -n

# 4) Charger les données de référence (profils, technologies)
docker compose exec app php bin/console app:load-reference-data

# 5) Créer des utilisateurs de test pour tous les rôles
docker compose exec app php bin/console app:create-test-users
# Utilisateurs créés: intervenant@test.com, chef-projet@test.com, manager@test.com,
# compta@test.com, admin@test.com, superadmin@test.com (password: "password")

# 6) (Optionnel) Générer des projets de test avec devis et temps passés
docker compose exec app php bin/console app:seed-projects-2025 --count=50 --year=2025

# 7) Builder les assets (au choix)
# En local
./build-assets.sh dev
# OU dans Docker
./docker-build-assets.sh dev
```

**URLs principales:**
- Application: http://localhost:8080
- Backoffice Admin: http://localhost:8080/backoffice (ROLE_ADMIN requis)

## Développement quotidien
- Lancer/arrêter: `docker compose up -d` / `docker compose down`
- Logs nginx/PHP: `docker compose logs -f web` / `docker compose logs -f app`
- Console Symfony: `docker compose exec app php bin/console`

### Assets (Webpack Encore)
```bash path=null start=null
# Dév (watch)
./build-assets.sh watch
# Prod
./build-assets.sh prod
# Docker (watch)
./docker-build-assets.sh watch
```

## Fonctionnalités clés

### 🏢 Multi-tenancy & Administration
- **Architecture multi-tenant** : Isolation complète des données par société (Company)
- **Backoffice EasyAdmin** : Interface d'administration moderne (`/backoffice`)
  - Gestion des sociétés (Company) avec configuration fine (limites, forfaits, features)
  - Configuration des technologies, profils métiers, catégories de service
  - Gestion des abonnements SaaS et planification des tâches

### 👤 Authentification & Sécurité
- Authentification multi-rôles (INTERVENANT → CHEF_PROJET → MANAGER → COMPTA/ADMIN → SUPERADMIN)
- 2FA TOTP (scheb/2fa-bundle)
- Protection CSRF sur tous les formulaires
- API REST sécurisée (JWT via lexik/jwt-authentication-bundle)

### 💼 Gestion de projet
- Gestion Contributeurs, Périodes d'emploi, Profils métiers
- Projets: type (forfait/régie), statut, achats, technologies, catégories de service
- Devis: sections + lignes (jours/TJM/achats), contingence, workflow de validation
- Timesheet: saisie hebdo avec timer, historique, vue globale

### 📅 Planning & Ressources
- **Planning Resource Timeline**: FullCalendar Scheduler avec gestion des congés
- **Optimisation intelligente**: analyse TACE avec recommandations IA (OpenAI GPT-4o-mini, Anthropic Claude 3.5 Haiku)
- **Workflow de congés**: demandes avec validation hiérarchique, notifications temps réel
- **Staffing Dashboard**: métriques de charge, disponibilité, taux d'activité

### 📊 Analytics & KPIs
- Tableaux de bord: Analytics, Profitabilité, Ventes, Staffing
- Export Excel avec graphiques et évolutions mensuelles
- Métriques pré-calculées (star schema) avec fallback temps réel
- **Niveaux de service client**: VIP, Prioritaire, Standard, Basse priorité (calcul auto ou manuel)

## Dernières mises à jour

### 🏢 Architecture Multi-tenant & Backoffice (Janvier 2025)
- **Multi-tenancy complet**: Entité Company avec isolation des données
  - Gestion des abonnements (Starter/Professional/Enterprise)
  - Limites configurables (utilisateurs, projets, stockage)
  - Feature flags modulaires (Invoicing, Planning, Analytics, AI Tools, API Access)
  - Paramètres métier (coefficients de charges, congés, RTT)
- **Backoffice EasyAdmin 4.27**: Interface d'administration complète
  - CRUD Company avec tous les champs de configuration
  - Gestion Technologies, Profils, Catégories de service, Compétences
  - Gestion abonnements SaaS (Providers, Services, Subscriptions)
  - Monitoring Scheduler et paramètres notifications
- **Commandes de setup améliorées**:
  - `app:load-reference-data`: Charge profils métiers et technologies avec descriptions
  - `app:create-test-users`: Génère utilisateurs pour tous les rôles (password: "password")
  - `app:seed-projects-2025`: Génère projets complets avec devis signés, tâches et temps passés

### 🤖 Optimisation IA du planning (Novembre 2024)
- **Analyse TACE intelligente**: détection automatique des surcharges et sous-utilisations
- **Recommandations IA**: intégration OpenAI (GPT-4o-mini) et Anthropic (Claude 3.5 Haiku)
- **Dashboard d'optimisation**: `/planning/optimization` avec recommandations actionnables
- **Alertes intégrées**: bannières dans le planning pour les situations critiques
- **Prise en compte des niveaux de service**: priorisation VIP/Priority dans les recommandations

### 🏖️ Workflow de congés complet
- **Demandes de congés**: interface dédiée pour les intervenants
- **Validation hiérarchique**: rattachement contributeur → manager
- **Notifications en temps réel**: via Symfony Messenger (email + interface)
- **Affichage dans le planning**: congés approuvés visibles en lecture seule
- **Dashboard manager**: widget dédié sur la page d'accueil

### 👥 Niveaux de service client
- **4 niveaux**: VIP, Prioritaire, Standard, Basse priorité
- **Calcul automatique**: basé sur le CA annuel (Top 20 = VIP, Top 50 = Prioritaire)
- **Mode manuel**: possibilité de forcer un niveau spécifique
- **Commande de recalcul**: `app:client:recalculate-service-level`
- **Badges visuels**: affichage dans toute l'application

### Autres mises à jour
- Compteur de temps: start/stop depuis la saisie hebdo (un seul actif), imputation auto (min 0,125j)
- Création automatique des tâches par défaut (AVV, Non-vendu) à la création d'un projet
- Prise en compte du type et du statut de projet à la création/édition
- Ajout de la relation optionnelle Timesheet → ProjectTask (modèle)
- Devis: modification rapide du statut depuis la page devis et la liste des devis (POST CSRF → route order_update_status)
- Projets: la colonne « Type » du listing montre Forfait/Régie + badge « Interne/Client »

Après pull, exécuter:
```bash path=null start=null
docker compose exec app php bin/console make:migration   # si fichiers d'entités ont évolué
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

## Tests & qualité
```bash
# Tests (unit/int/func/E2E)
docker compose exec app ./vendor/bin/phpunit

# Analyse statique & style
docker compose exec app composer check-code
```

- Environnement de test: `.env.test` utilise SQLite (isolation sans DB externe)
- E2E (Panther): nécessite Chrome/Chromium; variables utiles (si besoin):
```bash
export PANTHER_CHROME_BINARY="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
export PANTHER_NO_SANDBOX=1
```
- CI: GitHub Actions exécute PHPUnit (incl. E2E headless) + qualité (`.github/workflows/ci.yml`)
- Plus d’infos: `docs/tests.md`

## URLs utiles

### Administration
- **Backoffice**: http://localhost:8080/backoffice (EasyAdmin - ROLE_ADMIN)
  - Gestion Company, Technologies, Profils, SaaS, Scheduler
- **Configuration legacy**: /admin/technologies, /admin/service-categories, /admin/job-profiles

### Application principale
- **App**: http://localhost:8080
- **Périodes d'emploi**: /employment-periods
- **Planning**: /planning (resource timeline avec gestion des congés)
- **Optimisation planning**: /planning/optimization (recommandations IA)
- **Demande de congés**: /vacation-request (pour intervenants)
- **Validation congés**: /vacation-approval (pour managers)

### Analytics & Dashboards
- **Analytics**: /analytics/dashboard (KPIs consolidés avec export Excel)
- **Profitabilité**: /profitability/dashboard
- **Ventes**: /sales/dashboard
- **Staffing & TACE**: /staffing/dashboard

### API
- **Documentation**: /api/documentation (Swagger/OpenAPI)

## Accès Base de données (clients externes)
- Host: localhost
- Port: 3307
- DB: hotones
- User/Pass: symfony/symfony

## Notes
- 2FA à configurer via `/me/2fa/enable`
- Timesheet peut (optionnellement) référencer une tâche projet pour exclure AVV/Non-vendu des calculs.

## 📄 Licence

Ce projet est sous licence **Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International** (CC BY-NC-SA 4.0).

### Résumé des droits

✅ **Vous êtes autorisé à** :
- **Partager** : Copier et redistribuer le code
- **Adapter** : Remixer, transformer et développer à partir du code
- **Usage personnel et éducatif** : Utiliser pour apprendre, étudier, former

❌ **Vous N'ÊTES PAS autorisé à** :
- **Usage commercial** : Utiliser ce code dans un cadre commercial, vendre des services basés sur ce code, l'intégrer dans un produit commercial
- **Changer de licence** : Toute œuvre dérivée doit utiliser la même licence CC BY-NC-SA 4.0

📋 **Conditions** :
- **Attribution** : Vous devez créditer l'auteur original
- **ShareAlike** : Les modifications doivent être partagées sous la même licence
- **NonCommercial** : Pas d'utilisation commerciale

Pour plus de détails, consultez le fichier [LICENSE](LICENSE) complet ou visitez [creativecommons.org/licenses/by-nc-sa/4.0/](https://creativecommons.org/licenses/by-nc-sa/4.0/).

### Usage commercial

Si vous souhaitez utiliser ce code dans un contexte commercial, veuillez contacter l'auteur pour discuter d'une licence commerciale séparée.

## 🤝 Contribution

Les contributions sont les bienvenues ! Consultez [CONTRIBUTING.md](CONTRIBUTING.md) pour les guidelines de développement et le processus de contribution.
