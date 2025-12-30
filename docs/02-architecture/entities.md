# 📦 Entités principales

```php
// Authentification & Gestion utilisateurs
User (authentification)
├── email, password, roles
├── firstName, lastName, phone, address, avatar
└── totpSecret, totpEnabled (2FA)

EmploymentPeriod (historique RH)
├── contributor_id → Contributor
├── salary, cjm, tjm, weeklyHours, workTimePercentage
├── startDate, endDate, notes
└── profiles[] → Profile (ManyToMany)

Contributor (intervenants)
├── name, email, phone, active
├── user_id → User (optionnel)
├── profiles[] → Profile (dev, lead, chef projet...)
├── employmentPeriods[] → EmploymentPeriod
├── timesheets[]
└── getCjm(), getTjm(), getSalary() → proviennent de la période d'emploi active/récente

Profile (profils métier)
├── name, description, defaultTjm
└── contributors[] (ManyToMany)

// Projets & Devis
Project (projets client)
├── name, client, description
├── purchasesAmount, purchasesDescription
├── startDate, endDate, status, isInternal
├── projectType (forfait/régie)
├── keyAccountManager, projectManager, projectDirector, salesPerson → User
├── serviceCategory → ServiceCategory
├── technologies[] → Technology
└── orders[] → Order

Order (devis)
├── name, orderNumber, notes
├── totalAmount, contingenceAmount, contingenceReason
├── createdAt, validatedAt, status
├── contractType (forfait|regie)
├── project → Project
├── sections[] → OrderSection
├── paymentSchedules[] → OrderPaymentSchedule (si forfait)
└── tasks[] → OrderTask (ancienne structure)

OrderSection (sections de devis)
├── name, description, position
├── order → Order
└── lines[] → OrderLine

OrderLine (lignes de devis)
├── profile → Profile, days, dailyRate (TJM)
├── type (service|purchase|fixed_amount)
├── totalAmount, attachedPurchaseAmount
├── section → OrderSection
└── createProjectTask(Project) → ProjectTask (génération auto)

OrderPaymentSchedule (échéance devis au forfait)
├── order → Order
├── billingDate, amountType (percent|fixed)
├── percent (si percent), fixedAmount (si fixed)
└── computeAmount(totalOrder) → €

// Temps & Planification
Timesheet (temps passés)
├── contributor → Contributor
├── project → Project
├── task → ProjectTask (optionnel)
├── subTask → ProjectSubTask (optionnel)
├── date, hours, notes
└── Relation: temps sur subTask → agrégé dans task → agrégé dans project

Planning (planification future)
├── contributor → Contributor
├── project → Project
├── profile → Profile (profil planifié)
├── startDate, endDate, dailyHours (heures/jour)
├── status (planned|confirmed|cancelled)
├── notes
└── Utilisé dans FactStaffingMetrics pour calculer plannedDays

Vacation (congés)
├── contributor → Contributor
├── startDate, endDate, type
└── notes, status

// Configuration
Technology (technologies)
├── name, category, color, active
└── projects[] (ManyToMany)

ServiceCategory (catégories service)
├── name, description
└── projects[]

CompanySettings (paramètres entreprise - Singleton)
├── structureCostCoefficient (défaut: 1.35)
├── employerChargesCoefficient (défaut: 1.45)
├── annualPaidLeaveDays (défaut: 25)
├── annualRttDays (défaut: 10)
├── updatedAt
└── getGlobalChargeCoefficient() → structureCost × employerCharges

ProjectTask (tâches de réalisation)
├── project → Project
├── orderLine → OrderLine (ligne budgétaire source, nullable)
├── name, description, type (regular|avv|non_vendu)
├── isDefault, countsForProfitability, active, position
├── estimatedHoursSold (heures vendues)
├── estimatedHoursRevised (heures révisées = propre + Σ sous-tâches)
├── progressPercentage, status
├── assignedContributor → Contributor
├── requiredProfile → Profile, dailyRate
├── startDate, endDate
├── subTasks[] → ProjectSubTask
└── getTotalHours() → temps propre + Σ sous-tâches

ProjectSubTask (sous-tâches Kanban)
├── project → Project
├── task → ProjectTask (tâche parente)
├── assignee → Contributor
├── title, status (todo|in_progress|done|blocked)
├── initialEstimatedHours (estimation initiale)
├── remainingHours (reste à faire RAF)
├── position, createdAt, updatedAt
└── getTimeSpentHours() → Σ timesheets

// Analytics (Modèle en étoile)
DimTime (dimension temporelle)
├── date, year, quarter, month
├── yearMonth, yearQuarter
└── monthName, quarterName

DimProjectType (dimension types projet)
├── projectType, serviceCategory, status, isInternal
└── compositeKey (unicité)

DimContributor (dimension contributeurs)
├── user → User, name, role, isActive
└── compositeKey (unicité)

DimProfile (dimension profils métier)
├── profile → Profile, name
├── isProductive (indique si productif)
├── isActive
└── compositeKey (unicité)

FactProjectMetrics (table de faits)
├── dimTime, dimProjectType, dimProjectManager...
├── projectCount, activeProjectCount, orderCount...
├── totalRevenue, totalCosts, grossMargin, marginPercentage
├── totalSoldDays, totalWorkedDays, utilizationRate
└── calculatedAt, granularity

FactStaffingMetrics (table de faits staffing)
├── dimTime, dimProfile, contributor
├── availableDays (jours disponibles hors congés)
├── workedDays (jours travaillés réels)
├── staffedDays (jours staffés sur missions)
├── vacationDays (jours de congés)
├── plannedDays (jours planifiés futur)
├── staffingRate (taux de staffing en %)
├── tace (Taux d’Activité Congés Exclus en %)
├── contributorCount
└── calculatedAt, granularity
```

## 🔗 Relation Order → OrderLine → ProjectTask → ProjectSubTask

### Flux de travail

```
1. Création devis (Order)
   └── Sections (OrderSection)
       └── Lignes budgétaires (OrderLine)
           ├── Type service: Profil + Jours + TJM
           └── Type achat/fixe: Montant direct

2. Validation devis (statut 'signe', 'gagne' ou 'termine')
   └── Génération automatique des tâches projet
       OrderLine.createProjectTask(Project) → ProjectTask
       ├── estimatedHoursSold = days × 8
       ├── name = description ligne
       ├── requiredProfile = profile ligne
       └── orderLine pointeur vers source

3. Découpage en sous-tâches (optionnel)
   ProjectTask → ProjectSubTask(s)
   ├── Gestion Kanban (todo, in_progress, done, blocked)
   ├── Assignation aux contributeurs
   └── Estimation + RAF (reste à faire)

4. Saisie des temps
   Timesheet
   ├── Sur ProjectTask directement
   └── Ou sur ProjectSubTask (plus précis)
```

### Règles de cohérence des temps

#### Pour ProjectTask

**Temps vendu** (`estimatedHoursSold`):
- Source: `OrderLine.days × 8` (jours → heures)
- **Verrouillé** si la tâche est liée à une ligne budgétaire
- Modifiable uniquement pour les tâches AVV/non-vendu (sans orderLine)

**Temps révisé** (`getEstimatedHoursRevised()`):
```php
// Si la tâche a des sous-tâches:
temps_révisé_tache = estimatedHoursRevised (propre) + Σ(subTask.initialEstimatedHours)

// Sinon:
temps_révisé_tache = estimatedHoursRevised (valeur propre)
```

**Temps passé** (`getTotalHours()`):
```php
temps_passé_tache = 
  Σ(timesheets où task=this ET subTask=null) +  // Temps propre
  Σ(subTask.getTimeSpentHours())                  // Temps sous-tâches
```

#### Pour ProjectSubTask

**Estimation initiale** (`initialEstimatedHours`):
- Saisie manuelle lors de la création
- Sert de base pour le RAF initial

**Reste à faire** (`remainingHours`):
- Mis à jour manuellement au fil de l'eau
- Permet de calculer l'avancement: `temps_passé / (temps_passé + RAF) * 100`

**Temps passé** (`getTimeSpentHours()`):
```php
temps_passé_subtask = Σ(timesheets où subTask=this)
```

#### Pour Project

**CA total** (`getTotalSoldAmount()`):
```php
CA_projet = Σ(order.totalAmount) 
  WHERE order.status IN ('signe', 'gagne', 'termine')
```

**Important**: Le CA est calculé depuis les devis validés, pas depuis les tâches. Les tâches sont liées aux lignes budgétaires pour la traçabilité et le suivi d'exécution.

### Contraintes d'intégrité

1. **Une OrderLine peut générer 0 ou 1 ProjectTask**
   - 0 si type='purchase' ou type='fixed_amount'
   - 1 si type='service' avec profil et jours

2. **Une ProjectTask peut avoir 0 ou 1 OrderLine**
   - 0 pour les tâches AVV/non-vendu (créées manuellement)
   - 1 pour les tâches générées depuis un devis

3. **Un Timesheet doit avoir**:
   - Toujours: `project` + `contributor` + `date` + `hours`
   - Optionnel: `task` (si lié à une tâche)
   - Optionnel: `subTask` (si lié à une sous-tâche)
   - Si `subTask` est renseigné, `task` doit l'être aussi

4. **Calculs toujours agrégés de bas en haut**:
   ```
   Timesheet → ProjectSubTask → ProjectTask → Project
   ```
