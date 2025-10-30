# 🔧 Installation & Usage

## Prérequis
- Docker & Docker Compose
- Node.js + npm (pour les assets)

## Démarrage
```bash
# Clone et démarrage
docker compose up -d --build

# Création d'un utilisateur
docker compose exec app php bin/console app:user:create email@example.com password Prénom Nom
```
