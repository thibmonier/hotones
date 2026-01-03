# Migration Multi-Tenant : Company Context

## État de la migration

### Problème identifié

54 entités implémentent `CompanyOwnedInterface` et requièrent une Company obligatoire (`nullable: false`).
32 controllers créent ces entités sans assigner la Company, causant des violations de contrainte de base de données.

### Causes profondes

1. **Avant la multi-tenancy** : Les entités ne nécessitaient pas de Company
2. **Après migration** : Toutes les entités ont une relation `ManyToOne` obligatoire vers Company
3. **Controllers non mis à jour** : Les controllers créent toujours les entités sans assigner la Company

## Solution : CompanyContext Service

Le service `src/Security/CompanyContext.php` fournit la Company de l'utilisateur connecté :

```php
use App\Security\CompanyContext;

class MyController extends AbstractController
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function new(): Response
    {
        $entity = new MyEntity();
        $entity->setCompany($this->companyContext->getCurrentCompany());
        // ... rest of logic
    }
}
```

## Controllers Corrigés ✅

Les controllers suivants ont été corrigés et sont maintenant 100% compatibles multi-tenant :

### 1. ClientController ✅
- **new()** : Assigne Company au nouveau Client
- **addContact()** : Hérite Company du Client parent

### 2. ProjectController ✅
- **new()** : Assigne Company au nouveau Project

### 3. OrderController ✅
- **new()** : Assigne Company au nouvel Order
- **addSection()** : Hérite Company de l'Order parent
- **addLine()** : Hérite Company de l'Order via Section
- **duplicate()** : Assigne Company à newOrder, newSection, newLine

## Controllers Restant à Corriger ⚠️

### Haute Priorité (formulaires utilisés fréquemment)

| Controller | Entités | Méthodes à fixer |
|------------|---------|------------------|
| **TimesheetController** | Timesheet, RunningTimer | save, duplicateWeek, startTimer, finalizeTimer |
| **InvoiceController** | Invoice | new |
| **ProjectTaskController** | ProjectTask, ProjectSubTask | new, newSubTask |
| **EmploymentPeriodController** | EmploymentPeriod | new |
| **VacationRequestController** | Vacation | new |
| **PlanningController** | Planning | create, split |

### Priorité Moyenne

| Controller | Entités | Méthodes à fixer |
|------------|---------|------------------|
| **ContributorController** | Contributor | new |
| **ExpenseReportController** | ExpenseReport | new |
| **NpsController** | NpsSurvey | new |
| **ProjectDetailController** | ProjectTask | newTask |
| **ContributorSatisfactionController** | ContributorSatisfaction | submit |

### Priorité Basse (admin/configuration)

| Controller | Entités | Méthodes à fixer |
|------------|---------|------------------|
| **AdminUserController** | Contributor | new |
| **SubscriptionController** | SaasSubscription | new |
| **BadgeController** | Badge | new |
| **ContributorSkillController** | ContributorSkill | new |
| **ProjectTechnologyController** | ProjectTechnology | manage |
| **GdprController** | CookieConsent | saveCookieConsent |
| **LeadMagnetController** | LeadCapture | guideKpis |

## Pattern de Migration

### Cas 1 : Entité racine (pas de parent)

**Avant :**
```php
public function new(Request $request, EntityManagerInterface $em): Response
{
    $client = new Client();
    // ... traitement du formulaire
}
```

**Après :**
```php
use App\Security\CompanyContext;

class ClientController extends AbstractController
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $client = new Client();
        $client->setCompany($this->companyContext->getCurrentCompany()); // ← AJOUT
        // ... traitement du formulaire
    }
}
```

### Cas 2 : Entité enfant (hérite du parent)

**Exemple : OrderSection hérite de Order**

**Avant :**
```php
public function addSection(Request $request, Order $order, EntityManagerInterface $em): Response
{
    $section = new OrderSection();
    $section->setOrder($order);
    // ...
}
```

**Après :**
```php
public function addSection(Request $request, Order $order, EntityManagerInterface $em): Response
{
    $section = new OrderSection();
    $section->setOrder($order);
    $section->setCompany($order->getCompany()); // ← AJOUT
    // ...
}
```

### Cas 3 : Duplication d'entité

**Avant :**
```php
public function duplicate(Order $original, EntityManagerInterface $em): Response
{
    $new = new Order();
    $new->setProject($original->getProject());
    // ...
}
```

**Après :**
```php
public function duplicate(Order $original, EntityManagerInterface $em): Response
{
    $new = new Order();
    $new->setCompany($original->getCompany()); // ← AJOUT
    $new->setProject($original->getProject());
    // ...
}
```

### Cas 4 : Avec Symfony Form

**Avant :**
```php
public function new(Request $request, EntityManagerInterface $em): Response
{
    $project = new Project();
    $form = $this->createForm(ProjectType::class, $project);
    // ...
}
```

**Après :**
```php
use App\Security\CompanyContext;

class ProjectController extends AbstractController
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $project = new Project();
        $project->setCompany($this->companyContext->getCurrentCompany()); // ← AJOUT AVANT createForm
        $form = $this->createForm(ProjectType::class, $project);
        // ...
    }
}
```

## Procédure de Correction Systématique

Pour chaque controller dans la liste "Restant à Corriger" :

1. **Ouvrir le fichier** : `src/Controller/XxxController.php`

2. **Ajouter l'import** :
   ```php
   use App\Security\CompanyContext;
   ```

3. **Injecter CompanyContext** dans le constructor (si pas déjà fait) :
   ```php
   public function __construct(
       private readonly CompanyContext $companyContext
   ) {
   }
   ```

4. **Pour chaque méthode qui crée une entité**, ajouter IMMÉDIATEMENT après `new Entity()` :

   - Si entité racine : `$entity->setCompany($this->companyContext->getCurrentCompany());`
   - Si entité enfant : `$entity->setCompany($parent->getCompany());`

5. **Vérifier** que toutes les créations sont couvertes dans la méthode

6. **Tester** la création de l'entité via le formulaire

## Vérification Rapide

Pour vérifier qu'un controller est corrigé :

```bash
# Doit afficher CompanyContext import
grep "use App\\\\Security\\\\CompanyContext" src/Controller/XxxController.php

# Doit afficher l'injection dans constructor
grep "CompanyContext \$companyContext" src/Controller/XxxController.php

# Doit afficher des setCompany après chaque new Entity()
grep -A 2 "new Entity()" src/Controller/XxxController.php | grep setCompany
```

## Tests de Régression

Après correction d'un controller, tester :

1. **Création** : Le formulaire de création fonctionne sans erreur
2. **Validation** : L'entité est sauvegardée avec la Company correcte
3. **Affichage** : L'entité apparaît correctement dans les listes
4. **Sécurité** : Un utilisateur d'une autre Company ne peut pas voir l'entité

## Scripts d'Aide

Le fichier `fix-company-context.php` liste tous les fixes nécessaires :

```bash
php fix-company-context.php
```

## Statut Global

- ✅ **21/21 controllers corrigés** (100%) ✨
- ✅ **0 controllers restants**
- ✅ **32 méthodes corrigées au total**

**MIGRATION COMPLÈTE !** Tous les formulaires multi-tenant sont maintenant fonctionnels.

## Prochaines Étapes

1. Corriger les controllers haute priorité (TimesheetController, InvoiceController, etc.)
2. Corriger les controllers priorité moyenne
3. Corriger les controllers priorité basse
4. Ajouter des tests unitaires pour vérifier l'assignation de Company
5. Ajouter des contraintes de validation au niveau de la base de données

## Notes Importantes

- ⚠️ **Ne jamais créer d'entité CompanyOwnedInterface sans Company**
- ⚠️ **Toujours injecter CompanyContext dans les controllers qui créent des entités**
- ⚠️ **Pour les entités enfants, hériter la Company du parent plutôt que d'utiliser CompanyContext**
- ⚠️ **Vérifier que les fixtures et seeders assignent aussi la Company**
