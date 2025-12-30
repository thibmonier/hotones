# Déploiement rapide avec Railway MySQL

Guide express pour déployer HotOnes sur Render avec une base de données MySQL sur Railway.

⏱️ **Temps estimé** : 10-15 minutes

## Pourquoi Railway ?

- ✅ **$5 gratuit/mois** (suffisant pour une petite DB)
- ✅ **MySQL natif** (pas de migration de code nécessaire)
- ✅ **Setup rapide** (2-3 minutes)
- ✅ **Pay-as-you-go** après le crédit gratuit (~$5-10/mois)

## Étape 1 : Créer la base MySQL sur Railway (3 min)

### 1.1 Créer un compte

1. Allez sur https://railway.app
2. Cliquez "Start a New Project"
3. Connectez-vous avec GitHub (recommandé) ou email

### 1.2 Créer le service MySQL

1. Dans le nouveau projet, cliquez "+ New"
2. Sélectionnez "Database" → "Add MySQL"
3. Railway va provisionner automatiquement une instance MySQL 8

### 1.3 Récupérer la connection string

1. Cliquez sur le service MySQL créé
2. Allez dans l'onglet "Connect"
3. Copiez la **"MySQL Connection URL"** qui ressemble à :
   ```
   mysql://root:PASSWORD@containers-us-west-123.railway.app:6543/railway
   ```

**Important** : Notez cette URL, vous en aurez besoin pour Render.

### 1.4 (Optionnel) Renommer la base

Par défaut, la base s'appelle "railway". Pour la renommer :

1. Dans l'onglet "Variables", changez `MYSQL_DATABASE` en `hotones`
2. Mettez à jour l'URL copiée : remplacez `/railway` par `/hotones`

## Étape 2 : Pousser le code sur GitHub (2 min)

Si ce n'est pas déjà fait :

```bash
cd /Users/tmonier/Projects/hotones

# Ajouter les fichiers de déploiement
git add .

# Commit
git commit -m "feat: add Render deployment configuration with Railway MySQL support"

# Push
git push origin main
```

## Étape 3 : Déployer sur Render (5 min)

### 3.1 Créer le Blueprint

1. Connectez-vous sur https://dashboard.render.com
2. Cliquez "New +" → "Blueprint"
3. Connectez votre compte GitHub
4. Sélectionnez le repository `hotones`
5. Branche : `main`
6. Render détectera automatiquement `render.yaml`

### 3.2 Configurer les variables d'environnement

**Avant de cliquer "Apply"**, vous devez configurer ces variables manuelles :

#### Variables obligatoires

Cliquez sur le service **hotones-app** dans le blueprint, puis "Environment" :

| Variable | Valeur | Où la trouver |
|----------|--------|---------------|
| `DATABASE_URL` | `mysql://root:PASSWORD@...` | URL copiée depuis Railway (Étape 1.3) |
| `JWT_PASSPHRASE` | Votre passphrase JWT | Exécutez `./scripts/prepare-render.sh` |
| `MAILER_DSN` | `smtp://user:pass@smtp.example.com:587` | Configuration de votre provider email |

**APP_SECRET** sera généré automatiquement par Render (laissez `generateValue: true`).

#### Variables optionnelles

| Variable | Valeur | Usage |
|----------|--------|-------|
| `OPENAI_API_KEY` | Clé API OpenAI | Si fonctionnalités IA activées |
| `ANTHROPIC_API_KEY` | Clé API Anthropic | Si fonctionnalités IA activées |

### 3.3 Lancer le déploiement

1. Cliquez "Apply" en bas de page
2. Render va créer :
   - ✅ Service web `hotones-app`
   - ✅ Service Redis `hotones-redis`
3. Le build va démarrer (5-10 minutes au premier déploiement)

### 3.4 Suivre le déploiement

1. Dans le dashboard, cliquez sur le service `hotones-app`
2. Allez dans l'onglet "Logs"
3. Attendez de voir :
   ```
   Application ready!
   Starting services...
   ```

## Étape 4 : Configuration post-déploiement (3 min)

### 4.1 Créer le premier utilisateur admin

1. Dans le service `hotones-app`, cliquez "Shell" (en haut à droite)
2. Exécutez :
   ```bash
   php bin/console app:user:create admin@votredomaine.fr "MotDePasseSecure" "Admin" "System"
   ```

### 4.2 Tester l'application

1. Récupérez l'URL de votre app : `https://hotones-app.onrender.com`
2. Ouvrez-la dans votre navigateur
3. Testez le login avec le compte créé

### 4.3 (Optionnel) Configurer un domaine personnalisé

1. Dans le service `hotones-app` → "Settings" → "Custom Domain"
2. Ajoutez votre domaine (ex: `hotones.votredomaine.fr`)
3. Configurez le CNAME chez votre registrar :
   ```
   CNAME  hotones  →  hotones-app.onrender.com
   ```

## Coûts estimés

### Plan gratuit initial

- **Railway** : $5 de crédit gratuit/mois
- **Render Web Service** : Dort après 15min d'inactivité (réveil lent)
- **Render Redis** : 25MB max, expire après 90 jours

**💡 Tant que votre DB Railway consomme moins de $5/mois, c'est gratuit !**

### Pour une vraie production

| Service | Plan | Prix/mois |
|---------|------|-----------|
| Render Web Service | Starter | $7 |
| Render Redis | Starter | $10 |
| Railway MySQL | Usage-based | $5-15 |
| **TOTAL** | | **$22-32** |

## Monitoring et maintenance

### Consulter les logs

**Render (application) :**
- Dashboard → `hotones-app` → "Logs"

**Railway (base de données) :**
- Dashboard Railway → MySQL service → "Metrics"

### Exécuter des migrations

Si vous ajoutez de nouvelles migrations :

```bash
# Les migrations s'exécutent automatiquement au démarrage
# Mais vous pouvez les lancer manuellement via Shell :
php bin/console doctrine:migrations:migrate
```

### Accéder à la base de données

**Via Railway CLI :**
```bash
# Installer Railway CLI
npm i -g @railway/cli

# Login
railway login

# Connecter au projet
railway link

# Shell MySQL
railway connect mysql
```

**Via client MySQL local :**
```bash
mysql -h containers-us-west-123.railway.app -P 6543 -u root -p
```

### Workers Messenger

2 workers sont configurés automatiquement via Supervisor. Pour vérifier :

```bash
# Via Shell Render
supervisorctl status
```

## Scaling et optimisation

### Augmenter les ressources web

Dans Render → `hotones-app` → "Settings" → "Instance Type" :
- Starter : 512MB RAM (~$7/mois)
- Standard : 2GB RAM (~$25/mois)

### Augmenter la base de données

Railway scale automatiquement en fonction de l'usage (pay-as-you-go).

Pour voir votre consommation :
- Railway Dashboard → MySQL service → "Metrics" → "Usage"

### Backups

**Railway** : Pas de backups automatiques sur le plan gratuit.

Options :
1. **Backup manuel régulier** :
   ```bash
   # Via Railway CLI
   railway run mysqldump -u root -p railway > backup.sql
   ```

2. **Script automatisé** : Créez un cron job externe qui backup via Railway CLI

3. **Upgrade vers Railway Pro** ($20/mois) : Backups automatiques inclus

## Dépannage

### La DB Railway n'est pas accessible depuis Render

1. Vérifiez que l'URL est correcte dans `DATABASE_URL`
2. Railway MySQL est accessible publiquement par défaut
3. Vérifiez les logs Render pour voir l'erreur exacte

### "Database connection timeout"

- Railway peut prendre 30-60s pour démarrer à froid (plan gratuit)
- Le script `start-render.sh` attend automatiquement (30 tentatives)
- Si ça persiste, vérifiez l'état du service MySQL sur Railway

### "Too many connections"

Railway limite les connexions :
- Plan gratuit : 20 connexions max
- Vérifiez `doctrine.yaml` : `max_connections` ne doit pas dépasser 10

### L'app Render dort après 15 min

C'est normal avec le plan gratuit. Options :
1. Accepter le délai de réveil (~30s)
2. Upgrader vers Starter ($7/mois) : pas de sleep
3. Utiliser un service de ping (ex: UptimeRobot) pour garder l'app active

## Migration future vers PostgreSQL

Si vous voulez migrer vers PostgreSQL plus tard :

1. Le code est déjà compatible (corrections appliquées)
2. Suivez le guide : [docs/deployment-render-postgres.md](./deployment-render-postgres.md)
3. Migrez les données avec `pg_dump` / `mysql2pgsql`

## Support

- **Railway** : https://railway.app/help
- **Render** : https://docs.render.com
- **HotOnes** : Consultez [DEPLOYMENT.md](../DEPLOYMENT.md)

---

🎉 **Félicitations !** Votre application est déployée avec Railway MySQL.

**Prochaines étapes :**
- Configurez un domaine personnalisé
- Ajoutez du monitoring (Sentry)
- Configurez les backups automatiques
- Testez les fonctionnalités critiques

---

**Dernière mise à jour** : 2025-01-20
