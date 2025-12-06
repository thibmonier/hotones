# Plan de migration vers Symfony 8

## Prérequis
✅ PHP 8.4
✅ Tous les packages compatibles Symfony 8
✅ Tests passent (99/99)
✅ 0 erreur PHPStan

## Étapes de migration

### 1. Mettre à jour composer.json

```bash
# Forcer Symfony 8 uniquement
composer require "symfony/*:^8.0" --with-all-dependencies
```

**OU modification manuelle de composer.json :**

```json
// Remplacer toutes les occurrences de :
"^7.4 || ^8.0"  →  "^8.0"

// Packages spécifiques à mettre à jour :
"symfony/var-exporter": "^8.0"  // était ^7.4
"symfony/monolog-bundle": "^4.0"  // optionnel, v3.11 fonctionne
```

### 2. Mettre à jour le lock file

```bash
composer update symfony/* --with-all-dependencies
```

### 3. Vérifications post-migration

```bash
# Vérifier la version
php bin/console --version  # Doit afficher 8.0.x

# Vérifier les dépréciations
php bin/console debug:container --deprecations

# Tests
composer test

# Code quality
composer check-code
```

### 4. Changements breaking potentiels

**Symfony 8.0 breaking changes :**

1. **Attributs PHP 8** : Tous les attributs `#[Route]`, `#[AsCommand]` etc. ✅ Déjà utilisés
2. **Serializer** : `Annotation\Groups` → `Attribute\Groups` ✅ **FAIT**
3. **Type hints stricts** : Plus de mixed, tout typé ✅ Déjà en place
4. **Validation** : Contraintes strictes ⚠️ À vérifier
5. **Messenger** : Handler interface changée ⚠️ À vérifier

### 5. Tests de régression

**Zones à tester en priorité :**
- [ ] Authentification 2FA
- [ ] API Platform endpoints
- [ ] Messenger + Scheduler
- [ ] Form validations
- [ ] Assets build (Webpack Encore)
- [ ] Cache Redis
- [ ] Emails (Mailer)

## Rollback si problème

```bash
git checkout composer.json composer.lock
composer install
```

## Estimation

**Temps nécessaire :** 2-4 heures
- Migration : 30 min
- Tests : 1-2h
- Corrections éventuelles : 1-2h

**Risque :** 🟡 **MOYEN**
- Code déjà moderne (PHP 8.4, attributs)
- Bundles tiers compatibles
- Tests complets en place

## Recommandation

✅ **MIGRATION RECOMMANDÉE**

Le projet est **prêt techniquement** pour Symfony 8. La migration devrait être fluide grâce à :
- PHP 8.4 déjà utilisé
- Code moderne avec attributs
- Migration Serializer déjà faite
- 0 erreur PHPStan
- Tests complets

**Meilleur moment :** Maintenant, avant d'ajouter de nouvelles fonctionnalités.
