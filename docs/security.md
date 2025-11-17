# Guide de Sécurité

Ce document décrit les mesures de sécurité implémentées dans HotOnes et les bonnes pratiques à suivre.

## Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Authentification](#authentification)
- [Autorisation et contrôle d'accès](#autorisation-et-contrôle-daccès)
- [Protection des données](#protection-des-données)
- [Gestion des secrets](#gestion-des-secrets)
- [Sécurité des APIs](#sécurité-des-apis)
- [Protection contre les attaques courantes](#protection-contre-les-attaques-courantes)
- [Upload de fichiers](#upload-de-fichiers)
- [Sécurité du serveur](#sécurité-du-serveur)
- [Logs et audit](#logs-et-audit)
- [Conformité RGPD](#conformité-rgpd)
- [Checklist de sécurité](#checklist-de-sécurité)

---

## Vue d'ensemble

HotOnes implémente plusieurs couches de sécurité:

| Couche | Mécanisme | Statut |
|--------|-----------|--------|
| Authentification | JWT + 2FA (TOTP) | ✅ Implémenté |
| Autorisation | Rôles hiérarchiques Symfony | ✅ Implémenté |
| CSRF | Tokens CSRF Symfony | ✅ Implémenté |
| XSS | Twig auto-escaping | ✅ Implémenté |
| SQL Injection | Doctrine ORM prepared statements | ✅ Implémenté |
| Secrets | Symfony Secrets (à activer) | ⚠️ À configurer en prod |
| HTTPS | SSL/TLS Let's Encrypt | ✅ En production |
| Rate Limiting | À implémenter | ❌ Non implémenté |

---

## Authentification

### JWT (JSON Web Tokens)

**Bundle:** LexikJWTAuthenticationBundle

**Configuration:**
```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600  # 1 heure
```

**Génération des clés:**
```bash
php bin/console lexik:jwt:generate-keypair

# Sécuriser les permissions
chmod 600 config/jwt/*.pem
```

**Bonnes pratiques:**
- ✅ TTL court (1h max)
- ✅ Clés privées hors du repository (.gitignore)
- ✅ Passphrase forte et stockée en secret
- ❌ Ne jamais exposer la clé privée
- ⚠️ Rotation des clés régulière (tous les 6 mois)

**Endpoint d'authentification:**
```http
POST /api/login
Content-Type: application/json

{
  "username": "user@example.com",
  "password": "SecurePassword123",
  "totp_code": "123456"
}
```

**Réponse:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200..."
}
```

---

### 2FA (Two-Factor Authentication)

**Implémentation:** TOTP (Time-based One-Time Password) via `spomky-labs/otphp`

**Entité User:**
```php
class User
{
    private ?string $totpSecret = null;  // Secret 2FA
}
```

**Activation du 2FA:**
1. Générer le secret TOTP
2. Afficher le QR code à scanner (Google Authenticator, Authy, etc.)
3. Vérifier le code avant d'activer
4. Stocker le secret hashé en base

**Vérification:**
```php
use OTPHP\TOTP;

$totp = TOTP::create($user->getTotpSecret());
$isValid = $totp->verify($userProvidedCode, null, 30);  // 30s de window
```

**Bonnes pratiques:**
- ✅ Codes de backup en cas de perte du device
- ✅ Window de tolérance (30s) pour décalage d'horloge
- ✅ Rate limiting sur tentatives 2FA
- ❌ Ne jamais envoyer le code 2FA par email
- ⚠️ Forcer 2FA pour les rôles sensibles (ADMIN, MANAGER)

---

### Mots de passe

**Hachage:** Symfony PasswordHasher (bcrypt ou sodium)

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: auto  # bcrypt ou sodium selon disponibilité
            cost: 12         # Pour bcrypt
```

**Bonnes pratiques implémentées:**
- ✅ Auto-hashing via UserPasswordHasher
- ✅ Algorithme moderne (bcrypt/sodium)
- ✅ Cost factor élevé (12+)

**À implémenter:**
- ❌ Politique de mot de passe fort (min 12 caractères, complexité)
- ❌ Expiration des mots de passe (tous les 90 jours)
- ❌ Historique des mots de passe (interdire réutilisation)
- ❌ Compte verrouillé après N tentatives échouées

**Exemple de validation:**
```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotCompromisedPassword]  // Vérifie si le mot de passe a fuité
#[Assert\Length(min: 12)]
#[Assert\Regex(
    pattern: '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])/',
    message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial'
)]
private string $plainPassword;
```

---

## Autorisation et contrôle d'accès

### Hiérarchie des rôles

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_INTERVENANT: []
        ROLE_CHEF_PROJET: [ROLE_INTERVENANT]
        ROLE_COMMERCIAL: [ROLE_INTERVENANT]
        ROLE_MANAGER: [ROLE_CHEF_PROJET, ROLE_COMMERCIAL]
        ROLE_COMPTA: [ROLE_USER]
        ROLE_ADMIN: [ROLE_MANAGER, ROLE_COMPTA]
```

**Principe du moindre privilège:**
- Chaque utilisateur a uniquement les rôles nécessaires
- Les rôles sont cumulatifs (hiérarchie)

---

### Protection des contrôleurs

**Attributs #[IsGranted()]:**
```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients')]
#[IsGranted('ROLE_INTERVENANT')]  // Accès minimum
class ClientController extends AbstractController
{
    #[Route('/new')]
    #[IsGranted('ROLE_CHEF_PROJET')]  // Restriction supplémentaire
    public function new(): Response { }

    #[Route('/{id}/delete')]
    #[IsGranted('ROLE_MANAGER')]  // Action sensible
    public function delete(): Response { }
}
```

---

### Voters personnalisés

**Pour logique d'autorisation complexe:**

```php
// src/Security/Voter/ProjectVoter.php
namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    const EDIT = 'PROJECT_EDIT';
    const DELETE = 'PROJECT_DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        return match($attribute) {
            self::EDIT => $this->canEdit($project, $user),
            self::DELETE => $this->canDelete($project, $user),
            default => false,
        };
    }

    private function canEdit(Project $project, User $user): bool
    {
        // Chef de projet du projet OU Manager
        return $project->getProjectManager() === $user
            || in_array('ROLE_MANAGER', $user->getRoles());
    }

    private function canDelete(Project $project, User $user): bool
    {
        // Seulement les Managers
        return in_array('ROLE_MANAGER', $user->getRoles());
    }
}
```

**Usage:**
```php
$this->denyAccessUnlessGranted('PROJECT_EDIT', $project);
```

---

## Protection des données

### Données sensibles

**Champs sensibles dans l'application:**
- Mots de passe (hashés)
- Secrets 2FA (chiffrés)
- Tokens JWT
- Données financières (CA, marges, TJM)
- Informations personnelles (email, téléphone)

**Bonnes pratiques:**
- ✅ Ne jamais logger les mots de passe en clair
- ✅ Ne jamais retourner les mots de passe dans l'API
- ✅ Chiffrer les secrets 2FA
- ⚠️ Masquer les données sensibles dans les logs
- ⚠️ Anonymisation des données de test

---

### Chiffrement en base de données

**Pour les champs ultra-sensibles (optionnel):**

```php
use Doctrine\ORM\Mapping as ORM;

class User
{
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $totpSecret = null;

    public function setTotpSecret(?string $secret): self
    {
        // Chiffrer avant stockage
        $this->totpSecret = $secret ? $this->encrypt($secret) : null;
        return $this;
    }

    public function getTotpSecret(): ?string
    {
        // Déchiffrer à la lecture
        return $this->totpSecret ? $this->decrypt($this->totpSecret) : null;
    }

    private function encrypt(string $data): string
    {
        // Utiliser sodium ou openssl
        return sodium_crypto_secretbox($data, $nonce, $key);
    }
}
```

---

## Gestion des secrets

### Symfony Secrets Vault

**Non configuré actuellement, à activer en production:**

```bash
# Générer les clés du vault
php bin/console secrets:generate-keys --env=prod

# Ajouter un secret
php bin/console secrets:set DATABASE_PASSWORD --env=prod

# Lister les secrets
php bin/console secrets:list --env=prod
```

**Dans .env.prod:**
```bash
DATABASE_URL="mysql://user:%env(DATABASE_PASSWORD)%@host/db"
```

**Avantages:**
- Secrets chiffrés dans le repository (config/secrets/prod/)
- Clé de déchiffrement hors du repository
- Audit des secrets

**Configuration serveur:**
```bash
# Copier la clé de déchiffrement sur le serveur
scp config/secrets/prod/prod.decrypt.private.php server:/var/www/hotones/config/secrets/prod/

# Sécuriser
chmod 600 /var/www/hotones/config/secrets/prod/prod.decrypt.private.php
```

---

### Variables d'environnement

**Bonnes pratiques:**
- ✅ Ne jamais committer `.env.local`, `.env.prod.local`
- ✅ Utiliser `.env.example` comme template
- ✅ Générer des secrets aléatoires (32+ caractères)
- ❌ Ne jamais hardcoder de secrets dans le code
- ⚠️ Rotation régulière des secrets (APP_SECRET, JWT_PASSPHRASE)

**Génération de secrets:**
```bash
# APP_SECRET, JWT_PASSPHRASE
php -r "echo bin2hex(random_bytes(32));"

# Ou
openssl rand -hex 32
```

---

## Sécurité des APIs

### CORS (Cross-Origin Resource Sharing)

**À configurer pour l'API:**

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api/':
            allow_origin: ['https://app.example.com']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE']
            max_age: 3600
```

**Production:**
```bash
CORS_ALLOW_ORIGIN=^https?://(app\.example\.com|admin\.example\.com)$
```

---

### Rate Limiting

**À implémenter avec RateLimiterBundle:**

```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        # Limiter les tentatives de login
        login:
            policy: 'sliding_window'
            limit: 5
            interval: '15 minutes'

        # Limiter les appels API
        api:
            policy: 'fixed_window'
            limit: 100
            interval: '1 hour'
```

**Usage dans les contrôleurs:**
```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/api/login')]
public function login(Request $request, RateLimiterFactory $loginLimiter): Response
{
    $limiter = $loginLimiter->create($request->getClientIp());

    if (false === $limiter->consume(1)->isAccepted()) {
        throw new TooManyRequestsHttpException();
    }

    // ... login logic
}
```

---

### Content Security Policy (CSP)

**Headers de sécurité dans Nginx:**

```nginx
# docker/nginx/conf.d/default.conf
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

---

## Protection contre les attaques courantes

### SQL Injection

**Protection:** Doctrine ORM avec prepared statements.

**✅ Sécurisé (Doctrine DQL):**
```php
$query = $em->createQuery('SELECT u FROM App\Entity\User u WHERE u.email = :email')
    ->setParameter('email', $userInput);  // Automatiquement échappé
```

**❌ Dangereux (SQL brut):**
```php
// NE JAMAIS FAIRE
$sql = "SELECT * FROM user WHERE email = '$userInput'";
```

**Si SQL brut nécessaire:**
```php
$sql = "SELECT * FROM user WHERE email = :email";
$stmt = $conn->prepare($sql);
$stmt->bindValue('email', $userInput);  // Paramètres liés
$stmt->execute();
```

---

### XSS (Cross-Site Scripting)

**Protection:** Twig auto-escaping activé par défaut.

**✅ Sécurisé (échappement automatique):**
```twig
{{ user.name }}  {# Échappe automatiquement les < > & etc. #}
```

**⚠️ Désactiver l'escaping (dangereux):**
```twig
{{ user.bio|raw }}  {# Ne pas échapper - risque XSS #}
```

**Bonnes pratiques:**
- Utiliser `|raw` uniquement pour du contenu de confiance
- Valider et sanitizer tout input utilisateur
- Utiliser un purificateur HTML (HtmlSanitizer) pour contenu riche

**HtmlSanitizer:**
```php
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

public function sanitizeUserContent(string $html, HtmlSanitizerInterface $sanitizer): string
{
    return $sanitizer->sanitize($html);
}
```

---

### CSRF (Cross-Site Request Forgery)

**Protection:** Tokens CSRF Symfony activés par défaut.

**Formulaires Symfony (automatique):**
```php
$form = $this->createFormBuilder()
    ->add('name')
    ->getForm();  // Token CSRF ajouté automatiquement
```

**Formulaires manuels:**
```twig
<form method="post">
    <input type="hidden" name="_token" value="{{ csrf_token('delete_item') }}">
    <button type="submit">Supprimer</button>
</form>
```

**Validation:**
```php
if ($this->isCsrfTokenValid('delete_item', $request->request->get('_token'))) {
    // Token valide, procéder
}
```

---

### SSRF (Server-Side Request Forgery)

**Risque:** Appels HTTP vers URLs fournies par l'utilisateur.

**Protection:**
```php
use Symfony\Component\HttpClient\HttpClient;

public function fetchUrl(string $url): string
{
    // Whitelist de domaines autorisés
    $allowedHosts = ['api.example.com', 'cdn.example.com'];

    $parsedUrl = parse_url($url);
    if (!in_array($parsedUrl['host'], $allowedHosts)) {
        throw new \InvalidArgumentException('Host non autorisé');
    }

    // Bloquer les IPs privées
    $ip = gethostbyname($parsedUrl['host']);
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        throw new \InvalidArgumentException('IP privée non autorisée');
    }

    $client = HttpClient::create();
    return $client->request('GET', $url)->getContent();
}
```

---

## Upload de fichiers

### Validation

**Implémentation actuelle (logos clients):**

```php
$logo = $request->files->get('logo');
if ($logo instanceof UploadedFile && $logo->isValid()) {
    // Extension basée sur MIME type
    $extension = $logo->guessExtension();

    // Nom unique
    $safeName = uniqid('client_', true) . '.' . $extension;

    // Upload
    $logo->move($uploadDir, $safeName);
}
```

**Améliorations recommandées:**

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\File(
    maxSize: '5M',
    mimeTypes: ['image/jpeg', 'image/png', 'image/svg+xml'],
    mimeTypesMessage: 'Format non autorisé. Utilisez JPG, PNG ou SVG.'
)]
private ?UploadedFile $logo = null;
```

**Validation avancée:**
```php
// Vérifier le vrai type MIME (pas juste l'extension)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $logo->getPathname());
finfo_close($finfo);

$allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml'];
if (!in_array($mimeType, $allowedTypes)) {
    throw new \Exception('Type de fichier non autorisé');
}

// Vérifier que c'est bien une image (pas un PHP déguisé)
$imageInfo = getimagesize($logo->getPathname());
if ($imageInfo === false) {
    throw new \Exception('Fichier invalide');
}
```

---

### Stockage sécurisé

**Bonnes pratiques:**
- ✅ Stocker hors de `public/` si non-publics
- ✅ Noms de fichiers aléatoires (éviter écrasement)
- ✅ Permissions strictes (775 pour dossiers, 664 pour fichiers)
- ❌ Ne jamais exécuter les fichiers uploadés
- ⚠️ Scanner antivirus (ClamAV) en production

**Configuration Nginx:**
```nginx
# Interdire l'exécution de PHP dans uploads/
location ~* ^/uploads/.*\.(php|php3|php4|php5|phtml)$ {
    deny all;
}
```

---

## Sécurité du serveur

### Firewall (UFW)

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw deny 3306/tcp   # Bloquer MySQL de l'extérieur
sudo ufw enable
```

---

### Fail2Ban

**Protection contre brute-force SSH et HTTP:**

```bash
sudo apt install fail2ban

# Configuration
sudo nano /etc/fail2ban/jail.local
```

```ini
[sshd]
enabled = true
port = 22
maxretry = 3
bantime = 3600

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 5
bantime = 600
```

---

### SSL/TLS

**Let's Encrypt (gratuit):**

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d hotones.example.com
```

**Renouvellement automatique:**
```cron
0 3 * * * certbot renew --quiet
```

**Configuration Nginx forte:**
```nginx
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers HIGH:!aNULL:!MD5;
ssl_prefer_server_ciphers on;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
```

---

### Mises à jour de sécurité

**Automatiser les mises à jour:**

```bash
# Installer unattended-upgrades
sudo apt install unattended-upgrades

# Activer
sudo dpkg-reconfigure -plow unattended-upgrades
```

**Vérifier régulièrement:**
```bash
sudo apt update
sudo apt list --upgradable
sudo apt upgrade
```

---

## Logs et audit

### Logs de sécurité

**Events à logger:**
- Tentatives de login échouées
- Accès refusés (403 Forbidden)
- Modifications de données sensibles
- Création/modification d'utilisateurs
- Changements de rôles

**EventSubscriber d'audit:**

```php
namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SecurityAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $securityLogger) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $this->securityLogger->info('Login successful', [
            'user' => $user->getUserIdentifier(),
            'ip' => $event->getRequest()->getClientIp(),
        ]);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->securityLogger->warning('Login failed', [
            'username' => $event->getPassport()?->getUser()?->getUserIdentifier(),
            'ip' => $event->getRequest()->getClientIp(),
            'reason' => $event->getException()->getMessage(),
        ]);
    }
}
```

---

### Monitoring des accès

**Outils recommandés:**
- Graylog / ELK Stack pour centralisation des logs
- Sentry pour monitoring d'erreurs
- Fail2Ban pour bannir les IPs suspectes

---

## Conformité RGPD

### Données personnelles collectées

- Identité : Nom, prénom, email
- Professionnel : Poste, entreprise, TJM
- Connexion : IP, dates de connexion, logs
- Utilisation : Temps saisis, projets, tâches

---

### Droits des utilisateurs

**À implémenter:**

1. **Droit d'accès:** Export de toutes les données
2. **Droit de rectification:** Modification via profil
3. **Droit à l'effacement:** Suppression compte
4. **Droit à la portabilité:** Export JSON/CSV
5. **Droit d'opposition:** Opt-out notifications

**Exemple export RGPD:**
```php
#[Route('/profile/export-data', methods: ['GET'])]
public function exportData(): JsonResponse
{
    $user = $this->getUser();

    $data = [
        'identity' => [
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
        ],
        'timesheets' => $this->timesheetRepo->findBy(['contributor' => $user->getContributor()]),
        // ... autres données
    ];

    return $this->json($data);
}
```

---

### Conservation des données

**Politiques recommandées:**
- Logs de sécurité : 1 an
- Notifications lues : 30 jours
- Comptes inactifs : 3 ans puis anonymisation
- Données de facturation : 10 ans (obligation légale)

---

## Checklist de sécurité

### Développement

- [ ] Variables sensibles dans .env.local (non committé)
- [ ] Validation des inputs utilisateur
- [ ] Échappement des outputs (Twig)
- [ ] CSRF tokens sur tous les formulaires
- [ ] Tests de sécurité (PHPStan, PHPMD)

### Pré-production

- [ ] Audit de sécurité du code
- [ ] Scan des dépendances (composer audit)
- [ ] Tests de pénétration
- [ ] Configuration HTTPS
- [ ] Headers de sécurité configurés

### Production

- [ ] SSL/TLS activé (A+ sur SSLLabs)
- [ ] Firewall configuré (UFW)
- [ ] Fail2Ban actif
- [ ] Secrets en Symfony Vault
- [ ] Sauvegardes automatiques chiffrées
- [ ] Monitoring actif (Sentry, logs)
- [ ] Mises à jour de sécurité automatiques
- [ ] Rate limiting activé
- [ ] 2FA obligatoire pour admins
- [ ] Politique de mots de passe forts

---

## Ressources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Symfony Security Best Practices](https://symfony.com/doc/current/security/best_practices.html)
- [ANSSI - Recommandations de sécurité](https://www.ssi.gouv.fr/)
- [CNIL - RGPD](https://www.cnil.fr/fr/reglement-europeen-protection-donnees)

---

## Voir aussi

- [Deployment](deployment.md) - Sécurisation du serveur
- [Environment Variables](environment-variables.md) - Gestion des secrets
- [API](api.md) - Sécurité des APIs
- [Roles](roles.md) - Contrôle d'accès
