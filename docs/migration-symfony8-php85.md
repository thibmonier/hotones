# 📋 Rapport de Migration PHP 8.5 / Symfony 8.0

**Date de création** : 2 décembre 2025
**Auteur** : Équipe technique HotOnes
**Statut actuel** : ✅ PHP 8.5.0 | 🔄 Symfony 7.4.0 (LTS) → Symfony 8.0 (migration partielle en cours)

---

## 🎯 Objectifs de la Migration

### PHP 8.5
- ✅ **DÉJÀ FAIT** : Migration vers PHP 8.5.0 complétée
- Environnement local déjà sous PHP 8.5.0
- Profiter des améliorations de performance et des nouvelles fonctionnalités

### Symfony 8.0
- 🔄 **EN COURS** : Migration progressive de Symfony 7.4 (LTS) vers 8.0
- Actuellement : **mix de packages 7.4 et 8.0**
- Objectif : **100% Symfony 8.0** stable

---

## 📊 État Actuel de la Stack

### Versions Installées

#### PHP
```
PHP 8.5.0 (cli) (built: Nov 18 2025)
Zend Engine v4.5.0
✅ Migration PHP 8.5 : TERMINÉE
```

#### Symfony
```
Symfony 7.4.0 (env: dev, debug: true)
🔄 Migration partielle vers 8.0 en cours
```

### Analyse des Packages Symfony

#### ✅ Packages déjà migrés vers 8.0 (47 packages)

| Package | Version | Statut |
|---------|---------|--------|
| symfony/asset | 8.0.0 | ✅ |
| symfony/asset-mapper | 8.0.0 | ✅ |
| symfony/clock | 8.0.0 | ✅ |
| symfony/doctrine-messenger | 8.0.0 | ✅ |
| symfony/dotenv | 8.0.0 | ✅ |
| symfony/error-handler | 8.0.0 | ✅ |
| symfony/expression-language | 8.0.0 | ✅ |
| symfony/finder | 8.0.0 | ✅ |
| symfony/form | 8.0.0 | ✅ |
| symfony/html-sanitizer | 8.0.0 | ✅ |
| symfony/intl | 8.0.0 | ✅ |
| symfony/mailer | 8.0.0 | ✅ |
| symfony/messenger | 8.0.0 | ✅ |
| symfony/mime | 8.0.0 | ✅ |
| symfony/notifier | 8.0.0 | ✅ |
| symfony/options-resolver | 8.0.0 | ✅ |
| symfony/password-hasher | 8.0.0 | ✅ |
| symfony/property-info | 8.0.0 | ✅ |
| symfony/redis-messenger | 8.0.0 | ✅ |
| symfony/routing | 8.0.0 | ✅ |
| symfony/runtime | 8.0.0 | ✅ |
| symfony/scheduler | 8.0.0 | ✅ |
| symfony/security-core | 8.0.0 | ✅ |
| symfony/security-csrf | 8.0.0 | ✅ |
| symfony/security-http | 8.0.0 | ✅ |
| symfony/serializer | 8.0.0 | ✅ |
| symfony/stopwatch | 8.0.0 | ✅ |
| symfony/translation | 8.0.0 | ✅ |
| symfony/twig-bridge | 8.0.0 | ✅ |
| symfony/type-info | 8.0.0 | ✅ |
| symfony/validator | 8.0.0 | ✅ |
| symfony/var-dumper | 8.0.0 | ✅ |
| symfony/web-link | 8.0.0 | ✅ |
| symfony/web-profiler-bundle | 8.0.0 | ✅ |
| ... et 13 autres | 8.0.0 | ✅ |

#### 🔄 Packages Core restant en 7.4 (12 packages critiques)

| Package | Version Actuelle | Version Cible | Priorité |
|---------|------------------|---------------|----------|
| **symfony/framework-bundle** | 7.4.0 | 8.0.0 | 🔴 CRITIQUE |
| **symfony/console** | 7.4.0 | 8.0.0 | 🔴 CRITIQUE |
| **symfony/security-bundle** | 7.4.0 | 8.0.0 | 🔴 CRITIQUE |
| **symfony/twig-bundle** | 7.4.0 | 8.0.0 | 🔴 CRITIQUE |
| **symfony/http-kernel** | 7.4.0 | 8.0.0 | 🔴 CRITIQUE |
| **symfony/http-client** | 7.4.0 | 8.0.0 | 🟡 HAUTE |
| symfony/cache | 7.4.0 | 8.0.0 | 🟡 HAUTE |
| symfony/config | 7.4.0 | 8.0.0 | 🟡 HAUTE |
| symfony/dependency-injection | 7.4.0 | 8.0.0 | 🟡 HAUTE |
| symfony/event-dispatcher | 7.4.0 | 8.0.0 | 🟡 HAUTE |
| symfony/http-foundation | 7.4.0 | 8.0.0 | 🟡 HAUTE |
| symfony/var-exporter | 7.4.0 | 8.0.0 | 🟢 BASSE |

---

## 🔍 Audit de Compatibilité

### Dépendances Tier (Écosystème Symfony)

#### ✅ Compatible Symfony 8.0

| Package | Version | Statut |
|---------|---------|--------|
| **doctrine/dbal** | 4.4.0 | ✅ Compatible |
| **doctrine/orm** | 3.5.8 | ✅ Compatible |
| **doctrine/doctrine-bundle** | 2.18.1 | ⚠️ 3.1.0 disponible (recommandé pour Symfony 8) |
| **api-platform/core** | 4.2.8 | ✅ Compatible |
| **lexik/jwt-authentication-bundle** | 3.1.1 | ✅ Compatible |
| twig/twig | 3.22.0 | ✅ Compatible |
| monolog/monolog | via symfony/monolog-bundle 3.11.0 | ⚠️ 4.0.0 disponible |

#### 🟡 Mises à jour mineures recommandées

| Package | Version Actuelle | Version Recommandée |
|---------|------------------|---------------------|
| endroid/qr-code-bundle | 6.0.0 | 6.1.0 |
| phpstan/phpstan-doctrine | 2.0.11 | 2.0.12 |
| scheb/2fa-bundle | 7.12.1 | 7.12.2 |
| scheb/2fa-totp | 7.12.1 | 7.12.2 |
| friendsofphp/php-cs-fixer | 3.91.0 | 3.91.2 |

#### ✅ Autres dépendances critiques

| Package | Version | Statut |
|---------|---------|--------|
| openai-php/client | 0.18.0 | ✅ Compatible PHP 8.5 |
| anthropic-ai/sdk | 0.3.0 | ✅ Compatible PHP 8.5 (0.4.0 disponible) |
| dompdf/dompdf | 3.1.4 | ✅ Compatible |
| phpoffice/phpspreadsheet | 5.3 | ✅ Compatible |

---

## 🚨 Deprecations Détectées

### ✅ Deprecations Corrigées Récemment

1. **Doctrine DBAL 4.0** :
   - ❌ `Connection::PARAM_STR_ARRAY` → ✅ `ArrayParameterType::STRING`
   - ❌ `getDatabasePlatform()->getName()` → ✅ Méthodes typées

2. **Symfony Routing** :
   - ❌ `@Route` annotation → ✅ `#[Route]` attribute

3. **Doctrine ORM** :
   - ❌ `@ORM\Column(type="string")` → ✅ `#[ORM\Column(type: Types::STRING)]`

### 🔍 Deprecations Restantes (à vérifier)

#### Symfony 7.4 → 8.0

1. **Configuration XML** :
   ```
   User Deprecated: Since symfony/dependency-injection 7.4: XML configuration format is deprecated, use YAML or PHP instead.
   ```
   - **Action** : Migrer les configurations XML vers YAML ou PHP
   - **Fichiers concernés** : Potentiellement bundles tiers, à auditer

2. **Contrats Symfony** :
   - Vérifier l'utilisation de contrats dépréciés dans les services custom
   - Audit des interfaces `*Interface` obsolètes

3. **EventDispatcher** :
   - Vérifier que tous les événements utilisent les nouvelles conventions Symfony 8

---

## 📝 Plan de Migration Symfony 8.0

### Phase 1 : Préparation (1-2 jours)

#### 1.1 Mise à jour des dépendances mineures
```bash
composer update endroid/qr-code-bundle scheb/2fa-bundle scheb/2fa-totp phpstan/phpstan-doctrine --with-dependencies
```

#### 1.2 Mise à jour Doctrine Bundle vers 3.1
```bash
composer require doctrine/doctrine-bundle:"^3.1" --with-dependencies
```
**Attention** : Vérifier les breaking changes dans [CHANGELOG Doctrine Bundle 3.0](https://github.com/doctrine/DoctrineBundle/releases/tag/3.0.0)

#### 1.3 Audit des fichiers XML de configuration
```bash
find config/ -name "*.xml" -type f
```
- Migrer vers YAML ou PHP selon les cas
- Priorité : fichiers applicatifs (pas bundles tiers)

---

### Phase 2 : Migration des Packages Core (2-3 jours)

#### 2.1 Mise à jour des bundles Symfony critiques

**Commande de migration progressive** :
```bash
composer require \
    symfony/framework-bundle:"^8.0" \
    symfony/console:"^8.0" \
    symfony/security-bundle:"^8.0" \
    symfony/twig-bundle:"^8.0" \
    symfony/http-kernel:"^8.0" \
    symfony/http-client:"^8.0" \
    symfony/cache:"^8.0" \
    symfony/config:"^8.0" \
    symfony/dependency-injection:"^8.0" \
    symfony/event-dispatcher:"^8.0" \
    symfony/http-foundation:"^8.0" \
    symfony/var-exporter:"^8.0" \
    --with-all-dependencies
```

**⚠️ Risques** :
- Breaking changes dans `FrameworkBundle` (routes, config)
- Changements dans `SecurityBundle` (firewalls, voters)
- Modifications API dans `HttpKernel` (events, controllers)

#### 2.2 Tests de régression après migration

```bash
# 1. Vérifier que Symfony boot
php bin/console about

# 2. Lancer tous les tests
composer test

# 3. Vérifier les routes
php bin/console debug:router

# 4. Vérifier la sécurité
php bin/console debug:firewall

# 5. Vérifier les services
php bin/console debug:container --show-private
```

---

### Phase 3 : Refactoring des Deprecations (2-3 jours)

#### 3.1 Migration des configurations XML vers YAML

**Exemple** :
```xml
<!-- Avant (XML) -->
<service id="app.my_service" class="App\Service\MyService">
    <argument type="service" id="doctrine.orm.entity_manager"/>
</service>
```

```yaml
# Après (YAML)
services:
    app.my_service:
        class: App\Service\MyService
        arguments:
            $entityManager: '@doctrine.orm.entity_manager'
```

#### 3.2 Mise à jour des Events Symfony 8

Vérifier les événements custom dans `src/EventListener/` et `src/EventSubscriber/`

#### 3.3 Revue des Controllers

- Vérifier l'usage des nouvelles méthodes `HttpKernel\Attribute\*`
- Audit des `ParamConverter` (déprécié, utiliser `MapEntity`)

---

### Phase 4 : Tests et Validation (1-2 jours)

#### 4.1 Tests Automatisés

```bash
# Tests unitaires
composer test-unit

# Tests d'intégration
composer test-integration

# Tests fonctionnels
composer test-functional

# Tests API
composer test-api

# Tests E2E
composer test-e2e
```

#### 4.2 Tests Manuels

- [ ] Login / 2FA
- [ ] Création projet
- [ ] Création devis
- [ ] Saisie de temps (timesheet)
- [ ] Dashboard Analytics
- [ ] Dashboard Staffing
- [ ] Génération PDF (devis)
- [ ] Export Excel
- [ ] API endpoints (`/api/projects`, `/api/timesheets`)
- [ ] Scheduler (vérifier les tâches planifiées)
- [ ] Messenger (vérifier la file de messages)

#### 4.3 Performance Benchmarking

```bash
# Avant migration
ab -n 1000 -c 10 http://localhost:8080/

# Après migration
ab -n 1000 -c 10 http://localhost:8080/

# Comparer les résultats
```

**Objectif** : Pas de régression de performance > 5%

---

### Phase 5 : Mise en Production (1 jour)

#### 5.1 Préparation Docker

**Mettre à jour `docker-compose.yml` et `Dockerfile`** :
```dockerfile
FROM php:8.5-fpm-alpine

# S'assurer que toutes les extensions PHP sont compatibles
RUN docker-php-ext-install pdo pdo_mysql opcache intl
```

#### 5.2 Déploiement Render

**Vérifier `render.yaml`** :
```yaml
services:
  - type: web
    name: hotones
    runtime: docker
    envVars:
      - key: APP_ENV
        value: prod
      - key: PHP_VERSION
        value: "8.5"
```

#### 5.3 Checklist de déploiement

- [ ] Backup de la base de données de production
- [ ] Tests sur environnement de staging
- [ ] Déploiement sur production
- [ ] Monitoring Sentry (alertes erreurs)
- [ ] Vérification logs Symfony (`var/log/prod.log`)
- [ ] Health check de tous les endpoints critiques

---

## 🎯 Estimation Totale

| Phase | Durée | Complexité |
|-------|-------|------------|
| Phase 1 : Préparation | 1-2 jours | 🟢 Faible |
| Phase 2 : Migration Core | 2-3 jours | 🔴 Haute |
| Phase 3 : Refactoring | 2-3 jours | 🟡 Moyenne |
| Phase 4 : Tests | 1-2 jours | 🟡 Moyenne |
| Phase 5 : Production | 1 jour | 🟡 Moyenne |
| **TOTAL** | **7-11 jours** | 🟡 |

---

## ⚠️ Risques Identifiés

### Risques Critiques

1. **Breaking changes Symfony 8.0** :
   - Modification d'API dans `FrameworkBundle`
   - Changements dans le système de sécurité
   - Incompatibilités potentielles avec bundles tiers

2. **Doctrine Bundle 3.0** :
   - Changements de configuration
   - Modifications dans les repositories
   - Lazy loading modifié

3. **Régression de performance** :
   - Nouvelles versions peuvent introduire des ralentissements
   - Cache à reconfigurer

### Risques Modérés

1. **Tests instables** :
   - 30 tests actuellement en échec (UserFactory)
   - À corriger avant migration

2. **Dépendances bloquées** :
   - Certains bundles tiers peuvent ne pas supporter Symfony 8.0 immédiatement
   - Nécessité d'attendre des mises à jour

### Mitigation

- **Tests automatisés exhaustifs** avant déploiement
- **Environnement de staging** pour validation
- **Rollback plan** : possibilité de revenir à Symfony 7.4 (LTS)
- **Monitoring renforcé** post-déploiement (Sentry, logs)

---

## 📚 Ressources et Documentation

### Documentation Officielle

- [Symfony 8.0 Release Notes](https://symfony.com/releases/8.0)
- [Symfony Upgrade Guide 7.4 → 8.0](https://github.com/symfony/symfony/blob/8.0/UPGRADE-8.0.md)
- [Doctrine Bundle 3.0 Changelog](https://github.com/doctrine/DoctrineBundle/releases/tag/3.0.0)
- [PHP 8.5 New Features](https://www.php.net/releases/8.5/en.php)

### Outils Utiles

```bash
# Outil de migration Symfony
composer require --dev symfony/upgrade

# Analyse de deprecations
php bin/console debug:container --deprecations

# Vérification des exigences
php bin/console about
symfony check:requirements
```

---

## ✅ Checklist Finale

### Avant Migration

- [ ] Backup base de données
- [ ] Backup code source (tag Git)
- [ ] Corriger les 30 tests en échec (UserFactory)
- [ ] Audit complet des deprecations
- [ ] Communication équipe (planning maintenance)

### Pendant Migration

- [ ] Phase 1 : Préparation ✅
- [ ] Phase 2 : Migration Core
- [ ] Phase 3 : Refactoring
- [ ] Phase 4 : Tests
- [ ] Phase 5 : Production

### Après Migration

- [ ] Monitoring 24h (Sentry, logs)
- [ ] Performance check (AB testing)
- [ ] Feedback utilisateurs
- [ ] Documentation mise à jour
- [ ] Retour d'expérience (post-mortem)

---

## 📞 Contact et Support

**Questions techniques** : Consulter la documentation Symfony ou ouvrir une issue GitHub

**Urgence production** : Vérifier Sentry et logs Render

---

**Dernière mise à jour** : 2 décembre 2025
**Prochaine revue** : Après Phase 2 (migration Core)
