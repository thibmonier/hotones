# 📋 Plan d'implémentation des améliorations

> Document de référence pour l'implémentation des fonctionnalités avancées
> 
> Date : Décembre 2025

---

## ✅ Points déjà implémentés

### Point 2 - Performance et scalabilité
- ✅ Service `AnalyticsCacheService` pour cache Redis des métriques
- ✅ Commande CLI `app:analytics:cache` pour invalidation et warmup
- ✅ Script SQL d'index sur tables critiques (`migrations/performance_indexes.sql`)
- ✅ Support WebP pour optimisation images dans `SecureFileUploadService`

### Point 3 - Sécurité et conformité  
- ✅ Service `SecureFileUploadService` avec validation MIME stricte
- ✅ Configuration rate limiting (`config/packages/rate_limiter.yaml`)
- ✅ Limits configurées : login (5/15min), API (100/min), uploads (20/h)

### Point 5 - UX/UI (Partiel)
- ✅ Recherche globale avec raccourci Ctrl+K (`assets/js/global-search.js`)
- ✅ API endpoint `/api/search` déjà existante
- ✅ Modal recherche avec résultats groupés par type

### Point 11 - IA générative
- ✅ Service `AiAssistantService` avec support OpenAI et Anthropic
- ✅ Méthodes : `analyzeSentiment()`, `generateEmailReply()`, `generateQuoteLines()`
- ✅ Configuration via `.env` : `OPENAI_API_KEY` et `ANTHROPIC_API_KEY`

---

## 🚧 Points à compléter

### Point 5 - Expérience utilisateur (UX/UI) - Complétion

#### a) Favoris et Raccourcis
**Objectif** : Permettre d'épingler des projets/clients fréquents dans le menu latéral

**Implémentation** :

1. **Entity `UserFavorite`**
```php
class UserFavorite {
    private User $user;
    private string $entityType; // 'project', 'client', 'order'
    private int $entityId;
    private int $position; // ordre d'affichage
    private DateTime $createdAt;
}
```

2. **Repository** : `UserFavoriteRepository` avec méthode `findByUser(User $user)`

3. **Controller** : `FavoriteController`
   - Route POST `/favorites/{type}/{id}/toggle` pour ajouter/retirer
   - Route GET `/favorites` pour lister

4. **Twig** : Affichage dans `templates/layouts/_sidebar.html.twig`
   - Section "⭐ Mes favoris" en haut du menu
   - Icône étoile cliquable sur pages projet/client

5. **JavaScript** : `assets/js/favorites.js` pour toggle AJAX

**Estimation** : 4 heures

---

#### b) Derniers Consultés (Historique)
**Objectif** : Widget affichant 3-5 derniers éléments consultés

**Implémentation** :

1. **EventSubscriber `ViewHistorySubscriber`**
   - Écoute `kernel.controller` pour routes `*_show`
   - Stocke dans session ou Redis : `user_history_{userId}` = liste circulaire (max 10)

2. **Service `ViewHistoryService`**
```php
public function addToHistory(User $user, string $type, int $id, string $title): void;
public function getHistory(User $user, int $limit = 5): array;
```

3. **Twig** : Widget dans header `templates/layouts/_topbar.html.twig`
   - Dropdown "Historique" à côté de la recherche

**Estimation** : 3 heures

---

#### c) Auto-complétion Select2
**Objectif** : Améliorer sélection de Client, Projet, Contributeur dans formulaires

**Implémentation** :

1. **Installation Select2**
```bash
npm install select2 --save
```

2. **Import dans `assets/app.js`**
```javascript
import 'select2';
import 'select2/dist/css/select2.min.css';
```

3. **JavaScript** : `assets/js/select2-init.js`
```javascript
$(document).ready(function() {
    $('.select2-entity').select2({
        ajax: {
            url: function() {
                return $(this).data('ajax-url');
            },
            dataType: 'json',
            delay: 250,
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({
                results: data.map(item => ({ id: item.id, text: item.name }))
            })
        },
        minimumInputLength: 2
    });
});
```

4. **API Routes** :
   - `/api/clients/search` (déjà existe ?)
   - `/api/projects/search`
   - `/api/contributors/search`

5. **Twig** : Dans les FormTypes, ajouter attribut `attr => ['class' => 'select2-entity', 'data-ajax-url' => '/api/clients/search']`

**Estimation** : 5 heures (pour plusieurs entités)

---

#### d) Validation inline AJAX
**Objectif** : Validation en temps réel sans soumission formulaire

**Implémentation** :

1. **Controller** : Routes de validation
   - POST `/api/validate/email` → Retourne `{ valid: true|false, message: string }`
   - POST `/api/validate/order-reference`
   - POST `/api/validate/siret`

2. **JavaScript** : `assets/js/inline-validation.js`
```javascript
$('[data-validate]').on('blur', async function() {
    const input = $(this);
    const validateUrl = input.data('validate');
    const value = input.val();
    
    const response = await fetch(validateUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ value })
    });
    
    const result = await response.json();
    
    if (!result.valid) {
        input.addClass('is-invalid');
        input.next('.invalid-feedback').text(result.message);
    } else {
        input.removeClass('is-invalid').addClass('is-valid');
    }
});
```

3. **Twig** : Ajout attribut dans FormTypes
```php
->add('email', EmailType::class, [
    'attr' => [
        'data-validate' => '/api/validate/email'
    ]
])
```

**Estimation** : 6 heures

---

#### e) Dashboard personnalisable
**Objectif** : Permettre à chaque utilisateur de choisir les widgets affichés

**Implémentation** :

1. **Entity `DashboardWidget`**
```php
class DashboardWidget {
    private User $user;
    private string $widgetType; // 'revenue', 'projects_at_risk', 'timesheet_pending', etc.
    private int $position;
    private bool $visible;
    private array $config; // JSON pour paramètres spécifiques
}
```

2. **Repository** : `DashboardWidgetRepository`

3. **Service `DashboardConfigService`**
```php
public function getWidgets(User $user): array;
public function saveConfig(User $user, array $widgets): void;
public function resetToDefault(User $user): void;
```

4. **Controller** : `DashboardController`
   - Route GET `/` → Affiche widgets de l'utilisateur
   - Route POST `/dashboard/config` → Sauvegarde config
   - Route POST `/dashboard/widget/{type}/toggle`

5. **Templates** :
   - Widgets dans `templates/dashboard/_widgets/`
   - Drag & drop avec SortableJS ou GridStack

6. **JavaScript** : `assets/js/dashboard-config.js`
   - Mode édition/personnalisation
   - Drag & drop des widgets
   - Sauvegarde AJAX

**Estimation** : 12 heures

---

## Point 6 - Module prévisionnel financier avancé

### a) Simulation de scénarios What-If
**Objectif** : Modéliser l'impact de décisions business

**Implémentation** :

1. **Entity `Scenario`**
```php
class Scenario {
    private string $name;
    private User $createdBy;
    private array $assumptions; // JSON: { "new_hires": 2, "avg_tjm_increase": 50 }
    private DateTime $createdAt;
    private ?array $results; // JSON: résultats de simulation
}
```

2. **Service `ScenarioSimulatorService`**
```php
public function simulate(Scenario $scenario): array {
    // Algorithme :
    // 1. Charger données historiques (CA, marges, effectif)
    // 2. Appliquer les hypothèses (ex: +2 devs = + X€ de coûts mensuels)
    // 3. Recalculer CA projeté (capacité accrue -> plus de projets signables)
    // 4. Calculer marge nette projetée
    // 5. ROI estimé (mois pour atteindre break-even)
    
    return [
        'projected_revenue' => 500000,
        'projected_margin' => 125000,
        'break_even_months' => 8,
        'roi_12months' => 0.35 // 35%
    ];
}
```

3. **Controller** : `ScenarioController`
   - GET `/scenarios` → Liste des scénarios
   - GET `/scenarios/new` → Formulaire création
   - POST `/scenarios` → Sauvegarde + simulation
   - GET `/scenarios/{id}` → Résultats visuels (Chart.js)

4. **Templates** :
   - Formulaire avec sliders pour paramètres (nb recrutements, budget marketing, etc.)
   - Graphiques comparatifs (scénario actuel vs simulé)

**Estimation** : 15 heures

---

### b) Tableaux de bord directeur (DSO, BFR, Runway)
**Objectif** : Indicateurs financiers avancés pour la direction

**Implémentation** :

1. **Service `FinancialMetricsService`**
```php
public function calculateDSO(): float {
    // DSO (Days Sales Outstanding) = (Créances clients / CA) × 365
    // Créances = Factures émises non payées
    $unpaidInvoices = /* somme factures status != 'paid' */;
    $annualRevenue = /* CA sur 12 derniers mois */;
    return ($unpaidInvoices / $annualRevenue) * 365;
}

public function calculateBFR(): float {
    // BFR = Stocks + Créances clients - Dettes fournisseurs
    // Dans service web : pas de stocks
    // BFR = Créances clients - Dettes fournisseurs (factures Purchase à payer)
    $receivables = /* Factures clients impayées */;
    $payables = /* Achats non payés */;
    return $receivables - $payables;
}

public function calculateRunway(float $monthlyBurnRate): float {
    // Runway = Trésorerie / Burn Rate mensuel
    // Trésorerie = CA encaissé - Coûts payés
    // Burn Rate = Coûts mensuels moyens (salaires + achats)
    $cash = /* Trésorerie actuelle */;
    return $cash / $monthlyBurnRate; // Résultat en mois
}
```

2. **Controller** : `FinancialDashboardController`
   - Route GET `/financial/dashboard`

3. **Templates** : Cartes KPIs + graphiques d'évolution

**Estimation** : 10 heures

---

## Point 7 - Intégrations externes

### a) Export FEC (Fichier des Écritures Comptables)
**Objectif** : Conformité France pour logiciels comptables

**Format FEC** : Fichier texte pipe-delimited (|) avec 18 colonnes obligatoires selon norme DGFiP.

**Implémentation** :

1. **Service `FecExportService`**
```php
public function generateFEC(int $year): string {
    // Colonnes FEC (18) :
    // JournalCode|JournalLib|EcritureNum|EcritureDate|CompteNum|CompteLib|
    // CompAuxNum|CompAuxLib|PieceRef|PieceDate|EcritureLib|Debit|Credit|
    // EcritureLet|DateLet|ValidDate|Montantdevise|Idevise
    
    $lines = [];
    $lines[] = implode('|', [/* Header */]);
    
    // Pour chaque facture de l'année
    foreach ($invoices as $invoice) {
        // Ligne de crédit (compte client 411xxx)
        $lines[] = implode('|', [
            'VE', // Journal ventes
            'Ventes',
            $invoice->getReference(),
            $invoice->getCreatedAt()->format('Ymd'),
            '411001', // Compte client
            'Clients',
            $invoice->getClient()->getSiret(),
            $invoice->getClient()->getName(),
            $invoice->getReference(),
            $invoice->getCreatedAt()->format('Ymd'),
            'Facture ' . $invoice->getReference(),
            '', // Débit vide
            number_format($invoice->getTotalTtc(), 2, ',', ''),
            '', '', '', '', ''
        ]);
        
        // Ligne de débit (compte produit 706xxx)
        $lines[] = implode('|', [
            'VE',
            'Ventes',
            $invoice->getReference(),
            $invoice->getCreatedAt()->format('Ymd'),
            '706000',
            'Prestations de services',
            '',
            '',
            $invoice->getReference(),
            $invoice->getCreatedAt()->format('Ymd'),
            'Facture ' . $invoice->getReference(),
            number_format($invoice->getTotalTtc(), 2, ',', ''),
            '',
            '', '', '', '', ''
        ]);
    }
    
    return implode("\n", $lines);
}
```

2. **Controller** : Dans `InvoiceController` ou dédié
   - Route GET `/export/fec/{year}`
   - Génère fichier `FEC_SIRET_ANNEE.txt`

**Estimation** : 8 heures (+ tests conformité)

---

### b) Bot Slack pour saisie temps
**Objectif** : `/hotones log 2h ProjectX TaskY`

**Implémentation** :

1. **Installation Slack App**
   - Créer app sur api.slack.com
   - Permissions : `commands`, `chat:write`
   - Slash command `/hotones`
   - Request URL : `https://votre-domaine.com/slack/command`

2. **Controller** : `SlackController`
```php
#[Route('/slack/command', methods: ['POST'])]
public function handleCommand(Request $request): JsonResponse {
    $payload = $request->request->all();
    $command = $payload['text']; // "log 2h ProjectX TaskY"
    $slackUserId = $payload['user_id'];
    
    // Parser la commande
    if (preg_match('/log (\d+\.?\d*)h (.+?) (.+)/', $command, $matches)) {
        $hours = (float)$matches[1];
        $projectName = $matches[2];
        $taskName = $matches[3];
        
        // Trouver user Hotones par Slack user ID (mapping à faire)
        $user = $this->userRepository->findOneBySlackId($slackUserId);
        
        // Créer timesheet
        $timesheet = new Timesheet();
        $timesheet->setContributor($user->getContributor());
        $timesheet->setDate(new \DateTime());
        $timesheet->setHours($hours);
        // ... find project & task
        
        $this->em->persist($timesheet);
        $this->em->flush();
        
        return $this->json([
            'response_type' => 'ephemeral',
            'text' => "✅ Temps saisi : {$hours}h sur {$projectName} / {$taskName}"
        ]);
    }
    
    return $this->json([
        'response_type' => 'ephemeral',
        'text' => "❌ Format invalide. Utilisez : `/hotones log 2h ProjectX TaskY`"
    ]);
}
```

3. **Mapping Slack ↔ HotOnes** :
   - Ajouter champ `slackUserId` dans `User`
   - Page de configuration dans `/me` pour lier compte Slack

**Estimation** : 10 heures

---

## Point 8 - Module BI embarqué

### a) Interface no-code pour rapports
**Objectif** : Query builder visuel pour non-dev

**Implémentation** :

1. **Entity `CustomReport`**
```php
class CustomReport {
    private string $name;
    private User $createdBy;
    private string $entityType; // 'project', 'timesheet', 'invoice'
    private array $dimensions; // ['client', 'contributor', 'month']
    private array $metrics; // ['sum_revenue', 'count_projects', 'avg_margin']
    private array $filters; // [['field' => 'status', 'operator' => '=', 'value' => 'completed']]
    private array $groupBy; // ['client']
    private array $orderBy; // [['field' => 'sum_revenue', 'direction' => 'DESC']]
}
```

2. **Service `ReportBuilderService`**
```php
public function buildQuery(CustomReport $report): array {
    // Construire dynamiquement une requête DQL/SQL selon les dimensions/métriques
    $qb = $this->em->createQueryBuilder();
    $qb->select(/* dimensions + métriques */)
       ->from(/* entity */);
    
    foreach ($report->getFilters() as $filter) {
        $qb->andWhere("e.{$filter['field']} {$filter['operator']} :{$filter['field']}");
        $qb->setParameter($filter['field'], $filter['value']);
    }
    
    foreach ($report->getGroupBy() as $groupField) {
        $qb->groupBy("e.{$groupField}");
    }
    
    return $qb->getQuery()->getResult();
}
```

3. **Controller** : `ReportBuilderController`
   - GET `/reports/builder` → Interface drag & drop
   - POST `/reports` → Sauvegarde rapport
   - GET `/reports/{id}/execute` → Exécute et affiche résultats

4. **Frontend** :
   - Utiliser librairie comme **QueryBuilder.js** ou custom avec Vue/React
   - Sélection entité → Champs disponibles apparaissent
   - Drag & drop dimensions/métriques
   - Preview résultats en temps réel

**Estimation** : 20 heures

---

### b) Export planifié (cron)
**Objectif** : Envoi automatique de rapports par email

**Implémentation** :

1. **Entity `ScheduledReport`**
```php
class ScheduledReport {
    private CustomReport $report;
    private string $frequency; // 'daily', 'weekly', 'monthly'
    private string $dayOfWeek; // 'monday'
    private int $dayOfMonth;
    private string $time; // '09:00'
    private array $recipients; // ['email1@example.com', 'email2@example.com']
    private string $format; // 'pdf', 'excel', 'csv'
}
```

2. **Commande CLI** : `ScheduledReportCommand`
```php
#[AsCommand('app:reports:send-scheduled')]
public function execute(): int {
    $reportsToSend = $this->scheduledReportRepository->findDueReports();
    
    foreach ($reportsToSend as $scheduledReport) {
        $data = $this->reportBuilder->buildQuery($scheduledReport->getReport());
        
        // Générer fichier (PDF/Excel/CSV)
        $file = $this->reportGenerator->generate($data, $scheduledReport->getFormat());
        
        // Envoyer email
        $email = (new Email())
            ->to(...$scheduledReport->getRecipients())
            ->subject("Rapport : {$scheduledReport->getReport()->getName()}")
            ->attach($file);
        
        $this->mailer->send($email);
    }
    
    return Command::SUCCESS;
}
```

3. **Scheduler Symfony** : dans `config/packages/scheduler.yaml`
```yaml
framework:
    scheduler:
        send_scheduled_reports:
            task: 'app:reports:send-scheduled'
            frequency: '0 9 * * *' # Tous les jours à 9h
```

**Estimation** : 8 heures

---

## Point 9 - Gestion compétences enrichie

### a) Certification tracking
**Objectif** : Suivi des certifications avec alertes expiration

**Implémentation** :

1. **Entity `Certification`**
```php
class Certification {
    private Contributor $contributor;
    private string $name; // 'AWS Solutions Architect', 'Scrum Master'
    private string $provider; // 'Amazon', 'Scrum.org'
    private string $level; // 'Associate', 'Professional'
    private DateTime $obtainedAt;
    private ?DateTime $expiresAt;
    private ?string $credentialUrl; // Lien badge Credly
    private ?string $certificateFile;
}
```

2. **Repository** : `CertificationRepository`
   - `findExpiringSoon(int $days = 90)` → Certifications expirant dans X jours

3. **Commande CLI** : `CertificationReminderCommand`
```php
// Envoie emails de rappel pour certifications expirant dans 60j / 30j / 7j
```

4. **Controller** : `CertificationController`
   - CRUD certifications
   - Upload scan certificat

5. **Templates** :
   - Liste certifications dans page contributeur
   - Badges visuels (Credly style)

**Estimation** : 6 heures

---

### b) Learning Paths
**Objectif** : Parcours de montée en compétence par profil

**Implémentation** :

1. **Entity `LearningPath`**
```php
class LearningPath {
    private string $name; // 'Devenir Dev Senior Symfony'
    private Profile $targetProfile;
    private array $steps; // JSON: [{ "title": "Symfony Advanced", "duration_hours": 20, "skills": [1,2,3] }]
    private ?string $description;
}
```

2. **Entity `ContributorLearningPath`** (progression)
```php
class ContributorLearningPath {
    private Contributor $contributor;
    private LearningPath $learningPath;
    private int $currentStep;
    private ?DateTime $startedAt;
    private ?DateTime $completedAt;
}
```

3. **Service** : `LearningPathService`
   - `suggestPathsForContributor(Contributor $c): array` → Basé sur gap analysis
   - `markStepCompleted(ContributorLearningPath $clp, int $stepIndex): void`

4. **Templates** :
   - Vue "Mes parcours" pour contributeur
   - Barre de progression par learning path

**Estimation** : 10 heures

---

## 🎯 Priorisation recommandée

| Priorité | Fonctionnalité | Estimation | Impact |
|----------|----------------|------------|--------|
| P1 | Export FEC | 8h | ⭐⭐⭐ Obligatoire France |
| P1 | Validation inline AJAX | 6h | ⭐⭐⭐ Productivité |
| P1 | Favoris / Raccourcis | 4h | ⭐⭐ UX |
| P2 | Certification tracking | 6h | ⭐⭐ RH |
| P2 | Bot Slack | 10h | ⭐⭐ Productivité |
| P2 | Dashboard personnalisable | 12h | ⭐⭐ UX |
| P3 | Learning Paths | 10h | ⭐⭐ RH |
| P3 | Simulation scénarios | 15h | ⭐⭐ Finance |
| P3 | Module BI embarqué | 28h | ⭐⭐⭐ Différenciation |

---

## 📝 Configuration requise

### Variables d'environnement à ajouter dans `.env`

```env
# IA Générative (déjà en place)
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...

# Slack Integration
SLACK_BOT_TOKEN=xoxb-...
SLACK_SIGNING_SECRET=...

# Qonto API (si intégration bancaire)
QONTO_API_KEY=...
QONTO_ORGANIZATION_SLUG=...
```

---

## 🚀 Prochaines étapes

1. **Cette semaine** :
   - Appliquer le script SQL des index : `mysql < migrations/performance_indexes.sql`
   - Compiler assets Webpack : `npm run build`
   - Tester recherche globale Ctrl+K
   - Configurer clés API IA si besoin

2. **Semaine prochaine** :
   - Implémenter validation inline AJAX (P1)
   - Export FEC (P1)
   - Certification tracking (P2)

3. **Mois prochain** :
   - Module BI embarqué (grosse feature)
   - Dashboard personnalisable
   - Bot Slack

---

**Dernière mise à jour** : Décembre 2025
