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

---

## 📋 Pending Migrations

### Migrations 6-10: Remaining Batches
**Status:** 📝 Planned
- Batch 4: timesheets, planning
- Batch 5: technologies, service_categories, skills
- Batch 6: analytics (fact_*, dim_*)
- Batch 7: notifications, HR, finance

---

## 🗂️ Migration Infrastructure

### Backup Scripts
✅ `scripts/backup-database.sh` - Creates timestamped MySQL dumps
✅ `scripts/restore-database.sh` - Restores with metadata sync
✅ Latest backup: `backups/lot23_migration6_final.sql` (571KB)

### Documentation
✅ `docs/11-reports/lot-23-migration-guide.md` - Complete guide
✅ Rollback procedures documented (2 methods)
✅ Testing checklist provided

---

## 📊 Progress Summary

**Phase 2.6 - Database Migrations:** 60% Complete (6/10 migrations)

| Migration | Tables | Status | Reversible | Tested |
|-----------|--------|--------|------------|--------|
| 1 - Companies/BUs | 2 | ✅ | ✅ | ✅ |
| 2 - Users | 1 | ✅ | ✅ | ✅ |
| 3 - Batch 1 | 4 | ✅ | ✅ | ✅ |
| 4 - Batch 2 | 5 | ✅ | ✅ | ✅ |
| 5 - Batch 3 | 4 | ✅ | ✅ | ✅ |
| 6 - Batch 4 | 3 | ✅ | ✅ | ✅ |
| 7-10 - Remaining | ~27 | 📝 | - | - |

**Total tables with company_id:** 19/45 (42%)

---

## 🎯 Next Steps

1. ✅ Migration 3 complete
2. ✅ Migration 4 complete
3. ✅ Migration 5 complete
4. ✅ Migration 6 complete
5. 🔜 Create Migration 7 (Batch 5 - Reference Data)
6. Continue with Migrations 8-10
7. Phase 2.5: Frontend tenant selection components
8. Phase 3: Testing (API contract, E2E, security audit)

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
   - orders.order_number: unique → unique(order_number, company_id) ✅

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

**Last updated:** 2025-12-31 13:50
**Author:** Claude Code (Lot 23 - Phase 2.6)
