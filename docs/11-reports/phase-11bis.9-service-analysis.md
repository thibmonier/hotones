# Phase 2.9 - Service Layer Analysis (Lot 23 Multi-Tenant)

**Date:** 2026-01-01
**Status:** In Progress

## Overview

Analysis of 38 services to identify multi-tenant requirements. Services need updates in three scenarios:

1. **Entity Creation** - Services creating entities must set company
2. **Direct QueryBuilder** - Services using EntityManager->createQueryBuilder() need manual filtering
3. **Repository-only** - Services only using repositories work automatically (no changes)

## Update Patterns

### Pattern 1: Entity Creation

Services that instantiate entities need `CompanyContext` to set company:

```php
// Before
class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        // ...
    ) {}

    public function createNotification(User $recipient, ...): Notification
    {
        $notification = new Notification();
        $notification->setRecipient($recipient);
        // ... other properties

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }
}

// After
use App\Security\CompanyContext;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CompanyContext $companyContext,
        // ...
    ) {}

    public function createNotification(User $recipient, ...): Notification
    {
        $notification = new Notification();
        $notification->setRecipient($recipient);
        $notification->setCompany($this->companyContext->getCurrentCompany());
        // ... other properties

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }
}
```

### Pattern 2: Direct QueryBuilder Usage

Services using EntityManager->createQueryBuilder() need manual company filtering:

```php
// Before
$qb = $this->entityManager->createQueryBuilder();
$qb->select('f')
   ->from(FactProjectMetrics::class, 'f')
   ->where('f.date >= :start')
   ->setParameter('start', $startDate);

// After
use App\Security\CompanyContext;

$company = $this->companyContext->getCurrentCompany();

$qb = $this->entityManager->createQueryBuilder();
$qb->select('f')
   ->from(FactProjectMetrics::class, 'f')
   ->where('f.date >= :start')
   ->andWhere('f.company = :company')
   ->setParameter('start', $startDate)
   ->setParameter('company', $company);
```

### Pattern 3: Repository-Only (No Changes)

Services that only use repositories don't need changes - repositories auto-filter:

```php
// This works automatically - no changes needed
$projects = $this->projectRepository->findAll();  // Auto-filtered by company
$timesheets = $this->timesheetRepository->findByProject($project);  // Auto-filtered
```

## Service Classification

### ✅ No Changes Needed (Repository-Only Services)

These services only use repositories and don't create entities:

1. **TimeConversionService** - Pure utility, no DB access
2. **PdfGeneratorService** - Renders PDFs, doesn't query DB
3. **AnalyticsCacheService** - Cache abstraction only
4. **LeadMagnetMailer** - Email sending only
5. **NpsMailerService** - Email sending only
6. **GdprEmailService** - Email sending only
7. **InvoiceReminderService** - Uses InvoiceRepository only
8. **CjmCalculatorService** - Calculation logic only

### 🔶 Needs CompanyContext (Entity Creation)

Services that create entities and need company assignment:

1. **NotificationService** - Creates Notification entities
2. **ExpenseReportService** - Creates ExpenseReport entities
3. **StaffingMetricsCalculationService** - Creates FactStaffingMetrics, DimTime, DimProfile
4. **Planning/PlanningOptimizer** - Creates Planning entities
5. **OnboardingService** - Creates OnboardingTask entities (likely)
6. **GamificationService** - Creates Achievement/Badge entities (likely)

### 🔴 Needs CompanyContext + Manual Filtering (Direct QB)

Services using EntityManager->createQueryBuilder():

1. **Analytics/DashboardReadService** - Direct QB for analytics
2. **ExpenseReportService** - Has both entity creation AND direct QB
3. **GdprDataExportService** - Direct QB for data export

### 🔵 Needs Investigation

Services that may or may not need updates:

1. **ProfitabilityService** - Uses repositories only (likely OK)
2. **MetricsCalculationService** - Uses repositories (likely OK)
3. **InvoiceGeneratorService** - May create Invoice entities
4. **BillingService** - May create billing-related entities
5. **TreasuryService** - May create financial entities
6. **AlertDetectionService** - Creates event objects (non-entities)
7. **ForecastingService** - Likely uses repositories only
8. **ProjectRiskAnalyzer** - Likely uses repositories only
9. **WorkloadPredictionService** - Likely uses repositories only
10. **SkillGapAnalyzer** - Likely uses repositories only
11. **ProfitabilityPredictor** - Likely uses repositories only
12. **ClientServiceLevelCalculator** - Likely uses repositories only
13. **GlobalSearchService** - May use direct QB
14. **PerformanceReviewService** - May create PerformanceReview entities
15. **HrMetricsService** - Uses repositories (likely OK)
16. **ExcelExportService** - Read-only export (likely OK)
17. **SecureFileUploadService** - File handling (likely OK)
18. **Planning/ProjectPlanningAssistant** - May create Planning entities
19. **Planning/TaceAnalyzer** - Read-only analytics (likely OK)
20. **Planning/AI/PlanningAIAssistant** - AI integration (needs check)
21. **AI/AiAssistantService** - AI integration (needs check)
22. **Analytics/MetricsCalculationService** - May create analytics entities

## Investigation Plan

1. Read each "Needs Investigation" service
2. Check for:
   - `new EntityName()` - Entity creation
   - `->createQueryBuilder()` on EntityManager - Direct QB usage
   - Repository usage only - No changes needed

## Implementation Strategy

### Batch 1: High Priority (Entity Creation + Direct QB)
- NotificationService
- ExpenseReportService
- StaffingMetricsCalculationService
- Analytics/DashboardReadService
- GdprDataExportService

### Batch 2: Medium Priority (Entity Creation)
- Planning/PlanningOptimizer
- OnboardingService
- GamificationService
- InvoiceGeneratorService
- BillingService

### Batch 3: Investigation + Updates
- All "Needs Investigation" services
- Update as needed based on findings

## Expected Outcomes

- **Entity creation**: All new entities have company set
- **Direct queries**: All manual QB includes company filter
- **Repository usage**: Works automatically (no changes)
- **Tests**: May fail until factories/fixtures updated (Phase 2.10)

---

**Status:** Analysis in progress
**Next:** Investigate remaining services and begin Batch 1 implementation
