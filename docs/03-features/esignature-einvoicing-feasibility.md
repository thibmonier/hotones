# 📝 Étude de Faisabilité : Signature Électronique & Facturation Électronique

> **Date** : 17 décembre 2025
> **Statut** : Étude préliminaire
> **Priorité** : Haute (obligation légale pour la facturation électronique)

---

## 📋 Sommaire

1. [Signature Électronique](#1-signature-électronique)
2. [Facturation Électronique](#2-facturation-électronique)
3. [Synthèse et Recommandations](#3-synthèse-et-recommandations)

---

## 1. Signature Électronique

### 1.1 Contexte et Cadre Légal

#### Réglementation européenne (eIDAS)
Le règlement européen **eIDAS** (electronic IDentification, Authentication and trust Services) établit trois niveaux de signature électronique :

- **Signature électronique simple** : Équivalent numérique d'une signature manuscrite (ex: case à cocher, scan de signature)
- **Signature électronique avancée** : Liée uniquement au signataire, permet de l'identifier et de détecter toute modification ultérieure
- **Signature électronique qualifiée** : Créée par un dispositif qualifié et basée sur un certificat qualifié (équivalence juridique totale avec la signature manuscrite)

#### Valeur juridique en France
- Les **signatures simples** sont acceptées pour les contrats commerciaux B2B (article 1366 du Code civil)
- Les **signatures avancées** offrent un niveau de preuve supérieur en cas de litige
- Les **signatures qualifiées** sont obligatoires pour certains actes (marchés publics, immobilier)

**Pour HotOnes** : Une signature électronique **simple ou avancée** est suffisante pour les devis et contrats commerciaux B2B.

---

### 1.2 Cas d'Usage dans HotOnes

#### Fonctionnalités à implémenter
1. **Signature de devis** (Order) :
   - Envoi du devis au client par email avec lien sécurisé
   - Interface de signature en ligne (client ne nécessite pas de compte)
   - Changement automatique du statut du devis (`a_signer` → `signe`)
   - Archivage du document signé (PDF avec preuve de signature)
   - Notification interne (commercial, chef de projet)

2. **Signature de contrats** (futurs) :
   - Contrats de prestation (TMA, support, maintenance)
   - Contrats de confidentialité (NDA)
   - Avenants

3. **Signature multi-parties** (optionnel) :
   - Signature côté client + signature côté agence (directeur)
   - Workflow d'approbation interne avant envoi au client

---

### 1.3 Solutions du Marché

#### Comparatif des principaux fournisseurs

| Fournisseur | Type de signature | Prix indicatif | API | Avantages | Inconvénients |
|-------------|-------------------|----------------|-----|-----------|---------------|
| **Yousign** 🇫🇷 | Avancée (conforme eIDAS) | 9-15€/mois + 1-2€/signature | REST, Webhooks | Français, RGPD, support FR, interface simple | Coût par signature |
| **DocuSign** 🇺🇸 | Avancée/Qualifiée | 25€/mois + volume | REST, Webhooks | Leader mondial, très complet | Cher, complexe, support US |
| **Universign** 🇫🇷 | Avancée/Qualifiée | Sur devis (>500€/mois) | REST, SOAP | Qualifié eIDAS, banques | Coût élevé, complexe |
| **Adobe Sign** 🇺🇸 | Avancée | 18€/mois + volume | REST | Intégration Adobe | Coût, orientation B2C |
| **Oodrive Sign** 🇫🇷 | Avancée | Sur devis | REST | Souveraineté, sécurité | Coût élevé |
| **HelloSign (Dropbox)** 🇺🇸 | Simple/Avancée | Gratuit (3/mois) puis 15€ | REST | Gratuit début, simple | Limité, US |

#### Recommandation : **Yousign** 🥇

**Pourquoi ?**
- ✅ **Français** : Hébergement en France, RGPD natif, support en français
- ✅ **Conforme eIDAS** : Signature électronique avancée avec valeur juridique
- ✅ **API REST complète** : Facile à intégrer dans Symfony
- ✅ **Webhooks** : Notifications temps réel des événements (signé, refusé, expiré)
- ✅ **Prix abordable** : ~10-15€/mois + 1-2€ par signature (dégressif selon volume)
- ✅ **Interface utilisateur** : Expérience de signature fluide pour les clients
- ✅ **Preuves d'intégrité** : Certificat de signature + journal d'audit

**Alternatives crédibles** :
- **Universign** si besoin de signatures qualifiées (notaires, actes officiels) - non pertinent pour HotOnes
- **DocuSign** si multinationale avec clients anglophones - overkill pour HotOnes

---

### 1.4 Architecture Technique

#### Workflow de signature avec Yousign

```
1. Utilisateur HotOnes crée un devis → Statut "À signer"
2. Clic sur "Envoyer pour signature" dans l'interface
3. Backend Symfony :
   - Génère le PDF du devis (DomPDF/Snappy)
   - Appelle l'API Yousign pour créer une demande de signature
   - Stocke l'ID de la procédure Yousign dans Order.yousignProcedureId
4. Yousign envoie un email au client avec lien sécurisé
5. Client clique, visualise le PDF, signe électroniquement
6. Yousign appelle le webhook HotOnes : /webhooks/yousign
7. Symfony met à jour le devis :
   - Statut → "Signé"
   - Date de signature enregistrée
   - PDF signé téléchargé et stocké
   - Notification envoyée au commercial
8. Génération automatique des tâches projet (workflow existant)
```

#### Entités Doctrine à créer/modifier

```php
// src/Entity/Order.php
class Order
{
    // Nouveaux champs
    private ?string $yousignProcedureId = null;
    private ?string $yousignSignedFileUrl = null;
    private ?\DateTimeImmutable $signedAt = null;
    private ?string $signerName = null;
    private ?string $signerEmail = null;
    private ?string $signerIpAddress = null;
}

// Nouvelle entité
// src/Entity/SignatureAudit.php
class SignatureAudit
{
    private ?int $id = null;
    private Order $order;
    private string $provider; // 'yousign'
    private string $procedureId;
    private string $status; // 'pending', 'signed', 'refused', 'expired'
    private array $metadata; // JSON : IP, user-agent, timestamp
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $completedAt = null;
}
```

#### Services Symfony

```php
// src/Service/ElectronicSignature/SignatureProviderInterface.php
interface SignatureProviderInterface
{
    public function createSignatureProcedure(Order $order, string $signerEmail, string $signerName): string;
    public function getSignatureStatus(string $procedureId): string;
    public function downloadSignedDocument(string $procedureId): string;
}

// src/Service/ElectronicSignature/YousignProvider.php
class YousignProvider implements SignatureProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $yousignApiKey,
        private string $yousignApiUrl
    ) {}

    // Implémentation des méthodes
}

// src/Service/OrderSignatureService.php
class OrderSignatureService
{
    public function sendForSignature(Order $order, string $signerEmail, string $signerName): void;
    public function handleSignatureCompleted(string $procedureId): void;
    public function handleSignatureRefused(string $procedureId): void;
}
```

#### Contrôleur webhook

```php
// src/Controller/Webhook/YousignWebhookController.php
#[Route('/webhooks/yousign', name: 'webhook_yousign', methods: ['POST'])]
class YousignWebhookController extends AbstractController
{
    public function __invoke(Request $request, OrderSignatureService $signatureService): Response
    {
        // Vérification de la signature HMAC (sécurité)
        // Traitement de l'événement (signature.completed, signature.refused, etc.)
        // Mise à jour du devis
        return new JsonResponse(['status' => 'ok']);
    }
}
```

---

### 1.5 Sécurité

#### Mesures de sécurité à implémenter

1. **Authentification de l'API** :
   - Clé API Yousign stockée dans `.env` (jamais dans le code)
   - Utilisation de Symfony Secrets pour la production

2. **Validation des webhooks** :
   - Vérification de la signature HMAC des webhooks Yousign
   - Whitelist des IPs Yousign (optionnel)

3. **Accès aux documents** :
   - URL de signature à usage unique (Yousign)
   - PDF signés stockés dans un répertoire sécurisé (hors web root)
   - Téléchargement uniquement pour utilisateurs autorisés (ROLE_ADMIN, ROLE_MANAGER, créateur du devis)

4. **Audit trail** :
   - Journalisation de tous les événements de signature (entité `SignatureAudit`)
   - Conservation des preuves : IP, user-agent, timestamp
   - Export du journal d'audit en cas de litige

---

### 1.6 Coûts Estimés (Yousign)

#### Tarification Yousign (indicative, décembre 2025)

**Abonnement mensuel** :
- Plan **Start** : 9€ HT/mois (jusqu'à 20 signatures/mois)
- Plan **Scale** : 39€ HT/mois (signatures illimitées, API)

**Coût par signature** :
- 1,80€ HT/signature (plan Start)
- Dégressif selon volume (plan Scale) : 1,20€ à 0,80€ HT/signature

**Estimation pour HotOnes** :
- Hypothèse : 10 devis signés/mois en moyenne
- Plan Start : 9€ + (10 × 1,80€) = **27€ HT/mois** soit **324€ HT/an**
- Alternative : Plan Scale si >20 signatures/mois ou besoin d'API avancées

**ROI** :
- ✅ Gain de temps : plus d'impression, scan, envoi postal (2-3h/mois économisées)
- ✅ Réduction des délais de signature : 3-5 jours → quelques heures
- ✅ Amélioration du taux de conversion (facilité de signature)
- ✅ Traçabilité juridique renforcée

---

### 1.7 Planning de Mise en Œuvre

#### Phase 1 : POC (Proof of Concept) - 2 jours
- Création d'un compte Yousign (essai gratuit)
- Test de l'API en environnement de développement
- Génération d'un PDF de devis et envoi pour signature
- Réception du webhook de signature

#### Phase 2 : Développement - 5-6 jours
- Création des entités et migrations
- Implémentation des services (YousignProvider, OrderSignatureService)
- Ajout du bouton "Envoyer pour signature" dans l'interface devis
- Développement du contrôleur webhook
- Téléchargement et stockage du PDF signé
- Mise à jour du statut du devis

#### Phase 3 : Tests - 2 jours
- Tests unitaires des services
- Tests d'intégration avec l'API Yousign (mock)
- Tests fonctionnels du workflow complet
- Tests de sécurité (webhook HMAC, accès documents)

#### Phase 4 : Mise en production - 1 jour
- Configuration des secrets (API key)
- Déploiement
- Configuration du webhook dans Yousign
- Tests en production (signature test)
- Documentation utilisateur

**Total estimé : 10-11 jours**

---

## 2. Facturation Électronique

### 2.1 Contexte et Obligation Légale

#### Réforme française de la facturation électronique

**Calendrier de déploiement** (loi de finances 2024) :

| Date | Obligation |
|------|------------|
| **1er septembre 2026** | Toutes les entreprises doivent **accepter** les factures électroniques (réception obligatoire) |
| **1er septembre 2026** | Les grandes entreprises (>250 salariés ou CA >50M€) doivent **émettre** des factures électroniques |
| **1er septembre 2027** | Les ETI et PME doivent **émettre** des factures électroniques |

**Pour HotOnes** : Obligation d'**émettre et recevoir** des factures électroniques à partir de **septembre 2027** (hypothèse PME).

#### Formats de facture électronique acceptés

1. **Factur-X (recommandé)** :
   - Format hybride : PDF lisible + XML structuré (norme CII)
   - Compatible avec les logiciels de comptabilité
   - Recommandé par l'État français

2. **UBL (Universal Business Language)** :
   - Format XML international
   - Plus complexe à générer

3. **CII (Cross-Industry Invoice)** :
   - Format XML (norme UN/CEFACT)
   - Base de Factur-X

**Recommandation : Factur-X** (standard français, hybride PDF+XML)

---

### 2.2 Portail Public de Facturation (PPF)

#### Qu'est-ce que le PPF ?

Le **Portail Public de Facturation** est la plateforme nationale gratuite de l'État pour :
- **Transmettre** les factures entre entreprises (via les PDP)
- **Extraire** les données fiscales pour la déclaration de TVA pré-remplie
- **Archiver** les factures (obligation légale de conservation 10 ans)

#### Plateformes de Dématérialisation Partenaires (PDP)

Les entreprises doivent passer par une **PDP** (Plateforme de Dématérialisation Partenaire) pour :
- Convertir les factures au format structuré (Factur-X, UBL, CII)
- Transmettre au PPF et aux destinataires
- Garantir l'intégrité et l'authenticité des factures

**Liste de PDP certifiées** (décembre 2025) :
- **Chorus Pro** (État, gratuit pour émission/réception)
- **Docaposte** (La Poste)
- **Generix**
- **Pagero**
- **Edipost**
- **Sage E-invoicing** (intégré à Sage)
- **Cegid e-invoicing** (intégré à Cegid)

**Recommandation pour HotOnes** :
- **Chorus Pro** (gratuit) si budget limité
- **Docaposte** ou **Generix** si besoin de fonctionnalités avancées (EDI, workflows)

---

### 2.3 Cas d'Usage dans HotOnes

#### Fonctionnalités à implémenter

1. **Génération de factures électroniques** (Factur-X) :
   - À partir d'un devis signé (forfait) ou de temps saisis (régie)
   - Génération automatique du PDF + XML structuré
   - Validation du format (conformité Factur-X)
   - Numérotation unique et chronologique (obligation légale)

2. **Émission via PDP** :
   - Envoi automatique vers la PDP (API Chorus Pro ou autre)
   - Transmission au client et au PPF
   - Suivi du statut (émise, reçue, rejetée, acceptée)

3. **Réception de factures fournisseurs** :
   - Récupération des factures depuis la PDP
   - Parsing du XML pour extraction des données
   - Enregistrement dans l'entité `Purchase` (achats)
   - Rapprochement automatique avec les commandes

4. **Archivage légal** :
   - Conservation des factures PDF+XML pendant 10 ans
   - Horodatage qualifié (optionnel mais recommandé)
   - Export pour audit fiscal

---

### 2.4 Architecture Technique

#### Workflow d'émission de facture

```
1. Utilisateur crée une facture depuis un devis signé (Order) ou depuis des timesheets (régie)
2. Backend Symfony :
   - Génère le PDF de la facture (template personnalisable)
   - Génère le XML CII (format Factur-X)
   - Fusionne PDF + XML = Factur-X
   - Stocke la facture dans Invoice.facturxFilePath
3. Envoi automatique vers la PDP :
   - Appelle l'API de la PDP (ex: Chorus Pro)
   - Transmet la facture + métadonnées (SIRET client, SIREN émetteur)
4. PDP transmet au PPF et au client
5. Webhook de la PDP : facture reçue par le client
6. Symfony met à jour le statut : Invoice.status → 'envoyee'
```

#### Entités Doctrine à créer/modifier

```php
// src/Entity/Invoice.php
class Invoice
{
    private ?int $id = null;
    private string $invoiceNumber; // Ex: FAC-2025-001 (unique, chronologique)
    private Order $order; // Lien vers le devis source
    private Client $client;
    private \DateTimeImmutable $issuedAt;
    private ?\DateTimeImmutable $dueAt = null; // Échéance de paiement
    private string $status; // 'draft', 'issued', 'sent', 'paid', 'overdue', 'cancelled'
    private string $totalHT;
    private string $totalTTC;
    private string $tvaAmount;
    private float $tvaRate = 20.0;
    private ?string $pdfFilePath = null;
    private ?string $facturxFilePath = null; // PDF+XML Factur-X
    private ?string $pdpReferenceId = null; // ID de la PDP (Chorus Pro, etc.)
    private ?\DateTimeImmutable $sentAt = null;
    private ?\DateTimeImmutable $paidAt = null;
    // Ligne de facture
    private Collection $lines; // InvoiceLine[]
}

// src/Entity/InvoiceLine.php
class InvoiceLine
{
    private ?int $id = null;
    private Invoice $invoice;
    private string $description;
    private string $quantity;
    private string $unitPrice;
    private string $totalHT;
    private float $tvaRate = 20.0;
}

// src/Entity/PdpLog.php (audit des échanges avec la PDP)
class PdpLog
{
    private ?int $id = null;
    private Invoice $invoice;
    private string $provider; // 'chorus_pro', 'docaposte'
    private string $action; // 'send', 'status_update'
    private string $status; // 'success', 'error'
    private array $requestData; // JSON
    private array $responseData; // JSON
    private \DateTimeImmutable $createdAt;
}
```

#### Services Symfony

```php
// src/Service/EInvoicing/FacturXGeneratorService.php
class FacturXGeneratorService
{
    public function generateFacturX(Invoice $invoice): string; // Retourne le chemin du fichier Factur-X
}

// src/Service/EInvoicing/PdpProviderInterface.php
interface PdpProviderInterface
{
    public function sendInvoice(Invoice $invoice, string $facturxFilePath): string; // Retourne l'ID PDP
    public function getInvoiceStatus(string $pdpReferenceId): string;
    public function receiveInvoices(): array; // Récupère les factures fournisseurs
}

// src/Service/EInvoicing/ChorusProProvider.php
class ChorusProProvider implements PdpProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $chorusProApiUrl,
        private string $chorusProLogin,
        private string $chorusProPassword,
        private string $companySiren
    ) {}

    // Implémentation des méthodes
}

// src/Service/InvoiceService.php
class InvoiceService
{
    public function createInvoiceFromOrder(Order $order): Invoice;
    public function createInvoiceFromTimesheets(Project $project, array $timesheets): Invoice;
    public function sendToClient(Invoice $invoice): void; // Envoi via PDP
    public function markAsPaid(Invoice $invoice, \DateTimeImmutable $paidAt): void;
}
```

#### Génération Factur-X avec PHP

Utiliser la bibliothèque **horstoeko/zugferd** (packagist) :
- Génération de XML CII conforme norme EN 16931
- Support des profils Factur-X (Basic, Minimum, EN 16931)
- Fusion PDF + XML

```bash
composer require horstoeko/zugferd
```

```php
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\ZugferdDocumentBuilder;

$document = ZugferdDocumentBuilder::CreateNew(ZugferdProfiles::PROFILE_EN16931);
$document
    ->setDocumentInformation("FAC-2025-001", "380", \DateTime::createFromFormat("Ymd", "20250115"), "EUR")
    ->addDocumentSeller("Ma Société", "12345678901234")
    ->addDocumentBuyer("Client SAS", "98765432109876")
    // ... Ajout des lignes de facture
    ->writeFile('/path/to/facture.xml');

// Fusion PDF + XML
$pdfBuilder = new ZugferdDocumentPdfBuilder($document, '/path/to/facture.pdf');
$pdfBuilder->generateDocument()->saveDocument('/path/to/facture-facturx.pdf');
```

---

### 2.5 Intégration Chorus Pro

#### Présentation de Chorus Pro

**Chorus Pro** est la plateforme publique de facturation électronique de l'État français :
- Gratuit pour émission et réception
- Obligatoire pour facturer l'État et les collectivités
- PDP certifiée pour le B2B (entreprises privées)

#### API Chorus Pro

**Documentation** : https://chorus-pro.gouv.fr/cpp/developpeur

**Flux principaux** :
1. **Dépôt de facture** : POST `/cpro/deposerfacture/v1`
   - Format Factur-X ou UBL
   - Métadonnées (SIRET émetteur, SIRET destinataire, montants)

2. **Consultation du statut** : GET `/cpro/consulter/v1`
   - Retourne le statut (déposée, transmise, rejetée, acceptée, mise en paiement)

3. **Récupération de factures** : GET `/cpro/rechercher/v1`
   - Liste des factures reçues (fournisseurs)

**Authentification** :
- Certificat client (X.509) pour les appels API
- Login/mot de passe pour l'interface web

**Environnements** :
- **Qualification (test)** : https://chorus-pro-qua.finances.rie.gouv.fr
- **Production** : https://chorus-pro.gouv.fr

#### Mise en œuvre technique

```php
// config/packages/framework.yaml
framework:
    http_client:
        scoped_clients:
            chorus_pro.client:
                base_uri: '%env(CHORUS_PRO_API_URL)%'
                headers:
                    'Content-Type': 'application/json'
                auth_bearer: '%env(CHORUS_PRO_API_TOKEN)%'
```

```php
// src/Service/EInvoicing/ChorusProProvider.php
class ChorusProProvider implements PdpProviderInterface
{
    public function sendInvoice(Invoice $invoice, string $facturxFilePath): string
    {
        $response = $this->httpClient->request('POST', '/cpro/deposerfacture/v1', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
            ],
            'json' => [
                'fichier' => base64_encode(file_get_contents($facturxFilePath)),
                'nomFichier' => 'facture-' . $invoice->getInvoiceNumber() . '.pdf',
                'siretEmetteur' => $this->companySiren,
                'siretDestinataire' => $invoice->getClient()->getSiret(),
                'montantHT' => $invoice->getTotalHT(),
                'montantTTC' => $invoice->getTotalTTC(),
            ],
        ]);

        $data = $response->toArray();
        return $data['identifiant']; // ID Chorus Pro
    }
}
```

---

### 2.6 Sécurité et Conformité

#### Sécurité technique

1. **Intégrité des factures** :
   - Hash SHA-256 du fichier Factur-X stocké en base
   - Vérification de l'intégrité lors de la lecture

2. **Horodatage qualifié** (optionnel) :
   - Certificat d'horodatage (TSA - Time Stamping Authority)
   - Preuve de la date d'émission (valeur légale)

3. **Authentification PDP** :
   - Certificat client X.509 pour Chorus Pro
   - API Key pour autres PDP (stockage sécurisé)

4. **Archivage** :
   - Stockage chiffré des factures (AES-256)
   - Réplication sur backup externe (obligation de conservation 10 ans)

#### Conformité légale

1. **Numérotation des factures** :
   - Séquentielle et chronologique (obligation légale)
   - Pas de trou dans la numérotation
   - Format : FAC-[ANNEE]-[NUMERO] (ex: FAC-2025-001)

2. **Mentions obligatoires** :
   - Numéro de facture unique
   - Date d'émission
   - Date d'échéance de paiement
   - Identité complète de l'émetteur (nom, adresse, SIREN/SIRET)
   - Identité complète du client (nom, adresse, SIREN/SIRET)
   - Numéro de TVA intracommunautaire
   - Montants HT, TVA, TTC
   - Taux de TVA appliqué
   - Conditions de paiement et pénalités de retard

3. **Conservation** :
   - 10 ans minimum (obligation fiscale)
   - Format électronique (PDF+XML)
   - Intégrité garantie (hash, horodatage)

---

### 2.7 Coûts Estimés

#### Solution gratuite : Chorus Pro

**Coûts** :
- Gratuit pour émission et réception de factures
- Pas de limite de volume
- Support technique de l'État (limité)

**Limitations** :
- Interface moins ergonomique que les PDP privées
- Fonctionnalités basiques (pas de workflow avancé)
- Pas de support commercial

**Coûts de développement** :
- Intégration API : 8-10 jours
- Certificat client X.509 : ~50-100€/an (fournisseur de confiance)

#### Solutions payantes (alternatives)

| Fournisseur | Prix indicatif | Avantages |
|-------------|----------------|-----------|
| **Docaposte** | 15-30€/mois + 0,20-0,50€/facture | Interface moderne, EDI, support |
| **Generix** | Sur devis (>500€/mois) | Workflows avancés, intégration ERP |
| **Sage E-invoicing** | Inclus dans Sage (15-50€/mois) | Intégration native Sage |

**Recommandation : Chorus Pro (gratuit)** pour démarrer, migration vers PDP privée si besoins avancés.

---

### 2.8 Planning de Mise en Œuvre

#### Phase 1 : Conception et préparation - 3 jours
- Analyse des besoins (types de factures, workflows)
- Choix de la PDP (Chorus Pro recommandé)
- Création d'un compte Chorus Pro (environnement de qualification)
- Obtention d'un certificat client (test)

#### Phase 2 : Développement des entités - 2 jours
- Création des entités Invoice, InvoiceLine, PdpLog
- Migrations de base de données
- Génération des CRUD de base

#### Phase 3 : Génération Factur-X - 4 jours
- Installation de horstoeko/zugferd
- Implémentation du service FacturXGeneratorService
- Génération du PDF de facture (template personnalisable)
- Génération du XML CII (profil EN 16931)
- Fusion PDF + XML
- Tests de validation (conformité Factur-X)

#### Phase 4 : Intégration PDP (Chorus Pro) - 5 jours
- Implémentation du service ChorusProProvider
- Envoi de facture via API
- Récupération du statut de la facture
- Réception de factures fournisseurs
- Gestion des erreurs et rejets
- Tests en environnement de qualification

#### Phase 5 : Interface utilisateur - 4 jours
- Page de liste des factures (filtres, recherche, export)
- Formulaire de création de facture
- Bouton "Créer une facture depuis un devis"
- Génération automatique depuis temps saisis (régie)
- Prévisualisation du PDF
- Envoi vers le client (PDP)
- Suivi du statut (timeline)

#### Phase 6 : Archivage et conformité - 2 jours
- Mise en place du stockage sécurisé
- Calcul et stockage des hash (intégrité)
- Export pour audit fiscal
- Documentation de conformité

#### Phase 7 : Tests - 3 jours
- Tests unitaires (génération Factur-X, calculs)
- Tests d'intégration (API Chorus Pro)
- Tests fonctionnels (workflow complet)
- Tests de conformité (validation Factur-X)

#### Phase 8 : Mise en production - 2 jours
- Configuration du certificat client (production)
- Déploiement
- Tests en production (factures de test)
- Formation utilisateurs
- Documentation

**Total estimé : 25-27 jours**

---

## 3. Synthèse et Recommandations

### 3.1 Priorités

| Fonctionnalité | Priorité | Raison | Échéance |
|----------------|----------|--------|----------|
| **Facturation électronique** | 🔴 **Haute** | **Obligation légale** septembre 2027 | Q2 2026 (anticiper) |
| **Signature électronique** | 🟡 **Moyenne** | Amélioration du processus commercial, gain de temps | Q3 2026 |

**Recommandation** : Démarrer par la **facturation électronique** (Q1-Q2 2026) pour anticiper l'obligation légale de septembre 2027. La **signature électronique** peut suivre en Q3 2026.

---

### 3.2 Solutions Recommandées

#### Signature électronique

| Critère | Solution | Justification |
|---------|----------|---------------|
| **Fournisseur** | **Yousign** | Français, conforme eIDAS, API complète, prix abordable |
| **Type de signature** | Avancée | Suffisant pour contrats B2B, valeur juridique |
| **Coût** | ~30€ HT/mois | Abordable pour une PME (~350€ HT/an) |

#### Facturation électronique

| Critère | Solution | Justification |
|---------|----------|---------------|
| **Format** | **Factur-X** (PDF+XML) | Standard français, hybride, compatible |
| **PDP** | **Chorus Pro** | Gratuit, certifié, obligatoire pour l'État |
| **Bibliothèque** | **horstoeko/zugferd** | Open-source, conforme EN 16931 |
| **Coût** | Gratuit (Chorus Pro) | Certificat client : ~50-100€/an |

---

### 3.3 Planning Global

#### Roadmap suggérée

**Q1 2026 : Facturation électronique (prioritaire)**
- Janvier-Février : Conception, développement (3 sprints)
- Mars : Tests et mise en production

**Q3 2026 : Signature électronique**
- Juillet : POC et développement (2 sprints)
- Août : Tests et mise en production

**Total estimé** :
- **Facturation électronique** : 25-27 jours
- **Signature électronique** : 10-11 jours
- **Total** : **35-38 jours** (7-8 semaines de développement)

---

### 3.4 Risques Identifiés

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Changement de réglementation (report échéance) | Moyenne | Faible | Veille réglementaire continue |
| Complexité d'intégration PDP | Moyenne | Moyen | POC en amont, support technique |
| Non-conformité Factur-X | Faible | Élevé | Validation avec outils officiels |
| Coûts cachés (certificat, support) | Faible | Faible | Budget de contingence (+20%) |
| Adoption utilisateurs | Moyenne | Moyen | Formation, documentation, support |

---

### 3.5 ROI Attendu

#### Signature électronique

**Gains** :
- ⏱️ **Gain de temps** : 2-3h/mois économisées (impression, scan, envoi)
- 📈 **Taux de conversion** : +10-15% (facilité de signature)
- ⚡ **Délai de signature** : 3-5 jours → quelques heures
- 🔒 **Sécurité juridique** : Traçabilité renforcée

**Coût** : ~350€ HT/an
**ROI** : Positif dès 6 mois (gain de temps + amélioration du taux de conversion)

#### Facturation électronique

**Gains** :
- ⚖️ **Conformité légale** : Éviter les sanctions (obligation 2027)
- ⏱️ **Gain de temps** : Automatisation de l'envoi (1h/facture → 5min)
- 💰 **Réduction des coûts** : Moins de papier, timbres, archivage physique (~200€/an)
- 📊 **Traçabilité** : Meilleur suivi des paiements

**Coût** : ~50-100€/an (certificat)
**ROI** : Positif immédiat (obligation légale + gains de productivité)

---

### 3.6 Dépendances avec les Lots Existants

#### Signature électronique

**Dépendances** :
- ✅ **Lot 1.4** : Gestion des Devis (Order) - TERMINÉ
- 📋 **Lot 1.4** : Prévisualisation PDF du devis - À FAIRE (nécessaire pour signer)
- 💡 **Lot 9** : Module de Facturation - PLANIFIÉ (signatures de contrats futurs)

**Peut être développé en parallèle** : Oui (une fois le PDF des devis implémenté)

#### Facturation électronique

**Dépendances** :
- ✅ **Lot 1.4** : Gestion des Devis (Order) - TERMINÉ
- 💡 **Lot 9** : Module de Facturation - PLANIFIÉ (entité Invoice à créer)
- 🔲 **Lot 2** : Saisie des Temps - EN COURS (facturation au temps passé pour régie)

**Doit être intégré dans** : **Lot 9** (Module de Facturation)

---

### 3.7 Recommandations Finales

#### Court terme (Q1 2026)

1. **Valider le budget** :
   - Signature électronique : ~350€ HT/an
   - Facturation électronique : ~50-100€/an (certificat)
   - **Total : ~500€ HT/an** (très abordable)

2. **Créer les comptes** :
   - Yousign (essai gratuit puis abonnement)
   - Chorus Pro (gratuit, environnement de qualification)

3. **Démarrer par la facturation électronique** (obligation 2027) :
   - POC en janvier 2026
   - Développement février-mars 2026
   - Mise en production avril 2026

#### Moyen terme (Q3 2026)

1. **Implémenter la signature électronique** :
   - POC en juillet 2026
   - Développement et mise en production août 2026

2. **Former les utilisateurs** :
   - Documentation interne
   - Sessions de formation (commerciaux, chefs de projet, comptabilité)

3. **Veille réglementaire** :
   - Suivre les évolutions de la réglementation (reports éventuels, nouvelles obligations)
   - Participer aux webinaires de la DGFIP

---

## 📚 Ressources et Documentation

### Signature électronique

- **Yousign** :
  - Site : https://yousign.com
  - Documentation API : https://developers.yousign.com
  - Tarifs : https://yousign.com/fr-fr/tarifs

- **Réglementation eIDAS** :
  - Règlement européen : https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32014R0910

### Facturation électronique

- **Portail de la facturation électronique** :
  - Site officiel : https://www.entreprises.gouv.fr/facture-electronique
  - Chorus Pro : https://chorus-pro.gouv.fr

- **Factur-X** :
  - Site officiel : https://fnfe-mpe.org/factur-x/
  - Spécification : https://fnfe-mpe.org/factur-x/factur-x_en16931/

- **Bibliothèque PHP** :
  - horstoeko/zugferd : https://packagist.org/packages/horstoeko/zugferd
  - Documentation : https://github.com/horstoeko/zugferd

- **Chorus Pro - Documentation développeur** :
  - API : https://chorus-pro.gouv.fr/cpp/developpeur
  - Guide d'intégration : https://communaute-chorus-pro.finances.gouv.fr

---

## 📝 Conclusion

Les fonctionnalités de **signature électronique** et de **facturation électronique** sont stratégiques pour HotOnes :

- **Facturation électronique** : **Obligation légale** en septembre 2027 → À anticiper dès Q1 2026
- **Signature électronique** : **Différenciation compétitive** et gain de productivité → Recommandé en Q3 2026

**Budget total** : ~500€ HT/an (très abordable pour une PME)
**Effort de développement** : ~35-38 jours (7-8 semaines)
**ROI** : Positif dès la première année (gains de temps + conformité légale)

**Prochaines étapes** :
1. ✅ Valider la roadmap et les priorités avec la direction
2. ✅ Créer les comptes (Yousign, Chorus Pro)
3. ✅ Ajouter les lots à la roadmap 2025 (Lot 25 : Facturation électronique, Lot 26 : Signature électronique)
4. ✅ Démarrer le développement en Q1 2026

---

**Document rédigé le** : 17 décembre 2025
**Auteur** : Claude Code
**Version** : 1.0
**Prochaine revue** : Février 2026 (après POC facturation électronique)
