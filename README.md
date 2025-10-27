# HotOnes

Gestion de rentabilité des projets d'agence web. Analyse de la rentabilité en croisant ventes (jours/TJM), temps passés, coûts (CJM), achats et KPIs consolidés.

## Stack
- Backend: Symfony 7.3, PHP 8.4
- DB: MariaDB 11.4 (Docker)
- Frontend: Twig + Bootstrap 5 (thème Skote)
- Assets: Webpack Encore + Sass
- 2FA: scheb/2fa-bundle (TOTP)

## Prérequis
- Docker + Docker Compose
- Node.js + Yarn (si build assets en local)

## Démarrage rapide (Docker)
```bash path=null start=null
# 1) Lancer l'environnement
docker compose up -d --build

# 2) Installer les dépendances PHP (dans le conteneur)
docker compose exec app composer install

# 3) Créer/update le schéma et exécuter les migrations
# (si une nouvelle migration est requise : docker compose exec app php bin/console make:migration)
docker compose exec app php bin/console doctrine:migrations:migrate -n

# 4) (Optionnel) Générer des données de test
docker compose exec app php bin/console app:generate-test-data --year=$(date +%Y)

# 5) Builder les assets (au choix)
# En local
./build-assets.sh dev
# OU dans Docker
./docker-build-assets.sh dev

# 6) Créer un utilisateur d'admin de test
docker compose exec app php bin/console app:user:create email@example.com password "Prénom" "Nom"
```

Application: http://localhost:8080

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
- Authentification + 2FA TOTP
- Gestion Contributeurs, Périodes d'emploi, Profils
- Projets: type (forfait/régie), statut, achats, technos, catégorie de service
- Devis: sections + lignes (jours/TJM/achats), contingence
- Timesheet: saisie hebdo, historique, vue globale
- Analytics: `/analytics/dashboard` (KPIs, filtres, graphiques)

## Dernières mises à jour
- Création automatique des tâches par défaut (AVV, Non-vendu) à la création d’un projet
- Prise en compte du type et du statut de projet à la création/édition
- Ajout de la relation optionnelle Timesheet → ProjectTask (modèle)

Après pull, exécuter:
```bash path=null start=null
docker compose exec app php bin/console make:migration   # si fichiers d'entités ont évolué
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

## Tests & qualité
```bash path=null start=null
# Tests (si présents)
docker compose exec app ./vendor/bin/phpunit

# Analyse statique
docker compose exec app composer check-code
```

## URLs utiles
- App: http://localhost:8080
- Admin config: /admin/technologies, /admin/service-categories, /admin/job-profiles
- Périodes d'emploi: /employment-periods
- Analytics: /analytics/dashboard

## Accès Base de données (clients externes)
- Host: localhost
- Port: 3307
- DB: hotones
- User/Pass: symfony/symfony

## Notes
- 2FA à configurer via `/me/2fa/enable`
- Timesheet peut (optionnellement) référencer une tâche projet pour exclure AVV/Non-vendu des calculs.
