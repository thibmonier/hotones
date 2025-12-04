# Sécurité et Qualité - HotOnes

## 🎯 Vue d'ensemble

Ce projet utilise plusieurs outils gratuits pour garantir la sécurité et la qualité du code :

| Outil | Rôle | Automatisation |
|-------|------|----------------|
| 🤖 **Dependabot** | Mises à jour automatiques | ✅ Hebdomadaire |
| 🛡️ **Snyk** | Scan de vulnérabilités | ✅ Quotidien |
| 📊 **SonarQube** | Qualité du code | ✅ À chaque push |
| 🔍 **PHPStan** | Analyse statique | ✅ CI/CD |
| ✨ **PHP-CS-Fixer** | Style de code | ✅ CI/CD |

## 🚀 Quick Start

### Secrets GitHub requis

```bash
# SonarCloud
SONAR_TOKEN=xxx
SONAR_HOST_URL=https://sonarcloud.io
SONAR_PROJECT_KEY=thibmonier_hotones
SONAR_ORGANIZATION=thibmonier

# Snyk
SNYK_TOKEN=xxx
```

### Commandes Essentielles

```bash
# Vérifier tout avant commit
docker compose exec app composer check-code

# Tester
docker compose exec app composer test

# Analyser les vulnérabilités (si Snyk CLI installé)
snyk test --severity-threshold=high
```

## 📁 Fichiers de Configuration

- `.github/dependabot.yml` - Configuration Dependabot
- `.snyk` - Configuration Snyk
- `sonar-project.properties` - Configuration SonarQube
- `.github/workflows/sonarqube.yml` - Workflow SonarQube
- `.github/workflows/snyk.yml` - Workflow Snyk

## 📖 Documentation Complète

Voir [docs/security-and-quality-setup.md](docs/security-and-quality-setup.md) pour :
- Configuration détaillée
- Workflow recommandé
- Gestion des alertes
- Dépannage
- Bonnes pratiques

## 🔒 Signaler une Vulnérabilité

Si vous découvrez une vulnérabilité de sécurité, **ne créez pas d'issue publique**.

Contactez : thibault.monier@example.com (remplacer par votre email)

## 📊 Métriques Actuelles

- **PHP analysé** : 37,439 lignes (sous limite SonarQube ✅)
- **Tests** : 127 tests, 309 assertions
- **Couverture** : À implémenter

## 🎯 Objectifs Qualité

- ✅ 0 vulnérabilité critique/haute
- ✅ Toutes dépendances à jour
- 🎯 > 70% couverture de tests
- 🎯 Note A sur SonarQube
