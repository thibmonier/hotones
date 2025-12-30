# Lot 8 : API REST - Résumé d'implémentation

## ✅ Statut : Implémenté (Base fonctionnelle)

Le Lot 8 a été implémenté avec succès en utilisant **API Platform 4** et **JWT Authentication**.

## 📦 Packages installés

```bash
composer require api-platform/core:^4.0
composer require lexik/jwt-authentication-bundle
```

## 🔧 Configuration

### 1. API Platform
- Fichier : `config/packages/api_platform.yaml`
- Titre : "HotOnes API"
- Version : 1.0.0
- Formats supportés : JSON-LD, JSON, HTML

### 2. JWT Authentication
- Fichier : `config/packages/lexik_jwt_authentication.yaml`
- Clés générées dans `config/jwt/` (privée et publique)
- Endpoint de login : `/api/login`

### 3. Security
- Fichier : `config/packages/security.yaml`
- Firewall `api_login` pour `/api/login`
- Firewall `api` pour tous les endpoints `/api` (JWT requis)
- Access control configuré

## 📍 Endpoints implémentés

| Ressource | Endpoint | Méthodes | Permissions |
|-----------|----------|----------|-------------|
| **Projets** | `/api/projects` | GET, POST, PUT, PATCH, DELETE | USER (lecture) / CHEF_PROJET (écriture) / MANAGER (delete) |
| **Timesheets** | `/api/timesheets` | GET, POST, PUT, DELETE | USER (lecture) / INTERVENANT (écriture) |
| **Contributeurs** | `/api/contributors` | GET | USER (lecture seule) |
| **Devis** | `/api/orders` | GET, POST, PUT, PATCH, DELETE | USER (lecture) / CHEF_PROJET (écriture) / MANAGER (delete) |
| **Utilisateurs** | `/api/users` | GET, POST, PUT, PATCH, DELETE | MANAGER (lecture) / self access |
| **Timer actif** | `/api/running_timers` | GET, POST, PUT, DELETE | USER / INTERVENANT (self) |
| **Métriques** | `/api/fact_project_metrics` | GET | MANAGER (lecture seule) |

## 🔐 Sécurité

### Authentification JWT
1. **Obtenir un token :**
   ```bash
   POST /api/login
   {
     "email": "user@example.com",
     "password": "password"
   }
   ```

2. **Utiliser le token :**
   ```bash
   Authorization: Bearer {token}
   ```

### Permissions par rôle
- `ROLE_USER` : Accès en lecture aux ressources publiques
- `ROLE_INTERVENANT` : Saisie de temps, timer
- `ROLE_CHEF_PROJET` : Gestion projets et devis
- `ROLE_MANAGER` : Administration, métriques
- `ROLE_SUPERADMIN` : Administration complète

## 📊 Groupes de sérialisation

Chaque ressource possède des groupes pour contrôler les données exposées :

| Ressource | Lecture | Écriture |
|-----------|---------|----------|
| Project | `project:read` | `project:write` |
| Timesheet | `timesheet:read` | `timesheet:write` |
| Contributor | `contributor:read` | - |
| Order | `order:read` | `order:write` |
| User | `user:read` | `user:write` |
| RunningTimer | `timer:read` | `timer:write` |
| Metrics | `metrics:read` | - |

## 📚 Documentation

### Interactive (Swagger)
Accessible à : `http://localhost:8080/api/documentation`

### OpenAPI JSON
Accessible à : `http://localhost:8080/api/docs.json`

### Documentation markdown
Fichier : `docs/api.md`

## 🧪 Tests

### Fichier exemple
`tests/Api/ProjectApiTest.php`

### Exécution
```bash
# Tous les tests API
docker compose exec app php bin/phpunit --group api

# Test spécifique
docker compose exec app php bin/phpunit tests/Api/ProjectApiTest.php
```

## 🚀 Utilisation

### Exemple : Lister les projets
```bash
curl -X GET http://localhost:8080/api/projects \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Exemple : Créer un projet
```bash
curl -X POST http://localhost:8080/api/projects \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Nouveau projet",
    "description": "Description",
    "status": "active",
    "projectType": "forfait",
    "isInternal": false
  }'
```

### Exemple : Saisir du temps
```bash
curl -X POST http://localhost:8080/api/timesheets \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "contributor": "/api/contributors/1",
    "project": "/api/projects/5",
    "date": "2025-01-15",
    "hours": "7.5",
    "notes": "Développement"
  }'
```

## 📋 Fonctionnalités manquantes (à implémenter)

### 🔴 Haute priorité
- [ ] **Rate Limiting** : Limiter les requêtes par utilisateur/IP
  - Package recommandé : `noxlogic/ratelimit-bundle`
  - Configuration par endpoint
  
### 🟡 Moyenne priorité
- [ ] **Filtres avancés** : Ajouter des filtres sur les collections
  - Exemple : `/api/projects?status=active&projectType=forfait`
  - Utiliser les annotations `@ApiFilter` d'API Platform

- [ ] **Tests complets** : Compléter la couverture de tests
  - Tests pour chaque endpoint
  - Tests de sécurité
  - Tests de validation

### 🟢 Basse priorité
- [ ] **Webhooks** : Notifications d'événements vers services tiers
- [ ] **GraphQL** : Support GraphQL en plus de REST
  - `composer require webonyx/graphql-php`
- [ ] **Versioning** : Gestion des versions d'API
- [ ] **SDKs clients** : Générer des SDKs JavaScript/Python

## 📂 Fichiers modifiés/créés

### Configuration
- `config/packages/api_platform.yaml` (créé)
- `config/packages/lexik_jwt_authentication.yaml` (créé)
- `config/packages/security.yaml` (modifié)
- `config/jwt/private.pem` (généré)
- `config/jwt/public.pem` (généré)

### Entités
- `src/Entity/Project.php` (modifié - attributs API Platform)
- `src/Entity/Timesheet.php` (modifié)
- `src/Entity/Contributor.php` (modifié)
- `src/Entity/Order.php` (modifié)
- `src/Entity/User.php` (modifié)
- `src/Entity/RunningTimer.php` (modifié)
- `src/Entity/Analytics/FactProjectMetrics.php` (modifié)

### Documentation
- `docs/api.md` (créé)
- `docs/lot8-readme.md` (ce fichier)

### Tests
- `tests/Api/ProjectApiTest.php` (créé - exemple)

## 🔍 Vérification

### Vider le cache
```bash
docker compose exec app php bin/console cache:clear
```

### Vérifier les routes
```bash
docker compose exec app php bin/console debug:router | grep "/api"
```

### Tester l'API
```bash
# Accéder à la doc Swagger
open http://localhost:8080/api/documentation

# Vérifier le JSON OpenAPI
curl http://localhost:8080/api/docs.json | jq '.info'
```

## ⚡ Performance

### Pagination
- Configurée par défaut (30-50 items/page selon ressource)
- Ajustable via paramètre `itemsPerPage`

### Cache HTTP
- Headers de cache configurés dans API Platform
- Vary sur : Content-Type, Authorization, Origin

### Optimisations futures
- [ ] Mettre en cache les réponses GET
- [ ] Utiliser Varnish ou Redis pour cache HTTP
- [ ] Implémenter la compression GZIP

## 🎯 Conformité au Lot 8

| Fonctionnalité | Statut | Notes |
|----------------|--------|-------|
| Endpoints /api/projects | ✅ | CRUD complet |
| Endpoints /api/timesheets | ✅ | CRUD complet |
| Endpoints /api/contributors | ✅ | Lecture seule |
| Endpoints /api/orders | ✅ | CRUD complet |
| Endpoints /api/metrics | ✅ | Lecture seule |
| Endpoints /api/users | ✅ | CRUD complet |
| Endpoints /api/running-timer | ✅ | Gestion timer |
| Authentification JWT | ✅ | Fonctionnel |
| Rate limiting | ⏳ | À implémenter |
| Scopes/permissions | ✅ | Via security expressions |
| Documentation OpenAPI | ✅ | Swagger disponible |
| Exemples d'utilisation | ✅ | Dans docs/api.md |
| SDKs | ⏳ | À générer |
| Tests API | 🔶 | Exemple créé, à compléter |
| Tests sécurité | ⏳ | À implémenter |

**Légende:**
- ✅ Implémenté
- 🔶 Partiellement implémenté
- ⏳ À implémenter

## 📞 Support

Pour toute question sur l'API :
1. Consulter `docs/api.md`
2. Accéder à la doc Swagger : `/api/documentation`
3. Voir les exemples dans `tests/Api/`

---

**Lot 8 - API REST**  
Implémenté le : Janvier 2025  
Version : 1.0.0 (MVP)
