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
├── name, email, phone, cjm, tjm, active
├── user_id → User (optionnel)
├── profiles[] → Profile (dev, lead, chef projet...)
└── employmentPeriods[], timesheets[]

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
├── profile → Profile, quantity, unitPrice
├── totalPrice, purchaseAmount
└── section → OrderSection

OrderPaymentSchedule (échéance devis au forfait)
├── order → Order
├── billingDate, amountType (percent|fixed)
├── percent (si percent), fixedAmount (si fixed)
└── computeAmount(totalOrder) → €

// Temps & Planification
Timesheet (temps passés)
├── contributor_id → Contributor
├── project_id → Project
├── date, hours, notes
└── task → ProjectTask (optionnel)

Planning (planification future)
├── contributor → Contributor
├── project → Project
├── startDate, endDate, estimatedHours
└── notes, status

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

ProjectTask (tâches par défaut)
├── name, isDefault, excludeFromProfitability
└── project → Project

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

FactProjectMetrics (table de faits)
├── dimTime, dimProjectType, dimProjectManager...
├── projectCount, activeProjectCount, orderCount...
├── totalRevenue, totalCosts, grossMargin, marginPercentage
├── totalSoldDays, totalWorkedDays, utilizationRate
└── calculatedAt, granularity
```
