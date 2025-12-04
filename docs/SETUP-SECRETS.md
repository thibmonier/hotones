# Guide de Configuration des Secrets GitHub

Ce guide vous accompagne pas à pas pour configurer SonarCloud et Snyk.

## 📝 Checklist

- [ ] Créer un compte SonarCloud
- [ ] Obtenir le token SonarCloud
- [ ] Configurer les secrets SonarCloud dans GitHub
- [ ] Créer un compte Snyk
- [ ] Obtenir le token Snyk
- [ ] Configurer le secret Snyk dans GitHub
- [ ] Vérifier que les workflows fonctionnent

---

## 🔷 PARTIE 1 : Configuration SonarCloud

### Étape 1.1 : Créer un compte SonarCloud

1. Allez sur **https://sonarcloud.io**
2. Cliquez sur **"Log in"** en haut à droite
3. Choisissez **"Log in with GitHub"**
4. Autorisez SonarCloud à accéder à votre compte GitHub
5. Vous êtes maintenant connecté à SonarCloud

### Étape 1.2 : Importer votre repository

1. Une fois connecté, cliquez sur le **"+"** en haut à droite
2. Sélectionnez **"Analyze new project"**
3. Choisissez votre organisation GitHub : **thibmonier**
4. Cochez le repository **hotones**
5. Cliquez sur **"Set Up"**

### Étape 1.3 : Configuration du projet

1. Choisissez **"With GitHub Actions"** comme méthode d'analyse
2. SonarCloud va vous montrer les informations suivantes (notez-les) :

```
SONAR_TOKEN: (token généré automatiquement - copiez-le!)
Project Key: thibmonier_hotones
Organization: thibmonier
```

3. **IMPORTANT** : Copiez le `SONAR_TOKEN` maintenant (vous ne pourrez plus le voir après)

### Étape 1.4 : Obtenir/Regénérer le token si besoin

Si vous n'avez pas copié le token ou si vous devez le régénérer :

1. Cliquez sur votre avatar en haut à droite
2. Allez dans **"My Account"**
3. Cliquez sur l'onglet **"Security"**
4. Dans la section **"Generate Tokens"** :
   - Name: `GitHub Actions - hotones`
   - Type: `Project Analysis Token`
   - Expiration: `No expiration` (ou 90 jours)
5. Cliquez sur **"Generate"**
6. **Copiez le token immédiatement** (il ne sera plus visible après)

### Étape 1.5 : Ajouter les secrets dans GitHub

1. Allez sur votre repository GitHub : **https://github.com/thibmonier/hotones**
2. Cliquez sur **"Settings"** (en haut)
3. Dans le menu de gauche, cliquez sur **"Secrets and variables"** → **"Actions"**
4. Cliquez sur **"New repository secret"**

Ajoutez les 4 secrets suivants (un par un) :

#### Secret 1 : SONAR_TOKEN
- Name: `SONAR_TOKEN`
- Secret: `[collez le token copié depuis SonarCloud]`
- Cliquez sur **"Add secret"**

#### Secret 2 : SONAR_HOST_URL
- Name: `SONAR_HOST_URL`
- Secret: `https://sonarcloud.io`
- Cliquez sur **"Add secret"**

#### Secret 3 : SONAR_PROJECT_KEY
- Name: `SONAR_PROJECT_KEY`
- Secret: `thibmonier_hotones`
- Cliquez sur **"Add secret"**

#### Secret 4 : SONAR_ORGANIZATION
- Name: `SONAR_ORGANIZATION`
- Secret: `thibmonier`
- Cliquez sur **"Add secret"**

✅ **SonarCloud est maintenant configuré !**

---

## 🛡️ PARTIE 2 : Configuration Snyk

### Étape 2.1 : Créer un compte Snyk

1. Allez sur **https://app.snyk.io/signup**
2. Choisissez **"Sign up with GitHub"**
3. Autorisez Snyk à accéder à votre compte GitHub
4. Suivez le processus d'inscription (gratuit)

### Étape 2.2 : Obtenir le token API

1. Une fois connecté, cliquez sur votre avatar/nom en haut à droite
2. Cliquez sur **"Account settings"**
3. Dans le menu de gauche, cliquez sur **"General"**
4. Descendez jusqu'à la section **"Auth Token"** ou **"API Token"**
5. Cliquez sur **"click to show"** pour révéler le token
6. **Copiez le token**

### Étape 2.3 : Importer le repository (optionnel mais recommandé)

1. Cliquez sur **"Add project"** (ou le "+" en haut)
2. Choisissez **"GitHub"**
3. Cherchez et sélectionnez le repository **hotones**
4. Cliquez sur **"Add selected repositories"**
5. Snyk va scanner automatiquement votre projet

### Étape 2.4 : Ajouter le secret dans GitHub

1. Retournez sur votre repository GitHub : **https://github.com/thibmonier/hotones**
2. Allez dans **"Settings"** → **"Secrets and variables"** → **"Actions"**
3. Cliquez sur **"New repository secret"**

#### Secret : SNYK_TOKEN
- Name: `SNYK_TOKEN`
- Secret: `[collez le token copié depuis Snyk]`
- Cliquez sur **"Add secret"**

✅ **Snyk est maintenant configuré !**

---

## ✅ PARTIE 3 : Vérification

### Étape 3.1 : Vérifier les secrets GitHub

1. Dans **Settings** → **"Secrets and variables"** → **"Actions"**
2. Vous devriez voir 5 secrets :
   - ✅ `SNYK_TOKEN`
   - ✅ `SONAR_HOST_URL`
   - ✅ `SONAR_ORGANIZATION`
   - ✅ `SONAR_PROJECT_KEY`
   - ✅ `SONAR_TOKEN`

### Étape 3.2 : Déclencher les workflows

Les workflows se déclencheront automatiquement au prochain push, mais vous pouvez les lancer manuellement :

1. Allez dans l'onglet **"Actions"** de votre repository
2. Dans le menu de gauche, sélectionnez **"SonarQube Analysis"**
3. Cliquez sur **"Run workflow"** → **"Run workflow"**
4. Faites de même pour **"Snyk Security"**

### Étape 3.3 : Vérifier que tout fonctionne

**SonarQube :**
1. Attendez que le workflow se termine (environ 2-3 minutes)
2. Allez sur **https://sonarcloud.io/project/overview?id=thibmonier_hotones**
3. Vous devriez voir les résultats de l'analyse

**Snyk :**
1. Attendez que le workflow se termine
2. Allez sur **https://app.snyk.io**
3. Cliquez sur votre projet **hotones**
4. Vous devriez voir les vulnérabilités détectées

---

## 🎉 Configuration Terminée !

Vous avez maintenant :
- ✅ SonarCloud actif (analyse à chaque push)
- ✅ Snyk actif (scan quotidien + à chaque push)
- ✅ Dependabot actif (déjà configuré)

### 🔍 Où voir les résultats ?

**GitHub :**
- Security tab → Dependabot alerts
- Actions tab → Voir les exécutions des workflows

**SonarCloud :**
- https://sonarcloud.io/project/overview?id=thibmonier_hotones

**Snyk :**
- https://app.snyk.io

---

## 🆘 Problèmes Courants

### SonarQube : "Invalid token"
→ Régénérez le token dans SonarCloud (My Account → Security) et remettez-le dans GitHub Secrets

### Snyk : "Authentication failed"
→ Vérifiez que vous avez copié le token complet (sans espaces) depuis Snyk Account Settings

### Workflow échoue avec "Secret not found"
→ Vérifiez que le nom du secret est exactement correct (sensible à la casse)

### SonarQube : "Quality Gate failed"
→ C'est normal au début. Consultez les problèmes détectés et corrigez-les progressivement

---

## 📞 Besoin d'Aide ?

Si vous rencontrez un problème :
1. Vérifiez les logs des workflows dans GitHub Actions
2. Consultez `docs/security-and-quality-setup.md` pour plus de détails
3. Vérifiez que tous les secrets sont bien configurés
