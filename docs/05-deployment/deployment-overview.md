# Déploiement HotOnes

Ce document résume les options de déploiement disponibles pour HotOnes.

## Déploiement sur Render ⭐ (Recommandé)

**Documentation complète** : [docs/deployment-render.md](docs/deployment-render.md)

Render est une plateforme cloud moderne qui simplifie le déploiement d'applications Symfony.

### Avantages
- ✅ Configuration Infrastructure as Code (`render.yaml`)
- ✅ Déploiement automatique via Git
- ✅ SSL gratuit avec certificats auto-renouvelés
- ✅ Redis intégré
- ✅ Scaling facile
- ✅ Plan gratuit disponible (avec limitations)

### Coût estimé (Production)
- Web Service Starter : $7/mois
- Redis Starter : $10/mois
- Database externe (PlanetScale/Railway) : $0-15/mois
- **Total** : ~$17-30/mois

### Quick Start

```bash
# 1. Pousser le code sur GitHub
git push origin main

# 2. Se connecter sur Render
# https://dashboard.render.com

# 3. New → Blueprint
# Sélectionner le repository et la branche main

# 4. Configurer les variables d'environnement
# - DATABASE_URL (depuis PlanetScale/Railway)
# - JWT_PASSPHRASE
# - MAILER_DSN

# 5. Déployer !
```

[📖 Guide complet](docs/deployment-render.md)

---

## Autres options de déploiement

### Docker Compose (Développement local)

Déjà configuré dans le projet.

```bash
docker compose up -d --build
```

### Déploiement VPS classique

Si vous avez un VPS (Ubuntu/Debian) :

1. **Prérequis serveur**
   ```bash
   # Nginx, PHP 8.4, MariaDB 11.4, Redis
   sudo apt update
   sudo apt install nginx php8.4-fpm mariadb-server redis-server
   ```

2. **Clone du projet**
   ```bash
   git clone https://github.com/votre-org/hotones.git /var/www/hotones
   cd /var/www/hotones
   ```

3. **Installation dépendances**
   ```bash
   composer install --no-dev --optimize-autoloader
   yarn install --production
   yarn build
   ```

4. **Configuration**
   ```bash
   cp .env .env.local
   # Éditer .env.local avec vos paramètres
   php bin/console doctrine:migrations:migrate
   php bin/console lexik:jwt:generate-keypair
   ```

5. **Nginx configuration**
   - Adapter `docker/nginx/conf.d/render.conf`
   - Pointer vers `/var/www/hotones/public`

### Kubernetes / Cloud providers

Pour un déploiement à grande échelle :

- **AWS ECS/EKS** : Utiliser `Dockerfile.render` comme base
- **Google Cloud Run** : Compatible avec le Dockerfile
- **Azure Container Instances** : Idem

Configuration avancée requise (ingress, load balancer, auto-scaling, etc.).

---

## Fichiers de configuration

### Pour Render

- `render.yaml` - Blueprint Infrastructure as Code
- `Dockerfile.render` - Image Docker production optimisée
- `docker/scripts/start-render.sh` - Script de démarrage
- `docker/nginx/conf.d/render.conf` - Configuration Nginx
- `docker/supervisor/supervisord.conf` - Supervision des services

### Pour développement local

- `docker-compose.yml` - Stack de développement
- `Dockerfile` - Image de développement

---

## Support et documentation

- **Guide Render détaillé** : [docs/deployment-render.md](docs/deployment-render.md)
- **Architecture** : [docs/architecture.md](docs/architecture.md)
- **Configuration** : [CLAUDE.md](CLAUDE.md)
- **Documentation principale** : [WARP.md](WARP.md)

---

Dernière mise à jour : 2025-01-20
