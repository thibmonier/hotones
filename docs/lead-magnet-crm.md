# Lead Magnet & CRM HotOnes - Documentation Complète

## Vue d'ensemble

Ce système complet permet de capturer des leads via un guide téléchargeable ("15 KPIs Essentiels pour Agences Web"), de les nourrir automatiquement avec une séquence d'emails, et de les gérer via un dashboard CRM dédié.

## Architecture du système

### 1. Capture de leads (Lead Magnet)

#### Entités

**LeadCapture** (`src/Entity/LeadCapture.php`)
- Stocke les informations des leads capturés
- Champs principaux :
  - `email`, `firstName`, `lastName`, `company`, `phone`
  - `source` : origine du lead (homepage, pricing, analytics, etc.)
  - `status` : statut dans le funnel (new, nurturing, qualified, converted, lost)
  - `marketingConsent` : consentement RGPD pour emails marketing
  - `downloadedAt`, `downloadCount` : tracking des téléchargements
  - `nurturingDay1SentAt`, `nurturingDay3SentAt`, `nurturingDay7SentAt` : tracking emails nurturing

#### Formulaire & Controller

**LeadCaptureType** (`src/Form/LeadCaptureType.php`)
- Formulaire RGPD-compliant avec validation
- Deux checkboxes de consentement : marketing (optionnel) et RGPD (obligatoire)

**LeadMagnetController** (`src/Controller/LeadMagnetController.php`)
- `/ressources/guide-kpis` : Landing page avec formulaire
- `/ressources/merci` : Page de remerciement
- `/ressources/telecharger/guide-kpis` : Téléchargement du PDF avec tracking

#### Templates

- `templates/lead_magnet/guide_kpis.html.twig` : Landing page
- `templates/lead_magnet/thank_you.html.twig` : Page de confirmation

### 2. Emails automatiques

#### Service d'envoi

**LeadMagnetMailer** (`src/Service/LeadMagnetMailer.php`)
- `sendGuideKpisEmail()` : Email immédiat avec lien de téléchargement
- `sendNurturingDay1()` : Email J+1 - Conseils pour démarrer
- `sendNurturingDay3()` : Email J+3 - Cas pratique d'agence
- `sendNurturingDay7()` : Email J+7 - Proposition d'essai HotOnes

#### Templates d'emails

- `templates/emails/lead_magnet/guide_kpis.html.twig` : Version HTML
- `templates/emails/lead_magnet/guide_kpis.txt.twig` : Version texte

### 3. Automatisation nurturing

#### Messages Messenger

**SendLeadNurturingEmailMessage** (`src/Message/SendLeadNurturingEmailMessage.php`)
- Message pour dispatcher l'envoi d'un email de nurturing individuel
- Paramètres : `leadId`, `dayNumber` (1, 3 ou 7)

**ProcessNurturingEmailsMessage** (`src/Message/ProcessNurturingEmailsMessage.php`)
- Message déclenché quotidiennement par le Scheduler
- Lance le traitement de tous les leads éligibles

#### Handlers

**SendLeadNurturingEmailMessageHandler** (`src/MessageHandler/SendLeadNurturingEmailMessageHandler.php`)
- Traite l'envoi d'un email de nurturing
- Marque le lead comme envoyé
- Gère les erreurs avec logs

**ProcessNurturingEmailsMessageHandler** (`src/MessageHandler/ProcessNurturingEmailsMessageHandler.php`)
- Identifie les leads éligibles pour chaque jour (J+1, J+3, J+7)
- Dispatche les messages individuels via Messenger
- Logs détaillés des statistiques d'envoi

#### Commande CLI

**SendNurturingEmailsCommand** (`src/Command/SendNurturingEmailsCommand.php`)
```bash
# Exécution manuelle
docker compose exec app php bin/console app:send-nurturing-emails

# Dry run (test sans envoi)
docker compose exec app php bin/console app:send-nurturing-emails --dry-run

# Traiter uniquement un jour spécifique
docker compose exec app php bin/console app:send-nurturing-emails --day=1
```

#### Scheduler

**LeadMagnetScheduleProvider** (`src/Scheduler/LeadMagnetScheduleProvider.php`)
- Exécution quotidienne à 9h du matin
- Cron : `0 9 * * *`
- Dispatche le message `ProcessNurturingEmailsMessage`

Pour démarrer le scheduler :
```bash
docker compose exec app php bin/console messenger:consume scheduler_default -vv
```

### 4. Dashboard CRM Admin

#### Controller

**CrmLeadController** (`src/Controller/Admin/CrmLeadController.php`)

Routes disponibles :
- `GET /admin/crm/statistics` : Vue d'ensemble des statistiques
- `GET /admin/crm/leads` : Liste paginée avec filtres
- `GET /admin/crm/leads/{id}` : Détail d'un lead avec timeline
- `POST /admin/crm/leads/{id}/status` : Changer le statut
- `POST /admin/crm/leads/{id}/notes` : Ajouter des notes internes
- `GET /admin/crm/leads/export/csv` : Export CSV

#### Pages

**Liste des leads** (`templates/admin/crm/leads/index.html.twig`)
- Pagination (50 par page)
- Filtres : statut, source, consentement, téléchargement, recherche
- Export CSV avec filtres appliqués
- Statistiques rapides en haut de page

**Détail d'un lead** (`templates/admin/crm/leads/show.html.twig`)
- Informations complètes du lead
- Timeline visuelle des événements
- Statut des emails de nurturing (envoyés ou à envoyer)
- Formulaire de changement de statut
- Zone de notes internes
- Actions rapides (email, retour liste)

**Statistiques CRM** (`templates/admin/crm/statistics.html.twig`)
- 4 KPIs principaux : Total leads, Taux consentement, Taux téléchargement, Téléchargements moyens
- Distribution par source avec taux de conversion
- Distribution par statut
- Leads récents (7 derniers jours)

#### Export CSV

Colonnes exportées :
- ID, Prénom, Nom, Email, Entreprise, Téléphone
- Source, Statut, Consentement Marketing
- Téléchargé, Nombre Téléchargements, Date Téléchargement
- Email J+1, Email J+3, Email J+7
- Date Création, Jours depuis création

Format : CSV avec séparateur `;`, encodage UTF-8 avec BOM (compatible Excel)

### 5. Menu de navigation

Nouveau menu dans la sidebar (`templates/layouts/_sidebar.html.twig`) :
- Section "CRM HotOnes"
- Accessible uniquement aux ROLE_ADMIN
- 2 entrées :
  - Statistiques
  - Tous les leads

## Configuration

### Variables d'environnement

Voir `docs/lead-magnet-email-config.md` pour la configuration SMTP complète.

Minimum requis :
```bash
MAILER_DSN=smtp://mailpit:1025  # Dev
# ou
MAILER_DSN=smtp://user:pass@smtp.example.com:587  # Prod

MAIL_FROM_ADDRESS=noreply@hotones.io
MAIL_FROM_NAME=HotOnes
```

### PDF du guide

Le PDF doit être placé à :
```
public/downloads/guide-kpis-agences-web.pdf
```

Voir `public/downloads/README.md` pour :
- Le contenu détaillé du guide (25 pages)
- Les instructions pour créer un PDF placeholder
- Les outils recommandés

## Utilisation

### Workflow complet

1. **Lead arrive sur la landing page**
   - URL : `/ressources/guide-kpis?source=homepage`
   - Remplit le formulaire avec consentement RGPD

2. **Soumission du formulaire**
   - Lead enregistré en base avec statut "new"
   - Email immédiat envoyé avec lien de téléchargement
   - Redirection vers page de remerciement

3. **Téléchargement du guide**
   - Click sur le lien dans l'email
   - Compteur de téléchargements incrémenté
   - Date de téléchargement enregistrée

4. **Nurturing automatique** (si consentement marketing)
   - J+1 : Email avec conseils pour démarrer
   - J+3 : Email avec cas pratique d'agence
   - J+7 : Email avec proposition d'essai HotOnes
   - Statut passe automatiquement à "nurturing"

5. **Gestion dans le CRM**
   - Admins consultent les statistiques
   - Filtrent et exportent les leads
   - Changent le statut (qualified, converted, lost)
   - Ajoutent des notes internes

### Statuts des leads

- **new** : Lead vient d'être capturé, aucun email de nurturing envoyé
- **nurturing** : Au moins un email de nurturing envoyé
- **qualified** : Lead qualifié manuellement par l'équipe
- **converted** : Lead converti en client (manuel)
- **lost** : Lead perdu (manuel)

⚠️ Les leads avec statut "converted" ou "lost" ne reçoivent plus d'emails de nurturing automatiques.

### Logique d'envoi nurturing

Un lead reçoit un email si :
- Il a donné son consentement marketing (`marketingConsent = true`)
- L'email n'a pas déjà été envoyé (`nurturingDayXSentAt IS NULL`)
- Le nombre de jours depuis la création est atteint (`getDaysSinceCreation() >= X`)
- Le statut n'est ni "converted" ni "lost"

## Tests

### Tester la capture de lead

```bash
# 1. Démarrer l'environnement
docker compose up -d

# 2. Accéder à la landing page
http://localhost:8080/ressources/guide-kpis

# 3. Remplir le formulaire et soumettre

# 4. Vérifier l'email dans Mailpit
http://localhost:8025
```

### Tester l'envoi manuel de nurturing

```bash
# Dry run pour voir les emails qui seraient envoyés
docker compose exec app php bin/console app:send-nurturing-emails --dry-run

# Envoyer réellement les emails
docker compose exec app php bin/console app:send-nurturing-emails

# Traiter uniquement les emails J+1
docker compose exec app php bin/console app:send-nurturing-emails --day=1
```

### Tester le Scheduler

```bash
# Voir les tâches planifiées
docker compose exec app php bin/console debug:scheduler

# Démarrer le worker scheduler
docker compose exec app php bin/console messenger:consume scheduler_default -vv
```

### Tester l'export CSV

```bash
# Accéder au CRM
http://localhost:8080/admin/crm/leads

# Cliquer sur "Export CSV"
# Le fichier sera téléchargé : leads-hotones-YYYY-MM-DD.csv
```

## Métriques & KPIs

### Dans le CRM

Le repository `LeadCaptureRepository::getStats()` retourne :
```php
[
    'total' => 150,                    // Total de leads
    'with_marketing_consent' => 120,   // Avec consentement marketing
    'downloaded' => 100,               // Ont téléchargé le guide
    'consent_rate' => 80.0,            // Taux de consentement (%)
    'download_rate' => 66.67,          // Taux de téléchargement (%)
    'avg_downloads' => 1.2             // Moyenne de téléchargements par lead
]
```

### Taux de conversion par source

Le CRM calcule automatiquement le taux de conversion pour chaque source :
```
Conversion Rate = (Leads convertis / Total leads) × 100
```

### Statistiques accessibles

- Distribution par source
- Distribution par statut
- Leads récents (7 derniers jours)
- Conversion par source

## Sécurité & RGPD

### Conformité RGPD

✅ Double consentement :
- Consentement RGPD obligatoire (checkbox validée)
- Consentement marketing optionnel

✅ Données collectées minimales :
- Seulement ce qui est nécessaire pour le service

✅ Droit à l'oubli :
- Notes internes pour tracer les demandes
- Possibilité de changer le statut en "lost"

✅ Transparence :
- Email de confirmation immédiat
- Lien de téléchargement clair

### Sécurité

- Routes CRM protégées par `#[IsGranted('ROLE_ADMIN')]`
- Validation CSRF sur tous les formulaires
- Emails validés côté formulaire
- Pas d'exposition de données sensibles

## Prochaines améliorations possibles

1. **Segmentation avancée**
   - Tags personnalisés pour les leads
   - Filtres par tags
   - Campagnes ciblées

2. **A/B Testing**
   - Tester différentes versions de la landing page
   - Tester différents timings de nurturing

3. **Intégrations**
   - Zapier / Make pour connecter à d'autres outils
   - Webhook pour notifier d'autres systèmes

4. **Analytics avancés**
   - Funnel de conversion visuel
   - Attribution multi-touch
   - Prédiction de conversion (ML)

5. **Emails avancés**
   - Templates personnalisables
   - A/B testing des emails
   - Tracking d'ouverture et de clics

6. **Lead Scoring**
   - Score automatique basé sur le comportement
   - Qualification automatique

## Fichiers créés/modifiés

### Entités
- `src/Entity/LeadCapture.php` (modifié - ajout champs nurturing + status)
- `migrations/Version20251219185551.php` (créé)

### Forms
- `src/Form/LeadCaptureType.php` (existant)

### Controllers
- `src/Controller/LeadMagnetController.php` (modifié - téléchargement PDF)
- `src/Controller/Admin/CrmLeadController.php` (créé)

### Services
- `src/Service/LeadMagnetMailer.php` (existant)

### Messages & Handlers
- `src/Message/SendLeadNurturingEmailMessage.php` (créé)
- `src/Message/ProcessNurturingEmailsMessage.php` (créé)
- `src/MessageHandler/SendLeadNurturingEmailMessageHandler.php` (créé)
- `src/MessageHandler/ProcessNurturingEmailsMessageHandler.php` (créé)

### Commands
- `src/Command/SendNurturingEmailsCommand.php` (créé)

### Scheduler
- `src/Scheduler/LeadMagnetScheduleProvider.php` (créé)

### Templates
- `templates/admin/crm/leads/index.html.twig` (créé)
- `templates/admin/crm/leads/show.html.twig` (créé)
- `templates/admin/crm/statistics.html.twig` (créé)
- `templates/layouts/_sidebar.html.twig` (modifié - ajout menu CRM)

### Documentation
- `docs/lead-magnet-email-config.md` (créé)
- `docs/lead-magnet-crm.md` (ce fichier - créé)
- `public/downloads/README.md` (modifié - ajout instructions PDF)

## Support

Pour toute question ou problème :
1. Consulter `docs/lead-magnet-email-config.md` pour les emails
2. Vérifier les logs : `docker compose exec app tail -f var/log/dev.log`
3. Tester en dry-run d'abord : `--dry-run`
