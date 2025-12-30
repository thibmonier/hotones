# 🚨 Pages d'Erreur Personnalisées

## Vue d'ensemble

HotOnes utilise des pages d'erreur personnalisées mettant en scène **Unit 404**, un agent spécialisé avec un humour décalé, pour rendre l'expérience des erreurs HTTP plus agréable pour les utilisateurs.

## Pages d'erreur disponibles

### 404 - Page Non Trouvée
- **Template** : `templates/bundles/TwigBundle/Exception/error404.html.twig`
- **Icône** : Bouée qui tourne (`bx-buoy bx-spin`)
- **Couleur** : Bleu primaire
- **Citation** : *"404 Not Found. C'est ironique, n'est-ce pas ? C'est aussi le nom de ma motivation."*
- **Actions** : Retour au tableau de bord

### 403 - Accès Interdit
- **Template** : `templates/bundles/TwigBundle/Exception/error403.html.twig`
- **Icône** : Bouclier barré clignotant (`bx-shield-x bx-flashing`)
- **Couleur** : Rouge danger
- **Citation** : *"Zone classifiée. Vos accréditations sont insuffisantes. Ou inexistantes. Probablement les deux."*
- **Actions** : Retour au tableau de bord, Se connecter/déconnecter

### 500 - Erreur Serveur Interne
- **Template** : `templates/bundles/TwigBundle/Exception/error500.html.twig`
- **Icône** : Cercle d'erreur animé (`bx-error-circle bx-tada`)
- **Couleur** : Jaune warning
- **Citation** : *"Erreur 500. On dirait que le serveur a décidé de prendre des congés non planifiés."*
- **Actions** : Retour au tableau de bord, Réessayer
- **Support** : Card avec informations de contact

### Erreur Générique (Fallback)
- **Template** : `templates/bundles/TwigBundle/Exception/error.html.twig`
- **Utilisation** : Toutes les autres erreurs HTTP
- **Citations** : Adaptées selon le code d'erreur
- **Actions** : Retour au tableau de bord, Page précédente

## Architecture

### Structure des fichiers

```
templates/
└── bundles/
    └── TwigBundle/
        └── Exception/
            ├── error.html.twig       # Fallback générique
            ├── error403.html.twig    # Accès interdit
            ├── error404.html.twig    # Page non trouvée
            └── error500.html.twig    # Erreur serveur

src/
└── Controller/
    └── ErrorTestController.php      # Controller de test (dev only)

templates/
└── error_test/
    └── index.html.twig              # Page de test des erreurs

assets/
└── images/
    └── unit404.png                  # Avatar de Unit 404
```

### Personnage : Unit 404

**Unit 404** est un agent fictif qui apparaît sur toutes les pages d'erreur :
- Avatar stylisé avec effet de shadow coloré selon l'erreur
- Citations humoristiques et décalées
- Rôles variés : spécialiste en disparitions, chef de la sécurité, expert en catastrophes

### Design

**Éléments visuels** :
- Layout sans navigation (`layouts-without-nav.html.twig`)
- Thème Bootstrap 5 (Skote)
- Icônes Boxicons animées
- Avatar Unit 404 avec drop-shadow dynamique
- Cartes d'alerte colorées selon le type d'erreur

**Couleurs** :
- 404 : Bleu primaire (`#667eea`)
- 403 : Rouge danger (`#ef4444`)
- 500 : Jaune warning (`#fbbf24`)
- Générique : Gris secondaire

## Testing

### En développement (APP_ENV=dev)

#### Via le controller de test (recommandé)

**URL de test** : `/test-errors` (accessible uniquement pour ROLE_ADMIN)

Le controller `ErrorTestController` fournit :
- Une page d'index avec liens vers toutes les erreurs
- Routes dédiées générant des exceptions :
  - `/test-errors/404` → NotFoundHttpException
  - `/test-errors/403` → AccessDeniedHttpException
  - `/test-errors/500` → RuntimeException

#### Via le profiler Symfony

En mode debug, Symfony fournit également :
- `/_error/404` - Prévisualisation erreur 404
- `/_error/403` - Prévisualisation erreur 403
- `/_error/500` - Prévisualisation erreur 500

### En production (APP_ENV=prod)

Les pages d'erreur personnalisées s'affichent **automatiquement** pour toutes les erreurs HTTP.

**Important** : En production, les détails techniques des erreurs sont masqués pour des raisons de sécurité.

## Configuration Symfony

### Environnement de production

Les templates personnalisés dans `templates/bundles/TwigBundle/Exception/` sont automatiquement utilisés en production.

### Environnement de développement

En mode debug, Symfony affiche par défaut la page d'erreur détaillée avec la stack trace.

Pour tester les pages personnalisées en dev :
1. Utiliser le controller de test `/test-errors`
2. Utiliser les routes du profiler `/_error/{code}`
3. Désactiver temporairement le mode debug dans `.env.local` :
   ```
   APP_DEBUG=0
   ```

## Variables Twig disponibles

Dans les templates d'erreur, les variables suivantes sont disponibles :

- `status_code` : Code HTTP de l'erreur (404, 403, 500, etc.)
- `status_text` : Texte descriptif de l'erreur (Not Found, Forbidden, etc.)
- `exception` : Objet exception (uniquement en dev)

## Bonnes pratiques

### Accessibilité
- Utilisation d'icônes avec texte descriptif
- Contrastes de couleurs respectant WCAG
- Liens et boutons clairement identifiables
- Messages d'erreur compréhensibles

### UX
- Ton humoristique pour dédramatiser
- Actions claires (retour, réessayer, connexion)
- Informations utiles sans détails techniques
- Design cohérent avec le reste de l'application

### Sécurité
- Pas d'affichage de détails techniques en production
- Pas de stack traces visibles
- Messages d'erreur génériques sans révéler l'architecture
- Gestion appropriée des permissions (403)

## Maintenance

### Ajouter une nouvelle page d'erreur

1. Créer un template dans `templates/bundles/TwigBundle/Exception/error{CODE}.html.twig`
2. Étendre le layout `layouts/layouts-without-nav.html.twig`
3. Ajouter une citation d'Unit 404
4. Tester via `ErrorTestController`

### Modifier les citations

Les citations sont directement dans les templates :
- `error404.html.twig` : ligne 17-18
- `error403.html.twig` : ligne 17-18
- `error500.html.twig` : ligne 17-18

### Personnaliser l'avatar

L'image est située dans `assets/images/unit404.png`.

Pour modifier :
1. Remplacer le fichier `unit404.png`
2. Rebuild les assets : `./build-assets.sh prod`
3. Vider le cache : `php bin/console cache:clear`

## Extension future

### Autres codes d'erreur
- **401** : Non authentifié
- **429** : Too Many Requests
- **503** : Service Unavailable

### Analytics
- Tracking des erreurs 404 pour détecter les liens cassés
- Monitoring des erreurs 500 pour alertes
- Statistiques d'accès interdit (403)

### Multilingue
- Traduction des messages
- Citations adaptées à la langue

---

**Dernière mise à jour** : 23 décembre 2025
**Version** : 1.0
**Lot** : 20 - Pages d'erreur personnalisées
