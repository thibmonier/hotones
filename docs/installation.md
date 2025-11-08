# 🔧 Installation & Usage

## Prérequis
- Docker & Docker Compose
- Node.js + Yarn (ou npm)

## Démarrage rapide (Docker)
```bash
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
```bash
# Dév (watch)
./build-assets.sh watch
# Prod
./build-assets.sh prod
# Docker (watch)
./docker-build-assets.sh watch
```

## Tests & qualité
```bash
# Unit/Intégration/Fonctionnels
docker compose exec app ./vendor/bin/phpunit

# Analyse statique & style
docker compose exec app composer check-code
```

## Files d’attente (Messenger)
```bash
# 1) Vérifier que Redis est up (docker compose up -d)
# 2) Préparer les transports (tables pour le transport 'failed' doctrine)
docker compose exec app php bin/console messenger:setup-transports

# 3) Démarrer le worker async (dev)
docker compose exec app php bin/console messenger:consume async -vv

# 4) Dispatcher un recalcul de métriques
# Pour une date donnée (mensuel par défaut)
docker compose exec app php bin/console app:metrics:dispatch --date=2025-01-01
# Pour une année complète (mensuel + trimestriel + annuel)
docker compose exec app php bin/console app:metrics:dispatch --year=2025
```

Notes:
- `.env.test` utilise SQLite pour isoler l'environnement de test (le transport async est en mémoire en test).
- Les tests E2E (Panther) nécessitent Google Chrome/Chromium (voir docs/tests.md).
- Par défaut en dev, le transport async est Redis (`MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages`).
- Les messages en échec aboutissent sur le transport `failed` (Doctrine). Relancer: `php bin/console messenger:failed:retry`.
