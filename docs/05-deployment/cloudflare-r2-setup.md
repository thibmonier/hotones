# Migration vers Cloudflare R2 pour le stockage des fichiers

## Contexte

Sur Render (et la plupart des plateformes PaaS), le système de fichiers est **éphémère** : tout ce qui est stocké localement est effacé à chaque déploiement. Pour conserver les fichiers uploadés (avatars, reçus de dépenses, etc.) et les images traitées (cache LiipImagine), nous devons utiliser un stockage externe.

## Solution : Cloudflare R2

Cloudflare R2 est un service de stockage objet compatible S3 avec les avantages suivants :
- **Gratuit jusqu'à 10GB** de stockage
- **Pas de frais de sortie** (egress) - contrairement à AWS S3
- Compatible avec l'API S3 (facile à utiliser avec Symfony Flysystem)
- Performant et distribué mondialement via le réseau Cloudflare
- **CDN intégré** pour servir les fichiers rapidement partout dans le monde

## Architecture complète

Notre solution stocke **deux types de fichiers** sur R2 :

### 1. Fichiers uploadés (via SecureFileUploadService)
- **Avatars** utilisateurs et contributeurs
- **Reçus de dépenses** (PDF, images)
- Autres documents uploadés

### 2. Images traitées (via LiipImagineBundle)
- **Cache d'images redimensionnées** (thumbnails, etc.)
- Différentes tailles : small (60x60), medium (80x80), large (250x250), etc.
- Générées à la demande et mises en cache

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
S3_BUCKET=hotones
S3_REGION=auto
S3_ENDPOINT=https://<your-account-id>.r2.cloudflarestorage.com

# URL publique (une des options de l'étape 3)
# IMPORTANT : Utilisez la même URL pour S3_PUBLIC_URL et IMAGINE_CACHE_URL
S3_PUBLIC_URL=https://pub-xxx.r2.dev
# OU pour un domaine personnalisé :
S3_PUBLIC_URL=https://files.hotones.com

# Cache LiipImagine (images redimensionnées)
# En production, utilise automatiquement S3_PUBLIC_URL
IMAGINE_CACHE_URL=https://pub-xxx.r2.dev
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

### Test 1 : Upload de fichiers

1. Déployez votre application sur Render
2. **Uploadez un avatar** via `/me/edit`
3. Vérifiez que l'avatar s'affiche correctement
4. Dans votre bucket R2, vérifiez la présence du fichier :
   ```
   avatars/
     └── filename-xxxxx.jpg
   ```
5. **Redéployez** l'application → l'avatar doit toujours être visible ✅

### Test 2 : Cache d'images LiipImagine

1. Accédez à une page utilisant LiipImagine (ex: chatbot avec avatar Unit404)
2. Les images seront traitées et mises en cache automatiquement
3. Dans votre bucket R2, vérifiez la structure :
   ```
   media/cache/
     ├── unit404_avatar_small/
     │   └── unit404.png
     ├── unit404_avatar_medium/
     │   └── unit404.png
     └── unit404_avatar_large/
         └── unit404.png
   ```
4. **Redéployez** → les images en cache persistent ✅
5. Les images sont servies directement depuis R2/CDN (pas de regeneration)

### Test 3 : URLs publiques

1. Inspectez une image dans le navigateur
2. L'URL doit pointer vers R2 :
   - Avatars : `https://pub-xxx.r2.dev/avatars/filename.jpg`
   - Cache : `https://pub-xxx.r2.dev/media/cache/filter/image.png`
3. Ouvrez l'URL directement → l'image doit s'afficher ✅

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
  - `config/packages/oneup_flysystem.yaml` - Configuration Flysystem (adapters S3 + local)
  - `config/packages/prod/oneup_flysystem.yaml` - Override production (utilise S3)
  - `config/packages/liip_imagine.yaml` - Configuration LiipImagine avec Flysystem
  - `config/packages/prod/liip_imagine.yaml` - Override production (URL publique R2)

- **Services** :
  - `src/Service/SecureFileUploadService.php` - Upload/delete via Flysystem
  - `src/Twig/FileStorageExtension.php` - Extension Twig pour URLs (avatar_url, file_url)

- **Contrôleurs** :
  - `src/Controller/AvatarController.php` - Redirige vers R2 en prod
  - `src/Controller/ProfileController.php` - Upload d'avatar via service
  - `src/Controller/AdminUserController.php` - Upload d'avatar via service
  - `src/Controller/ContributorController.php` - Upload d'avatar via service
  - `src/Controller/ExpenseReportController.php` - Upload de reçus via service

- **Templates** :
  - `templates/layouts/_topbar.html.twig` - Utilise avatar_url()
  - `templates/home/index.html.twig` - Utilise avatar_url()
  - `templates/profile/*.html.twig` - Utilise avatar_url()
  - `templates/admin/user/edit.html.twig` - Utilise avatar_url()

### Structure de stockage

```
hotones/  (bucket R2)
├── avatars/                    # Avatars utilisateurs/contributeurs
│   ├── filename-abc123.webp
│   ├── filename-def456.png
│   └── ...
├── expenses/                   # Reçus de dépenses
│   ├── facture-xyz789.pdf
│   ├── recu-abc123.jpg
│   └── ...
└── media/cache/               # Cache LiipImagine
    ├── unit404_avatar_small/
    │   └── unit404.png
    ├── unit404_avatar_medium/
    │   └── unit404.png
    ├── unit404_avatar_large/
    │   └── unit404.png
    ├── unit404_avatar_tiny/
    │   └── unit404.png
    └── unit404_avatar_widget/
        └── unit404.png
```

## Dépannage

### Les fichiers ne s'affichent pas après déploiement

1. **Vérifiez les variables d'environnement** sur Render
   - `FILESYSTEM_ADAPTER=s3_adapter` ✓
   - `S3_PUBLIC_URL` est défini ✓
   - `IMAGINE_CACHE_URL` est défini ✓

2. **Vérifiez les logs** Render pour les erreurs
   - Erreur de connexion S3 → credentials incorrects
   - Erreur 404 → bucket ou endpoint incorrect
   - Erreur 403 → permissions manquantes

3. **Testez l'accès direct** à R2 :
   ```bash
   # Ne fonctionne PAS (endpoint API, pas public)
   curl https://<account-id>.r2.cloudflarestorage.com/hotones/avatars/file.jpg

   # Fonctionne (URL publique)
   curl https://pub-xxx.r2.dev/avatars/file.jpg
   ```

### Erreur "Access Denied" lors de l'upload

- **Vérifiez les credentials** : `S3_ACCESS_KEY` et `S3_SECRET_KEY`
- **Vérifiez les permissions** du token API : "Object Read & Write"
- **Vérifiez le bucket name** : doit correspondre à `S3_BUCKET`

### Les URLs ne fonctionnent pas

**Symptôme** : Images uploadées mais erreur 404

1. **Vérifiez l'accès public R2** :
   - Dashboard R2 → Settings → R2.dev subdomain
   - Doit être "Allowed" (pas "Disabled")

2. **Vérifiez S3_PUBLIC_URL** :
   ```bash
   # ❌ INCORRECT (endpoint API)
   S3_PUBLIC_URL=https://abc.r2.cloudflarestorage.com/hotones

   # ✅ CORRECT (domaine public R2.dev)
   S3_PUBLIC_URL=https://pub-xxx.r2.dev

   # ✅ CORRECT (domaine personnalisé)
   S3_PUBLIC_URL=https://files.hotones.com
   ```

3. **Vérifiez la visibilité** des fichiers :
   - Les fichiers doivent être uploadés avec `visibility: public`
   - Configuration automatique dans `oneup_flysystem.yaml`

### Les images LiipImagine ne se génèrent pas

1. **Vérifiez IMAGINE_CACHE_URL** :
   ```bash
   # En production
   IMAGINE_CACHE_URL=https://pub-xxx.r2.dev
   ```

2. **Testez manuellement** la génération :
   ```bash
   php bin/console liip:imagine:cache:resolve unit404.png unit404_avatar_small
   ```

3. **Vérifiez les logs** pour erreurs Flysystem/S3

4. **Videz le cache** si nécessaire :
   ```bash
   php bin/console liip:imagine:cache:remove
   ```

### Performance : Images lentes à charger

**Solution** : Activer le CDN Cloudflare

1. Dashboard R2 → votre bucket → Settings
2. **Custom Domains** → connectez votre domaine
3. Le domaine bénéficiera automatiquement du CDN Cloudflare
4. Les images seront mises en cache aux edge locations

**Astuce** : Utilisez un domaine personnalisé plutôt que R2.dev pour de meilleures performances CDN.

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
