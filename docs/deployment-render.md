# Guide de déploiement sur Render

Ce guide vous accompagne dans le déploiement de HotOnes sur [Render.com](https://render.com).

## Prérequis

- Compte Render (gratuit ou payant)
- Code source sur GitHub/GitLab
- Base de données MySQL/MariaDB (voir options ci-dessous)

## Architecture sur Render

```
┌─────────────────────────────────────────┐
│  Web Service (Docker)                   │
│  - Nginx (port 8080)                    │
│  - PHP-FPM 8.4                          │
│  - Messenger Workers (2x)               │
│  - Persistent Disk (1GB pour /var)      │
└─────────────────────────────────────────┘
           ↓                    ↓
┌──────────────────┐  ┌──────────────────┐
│  Redis Service   │  │  MySQL Database  │
│  (Cache + Queue) │  │  (Externe)       │
└──────────────────┘  └──────────────────┘
```

## Étape 1 : Configurer la base de données MySQL

Render propose nativement PostgreSQL mais pas MySQL/MariaDB. Voici vos options :

### Tableau comparatif

| Solution | Type | Gratuit | Payant | Recommandation |
|----------|------|---------|--------|----------------|
| **PostgreSQL Render** | PostgreSQL | 90 jours | $7/mois | ⭐⭐⭐ Meilleur rapport qualité/prix |
| **Supabase** | PostgreSQL | ✅ 500MB permanent | $25/mois | ⭐⭐⭐ Excellent gratuit |
| **Neon** | PostgreSQL | ✅ 512MB permanent | $19/mois | ⭐⭐ Serverless innovant |
| **Railway** | MySQL | $5 crédit/mois | ~$10/mois | ⭐⭐ Si vous tenez à MySQL |
| **DigitalOcean** | MySQL/PostgreSQL | ❌ | $15/mois | ⭐ Production sérieuse |

### Option A : PostgreSQL Render (Recommandé) ⭐

**Avantages :**
- Intégré à votre infrastructure Render
- Configuration automatique via `render.yaml`
- 90 jours gratuits puis $7/mois
- Backups automatiques

**Inconvénient :** Nécessite migration MySQL → PostgreSQL

👉 **[Guide de migration vers PostgreSQL](./deployment-render-postgres.md)**

### Option B : Supabase PostgreSQL (Gratuit permanent)

[Supabase](https://supabase.com) offre PostgreSQL gratuit permanent (500MB, 2 CPU).

1. Créez un compte sur https://supabase.com
2. Créez un nouveau projet "hotones"
3. Dans "Settings" → "Database", récupérez la connection string
4. Ajoutez-la dans Render : `DATABASE_URL=postgresql://...`

**Avantages :**
- ✅ Gratuit permanent (pas de limite de temps)
- ✅ Dashboard puissant
- ✅ Backups automatiques
- ✅ 500MB suffisant pour démarrer

### Option C : Railway MySQL (Si vous gardez MySQL)

[Railway](https://railway.app) propose MySQL avec $5 de crédit gratuit/mois.

1. Créez un compte sur https://railway.app
2. Nouveau projet → Ajoutez "MySQL"
3. Récupérez la DATABASE_URL dans les variables
4. Ajoutez-la dans Render

**Coût :** Gratuit tant que vous restez sous $5/mois de consommation

### Option D : DigitalOcean Managed Database

Pour une production sérieuse avec MySQL :
- Plan MySQL/MariaDB à partir de $15/mois
- Très fiable et performant
- Configuration via https://cloud.digitalocean.com

### 💡 Notre recommandation

**Pour démarrer :** Supabase PostgreSQL (gratuit permanent)
**Pour production :** Render PostgreSQL Starter ($7/mois)

La migration MySQL → PostgreSQL est simple avec Symfony/Doctrine. Voir [le guide détaillé](./deployment-render-postgres.md).

## Étape 2 : Préparer le déploiement

### 2.1 Générer les clés JWT localement

Les clés JWT ne doivent pas être dans le repository. Générez-les localement :

```bash
# Générer les clés
php bin/console lexik:jwt:generate-keypair

# Récupérer le passphrase
cat config/jwt/private.pem | head -2
```

Notez la passphrase, vous en aurez besoin pour les variables d'environnement.

### 2.2 Pousser le code sur GitHub/GitLab

```bash
git add .
git commit -m "feat: add Render deployment configuration"
git push origin main
```

## Étape 3 : Créer les services sur Render

### 3.1 Méthode automatique (Blueprint)

1. Connectez-vous sur https://dashboard.render.com
2. Cliquez "New" → "Blueprint"
3. Connectez votre repository GitHub/GitLab
4. Sélectionnez la branche `main`
5. Render détectera automatiquement `render.yaml`

**IMPORTANT** : Avant de valider, configurez ces variables manuelles :

### 3.2 Variables d'environnement requises

Dans le dashboard Render, définissez ces variables pour le service web :

| Variable | Valeur | Description |
|----------|--------|-------------|
| `DATABASE_URL` | `mysql://...` | URL de connexion MySQL (PlanetScale/Railway/etc) |
| `JWT_PASSPHRASE` | `votre-passphrase` | Passphrase des clés JWT générées |
| `APP_SECRET` | `généré-auto` | Secret Symfony (auto-généré par Render) |
| `MAILER_DSN` | `smtp://...` | Configuration email (ex: Mailgun, SendGrid) |
| `DEFAULT_URI` | `https://votreapp.onrender.com` | URL de votre app |

**Variables optionnelles :**

| Variable | Valeur |
|----------|--------|
| `OPENAI_API_KEY` | Clé API OpenAI (si fonctionnalités IA activées) |
| `ANTHROPIC_API_KEY` | Clé API Anthropic/Claude |

### 3.3 Services créés automatiquement

Le Blueprint créera :

1. **hotones-app** (Web Service)
   - Type: Docker
   - Port: 8080
   - Disk: 1GB persistant sur `/var/www/html/var`
   - Health check: `/health`

2. **hotones-redis** (Redis Service)
   - Plan: Starter (ou Free)
   - Utilisé pour cache + message queue

3. **Base de données** : Externe (voir Étape 1)

## Étape 4 : Configuration post-déploiement

### 4.1 Vérifier le déploiement

1. Attendez la fin du build (5-10 minutes au premier déploiement)
2. Vérifiez les logs dans "Logs" du service web
3. Testez l'endpoint health : `https://votreapp.onrender.com/health`

### 4.2 Créer le premier utilisateur admin

Connectez-vous au shell du service web :

```bash
# Dans le dashboard Render, ouvrez "Shell" du service web
php bin/console app:user:create admin@votreentreprise.fr "MotDePasseSecurise" "Admin" "System"
```

### 4.3 Configurer le domaine personnalisé (optionnel)

1. Dans le service web, allez dans "Settings" → "Custom Domain"
2. Ajoutez votre domaine (ex: `hotones.votreentreprise.fr`)
3. Configurez le CNAME chez votre registrar :
   ```
   CNAME  hotones  →  votreapp.onrender.com
   ```

## Étape 5 : Optimisations et monitoring

### 5.1 Activer Auto-Deploy

Dans "Settings" du service web :
- Activez "Auto-Deploy" pour déployer automatiquement à chaque push sur `main`

### 5.2 Configurer les notifications

Dans "Settings" → "Notifications" :
- Slack/Discord pour les échecs de déploiement
- Email pour les alertes

### 5.3 Monitoring

Render fournit :
- CPU/RAM usage dans "Metrics"
- Logs en temps réel
- Health check automatique

Pour un monitoring avancé, intégrez :
- Sentry pour les erreurs : https://sentry.io
- New Relic pour les performances

## Gestion des migrations

Les migrations s'exécutent automatiquement au démarrage via `start-render.sh`.

Pour exécuter manuellement :

```bash
# Dans le Shell Render
php bin/console doctrine:migrations:migrate
```

## Gestion des workers Messenger

2 workers sont configurés dans Supervisor pour traiter les messages async.

Pour vérifier leur statut :

```bash
supervisorctl status
```

## Scaling

### Augmenter les ressources

Dans "Settings" → "Instance Type" :
- **Starter** : 512MB RAM, 0.5 CPU (~$7/mois)
- **Standard** : 2GB RAM, 1 CPU (~$25/mois)
- **Pro** : 4GB RAM, 2 CPU (~$85/mois)

### Scaling horizontal (plusieurs instances)

1. "Settings" → "Scaling" → Augmentez le nombre d'instances
2. **ATTENTION** : Nécessite un Redis externe et gestion des sessions

## Sauvegardes

### Base de données

- **PlanetScale** : Snapshots automatiques quotidiennes
- **Railway** : Backups manuels via dashboard
- **DigitalOcean** : Backups automatiques quotidiennes

### Persistent Disk (/var)

Render ne sauvegarde pas automatiquement les disks. Options :

1. **Backup manuel** : Téléchargez `/var` via Shell
2. **S3 Sync** : Script cron pour synchroniser vers AWS S3

## Déploiement manuel (sans Blueprint)

Si vous préférez créer les services manuellement :

### Service Web

```yaml
Type: Web Service
Environment: Docker
Dockerfile: Dockerfile.render
Region: Frankfurt
Plan: Starter
Health Check: /health
Port: 8080 (détecté automatiquement)

Disk:
  Name: hotones-storage
  Mount: /var/www/html/var
  Size: 1GB
```

### Redis

```yaml
Type: Redis
Plan: Starter
Region: Frankfurt (même région que web)
```

## Dépannage

### Le build échoue

- Vérifiez que `Dockerfile.render` est bien à la racine
- Consultez les logs de build

### L'app ne démarre pas

- Vérifiez les logs du service
- Testez la connexion à la base : regardez les logs de `start-render.sh`

### Erreurs 500

- Ouvrez le Shell et consultez : `tail -f var/log/prod.log`
- Vérifiez que toutes les variables d'environnement sont définies

### Workers Messenger ne fonctionnent pas

```bash
# Dans le Shell
supervisorctl restart messenger-worker:*
supervisorctl tail -f messenger-worker:00
```

### Base de données lente

- Vérifiez les index sur les tables fréquemment requêtées
- Envisagez un plan supérieur chez votre provider
- Activez le cache Doctrine (APCu configuré)

## Limitations du plan gratuit

- **Web Service** : Dort après 15min d'inactivité (premier démarrage lent)
- **Redis Free** : 25MB max, expire après 90 jours
- **Bandwidth** : 100GB/mois

Pour une production sérieuse, prévoyez :
- Web Service Starter : $7/mois
- Redis Starter : $10/mois
- Database (PlanetScale/Railway) : $0-15/mois

**Total estimé** : ~$17-30/mois pour un usage production léger

## Support

- Documentation Render : https://docs.render.com
- Community Forum : https://community.render.com
- Support HotOnes : Consultez le README.md

---

**Dernière mise à jour** : 2025-01-20
