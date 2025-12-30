# 🔒 Étude de Faisabilité : Conformité RGPD

> **Date** : 17 décembre 2025
> **Statut** : Étude préliminaire
> **Priorité** : Haute (obligation légale européenne)

---

## 📋 Sommaire

1. [Cadre Légal et Principes du RGPD](#1-cadre-légal-et-principes-du-rgpd)
2. [Analyse des Données Personnelles](#2-analyse-des-données-personnelles)
3. [Obligations Légales](#3-obligations-légales)
4. [Droits des Personnes](#4-droits-des-personnes)
5. [Mesures Techniques et Organisationnelles](#5-mesures-techniques-et-organisationnelles)
6. [Architecture Technique](#6-architecture-technique)
7. [Coûts et ROI](#7-coûts-et-roi)
8. [Planning de Mise en Œuvre](#8-planning-de-mise-en-œuvre)
9. [Synthèse et Recommandations](#9-synthèse-et-recommandations)

---

## 1. Cadre Légal et Principes du RGPD

### 1.1 Qu'est-ce que le RGPD ?

Le **Règlement Général sur la Protection des Données** (RGPD) est le règlement européen n°2016/679 entré en vigueur le **25 mai 2018**.

**Objectif** : Protéger les données personnelles des citoyens européens et harmoniser les réglementations nationales.

**Application** : Toute organisation qui traite des données personnelles de résidents de l'UE, quelle que soit sa localisation.

**Sanctions** : Jusqu'à **20 millions d'euros** ou **4% du chiffre d'affaires annuel mondial** (le montant le plus élevé).

---

### 1.2 Principes Fondamentaux

Le RGPD repose sur 6 principes clés :

| Principe | Description | Application dans HotOnes |
|----------|-------------|--------------------------|
| **Licéité, loyauté, transparence** | Traitement légal, équitable et transparent | Consentement explicite, politique de confidentialité claire |
| **Limitation des finalités** | Données collectées pour des finalités déterminées, explicites et légitimes | Collecte uniquement des données nécessaires à la gestion RH et projet |
| **Minimisation des données** | Données adéquates, pertinentes et limitées au strict nécessaire | Pas de sur-collecte (ex: pas de religion, orientation politique) |
| **Exactitude** | Données exactes et mises à jour | Possibilité pour les utilisateurs de modifier leurs données |
| **Limitation de la conservation** | Conservation limitée dans le temps | Suppression automatique après la fin de la relation contractuelle + délais légaux |
| **Intégrité et confidentialité** | Sécurité appropriée des données | Chiffrement, contrôle d'accès, journalisation |

---

### 1.3 Base Légale du Traitement

Pour HotOnes, les bases légales du traitement sont :

| Base légale | Exemples de traitements |
|-------------|-------------------------|
| **Exécution d'un contrat** (Art. 6.1.b) | Gestion des contrats de travail, paie, planning |
| **Obligation légale** (Art. 6.1.c) | Conservation des données RH (code du travail), comptabilité |
| **Intérêts légitimes** (Art. 6.1.f) | Suivi de la performance, analytics internes, sécurité du système |
| **Consentement** (Art. 6.1.a) | Cookies non essentiels, newsletters (si implémenté) |

---

## 2. Analyse des Données Personnelles

### 2.1 Données Traitées par HotOnes

#### Catégories de personnes concernées

1. **Utilisateurs / Contributeurs** (employés de l'agence)
2. **Contacts clients** (personnes physiques des entreprises clientes)
3. **Candidats** (si module de recrutement implémenté - Lot 19)

#### Données personnelles identifiées

| Catégorie | Données collectées | Entité(s) Doctrine | Finalité | Base légale |
|-----------|-------------------|-------------------|----------|-------------|
| **Identité** | Nom, prénom, photo (avatar) | `User`, `Contributor` | Authentification, gestion RH | Contrat de travail |
| **Coordonnées** | Email professionnel/personnel, téléphones | `User`, `Contributor` | Communication, contact | Contrat de travail |
| **Vie professionnelle** | Poste, profil métier, périodes d'emploi, salaire (CJM) | `Contributor`, `EmploymentPeriod`, `JobProfile` | Gestion RH, paie, rentabilité | Contrat + Obligation légale |
| **Données de connexion** | Email, mot de passe haché, IP, user-agent, sessions | `User`, logs Symfony | Authentification, sécurité | Intérêts légitimes (sécurité) |
| **Données de localisation** | Adresse personnelle (optionnelle) | `Contributor` | Gestion administrative | Contrat de travail |
| **Activité professionnelle** | Temps saisis, projets, tâches, plannings | `Timesheet`, `Project`, `ProjectTask`, `Planning` | Facturation, rentabilité, gestion de projet | Contrat + Intérêts légitimes |
| **Performance** | Métriques (CA généré, taux d'occupation, TACE) | `FactProjectMetrics`, `FactStaffingMetrics` | Pilotage RH, analytics | Intérêts légitimes |
| **Clients (B2B)** | Nom, prénom, email, téléphone du contact | `Client` (relation User) | Gestion commerciale | Intérêts légitimes (relation client B2B) |
| **Candidats** (futur) | CV, lettre de motivation, coordonnées, prétentions salariales | `Candidate` (Lot 19) | Recrutement | Consentement + Contrat (pré-contractuel) |

#### Données sensibles (Art. 9 RGPD)

**Constat** : HotOnes ne collecte **pas** de données sensibles au sens de l'article 9 RGPD (origine raciale/ethnique, opinions politiques/religieuses, santé, orientation sexuelle, données biométriques, données génétiques).

**Recommandation** : Ne **jamais** collecter de telles données, sauf obligation légale spécifique (ex: reconnaissance de travailleur handicapé - RQTH - avec consentement explicite).

---

### 2.2 Flux de Données

#### Transferts hors UE

**Question** : HotOnes transfère-t-il des données hors de l'Union Européenne ?

**Analyse** :
- **Hébergement** : Vérifier où sont hébergées les données (serveurs en France/UE ou hors UE)
- **Services tiers** : Vérifier les prestataires (Yousign, Chorus Pro, email, analytics)

**Exemples de services à auditer** :
- **Yousign** : 🇫🇷 France (UE) - Conforme
- **Chorus Pro** : 🇫🇷 France (UE) - Conforme
- **Symfony Mailer** : Dépend du fournisseur SMTP (ex: Mailgun, SendGrid, AWS SES)
- **Sentry** (si utilisé) : 🇺🇸 USA - Nécessite clauses contractuelles types (CCT)
- **Google Analytics** (si utilisé) : 🇺🇸 USA - **Non conforme depuis l'arrêt Schrems II** (utiliser alternative UE comme Matomo)

**Recommandation** : Privilégier des services hébergés en UE. Pour les services hors UE, vérifier :
- Clauses Contractuelles Types (CCT) approuvées par la Commission européenne
- Décision d'adéquation (ex: UK post-Brexit)
- Garanties appropriées (Privacy Shield invalidé en 2020)

---

## 3. Obligations Légales

### 3.1 Registre des Activités de Traitement (Art. 30)

**Obligation** : Tenir un registre de toutes les activités de traitement de données personnelles.

**Contenu du registre** :
- Nom et coordonnées du responsable de traitement (entreprise)
- Finalités du traitement
- Catégories de personnes concernées
- Catégories de données personnelles
- Catégories de destinataires (internes, sous-traitants, tiers)
- Transferts hors UE (le cas échéant)
- Durées de conservation
- Mesures de sécurité techniques et organisationnelles

**Exemple pour HotOnes** :

| Traitement | Finalité | Données | Personnes | Durée de conservation | Base légale |
|------------|----------|---------|-----------|----------------------|-------------|
| Gestion des comptes utilisateurs | Authentification et gestion des accès | Email, mot de passe, nom, prénom, rôles | Contributeurs | Durée du contrat + 5 ans (archivage) | Contrat de travail |
| Gestion RH | Administration du personnel, paie | Données d'identité, coordonnées, salaire, périodes d'emploi | Contributeurs | Durée du contrat + 5 ans (obligations comptables et sociales) | Contrat + Obligation légale |
| Suivi de la performance | Analytics RH, KPIs | Temps saisis, projets, métriques (CA, taux d'occupation) | Contributeurs | Durée du contrat + 3 ans (historique) | Intérêts légitimes |
| Gestion commerciale | Relation client, devis, facturation | Nom, prénom, email, téléphone du contact client | Contacts clients (B2B) | Durée de la relation commerciale + 3 ans (prescription) | Intérêts légitimes |
| Logs de sécurité | Sécurité du système, détection des intrusions | IP, user-agent, actions, timestamps | Tous utilisateurs | 6 mois (recommandation CNIL) | Intérêts légitimes (sécurité) |

**Mise en œuvre technique** :
- Entité `ProcessingActivity` (activité de traitement)
- Interface admin pour gérer le registre
- Export PDF/Excel du registre pour audit

---

### 3.2 Politique de Confidentialité (Art. 13-14)

**Obligation** : Informer les personnes concernées de manière claire et transparente.

**Contenu minimum** :
- Identité du responsable de traitement
- Coordonnées du DPO (Délégué à la Protection des Données) ou contact RGPD
- Finalités et base légale de chaque traitement
- Destinataires des données
- Durées de conservation
- Droits des personnes (accès, rectification, effacement, etc.)
- Droit d'introduire une réclamation auprès de la CNIL

**Mise en œuvre** :
- Page `/privacy-policy` accessible depuis le footer
- Lien dans le formulaire d'inscription / d'onboarding
- Acceptation lors de la première connexion (checkbox)
- Versionning de la politique (notification en cas de mise à jour)

---

### 3.3 Analyse d'Impact (PIA - Privacy Impact Assessment) (Art. 35)

**Obligation** : Réaliser une **Analyse d'Impact relative à la Protection des Données** (AIPD ou PIA en anglais) si le traitement présente un **risque élevé** pour les droits et libertés des personnes.

**Cas nécessitant un PIA** :
- Évaluation/notation systématique (ex: scoring de performance avec conséquences significatives)
- Traitement à grande échelle de données sensibles
- Surveillance systématique à grande échelle
- Profilage automatisé avec décisions produisant des effets juridiques

**Pour HotOnes** :
- **Non obligatoire** actuellement (pas de profilage automatisé ni de surveillance systématique)
- **Optionnel** : Peut être réalisé en bonne pratique pour le suivi de la performance (métriques RH)

**Recommandation** : Réaliser un PIA light si implémentation future de :
- Algorithmes de recommandation de staffing automatisés
- Système de notation/évaluation automatique des contributeurs

---

### 3.4 DPO (Délégué à la Protection des Données) (Art. 37-39)

**Obligation** : Désigner un DPO si :
- L'organisme est une autorité publique (non applicable)
- Les activités de base nécessitent un suivi régulier et systématique à grande échelle
- Les activités de base portent sur le traitement à grande échelle de données sensibles

**Pour HotOnes (PME/agence web)** :
- **Non obligatoire** (pas de traitement à grande échelle, pas de données sensibles)
- **Optionnel** : Peut désigner un **correspondant RGPD** interne ou externe (avocat, consultant)

**Recommandation** :
- Désigner un **référent RGPD** interne (ex: manager, dirigeant, ou RH)
- Rôle : Veille réglementaire, gestion des demandes d'exercice de droits, mise à jour du registre
- Email de contact : `rgpd@hotones.fr` ou `privacy@hotones.fr`

---

### 3.5 Notification de Violations de Données (Art. 33-34)

**Obligation** : En cas de violation de données personnelles (breach) :
- **Notification à la CNIL** sous **72 heures** (si risque pour les personnes)
- **Notification aux personnes concernées** (si risque élevé)

**Exemples de violations** :
- Accès non autorisé à la base de données
- Vol de backup contenant des données personnelles
- Ransomware chiffrant les données
- Fuite de données (ex: base de données exposée publiquement)

**Mise en œuvre technique** :
- Système de détection des incidents (monitoring, alertes)
- Procédure de gestion des violations (qui contacter, quoi faire)
- Documentation des violations (entité `DataBreach` pour traçabilité)
- Tests annuels de la procédure (exercice de simulation)

---

## 4. Droits des Personnes

### 4.1 Les 8 Droits Fondamentaux

| Droit | Article | Description | Mise en œuvre HotOnes |
|-------|---------|-------------|----------------------|
| **Droit d'accès** | Art. 15 | Obtenir une copie de ses données personnelles | Bouton "Télécharger mes données" (export JSON/PDF) |
| **Droit de rectification** | Art. 16 | Corriger des données inexactes | Page "Mon compte" (modification des données personnelles) |
| **Droit à l'effacement** ("droit à l'oubli") | Art. 17 | Supprimer les données (sous conditions) | Bouton "Supprimer mon compte" avec anonymisation |
| **Droit à la limitation** | Art. 18 | Limiter le traitement (gel des données) | Statut `User.dataProcessingLimited` (blocage temporaire) |
| **Droit à la portabilité** | Art. 20 | Recevoir ses données dans un format structuré | Export JSON/CSV/XML des données utilisateur |
| **Droit d'opposition** | Art. 21 | S'opposer au traitement (marketing, profilage) | Opt-out analytics, désinscription newsletter |
| **Droit de ne pas faire l'objet d'une décision automatisée** | Art. 22 | Intervention humaine dans les décisions automatisées | Non applicable (pas de décisions automatisées) |
| **Droit de définir des directives post-mortem** | Art. 40-3 du code civil (France) | Directives sur le devenir des données après décès | Formulaire optionnel "Directives post-mortem" |

---

### 4.2 Procédure d'Exercice des Droits

#### Canaux de demande
- **Email** : `rgpd@hotones.fr` ou `privacy@hotones.fr`
- **Formulaire web** : `/privacy/request` (formulaire dédié)
- **Courrier postal** : Adresse du siège social

#### Délais de réponse
- **1 mois** pour répondre (extensible à 3 mois si complexité, avec justification)
- Gratuit (sauf demandes manifestement infondées ou excessives)

#### Vérification de l'identité
- Pour les demandes sensibles (effacement, portabilité), demander une **pièce d'identité**
- Éviter l'usurpation d'identité

#### Workflow technique

```
1. Réception de la demande (email ou formulaire)
2. Enregistrement dans l'entité `PrivacyRequest`
3. Vérification de l'identité
4. Traitement de la demande (selon le droit invoqué)
5. Réponse à la personne (email + copie des données ou confirmation)
6. Archivage de la demande (conservation 3 ans pour preuve de conformité)
```

---

## 5. Mesures Techniques et Organisationnelles

### 5.1 Sécurité des Données (Art. 32)

#### Mesures déjà en place (bonnes pratiques)

✅ **Contrôle d'accès** :
- Authentification par email/mot de passe
- 2FA (TOTP) optionnel
- Hiérarchie de rôles (ROLE_INTERVENANT → ROLE_SUPERADMIN)
- Attribut `#[IsGranted()]` sur les contrôleurs

✅ **Chiffrement** :
- Mots de passe hachés (bcrypt via Symfony Security)
- HTTPS (TLS 1.2+) pour les communications
- Tokens JWT signés pour l'API

✅ **Journalisation** :
- Logs Symfony (app.log, security.log)
- Audit trail (à améliorer)

✅ **Sauvegarde** :
- Backup réguliers de la base de données (via Docker ou scripts)

#### Mesures à renforcer

🔲 **Chiffrement des données au repos** :
- Chiffrer les colonnes sensibles (salaire, données bancaires si ajoutées à l'avenir)
- Utiliser `defuse/php-encryption` ou `sodium` (PHP 7.2+)

🔲 **Anonymisation / Pseudonymisation** :
- Anonymiser les données des contributeurs partis depuis > 5 ans
- Pseudonymiser les logs (remplacer les IPs par des hash)

🔲 **Limitation de la rétention** :
- Purge automatique des logs > 6 mois
- Suppression des comptes inactifs > 3 ans (après relance)

🔲 **Audit trail complet** :
- Journaliser toutes les actions sensibles (modification de données RH, accès aux données sensibles)
- Entité `AuditLog` : qui, quoi, quand, IP, user-agent

🔲 **Tests de sécurité** :
- Pentests annuels (ou bug bounty)
- Scan de vulnérabilités (OWASP Top 10, injection SQL, XSS, CSRF)

---

### 5.2 Privacy by Design et Privacy by Default

**Privacy by Design** : Intégrer la protection des données dès la conception des fonctionnalités.

**Privacy by Default** : Paramètres de confidentialité les plus protecteurs par défaut.

#### Exemples d'application

| Principe | Mise en œuvre |
|----------|---------------|
| **Minimisation** | Ne collecter que les données strictement nécessaires (pas de champs facultatifs par défaut) |
| **Opt-in par défaut** | Analytics désactivé par défaut (activation manuelle par l'utilisateur) |
| **Visibilité limitée** | Un contributeur ne voit que ses propres données (sauf managers) |
| **Durée de conservation minimale** | Suppression automatique des données après la fin de la relation contractuelle + délais légaux |
| **Chiffrement** | Chiffrer les sauvegardes, les exports de données |

---

### 5.3 Durées de Conservation

| Données | Durée de conservation | Base légale / Justification |
|---------|----------------------|------------------------------|
| **Comptes utilisateurs actifs** | Durée du contrat de travail | Exécution du contrat |
| **Données RH (après départ)** | 5 ans | Obligations comptables et sociales (URSSAF, retraite) |
| **Factures et données comptables** | 10 ans | Obligation légale (code de commerce) |
| **Logs de sécurité** | 6 mois | Recommandation CNIL |
| **Logs applicatifs** | 1 an | Intérêts légitimes (debugging) |
| **Timesheets et projets** | Durée du contrat + 3 ans | Intérêts légitimes (historique, litiges) |
| **Données de candidats (non retenus)** | 2 ans | Consentement (recontact possible) |
| **Cookies non essentiels** | 13 mois maximum | Recommandation CNIL |

**Mise en œuvre** :
- Commande Symfony `app:gdpr:purge` (automatique via cron)
- Soft delete vs hard delete (selon les cas)
- Anonymisation plutôt que suppression pour conserver les statistiques agrégées

---

## 6. Architecture Technique

### 6.1 Entités Doctrine à Créer

#### `ProcessingActivity` (Registre des traitements)

```php
// src/Entity/Gdpr/ProcessingActivity.php
class ProcessingActivity
{
    private ?int $id = null;
    private string $name; // Ex: "Gestion des comptes utilisateurs"
    private string $purpose; // Finalité
    private array $legalBasis; // JSON: ['contract', 'legal_obligation']
    private array $dataCategories; // JSON: ['identity', 'contact', 'professional_life']
    private array $personCategories; // JSON: ['employees', 'clients']
    private array $recipients; // JSON: ['internal_hr', 'accounting_software']
    private ?string $retentionPeriod = null; // Ex: "5 ans après départ"
    private array $securityMeasures; // JSON: ['encryption', 'access_control', '2fa']
    private bool $internationalTransfer = false;
    private ?string $transferCountries = null; // Ex: "USA (CCT)"
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
}
```

#### `PrivacyRequest` (Demandes d'exercice de droits)

```php
// src/Entity/Gdpr/PrivacyRequest.php
class PrivacyRequest
{
    private ?int $id = null;
    private User $user; // Personne concernée
    private string $type; // 'access', 'rectification', 'erasure', 'portability', 'limitation', 'opposition'
    private string $status; // 'pending', 'in_progress', 'completed', 'rejected'
    private ?string $requestDetails = null; // Détails de la demande
    private ?string $responseDetails = null; // Réponse fournie
    private ?string $identityProof = null; // Chemin vers la pièce d'identité (si fournie)
    private \DateTimeImmutable $requestedAt;
    private ?\DateTimeImmutable $respondedAt = null;
    private ?User $processedBy = null; // Référent RGPD qui a traité
}
```

#### `DataBreach` (Violations de données)

```php
// src/Entity/Gdpr/DataBreach.php
class DataBreach
{
    private ?int $id = null;
    private string $title; // Ex: "Accès non autorisé à la base de données"
    private string $description;
    private \DateTimeImmutable $detectedAt;
    private \DateTimeImmutable $occurredAt;
    private string $severity; // 'low', 'medium', 'high', 'critical'
    private array $affectedDataCategories; // JSON: ['identity', 'contact']
    private int $affectedPersonsCount;
    private bool $notifiedCnil = false;
    private ?\DateTimeImmutable $cnilNotificationAt = null;
    private bool $notifiedPersons = false;
    private ?string $remediationActions = null; // Mesures correctives
    private string $status; // 'detected', 'under_investigation', 'resolved', 'closed'
}
```

#### `AuditLog` (Journalisation des actions sensibles)

```php
// src/Entity/Gdpr/AuditLog.php
class AuditLog
{
    private ?int $id = null;
    private ?User $user = null; // Utilisateur qui a effectué l'action (nullable si système)
    private string $action; // Ex: 'user.created', 'user.deleted', 'data.exported', 'privacy_request.completed'
    private string $entityType; // Ex: 'User', 'Contributor', 'PrivacyRequest'
    private ?int $entityId = null;
    private array $changes; // JSON: avant/après (pour les modifications)
    private string $ipAddress;
    private string $userAgent;
    private \DateTimeImmutable $occurredAt;
}
```

#### `ConsentRecord` (Consentements)

```php
// src/Entity/Gdpr/ConsentRecord.php
class ConsentRecord
{
    private ?int $id = null;
    private User $user;
    private string $purpose; // Ex: 'analytics', 'newsletter', 'cookies_marketing'
    private bool $consented;
    private \DateTimeImmutable $consentedAt;
    private ?string $ipAddress = null;
    private ?string $proofText = null; // Texte présenté lors du consentement
    private ?\DateTimeImmutable $withdrawnAt = null; // Retrait du consentement
}
```

---

### 6.2 Services Symfony

#### `GdprService` (service principal)

```php
// src/Service/Gdpr/GdprService.php
class GdprService
{
    public function exportUserData(User $user): array; // Export JSON des données
    public function anonymizeUser(User $user): void; // Anonymisation
    public function deleteUser(User $user): void; // Suppression (hard delete)
    public function limitDataProcessing(User $user, bool $limited): void; // Limitation
}
```

#### `PrivacyRequestService`

```php
// src/Service/Gdpr/PrivacyRequestService.php
class PrivacyRequestService
{
    public function createRequest(User $user, string $type, string $details): PrivacyRequest;
    public function processRequest(PrivacyRequest $request, User $processedBy): void;
    public function sendResponse(PrivacyRequest $request): void; // Email de réponse
}
```

#### `AuditLogService`

```php
// src/Service/Gdpr/AuditLogService.php
class AuditLogService
{
    public function log(string $action, string $entityType, ?int $entityId, array $changes = []): void;
}
```

#### `DataRetentionService`

```php
// src/Service/Gdpr/DataRetentionService.php
class DataRetentionService
{
    public function purgeOldLogs(): void; // Suppression logs > 6 mois
    public function anonymizeInactiveUsers(): void; // Anonymisation comptes inactifs > 3 ans
    public function purgeOldTimesheets(): void; // Suppression timesheets > 5 ans
}
```

---

### 6.3 Commandes CLI

```bash
# Purge automatique (à exécuter quotidiennement via cron)
php bin/console app:gdpr:purge

# Export des données d'un utilisateur
php bin/console app:gdpr:export-user <user-id>

# Anonymisation d'un utilisateur
php bin/console app:gdpr:anonymize-user <user-id>

# Génération du registre des traitements (PDF)
php bin/console app:gdpr:generate-register
```

---

### 6.4 Interface Utilisateur

#### Page "Confidentialité et Données" (`/privacy`)

**Sections** :
1. **Politique de confidentialité** : Texte légal (finalités, droits, durées de conservation)
2. **Mes données** : Export JSON/PDF de toutes les données personnelles
3. **Mes consentements** : Gestion des consentements (analytics, cookies, newsletter)
4. **Exercer mes droits** : Formulaire de demande (accès, rectification, effacement, portabilité, limitation, opposition)
5. **Mes demandes** : Historique des demandes d'exercice de droits et leur statut

#### Interface Admin RGPD (`/admin/gdpr`)

**Sections** :
1. **Registre des traitements** : Liste et gestion des activités de traitement
2. **Demandes de droits** : Liste des demandes en attente, en cours, traitées
3. **Violations de données** : Déclaration et suivi des violations
4. **Audit trail** : Consultation des logs d'audit (actions sensibles)
5. **Statistiques** : Nombre de demandes par type, délai moyen de réponse, violations

---

### 6.5 Cookies et Consentement

#### Conformité cookies (directive ePrivacy)

**Cookies utilisés par HotOnes** :

| Cookie | Type | Finalité | Durée | Consentement requis ? |
|--------|------|----------|-------|----------------------|
| `PHPSESSID` | Essentiel | Session utilisateur | Session | ❌ Non (strictement nécessaire) |
| `_csrf_token` | Essentiel | Protection CSRF | Session | ❌ Non (sécurité) |
| `remember_me` | Fonctionnel | Connexion persistante | 30 jours | ⚠️ Oui (fonctionnel non essentiel) |
| `_ga`, `_gid` (Google Analytics) | Analytique | Statistiques de visite | 2 ans / 24h | ✅ Oui (analytics) |

**Recommandation** : Remplacer Google Analytics par **Matomo** (auto-hébergé, conforme RGPD sans consentement si anonymisation IP).

**Mise en œuvre** :
- Bannière de consentement (cookies banner) via **Tarteaucitron.js** (français, open-source)
- Blocage des cookies non essentiels par défaut (opt-in)
- Enregistrement des consentements dans `ConsentRecord`

---

## 7. Coûts et ROI

### 7.1 Coûts de Mise en Conformité

#### Coûts humains (développement)

| Tâche | Estimation |
|-------|------------|
| **Analyse et audit RGPD** | 3 jours |
| **Rédaction du registre des traitements** | 2 jours |
| **Rédaction de la politique de confidentialité** | 1 jour |
| **Développement des entités et migrations** | 2 jours |
| **Développement des services RGPD** | 4 jours |
| **Interface utilisateur (export, consentements, demandes)** | 5 jours |
| **Interface admin (registre, demandes, violations, audit)** | 5 jours |
| **Commandes CLI (purge, export, anonymisation)** | 2 jours |
| **Bannière de consentement (cookies)** | 2 jours |
| **Audit trail (journalisation)** | 3 jours |
| **Tests (unitaires, fonctionnels, sécurité)** | 4 jours |
| **Documentation et formation** | 2 jours |

**Total estimé : 35-37 jours** de développement

#### Coûts externes (optionnels)

| Service | Coût indicatif | Fréquence |
|---------|----------------|-----------|
| **Audit RGPD par cabinet spécialisé** | 2 000 - 5 000€ | Ponctuel (recommandé tous les 2-3 ans) |
| **DPO externe (avocat, consultant)** | 1 000 - 3 000€/an | Annuel (optionnel pour PME) |
| **Pentest / Audit de sécurité** | 3 000 - 10 000€ | Annuel (recommandé) |
| **Assurance cyber-risques** | 500 - 2 000€/an | Annuel (optionnel) |
| **Formation RGPD pour les équipes** | 500 - 1 500€ | Ponctuel |

**Total optionnel : ~5 000 - 15 000€** (première année)

---

### 7.2 ROI et Bénéfices

#### Conformité légale
- ✅ Éviter les sanctions CNIL (jusqu'à 20M€ ou 4% du CA)
- ✅ Éviter les actions en justice de salariés/clients
- ✅ Conformité avec les appels d'offres (clause RGPD souvent obligatoire)

#### Confiance et image
- ✅ Renforcer la confiance des clients (transparence)
- ✅ Différenciation concurrentielle (peu d'agences sont réellement conformes)
- ✅ Marque employeur (respect de la vie privée des employés)

#### Sécurité
- ✅ Réduction des risques de fuites de données
- ✅ Meilleure gouvernance des données
- ✅ Résilience en cas de violation (procédures en place)

#### Efficacité opérationnelle
- ✅ Meilleure qualité des données (nettoyage régulier)
- ✅ Automatisation de la purge (réduction des volumes de stockage)
- ✅ Traçabilité des actions (audit trail pour debug et investigations)

---

## 8. Planning de Mise en Œuvre

### Phase 1 : Audit et Analyse (3 jours)
- Audit des données personnelles traitées
- Identification des flux de données et transferts hors UE
- Analyse des bases légales et durées de conservation
- Identification des risques et non-conformités

### Phase 2 : Documentation (3 jours)
- Rédaction du registre des activités de traitement
- Rédaction de la politique de confidentialité
- Rédaction des procédures internes (violations, demandes de droits)
- Désignation d'un référent RGPD interne

### Phase 3 : Développement Backend (11 jours)
- Création des entités (`ProcessingActivity`, `PrivacyRequest`, `DataBreach`, `AuditLog`, `ConsentRecord`)
- Migrations de base de données
- Développement des services (`GdprService`, `PrivacyRequestService`, `AuditLogService`, `DataRetentionService`)
- Commandes CLI (`app:gdpr:purge`, `app:gdpr:export-user`, etc.)
- Audit trail automatique (listeners Doctrine)

### Phase 4 : Interface Utilisateur (7 jours)
- Page `/privacy` (politique, export de données, consentements, exercice des droits)
- Formulaire de demande d'exercice de droits
- Bannière de consentement (cookies)
- Intégration Matomo (alternative à Google Analytics)

### Phase 5 : Interface Admin (5 jours)
- Page `/admin/gdpr` (registre, demandes, violations, audit)
- Gestion des activités de traitement
- Traitement des demandes d'exercice de droits
- Consultation de l'audit trail

### Phase 6 : Sécurité et Tests (6 jours)
- Renforcement de la sécurité (chiffrement, anonymisation)
- Tests unitaires (services RGPD)
- Tests fonctionnels (workflows)
- Tests de sécurité (accès, fuites de données)
- Tests de la procédure de violation (simulation)

### Phase 7 : Mise en Production (2 jours)
- Déploiement
- Configuration du cron pour `app:gdpr:purge` (quotidien)
- Formation de l'équipe (référent RGPD, managers)
- Communication aux utilisateurs (nouvelle politique de confidentialité)
- Tests en production

**Total estimé : 35-37 jours**

---

## 9. Synthèse et Recommandations

### 9.1 Priorité : HAUTE 🔴

**Pourquoi ?**
- **Obligation légale** : Le RGPD est en vigueur depuis 2018, toute entreprise traitant des données personnelles doit être conforme
- **Risques** : Sanctions CNIL jusqu'à 20M€ ou 4% du CA, actions en justice, perte de confiance
- **Opportunité** : Différenciation concurrentielle, conformité pour appels d'offres

---

### 9.2 Actions Immédiates (Q1 2026)

1. **Désigner un référent RGPD** interne (manager, dirigeant, ou RH)
2. **Créer l'email de contact** : `rgpd@hotones.fr` ou `privacy@hotones.fr`
3. **Rédiger la politique de confidentialité** (template CNIL disponible)
4. **Rédiger le registre des traitements** (template CNIL disponible)
5. **Mettre en place une bannière de consentement** (Tarteaucitron.js)

---

### 9.3 Actions Moyen Terme (Q2-Q3 2026)

1. **Développer les fonctionnalités techniques** (entités, services, interfaces)
2. **Implémenter l'audit trail** (journalisation des actions sensibles)
3. **Mettre en place les durées de conservation** (purge automatique)
4. **Anonymiser les données anciennes** (contributeurs partis depuis > 5 ans)
5. **Former les équipes** (sensibilisation RGPD, procédures)

---

### 9.4 Actions Long Terme (2027+)

1. **Audit RGPD externe** (tous les 2-3 ans)
2. **Pentest annuel** (tests de sécurité)
3. **Veille réglementaire** (évolutions du RGPD, jurisprudence CJUE, recommandations CNIL)
4. **Amélioration continue** (retours utilisateurs, nouvelles fonctionnalités)

---

### 9.5 Checklist de Conformité RGPD

#### Gouvernance
- [ ] Référent RGPD désigné
- [ ] Email de contact RGPD créé
- [ ] Registre des activités de traitement rédigé
- [ ] Politique de confidentialité rédigée et accessible
- [ ] Procédure de gestion des violations de données rédigée
- [ ] Procédure de gestion des demandes d'exercice de droits rédigée

#### Bases légales et finalités
- [ ] Base légale identifiée pour chaque traitement
- [ ] Finalités claires et explicites
- [ ] Pas de sur-collecte de données (minimisation)

#### Droits des personnes
- [ ] Droit d'accès implémenté (export des données)
- [ ] Droit de rectification implémenté (modification des données)
- [ ] Droit à l'effacement implémenté (suppression/anonymisation)
- [ ] Droit à la portabilité implémenté (export JSON/CSV/XML)
- [ ] Droit à la limitation implémenté (gel du compte)
- [ ] Droit d'opposition implémenté (opt-out analytics)
- [ ] Formulaire de demande accessible et fonctionnel

#### Sécurité
- [ ] Mots de passe hachés (bcrypt, argon2)
- [ ] HTTPS (TLS 1.2+) activé
- [ ] 2FA disponible
- [ ] Contrôle d'accès par rôles
- [ ] Chiffrement des données sensibles au repos
- [ ] Logs de sécurité conservés 6 mois
- [ ] Sauvegardes chiffrées
- [ ] Tests de sécurité réguliers (pentest, scan de vulnérabilités)

#### Durées de conservation
- [ ] Durées de conservation définies pour chaque traitement
- [ ] Purge automatique des données périmées
- [ ] Anonymisation des données anciennes

#### Transferts hors UE
- [ ] Inventaire des transferts hors UE
- [ ] Clauses Contractuelles Types (CCT) en place si nécessaire
- [ ] Pas d'utilisation de Google Analytics (remplacer par Matomo)

#### Consentement (cookies)
- [ ] Bannière de consentement implémentée
- [ ] Cookies non essentiels bloqués par défaut (opt-in)
- [ ] Enregistrement des consentements
- [ ] Possibilité de retirer le consentement

#### Sous-traitants
- [ ] Contrats de sous-traitance avec clauses RGPD (Yousign, Chorus Pro, hébergeur, email)
- [ ] Vérification de la conformité RGPD des sous-traitants

#### Audit et amélioration
- [ ] Audit RGPD réalisé (interne ou externe)
- [ ] Tests de la procédure de violation de données
- [ ] Formation des équipes à la RGPD
- [ ] Veille réglementaire active

---

## 📚 Ressources et Documentation

### Officielles

- **CNIL** (Commission Nationale de l'Informatique et des Libertés) :
  - Site : https://www.cnil.fr
  - Registre des traitements (modèle) : https://www.cnil.fr/fr/RGDP-le-registre-des-activites-de-traitement
  - Politique de confidentialité (modèle) : https://www.cnil.fr/fr/modele/rgpd/politique-confidentialite
  - Guide du développeur : https://www.cnil.fr/fr/guide-rgpd-du-developpeur

- **Règlement RGPD** :
  - Texte complet : https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32016R0679

- **EDPB** (European Data Protection Board) :
  - Guidelines : https://edpb.europa.eu/our-work-tools/general-guidance/guidelines-recommendations-best-practices_en

### Outils

- **Tarteaucitron.js** (bannière de consentement) :
  - Site : https://tarteaucitron.io
  - GitHub : https://github.com/AmauriC/tarteaucitron.js

- **Matomo** (analytics conforme RGPD) :
  - Site : https://matomo.org
  - Auto-hébergé, anonymisation IP, pas de transfert hors UE

- **Symfony GDPR Bundle** (communautaire) :
  - speicher210/gdpr-bundle : https://packagist.org/packages/speicher210/gdpr-bundle
  - artprima/gdpr-bundle : https://packagist.org/packages/artprima/gdpr-bundle

### Formations

- **MOOC CNIL** (gratuit) :
  - L'atelier RGPD : https://atelier-rgpd.cnil.fr

- **Formations certifiantes** :
  - DPO (Délégué à la Protection des Données) : 3-5 jours, 1 500 - 3 000€

---

## 📝 Conclusion

La mise en conformité RGPD est une **obligation légale incontournable** pour HotOnes. Au-delà de l'aspect réglementaire, c'est une opportunité de :

- ✅ Renforcer la **sécurité** des données
- ✅ Améliorer la **gouvernance** et la **traçabilité**
- ✅ Gagner la **confiance** des clients et des employés
- ✅ Se **différencier** de la concurrence
- ✅ Anticiper les **évolutions réglementaires** futures

**Budget total** : ~35-37 jours de développement + 5 000 - 15 000€ de coûts externes optionnels (audit, DPO, pentest)

**ROI** : Positif dès la première année (évitement des sanctions, conformité pour appels d'offres, amélioration de la sécurité)

**Prochaines étapes** :
1. ✅ Valider la roadmap RGPD avec la direction
2. ✅ Désigner un référent RGPD interne
3. ✅ Rédiger le registre des traitements et la politique de confidentialité
4. ✅ Démarrer le développement en Q1-Q2 2026

---

**Document rédigé le** : 17 décembre 2025
**Auteur** : Claude Code
**Version** : 1.0
**Prochaine revue** : Mars 2026 (après audit initial)
