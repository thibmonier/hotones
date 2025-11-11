# 🔌 API REST - HotOnes

## Vue d'ensemble

L'API REST de HotOnes est construite avec **API Platform 4** et sécurisée avec **JWT (JSON Web Tokens)**.

## Authentification

### Obtenir un token JWT

**Endpoint:** `POST /api/login`

**Payload:**
```json
{
  "email": "user@example.com",
  "password": "votre-mot-de-passe"
}
```

**Réponse:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "..."
}
```

### Utiliser le token

Pour tous les appels API authentifiés, ajouter le header:
```
Authorization: Bearer {votre-token}
```

## Endpoints disponibles

### 📁 Projets (`/api/projects`)

**Permissions:**
- **GET** (lecture): `ROLE_USER`
- **POST** (création): `ROLE_CHEF_PROJET`
- **PUT/PATCH** (modification): `ROLE_CHEF_PROJET`
- **DELETE** (suppression): `ROLE_MANAGER`

**Opérations:**
- `GET /api/projects` - Liste des projets (paginée, 30 par page)
- `GET /api/projects/{id}` - Détail d'un projet
- `POST /api/projects` - Créer un projet
- `PUT /api/projects/{id}` - Modifier un projet (remplacement complet)
- `PATCH /api/projects/{id}` - Modifier un projet (partiel)
- `DELETE /api/projects/{id}` - Supprimer un projet

**Groupes de sérialisation:**
- Lecture: `project:read`
- Écriture: `project:write`

### ⏱️ Timesheets (`/api/timesheets`)

**Permissions:**
- **GET** (lecture): `ROLE_USER`
- **POST** (création): `ROLE_INTERVENANT`
- **PUT** (modification): `ROLE_INTERVENANT` + propriétaire uniquement
- **DELETE** (suppression): `ROLE_CHEF_PROJET` ou propriétaire

**Opérations:**
- `GET /api/timesheets` - Liste des saisies de temps (50 par page)
- `GET /api/timesheets/{id}` - Détail d'une saisie
- `POST /api/timesheets` - Créer une saisie
- `PUT /api/timesheets/{id}` - Modifier une saisie
- `DELETE /api/timesheets/{id}` - Supprimer une saisie

**Groupes:**
- Lecture: `timesheet:read`
- Écriture: `timesheet:write`

### 👥 Contributeurs (`/api/contributors`)

**Permissions:**
- **GET** (lecture): `ROLE_USER` - **Lecture seule**

**Opérations:**
- `GET /api/contributors` - Liste des contributeurs (30 par page)
- `GET /api/contributors/{id}` - Détail d'un contributeur

**Groupes:**
- Lecture: `contributor:read`

### 📄 Devis (`/api/orders`)

**Permissions:**
- **GET** (lecture): `ROLE_USER`
- **POST** (création): `ROLE_CHEF_PROJET`
- **PUT/PATCH** (modification): `ROLE_CHEF_PROJET`
- **DELETE** (suppression): `ROLE_MANAGER`

**Opérations:**
- `GET /api/orders` - Liste des devis (30 par page)
- `GET /api/orders/{id}` - Détail d'un devis
- `POST /api/orders` - Créer un devis
- `PUT /api/orders/{id}` - Modifier un devis
- `PATCH /api/orders/{id}` - Modifier un devis (partiel)
- `DELETE /api/orders/{id}` - Supprimer un devis

**Groupes:**
- Lecture: `order:read`
- Écriture: `order:write`

### 👤 Utilisateurs (`/api/users`)

**Permissions:**
- **GET item** (lecture): `ROLE_MANAGER` ou soi-même
- **GET collection** (liste): `ROLE_MANAGER`
- **POST** (création): `ROLE_MANAGER`
- **PUT/PATCH** (modification): `ROLE_MANAGER` ou soi-même
- **DELETE** (suppression): `ROLE_SUPERADMIN`

**Opérations:**
- `GET /api/users` - Liste des utilisateurs (30 par page)
- `GET /api/users/{id}` - Détail d'un utilisateur
- `POST /api/users` - Créer un utilisateur
- `PUT /api/users/{id}` - Modifier un utilisateur
- `PATCH /api/users/{id}` - Modifier un utilisateur (partiel)
- `DELETE /api/users/{id}` - Supprimer un utilisateur

**Groupes:**
- Lecture: `user:read`
- Écriture: `user:write`

**Note:** Le mot de passe est en écriture seule (`user:write` uniquement).

### ⏲️ Timer en cours (`/api/running_timers`)

**Permissions:**
- **GET** (lecture): `ROLE_USER`
- **POST** (création): `ROLE_INTERVENANT`
- **PUT** (modification): `ROLE_INTERVENANT` + propriétaire uniquement
- **DELETE** (suppression): `ROLE_INTERVENANT` + propriétaire uniquement

**Opérations:**
- `GET /api/running_timers` - Liste des timers actifs (non paginé)
- `GET /api/running_timers/{id}` - Détail d'un timer
- `POST /api/running_timers` - Démarrer un timer
- `PUT /api/running_timers/{id}` - Modifier/arrêter un timer
- `DELETE /api/running_timers/{id}` - Supprimer un timer

**Groupes:**
- Lecture: `timer:read`
- Écriture: `timer:write`

### 📊 Métriques (`/api/fact_project_metrics`)

**Permissions:**
- **GET** (lecture seule): `ROLE_MANAGER`

**Opérations:**
- `GET /api/fact_project_metrics` - Liste des métriques (50 par page)
- `GET /api/fact_project_metrics/{id}` - Détail d'une métrique

**Groupes:**
- Lecture: `metrics:read`

**Note:** Endpoint en **lecture seule**. Les métriques sont calculées via le worker asynchrone.

## Documentation interactive

L'API dispose d'une documentation interactive Swagger/OpenAPI accessible à:

**URL:** `http://localhost:8080/api/documentation`

Cette interface permet de:
- Visualiser tous les endpoints
- Tester les requêtes directement
- Voir les schémas de données
- S'authentifier avec un token JWT

## Formats supportés

- **JSON-LD** (par défaut): `application/ld+json`
- **JSON**: `application/json`
- **HTML** (documentation): `text/html`

Pour forcer un format, utiliser le header `Accept` ou l'extension d'URL:
```
GET /api/projects.json
GET /api/projects.jsonld
```

## Pagination

Les collections sont paginées automatiquement. Paramètres disponibles:
- `page` : numéro de page (défaut: 1)
- `itemsPerPage` : nombre d'éléments par page

Exemple:
```
GET /api/projects?page=2&itemsPerPage=50
```

La réponse inclut les métadonnées de pagination dans les headers ou le body JSON-LD.

## Filtrage et tri

Les filtres et tris sont disponibles sur certains endpoints (à configurer selon les besoins).

Exemple (à implémenter):
```
GET /api/projects?status=active&order[startDate]=desc
```

## Gestion des erreurs

L'API retourne des codes HTTP standards:
- `200 OK` : Succès
- `201 Created` : Ressource créée
- `204 No Content` : Suppression réussie
- `400 Bad Request` : Données invalides
- `401 Unauthorized` : Non authentifié
- `403 Forbidden` : Droits insuffisants
- `404 Not Found` : Ressource introuvable
- `500 Internal Server Error` : Erreur serveur

Format des erreurs:
```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "Message d'erreur détaillé"
}
```

## Rate Limiting

⚠️ **À implémenter** (voir Lot 8 - roadmap)

Prévu:
- Limite par IP ou par utilisateur
- Headers de réponse avec informations de limite
- Codes 429 (Too Many Requests) en cas de dépassement

## Exemples d'utilisation

### Créer un projet

```bash
curl -X POST http://localhost:8080/api/projects \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Nouveau projet",
    "description": "Description du projet",
    "status": "active",
    "projectType": "forfait",
    "isInternal": false
  }'
```

### Saisir du temps

```bash
curl -X POST http://localhost:8080/api/timesheets \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "contributor": "/api/contributors/1",
    "project": "/api/projects/5",
    "task": "/api/project_tasks/12",
    "date": "2025-01-15",
    "hours": "7.5",
    "notes": "Développement de la fonctionnalité X"
  }'
```

### Consulter les métriques

```bash
curl -X GET http://localhost:8080/api/fact_project_metrics?granularity=monthly \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

## Tests

Des tests unitaires et fonctionnels sont prévus (voir Lot 8 - roadmap).

Lancer les tests:
```bash
docker compose exec app php bin/phpunit --group api
```

## Sécurité

### Bonnes pratiques

1. **Toujours utiliser HTTPS en production**
2. **Ne jamais exposer les tokens dans les URLs**
3. **Configurer les CORS** pour limiter les origines autorisées
4. **Mettre en place le rate limiting** pour éviter les abus
5. **Valider toutes les entrées** (fait automatiquement par API Platform)
6. **Auditer les accès** via les logs

### Configuration JWT

Les clés JWT sont stockées dans:
- `config/jwt/private.pem` (clé privée, ne pas committer)
- `config/jwt/public.pem` (clé publique)

Passphrase configurée dans `.env`:
```
JWT_PASSPHRASE=votre-passphrase-secure
```

## Versions futures

### Rate limiting (à implémenter)
- Bundle Symfony à installer: `noxlogic/ratelimit-bundle`
- Configuration par endpoint et par rôle

### Webhooks (optionnel)
- Notifications d'événements vers services tiers
- Configuration dans l'interface admin

### GraphQL (optionnel)
Si besoin, installer:
```bash
composer require webonyx/graphql-php
```

Puis accéder à `/api/graphql`.

---

**Documentation générée pour le Lot 8 - API REST**  
Version: 1.0.0  
Date: Janvier 2025
