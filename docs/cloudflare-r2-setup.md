# Migration vers Cloudflare R2 pour le stockage des fichiers

## Contexte

Sur Render (et la plupart des plateformes PaaS), le système de fichiers est **éphémère** : tout ce qui est stocké localement est effacé à chaque déploiement. Pour conserver les fichiers uploadés (avatars, reçus de dépenses, etc.), nous devons utiliser un stockage externe.

## Solution : Cloudflare R2

Cloudflare R2 est un service de stockage objet compatible S3 avec les avantages suivants :
- **Gratuit jusqu'à 10GB** de stockage
- **Pas de frais de sortie** (egress) - contrairement à AWS S3
- Compatible avec l'API S3 (facile à utiliser avec Symfony Flysystem)
- Performant et distribué mondialement via le réseau Cloudflare

## Étape 1 : Créer un bucket Cloudflare R2

1. Connectez-vous à votre compte Cloudflare : https://dash.cloudflare.com/
2. Allez dans **R2** dans le menu de gauche
3. Cliquez sur **Create bucket**
4. Nommez votre bucket (ex: `hotones-uploads`)
5. Choisissez une région (ou laissez "Automatic")
6. Créez le bucket

## Étape 2 : Créer des credentials API

1. Dans la section R2, allez dans **Manage R2 API Tokens**
2. Cliquez sur **Create API token**
3. Configurez le token :
   - **Token name** : `hotones-production`
   - **Permissions** : Object Read & Write
   - **TTL** : Laissez vide (illimité) ou définissez une expiration
   - **Bucket** : Sélectionnez votre bucket créé à l'étape 1
4. Cliquez sur **Create API Token**
5. **IMPORTANT** : Copiez et sauvegardez :
   - Access Key ID
   - Secret Access Key
   - Endpoint URL (format: `https://xxx.r2.cloudflarestorage.com`)

## Étape 3 : Configurer l'URL publique (optionnel mais recommandé)

Pour servir les fichiers directement depuis R2 sans passer par votre application :

### Option A : Domaine personnalisé (recommandé)

1. Dans votre bucket R2, allez dans **Settings**
2. Section **Public access**, cliquez sur **Connect domain**
3. Entrez un sous-domaine de votre site (ex: `files.hotones.com`)
4. Suivez les instructions pour configurer le DNS
5. Activez **Allow Public Access**

→ Votre URL publique sera : `https://files.hotones.com`

### Option B : Domaine R2.dev (simple mais limité)

1. Dans votre bucket R2, allez dans **Settings**
2. Section **R2.dev subdomain**, cliquez sur **Allow Access**
3. Vous obtiendrez une URL du type : `https://pub-xxx.r2.dev`

→ Votre URL publique sera : `https://pub-xxx.r2.dev`

## Étape 4 : Configurer les variables d'environnement sur Render

1. Allez sur https://dashboard.render.com/
2. Sélectionnez votre service web
3. Allez dans **Environment**
4. Ajoutez les variables suivantes :

```bash
# Utiliser l'adaptateur S3 en production
FILESYSTEM_ADAPTER=s3_adapter

# Credentials Cloudflare R2
S3_ACCESS_KEY=<votre_access_key_id>
S3_SECRET_KEY=<votre_secret_access_key>
S3_BUCKET=hotones-uploads
S3_REGION=auto
S3_ENDPOINT=https://<your-account-id>.r2.cloudflarestorage.com

# URL publique (une des options de l'étape 3)
S3_PUBLIC_URL=https://files.hotones.com
# OU
S3_PUBLIC_URL=https://pub-xxx.r2.dev
```

5. Cliquez sur **Save Changes**
6. Render redémarrera automatiquement votre application

## Étape 5 : Migrer les fichiers existants (si nécessaire)

Si vous avez déjà des fichiers uploadés en local que vous voulez migrer vers R2 :

### Option A : Via l'interface Web R2

1. Téléchargez vos fichiers depuis Render (si possible)
2. Uploadez-les manuellement dans votre bucket R2 via l'interface Cloudflare
3. Respectez la structure de dossiers :
   - `avatars/` pour les avatars
   - `expenses/` pour les reçus de dépenses

### Option B : Via AWS CLI (recommandé pour beaucoup de fichiers)

```bash
# Installer AWS CLI
brew install awscli  # macOS
# ou apt-get install awscli  # Linux

# Configurer le profil R2
aws configure --profile r2
# Access Key ID: <votre_access_key_id>
# Secret Access Key: <votre_secret_access_key>
# Region: auto
# Output format: json

# Synchroniser les fichiers locaux vers R2
aws s3 sync public/uploads/ s3://hotones-uploads/ \
  --endpoint-url https://<your-account-id>.r2.cloudflarestorage.com \
  --profile r2
```

## Étape 6 : Tester

1. Déployez votre application sur Render
2. Uploadez un nouvel avatar via `/me/edit`
3. Vérifiez que l'avatar s'affiche correctement
4. Vérifiez dans votre bucket R2 que le fichier est bien présent dans `avatars/`
5. Redéployez l'application → l'avatar doit toujours être visible

## Architecture technique

### En développement (local)

- `FILESYSTEM_ADAPTER=local_adapter`
- Les fichiers sont stockés dans `public/uploads/`
- Servis directement via `/uploads/avatars/{filename}`

### En production (Render)

- `FILESYSTEM_ADAPTER=s3_adapter`
- Les fichiers sont stockés sur Cloudflare R2
- Servis via l'URL publique configurée (ex: `https://files.hotones.com/avatars/{filename}`)
- Le contrôleur `AvatarController` redirige vers l'URL publique R2 (redirection 301)

### Code modifié

Les fichiers suivants ont été adaptés pour utiliser Flysystem :

- **Configuration** :
  - `config/packages/oneup_flysystem.yaml` - Configuration Flysystem
  - `config/packages/prod/oneup_flysystem.yaml` - Override pour production

- **Services** :
  - `src/Service/SecureFileUploadService.php` - Utilise Flysystem pour upload/delete

- **Contrôleurs** :
  - `src/Controller/AvatarController.php` - Redirige vers R2 en prod
  - `src/Controller/ProfileController.php` - Upload d'avatar via service
  - `src/Controller/AdminUserController.php` - Upload d'avatar via service
  - `src/Controller/ExpenseReportController.php` - Upload de reçus via service

### Structure de stockage

```
hotones-uploads/  (bucket R2)
├── avatars/
│   ├── u1-abc123.webp
│   ├── u2-def456.png
│   └── ...
└── expenses/
    ├── facture-xyz789.pdf
    ├── recu-abc123.jpg
    └── ...
```

## Dépannage

### Les fichiers ne s'affichent pas après déploiement

1. Vérifiez les variables d'environnement sur Render
2. Vérifiez les logs : `docker compose logs -f app` (local) ou logs Render
3. Testez l'accès à R2 :
   ```bash
   curl https://<your-account-id>.r2.cloudflarestorage.com/<bucket>/avatars/<filename>
   ```

### Erreur "Access Denied" lors de l'upload

- Vérifiez que les credentials (ACCESS_KEY/SECRET_KEY) sont corrects
- Vérifiez que le token API a les permissions "Object Read & Write"
- Vérifiez que le bucket est bien configuré

### Les URLs ne fonctionnent pas

- Si vous utilisez un domaine personnalisé, vérifiez la configuration DNS
- Vérifiez que `S3_PUBLIC_URL` est correctement configuré
- Vérifiez que "Public Access" est activé sur le bucket

## Coûts

Cloudflare R2 offre un tier gratuit généreux :
- **Stockage** : 10 GB/mois gratuit, puis $0.015/GB/mois
- **Opérations Class A** (write) : 1 million/mois gratuit, puis $4.50/million
- **Opérations Class B** (read) : 10 millions/mois gratuit, puis $0.36/million
- **Egress** : Gratuit (pas de frais de sortie)

Pour une application typique avec quelques centaines d'utilisateurs, vous resterez probablement dans le tier gratuit.

## Ressources

- [Documentation Cloudflare R2](https://developers.cloudflare.com/r2/)
- [Documentation Symfony Flysystem](https://github.com/thephpleague/flysystem)
- [OneupFlysystemBundle](https://github.com/1up-lab/OneupFlysystemBundle)
