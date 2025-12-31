# Gestion des Secrets - Guide Symfony Secrets

**Date:** 31 décembre 2025
**Statut:** ✅ Audit complété, recommandations documentées

## 📋 Vue d'ensemble

La gestion sécurisée des secrets (clés API, mots de passe, tokens) est essentielle pour protéger l'application contre les fuites de données sensibles. Symfony propose un système de secrets chiffrés intégré.

## 🔍 Audit des Secrets Actuels

### Secrets Identifiés dans `.env`

| Variable | Type | Criticité | Statut Actuel |
|----------|------|-----------|---------------|
| `APP_SECRET` | Clé application Symfony | 🔴 Critique | ⚠️ .env (non chiffré) |
| `JWT_SECRET_KEY` | Clé privée JWT | 🔴 Critique | ⚠️ .env (non chiffré) |
| `JWT_PUBLIC_KEY` | Clé publique JWT | 🟡 Moyenne | ⚠️ .env (non chiffré) |
| `S3_ACCESS_KEY` | Clé d'accès Cloudflare R2 | 🟠 Haute | ⚠️ .env (non chiffré) |
| `S3_SECRET_KEY` | Clé secrète Cloudflare R2 | 🔴 Critique | ⚠️ .env (non chiffré) |
| `OPENAI_API_KEY` | Clé API OpenAI | 🔴 Critique | ✅ Commentée |
| `ANTHROPIC_API_KEY` | Clé API Anthropic | 🔴 Critique | ✅ Commentée |
| `GEMINI_API_KEY` | Clé API Google Gemini | 🔴 Critique | ✅ Commentée |
| `MAILER_DSN` | Configuration email | 🟠 Haute | ⚠️ .env (non chiffré) |

**Total secrets sensibles** : 9 variables
**Stockage actuel** : ⚠️ Fichier .env non chiffré (risque moyen en production)

### Protection Actuelle

#### ✅ Points positifs

1. **`.env` dans `.gitignore`** : Les secrets ne sont pas versionnés
2. **`.env.example` fourni** : Template sans valeurs sensibles
3. **Clés AI commentées** : Pas de secrets AI en dur dans le dépôt
4. **Documentation setup** : `docs/01-getting-started/SETUP-SECRETS.md` existe

#### ⚠️ Points d'amélioration

1. **Secrets en clair** : Les secrets de production sont stockés en clair dans `.env`
2. **Pas de Symfony Secrets** : Le système de secrets chiffrés n'est pas configuré
3. **Rotation des secrets** : Pas de processus documenté pour la rotation
4. **Secrets partagés** : Risque de fuite lors du partage d'environnement

## 🎯 Recommandations - Symfony Secrets

### Pourquoi Symfony Secrets ?

**Avantages** :
- ✅ **Chiffrement** : Secrets chiffrés avec une clé de déchiffrement
- ✅ **Versionnement sécurisé** : Les secrets chiffrés peuvent être versionnés
- ✅ **Séparation dev/prod** : Secrets différents par environnement
- ✅ **Simplicité** : Commandes Symfony intégrées

**Workflow** :
```
Secrets en clair (dev) → Chiffrement → Secrets chiffrés (versionnés)
                                      ↓
                               Clé de déchiffrement (non versionnée)
                                      ↓
                               Secrets déchiffrés (runtime production)
```

### Architecture Recommandée

```
config/secrets/
├── dev/                    # Secrets développement (optionnel)
│   ├── dev.decrypt.private.php   # Clé privée DEV (gitignore)
│   ├── dev.encrypt.public.php    # Clé publique DEV (versionné)
│   └── dev.MY_SECRET.0d5e2c.php  # Secret chiffré (versionné)
└── prod/                   # Secrets production
    ├── prod.decrypt.private.php  # Clé privée PROD (gitignore)
    ├── prod.encrypt.public.php   # Clé publique PROD (versionné)
    └── prod.*.php                 # Secrets chiffrés (versionnés)
```

## 🛠️ Implémentation Symfony Secrets

### Étape 1 : Générer les Clés de Chiffrement

```bash
# Générer la paire de clés pour production
php bin/console secrets:generate-keys --env=prod

# Résultat :
# - config/secrets/prod/prod.encrypt.public.php (À VERSIONNER)
# - config/secrets/prod/prod.decrypt.private.php (NE PAS VERSIONNER)
```

**IMPORTANT** : La clé privée (`prod.decrypt.private.php`) ne doit JAMAIS être versionnée.
Elle doit être déployée de manière sécurisée (variable d'environnement, vault, etc.).

### Étape 2 : Migrer les Secrets

```bash
# Définir un secret
php bin/console secrets:set APP_SECRET --env=prod

# Le secret sera demandé de manière interactive (sans echo)
# Ou via --random pour générer une valeur aléatoire
php bin/console secrets:set JWT_SECRET --random --env=prod

# Exemples pour tous les secrets critiques :
php bin/console secrets:set S3_ACCESS_KEY --env=prod
php bin/console secrets:set S3_SECRET_KEY --env=prod
php bin/console secrets:set OPENAI_API_KEY --env=prod    # Si utilisé
php bin/console secrets:set ANTHROPIC_API_KEY --env=prod # Si utilisé
```

### Étape 3 : Lister les Secrets

```bash
# Voir tous les secrets définis
php bin/console secrets:list --env=prod

# Révéler la valeur d'un secret (debug seulement)
php bin/console secrets:reveal MY_SECRET --env=prod
```

### Étape 4 : Utiliser les Secrets dans le Code

Les secrets Symfony sont automatiquement injectés comme variables d'environnement :

```php
// Dans services.yaml ou .env.local (fallback)
# Pas de changement nécessaire, APP_SECRET est automatiquement résolu

// Dans le code PHP
$appSecret = $_ENV['APP_SECRET'];  // Résolu depuis secrets ou .env
```

**Ordre de résolution** :
1. `config/secrets/{env}/*.php` (prioritaire si existe)
2. `.env.{env}.local`
3. `.env.local`
4. `.env`

## 🔐 Déploiement en Production

### Option 1 : Variable d'Environnement (Recommandé pour PaaS)

Sur Render, Heroku, etc., stocker la clé privée dans une variable d'environnement :

```bash
# Sur Render.com (via dashboard ou CLI)
# Nom : SYMFONY_DECRYPTION_SECRET
# Valeur : contenu de prod.decrypt.private.php (base64 ou texte)
```

Symfony détecte automatiquement la variable `SYMFONY_DECRYPTION_SECRET`.

### Option 2 : Déploiement Manuel de la Clé

```bash
# Sur le serveur de production, copier la clé privée
scp config/secrets/prod/prod.decrypt.private.php user@server:/path/to/app/config/secrets/prod/
```

### Option 3 : Vault (Entreprise)

Pour les environnements sensibles, utiliser un vault (HashiCorp Vault, AWS Secrets Manager, etc.).

## 🔄 Rotation des Secrets

### Quand Faire une Rotation ?

- **Obligatoire** :
  - Fuite de secret avérée
  - Départ d'un collaborateur ayant accès
  - Compromission du serveur/dépôt

- **Recommandé** :
  - Tous les 6-12 mois (bonnes pratiques)
  - Après un audit de sécurité
  - Migration infrastructure

### Processus de Rotation

```bash
# 1. Générer un nouveau secret
php bin/console secrets:set API_KEY_NEW --random --env=prod

# 2. Déployer l'application avec les deux secrets (ancien + nouveau)
# L'application supporte temporairement les deux clés

# 3. Migrer les services externes vers la nouvelle clé

# 4. Supprimer l'ancien secret
php bin/console secrets:remove API_KEY_OLD --env=prod

# 5. Redéployer l'application
```

## 📊 Matrice de Migration

| Secret | Priorité | Action Recommandée | Délai |
|--------|----------|---------------------|-------|
| `APP_SECRET` | 🔴 P1 | Migrer vers Symfony Secrets | Immédiat |
| `JWT_SECRET_KEY` | 🔴 P1 | Migrer vers Symfony Secrets | Immédiat |
| `S3_SECRET_KEY` | 🔴 P1 | Migrer vers Symfony Secrets | Immédiat |
| `OPENAI_API_KEY` | 🟠 P2 | Migrer si utilisé en prod | Court terme |
| `ANTHROPIC_API_KEY` | 🟠 P2 | Migrer si utilisé en prod | Court terme |
| `MAILER_DSN` | 🟡 P3 | Optionnel (contient password) | Moyen terme |

**Priorités** :
- **P1** : Critique, migration immédiate en production
- **P2** : Haute, migration avant activation de la fonctionnalité
- **P3** : Moyenne, migration souhaitable mais non bloquante

## ✅ Checklist de Sécurité

### Configuration Actuelle (Développement)

- [x] `.env` dans `.gitignore`
- [x] `.env.example` fourni sans valeurs sensibles
- [x] Documentation SETUP-SECRETS.md
- [ ] Symfony Secrets configuré (dev)
- [ ] Rotation des secrets documentée

### Configuration Production (À Faire)

- [ ] Symfony Secrets configuré (prod)
- [ ] Clé privée déployée de manière sécurisée
- [ ] Secrets critiques migrés (APP_SECRET, JWT, S3)
- [ ] Processus de rotation documenté et testé
- [ ] Monitoring des accès aux secrets
- [ ] Backup sécurisé de la clé de déchiffrement

## 📚 Ressources

- **Documentation Symfony** : https://symfony.com/doc/current/configuration/secrets.html
- **Bonnes pratiques** : https://www.owasp.org/index.php/Key_Management_Cheat_Sheet
- **Guide rotation** : https://symfony.com/doc/current/configuration/secrets.html#rotating-secrets

## 🎯 Actions Recommandées (Post Lot 11bis)

### Court Terme (1-2 semaines)

1. Tester Symfony Secrets en environnement de développement
2. Documenter le processus de déploiement avec secrets
3. Migrer les 3 secrets critiques (APP_SECRET, JWT_SECRET_KEY, S3_SECRET_KEY)

### Moyen Terme (1-2 mois)

4. Définir et documenter une politique de rotation
5. Implémenter le monitoring des accès secrets
6. Former l'équipe au workflow Symfony Secrets

### Long Terme (3-6 mois)

7. Évaluer l'utilisation d'un vault entreprise (si croissance)
8. Automatiser la rotation des secrets non critiques
9. Audit annuel des secrets et permissions

---

**Dernière mise à jour** : 31 décembre 2025
**Responsable** : Équipe DevSecOps
**Statut** : ✅ Audit complété, implémentation recommandée post-11bis
