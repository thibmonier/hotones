# 📝 Bonnes pratiques implémentées

## Architecture et Code
- Pattern Repository : Logique métier séparée des contrôleurs
- Injection de dépendances : Symfony DI
- Entités Doctrine : Relations bien définies
- Sécurité : Contrôle d'accès par rôles (`ROLE_MANAGER`)
- Validation : Token CSRF sur suppressions et formulaires

## Interface utilisateur
- Feedback utilisateur (messages flash)
- Navigation intuitive (breadcrumbs, liens cohérents)
- Filtrage (par contributeur)
- Responsivité (Bootstrap 5, thème Skote)
- Accessibilité : Statuts visuels avec couleurs et icônes

## Gestion des données
- Validation métier : chevauchements de périodes
- Flexibilité : temps partiel, profils multiples
- Traçabilité : historique complet des périodes d'emploi
- Calculs automatiques : coûts et durées

---

## ⚡ Performance (rappels)
- Activer HTTP caching (ETag/Last-Modified), reverse proxy si possible; mettre en cache les réponses idempotentes.
- Configurer OPcache (prod) + preloading; APCu/Redis pour `cache.app` et `cache.system`.
- Doctrine: éviter le N+1 (joins, fetch joins mesurés), pagination, projections/DTOs, index pertinents, requêtes ciblées; désactiver `logging` en prod.
- Front: minifier/split JS/CSS, HTTP/2 push/preload, images WebP/AVIF, lazy-load, assets versionnés (cache-busting).
- Données volumineuses: pagination/scroll infini, exports streamés, tâches asynchrones (Messenger) pour traitements lourds.
- Logs: niveau `warning` en prod; limiter les handlers synchrones.

## ✅ Bonnes pratiques Symfony 7.x
- Contrôleurs fins, logique métier dans Services/Repositories; injection de dépendances (autowire/autoconfigure).
- Routing par attributs, types stricts, `readonly` quand possible; utiliser `#[AsCommand]` pour CLI.
- Sécurité: nouveau système Security (authenticator-based), Password Hasher, voters pour l’ACL.
- Validation avec contraintes et groupes; formulaires découplés des entités pour cas complexes (DTO/FormModel).
- Doctrine: `ServiceEntityRepository`, migrations versionnées, indexes/composites, `ENUM` via types Doctrine.
- Cache: utiliser le composant Cache (APCu/Redis) et invalider finement.
- HTTP Client pour intégrations externes; RateLimiter pour quotas; Lock pour sections critiques.
- Messenger pour asynchrone / file d’attente; transports dédiés (ex: Redis, RabbitMQ) et retry/backoff.
- Templates Twig: `strict_variables` en dev, `auto_reload: false` en prod; macros et composants; éviter la logique lourde en templates.
- Config par environnements (`config/packages/*.yaml`), variables via secrets vault; pas de secrets en clair.
- Observabilité: Monolog configuré par env; profiler seulement en dev; prévoir Blackfire ou équivalent.
