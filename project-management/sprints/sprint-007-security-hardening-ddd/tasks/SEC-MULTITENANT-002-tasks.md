# SEC-MULTITENANT-002 — Tasks

> Backfill `TenantAwareTrait` sur 50+ entités tenant-scoped.
> 5 pts / 4 tasks / ~6-8h.

## Tasks

| ID | Type | Description | Estimate | Depends on | Status |
|----|------|-------------|---------:|------------|--------|
| T-SMT2-01 | [TEST] | Audit `src/Entity/*.php` : lister les 63 entités, identifier celles avec champ `company_id` existant (tenant-scoped) | 1h | SEC-MULTITENANT-001 done | 🔲 |
| T-SMT2-02 | [BE] | Refactor : remplacer le champ `private Company $company` direct par `use TenantAwareTrait` sur les entités tenant-scoped (50+) | 3-4h | T-SMT2-01 | 🔲 |
| T-SMT2-03 | [DB] | Vérifier qu'aucune migration n'est nécessaire (le champ `tenant_id` correspond à `company_id` existant) — si oui, créer migration de renommage | 1h | T-SMT2-02 | 🔲 |
| T-SMT2-04 | [TEST] | Run full PHPUnit suite + PHPStan max + Deptrac → 0 régression | 1-2h | T-SMT2-02, T-SMT2-03 | 🔲 |

## Acceptance Criteria

- [ ] Audit liste les 50+ entités tenant-scoped (vs entités globales : `BlogPost`, `Skill`, `Technology`, etc.)
- [ ] Entités tenant-scoped utilisent `use TenantAwareTrait;` (compatibilité Doctrine ORM préservée)
- [ ] Schéma BDD inchangé (le champ existe déjà comme `company_id`)
- [ ] PHPUnit suite full passe sans régression
- [ ] Coverage tenant ratio : `grep -r 'use TenantAwareTrait' src/Entity` ≥ 50
- [ ] Deptrac vert (Domain ne dépend pas de Doctrine)

## Notes techniques

- Le trait peut **co-exister** avec le champ `Company $company` existant (Doctrine ORM utilise les annotations XML/PHP pour mapping).
- Compromis pragmatique : trait expose `getTenantId(): TenantId` qui lit `$this->company->getId()` ; permet d'ajouter le filter sans modifier le schéma.
- Deux approches possibles :
  - **A** : trait avec champ `private string $tenantId` séparé + migration ajout colonne (overhead BDD).
  - **B** : trait avec méthode `getTenantId()` qui lit `$this->company->getId()` (zéro changement schéma, recommandé).

Décision : **Option B** par défaut. Migration vers Option A si performance index `tenant_id` < `company_id` indexé.

## Liste prévisionnelle entités tenant-scoped (audit T-SMT2-01)

Tenant-scoped (à confirmer via grep `private Company \$company` ou `company_id`) :

```
AccountDeletionRequest, BillingMarker, Client, ClientContact, Contributor,
ContributorProgress, ContributorSatisfaction, ContributorSkill,
ContributorTechnology, EmploymentPeriod, ExpenseReport, FactForecast,
HubSpotSettings, BoondManagerSettings, Invoice, InvoiceLine, LeadCapture,
NpsSurvey, OnboardingTask, OnboardingTemplate, Order, OrderLine,
OrderPaymentSchedule, OrderSection, OrderTask, PerformanceReview, Planning,
PlanningSkill, Project, ProjectEvent, ProjectHealthScore, ProjectSkill,
ProjectSubTask, ProjectTask, ProjectTechnology, RunningTimer,
SaasProvider, SaasService, SaasSubscription, SchedulerEntry, ServiceCategory,
Subscription, Timesheet, User, Vendor, BusinessUnit, CompanySettings, ...
```

(~50 entités)

Globales (pas tenant-scoped, pas de trait) :
```
Achievement, Badge, BlogPost, BlogCategory, BlogTag, Company,
CookieConsent, EmployeeLevel, Notification, NotificationPreference,
NotificationSetting, Profile, Provider, Skill, Technology, XpHistory
```

(~16 entités, à valider)
