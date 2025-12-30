# 💰 Module de Gestion des Notes de Frais

> Fonctionnalité de gestion des notes de frais pour les contributeurs
>
> Document créé le : 8 décembre 2025

## Liens
- Roadmap 2025 : [docs/roadmap-2025.md](./roadmap-2025.md)
- Plan d'Exécution 2025 : [docs/execution-plan-2025.md](./execution-plan-2025.md)

---

## 📊 Vue d'ensemble

### Objectif
Permettre aux contributeurs de déclarer leurs frais professionnels et à l'équipe comptabilité de les gérer. Les frais peuvent être rattachés à des projets/devis et refacturés aux clients selon les conditions contractuelles.

### Périmètre fonctionnel
- Saisie des notes de frais par les contributeurs
- Rattachement optionnel à un projet et/ou devis
- Gestion de la refacturation client (paramétrable par devis)
- Frais de gestion associés aux frais refacturés
- Frais internes portés uniquement par l'agence
- Écran de gestion et suivi dans la section Comptabilité

---

## 🏗️ Architecture

### Entités

#### Entité `ExpenseReport` (Note de frais)
```php
class ExpenseReport
{
    private ?int $id;
    private Contributor $contributor;          // Contributeur ayant engagé le frais
    private DateTimeInterface $expenseDate;    // Date du frais
    private string $category;                  // Type de frais (déplacement, repas, hébergement, matériel, autre)
    private string $description;
    private float $amountHT;                   // Montant HT
    private float $vatRate;                    // Taux de TVA (0, 5.5, 10, 20)
    private float $amountTTC;                  // Montant TTC
    private ?Project $project;                 // Projet rattaché (optionnel)
    private ?Order $order;                     // Devis rattaché (optionnel)
    private string $status;                    // Statut : brouillon, en_attente, validé, refusé, payé
    private ?string $filePath;                 // Justificatif (PDF, image)
    private ?User $validator;                  // Validateur (manager ou comptable)
    private ?DateTimeInterface $validatedAt;
    private ?string $validationComment;
    private ?DateTimeInterface $paidAt;        // Date de remboursement
    private DateTimeInterface $createdAt;
    private DateTimeInterface $updatedAt;
}
```

#### Ajouts aux entités existantes

**`Order` (Devis)**
```php
class Order
{
    // ... champs existants
    
    private bool $expensesRebillable = false;         // Les frais sont-ils refacturés au client ?
    private float $expenseManagementFeeRate = 0.0;    // Taux de frais de gestion (ex: 10.0 pour 10%)
    
    // Relation
    private Collection $expenseReports;               // One-to-Many vers ExpenseReport
}
```

### Catégories de frais
- `transport` : Transport (train, avion, taxi, péage, carburant)
- `meal` : Repas
- `accommodation` : Hébergement
- `equipment` : Matériel
- `training` : Formation
- `other` : Autre

### Statuts de frais
- `draft` : Brouillon (saisi par contributeur, non soumis)
- `pending` : En attente de validation
- `validated` : Validé par manager/comptable
- `rejected` : Refusé
- `paid` : Remboursé au contributeur

---

## 🎯 Fonctionnalités

### 1. Saisie de frais (Contributeur)

#### Interface `/expense-reports/new`
- Formulaire de saisie :
  - Date du frais (DatePicker)
  - Catégorie (Select avec icônes)
  - Description
  - Montant HT
  - Taux de TVA (select : 0%, 5.5%, 10%, 20%)
  - Calcul automatique TTC
  - Upload justificatif (image ou PDF, max 10 Mo)
  - Rattachement projet (optionnel, autocomplete)
  - Rattachement devis (optionnel, filtré par projet si sélectionné)
- Actions :
  - Enregistrer brouillon
  - Soumettre pour validation (statut → `pending`)

#### Liste mes frais `/expense-reports/mine`
- Tableau avec colonnes :
  - Date
  - Catégorie (icône + label)
  - Description
  - Montant TTC
  - Projet / Devis
  - Statut (badge coloré)
  - Actions (Voir, Modifier si brouillon, Supprimer si brouillon)
- Filtres :
  - Statut
  - Catégorie
  - Période
  - Projet
- Total TTC affiché
- Export CSV/Excel

### 2. Validation de frais (Manager / Comptable)

#### Liste des frais en attente `/expense-reports/pending`
- Tableau similaire à la vue contributeur
- Actions par ligne :
  - Voir détail (avec justificatif)
  - Valider (modal avec commentaire optionnel)
  - Refuser (modal avec commentaire obligatoire)
- Actions en masse :
  - Validation multiple (checkbox)
- Notifications :
  - Email au contributeur en cas de validation/rejet

### 3. Gestion comptable

#### Dashboard comptabilité `/accounting/expenses`
- **KPIs** :
  - Total frais du mois (TTC)
  - Total à rembourser (validés non payés)
  - Total refacturable client
  - Total frais internes (non refacturables)
- **Graphiques** :
  - Répartition par catégorie (camembert)
  - Évolution mensuelle des frais (ligne)
  - Top 5 contributeurs par montant
- **Filtres dynamiques** :
  - Statut
  - Catégorie
  - Période
  - Contributeur
  - Projet
  - Refacturable (oui/non)

#### Liste de tous les frais `/expense-reports`
- Tableau complet avec colonnes :
  - Date
  - Contributeur
  - Catégorie
  - Description
  - Montant TTC
  - Projet / Devis
  - Refacturable (icône si oui)
  - Frais de gestion (€)
  - Statut
  - Actions (Voir, Modifier statut, Marquer comme payé, Supprimer)
- Filtres avancés
- Export comptable (CSV pour logiciel compta)

#### Marquer comme payé
- Action disponible pour statut `validated`
- Modal avec :
  - Date de paiement (défaut : aujourd'hui)
  - Mode de paiement (virement, espèces, note de crédit)
  - Référence paiement (optionnel)
- Envoi email confirmation au contributeur

### 4. Intégration aux devis et factures

#### Affichage dans devis (`/orders/{id}`)
- Onglet ou section "Frais associés"
- Paramètres devis :
  - Checkbox "Frais refacturables au client"
  - Champ "Taux de frais de gestion" (%, ex: 10)
- Tableau des frais rattachés :
  - Liste des frais validés liés à ce devis
  - Total frais
  - Total avec frais de gestion
- Calcul automatique :
  - Montant refacturé = Somme frais × (1 + taux frais de gestion)

#### Intégration factures
- Lors de génération facture depuis devis :
  - Ajout automatique ligne "Frais" si `expensesRebillable = true`
  - Montant = Total frais × (1 + taux frais de gestion)
  - Détail en annexe (optionnel) : liste des frais

### 5. Rapports et statistiques

#### Rapport frais par projet (`/reports/expenses-by-project`)
- Tableau par projet :
  - Total frais engagés
  - Total refacturé
  - Total frais internes
  - Marge frais de gestion
- Filtres : période, projet, catégorie
- Export PDF/Excel

#### Rapport frais par contributeur (`/reports/expenses-by-contributor`)
- Tableau par contributeur :
  - Total frais du mois/trimestre/année
  - Répartition par catégorie
  - Total remboursé vs en attente
- Export PDF/Excel

---

## 🔐 Permissions

| Rôle | Saisir | Voir ses frais | Voir tous frais | Valider | Gérer paiement | Paramétrer devis |
|------|--------|----------------|-----------------|---------|----------------|------------------|
| **Contributeur** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Chef de projet** | ✅ | ✅ | ✅ (ses projets) | ✅ (ses projets) | ❌ | ❌ |
| **Manager** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Comptable** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Admin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 🛠️ Implémentation technique

### Migration
```php
// Migration : create expense_reports table
Schema::create('expense_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('contributor_id')->constrained()->onDelete('cascade');
    $table->date('expense_date');
    $table->string('category', 50);
    $table->text('description');
    $table->decimal('amount_ht', 10, 2);
    $table->decimal('vat_rate', 5, 2)->default(20.0);
    $table->decimal('amount_ttc', 10, 2);
    $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
    $table->string('status', 20)->default('draft');
    $table->string('file_path')->nullable();
    $table->foreignId('validator_id')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('validated_at')->nullable();
    $table->text('validation_comment')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
    
    $table->index('status');
    $table->index('expense_date');
});

// Migration : add columns to orders table
Schema::table('orders', function (Blueprint $table) {
    $table->boolean('expenses_rebillable')->default(false);
    $table->decimal('expense_management_fee_rate', 5, 2)->default(0.0);
});
```

### Repositories
- `ExpenseReportRepository` :
  - `findByContributor(Contributor $contributor, array $filters = [])`
  - `findPending()`
  - `findByProject(Project $project)`
  - `findByOrder(Order $order)`
  - `calculateTotalByCategory(DateTimeInterface $start, DateTimeInterface $end)`
  - `calculateTotalRebillable(Order $order)`

### Services
- `ExpenseReportService` :
  - `create(array $data, User $user): ExpenseReport`
  - `submit(ExpenseReport $expense): void` (brouillon → en_attente)
  - `validate(ExpenseReport $expense, User $validator, ?string $comment): void`
  - `reject(ExpenseReport $expense, User $validator, string $comment): void`
  - `markAsPaid(ExpenseReport $expense, DateTimeInterface $paidAt, array $paymentData): void`
  - `calculateRebillableAmount(Order $order): float`

- `ExpenseReportNotificationService` :
  - `notifyValidation(ExpenseReport $expense): void`
  - `notifyRejection(ExpenseReport $expense): void`
  - `notifyPayment(ExpenseReport $expense): void`

### Controllers
- `ExpenseReportController` (contributeur + manager)
- `ExpenseReportAccountingController` (comptabilité)

### Templates
- `expense_report/index.html.twig` (liste générale)
- `expense_report/mine.html.twig` (mes frais)
- `expense_report/pending.html.twig` (en attente validation)
- `expense_report/new.html.twig` (formulaire saisie)
- `expense_report/show.html.twig` (détail)
- `accounting/expenses_dashboard.html.twig` (dashboard comptabilité)

---

## 📋 Tests

### Tests unitaires
- `ExpenseReportServiceTest` : création, validation, rejet, paiement
- `ExpenseReportRepositoryTest` : requêtes d'agrégation
- Calculs de montants TTC et refacturables

### Tests fonctionnels
- `ExpenseReportControllerTest` : CRUD complet
- Workflow de validation
- Permissions par rôle

### Tests E2E
- Parcours contributeur : saisie → soumission → validation → paiement
- Parcours comptable : dashboard → validation masse → export

---

## 📊 Estimation

### Développement
- **Entités et migrations** : 1 jour
- **CRUD et formulaires** : 2 jours
- **Workflow de validation** : 1 jour
- **Dashboard comptabilité** : 2 jours
- **Intégration devis/factures** : 1 jour
- **Rapports et exports** : 1 jour
- **Tests** : 1 jour
- **Documentation** : 0.5 jour

**Total** : ~9-10 jours

### Dépendances
- Module de facturation (Lot 9) pour intégration complète
- Upload de fichiers (existant dans Documents)

---

## 🎯 Positionnement dans la roadmap

### Suggestion de priorisation
- **Priorité** : Moyenne-Haute
- **Phase** : Phase 1 (Consolidation) ou Phase 2 (Analytics)
- **Placement** : Après Lot 9 (Module de Facturation)
- **Nouveau numéro** : **Lot 9.5 : Gestion des Notes de Frais**

### Justification
- Fonctionnalité complémentaire au module de facturation
- Impact direct sur la trésorerie et la gestion comptable
- Demande fréquente des contributeurs terrain
- Permet une meilleure traçabilité des coûts projet

---

## 💡 Évolutions futures

### Phase 1 (optionnel)
- Scanner de tickets via mobile (OCR pour extraction automatique)
- Validation automatique si montant < seuil (ex: < 50€)
- Barème kilométrique pour frais véhicule personnel

### Phase 2
- Intégration avec banques (import relevés bancaires)
- Détection automatique de doublons
- Analytics prédictifs : budget frais par projet
- Export vers logiciels comptables (Sage, Cegid)

### Phase 3
- Application mobile dédiée (scan tickets, saisie vocale)
- IA pour catégorisation automatique des frais
- Comparaison avec budgets prévisionnels

---

## 📝 Notes importantes

- **Justificatifs obligatoires** : À paramétrer par catégorie ou montant seuil
- **Politique de frais** : Document à créer définissant les règles de remboursement
- **Devises** : À gérer si frais internationaux (hors scope initial)
- **Avances** : Gestion des avances sur frais (hors scope initial)
- **Carte bancaire entreprise** : Intégration possible en phase 2

---

**Document créé le** : 8 décembre 2025
**Statut** : Proposition - À valider
**Prochaine action** : Intégration dans la roadmap 2025
