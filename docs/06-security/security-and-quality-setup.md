# Configuration des Outils de Sécurité et Qualité

Ce guide explique comment configurer et utiliser les outils de sécurité et qualité du projet HotOnes.

## 🛠️ Outils Configurés

| Outil | Version | Objectif | Fréquence |
|-------|---------|----------|-----------|
| **Dependabot** | Gratuit | Mises à jour automatiques des dépendances | Hebdomadaire |
| **Snyk** | Gratuit | Détection de vulnérabilités de sécurité | Quotidien |
| **SonarQube** | Gratuit (50k lignes) | Analyse de qualité du code | À chaque push |
| **PHPStan** | Local | Analyse statique PHP | CI/CD |
| **PHP-CS-Fixer** | Local | Style de code PHP | CI/CD |

## 📊 Statistiques du Projet

- **PHP dans src/** : 37,439 lignes (sous la limite SonarQube de 50,000 ✅)
- **Templates** : 30,352 lignes (exclus de SonarQube)
- **Total** : 67,791 lignes

## 🔧 Configuration Initiale

### 1. GitHub Secrets à Configurer

Allez dans `Settings > Secrets and variables > Actions` de votre repository et ajoutez :

#### Pour SonarQube/SonarCloud
```
SONAR_TOKEN=votre_token_sonarcloud
SONAR_HOST_URL=https://sonarcloud.io
SONAR_PROJECT_KEY=thibmonier_hotones
SONAR_ORGANIZATION=thibmonier
```

**Comment obtenir SONAR_TOKEN:**
1. Allez sur https://sonarcloud.io
2. Connectez-vous avec votre compte GitHub
3. My Account > Security > Generate Token
4. Copiez le token et ajoutez-le aux secrets GitHub

#### Pour Snyk
```
SNYK_TOKEN=votre_token_snyk
```

**Comment obtenir SNYK_TOKEN:**
1. Allez sur https://snyk.io
2. Créez un compte gratuit
3. Account Settings > API Token
4. Copiez le token et ajoutez-le aux secrets GitHub

### 2. Activer Dependabot

Dependabot est automatiquement activé grâce au fichier `.github/dependabot.yml`.

**Vérification:**
1. Allez dans `Settings > Code security and analysis`
2. Vérifiez que "Dependabot alerts" est activé
3. Vérifiez que "Dependabot security updates" est activé
4. Vérifiez que "Dependabot version updates" est activé

### 3. Intégration SonarCloud

1. Allez sur https://sonarcloud.io
2. Cliquez sur "+" > "Analyze new project"
3. Sélectionnez votre repository GitHub
4. Suivez les instructions pour l'intégration
5. Le workflow GitHub Actions fera le reste automatiquement

### 4. Intégration Snyk

1. Allez sur https://app.snyk.io
2. Connectez votre compte GitHub
3. Importez le repository `hotones`
4. Activez le monitoring automatique

## 📋 Utilisation Quotidienne

### Dependabot

**Automatique** : Dependabot crée des Pull Requests chaque lundi matin à 6h.

**Actions manuelles:**
```bash
# Voir les dépendances obsolètes
composer outdated
npm outdated

# Mettre à jour une dépendance spécifique
composer update vendor/package
npm update package-name
```

### Snyk

**Automatique** : Scan quotidien à 6h via GitHub Actions.

**Commandes locales:**
```bash
# Installer Snyk CLI (première fois)
npm install -g snyk
snyk auth

# Scanner les dépendances PHP
snyk test --file=composer.lock

# Scanner les dépendances npm
snyk test --file=package.json

# Voir les vulnérabilités avec détails
snyk test --json

# Monitorer le projet
snyk monitor
```

### SonarQube

**Automatique** : Analyse à chaque push sur main/master et PR.

**Accéder aux résultats:**
1. Allez sur https://sonarcloud.io/project/overview?id=thibmonier_hotones
2. Consultez les métriques :
   - Bugs
   - Vulnérabilités
   - Code Smells
   - Couverture
   - Duplication

**Commande locale (optionnel):**
```bash
# Installer SonarScanner
npm install -g sonarqube-scanner

# Analyser localement
sonar-scanner \
  -Dsonar.projectKey=thibmonier_hotones \
  -Dsonar.sources=src \
  -Dsonar.host.url=https://sonarcloud.io \
  -Dsonar.token=$SONAR_TOKEN
```

### PHPStan et PHP-CS-Fixer

**Commandes locales:**
```bash
# Vérifier la qualité du code
docker compose exec app composer check-code

# Analyse statique
docker compose exec app composer phpstan

# Vérifier le style (dry-run)
docker compose exec app composer phpcsfixer

# Corriger le style automatiquement
docker compose exec app composer phpcsfixer-fix
```

## 🎯 Workflow Recommandé

### Avant de committer
```bash
# 1. Vérifier le style et la qualité
docker compose exec app composer check-code

# 2. Lancer les tests
docker compose exec app composer test

# 3. Vérifier les vulnérabilités (si Snyk installé)
snyk test
```

### Lors d'une Pull Request

1. **Automatique** : Les workflows GitHub Actions s'exécutent
   - Tests PHPUnit
   - PHPStan
   - PHP-CS-Fixer
   - SonarQube
   - Snyk

2. **Vérifiez** :
   - ✅ Tous les checks passent
   - ✅ Pas de nouvelles vulnérabilités
   - ✅ Quality Gate SonarQube OK
   - ✅ Pas de régression de couverture

3. **Mergez** uniquement si tout est vert

### Gestion des Dependabot PRs

Dependabot crée des PRs groupées :
- **symfony/** : Toutes les mises à jour Symfony ensemble
- **doctrine/** : Toutes les mises à jour Doctrine ensemble
- **dev-dependencies** : Dépendances de dev mineures/patches

**Processus:**
1. Vérifiez les notes de version (changelog)
2. Vérifiez que les tests passent
3. Testez localement si mise à jour majeure
4. Mergez si tout est OK

## 📈 Métriques et Objectifs

### Objectifs de Qualité SonarQube

| Métrique | Objectif | Actuel |
|----------|----------|---------|
| Bugs | 0 | À vérifier |
| Vulnérabilités | 0 | À vérifier |
| Code Smells | < 100 | À vérifier |
| Couverture | > 70% | À implémenter |
| Duplication | < 3% | À vérifier |
| Maintenabilité | A | À vérifier |

### Objectifs de Sécurité

- ✅ Aucune vulnérabilité critique ou haute
- ✅ Toutes les dépendances à jour (< 6 mois)
- ✅ Scan quotidien Snyk
- ✅ Dependabot actif

## 🚨 Gestion des Alertes

### Vulnérabilité Critique/Haute (Snyk ou Dependabot)

1. **Immédiatement** : Évaluer l'impact
2. **Dans les 24h** : Appliquer le correctif
3. **Si pas de correctif** : Mettre en place un workaround
4. **Documenter** : Ajouter un commentaire dans `.snyk` si ignoré temporairement

### Quality Gate Failed (SonarQube)

1. Identifier les nouveaux problèmes
2. Corriger dans la même PR
3. Ne pas merger tant que Quality Gate n'est pas OK
4. Exception : discuter avec l'équipe si nécessaire

### Dépendance Obsolète (Dependabot)

**Mise à jour mineure/patch** : Merger rapidement après vérification des tests

**Mise à jour majeure** :
1. Lire les breaking changes
2. Tester localement
3. Adapter le code si nécessaire
4. Tester en staging
5. Merger

## 🔍 Dépannage

### SonarQube : "Line limit exceeded"

✅ **Déjà configuré** : Seul `src/` est analysé (37k lignes < 50k)

Si le problème persiste :
```properties
# Dans sonar-project.properties
sonar.sources=src
# Vérifier les exclusions
sonar.exclusions=**/vendor/**,**/var/**,**/tests/**
```

### Snyk : Trop de vulnérabilités

1. Filtrer par sévérité : `snyk test --severity-threshold=high`
2. Ignorer les dev dependencies : déjà configuré dans `.snyk`
3. Corriger les plus critiques en premier

### Dependabot : PRs trop nombreuses

Déjà configuré pour :
- Max 5 PRs Composer
- Max 5 PRs npm
- Groupement des mises à jour mineures

Si nécessaire, ajuster `open-pull-requests-limit` dans `.github/dependabot.yml`

### GitHub Actions : Échec des workflows

```bash
# Tester localement
act -j sonarqube  # Nécessite 'act' installé
act -j snyk-php

# Vérifier les secrets
gh secret list
```

## 📚 Ressources

- [Dependabot Documentation](https://docs.github.com/en/code-security/dependabot)
- [Snyk Documentation](https://docs.snyk.io/)
- [SonarQube Documentation](https://docs.sonarqube.org/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHP-CS-Fixer Documentation](https://cs.symfony.com/)

## 🎓 Bonnes Pratiques

1. **Ne jamais ignorer une vulnérabilité** sans comprendre le risque
2. **Tester localement** avant de merger les mises à jour majeures
3. **Maintenir la couverture de tests** > 70%
4. **Corriger les code smells** au fur et à mesure
5. **Reviewer les PRs Dependabot** même si automatiques
6. **Vérifier SonarQube** avant de merger
7. **Mettre à jour régulièrement** (ne pas accumuler)

## ✅ Checklist Post-Configuration

- [ ] Secrets GitHub configurés (SONAR_TOKEN, SNYK_TOKEN)
- [ ] SonarCloud project créé et lié
- [ ] Snyk project importé
- [ ] Dependabot activé dans GitHub Settings
- [ ] Premier scan SonarQube réussi
- [ ] Premier scan Snyk réussi
- [ ] Workflows GitHub Actions tous verts
- [ ] Badge SonarQube ajouté au README
- [ ] Badge Snyk ajouté au README
- [ ] Équipe formée sur les outils
