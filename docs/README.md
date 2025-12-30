# Documentation HotOnes

Documentation technique et fonctionnelle du projet HotOnes - Système de gestion de projets et suivi de rentabilité pour agences web.

## 📚 Structure de la documentation

### [01-getting-started](./01-getting-started/) - Démarrage
Installation, configuration initiale, variables d'environnement, dépannage.

**Fichiers clés :**
- `installation.md` - Guide d'installation
- `SETUP-SECRETS.md` - Configuration des secrets
- `environment-variables.md` - Variables d'environnement
- `overview.md` - Vue d'ensemble du projet

### [02-architecture](./02-architecture/) - Architecture technique
Architecture système, diagrammes, modèle de données, technologies.

**Fichiers clés :**
- `architecture.md` - Architecture globale
- `architecture-diagrams.md` - Diagrammes détaillés
- `entities.md` - Modèle de données (entités Doctrine)
- `design-system.md` - Design system et composants

### [03-features](./03-features/) - Fonctionnalités
Spécifications fonctionnelles détaillées de toutes les features du système.

**Fichiers clés :**
- `features.md` - Liste des fonctionnalités
- `profitability.md` - Calculs de rentabilité
- `analytics.md` - Tableaux de bord analytiques
- `time-planning.md` - Gestion du temps et planning

### [04-development](./04-development/) - Développement
Bonnes pratiques, tests, API, commandes Symfony, développement quotidien.

**Fichiers clés :**
- `good-practices.md` - Best practices de développement
- `tests.md` - Stratégie et guide de tests
- `commands.md` - Commandes Symfony disponibles
- `api.md` - Documentation API

### [05-deployment](./05-deployment/) - Déploiement
Infrastructure, Docker, hébergement, CI/CD, déploiement en production.

**Fichiers clés :**
- `deployment.md` - Guide de déploiement général
- `docker.md` - Configuration Docker
- `cloudflare-r2-setup.md` - Stockage fichiers (Cloudflare R2)
- `health-checks.md` - Monitoring et santé du système

### [06-security](./06-security/) - Sécurité & Conformité
Sécurité applicative, audits OWASP, RGPD, CSP, rate limiting.

**Fichiers clés :**
- `security.md` - Guide de sécurité
- `security-audit-owasp-2025-12-27.md` - Dernier audit OWASP
- `gdpr-technical.md` - Conformité RGPD (technique)
- `csp-configuration-2025-12-28.md` - Configuration Content Security Policy

### [07-performance](./07-performance/) - Performance
Optimisation, profiling, monitoring, logs, métriques.

**Fichiers clés :**
- `performance.md` - Guide d'optimisation
- `blackfire-profiling.md` - Profiling avec Blackfire
- `logging-guide.md` - Gestion des logs
- `performance-optimization-report.md` - Rapport d'optimisation

### [08-ui-ux](./08-ui-ux/) - Interface & Expérience utilisateur
Frontend, formulaires, validation, design, ergonomie.

**Fichiers clés :**
- `ui.md` - Guide UI/UX
- `form-wizard.md` - Formulaires multi-étapes
- `ux-ui-audit-2025.md` - Audit UX/UI
- `error-pages.md` - Pages d'erreur personnalisées

### [09-migration](./09-migration/) - Migrations
Migrations Symfony 8, PHP 8.5, mises à jour majeures, PHPStan.

**Fichiers clés :**
- `MIGRATION_SYMFONY8.md` - Migration Symfony 8
- `migration-php85-symfony8.md` - Migration PHP 8.5
- `symfony-ai-bundle-analysis.md` - Analyse Symfony AI Bundle
- `phpstan-level-upgrade-analysis.md` - Montée de niveau PHPStan

### [10-planning](./10-planning/) - Planning & Roadmap
Roadmaps, planification, idées, stratégie produit, évolutions futures.

**Fichiers clés :**
- `roadmap-2025.md` - Roadmap 2025
- `roadmap-lots.md` - Planning par lots
- `plan-execution-saas-2025.md` - Plan SaaS multi-tenant
- `ideas.md` - Idées d'améliorations

### [11-reports](./11-reports/) - Rapports & Sprints
Rapports de sprints, audits techniques, bilans, progression des lots.

**Fichiers clés :**
- `lot11bis-progress-2025-12-28.md` - Dernier rapport de lot
- `technical-audit-report-2025-12-27.md` - Audit technique
- `technical-debt-hotspots-2025-12-28.md` - Dette technique
- `sprint-comparison-report.md` - Comparaison des sprints

### [archive](./archive/) - Archive
Anciens documents, fichiers obsolètes, versions dépassées.

---

## 🚀 Démarrage rapide

1. **Installation :** Voir [01-getting-started/installation.md](./01-getting-started/installation.md)
2. **Architecture :** Voir [02-architecture/architecture.md](./02-architecture/architecture.md)
3. **Développement :** Voir [04-development/good-practices.md](./04-development/good-practices.md)

## 📝 Contribution

Pour ajouter de la documentation :
1. Placer le fichier dans la catégorie appropriée
2. Mettre à jour l'index de la catégorie si nécessaire
3. Utiliser le format Markdown avec des titres clairs

## 🔗 Liens utiles

- **WARP.md** (racine du projet) - Index de documentation principal
- **CLAUDE.md** (racine du projet) - Instructions pour Claude Code
- **README.md** (racine du projet) - Vue d'ensemble du projet

---

**Dernière mise à jour :** 2025-12-31
**Organisation :** Documentation réorganisée en 11 catégories + archive
