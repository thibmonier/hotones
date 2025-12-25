# Documentation Technique RGPD - HotOnes

## Vue d'ensemble

Cette documentation technique détaille l'implémentation RGPD dans HotOnes, destinée aux développeurs et administrateurs système.

## Architecture

### Entités

#### CookieConsent
**Fichier:** `src/Entity/CookieConsent.php`

Stocke les consentements cookies des utilisateurs pour traçabilité RGPD.

```php
class CookieConsent
{
    private ?int $id;
    private ?User $user;              // Nullable pour visiteurs anonymes
    private bool $essential = true;   // Toujours true (obligatoire)
    private bool $functional = false; // Optionnel
    private bool $analytics = false;  // Optionnel
    private string $version;          // Version de la politique (ex: "1.0")
    private ?string $ipAddress;       // IP pour traçabilité
    private ?string $userAgent;       // Navigateur pour traçabilité
    private \DateTimeImmutable $consentedAt; // Date du consentement
}
```

**Index:**
- `user_id` (pour retrouver les consentements d'un utilisateur)
- `consented_at` (pour tri chronologique)

**Repository:** `CookieConsentRepository`
- `findLatestForUser(User $user): ?CookieConsent` - Dernier consentement de l'utilisateur

#### AccountDeletionRequest
**Fichier:** `src/Entity/AccountDeletionRequest.php`

Gère le workflow de suppression de compte avec période de grâce.

```php
class AccountDeletionRequest
{
    private ?int $id;
    private User $user;
    private string $confirmationToken;        // Token unique pour confirmation email
    private \DateTimeImmutable $requestedAt;  // Date de la demande initiale
    private ?\DateTimeImmutable $confirmedAt; // Date de confirmation (clic email)
    private ?\DateTimeImmutable $scheduledDeletionAt; // Date prévue suppression (confirmedAt + 30j)
    private ?\DateTimeImmutable $deletedAt;   // Date suppression effective
    private ?\DateTimeImmutable $cancelledAt; // Date annulation (si applicable)
    private ?string $ipAddress;               // IP de la demande
    private string $status;                   // pending|confirmed|processing|deleted|cancelled
}
```

**Statuts possibles:**
- `pending` - Demande créée, en attente confirmation email (48h max)
- `confirmed` - Email confirmé, période de grâce active (30j)
- `processing` - Suppression en cours (état transitoire)
- `deleted` - Compte supprimé définitivement
- `cancelled` - Demande annulée par l'utilisateur

**Méthodes métier:**
- `confirm(): void` - Confirme la demande, calcule scheduledDeletionAt (+30j), status = confirmed
- `cancel(): void` - Annule la demande, status = cancelled
- `delete(): void` - Marque comme supprimée, status = deleted
- `isConfirmationTokenExpired(): bool` - Vérifie si token expiré (48h)
- `isInGracePeriod(): bool` - Vérifie si période de grâce active
- `isDeletionDue(): bool` - Vérifie si date de suppression atteinte

**Index:**
- `user_id` (unique si status = pending|confirmed)
- `status` (pour queries de filtrage)
- `scheduled_deletion_at` (pour processus automatique)
- `confirmation_token` (unique, pour retrouver par token)

**Repository:** `AccountDeletionRequestRepository`
- `findByConfirmationToken(string $token): ?AccountDeletionRequest`
- `findActiveDeletionRequestForUser(User $user): ?AccountDeletionRequest`
- `findDueDeletionRequests(): array` - Demandes à traiter (scheduledDeletionAt <= now)
- `findRequestsNeedingReminder(int $daysBefore = 7): array` - Demandes nécessitant rappel

### Services

#### GdprDataExportService
**Fichier:** `src/Service/GdprDataExportService.php`

Service d'export complet des données utilisateur au format JSON.

**Méthode principale:**
```php
public function exportUserData(User $user): array
{
    return [
        'user' => [...],           // Données compte
        'contributor' => [...],    // Profil collaborateur
        'employment_periods' => [...],
        'timesheets' => [...],
        'plannings' => [...],
        'cookie_consents' => [...],
        'export_metadata' => [
            'exported_at' => '2025-01-15T10:30:00+01:00',
            'export_version' => '1.0',
            'gdpr_compliance' => 'EU 2016/679'
        ]
    ];
}
```

**Données exportées:**
- Informations compte (id, email, nom, prénom, rôles, téléphone)
- Données collaborateur (si applicable)
- Périodes d'emploi avec profils
- Tous les timesheets (projet, tâche, heures, description)
- Plannings futurs et passés
- Historique consentements cookies
- Métadonnées d'export (date, version, conformité)

**Format:** JSON avec structure hiérarchique, dates ISO 8601, compatible RGPD.

#### GdprEmailService
**Fichier:** `src/Service/GdprEmailService.php`

Service centralisé pour tous les emails RGPD.

**Configuration:**
```php
private const DPO_EMAIL = 'dpo@hotones.example';     // À configurer
private const FROM_EMAIL = 'noreply@hotones.example'; // À configurer
private const FROM_NAME = 'HotOnes - RGPD';
```

**Méthodes:**
```php
// Email initial avec lien confirmation (48h)
public function sendDeletionRequestConfirmation(AccountDeletionRequest $deletionRequest): void

// Email confirmation début période grâce (30j)
public function sendDeletionConfirmed(AccountDeletionRequest $deletionRequest): void

// Email rappel 7j avant suppression
public function sendDeletionReminder(AccountDeletionRequest $deletionRequest): void

// Email confirmation annulation
public function sendDeletionCancelled(AccountDeletionRequest $deletionRequest): void
```

**Templates utilisés:**
- `emails/gdpr/deletion_request_confirmation.html.twig` + `.txt.twig`
- `emails/gdpr/deletion_confirmed.html.twig` + `.txt.twig`
- `emails/gdpr/deletion_reminder.html.twig` + `.txt.twig`
- `emails/gdpr/deletion_cancelled.html.twig` + `.txt.twig`

**Gestion d'erreurs:**
- Try-catch sur chaque envoi
- Logging complet (succès + erreurs)
- Exception rethrow pour signalement au contrôleur
- Dégradation gracieuse (application continue même si email échoue)

### Contrôleur

#### GdprController
**Fichier:** `src/Controller/GdprController.php`

**Routes:**

```php
// Sauvegarde consentement cookies (AJAX)
POST /gdpr/cookie-consent -> cookie_consent_save

// Pages publiques
GET /gdpr/privacy-policy -> privacy_policy
GET /gdpr/legal-notice -> legal_notice
GET /gdpr/user-guide -> gdpr_user_guide

// Gestion données personnelles (authentifié)
GET /gdpr/my-data -> gdpr_my_data
POST /gdpr/export-my-data -> gdpr_export_data

// Workflow suppression compte
POST /gdpr/request-account-deletion -> gdpr_request_deletion
GET /gdpr/confirm-account-deletion/{token} -> gdpr_confirm_deletion
POST /gdpr/cancel-account-deletion -> gdpr_cancel_deletion
```

**Sécurité:**
- CSRF protection sur toutes les actions POST
- Authentification requise pour actions utilisateur
- Validation des tokens de confirmation
- Logging de toutes les actions RGPD

### Commandes CLI

#### ProcessAccountDeletionsCommand
**Fichier:** `src/Command/ProcessAccountDeletionsCommand.php`

Processus automatique de suppression des comptes dus.

**Usage:**
```bash
# Mode dry-run (affiche ce qui serait fait)
php bin/console app:process-account-deletions --dry-run

# Mode production (suppression réelle)
php bin/console app:process-account-deletions

# Mode verbeux avec détails
php bin/console app:process-account-deletions -vv
```

**Workflow:**
1. Récupère toutes les demandes avec `scheduledDeletionAt <= now` et status = confirmed
2. Pour chaque demande:
   - Vérifie que la période de grâce est écoulée
   - Appelle `deleteUserAccount($user)` (à implémenter)
   - Marque la demande comme deleted
   - Log l'action

**À implémenter:**
```php
private function deleteUserAccount(User $user): void
{
    // TODO: Choisir stratégie de suppression
    // Option 1: Soft delete (isDeleted = true)
    // Option 2: Anonymisation (remplace données par "DELETED_USER_XXX")
    // Option 3: Hard delete (suppression physique)

    // Conserver données légales (factures) selon obligations comptables (10 ans)
}
```

**Cron recommandé:**
```bash
# Tous les jours à 2h du matin
0 2 * * * cd /path/to/hotones && php bin/console app:process-account-deletions >> /var/log/gdpr-deletions.log 2>&1
```

## Workflow Détaillé

### 1. Consentement Cookies

```mermaid
sequenceDiagram
    User->>Frontend: Visite le site
    Frontend->>User: Affiche bannière cookies
    User->>Frontend: Accepte/Refuse cookies
    Frontend->>API: POST /gdpr/cookie-consent
    API->>DB: INSERT CookieConsent
    API->>Frontend: {success: true}
    Frontend->>User: Confirmation visuelle
```

**Points d'attention:**
- Bannière affichée uniquement si aucun consentement enregistré
- Cookies essentiels toujours activés (session, CSRF, sécurité)
- Traçabilité complète (IP, User-Agent, timestamp)

### 2. Export de Données

```mermaid
sequenceDiagram
    User->>Frontend: Clique "Exporter mes données"
    Frontend->>API: POST /gdpr/export-my-data
    API->>GdprDataExportService: exportUserData(user)
    GdprDataExportService->>DB: Queries (user, contributor, timesheets, etc.)
    DB->>GdprDataExportService: Data
    GdprDataExportService->>API: JSON array
    API->>User: Download my_data_email_2025-01-15.json
```

**Points d'attention:**
- Export immédiat (pas de queue)
- Toutes les données personnelles incluses
- Format JSON structuré et lisible
- Nom de fichier unique avec email et date

### 3. Suppression de Compte (Workflow Complet)

```mermaid
sequenceDiagram
    participant User
    participant Frontend
    participant API
    participant Email
    participant Cron
    participant DB

    User->>Frontend: Demande suppression
    Frontend->>API: POST /gdpr/request-account-deletion
    API->>DB: INSERT AccountDeletionRequest (status=pending)
    API->>Email: sendDeletionRequestConfirmation()
    Email->>User: Email avec lien (48h)
    API->>Frontend: {success: true}

    Note over User,Email: Utilisateur a 48h pour confirmer

    User->>API: GET /gdpr/confirm-account-deletion/{token}
    API->>DB: UPDATE status=confirmed, scheduledDeletionAt=now+30j
    API->>Email: sendDeletionConfirmed()
    Email->>User: Email période grâce (30j)
    API->>User: Redirect /gdpr/my-data

    Note over User,Cron: Période de grâce 30 jours (annulation possible)

    Cron->>API: app:process-account-deletions (J-7)
    API->>Email: sendDeletionReminder()
    Email->>User: Email rappel (7j restants)

    Note over User,Cron: Utilisateur peut annuler jusqu'au dernier jour

    Cron->>API: app:process-account-deletions (J+30)
    API->>DB: DELETE/ANONYMIZE user data
    API->>DB: UPDATE status=deleted
    API->>Email: sendDeletionCompleted() (optionnel)
```

**Cas spéciaux:**

**Annulation pendant période de grâce:**
```mermaid
sequenceDiagram
    User->>API: POST /gdpr/cancel-account-deletion
    API->>DB: UPDATE status=cancelled
    API->>Email: sendDeletionCancelled()
    Email->>User: Email confirmation annulation
    API->>User: {success: true}
```

**Token expiré (48h):**
```mermaid
sequenceDiagram
    User->>API: GET /gdpr/confirm-account-deletion/{token} (après 48h)
    API->>DB: SELECT AccountDeletionRequest
    API->>API: isConfirmationTokenExpired() = true
    API->>User: Flash error "Token expiré, refaire demande"
```

## Points d'Attention Développeurs

### 1. Sécurité

**Tokens de confirmation:**
- Générés avec `bin2hex(random_bytes(32))` (64 caractères)
- Uniques en base de données
- Expiration automatique 48h
- Invalides après utilisation (status change)

**Protection CSRF:**
- Toutes les routes POST/DELETE protégées
- Validation via `$this->isCsrfTokenValid('token_id', $token)`
- Tokens dans formulaires Twig: `{{ csrf_token('token_id') }}`

**Validation des permissions:**
- Vérifier que l'utilisateur connecté = propriétaire des données
- Pas de leak d'informations via messages d'erreur
- Logging de toutes les actions sensibles

### 2. Performance

**Export de données:**
- Peut être lourd pour utilisateurs avec beaucoup de timesheets
- Envisager pagination/limite si > 10 000 entrées
- Possibilité d'ajouter queue asynchrone (Messenger) si nécessaire

**Queries N+1:**
- Utiliser `addSelect()` sur les jointures
- Précharger relations dans repositories
- Exemple: `findActiveDeletionRequestForUser()` utilise JOIN FETCH

### 3. Emails

**Configuration production:**
```yaml
# .env.local ou .env.prod
MAILER_DSN=smtp://user:pass@smtp.mailtrap.io:2525

# config/packages/mailer.yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
        headers:
            From: 'noreply@hotones.example'
```

**Templates:**
- Toujours fournir HTML + TXT
- Tester sur différents clients (Gmail, Outlook, Apple Mail)
- Éviter CSS complexe (support limité)
- Images inline ou hébergées (pas d'attachments)

**Monitoring:**
- Logger tous les envois (succès + échecs)
- Alerter si taux échec > 5%
- Vérifier spam score avec Mail Tester

### 4. Suppression de Données

**Stratégies possibles:**

**Option 1: Soft Delete (recommandé pour démarrage)**
```php
// User entity
private bool $isDeleted = false;
private ?\DateTimeImmutable $deletedAt = null;

// Dans deleteUserAccount()
$user->setIsDeleted(true);
$user->setDeletedAt(new \DateTimeImmutable());
$user->setEmail('deleted_' . $user->getId() . '@deleted.local');
$user->setFirstName('SUPPRIMÉ');
$user->setLastName('SUPPRIMÉ');
// Garder l'ID pour relations étrangères
```

**Option 2: Anonymisation (RGPD compliant)**
```php
$anonymousId = 'ANON_' . bin2hex(random_bytes(8));
$user->setEmail($anonymousId . '@anonymized.local');
$user->setFirstName('Utilisateur');
$user->setLastName('Anonymisé');
$user->setPhone(null);
// Supprimer données collaborateur
if ($contributor = $user->getContributor()) {
    $em->remove($contributor);
}
// Anonymiser timesheets
foreach ($user->getTimesheets() as $timesheet) {
    $timesheet->setDescription('Anonymisé');
}
```

**Option 3: Hard Delete (attention aux contraintes FK)**
```php
// Supprimer d'abord toutes les relations
foreach ($user->getTimesheets() as $timesheet) {
    $em->remove($timesheet);
}
foreach ($user->getPlannings() as $planning) {
    $em->remove($planning);
}
// ... toutes les autres relations
$em->remove($user);
```

**Données à conserver légalement:**
- Factures, contrats (10 ans minimum)
- Bulletins de paie (50 ans)
- Documents comptables (10 ans)
→ Anonymiser plutôt que supprimer

### 5. Tests

**Tests à écrire:**

```php
// tests/Unit/Entity/AccountDeletionRequestTest.php
testConfirmSetsConfirmedAtAndScheduledDeletionAt()
testCancelSetsStatusToCancelled()
testIsConfirmationTokenExpired()
testIsInGracePeriod()
testIsDeletionDue()

// tests/Integration/Repository/AccountDeletionRequestRepositoryTest.php
testFindByConfirmationToken()
testFindActiveDeletionRequestForUser()
testFindDueDeletionRequests()
testFindRequestsNeedingReminder()

// tests/Functional/Controller/GdprControllerTest.php
testRequestAccountDeletion()
testConfirmAccountDeletion()
testCancelAccountDeletion()
testExportMyData()
testCookieConsentSave()

// tests/Unit/Service/GdprDataExportServiceTest.php
testExportUserDataIncludesAllFields()
testExportFormatIsValidJson()

// tests/Unit/Service/GdprEmailServiceTest.php (avec EmailAssertions)
testSendDeletionRequestConfirmationSendsEmail()
testSendDeletionConfirmedIncludesScheduledDate()
```

## Configuration Production

### 1. Variables d'environnement

```bash
# .env.prod
MAILER_DSN=smtp://apikey:SG.xxx@smtp.sendgrid.net:587
DPO_EMAIL=dpo@votre-domaine.com
FROM_EMAIL=noreply@votre-domaine.com
```

### 2. Cron Jobs

```bash
# /etc/crontab ou crontab -e

# Traitement suppressions de compte (tous les jours à 2h)
0 2 * * * cd /var/www/hotones && php bin/console app:process-account-deletions >> /var/log/hotones/gdpr-deletions.log 2>&1

# Envoi rappels 7j avant suppression (tous les jours à 9h)
0 9 * * * cd /var/www/hotones && php bin/console app:send-deletion-reminders >> /var/log/hotones/gdpr-reminders.log 2>&1
```

**Note:** La commande `app:send-deletion-reminders` reste à créer si besoin.

### 3. Monitoring

**Logs à surveiller:**
```bash
# Suppressions effectuées
grep "Account deleted" /var/log/hotones/prod.log

# Échecs d'envoi email
grep "Failed to send" /var/log/hotones/prod.log

# Demandes de suppression
grep "GDPR deletion request" /var/log/hotones/prod.log
```

**Métriques recommandées:**
- Nombre demandes suppression / mois
- Taux annulation (cancelled / total)
- Délai moyen entre demande et confirmation
- Taux échec envoi emails

### 4. Backup et Restauration

**Avant suppression définitive:**
- Optionnel: Backup des données utilisateur dans archive chiffrée
- Conservation 30 jours supplémentaires en cas de litige
- Puis suppression définitive de l'archive

```php
// Dans deleteUserAccount() - optionnel
$backupData = $this->gdprDataExportService->exportUserData($user);
$filename = sprintf('backup_user_%d_%s.json.enc', $user->getId(), date('Y-m-d'));
$encrypted = openssl_encrypt(
    json_encode($backupData),
    'aes-256-cbc',
    $_ENV['BACKUP_ENCRYPTION_KEY'],
    0,
    $_ENV['BACKUP_ENCRYPTION_IV']
);
file_put_contents('/var/backups/gdpr/' . $filename, $encrypted);
```

## Évolutions Futures

### Fonctionnalités à envisager

1. **Droit de rectification**
   - Interface permettant à l'utilisateur de modifier ses données
   - Validation et traçabilité des modifications

2. **Droit d'opposition**
   - Possibilité de refuser certains traitements (marketing, profilage)
   - Granularité fine des consentements

3. **Portabilité avancée**
   - Export vers formats standards (XML, CSV)
   - API REST pour transfert automatique vers autres services

4. **Audit RGPD**
   - Dashboard admin avec statistiques RGPD
   - Historique complet des actions par utilisateur
   - Rapport de conformité automatique

5. **Gestion des sous-traitants**
   - Liste des processeurs de données (Stripe, SendGrid, etc.)
   - Suivi des DPA (Data Processing Agreements)

6. **Privacy by Design**
   - Minimisation automatique des données collectées
   - Durée de conservation configurable par type de données
   - Purge automatique des données obsolètes

## Ressources

### Documentation RGPD officielle
- [RGPD - Texte officiel](https://eur-lex.europa.eu/eli/reg/2016/679/oj)
- [CNIL - Guide développeurs](https://www.cnil.fr/fr/guide-developpeur)
- [CNIL - Exemples de mentions](https://www.cnil.fr/fr/modeles-de-mentions-dinformation)

### Symfony & RGPD
- [Symfony Security Best Practices](https://symfony.com/doc/current/security.html)
- [Symfony Mailer](https://symfony.com/doc/current/mailer.html)
- [Doctrine Data Privacy](https://www.doctrine-project.org/projects/doctrine-orm/en/current/cookbook/implementing-the-notify-changetracking-policy.html)

### Outils
- [Mail Tester](https://www.mail-tester.com/) - Test spam score emails
- [GDPR Checklist](https://gdprchecklist.io/) - Liste de conformité
- [Privacy Policies Generator](https://www.freeprivacypolicy.com/) - Générateur de politique

---

**Dernière mise à jour:** 2025-01-15
**Version:** 1.0
**Mainteneur:** Équipe développement HotOnes
