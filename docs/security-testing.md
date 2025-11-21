# Tests de sécurité

Ce document décrit la stratégie et les outils de tests de sécurité mis en place pour l'application HotOnes.

## Vue d'ensemble

La sécurité de l'application est testée à plusieurs niveaux :
1. **Audit des dépendances** : Vérification des vulnérabilités connues
2. **Tests fonctionnels** : Protection CSRF, authentification, autorisation
3. **Tests d'injection** : SQL injection, XSS
4. **Headers de sécurité** : Vérification des headers HTTP

## Commandes disponibles

### Audit de sécurité complet
```bash
# Exécuter tous les tests de sécurité
docker compose exec app composer security-test

# Ou via make
composer security-test
```

### Audit des dépendances uniquement
```bash
# Vérifier les vulnérabilités dans les dépendances Composer
docker compose exec app composer security-check

# Ou directement
docker compose exec app composer audit
```

### Tests de sécurité PHPUnit uniquement
```bash
# Exécuter uniquement la suite de tests de sécurité
docker compose exec app composer test-security

# Ou directement avec PHPUnit
docker compose exec app ./vendor/bin/phpunit --testsuite security
```

## Suites de tests

### 1. SecurityHeadersTest
Vérifie que les headers de sécurité HTTP sont correctement configurés :
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY/SAMEORIGIN`
- `Referrer-Policy`

Teste également :
- L'accessibilité des routes publiques
- La protection des routes privées (redirection vers login)

### 2. CsrfProtectionTest
Vérifie la protection CSRF :
- Rejet des soumissions de formulaires sans token CSRF
- Rejet des actions de suppression sans token valide
- Présence du token CSRF dans tous les formulaires

### 3. AuthenticationTest
Tests d'authentification et d'autorisation :
- Accessibilité de la page de login
- Rejet des identifiants invalides
- Protection des zones admin par rôle
- Fonctionnement de la 2FA (si activée)
- Invalidation de session lors de la déconnexion

### 4. SqlInjectionTest
Protection contre les injections SQL et XSS :
- Test de payloads d'injection SQL courants
- Vérification de l'utilisation de requêtes préparées
- Échappement correct des entrées utilisateur (XSS)
- Pas d'exposition d'erreurs SQL

## Payloads testés

### SQL Injection
- Simple quote: `' OR '1'='1`
- Union select: `' UNION SELECT NULL--`
- Commentaires: `admin'--`
- Stacked queries: `'; DROP TABLE users--`
- Boolean based: `1' AND '1'='1`
- Time based: `' OR SLEEP(5)--`

### XSS (Cross-Site Scripting)
- Basic script: `<script>alert(1)</script>`
- Image onerror: `<img src=x onerror=alert(1)>`
- Event handler: `<div onload=alert(1)>`

## Bonnes pratiques

### Avant chaque commit
```bash
# Vérifier la qualité du code ET la sécurité
docker compose exec app composer check-code
docker compose exec app composer security-check
```

### Avant chaque release
```bash
# Test complet incluant sécurité
docker compose exec app composer test
docker compose exec app composer security-test
```

### Dans le CI/CD
Ajouter ces vérifications dans votre pipeline :
```yaml
- name: Security audit
  run: docker compose exec app composer audit

- name: Security tests
  run: docker compose exec app composer test-security
```

## Configuration requise

### Headers de sécurité (config/packages/framework.yaml)
```yaml
framework:
    # ...
    http_method_override: false # Désactiver pour éviter parameter pollution

# Dans config/packages/security.yaml
security:
    # Protection CSRF activée par défaut sur les formulaires
    csrf:
        enabled: true
```

### Headers personnalisés (Nginx/Apache ou Symfony)
Ajouter dans `.htaccess` ou configuration Nginx :
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'
```

## Checklist de sécurité

- [ ] Audit des dépendances sans vulnérabilités
- [ ] Tous les tests de sécurité passent
- [ ] Protection CSRF active sur tous les formulaires
- [ ] Routes protégées par authentification
- [ ] Rôles et permissions configurés correctement
- [ ] 2FA disponible pour les comptes sensibles
- [ ] Headers de sécurité configurés
- [ ] Logs des tentatives d'authentification échouées
- [ ] Rate limiting sur les endpoints sensibles
- [ ] Validation et échappement des entrées utilisateur
- [ ] Requêtes SQL utilisent des prepared statements
- [ ] Pas d'exposition d'informations sensibles dans les erreurs

## Outils complémentaires

### OWASP Dependency-Check (optionnel)
Pour une analyse plus approfondie des dépendances :
```bash
# Installation
docker run --rm -v $(pwd):/src owasp/dependency-check:latest \
  --scan /src/composer.lock \
  --format HTML \
  --out /src/var/security-report

# Voir le rapport
open var/security-report/dependency-check-report.html
```

### Symfony Security Checker CLI (optionnel)
```bash
# Installation globale
symfony check:security

# Ou via Docker
docker run --rm -v $(pwd):/app composer audit
```

## Ressources

- [Symfony Security Best Practices](https://symfony.com/doc/current/security.html)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Symfony Security Advisories](https://github.com/FriendsOfPHP/security-advisories)
- [PHPStan Security Extensions](https://github.com/phpstan/phpstan-symfony)

## Support

Pour toute question ou amélioration des tests de sécurité, consulter :
- La documentation Symfony : https://symfony.com/doc/current/security.html
- Les tests existants dans `tests/Security/`
- Le fichier `WARP.md` pour la documentation générale du projet
