# Analyse de Compatibilité Symfony 8.0

**Date** : 2 décembre 2025
**Contexte** : Évaluation de faisabilité de migration Symfony 7.4 LTS → 8.0

---

## 🎯 Conclusion

**❌ Migration Symfony 8.0 NON RECOMMANDÉE pour le moment**

**Raison principale** : `scheb/2fa-bundle` (2FA authentication) ne supporte pas encore Symfony 8.0.

**Recommandation** : **Rester sur Symfony 7.4 LTS** jusqu'à la sortie de scheb/2fa-bundle v8.x

---

## 📊 Compatibilité des Bundles Critiques

### ❌ Bundles INCOMPATIBLES Symfony 8.0

| Bundle | Version Actuelle | Support Symfony | Statut |
|--------|------------------|-----------------|--------|
| **scheb/2fa-bundle** | 7.12.2 | ^6.4 \|\| ^7.0 | ❌ **BLOQUANT** |
| **scheb/2fa-totp** | 7.12.2 | ^6.4 \|\| ^7.0 | ❌ **BLOQUANT** |

#### scheb/2fa-bundle

- **Dernière version** : v7.12.2 (1er décembre 2025)
- **Support actuel** : Symfony 6.4 et 7.0 uniquement
- **Branche dev** : 8.x-dev existe (en développement)
- **Impact** : Authentification 2FA (TOTP) indisponible sans ce bundle
- **Sources** :
  - [scheb/2fa-bundle sur Packagist](https://packagist.org/packages/scheb/2fa-bundle)
  - [GitHub - scheb/2fa](https://github.com/scheb/2fa)

---

### ✅ Bundles COMPATIBLES Symfony 8.0

| Bundle | Version Actuelle | Support Symfony 8 | Statut |
|--------|------------------|-------------------|--------|
| **api-platform/core** | 4.2.8 | ✅ ^6.4\|\|^7.0\|\|^8.0 | ✅ OK |
| **doctrine/doctrine-bundle** | 3.1.0 | ✅ Compatible | ✅ OK |
| **doctrine/orm** | 3.5.8 | ✅ Compatible | ✅ OK |
| **doctrine/dbal** | 4.4.0 | ✅ Compatible | ✅ OK |
| **lexik/jwt-authentication-bundle** | 3.1.1 | ✅ Compatible | ✅ OK |
| **symfony/monolog-bundle** | 3.11.0 | ✅ Compatible | ✅ OK |
| **twig/twig** | 3.22.0 | ✅ Compatible | ✅ OK |

#### API Platform

- **Version** : 4.2.8 (30 novembre 2025)
- **Support Symfony 8** : ✅ Plein support depuis v4.2.7
- **Requirement** : symfony/http-foundation ^6.4.14 || ^7.0 || ^8.0
- **Sources** :
  - [api-platform/core sur Packagist](https://packagist.org/packages/api-platform/core)
  - [API Platform Documentation](https://api-platform.com/docs/core/)

#### Doctrine

- **doctrine-bundle 3.1.0** : Compatible Symfony 8 (déjà installé ✅)
- **ORM 3.5.8** : Compatible
- **DBAL 4.4.0** : Compatible

---

### 🔄 Bundles Symfony (État Mixte)

**47 packages Symfony déjà sur 8.0** (voir rapport migration précédent), dont :
- symfony/asset 8.0.0 ✅
- symfony/form 8.0.0 ✅
- symfony/security-core 8.0.0 ✅
- symfony/validator 8.0.0 ✅
- ...

**12 packages core Symfony encore sur 7.4** :
- symfony/framework-bundle 7.4.0
- symfony/console 7.4.0
- symfony/security-bundle 7.4.0
- symfony/twig-bundle 7.4.0
- ...

**Problème** : Impossible de migrer ces packages tant que scheb/2fa-bundle bloque.

---

## 🚫 Autres Blocages Identifiés

### PHP 8.5

**Statut** : ❌ Non compatible avec environnement Docker Alpine

**Problème** :
```
Build error: extension intl failed to compile on PHP 8.5-fpm-alpine
Exit code: 2
```

**Cause** : PHP 8.5 est trop récent (sorti novembre 2025), les extensions Alpine ne sont pas encore toutes compatibles.

**Décision** : ✅ Rester sur **PHP 8.4** (stable et éprouvé)

---

### sabberworm/php-css-parser

**Statut** : ❌ Non compatible PHP 8.5

**Dépendance** : dompdf/dompdf → dompdf/php-svg-lib → sabberworm/php-css-parser

**Version actuelle** : v8.9.0
**Requirement** : php ^5.6.20 || ^7.0.0 || ~8.0.0 || ~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0

**Impact** : Bloque migration PHP 8.5 (génération PDF devis)

**Note** : Non bloquant pour Symfony 8, seulement pour PHP 8.5

---

## 📝 Recommandations

### Court Terme (Q1 2025)

1. ✅ **Rester sur Symfony 7.4 LTS**
   - Support jusqu'en novembre 2028
   - Stable et éprouvé en production
   - Compatible avec tous nos bundles

2. ✅ **Rester sur PHP 8.4**
   - Stable sur Alpine Linux
   - Compatible avec toutes les extensions
   - Performance optimale

3. ✅ **Doctrine Bundle 3.1 migration complétée**
   - Préparation pour Symfony 8 future
   - Configuration nettoyée

### Moyen Terme (Q2-Q3 2025)

1. 🔍 **Surveiller scheb/2fa-bundle v8.x**
   - Suivre le dépôt GitHub : [scheb/2fa](https://github.com/scheb/2fa)
   - Tester la branche 8.x-dev quand stable
   - Migrer vers Symfony 8 une fois v8.0.0 sortie

2. 🔍 **Surveiller PHP 8.5 sur Alpine**
   - Attendre stabilisation des extensions
   - Tester migration dans 3-6 mois

### Actions Immédiates

- [x] Annuler modification Dockerfile (revenir à PHP 8.4)
- [x] Mettre à jour rapport de migration
- [x] Documenter les blocages identifiés
- [ ] Informer l'équipe : Symfony 7.4 LTS est la version stable recommandée

---

## 🎯 Stratégie de Migration Future

### Quand migrer vers Symfony 8.0 ?

**Conditions nécessaires** :
1. ✅ scheb/2fa-bundle v8.x.x stable released
2. ✅ Tests de compatibilité réussis en environnement staging
3. ✅ Aucun autre bundle critique incompatible

**Effort estimé** : 2-3 jours (Phase 2 du plan migration)

### Avantages de rester sur 7.4 LTS

- ✅ **Support long terme** : jusqu'en novembre 2028 (3 ans)
- ✅ **Stabilité** : version LTS éprouvée en production
- ✅ **Compatibilité** : tous les bundles supportés
- ✅ **Performance** : optimisations matures
- ✅ **Sécurité** : patches de sécurité garantis

### Inconvénients de rester sur 7.4

- ❌ Pas d'accès aux nouvelles features Symfony 8
- ❌ Dépendances qui vont progressivement cibler Symfony 8

**Balance** : Les avantages dépassent largement les inconvénients pour le moment.

---

## 📚 Sources

- [scheb/2fa-bundle sur Packagist](https://packagist.org/packages/scheb/2fa-bundle)
- [GitHub - scheb/2fa](https://github.com/scheb/2fa)
- [SchebTwoFactorBundle Documentation](https://symfony.com/bundles/SchebTwoFactorBundle/current/index.html)
- [api-platform/core sur Packagist](https://packagist.org/packages/api-platform/core)
- [API Platform Documentation](https://api-platform.com/docs/core/)
- [Symfony 7.4 Release](https://symfony.com/releases/7.4)
- [Symfony 8.0 Release](https://symfony.com/releases/8.0)

---

**Dernière mise à jour** : 2 décembre 2025
**Prochaine revue** : Mars 2025 (vérifier scheb/2fa-bundle v8.x)
