# Module de Gestion des Abonnements SaaS

Ce module permet de gérer les abonnements aux services SaaS (Software as a Service) de l'entreprise, avec suivi des coûts, renouvellements automatiques et alertes.

## Vue d'ensemble

Le module est conçu comme un système autonome pour :
- **Suivre les abonnements** aux services SaaS (Google Workspace, Slack, GitHub, etc.)
- **Gérer les fournisseurs** et services disponibles
- **Automatiser les renouvellements** mensuels ou annuels
- **Calculer les coûts** totaux (mensuels et annuels)
- **Alerter** sur les renouvellements à venir

## Architecture

### Entités

#### 1. SaasProvider (Fournisseur)
Représente un fournisseur de services SaaS (Google, Microsoft, Adobe, etc.).

**Fichier:** `src/Entity/SaasProvider.php`

**Propriétés principales:**
- `name` - Nom du fournisseur
- `website` - Site web
- `contactEmail` - Email de contact
- `contactPhone` - Téléphone
- `logoUrl` - URL du logo
- `active` - Statut actif/inactif
- `services` - Collection des services proposés

#### 2. SaasService (Service)
Représente un service SaaS spécifique (Slack Premium, GitHub Team, etc.).

**Fichier:** `src/Entity/SaasService.php`

**Propriétés principales:**
- `name` - Nom du service
- `description` - Description
- `provider` - Fournisseur (nullable pour souscription directe)
- `category` - Catégorie (Communication, Productivité, etc.)
- `serviceUrl` - URL du service
- `logoUrl` - URL du logo
- `defaultMonthlyPrice` - Prix mensuel par défaut
- `defaultYearlyPrice` - Prix annuel par défaut
- `currency` - Devise (EUR, USD, GBP)
- `subscriptions` - Collection des abonnements

#### 3. SaasSubscription (Abonnement)
Représente un abonnement actif à un service SaaS.

**Fichier:** `src/Entity/SaasSubscription.php`

**Constantes:**
```php
// Périodes de facturation
const BILLING_MONTHLY = 'monthly';  // Mensuel
const BILLING_YEARLY = 'yearly';    // Annuel

// Statuts
const STATUS_ACTIVE = 'active';         // Actif
const STATUS_CANCELLED = 'cancelled';   // Annulé
const STATUS_SUSPENDED = 'suspended';   // Suspendu
const STATUS_EXPIRED = 'expired';       // Expiré
```

**Propriétés principales:**
- `service` - Service SaaS auquel on est abonné
- `customName` - Nom personnalisé (optionnel)
- `billingPeriod` - Périodicité (monthly/yearly)
- `price` - Prix par période
- `currency` - Devise
- `quantity` - Nombre de licences/utilisateurs
- `startDate` - Date de début
- `endDate` - Date de fin (null si actif)
- `nextRenewalDate` - Date du prochain renouvellement
- `lastRenewalDate` - Date du dernier renouvellement
- `autoRenewal` - Renouvellement automatique activé
- `status` - Statut de l'abonnement
- `externalReference` - Référence externe (numéro de commande, etc.)
- `notes` - Notes internes

**Méthodes utiles:**
```php
// Calculs de coûts
getMonthlyCost(): float          // Coût mensuel normalisé
getYearlyCost(): float           // Coût annuel normalisé

// Gestion du renouvellement
calculateNextRenewalDate(?DateTimeInterface $fromDate = null): DateTimeInterface
renew(): self                    // Renouvelle l'abonnement
shouldBeRenewed(): bool          // Vérifie si doit être renouvelé

// Gestion du cycle de vie
cancel(?DateTimeInterface $endDate = null): self
suspend(): self
reactivate(): self
isActive(): bool

// Affichage
getDisplayName(): string         // Nom personnalisé ou nom du service
```

### Repositories

#### SaasProviderRepository
**Fichier:** `src/Repository/SaasProviderRepository.php`

**Méthodes:**
- `findActive()` - Fournisseurs actifs
- `searchByName(string $search)` - Recherche par nom
- `getProvidersWithServiceCount()` - Fournisseurs avec compteur de services

#### SaasServiceRepository
**Fichier:** `src/Repository/SaasServiceRepository.php`

**Méthodes:**
- `findActive()` - Services actifs
- `findByProvider(SaasProvider $provider)` - Services d'un fournisseur
- `findByCategory(string $category)` - Services par catégorie
- `searchByName(string $search)` - Recherche par nom
- `findAllCategories()` - Liste des catégories
- `getServicesWithSubscriptionCount()` - Services avec compteur d'abonnements

#### SaasSubscriptionRepository
**Fichier:** `src/Repository/SaasSubscriptionRepository.php`

**Méthodes principales:**
```php
// Recherche et filtrage
findActive(): array                          // Abonnements actifs
findByStatus(string $status): array          // Par statut
findByService(SaasService $service): array   // Par service
searchByName(string $search): array          // Recherche

// Renouvellements et alertes
findDueForRenewal(): array                   // À renouveler maintenant
findExpiringInDays(int $days): array         // Expirant dans N jours

// Calculs et statistiques
calculateTotalMonthlyCost(): float           // Coût mensuel total
calculateTotalYearlyCost(): float            // Coût annuel total
countActive(): int                           // Nombre d'abonnements actifs
getStatsByStatus(): array                    // Stats par statut
getStatsByBillingPeriod(): array             // Stats par période
```

### Controllers

#### SaasController
**Fichier:** `src/Controller/SaasController.php`
**Route:** `/saas`
**Rôle requis:** `ROLE_ADMIN`

**Actions:**
- `dashboard()` - Dashboard avec KPIs et alertes

#### SaasSubscriptionController
**Fichier:** `src/Controller/SaasSubscriptionController.php`
**Route:** `/saas/subscriptions`
**Rôle requis:** `ROLE_ADMIN`

**Actions:**
- `index()` - Liste paginée avec filtres
- `new()` - Créer un nouvel abonnement
- `show($id)` - Détail d'un abonnement
- `edit($id)` - Modifier un abonnement
- `delete($id)` - Supprimer un abonnement
- `renew($id)` - Renouveler manuellement
- `cancel($id)` - Annuler un abonnement
- `suspend($id)` - Suspendre un abonnement
- `reactivate($id)` - Réactiver un abonnement

### Formulaires

#### SaasSubscriptionType
**Fichier:** `src/Form/SaasSubscriptionType.php`

Formulaire complet pour créer/modifier un abonnement avec tous les champs nécessaires et validations.

### Templates

**Structure:**
```
templates/saas/
├── dashboard.html.twig                    # Dashboard principal
└── subscription/
    ├── index.html.twig                    # Liste des abonnements
    ├── show.html.twig                     # Détail d'un abonnement
    ├── new.html.twig                      # Créer un abonnement
    ├── edit.html.twig                     # Modifier un abonnement
    └── _form.html.twig                    # Formulaire partagé
```

### Commandes Console

#### app:saas:renew-subscriptions
**Fichier:** `src/Command/SaasRenewSubscriptionsCommand.php`

Renouvelle automatiquement les abonnements échus avec auto-renewal activé.

**Usage:**
```bash
# Renouveler les abonnements échus
docker compose exec app php bin/console app:saas:renew-subscriptions

# Mode dry-run (simulation sans modification)
docker compose exec app php bin/console app:saas:renew-subscriptions --dry-run
```

**Recommandation:** Ajouter cette commande au scheduler Symfony pour exécution quotidienne.

## Utilisation

### Accès au module

Le module est accessible via le menu "Gestion > Abonnements SaaS" dans la sidebar (réservé aux `ROLE_ADMIN`).

**URLs principales:**
- Dashboard: `/saas`
- Liste des abonnements: `/saas/subscriptions`
- Nouvel abonnement: `/saas/subscriptions/new`
- Détail: `/saas/subscriptions/{id}`

### Workflow typique

1. **Créer les fournisseurs et services** (optionnel pour les services directs)
2. **Créer un abonnement:**
   - Sélectionner le service
   - Choisir la période de facturation (mensuel/annuel)
   - Définir le prix et la quantité
   - Configurer les dates
   - Activer le renouvellement automatique si souhaité

3. **Suivre les abonnements:**
   - Dashboard avec KPIs (coûts mensuels/annuels, nombre d'abonnements)
   - Alertes pour les renouvellements à venir (30 jours)
   - Liste filtrée et paginée

4. **Gérer le cycle de vie:**
   - Renouveler manuellement ou automatiquement (via commande)
   - Suspendre temporairement
   - Annuler définitivement
   - Réactiver si besoin

### Calcul des coûts

Le système normalise automatiquement les coûts :
- **Abonnement mensuel:** Prix × Quantité = Coût mensuel
- **Abonnement annuel:** (Prix × Quantité) / 12 = Coût mensuel
- **Coût annuel:** Coût mensuel × 12

### Renouvellements automatiques

Les abonnements avec `autoRenewal = true` sont renouvelés automatiquement :
- **Mensuel:** Le même jour du mois suivant
- **Annuel:** À la date anniversaire

Le renouvellement :
1. Copie `nextRenewalDate` vers `lastRenewalDate`
2. Calcule la nouvelle `nextRenewalDate` (+1 mois ou +1 an)
3. Conserve le statut `active`

## Exemple de configuration scheduler

Pour automatiser les renouvellements, ajouter dans `src/Scheduler/ScheduleProvider.php`:

```php
use App\Command\SaasRenewSubscriptionsCommand;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;

#[AsSchedule('default')]
class ScheduleProvider
{
    public function __invoke(): iterable
    {
        // Renouvellement automatique des abonnements SaaS (tous les jours à 6h)
        yield RecurringMessage::cron(
            '0 6 * * *',
            new AsMessageCommand(SaasRenewSubscriptionsCommand::class)
        );
    }
}
```

## Base de données

### Tables créées

**Migration:** `migrations/Version20251220172900.php`

**Tables:**
- `saas_providers` - Fournisseurs de services
- `saas_services` - Services disponibles
- `saas_subscriptions` - Abonnements actifs

**Indexes:**
- `idx_saas_service_provider` - Sur `saas_services.provider_id`
- `idx_saas_subscription_service` - Sur `saas_subscriptions.service_id`
- `idx_saas_subscription_status` - Sur `saas_subscriptions.status`
- `idx_saas_subscription_renewal` - Sur `saas_subscriptions.next_renewal_date`

**Contraintes:**
- FK `saas_services.provider_id` → `saas_providers.id` (ON DELETE SET NULL)
- FK `saas_subscriptions.service_id` → `saas_services.id` (ON DELETE RESTRICT)

## Sécurité

- **Accès:** Réservé aux utilisateurs avec `ROLE_ADMIN`
- **CSRF:** Toutes les actions de modification sont protégées par token CSRF
- **Validation:** Formulaires avec contraintes de validation Symfony

## Évolutions futures possibles

- **Notifications email** avant expiration des abonnements
- **Historique** des renouvellements et modifications
- **Budgets** et alertes de dépassement
- **Import/Export** CSV des abonnements
- **Rapports** détaillés par fournisseur, catégorie, période
- **Intégration** avec APIs des fournisseurs (récupération automatique des factures)
- **Multi-devise** avec conversion automatique
- **Approbation workflow** pour nouveaux abonnements

## Maintenance

### Commandes utiles

```bash
# Lister les abonnements à renouveler
docker compose exec app php bin/console app:saas:renew-subscriptions --dry-run

# Renouveler les abonnements
docker compose exec app php bin/console app:saas:renew-subscriptions

# Vérifier le schéma de la base
docker compose exec app php bin/console doctrine:schema:validate

# Voir les statistiques
docker compose exec db mariadb -usymfony -psymfony hotones -e "
    SELECT
        status,
        COUNT(*) as count,
        SUM(price * quantity) as total
    FROM saas_subscriptions
    GROUP BY status;
"
```

### Troubleshooting

**Problème:** Les abonnements ne sont pas renouvelés automatiquement
**Solution:** Vérifier que la commande est configurée dans le scheduler ou dans un cron

**Problème:** Erreur lors du calcul des coûts
**Solution:** Vérifier que le champ `price` est un nombre valide (utilise type DECIMAL en base)

**Problème:** Les dates de renouvellement sont incorrectes
**Solution:** Vérifier la configuration du timezone dans `.env` et la méthode `calculateNextRenewalDate()`

## Support

Pour toute question ou problème :
1. Consulter cette documentation
2. Vérifier les logs Symfony (`var/log/dev.log` ou `var/log/prod.log`)
3. Contacter l'équipe technique
