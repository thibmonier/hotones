# Phase 11bis.2 - Multi-Tenant Migration Progress

**Branch:** `feature/lot-23-multi-tenant`
**Started:** 2025-12-31
**Last Updated:** 2025-12-31 11:05

---

## ✅ Completed Migrations

### Migration 1: Create companies and business_units tables
**File:** `migrations/Version20251231092056.php`
**Status:** ✅ Completed & Tested
**Commit:** 4eb70d6

Created core multi-tenant tables:
- `companies`: Root tenant entity (52 fields)
- `business_units`: Hierarchical sub-organization

### Migration 2: Add company_id to users table
**File:** `migrations/Version20251231092500.php`
**Status:** ✅ Completed & Tested
**Commit:** 4eb70d6

- Created default "HotOnes" company (id=1)
- Added company_id to users (NOT NULL)
- Changed unique constraint: email → (email, company_id)
- All existing users assigned to default company

### Migration 3: Add company_id to Batch 1 (Contributors)
**File:** `migrations/Version20251231092547.php`
**Status:** ✅ Completed & Tested
**Commit:** dab30ff

Tables modified:
1. **contributors** - company_id from users.company_id (via user_id)
2. **employment_periods** - company_id from contributors
3. **profiles** - all to default company, unique(name, company_id)
4. **contributor_skills** - company_id from contributors

Entity changes:
- User: Added company ManyToOne relationship
- BusinessUnit: Implements CompanyOwnedInterface

Testing:
- ✅ Migration up successful
- ✅ Rollback down tested
- ✅ Re-migration confirmed
- ✅ PHPStan passes
- ✅ Backup created (566KB)

### Migration 4: Add company_id to Batch 2 (Projects)
**File:** `migrations/Version20251231100120.php`
**Status:** ✅ Completed & Tested
**Commit:** e5cfb7f

Tables modified:
1. **clients** - all to default company (id=1)
2. **projects** - company_id from clients, or default if no client
3. **client_contacts** - company_id from clients
4. **project_tasks** - company_id from projects
5. **project_sub_tasks** - company_id from projects

Testing:
- ✅ Migration up successful (438ms, 26 SQL queries)
- ✅ Rollback down tested (68ms, 15 SQL queries)
- ✅ Re-migration confirmed (180ms)
- ✅ PHPStan passes
- ✅ Backup created (567KB)

### Migration 5: Add company_id to Batch 3 (Orders)
**File:** `migrations/Version20251231124027.php`
**Status:** ✅ Completed & Tested
**Commit:** 268acb6

Tables modified:
1. **orders** - company_id from projects, or default if no project
   - **CRITICAL:** order_number unique constraint changed from global to composite (order_number, company_id)
2. **order_sections** - company_id from orders
3. **order_lines** - company_id from orders (via sections double-hop)
4. **order_payment_schedules** - company_id from orders

Unique constraint changes:
- Dropped: `UNIQ_E52FFDEE551F0F81` (order_number only)
- Added: `order_number_company_unique` (order_number, company_id)
- Allows different companies to use same order numbers

Testing:
- ✅ Migration up successful (242ms, 23 SQL queries)
- ✅ Rollback down tested (60ms, 14 SQL queries)
- ✅ Re-migration confirmed (117ms, 23 SQL queries)
- ✅ PHPStan passes
- ✅ Backup created (568KB)

### Migration 6: Add company_id to Batch 4 (Timesheets & Planning)
**File:** `migrations/Version20251231124749.php`
**Status:** ✅ Completed & Tested
**Commit:** a4e1da4

Tables modified:
1. **timesheets** - company_id from contributors
2. **vacations** - company_id from contributors
3. **planning** - company_id from contributors

Data propagation:
- All three tables have contributor_id as required FK
- Straightforward copy from contributors.company_id

Testing:
- ✅ Migration up successful (184ms, 15 SQL queries)
- ✅ Rollback down tested (57ms, 9 SQL queries)
- ✅ Re-migration confirmed (104ms, 15 SQL queries)
- ✅ PHPStan passes
- ✅ Backup created (571KB)

### Migration 7: Add company_id to Batch 5 (Reference Data)
**File:** `migrations/Version20251231125258.php`
**Status:** ✅ Completed & Tested
**Commit:** 4c02669

Tables modified:
1. **technologies** - all to default company (id=1)
2. **service_categories** - all to default company (id=1)
3. **skills** - all to default company (id=1)
   - **CRITICAL:** skills.name unique constraint changed from global to composite (name, company_id)

Unique constraint changes (skills):
- Dropped: `UNIQ_D53116705E237E06` (name only)
- Added: `skill_name_company_unique` (name, company_id)
- Allows different companies to use same skill names

Data propagation:
- Reference data tables have no direct company relationships
- All assigned to default company (id=1)

Testing:
- ✅ Migration up successful (81ms, 17 SQL queries)
- ✅ Rollback down tested (37ms, 11 SQL queries)
- ✅ Re-migration confirmed (68ms, 17 SQL queries)
- ✅ PHPStan passes
- ✅ Backup created (571KB)

### Migration 8: Add company_id to Batch 6 (Analytics)
**File:** `migrations/Version20251231125836.php`
**Status:** ✅ Completed & Tested
**Commit:** 2b17005

Dimension tables (4 total):
1. **dim_time** - all to default company (temporal dimension)
2. **dim_contributor** - copy from users via user_id, or default
3. **dim_profile** - copy from profiles via profile_id, or default
4. **dim_project_type** - all to default company (no FK)

Fact tables (3 total):
5. **fact_project_metrics** - copy from projects OR orders (fallback), or default
6. **fact_staffing_metrics** - copy from contributors, or default
7. **fact_forecast** - all to default company (no FK)

Data propagation:
- Complex fallback logic for fact_project_metrics (projects → orders → default)
- Dimension tables copy from source entities when FK exists
- Star schema maintains referential integrity with company isolation

Testing:
- ✅ Migration up successful (520ms, 40 SQL queries)
- ✅ Rollback down tested (70ms, 21 SQL queries)
- ✅ Re-migration confirmed (338ms, 40 SQL queries)
- ✅ PHPStan passes
- ✅ Backup created (580KB)

### Migration 9: Add company_id to Batch 7 (Business Critical Tables)
**File:** `migrations/Version20251231142500.php`
**Status:** ✅ Completed & Tested
**Commit:** f4dcea4

This is the largest migration, adding company_id to 22 business-critical tables across 4 batches:

**Batch 7A: Project & Finance (9 tables)**
1. **project_events** - copy from projects
2. **project_health_score** - copy from projects
3. **project_technologies** - junction table (no id), copy from projects
4. **project_technology_versions** - copy from projects
5. **order_tasks** - copy from orders
6. **running_timers** - copy from contributors (primary) OR projects (fallback)
7. **invoices** - copy from clients (primary) OR projects (fallback)
   - **CRITICAL:** invoice_number unique constraint changed to composite (invoice_number, company_id)
8. **invoice_lines** - copy from invoices
9. **expense_reports** - copy from contributors (primary) OR projects (fallback)

**Batch 7B: HR & Employment (3 tables)**
10. **employment_period_profiles** - junction table (no id), copy from employment_periods
11. **contributor_profiles** - junction table (no id), copy from contributors
12. **performance_reviews** - copy from contributors

**Batch 7C: SaaS & Subscriptions (7 tables)**
13. **saas_providers** - all to default company (no FK)
14. **saas_services** - copy from saas_providers
15. **saas_subscriptions** - copy from saas_services
16. **saas_vendors** - all to default company (no FK)
17. **saas_distribution_providers** - all to default company (no FK)
18. **saas_subscriptions_v2** - copy from saas_vendors OR saas_distribution_providers (fallback)
19. **billing_markers** - copy from orders

**Batch 7D: Notifications (3 tables)**
20. **notifications** - copy from users (recipient_id)
21. **notification_preferences** - copy from users
22. **notification_settings** - all to default company (global settings)
    - **CRITICAL:** setting_key unique constraint changed to composite (setting_key, company_id)

Unique constraint changes:
- Dropped: `UNIQ_6A2F2F952DA68207` (invoice_number only)
- Added: `invoice_number_company_unique` (invoice_number, company_id)
- Dropped: `UNIQ_B05598605FA1E697` (setting_key only)
- Added: `setting_key_company_unique` (setting_key, company_id)

Junction tables (no id column):
- project_technologies (project_id, technology_id composite PK)
- employment_period_profiles (employment_period_id, profile_id composite PK)
- contributor_profiles (contributor_id, profile_id composite PK)

Testing:
- ✅ Migration up successful (1838.7ms, 122 SQL queries)
- ✅ Rollback down tested (218.2ms, 70 SQL queries)
- ✅ Re-migration confirmed (735.1ms, 122 SQL queries)
- ✅ PHPStan passes (level 3)
- ✅ Backup created (586KB)

### Migration 10: Add company_id to Batch 8 (Final Tables) 🎉
**File:** `migrations/Version20251231145000.php`
**Status:** ✅ Completed & Tested
**Commit:** a244d7e

This is the **FINAL migration**, completing the database migration phase by adding company_id to the last 13 tables:

**Batch 8A: Gamification (4 tables)**
1. **badges** - all to default company (no FK)
2. **achievements** - copy from contributors
3. **xp_history** - copy from contributors
4. **contributor_progress** - copy from contributors

**Batch 8B: System & Misc (9 tables)**
5. **account_deletion_requests** - copy from users
6. **cookie_consents** - copy from users
7. **contributor_satisfactions** - copy from contributors
8. **nps_surveys** - copy from projects
9. **onboarding_tasks** - copy from contributors
10. **onboarding_templates** - copy from profiles, fallback to default
11. **company_settings** - all to default company
    - **SPECIAL:** Added UNIQUE constraint on company_id (one settings per company)
12. **scheduler_entries** - all to default company (system-level)
13. **lead_captures** - all to default company (marketing data)

Special handling:
- **onboarding_templates**: Rows without profile_id assigned to default company
- **company_settings**: UNIQUE constraint ensures one settings record per company

Testing:
- ✅ Migration up successful (813.9ms, 67 SQL queries)
- ✅ Rollback down tested (145.9ms, 40 SQL queries)
- ✅ Re-migration confirmed (835.1ms, 67 SQL queries)
- ✅ PHPStan passes (level 3)
- ✅ Backup created (588KB)

---

## 🎉 Phase 2.6 - Database Migrations: COMPLETE!

---

## 🗂️ Migration Infrastructure

### Backup Scripts
✅ `scripts/backup-database.sh` - Creates timestamped MySQL dumps
✅ `scripts/restore-database.sh` - Restores with metadata sync
✅ Latest backup: `backups/lot23_migration10_final.sql` (588KB)

### Documentation
✅ `docs/11-reports/lot-23-migration-guide.md` - Complete guide
✅ Rollback procedures documented (2 methods)
✅ Testing checklist provided

---

## 📊 Progress Summary

**Phase 2.6 - Database Migrations:** ✅ 100% COMPLETE (10/10 migrations)

| Migration | Tables | Status | Reversible | Tested |
|-----------|--------|--------|------------|--------|
| 1 - Companies/BUs | 2 | ✅ | ✅ | ✅ |
| 2 - Users | 1 | ✅ | ✅ | ✅ |
| 3 - Batch 1 | 4 | ✅ | ✅ | ✅ |
| 4 - Batch 2 | 5 | ✅ | ✅ | ✅ |
| 5 - Batch 3 | 4 | ✅ | ✅ | ✅ |
| 6 - Batch 4 | 3 | ✅ | ✅ | ✅ |
| 7 - Batch 5 | 3 | ✅ | ✅ | ✅ |
| 8 - Batch 6 | 7 | ✅ | ✅ | ✅ |
| 9 - Batch 7 | 22 | ✅ | ✅ | ✅ |
| 10 - Batch 8 | 13 | ✅ | ✅ | ✅ |

**Total tables with company_id:** 64/64 (100%) 🎉

---

## 🎯 Next Steps

1. ✅ Migration 3 complete
2. ✅ Migration 4 complete
3. ✅ Migration 5 complete
4. ✅ Migration 6 complete
5. ✅ Migration 7 complete
6. ✅ Migration 8 complete
7. ✅ Migration 9 complete
8. ✅ Migration 10 complete - **ALL MIGRATIONS DONE!** 🎉
9. 🔜 Phase 2.7: Entity updates (add CompanyOwnedInterface to all entities)
10. Phase 2.8: Repository filters (add company scoping)
11. Phase 2.9: Service layer updates
12. Phase 2.5: Frontend tenant selection components
13. Phase 3: Testing (API contract, E2E, security audit)

---

## ⚠️ Key Decisions Made

1. **Reversibility Strategy:** Dual approach
   - Database backup/restore for full snapshots
   - Complete down() methods for granular rollback

2. **Default Company:** All existing data assigned to "HotOnes" (id=1)
   - Enterprise tier, active status
   - Remains after rollback (harmless)

3. **Unique Constraints Modified:**
   - users.email: unique → unique(email, company_id)
   - profiles.name: unique → unique(name, company_id)
   - orders.order_number: unique → unique(order_number, company_id)
   - skills.name: unique → unique(name, company_id)
   - invoices.invoice_number: unique → unique(invoice_number, company_id)
   - notification_settings.setting_key: unique → unique(setting_key, company_id)

4. **Cascade Deletes:** All FK to companies(id) use ON DELETE CASCADE

---

## 🧪 Quality Checks

All migrations pass:
- ✅ PHPStan static analysis (level 3)
- ✅ PHP CS Fixer code style
- ✅ Doctrine schema validation (mapping correct)
- ✅ Up/down migration cycle
- ✅ Pre-commit hooks (API tests)

---

**Last updated:** 2025-12-31 14:50
**Status:** ✅ Phase 2.6 Complete - All database migrations executed successfully!
**Author:** Claude Code (Lot 23 - Phase 2.6)
