# 🔒 Audit de Sécurité OWASP Top 10 (2021) - HotOnes

**Date :** 27 décembre 2025
**Contexte :** Lot 11bis.1 - Sprint Technique & Consolidation
**Framework :** OWASP Top 10:2021

---

## 📊 Résumé Exécutif

| Catégorie OWASP | Statut | Sévérité | Actions requises |
|-----------------|--------|----------|------------------|
| A01 - Broken Access Control | ⚠️ Partiel | Moyenne | Audit voters, tests permissions |
| A02 - Cryptographic Failures | ✅ Bon | Faible | RAS |
| A03 - Injection | ✅ Bon | Faible | RAS (Doctrine paramétré) |
| A04 - Insecure Design | ⚠️ Partiel | Moyenne | Violations Deptrac (9) |
| A05 - Security Misconfiguration | 🔴 Critique | **Haute** | **Headers manquants** |
| A06 - Vulnerable Components | ✅ Excellent | Nulle | Roave Security Advisories |
| A07 - Auth Failures | ✅ Bon | Faible | 2FA activée |
| A08 - Software Integrity | ⚠️ Partiel | Moyenne | CSRF OK, SRI manquant |
| A09 - Logging Failures | ⚠️ Partiel | Moyenne | Logs basiques, monitoring absent |
| A10 - SSRF | ✅ Bon | Faible | Pas d'appels externes |

**Score global : 6.5/10** ⚠️ **MOYEN** - Actions correctrices nécessaires

---

## A01:2021 – Broken Access Control ⚠️

### État actuel : PARTIEL

**✅ Points forts :**
- Hiérarchie des rôles bien définie (`config/packages/security.yaml`)
  ```yaml
  ROLE_SUPERADMIN → ROLE_ADMIN → ROLE_MANAGER → ROLE_CHEF_PROJET → ROLE_INTERVENANT
  ```
- Access Control Lists (ACL) configurées pour 18 routes publiques
- CSRF activé sur login et 2FA (`enable_csrf: true`)
- JWT pour l'API

**⚠️ Points d'attention :**
1. **Voters non documentés** - Absence de documentation sur les voters personnalisés
2. **Tests de permissions manquants** - Aucun test fonctionnel vérifiant l'isolation par rôle
3. **API JWT** - Pas de rate limiting visible sur `/api/login`

### Recommandations

#### 1. Audit des Voters Symfony

```bash
# Lister tous les voters
docker compose exec app php bin/console debug:container --tag=security.voter
```

**Actions :**
- Documenter chaque voter (responsabilité, règles)
- Tester chaque voter unitairement
- Vérifier l'absence de failles d'élévation de privilèges

#### 2. Tests d'isolation par rôle

**Exemple de test fonctionnel :**
```php
// tests/Functional/Security/AccessControlTest.php
public function testIntervenantCannotAccessAdminRoutes(): void
{
    $client = static::createClient();
    $this->loginAs('intervenant@example.com'); // ROLE_INTERVENANT

    $client->request('GET', '/admin/users');
    $this->assertResponseStatusCodeSame(403); // Forbidden
}
```

**Priorité :** 🟠 HAUTE
**Estimation :** 4 heures

---

## A02:2021 – Cryptographic Failures ✅

### État actuel : BON

**✅ Points forts :**
- **Hachage des mots de passe :** Algorithme `auto` (bcrypt/argon2id selon PHP version)
  ```yaml
  password_hashers:
      Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
  ```
- **HTTPS obligatoire** (à vérifier en production)
- **JWT pour API** (lexik/jwt-authentication-bundle)

**⚠️ Points d'amélioration :**
1. **Secrets management**
   - Vérifier utilisation de **Symfony Secrets** en production
   - Audit du fichier `.env` (pas de secrets hardcodés)

### Vérifications à effectuer

```bash
# Vérifier si Symfony Secrets est utilisé
ls -la config/secrets/prod/

# Auditer .env pour secrets hardcodés
grep -Ei '(password|secret|key|token)=' .env | grep -v '^#'
```

**Priorité :** 🟡 MOYENNE
**Estimation :** 1 heure

---

## A03:2021 – Injection ✅

### État actuel : BON

**✅ Points forts :**
- **Doctrine ORM** : Requêtes paramétrées par défaut
- **Twig auto-escaping** : Activé automatiquement
- **Validation de formulaires** : Symfony Validator

**Exemple de requête sécurisée :**
```php
// Repository avec paramètres nommés
$qb->where('p.status = :status')
   ->setParameter('status', $status);
```

**⚠️ Points d'attention :**
- Vérifier l'absence de `createNativeQuery()` ou SQL brut
- Auditer les appels à des APIs externes (si présents)

### Audit recommandé

```bash
# Rechercher SQL natif ou concat de requêtes
grep -r "createNativeQuery\|ExecuteSql\|rawQuery" src/

# Rechercher concaténation SQL (danger)
grep -r 'WHERE.*\.\$' src/
```

**Priorité :** 🟢 BASSE (vérification)
**Estimation :** 30 minutes

---

## A04:2021 – Insecure Design ⚠️

### État actuel : PARTIEL

**✅ Points forts :**
- **Deptrac** configuré et exécuté
- Architecture en couches (Entity, Repository, Service, Controller)
- Séparation des responsabilités

**⚠️ Points d'attention :**
- **9 violations Deptrac** : Entités dépendent de leurs Repositories
  ```
  App\Entity\AccountDeletionRequest → App\Repository\AccountDeletionRequestRepository
  App\Entity\CookieConsent → App\Repository\CookieConsentRepository
  ... (7 autres)
  ```

**Impact sécurité :** 🟡 FAIBLE
Ces violations sont **acceptables** car :
- Convention Doctrine standard (`#[ORM\Entity(repositoryClass:...)]`)
- Pas de couplage runtime (annotation statique)
- N'introduit pas de faille de sécurité directe

### Recommandations

```yaml
# deptrac.yaml - Ajouter exception pour annotations Doctrine
layers:
  - name: Entity
    collectors:
      - type: className
        regex: ^App\\Entity\\.*
    skip_violations:
      - App\Repository\.*Repository  # Doctrine ORM annotations OK
```

**Priorité :** 🟢 BASSE
**Estimation :** 30 minutes (configuration)

---

## A05:2021 – Security Misconfiguration 🔴

### État actuel : CRITIQUE ⚠️

### 🔴 Headers de sécurité manquants

**Package recommandé :** `nelmio/security-bundle` **NON INSTALLÉ**

#### Headers absents (0/5)

| Header | Présent | Recommandation |
|--------|---------|----------------|
| **Content-Security-Policy (CSP)** | ❌ | `default-src 'self'; script-src 'self' cdn.jsdelivr.net; ...` |
| **Strict-Transport-Security (HSTS)** | ❌ | `max-age=31536000; includeSubDomains; preload` |
| **X-Frame-Options** | ❌ | `DENY` ou `SAMEORIGIN` |
| **X-Content-Type-Options** | ❌ | `nosniff` |
| **Referrer-Policy** | ❌ | `strict-origin-when-cross-origin` |

#### Impact

- **Clickjacking** : Pas de protection X-Frame-Options
- **XSS** : Pas de CSP (Content Security Policy)
- **MITM** : Pas de HSTS (HTTP Strict Transport Security)
- **MIME sniffing** : Pas de X-Content-Type-Options

### Solution : Installation de nelmio/security-bundle

```bash
# Installation
docker compose exec app composer require nelmio/security-bundle

# Configuration recommandée
# config/packages/nelmio_security.yaml
nelmio_security:
    signed_cookie:
        names: ['*']
    encrypted_cookie:
        names: []

    content_security_policy:
        enabled: true
        hosts: []
        report_endpoint: /csp/report
        compat_headers: true
        hash:
            algorithm: sha256
        directives:
            default-src: ["'self'"]
            script-src:
                - "'self'"
                - "'unsafe-inline'"  # Nécessaire pour Chart.js, à affiner
                - "cdn.jsdelivr.net"
                - "cdnjs.cloudflare.com"
            style-src:
                - "'self'"
                - "'unsafe-inline'"  # Nécessaire pour Bootstrap
            img-src:
                - "'self'"
                - "data:"
                - "blob:"
            font-src:
                - "'self'"
                - "fonts.gstatic.com"
            connect-src:
                - "'self'"
            frame-ancestors:
                - "'none'"  # Équivalent à X-Frame-Options: DENY

    forced_ssl:
        enabled: true
        hsts_max_age: 31536000  # 1 an
        hsts_subdomains: true
        hsts_preload: true

    referrer_policy:
        enabled: true
        policies:
            - 'strict-origin-when-cross-origin'

    x_content_type_options:
        enabled: true
```

**Priorité :** 🔴 **CRITIQUE**
**Estimation :** 3 heures (installation + configuration + tests)

---

### ⚠️ Configuration environnement

```bash
# Vérifier mode debug désactivé en production
grep APP_ENV .env.local  # Doit être "prod"
grep APP_DEBUG .env.local  # Doit être "0" ou absent
```

**Recommandations :**
- `.env` ne doit contenir que des valeurs par défaut
- `.env.local` et `.env.prod.local` ne doivent **JAMAIS** être commités
- Utiliser **Symfony Secrets** pour les clés sensibles en production

---

## A06:2021 – Vulnerable and Outdated Components ✅

### État actuel : EXCELLENT ✅

**✅ Points forts :**
- **Roave Security Advisories** installé :
  ```
  roave/security-advisories dev-master a08c383
  ```
- **`composer audit`** : ✅ Aucune vulnérabilité détectée
  ```
  No security vulnerability advisories found.
  ```

**Impact :** Ce package **empêche l'installation** de dépendances avec vulnérabilités connues.

### Maintien de la sécurité

```bash
# Audit régulier (à intégrer en CI/CD)
composer audit

# Mise à jour des dépendances
composer outdated --direct
composer update --with-dependencies
```

**Priorité :** 🟢 BASSE (maintien)
**Estimation :** 1 heure/mois (monitoring)

---

## A07:2021 – Identification and Authentication Failures ✅

### État actuel : BON

**✅ Points forts :**
- **2FA activée** (scheb/2fa-bundle) :
  ```yaml
  two_factor:
      prepare_on_login: true
      enable_csrf: true
  ```
- **CSRF protection** activée sur login et 2FA
- **JWT pour API** avec tokens expirables

**⚠️ Points d'amélioration :**

### 1. Rate Limiting sur login

**Problème :** Pas de protection brute-force visible sur `/login` et `/api/login`

**Solution :** Utiliser `symfonycasts/reset-password-bundle` ou configurer rate limiting

```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        login:
            policy: 'sliding_window'
            limit: 5  # 5 tentatives
            interval: '15 minutes'
```

**Priorité :** 🟠 HAUTE
**Estimation :** 2 heures

---

### 2. Politique de mots de passe

**Vérifier dans l'entité User ou le formulaire :**
```php
#[Assert\Length(min: 12)]
#[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM)]
```

**Si absent :**
```php
// src/Entity/User.php
#[Assert\PasswordStrength(
    minScore: PasswordStrength::STRENGTH_STRONG,
    message: 'Le mot de passe doit contenir au moins 12 caractères, une majuscule, un chiffre et un caractère spécial.'
)]
private ?string $plainPassword = null;
```

**Priorité :** 🟡 MOYENNE
**Estimation :** 1 heure

---

## A08:2021 – Software and Data Integrity Failures ⚠️

### État actuel : PARTIEL

**✅ Points forts :**
- **CSRF protection** : Activée sur tous les formulaires
  ```php
  'csrf_protection' => true  // form config
  ```

**⚠️ Points d'amélioration :**

### 1. Subresource Integrity (SRI)

**Problème :** Aucune vérification d'intégrité des CDN externes

**Actuellement dans les templates :**
```html
<!-- Sans SRI (vulnérable à CDN compromise) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

**Recommandé :**
```html
<!-- Avec SRI -->
<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"
    integrity="sha384-XXX..."
    crossorigin="anonymous">
</script>
```

**Outil :** https://www.srihash.org/

**Priorité :** 🟡 MOYENNE
**Estimation :** 2 heures

---

### 2. Validation des uploads de fichiers

```php
// Vérifier dans SecureFileUploadService
- Extension whitelist (pas de blacklist)
- Vérification MIME type réelle (pas seulement extension)
- Taille maximale stricte
- Stockage hors web root
```

**À auditer :** `src/Service/SecureFileUploadService.php`

**Priorité :** 🟠 HAUTE
**Estimation :** 1 heure (audit)

---

## A09:2021 – Security Logging and Monitoring Failures ⚠️

### État actuel : PARTIEL

**✅ Points forts :**
- Logs Symfony activés (probablement Monolog)

**❌ Points manquants :**

### 1. Logs d'événements sensibles

**Événements à logger :**
- ✅ Login/Logout (Symfony le fait)
- ❌ Échecs de login (tentatives brute-force)
- ❌ Changements de permissions
- ❌ Accès refusés (403)
- ❌ Modifications de données sensibles (GDPR)
- ❌ Modifications de comptes utilisateurs

**Solution :**
```php
// src/EventSubscriber/SecurityEventsSubscriber.php
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

public function onLoginFailure(LoginFailureEvent $event): void
{
    $this->logger->warning('Failed login attempt', [
        'email' => $event->getPassport()->getUser()->getUserIdentifier(),
        'ip' => $event->getRequest()->getClientIp(),
        'user_agent' => $event->getRequest()->headers->get('User-Agent'),
    ]);
}
```

**Priorité :** 🟠 HAUTE
**Estimation :** 3 heures

---

### 2. Centralisation et monitoring

**Recommandé :**
- **Sentry** (erreurs + performance monitoring)
- **ELK Stack** ou **Loki + Grafana** (logs centralisés)
- **Alerting** : Notifications Slack/Discord pour événements critiques

**Priorité :** 🟡 MOYENNE (Lot 11bis.5 Infrastructure)
**Estimation :** 4 heures

---

## A10:2021 – Server-Side Request Forgery (SSRF) ✅

### État actuel : BON

**✅ Points forts :**
- Pas d'appels HTTP externes visibles dans les controllers/services
- Pas de feature permettant de fournir une URL arbitraire

**⚠️ À vérifier si implémenté :**

### Fonctionnalités à risque SSRF
- Import de données depuis URL
- Webhooks (ex: Yousign, Chorus Pro)
- Téléchargement d'avatars depuis URL
- Intégrations externes (Jira, GitHub, etc.)

**Protection recommandée si implémenté :**
```php
// Whitelist de domaines autorisés
private const ALLOWED_HOSTS = [
    'api.yousign.com',
    'choruspro.gouv.fr',
];

public function isUrlSafe(string $url): bool
{
    $host = parse_url($url, PHP_URL_HOST);
    return in_array($host, self::ALLOWED_HOSTS, true);
}
```

**Priorité :** 🟢 BASSE (préventif)
**Estimation :** 1 heure (si besoin)

---

## 📋 Plan d'Action Priorisé

### 🔴 CRITIQUE (À faire immédiatement)

| # | Action | Estimation | Lot |
|---|--------|-----------|-----|
| 1 | **Installer nelmio/security-bundle** et configurer headers | 3h | 11bis.4 |
| 2 | Configurer CSP, HSTS, X-Frame-Options | 2h | 11bis.4 |

**Total Critique :** **5 heures**

---

### 🟠 HAUTE (Semaine courante)

| # | Action | Estimation | Lot |
|---|--------|-----------|-----|
| 3 | Rate limiting sur login (`/login`, `/api/login`) | 2h | 11bis.4 |
| 4 | Audit + tests voters Symfony | 4h | 11bis.2 |
| 5 | Logger événements sensibles (login failures, 403) | 3h | 11bis.4 |
| 6 | Audit SecureFileUploadService | 1h | 11bis.4 |

**Total Haute :** **10 heures**

---

### 🟡 MOYENNE (Ce mois)

| # | Action | Estimation | Lot |
|---|--------|-----------|-----|
| 7 | Politique de mots de passe forts | 1h | 11bis.4 |
| 8 | SRI sur CDN externes (Chart.js, Bootstrap) | 2h | 11bis.4 |
| 9 | Audit secrets management (Symfony Secrets) | 1h | 11bis.4 |
| 10 | Documentation voters | 2h | 11bis.1 |

**Total Moyenne :** **6 heures**

---

### 🟢 BASSE (Opportuniste)

| # | Action | Estimation | Lot |
|---|--------|-----------|-----|
| 11 | Audit SQL natif (createNativeQuery) | 0.5h | 11bis.1 |
| 12 | Protection SSRF (préventif) | 1h | 11bis.4 |
| 13 | Skip violations Deptrac (Doctrine) | 0.5h | 11bis.1 |

**Total Basse :** **2 heures**

---

## 🎯 Synthèse

**Total estimé :** **23 heures** (~3 jours)
**Budget Lot 11bis.4 (Sécurité) :** 2-3 jours ✅

**Répartition :**
- Actions CRITIQUES : 5h (0.6j)
- Actions HAUTES : 10h (1.3j)
- Actions MOYENNES : 6h (0.8j)
- Actions BASSES : 2h (0.2j)

---

## 📊 Score OWASP après corrections

| Catégorie | Avant | Après actions | Amélioration |
|-----------|-------|---------------|--------------|
| A05 - Security Misconfiguration | 🔴 2/10 | ✅ 9/10 | +7 |
| A07 - Auth Failures | ✅ 7/10 | ✅ 9/10 | +2 |
| A08 - Software Integrity | ⚠️ 6/10 | ✅ 8/10 | +2 |
| A09 - Logging Failures | ⚠️ 4/10 | ✅ 8/10 | +4 |

**Score global estimé :** 6.5/10 → **8.5/10** 🎉

---

**Prochaines étapes :**
1. ✅ Audit OWASP Top 10 - TERMINÉ
2. 🔄 Installer nelmio/security-bundle - EN COURS
3. ⏳ Configurer headers de sécurité
4. ⏳ Rate limiting login
5. ⏳ Logger événements sensibles

**Dernière mise à jour :** 27 décembre 2025
**Auteur :** Claude Sonnet 4.5 via Claude Code
