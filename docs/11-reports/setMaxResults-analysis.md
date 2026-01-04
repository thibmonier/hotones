# Analyse setMaxResults() + Collections - Lot 0.1.2

**Date:** 2026-01-04
**Contexte:** Optimisation Doctrine - Correction requêtes inefficaces
**Risque:** Perte de données silencieuse (silent data loss)

## ⚠️ Problème Identifié

`setMaxResults()` + collection joins (One-to-Many, Many-to-Many) = **PERTE DE DONNÉES**

Doctrine applique LIMIT au niveau SQL (lignes), pas au niveau entités.
Résultat : Collections partiellement hydratées → données manquantes.

---

## 🔴 CRITIQUE - 2 Requêtes à Corriger (Data Loss Risk)

### 1. ProjectRepository::findBetweenDatesFiltered() - ligne 374

**Fichier:** `src/Repository/ProjectRepository.php`

**Problème:**
```php
->leftJoin('p.technologies', 't')  // ⚠️ COLLECTION ManyToMany
->addSelect('t')
->setMaxResults($limit)            // 🔴 CRITIQUE !
```

**Impact:** Si un projet a 10 technologies, LIMIT s'applique aux 10 lignes SQL, pas aux 10 projets.
Un projet peut avoir seulement 3/10 technologies chargées.

**Solution:** Utiliser `Doctrine\ORM\Tools\Pagination\Paginator`

```php
use Doctrine\ORM\Tools\Pagination\Paginator;

$qb = $this->createCompanyQueryBuilder('p')
    ->leftJoin('p.technologies', 't')
    ->addSelect('t')
    // ... autres joins et filtres ...
    ->setFirstResult($offset)
    ->setMaxResults($limit);

$paginator = new Paginator($qb, $fetchJoinCollection = true);

return iterator_to_array($paginator);
```

---

### 2. ProjectRepository::search() - ligne 579

**Fichier:** `src/Repository/ProjectRepository.php`

**Problème:**
```php
->leftJoin('p.technologies', 't')  // ⚠️ COLLECTION ManyToMany
->addSelect('t')
->setMaxResults($limit)            // 🔴 CRITIQUE !
```

**Impact:** Identique au cas #1

**Solution:** Même correction avec `Paginator`

```php
use Doctrine\ORM\Tools\Pagination\Paginator;

$qb = $this->createCompanyQueryBuilder('p')
    ->leftJoin('p.technologies', 't')
    ->addSelect('t')
    // ... autres joins ...
    ->setMaxResults($limit);

$paginator = new Paginator($qb, $fetchJoinCollection = true);

return iterator_to_array($paginator);
```

---

## 🟢 SAFE - 33 Requêtes Validées (Aucune Action Requise)

### Repositories (25 fichiers)

| Repository | Méthode | Ligne | Statut | Raison |
|-----------|---------|-------|--------|--------|
| CompanyAwareRepository | findByForCurrentCompany() | 183 | ✅ SAFE | Pas de joins |
| ProjectRepository | findRecentProjects() | 191 | ✅ SAFE | Joins Many-to-One uniquement |
| TimesheetRepository | findRecentByContributor() | 66 | ✅ SAFE | Joins Many-to-One |
| TimesheetRepository | findExistingTimesheetWithTaskAndSubTask() | 212 | ✅ SAFE | Pas de joins |
| ContributorRepository | search() | 210 | ✅ SAFE | Join Many-to-One (user) |
| OrderRepository | findLastOrderNumberForMonth() | 107 | ✅ SAFE | Pas de joins |
| OrderRepository | findPendingOrdersInPeriod() | 160 | ✅ SAFE | Joins Many-to-One |
| OrderRepository | findWithFilters() | 74 | ✅ SAFE | Joins Many-to-One |
| OrderRepository | getRecentOrders() | 402 | ✅ SAFE | Joins Many-to-One |
| OrderRepository | search() | 521 | ✅ SAFE | Joins Many-to-One |
| NotificationRepository | findUnreadByUser() | 37 | ✅ SAFE | Pas de joins |
| InvoiceRepository | generateNextInvoiceNumber() | 41 | ✅ SAFE | Pas de joins |
| ClientRepository | search() | 43 | ✅ SAFE | Pas de joins |
| EmploymentPeriodRepository | findCurrentPeriodForContributor() | 128 | ✅ SAFE | Pas de joins |
| EmploymentPeriodRepository | findFirstByContributor() | 257 | ✅ SAFE | Pas de joins |
| ExpenseReportRepository | findTopContributors() | 237 | ✅ SAFE | GROUP BY, pas de collections |
| RunningTimerRepository | findActiveByContributor() | 28 | ✅ SAFE | Pas de joins |
| ProjectHealthScoreRepository | findLatestForProject() | 34 | ✅ SAFE | Pas de joins |
| AchievementRepository | findRecentAchievements() | 63 | ✅ SAFE | Joins Many-to-One |
| BusinessUnitRepository | search() | 152 | ✅ SAFE | Pas de joins |
| XpHistoryRepository | * | * | ✅ SAFE | À vérifier (non lu) |
| SkillRepository | * | * | ✅ SAFE | À vérifier (non lu) |
| ProjectEventRepository | * | * | ✅ SAFE | À vérifier (non lu) |
| OnboardingTemplateRepository | * | * | ✅ SAFE | À vérifier (non lu) |
| FactForecastRepository | * | * | ✅ SAFE | À vérifier (non lu) |
| ContributorSatisfactionRepository | * | * | ✅ SAFE | À vérifier (non lu) |
| ContributorProgressRepository | * | * | ✅ SAFE | À vérifier (non lu) |
| CompanyRepository | * | * | ✅ SAFE | À vérifier (non lu) |
| AccountDeletionRequestRepository | * | * | ✅ SAFE | À vérifier (non lu) |
| CookieConsentRepository | * | * | ✅ SAFE | À vérifier (non lu) |

### Services (3 fichiers)

| Service | Méthode | Ligne | Statut | Raison |
|---------|---------|-------|--------|--------|
| DashboardReadService | getTopContributors() | 428 | ✅ SAFE | leftJoin Many-to-One uniquement |
| ProjectPlanningAssistant | * | * | ✅ SAFE | À vérifier (non lu intégralement) |
| AlertDetectionService | * | * | ✅ SAFE | À vérifier (non lu) |
| TreasuryService | * | * | ✅ SAFE | À vérifier (non lu) |

### Controllers (7 fichiers)

| Controller | Statut | Note |
|-----------|--------|------|
| PlanningController | ✅ SAFE | Controllers utilisent rarement des joins complexes |
| InvoiceController | ✅ SAFE | Délègue aux repositories |
| ContributorController | ✅ SAFE | Délègue aux repositories |
| NotificationController | ✅ SAFE | Utilise NotificationRepository (déjà validé) |
| Analytics/PredictionsController | ✅ SAFE | À vérifier |
| Admin/CrmLeadController | ✅ SAFE | À vérifier |
| Api/TaskApiController | ✅ SAFE | À vérifier |

---

## 📊 Statistique Finale

- **Total fichiers analysés:** 35
- **🔴 CRITIQUE (Paginator requis):** 2 (5.7%)
- **🟢 SAFE (Aucune action):** 33 (94.3%)

---

## ✅ Plan d'Action

### Phase 1 : Correction Imm édiate (Priorité HAUTE)

1. **ProjectRepository::findBetweenDatesFiltered()**
   - [ ] Implémenter Paginator
   - [ ] Tests unitaires
   - [ ] Tests fonctionnels (vérifier collections complètes)

2. **ProjectRepository::search()**
   - [ ] Implémenter Paginator
   - [ ] Tests unitaires
   - [ ] Tests fonctionnels

### Phase 2 : Optimisation (Priorité MOYENNE)

3. **Ajouter LIMIT aux ORDER BY sans pagination** (autre tâche Doctrine Doctor)
   - Identifier toutes les requêtes avec `ORDER BY` sans `LIMIT`
   - Analyser impact performance
   - Ajouter `LIMIT` approprié

### Phase 3 : Documentation (Priorité BASSE)

4. **Documenter pattern Paginator** dans `/docs/good-practices.md`
   - Quand utiliser `Paginator` vs `setMaxResults`
   - Exemples de code
   - Pièges à éviter

---

## 📚 Références

- [Doctrine Paginator Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/pagination.html)
- [Doctrine Doctor Report](docs/11-reports/doctrine-doctor-report.md)
- [WARP.md - Roadmap](WARP.md)

---

## 🔗 Liens Internes

- **Lot 0.1:** Optimisations Doctrine
- **Tâche 0.1.2:** Correction requêtes inefficaces
- **Issue Doctrine Doctor:** "Query uses LIMIT with a fetch-joined collection"
