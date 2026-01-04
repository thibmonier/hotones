# Native PHP Enums - Candidats pour Migration (Lot 0.2.2)

**Date:** 2026-01-04
**Contexte:** Migration PHP 8.5 Features - Native Enums
**Source:** Doctrine Doctor Report
**Objectif:** Type safety, IDE autocomplete, prévention valeurs invalides

---

## 📊 Résumé Exécutif

**6 champs identifiés** pour conversion en native PHP 8.1+ enums :

| # | Entité | Champ | Valeurs Actuelles | Distinctes | Unicité |
|---|--------|-------|-------------------|------------|---------|
| 1 | Contributor | `$gender` | male, female, other | 1-3 | 1.7% |
| 2 | Project | `$projectType` | forfait, regie | 2 | 2.7% |
| 3 | Order | `$contractType` | forfait, regie | 2 | 2.8% |
| 4 | OrderLine | `$type` | service, purchase, fixed_amount | 1-3 | 2.1% |
| 5 | ProjectEvent | `$eventType` | (à identifier) | 1-? | 1.3% |
| 6 | Planning | `$status` | planned, confirmed, cancelled | 2-3 | 2.1% |

**Bénéfices attendus:**
- ✅ Type safety (erreurs de compilation au lieu de runtime)
- ✅ IDE autocomplete & refactoring
- ✅ Élimination des magic strings
- ✅ Documentation self-service du code
- ✅ Validation automatique par PHP

---

## 🔍 Analyse Détaillée des Candidats

### 1. Contributor::$gender

**État Actuel:**
```php
#[ORM\Column(type: 'string', length: 10, nullable: true)]
public ?string $gender = null; // 'male', 'female', 'other'
```

**Usage:**
```php
// Dans Contributor.php:236
public function getGenderLabel(): string
{
    return match ($this->gender) {
        'male'   => 'Homme',
        'female' => 'Femme',
        default  => 'Autre',
    };
}
```

**✅ Proposition: Enum `Gender`**

```php
<?php

namespace App\Enum;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::MALE => 'Homme',
            self::FEMALE => 'Femme',
            self::OTHER => 'Autre',
        };
    }

    /**
     * Get icon for UI display.
     */
    public function icon(): string
    {
        return match($this) {
            self::MALE => 'bi-gender-male',
            self::FEMALE => 'bi-gender-female',
            self::OTHER => 'bi-gender-ambiguous',
        };
    }
}
```

**Après Migration:**
```php
#[ORM\Column(type: 'string', length: 10, nullable: true, enumType: Gender::class)]
public ?Gender $gender = null;

// Usage simplifié:
echo $contributor->gender?->label(); // "Homme"
```

**Impact:**
- Fichiers à modifier: `Contributor.php`, possiblement formulaires
- Breaking change: Non (même représentation DB)
- Tests à adapter: Constructeurs de tests utilisant 'male'/'female'

---

### 2. Project::$projectType

**État Actuel:**
```php
#[ORM\Column(type: 'string', length: 20)]
public string $projectType = 'forfait'; // forfait, regie
```

**✅ Proposition: Enum `ProjectType`**

```php
<?php

namespace App\Enum;

enum ProjectType: string
{
    case FORFAIT = 'forfait'; // Fixed-price project
    case REGIE = 'regie';     // Time & materials

    public function label(): string
    {
        return match($this) {
            self::FORFAIT => 'Forfait',
            self::REGIE => 'Régie',
        };
    }

    public function isBillableByDay(): bool
    {
        return $this === self::REGIE;
    }

    public function isBillableByDeliverable(): bool
    {
        return $this === self::FORFAIT;
    }
}
```

**Après Migration:**
```php
#[ORM\Column(type: 'string', length: 20, enumType: ProjectType::class)]
public ProjectType $projectType = ProjectType::FORFAIT;
```

**Impact:**
- **HIGH** : Champ utilisé dans de nombreux endroits (analytics, dashboards, filters)
- Fichiers à modifier: `Project.php`, DashboardReadService, filtres, templates
- Breaking change: Non (DB reste identique)
- Tests impactés: Nombreux tests création projet

---

### 3. Order::$contractType

**État Actuel:**
```php
#[ORM\Column(type: 'string', length: 20, options: ['default' => 'forfait'])]
public string $contractType = 'forfait'; // forfait, regie
```

**✅ Proposition: Réutiliser `ProjectType`**

```php
#[ORM\Column(type: 'string', length: 20, enumType: ProjectType::class)]
public ProjectType $contractType = ProjectType::FORFAIT;
```

**Rationale:** Même sémantique que Project::$projectType → réutiliser l'enum

**Impact:**
- Fichiers à modifier: `Order.php`, formulaires devis
- Breaking change: Non
- Cohérence accrue: Order et Project utilisent le même enum

---

### 4. OrderLine::$type

**État Actuel:**
```php
#[ORM\Column(type: 'string', length: 50)]
public string $type = 'service'; // service, purchase, fixed_amount
```

**✅ Proposition: Enum `OrderLineType`**

```php
<?php

namespace App\Enum;

enum OrderLineType: string
{
    case SERVICE = 'service';             // Service line (days * TJM)
    case PURCHASE = 'purchase';           // Purchase/expense
    case FIXED_AMOUNT = 'fixed_amount';   // Fixed price line

    public function label(): string
    {
        return match($this) {
            self::SERVICE => 'Prestation (forfait/régie)',
            self::PURCHASE => 'Achat / Frais',
            self::FIXED_AMOUNT => 'Montant fixe',
        };
    }

    public function requiresProfile(): bool
    {
        return $this === self::SERVICE;
    }

    public function requiresDirectAmount(): bool
    {
        return in_array($this, [self::PURCHASE, self::FIXED_AMOUNT], true);
    }

    public function icon(): string
    {
        return match($this) {
            self::SERVICE => 'bi-person-workspace',
            self::PURCHASE => 'bi-cart',
            self::FIXED_AMOUNT => 'bi-cash-coin',
        };
    }
}
```

**Après Migration:**
```php
#[ORM\Column(type: 'string', length: 50, enumType: OrderLineType::class)]
public OrderLineType $type = OrderLineType::SERVICE;
```

**Impact:**
- Fichiers à modifier: `OrderLine.php`, logique validation formulaires devis
- Breaking change: Non
- Logique métier à migrer: Conditions `if ($line->type === 'service')` → `if ($line->type === OrderLineType::SERVICE)`

---

### 5. ProjectEvent::$eventType

**État Actuel:**
```php
#[ORM\Column(type: Types::STRING, length: 50)]
private ?string $eventType = null;
```

**⚠️ Valeurs à identifier** - Aucun commentaire dans le code

**Action requise:**
```sql
-- Requête pour identifier les valeurs distinctes
SELECT DISTINCT event_type, COUNT(*) as count
FROM project_events
GROUP BY event_type
ORDER BY count DESC;
```

**✅ Proposition: Enum `ProjectEventType` (à compléter)**

```php
<?php

namespace App\Enum;

enum ProjectEventType: string
{
    // À compléter après analyse DB
    // Exemples possibles:
    // case CREATED = 'created';
    // case STATUS_CHANGED = 'status_changed';
    // case MILESTONE_REACHED = 'milestone_reached';
    // case BUDGET_UPDATED = 'budget_updated';

    public function label(): string
    {
        return match($this) {
            // À implémenter
        };
    }

    public function icon(): string
    {
        return match($this) {
            // À implémenter
        };
    }

    public function severity(): string
    {
        return match($this) {
            // info, warning, success, danger
        };
    }
}
```

**Impact:**
- **BLOQUÉ** : Nécessite analyse DB d'abord
- Fichiers à modifier: `ProjectEvent.php` + possibles listeners/subscribers

---

### 6. Planning::$status

**État Actuel:**
```php
#[ORM\Column(type: 'string', length: 20)]
public string $status = 'planned'; // planned, confirmed, cancelled
```

**✅ Proposition: Enum `PlanningStatus`**

```php
<?php

namespace App\Enum;

enum PlanningStatus: string
{
    case PLANNED = 'planned';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PLANNED => 'Planifié',
            self::CONFIRMED => 'Confirmé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PLANNED => 'badge-warning',
            self::CONFIRMED => 'badge-success',
            self::CANCELLED => 'badge-danger',
        };
    }

    public function isActive(): bool
    {
        return $this !== self::CANCELLED;
    }

    public function canBeModified(): bool
    {
        return $this === self::PLANNED;
    }
}
```

**Après Migration:**
```php
#[ORM\Column(type: 'string', length: 20, enumType: PlanningStatus::class)]
public PlanningStatus $status = PlanningStatus::PLANNED;
```

**Impact:**
- Fichiers à modifier: `Planning.php`, PlanningController, templates FullCalendar
- Breaking change: Non
- Tests à adapter: Tests création planning

---

## 📁 Structure Proposée

```
src/
└── Enum/
    ├── Gender.php
    ├── ProjectType.php
    ├── OrderLineType.php
    ├── ProjectEventType.php
    └── PlanningStatus.php
```

---

## ✅ Plan de Migration

### Phase 1: Création Enums (1-2h)

1. **Créer les 5 enums validés**
   - [ ] `src/Enum/Gender.php`
   - [ ] `src/Enum/ProjectType.php`
   - [ ] `src/Enum/OrderLineType.php`
   - [ ] `src/Enum/PlanningStatus.php`
   - [ ] Identifier valeurs `ProjectEventType` via SQL
   - [ ] `src/Enum/ProjectEventType.php`

### Phase 2: Migration Entités (2-3h)

2. **Modifier les 6 entités**
   - [ ] `Contributor::$gender` → `?Gender`
   - [ ] `Project::$projectType` → `ProjectType`
   - [ ] `Order::$contractType` → `ProjectType` (réutilisation)
   - [ ] `OrderLine::$type` → `OrderLineType`
   - [ ] `ProjectEvent::$eventType` → `ProjectEventType`
   - [ ] `Planning::$status` → `PlanningStatus`

3. **Ajouter `enumType` dans ORM mappings**
   ```php
   #[ORM\Column(enumType: Gender::class)]
   public ?Gender $gender = null;
   ```

### Phase 3: Migration Code Métier (3-4h)

4. **Remplacer magic strings**
   ```php
   // AVANT
   if ($project->projectType === 'forfait') { ... }

   // APRÈS
   if ($project->projectType === ProjectType::FORFAIT) { ... }
   ```

5. **Utiliser méthodes enum**
   ```php
   // AVANT
   $label = match($project->projectType) {
       'forfait' => 'Forfait',
       'regie' => 'Régie',
   };

   // APRÈS
   $label = $project->projectType->label();
   ```

### Phase 4: Templates Twig (1-2h)

6. **Adapter templates**
   ```twig
   {# AVANT #}
   {% if project.projectType == 'forfait' %}

   {# APRÈS - Option 1: Accès direct (Twig gère les enums) #}
   {% if project.projectType.value == 'forfait' %}

   {# APRÈS - Option 2: Méthode helper #}
   {% if project.projectType.name == 'FORFAIT' %}

   {# APRÈS - Option 3: Labels #}
   {{ project.projectType.label() }}
   ```

### Phase 5: Tests (2-3h)

7. **Adapter fixtures et tests**
   ```php
   // AVANT
   $project = new Project();
   $project->setProjectType('forfait');

   // APRÈS
   $project = new Project();
   $project->projectType = ProjectType::FORFAIT;
   ```

8. **Vérifier tous les tests**
   ```bash
   docker compose exec app composer test
   ```

### Phase 6: Validation (1h)

9. **Vérifications finales**
   - [ ] PHPStan niveau 9 : 0 erreurs
   - [ ] Tests : 100% pass
   - [ ] Code style : conforme
   - [ ] Manuel: Tester création projet, devis, planning

---

## 📊 Estimation Totale

| Phase | Durée | Risque |
|-------|-------|--------|
| 1. Création enums | 1-2h | 🟢 LOW |
| 2. Migration entités | 2-3h | 🟡 MEDIUM |
| 3. Migration code métier | 3-4h | 🟡 MEDIUM |
| 4. Templates Twig | 1-2h | 🟡 MEDIUM |
| 5. Tests | 2-3h | 🟢 LOW |
| 6. Validation | 1h | 🟢 LOW |
| **TOTAL** | **10-15h** | **🟡 MEDIUM** |

**Répartition:** ~2 jours développeur

---

## ⚠️ Risques & Mitigation

### Risque 1: Breaking Changes Sérialization API
**Impact:** Endpoints API retournant des string deviennent des objets
**Mitigation:**
- Vérifier serializer Symfony (devrait convertir auto en string)
- Ajouter `#[Groups]` si nécessaire
- Tests API avant déploiement

### Risque 2: Formulaires Symfony
**Impact:** ChoiceType avec enum nécessite adaptation
**Mitigation:**
```php
// Form Type adapté aux enums
$builder->add('projectType', EnumType::class, [
    'class' => ProjectType::class,
    'choice_label' => fn(ProjectType $type) => $type->label(),
]);
```

### Risque 3: Requêtes DQL/SQL
**Impact:** Comparaisons DQL avec enums
**Mitigation:**
```php
// Doctrine gère automatiquement
$qb->andWhere('p.projectType = :type')
   ->setParameter('type', ProjectType::FORFAIT); // OK!
```

### Risque 4: Templates Twig
**Impact:** Comparaisons string vs enum
**Mitigation:**
- Utiliser `.value` ou `.name`
- Créer Twig extension si besoin
```twig
{# Extension Twig helper #}
{% if project.projectType is same as(constant('App\\Enum\\ProjectType::FORFAIT')) %}
```

---

## 🎯 Bénéfices Mesurables

### Avant (Magic Strings)
```php
// ❌ Erreur silencieuse - typo non détectée
$project->projectType = 'forfa1t'; // Oops!

// ❌ Aucune autocomplete
if ($project->projectType === /* quoi déjà? */)

// ❌ Refactoring impossible
// "Renommer 'forfait' en 'fixed_price'" → recherche/remplacement manuel risqué
```

### Après (Native Enums)
```php
// ✅ Erreur de compilation - impossible de builder
$project->projectType = ProjectType::FORFA1T; // PHP Error!

// ✅ Autocomplete IDE complet
if ($project->projectType === ProjectType::| // IDE propose FORFAIT, REGIE

// ✅ Refactoring sûr
// "Rename enum case" → IDE refactor toutes les occurrences
```

---

## 📚 Références

- [PHP 8.1 Enums RFC](https://wiki.php.net/rfc/enumerations)
- [Doctrine Enum Type](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/mysql-enums.html)
- [Symfony EnumType](https://symfony.com/doc/current/reference/forms/types/enum.html)
- [WARP.md - Roadmap Lot 0.2](../../WARP.md)

---

**Prochaine action:** Identifier valeurs `ProjectEvent::$eventType` via requête SQL puis créer les 5 enums
