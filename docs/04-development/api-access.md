# 🔗 Accès rapide à la documentation API

## Interface Swagger UI

La documentation interactive de l'API est accessible à l'adresse suivante :

**👉 http://localhost:8080/api/documentation**

Cette interface vous permet de :
- 📖 Consulter tous les endpoints disponibles
- 🧪 Tester les requêtes directement depuis le navigateur
- 🔐 S'authentifier avec un token JWT
- 📋 Voir les schémas de données (JSON Schema)
- 💾 Télécharger la spécification OpenAPI

## Documentation JSON (OpenAPI)

La spécification OpenAPI au format JSON est disponible à :

**http://localhost:8080/api/docs.json**

Vous pouvez l'importer dans :
- Postman
- Insomnia
- SwaggerHub
- Tout autre client API compatible OpenAPI 3.x

## Autres formats

- **JSON-LD** : http://localhost:8080/api/docs.jsonld
- **HTML Hydra** : http://localhost:8080/api/docs (format API Platform natif)

## Authentification

Pour tester l'API :

1. **Obtenir un token JWT** :
   ```bash
   POST /api/login
   {
     "email": "votre-email@example.com",
     "password": "votre-mot-de-passe"
   }
   ```

2. **Utiliser le token dans Swagger UI** :
   - Cliquer sur le bouton "Authorize" 🔓
   - Entrer : `Bearer {votre-token}`
   - Valider

3. **Tester les endpoints** !

## Documentation complète

Pour plus d'informations, consulter :
- **Guide API complet** : `docs/api.md`
- **README Lot 8** : `docs/lot8-readme.md`
- **Tests exemples** : `tests/Api/`

---

**Note** : En production, pensez à sécuriser l'accès à la documentation ou à la désactiver.
