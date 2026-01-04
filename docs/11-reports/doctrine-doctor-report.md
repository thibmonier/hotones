# Doctrine_doctor report = optimisations de la base de données et des accès doctrine

**Date de création:** 2026-01-03
**Version PHP:** 8.5.1
**Durée estimée:** ? semaines
**Objectif:** Maximiser les bonnes pratiques autour de l'utilisation et la configuration de doctrine

## CONFIGURATION

### Missing SQL Strict Mode Settings

Your database is missing important SQL modes: NO_ZERO_DATE, NO_ZERO_IN_DATE. These modes prevent silent data truncation and invalid data insertion.

Configuration needs adjustment :
Add missing modes to prevent data corruption and ensure data integrity

Current vs Recommended
Current	: STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION
Recommended	: STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION 

How to Fix
-- In your MySQL configuration file (my.cnf or my.ini):
[mysqld]
sql_mode = &apos;STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE&apos;

-- Or set it dynamically (session only):
SET SESSION sql_mode = &apos;STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE&apos;;

-- Or globally (requires SUPER privilege):
SET GLOBAL sql_mode = &apos;STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE&apos;;

### MySQL timezone tables not loaded

MySQL timezone tables (mysql.time_zone_name) are empty. This prevents timezone conversions with CONVERT_TZ() and named timezones. You can only use offset-based timezones like "+00:00" which is inflexible.

### 61 tables with different collation than database

Found 61 tables ALL using collation "utf8mb4_uca1400_ai_ci" while database default is "utf8mb4_unicode_ci". This appears to be intentional (consistent). Only problematic if JOINing with tables using "utf8mb4_unicode_ci".


## PERFORMANCES

### setMaxResults() with Collection Join Detected

Query uses LIMIT with a fetch-joined collection. This causes LIMIT to apply to SQL rows instead of entities, resulting in partially hydrated collections (silent data loss).

Problem: If the main entity has multiple related items, only some will be loaded. For example, if a Pet has 4 pictures and you use setMaxResults(1), only 1 picture will be loaded instead of 4.

Solution: Use Doctrine's Paginator which executes 2 queries to properly handle collection joins.

setMaxResults() with collection join

Data loss risk - setMaxResults() with fetch-joined collections applies LIMIT to SQL rows, not entities, causing incomplete collections.
LIMIT applies to rows, not entities. If an order has 5 items, you might only get 2.

Current (incomplete)
$query = $em->createQueryBuilder()
->select('order', 'items')
->from(Order::class, 'order')
->leftJoin('order.items', 'items')
->setMaxResults(10)  // Wrong!
->getQuery();

Solution: Use Paginator
use Doctrine\ORM\Tools\Pagination\Paginator;

$paginator = new Paginator($query, $fetchJoinCollection = true);
$orders = iterator_to_array($paginator);

### ORDER BY Without LIMIT in Array Query (0.81ms)

Query uses ORDER BY without LIMIT and returns an array of results (getResult/findBy). This sorts the entire result set without limiting rows, which can cause performance issues. Execution time: 0.81ms. **Add LIMIT** for pagination or to restrict the number of results returned. ORDER BY clause: v0_.created_at DESC


### Inefficient Entity Loading: 12 find() queries detected

Detected 12 simple SELECT by ID queries across 5 table(s): users, contributors, companies, clients, orders. Consider using getReference() instead of find() when you only need the entity reference for associations (threshold: 2)

Use getReference() for better performance
Found 12 places where find() is used just to set a relationship.
When you only need to set a foreign key, find() wastes a query. Use getReference() instead — it creates a proxy without hitting the database.

Current code
// Triggers a SELECT query
$user = $em->find(User::class, $userId);
$order->setUser($user);

Use getReference()
// No query until you access properties
$user = $em->getReference(User::class, $userId);
$order->setUser($user);
Use find() when you need to access the entity's data or validate it exists. Use getReference() when you only need the ID for a relationship.


## Better usage

### Blameable

Missing Blameable Trait: User
Entity User has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: Company
Entity Company has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: BusinessUnit
Entity BusinessUnit has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: ContributorSkill
Entity ContributorSkill has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: ProjectSubTask
Entity ProjectSubTask has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: Skill
Entity Skill has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: ExpenseReport
Entity ExpenseReport has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: Invoice
Entity Invoice has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: Vacation
Entity Vacation has timestamp field(s) (createdAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: updatedAt, createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: Subscription
Entity Subscription has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: SchedulerEntry
Entity SchedulerEntry has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: SaasProvider
Entity SaasProvider has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: OnboardingTask
Entity OnboardingTask has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: SaasSubscription
Entity SaasSubscription has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: SaasService
Entity SaasService has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: OnboardingTemplate
Entity OnboardingTemplate has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: ContributorProgress
Entity ContributorProgress has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: ContributorSatisfaction
Entity ContributorSatisfaction has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: CookieConsent
Entity CookieConsent has timestamp field(s) (createdAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: updatedAt, createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: PerformanceReview
Entity PerformanceReview has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: ProjectEvent
Entity ProjectEvent has timestamp field(s) (createdAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: updatedAt, createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: NpsSurvey
Entity NpsSurvey has timestamp field(s) (createdAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: updatedAt, createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: LeadCapture
Entity LeadCapture has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: Provider
Entity Provider has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: Notification
Entity Notification has timestamp field(s) (createdAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: updatedAt, createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: FactForecast
Entity FactForecast has timestamp field(s) (createdAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: updatedAt, createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: Badge
Entity Badge has timestamp field(s) (createdAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: updatedAt, createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

Missing Blameable Trait: Planning
Entity Planning has timestamp field(s) (createdAt, updatedAt) but no blameable fields (createdBy/updatedBy). Consider using BlameableTrait and TimestampableTrait for proper audit trail. Missing fields: createdBy, updatedBy. Benefits: automatic tracking of who created/updated records, standardized audit across entities.

### Use Enum 

Consider using native enum for Contributor::$gender
Field "Contributor::$gender" has only 1 distinct values across 59 rows (1.7% uniqueness). This suggests a fixed set of values that would benefit from a PHP 8.1+ native enum. This provides type safety, IDE autocomplete, and prevents invalid values.

onsider using native enum for Project::$projectType
Field "Project::$projectType" has only 2 distinct values across 75 rows (2.7% uniqueness). This suggests a fixed set of values that would benefit from a PHP 8.1+ native enum. This provides type safety, IDE autocomplete, and prevents invalid values.

Consider using native enum for Order::$contractType
Field "Order::$contractType" has only 2 distinct values across 71 rows (2.8% uniqueness). This suggests a fixed set of values that would benefit from a PHP 8.1+ native enum. This provides type safety, IDE autocomplete, and prevents invalid values.

Consider using native enum for OrderLine::$type
Field "OrderLine::$type" has only 1 distinct values across 48 rows (2.1% uniqueness). This suggests a fixed set of values that would benefit from a PHP 8.1+ native enum. This provides type safety, IDE autocomplete, and prevents invalid values.

Consider using native enum for ProjectEvent::$eventType
Field "ProjectEvent::$eventType" has only 1 distinct values across 75 rows (1.3% uniqueness). This suggests a fixed set of values that would benefit from a PHP 8.1+ native enum. This provides type safety, IDE autocomplete, and prevents invalid

Consider using native enum for Planning::$status
Field "Planning::$status" has only 2 distinct values across 94 rows (2.1% uniqueness). This suggests a fixed set of values that would benefit from a PHP 8.1+ native enum. This provides type safety, IDE autocomplete, and prevents invalid values.

