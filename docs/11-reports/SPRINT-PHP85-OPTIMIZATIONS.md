# Sprint PHP 8.5 - Optimisations

**Date de création:** 2025-12-28
**Version PHP:** 8.5.1
**Durée estimée:** 4 semaines
**Objectif:** Moderniser le codebase pour tirer parti des fonctionnalités PHP 8.4+ et optimiser les performances

## Vue d'ensemble

Ce sprint vise à optimiser le code en exploitant les nouvelles fonctionnalités PHP 8.4/8.5 :
- **Property Hooks** - Réduction du boilerplate getter/setter
- **Asymmetric Visibility** - Meilleure encapsulation des propriétés
- **Performance** - Optimisation des requêtes N+1 et calculs coûteux
- **Type System** - Types plus précis pour de meilleures performances JIT
- **Enums** - Remplacement des constantes de classe

## Métriques de succès

- ✅ Réduction de ~500 lignes de code boilerplate
- ✅ Réduction de 50-70% des requêtes DB dans les services de métriques
- ✅ Amélioration de 30-50% des performances de calcul des KPI
- ✅ Couverture de tests maintenue à 100%

---

## Phase 1 : Property Hooks (Semaine 1)

**Objectif:** Réduire le boilerplate et améliorer la lisibilité
**Impact:** Réduction de ~500 lignes de code
**Effort:** 🟢 Faible (2-3 jours)

### Tâche 1.1 : Entities simples avec Property Hooks

**Fichiers:**
- `src/Entity/Timesheet.php` (lignes 77-165)
- `src/Entity/Order.php`
- `src/Entity/OrderLine.php`

**Avant:**
```php
#[ORM\Column(type: 'string', length: 180)]
private string $name;

public function getName(): string
{
    return $this->name;
}

public function setName(string $name): self
{
    $this->name = $name;
    return $this;
}
```

**Après:**
```php
#[ORM\Column(type: 'string', length: 180)]
public string $name {
    get => $this->name;
    set => $this->name = $value;
}
```

**Tests requis:**
- ✅ Tests unitaires existants doivent passer
- ✅ Vérifier la sérialisation Doctrine
- ✅ Tester les fixtures/factories

**Checklist:**
- [x] Timesheet.php - 6 propriétés simples (~90 lignes réduites)
- [x] Order.php - 8 propriétés simples (~120 lignes réduites)
- [x] OrderLine.php - 5 propriétés simples (~75 lignes réduites)
- [x] Client.php - 6 propriétés simples (~60 lignes réduites)
- [ ] Exécuter `composer test-unit`
- [ ] Exécuter `composer test-functional`

---

### Tâche 1.2 : Project.php avec Property Hooks

**Fichier:** `src/Entity/Project.php` (lignes 166-284)

**Propriétés candidates:**
- `name` (lignes 166-176)
- `client` (lignes 178-188)
- `purchasesAmount` (lignes 202-212)
- `startDate` (lignes 226-236)
- `endDate` (lignes 238-248)
- `status` (lignes 250-260)
- `projectType` (lignes 274-284)

**Réduction estimée:** ~120 lignes

**Checklist:**
- [x] Convertir les 7 propriétés en property hooks
- [ ] Tester l'hydratation Doctrine
- [ ] Vérifier les relations OneToMany/ManyToOne
- [ ] Tests ProjectRepository existants
- [ ] Tests ProfitabilityService

---

### Tâche 1.3 : Contributor.php avec computed properties

**Fichier:** `src/Entity/Contributor.php` (lignes 122-325)

**Propriétés simples (lignes 122-281):**
- firstName, lastName, email, phone, birthDate, gender, address, notes

**Propriétés calculées (lignes 287-325):**
```php
// Avant
public function getCjm(): ?string
{
    $period = $this->getRelevantEmploymentPeriod();
    return $period?->getCjm() ?? $this->cjm;
}

// Après avec hook
public string|null $cjm {
    get => $this->getRelevantEmploymentPeriod()?->getCjm() ?? $this->cjm;
}
```

**Réduction estimée:** ~180 lignes

**Checklist:**
- [x] 9 propriétés simples converties
- [x] 2 propriétés calculées (cjm, tjm) converties
- [ ] Tests ContributorService
- [ ] Tests StaffingMetricsCalculationService

---

### Tâche 1.4 : EmploymentPeriod.php avec type coercion

**Fichier:** `src/Entity/EmploymentPeriod.php` (lignes 82-128)

**Pattern spécifique:** Float vers String dans les setters

**Avant:**
```php
public function setSalary(?float $salary): self
{
    $this->salary = $salary !== null ? (string) $salary : null;
    return $this;
}
```

**Après:**
```php
public string|null $salary {
    get => $this->salary;
    set => $this->salary = $value !== null ? (string) $value : null;
}
```

**Propriétés concernées:** salary, cjm, tjm, annualGrossSalary (~40 lignes réduites)

**Checklist:**
- [x] Convertir 4 propriétés avec coercion
- [ ] Tests EmploymentPeriodRepository
- [ ] Vérifier le calcul des métriques

---

## Phase 2 : Asymmetric Visibility (Semaine 2)

**Objectif:** Améliorer l'encapsulation et les performances
**Impact:** 5-10% de gain de performance sur les propriétés fréquemment accédées
**Effort:** 🟢 Faible (2-3 jours)

### Tâche 2.1 : Propriétés ID en lecture seule

**Fichiers:** Toutes les entities (~50 fichiers)

**Avant:**
```php
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: 'integer')]
private ?int $id = null;

public function getId(): ?int
{
    return $this->id;
}
```

**Après:**
```php
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: 'integer')]
public private(set) ?int $id = null;
```

**Impact:** Le JIT peut inline l'accès direct à la propriété

**Checklist:**
- [ ] Script automatique pour convertir tous les ID
- [ ] User.php (ligne 57)
- [x] Project.php
- [x] Contributor.php
- [x] Timesheet.php
- [x] Order.php
- [x] OrderLine.php
- [x] EmploymentPeriod.php
- [x] Client.php
- [ ] ~50 autres entities
- [ ] Tests d'intégration complets

---

### Tâche 2.2 : Timestamps en lecture seule publique

**Pattern:** Les champs `createdAt` et `updatedAt` sont écrits une fois, lus souvent

**Avant:**
```php
#[ORM\Column(type: 'datetime_immutable')]
private ?\DateTimeImmutable $createdAt = null;

public function getCreatedAt(): ?\DateTimeImmutable
{
    return $this->createdAt;
}
```

**Après:**
```php
#[ORM\Column(type: 'datetime_immutable')]
public private(set) ?\DateTimeImmutable $createdAt = null;
```

**Checklist:**
- [ ] Automatiser la conversion des timestamps
- [ ] Vérifier les PrePersist/PreUpdate callbacks
- [ ] Tests sur les fixtures avec dates

---

## Phase 3 : Optimisations Performance (Semaine 3)

**Objectif:** Éliminer les requêtes N+1 et optimiser les calculs
**Impact:** 50-70% de réduction des requêtes DB, 30-50% plus rapide sur les KPI
**Effort:** 🟠 Moyen (4-5 jours)

### Tâche 3.1 : 🔴 CRITIQUE - StaffingMetricsCalculationService N+1

**Fichier:** `src/Service/StaffingMetricsCalculationService.php` (lignes 40-103)

**Problème actuel:**
```php
foreach ($periods as $period) {
    foreach ($contributors as $contributor) {
        // Requête DB pour chaque contributor × period
        $employmentPeriod = $this->getActiveEmploymentPeriod($contributor, $period);
        $metrics = $this->calculateMetricsForContributor(...);  // Encore plus de requêtes
    }
}
```

**Solution:**
```php
// Batch load TOUS les employment periods en une requête
$employmentPeriods = $this->employmentPeriodRepository
    ->findByContributorsAndDateRange($contributors, $startPeriod, $endPeriod);

// Index par contributor ID pour lookup O(1)
$periodsByContributor = [];
foreach ($employmentPeriods as $period) {
    $periodsByContributor[$period->getContributor()->getId()][] = $period;
}

foreach ($periods as $period) {
    $dimTime = $this->dimTimeCache[$period->format('Y-m-d')]
        ??= $this->getOrCreateDimTime($period);

    foreach ($contributors as $contributor) {
        $employmentPeriod = $this->findActivePeriod(
            $periodsByContributor[$contributor->getId()] ?? [],
            $period
        );
        // Plus de requête DB ici!
    }
}
```

**Gain estimé:** 50-70% de réduction des requêtes (de 100+ à ~5 requêtes)

**Checklist:**
- [x] Créer méthode `findByContributorsAndDateRange` dans repository
- [x] Implémenter identity map pour DimTime/DimProfile
- [x] Caching des résultats de calcul (Redis)
- [ ] Benchmark avant/après avec Blackfire
- [ ] Tests de charge avec 100+ contributors

---

### Tâche 3.2 : 🔴 CRITIQUE - Project.php computed values caching

**Fichier:** `src/Entity/Project.php` (lignes 460-797)

**Problème:** 34 appels bcmath, boucles imbriquées, appelés à chaque accès

**Méthodes coûteuses:**
- `getTotalSoldAmount()` (lignes 460-471) - Itère tous les orders
- `getTotalSoldDays()` (lignes 474-484) - Nested iteration
- `getTotalTasksSoldHours()` (lignes 491-501) - Filtre + boucle
- `getProjectContributorsWithHours()` (lignes 623-667) - Triple nested loop!

**Solution 1:** Cache transient (non persisté)
```php
#[ORM\Entity]
class Project
{
    // Champs cachés (non en DB)
    private ?string $cachedTotalSoldAmount = null;
    private ?string $cachedTotalSoldDays = null;

    public function getTotalSoldAmount(): string
    {
        if ($this->cachedTotalSoldAmount !== null) {
            return $this->cachedTotalSoldAmount;
        }

        $total = '0';
        foreach ($this->orders as $order) {
            if ($order->isValidForCalculation()) {
                $total = bcadd($total, $order->getTotalAmount(), 2);
            }
        }

        return $this->cachedTotalSoldAmount = $total;
    }

    // Invalider le cache quand les orders changent
    public function addOrder(Order $order): self
    {
        $this->orders->add($order);
        $this->invalidateCache();
        return $this;
    }

    private function invalidateCache(): void
    {
        $this->cachedTotalSoldAmount = null;
        $this->cachedTotalSoldDays = null;
    }
}
```

**Solution 2:** Colonne calculée en DB (MySQL 8.0+)
```php
#[ORM\Column(type: 'decimal', precision: 10, scale: 2, generated: 'ALWAYS AS (
    SELECT COALESCE(SUM(o.total_amount), 0)
    FROM orders o
    WHERE o.project_id = id AND o.status IN ("signe", "gagne", "termine")
) STORED')]
private string $totalSoldAmount;
```

**Checklist:**
- [ ] Implémenter cache transient pour 4 méthodes critiques
- [ ] Ajouter invalidation sur addOrder/removeOrder
- [ ] Benchmark avec projet ayant 50+ orders
- [ ] Tests de cohérence des données
- [ ] Optionnel: Migration vers colonnes générées (phase 2)

---

### Tâche 3.3 : MetricsCalculationService - Date calculation

**Fichier:** `src/Service/MetricsCalculationService.php` (lignes 284-301)

**Problème:** `calculateWorkingDays()` crée un objet DateTime à chaque itération

**Avant:**
```php
private function calculateWorkingDays(DateTimeInterface $startDate, DateTimeInterface $endDate): int
{
    $count = 0;
    $current = clone $startDate;
    $end = clone $endDate;

    while ($current <= $end) {
        $dayOfWeek = (int) $current->format('N');
        if ($dayOfWeek <= 5) {
            ++$count;
        }
        $current->modify('+1 day');  // 🐌 Lent
    }
    return $count;
}
```

**Après:**
```php
private function calculateWorkingDays(DateTimeInterface $startDate, DateTimeInterface $endDate): int
{
    $period = new \DatePeriod(
        $startDate,
        new \DateInterval('P1D'),
        $endDate->modify('+1 day')
    );

    $workingDays = 0;
    foreach ($period as $date) {
        if ((int)$date->format('N') <= 5) {
            ++$workingDays;
        }
    }

    return $workingDays;
}
```

**Gain estimé:** 20-30% plus rapide

**Checklist:**
- [x] Convertir vers DatePeriod
- [ ] Tests avec différentes plages de dates
- [ ] Vérifier les edge cases (weekend, jours fériés)

---

### Tâche 3.4 : ExcelExportService - Reduce iterations

**Fichier:** `src/Service/ExcelExportService.php` (lignes 46-57)

**Avant:**
```php
if (isset($kpis['projectsByType']) && !empty($kpis['projectsByType'])) {
    $this->createProjectTypeSheet($spreadsheet, $kpis['projectsByType']);
}
if (isset($kpis['projectsByCategory']) && !empty($kpis['projectsByCategory'])) {
    $this->createProjectCategorySheet($spreadsheet, $kpis['projectsByCategory']);
}
// ... 3 fois de plus
```

**Après:**
```php
$sheets = [
    'projectsByType' => 'createProjectTypeSheet',
    'projectsByCategory' => 'createProjectCategorySheet',
    'topContributors' => 'createTopContributorsSheet',
    'monthlyEvolution' => 'createMonthlyEvolutionSheet',
    'salesByStatus' => 'createSalesByStatusSheet',
];

foreach ($sheets as $key => $method) {
    if (!empty($kpis[$key] ?? null)) {
        $this->$method($spreadsheet, $kpis[$key]);
    }
}
```

**Checklist:**
- [x] Refactoriser en boucle
- [ ] Tests Excel export complets

---

## Phase 4 : Type System & Modernisation (Semaine 4)

**Objectif:** Types plus précis pour JIT et sécurité
**Impact:** 10-15% de gain JIT, meilleure DX
**Effort:** 🟡 Moyen (3-4 jours)

### Tâche 4.1 : Typed arrays dans les services

**Fichiers:** Services avec méthodes retournant des arrays

**MetricsCalculationService.php:**
```php
// Avant
public function calculateRevenue(array $projects, ...): array

// Après
/**
 * @param array<Project> $projects
 * @return array{
 *     total_revenue: string,
 *     total_cost: string,
 *     total_margin: string,
 *     margin_rate: float
 * }
 */
public function calculateRevenue(array $projects, ...): array
```

**Fichiers concernés:**
- MetricsCalculationService.php (lignes 81-86, 140-155)
- ProfitabilityService.php (lignes 34-100)
- StaffingMetricsCalculationService.php

**Checklist:**
- [ ] Ajouter PHPDoc avec array shapes
- [ ] Activer PHPStan level 4 (nécessite array shapes)
- [ ] Corriger les erreurs PHPStan

---

### Tâche 4.2 : Enums pour les constantes

**Fichiers:** Entities avec constantes de status

**Order.php (lignes 41-49):**

**Avant:**
```php
public const STATUS_OPTIONS = [
    'a_signer' => 'À signer',
    'gagne' => 'Gagné',
    'signe' => 'Signé',
    'perdu' => 'Perdu',
];

#[ORM\Column(type: 'string', length: 50)]
private string $status;
```

**Après:**
```php
enum OrderStatus: string
{
    case PENDING = 'a_signer';
    case WON = 'gagne';
    case SIGNED = 'signe';
    case LOST = 'perdu';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'À signer',
            self::WON => 'Gagné',
            self::SIGNED => 'Signé',
            self::LOST => 'Perdu',
        };
    }

    public function isValid(): bool
    {
        return match($this) {
            self::SIGNED, self::WON => true,
            default => false,
        };
    }
}

#[ORM\Column(type: 'string', enumType: OrderStatus::class)]
private OrderStatus $status;
```

**Entities à convertir:**
- Order.php - OrderStatus (4 valeurs)
- Project.php - ProjectStatus (3 valeurs)
- ProjectTask.php - TaskType (3 valeurs) + TaskStatus (4 valeurs)

**Checklist:**
- [ ] Créer enum OrderStatus dans src/Enum/
- [ ] Créer enum ProjectStatus
- [ ] Créer enum TaskType et TaskStatus
- [ ] Migration Doctrine pour les colonnes
- [ ] Mettre à jour les formulaires (ChoiceType)
- [ ] Tests sur les filtres par status

---

### Tâche 4.3 : Validation stricte avec assertions

**Pattern:** Ajouter des assertions dans les setters pour validation runtime

**ProjectTask.php (lignes 295-330):**

**Avant:**
```php
public function setEstimatedHoursSold(?int $hours): self
{
    $this->estimatedHoursSold = $hours;
    return $this;
}
```

**Après:**
```php
public function setEstimatedHoursSold(?int $hours): self
{
    if ($hours !== null && $hours < 0) {
        throw new \InvalidArgumentException(
            'Estimated hours sold cannot be negative'
        );
    }
    $this->estimatedHoursSold = $hours;
    return $this;
}
```

**Checklist:**
- [ ] Ajouter validations sur heures négatives
- [ ] Ajouter validations sur montants négatifs
- [ ] Tests unitaires pour exceptions

---

## Phase 5 : Caching avancé (Bonus)

**Si temps disponible après phases 1-4**

### Tâche 5.1 : AnalyticsCacheService avec match

**Fichier:** `src/Service/AnalyticsCacheService.php`

**Modernisation syntaxe:**
```php
public function getOrCompute(
    string $key,
    callable $callback,
    int $ttl = self::DEFAULT_TTL
): mixed {
    $cacheKey = self::CACHE_KEY_PREFIX.$key;

    return match ($cached = $this->cache->get($cacheKey)) {
        null => tap(
            $callback(),
            fn($result) => $this->cache->set(
                $cacheKey,
                $result,
                new \DateTime("+{$ttl} seconds")
            )
        ),
        default => $cached
    };
}
```

---

### Tâche 5.2 : Repository query caching

**TimesheetRepository.php:**

Ajouter attribut `#[Cache]` sur méthodes fréquentes:
```php
#[Cache(lifetime: 3600)]
public function findByContributorAndDateRange(
    Contributor $contributor,
    \DateTimeInterface $startDate,
    \DateTimeInterface $endDate
): array {
    // ...
}
```

---

## Plan de tests

### Tests requis à chaque phase

**Phase 1 - Property Hooks:**
```bash
composer test-unit           # Tests unitaires entities
composer test-functional     # Tests d'intégration
composer phpstan             # Analyse statique
```

**Phase 2 - Asymmetric Visibility:**
```bash
composer test-integration    # Tests repositories
composer test-functional     # Tests controllers
composer check-architecture  # Deptrac
```

**Phase 3 - Performance:**
```bash
composer test               # Suite complète
# Benchmarks personnalisés:
docker compose exec app php bin/console app:benchmark:metrics --iterations=100
docker compose exec app php bin/console app:benchmark:staffing --iterations=100
```

**Phase 4 - Types & Enums:**
```bash
composer phpstan            # Level 4 requis
composer test-unit
composer test-functional
```

---

## Métriques & Monitoring

### Avant le sprint (Baseline)

```bash
# Compter les lignes de code
cloc src/Entity/ src/Service/

# Profiling performance
docker compose exec app php bin/console app:metrics:dispatch --year=2025
# → Noter le temps d'exécution

# Requêtes DB
# Activer Symfony Profiler et compter les requêtes sur /analytics/dashboard
```

### Après chaque phase

```bash
# Réduction de code
git diff --stat main feature/php85-optimizations

# Performance
docker compose exec app vendor/bin/phpbench run tests/Benchmark/ --report=default

# Qualité
composer check-all
```

---

## Checklist globale du sprint

### Préparation
- [x] Créer branche `feature/php85-optimizations`
- [ ] Backup de la DB de dev
- [ ] Documenter les métriques baseline
- [ ] Planifier les reviews de code

### Phase 1 (Semaine 1)
- [x] ✅ Tâche 1.1 : Entities simples
- [x] ✅ Tâche 1.2 : Project.php
- [x] ✅ Tâche 1.3 : Contributor.php
- [x] ✅ Tâche 1.4 : EmploymentPeriod.php
- [ ] 📊 Métriques : ~500 lignes réduites

### Phase 2 (Semaine 2)
- [x] ✅ Tâche 2.1 : IDs en lecture seule
- [ ] ✅ Tâche 2.2 : Timestamps
- [ ] 📊 Métriques : 5-10% gain performance

### Phase 3 (Semaine 3)
- [x] ✅ Tâche 3.1 : StaffingMetrics N+1
- [ ] ✅ Tâche 3.2 : Project caching
- [x] ✅ Tâche 3.3 : Date calculations
- [x] ✅ Tâche 3.4 : Excel iterations
- [ ] 📊 Métriques : 50-70% réduction requêtes

### Phase 4 (Semaine 4)
- [ ] ✅ Tâche 4.1 : Typed arrays
- [ ] ✅ Tâche 4.2 : Enums
- [ ] ✅ Tâche 4.3 : Validations
- [ ] 📊 Métriques : PHPStan level 4

### Finalisation
- [ ] Code review complet
- [ ] Documentation mise à jour
- [ ] CHANGELOG.md
- [ ] Merge vers main
- [ ] Déploiement staging
- [ ] Tests de charge production-like
- [ ] Déploiement production

---

## Risques & Mitigations

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Property hooks cassent Doctrine | Faible | Élevé | Tests complets sur hydratation/serialization |
| Asymmetric visibility incompatible API Platform | Moyen | Moyen | Vérifier docs API Platform 4.x |
| Caching invalide données périmées | Moyen | Élevé | Stratégie d'invalidation stricte + tests |
| Performance régression sur certaines requêtes | Faible | Moyen | Benchmarks avant/après obligatoires |
| Breaking changes pour frontend | Faible | Moyen | Contrats API maintenus |

---

## Ressources

### Documentation PHP 8.4+
- [Property Hooks RFC](https://wiki.php.net/rfc/property-hooks)
- [Asymmetric Visibility RFC](https://wiki.php.net/rfc/asymmetric-visibility-v2)
- [PHP 8.5 Release Notes](https://www.php.net/releases/8.5/en.php)

### Doctrine & Symfony
- [Doctrine Performance Best Practices](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/improving-performance.html)
- [Symfony Cache Component](https://symfony.com/doc/current/cache.html)

### Outils
- **Blackfire.io** - Profiling PHP
- **PHPBench** - Benchmarking
- **PHPStan Level 4+** - Analyse statique stricte

---

**Créé le:** 2025-12-28
**Auteur:** Claude Code
**Version:** 1.0
