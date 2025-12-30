# Plan de Migration PHP 8.5 + Symfony 8

**Statut:** 📋 Planification (PHP 8.5 et Symfony 8.0 prévus pour novembre 2025)

**Date de création:** 27 novembre 2024

**Versions actuelles:**
- PHP: 8.4
- Symfony: 7.3

**Versions cibles:**
- PHP: 8.5 (prévu novembre 2025)
- Symfony: 8.0 (prévu novembre 2025)

---

## 📊 Vue d'ensemble

Cette migration est une **mise à jour majeure** qui nécessite une planification approfondie et des tests exhaustifs. Les versions cibles ne sont pas encore sorties, ce document sera mis à jour au fur et à mesure des annonces officielles.

### Bénéfices attendus

**PHP 8.5:**
- Améliorations de performance
- Nouvelles fonctionnalités du langage
- Corrections de bugs et améliorations de sécurité

**Symfony 8:**
- Architecture modernisée
- Meilleures performances
- Nouvelles fonctionnalités DX (Developer Experience)
- Support à long terme (LTS probablement pour 8.4 en 2026)

### Risques identifiés

- ⚠️ **Breaking changes** dans PHP et Symfony
- ⚠️ **Compatibilité des bundles tiers** (certains peuvent ne pas supporter Symfony 8 immédiatement)
- ⚠️ **APIs dépréciées** nécessitant des refactoring
- ⚠️ **Tests E2E** pouvant nécessiter des ajustements
- ⚠️ **Environnement de production** (Render.com) doit supporter PHP 8.5

---

## 🎯 Phases du Sprint

### Phase 1: Préparation et Analyse (2-3 jours)

#### 1.1 Veille technologique
- [ ] Suivre les RFCs PHP 8.5 sur https://wiki.php.net/rfc
- [ ] Lire le changelog préliminaire de Symfony 8 sur https://symfony.com/releases/8.0
- [ ] S'abonner aux notifications de release

#### 1.2 Analyse des dépendances
- [ ] Vérifier la compatibilité de chaque bundle avec Symfony 8
- [ ] Identifier les bundles à mettre à jour
- [ ] Identifier les bundles nécessitant des alternatives
- [ ] Documenter les versions compatibles

**Dépendances critiques à vérifier:**

| Bundle | Version actuelle | Symfony 8 compatible | Notes |
|--------|------------------|---------------------|-------|
| api-platform/core | ^4.2.6 | ✅ Probablement | À vérifier |
| doctrine/orm | ^3.5.7 | ✅ Oui | Compatible |
| doctrine/doctrine-bundle | ^2.18.1 | ? | À vérifier |
| lexik/jwt-authentication-bundle | ^3.1.1 | ? | À vérifier |
| scheb/2fa-bundle | >=7.12.1 | ? | À vérifier |
| endroid/qr-code-bundle | >=6 | ? | À vérifier |
| knplabs/knp-paginator-bundle | ^6.9.1 | ? | À vérifier |
| symfony/webpack-encore-bundle | ^2.3 | ✅ Probablement | À vérifier |
| dompdf/dompdf | ^3.1.4 | ✅ Indépendant | OK |
| phpoffice/phpspreadsheet | ^5.3 | ✅ Indépendant | OK |
| gedmo/doctrine-extensions | >=3.21 | ? | À vérifier |
| sentry/sentry-symfony | ^5.6 | ? | À vérifier |

#### 1.3 Identification des breaking changes

**PHP 8.5 (à compléter à la sortie):**
- [ ] Lire UPGRADING.md officiel
- [ ] Identifier les fonctions dépréciées dans le code
- [ ] Identifier les changements de comportement
- [ ] Scanner le code avec `phpstan` niveau 9

**Symfony 8.0 (à compléter à la sortie):**
- [ ] Lire UPGRADE-8.0.md
- [ ] Identifier les classes/méthodes dépréciées utilisées
- [ ] Vérifier les changements dans:
  - Routing (annotations → attributs si nécessaire)
  - Security (authentification/autorisation)
  - Forms (types de formulaire)
  - Doctrine integration
  - Messenger
  - Mailer

#### 1.4 Environnement de test
- [ ] Créer une branche `feature/php85-symfony8`
- [ ] Configurer un environnement Docker local avec PHP 8.5
- [ ] Vérifier que les CI/CD acceptent PHP 8.5

---

### Phase 2: Migration technique (5-7 jours)

#### 2.1 Mise à jour Docker

**Fichiers à modifier:**
- `Dockerfile` (ligne 23): `FROM php:8.5-fpm-alpine`
- `docker-compose.yml`: Vérifier compatibilité images (nginx, mariadb, redis)

**Extensions PHP à vérifier:**
- ext-bcmath ✅
- ext-ctype ✅
- ext-iconv ✅
- ext-redis ✅
- apcu ✅
- intl ✅
- opcache ✅
- gd ✅
- pdo_mysql ✅
- zip ✅

#### 2.2 Mise à jour composer.json

```json
{
  "require": {
    "php": ">=8.5",
    "symfony/framework-bundle": "8.0.*",
    "symfony/console": "8.0.*",
    "symfony/doctrine-messenger": "8.0.*",
    // ... tous les composants Symfony
  }
}
```

**Commandes:**
```bash
# Sauvegarder composer.lock
cp composer.lock composer.lock.backup

# Mettre à jour PHP requirement
composer config platform.php 8.5.0

# Mettre à jour vers Symfony 8
composer require "symfony/framework-bundle:^8.0" --no-update
composer require "symfony/console:^8.0" --no-update
# ... répéter pour tous les composants Symfony

# Mettre à jour toutes les dépendances
composer update

# En cas de conflit, mettre à jour les bundles un par un
composer why-not symfony/framework-bundle 8.0
```

#### 2.3 Polyfills à supprimer

Mettre à jour la section `replace` dans composer.json:
```json
"replace": {
  "symfony/polyfill-ctype": "*",
  "symfony/polyfill-iconv": "*",
  "symfony/polyfill-php72": "*",
  "symfony/polyfill-php73": "*",
  "symfony/polyfill-php74": "*",
  "symfony/polyfill-php80": "*",
  "symfony/polyfill-php81": "*",
  "symfony/polyfill-php82": "*",
  "symfony/polyfill-php83": "*",
  "symfony/polyfill-php84": "*"
}
```

#### 2.4 Configuration Symfony

Vérifier et mettre à jour si nécessaire:
- `config/packages/*.yaml` - Nouvelles options de configuration
- `config/routes/*.yaml` - Changements de routing
- `config/services.yaml` - Autowiring, tags
- `.env` - Nouvelles variables d'environnement

#### 2.5 Code source

**Rechercher et remplacer les dépréciations:**

```bash
# Trouver les usages de méthodes dépréciées
grep -r "deprecated" vendor/symfony/ | grep -i "trigger_error"

# Scanner avec PHPStan
composer phpstan-clear
composer phpstan
```

**Points d'attention:**

1. **Attributs PHP 8** - Vérifier que tous les attributs sont à jour
2. **Types de retour** - Ajouter les types manquants si Symfony 8 les requiert
3. **Constructeur de services** - Vérifier l'injection de dépendances
4. **Security** - Nouvelles APIs d'authentification
5. **Validators** - Changements dans les contraintes
6. **Forms** - Types de formulaires mis à jour

---

### Phase 3: Tests et Validation (3-5 jours)

#### 3.1 Tests automatisés

```bash
# Rebuilder les images Docker
docker compose down -v
docker compose build --no-cache
docker compose up -d

# Vérifier les versions
docker compose exec app php -v
docker compose exec app php bin/console --version

# Installer les dépendances
docker compose exec app composer install

# Lancer la suite de tests
docker compose exec app composer test

# Tests par suite
docker compose exec app composer test-unit
docker compose exec app composer test-integration
docker compose exec app composer test-functional
docker compose exec app composer test-api
docker compose exec app composer test-e2e

# Code quality
docker compose exec app composer check-code
```

**Checklist des tests:**
- [ ] ✅ Tous les tests unitaires passent
- [ ] ✅ Tous les tests d'intégration passent
- [ ] ✅ Tous les tests fonctionnels passent
- [ ] ✅ Tous les tests API passent
- [ ] ✅ Tests E2E (Panther) fonctionnels
- [ ] ✅ PHPStan niveau 3 sans erreur
- [ ] ✅ PHP CS Fixer sans erreur
- [ ] ✅ PHPMD sans erreur critique

#### 3.2 Tests manuels fonctionnels

**Authentification & Sécurité:**
- [ ] Connexion utilisateur (email/mot de passe)
- [ ] 2FA (TOTP)
- [ ] Déconnexion
- [ ] Gestion des rôles (INTERVENANT, CHEF_PROJET, MANAGER, COMPTA, ADMIN)
- [ ] API JWT authentication

**Contributeurs:**
- [ ] Liste des contributeurs
- [ ] Création d'un contributeur
- [ ] Édition d'un contributeur
- [ ] Upload d'avatar
- [ ] Profils multiples (dev, lead, chef projet)
- [ ] Périodes d'emploi (CJM, TJM, salaire)
- [ ] Compétences avec niveaux

**Clients & Projets:**
- [ ] Création client avec contacts
- [ ] Service level (VIP, prioritaire, standard)
- [ ] Création projet (forfait/régie)
- [ ] Technologies et catégories
- [ ] Tâches et sous-tâches (Kanban)

**Devis & Commandes:**
- [ ] Création de devis
- [ ] Sections et lignes de commande
- [ ] Génération PDF
- [ ] Signature du devis (passage à "signé")
- [ ] Génération automatique de tâches projet

**Temps & Planning:**
- [ ] Saisie de temps (timesheet hebdomadaire)
- [ ] Timer start/stop
- [ ] Imputation sur tâches/sous-tâches
- [ ] Planning ressources (FullCalendar)
- [ ] Drag & drop de plannings
- [ ] Affichage des vacances

**Analytics & Rentabilité:**
- [ ] Dashboard analytics
- [ ] Dashboard profitabilité
- [ ] Dashboard ventes
- [ ] Dashboard staffing
- [ ] Prédiction de charge
- [ ] Calcul des métriques (commandes console)
- [ ] Export Excel

**Système:**
- [ ] Notifications
- [ ] Cache (Redis)
- [ ] Message queue (Messenger)
- [ ] Emails (Mailer)
- [ ] Logs (Monolog)
- [ ] Admin config (technologies, catégories, profils)

#### 3.3 Tests de performance

```bash
# Vérifier opcache
docker compose exec app php -i | grep opcache

# Benchmarks simples
time docker compose exec app php bin/console cache:clear
time docker compose exec app php bin/console cache:warmup

# Temps de réponse des pages principales
curl -o /dev/null -s -w 'Total time: %{time_total}s\n' http://localhost:8080/login
```

**Métriques à comparer (avant/après):**
- [ ] Temps de réponse homepage
- [ ] Temps de réponse liste contributeurs
- [ ] Temps de réponse dashboard analytics
- [ ] Temps de calcul des métriques
- [ ] Taille du cache généré
- [ ] Utilisation mémoire PHP

---

### Phase 4: Documentation et Déploiement (1-2 jours)

#### 4.1 Documentation

**Fichiers à mettre à jour:**
- [ ] `CLAUDE.md` - Versions PHP/Symfony
- [ ] `README.md` - Prérequis système
- [ ] `docs/architecture.md` - Stack technique
- [ ] `docs/deployment-*.md` - Instructions de déploiement
- [ ] `CHANGELOG.md` - Ajouter entrée pour migration

#### 4.2 Environnements

**Local/Dev:**
- [ ] Dockerfile mis à jour
- [ ] docker-compose.yml mis à jour
- [ ] Instructions build-assets.sh testées

**Staging (si disponible):**
- [ ] Déployer sur environnement de staging
- [ ] Tests de validation
- [ ] Tests de charge

**Production (Render.com):**
- [ ] Vérifier que Render supporte PHP 8.5
- [ ] Mettre à jour `render.yaml` si nécessaire
- [ ] Planifier une fenêtre de maintenance
- [ ] Préparer le plan de rollback
- [ ] Déployer

#### 4.3 Plan de rollback

**En cas de problème critique en production:**

1. **Rollback Docker:**
   ```bash
   # Revenir à l'image précédente
   docker tag hotones-app:latest hotones-app:php84-symfony73
   docker tag hotones-app:previous hotones-app:latest
   ```

2. **Rollback Git:**
   ```bash
   # Créer un tag avant migration
   git tag pre-php85-symfony8
   git push origin pre-php85-symfony8

   # En cas de problème
   git revert <commit-hash-migration>
   git push origin main
   ```

3. **Rollback Composer:**
   ```bash
   # Restaurer composer.lock
   cp composer.lock.backup composer.lock
   composer install
   ```

4. **Rollback base de données:**
   - Si nouvelles migrations: `php bin/console doctrine:migrations:migrate prev`
   - Backup avant migration recommandé

---

## 📋 Checklist finale avant production

**Code:**
- [ ] Toutes les dépréciations corrigées
- [ ] Tests automatisés à 100% (53/53)
- [ ] Code quality: PHPStan ✅, PHP CS Fixer ✅, PHPMD ✅
- [ ] Aucun warning/notice dans les logs

**Infrastructure:**
- [ ] Images Docker buildent correctement
- [ ] Extensions PHP toutes installées
- [ ] Redis fonctionne
- [ ] MariaDB compatible
- [ ] Nginx configuré correctement

**Tests:**
- [ ] Tests manuels complets effectués
- [ ] Tests de performance satisfaisants
- [ ] Tests de charge OK (si applicable)
- [ ] Tests sur environnement de staging OK

**Documentation:**
- [ ] CLAUDE.md mis à jour
- [ ] README.md mis à jour
- [ ] CHANGELOG.md complété
- [ ] Migration guide créé

**Déploiement:**
- [ ] Tag Git créé (pre-migration)
- [ ] Backup base de données créé
- [ ] Fenêtre de maintenance planifiée
- [ ] Plan de rollback prêt
- [ ] Équipe informée

---

## 🔗 Ressources

### Documentation officielle
- **PHP:** https://www.php.net/releases/8.5/en.php (à venir)
- **Symfony:** https://symfony.com/releases/8.0 (à venir)
- **Doctrine:** https://www.doctrine-project.org/
- **API Platform:** https://api-platform.com/

### Outils de migration
- **Rector:** https://getrector.org/ (automatise certaines migrations)
- **PHP Compatibility:** https://github.com/PHPCompatibility/PHPCompatibility

### Guides de migration
- Symfony UPGRADE-8.0.md (à venir dans le repo Symfony)
- PHP UPGRADING (à venir dans le repo PHP)

---

## 📝 Notes de suivi

### Date: [À compléter]
- **Status:**
- **Blockers:**
- **Décisions prises:**
- **Prochaines étapes:**

---

## ⚡ Quick Commands

```bash
# Vérifier les versions actuelles
php -v
php bin/console --version

# Analyser les dépréciations
grep -r "@deprecated" src/

# Vérifier les bundles obsolètes
composer outdated --direct

# Tester la migration
composer require symfony/framework-bundle:^8.0 --dry-run

# Lancer tous les tests
composer test

# Vérifier la qualité du code
composer check-code
```

---

**Dernière mise à jour:** 27 novembre 2024
**Auteur:** Claude Code
**Status:** 📋 En planification
