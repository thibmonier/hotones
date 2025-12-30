# Configuration Email pour Lead Magnet

Ce document explique comment configurer l'envoi d'emails pour le système de lead magnet (téléchargement du guide des 15 KPIs).

## Vue d'ensemble

Le système utilise **Symfony Mailer** pour envoyer automatiquement des emails aux leads qui téléchargent le guide. Les emails sont envoyés via le service `LeadMagnetMailer`.

## Configuration SMTP

### Variables d'environnement requises

Dans votre fichier `.env` ou `.env.local`, configurez les variables suivantes :

```bash
###> symfony/mailer ###
MAILER_DSN=smtp://user:pass@smtp.example.com:587
###< symfony/mailer ###

###> Email From Configuration ###
MAIL_FROM_ADDRESS=noreply@hotones.io
MAIL_FROM_NAME="HotOnes"
###< Email From Configuration ###
```

### Exemples de configuration SMTP par fournisseur

#### Gmail

```bash
# Option 1 : Avec mot de passe d'application (recommandé)
MAILER_DSN=gmail+smtp://username@gmail.com:app-password@default

# Option 2 : Avec mot de passe normal (moins sécurisé)
MAILER_DSN=smtp://username@gmail.com:password@smtp.gmail.com:587
```

**Note** : Pour Gmail, vous devez activer l'authentification à deux facteurs et générer un "mot de passe d'application" :
1. Aller dans les paramètres Google Account
2. Sécurité → Authentification à deux facteurs → Mots de passe d'application
3. Générer un nouveau mot de passe pour "Mail"

#### SendGrid

```bash
MAILER_DSN=sendgrid://API_KEY@default
```

#### Mailgun

```bash
MAILER_DSN=mailgun://API_KEY:DOMAIN@default
```

#### Amazon SES

```bash
MAILER_DSN=ses+smtp://ACCESS_KEY:SECRET_KEY@default?region=eu-west-1
```

#### OVH

```bash
MAILER_DSN=smtp://username@domain.com:password@ssl0.ovh.net:587
```

#### Office 365 / Outlook

```bash
MAILER_DSN=smtp://username@domain.com:password@smtp.office365.com:587
```

#### Serveur SMTP générique

```bash
# Avec TLS (port 587)
MAILER_DSN=smtp://username:password@smtp.example.com:587

# Avec SSL (port 465)
MAILER_DSN=smtp://username:password@smtp.example.com:465?encryption=ssl

# Sans chiffrement (déconseillé)
MAILER_DSN=smtp://username:password@smtp.example.com:25?encryption=none
```

## Configuration de développement

### Utiliser Mailpit (recommandé pour dev)

Mailpit est déjà configuré dans `docker-compose.yml` :

```yaml
mailpit:
  image: axllent/mailpit
  ports:
    - "8025:8025"
    - "1025:1025"
```

Configuration dans `.env.local` :

```bash
MAILER_DSN=smtp://mailpit:1025
```

**Interface web** : http://localhost:8025

Tous les emails envoyés en développement seront capturés par Mailpit et visibles dans l'interface web.

### Utiliser Mailtrap

```bash
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
```

### Mode null transport (ne pas envoyer d'emails)

```bash
MAILER_DSN=null://null
```

## Configuration de production

### Recommandations

1. **Utiliser un service d'envoi professionnel** : SendGrid, Mailgun, Amazon SES
   - Meilleure délivrabilité
   - Statistiques d'envoi
   - Gestion des bounces et spam
   - Infrastructure fiable

2. **Configurer SPF, DKIM et DMARC** pour votre domaine
   - Améliore la délivrabilité
   - Évite que vos emails soient marqués comme spam

3. **Utiliser des variables d'environnement** pour les credentials
   - Ne jamais committer les mots de passe dans le code
   - Utiliser `.env.local` ou des secrets sur votre plateforme de déploiement

### Exemple avec Render.com

Dans les variables d'environnement de votre service Render :

```
MAILER_DSN=smtp://apikey:SG.xxxxxxxxxxxxx@smtp.sendgrid.net:587
MAIL_FROM_ADDRESS=noreply@hotones.io
MAIL_FROM_NAME=HotOnes
```

## Test de configuration

### Commande de test manuelle

Créez un fichier de test temporaire :

```php
// test-email.php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

$dsn = $_ENV['MAILER_DSN'] ?? 'smtp://mailpit:1025';
$transport = Transport::fromDsn($dsn);
$mailer = new Mailer($transport);

$email = (new Email())
    ->from('noreply@hotones.io')
    ->to('test@example.com')
    ->subject('Test Email HotOnes')
    ->text('Ceci est un email de test.');

try {
    $mailer->send($email);
    echo "Email envoyé avec succès !\n";
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
```

Exécuter :

```bash
docker compose exec app php test-email.php
```

### Vérifier les logs Symfony

```bash
# Logs généraux
docker compose logs -f app

# Logs Symfony spécifiques
docker compose exec app tail -f /var/www/html/var/log/dev.log
```

## Emails envoyés par le système

### 1. Email de téléchargement du guide

**Trigger** : Soumission du formulaire sur `/ressources/guide-kpis`

**Template** :
- HTML : `templates/emails/lead_magnet/guide_kpis.html.twig`
- Texte : `templates/emails/lead_magnet/guide_kpis.txt.twig`

**Variables** :
- `firstName` : Prénom du lead
- `downloadUrl` : URL de téléchargement du PDF
- `thankYouUrl` : URL de la page de remerciement

**Service** : `App\Service\LeadMagnetMailer::sendGuideKpisEmail()`

### 2. Emails de nurturing (à implémenter)

Séquence automatique après le téléchargement :

- **J+1** : Conseils pour mettre en place les premiers KPIs
- **J+3** : Cas pratique d'une agence qui a amélioré sa rentabilité
- **J+7** : Découverte de HotOnes et proposition d'essai gratuit

**Service** : `App\Service\LeadMagnetMailer::sendNurturingDay1/3/7()`

## Résolution de problèmes

### Les emails ne partent pas

1. **Vérifier la configuration SMTP**
   ```bash
   docker compose exec app php bin/console debug:config framework mailer
   ```

2. **Vérifier les logs**
   ```bash
   docker compose exec app tail -f var/log/dev.log | grep -i mail
   ```

3. **Tester la connexion SMTP manuellement**
   ```bash
   docker compose exec app telnet smtp.example.com 587
   ```

### Les emails arrivent dans les spams

1. **Configurer SPF** pour votre domaine :
   ```
   TXT @ "v=spf1 include:_spf.google.com ~all"
   ```

2. **Configurer DKIM** (via votre fournisseur d'email)

3. **Configurer DMARC** :
   ```
   TXT _dmarc "v=DMARC1; p=none; rua=mailto:dmarc@hotones.io"
   ```

4. **Vérifier la réputation de votre IP/domaine** :
   - https://mxtoolbox.com/blacklists.aspx
   - https://www.mail-tester.com/

### Erreur "Could not authenticate"

- Vérifier que le username/password sont corrects
- Pour Gmail : utiliser un mot de passe d'application
- Vérifier que le port est correct (587 pour TLS, 465 pour SSL)

### Timeout de connexion

- Vérifier le firewall (ports 587, 465, 25)
- Vérifier que le serveur SMTP est accessible depuis votre environnement
- Augmenter le timeout dans la configuration si nécessaire

## Monitoring et statistiques

### Logs d'envoi

Les emails envoyés sont automatiquement logués par Symfony :

```bash
docker compose exec app grep "Email sent" var/log/prod.log
```

### Suivi des leads

Les téléchargements sont enregistrés dans la table `lead_captures` :

```sql
SELECT
    email,
    first_name,
    last_name,
    created_at,
    downloaded_at,
    download_count
FROM lead_captures
ORDER BY created_at DESC;
```

### Statistiques via le repository

```php
$stats = $leadCaptureRepository->getStats();
// Returns:
// [
//     'total' => 150,
//     'with_marketing_consent' => 120,
//     'downloaded' => 100,
//     'consent_rate' => 80.0,
//     'download_rate' => 66.67,
//     'avg_downloads' => 1.2
// ]
```

## Sécurité

### Bonnes pratiques

1. **Ne jamais exposer les credentials SMTP** dans le code ou les commits
2. **Utiliser des variables d'environnement** pour tous les secrets
3. **Valider les emails** avant l'envoi (déjà fait dans le formulaire)
4. **Rate limiting** : limiter le nombre d'emails par IP/email
5. **RGPD** : obtenir le consentement explicite (déjà implémenté)
6. **Unsubscribe** : permettre aux utilisateurs de se désabonner

### Protection contre le spam

Le formulaire inclut déjà :
- Validation CSRF
- Validation d'email
- Consentement RGPD obligatoire

À ajouter si besoin :
- Captcha (Google reCAPTCHA, hCaptcha)
- Rate limiting par IP
- Honeypot fields

## Ressources utiles

- [Documentation Symfony Mailer](https://symfony.com/doc/current/mailer.html)
- [Mailpit (dev mail catcher)](https://github.com/axllent/mailpit)
- [SendGrid Documentation](https://docs.sendgrid.com/)
- [Amazon SES Guide](https://docs.aws.amazon.com/ses/)
- [Email Deliverability Best Practices](https://sendgrid.com/blog/email-deliverability-best-practices/)
