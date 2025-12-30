# 🔍 Rapport d'Audit Technique - HotOnes

**Date :** 27 décembre 2025
**Contexte :** Lot 11bis - Sprint Technique & Consolidation
**Objectif :** Établir un état des lieux de la qualité du code avant optimisation

---

## 📊 Vue d'ensemble

### Résumé exécutif

| Dimension | Score actuel | Objectif Lot 11bis | Gap |
|-----------|--------------|-------------------|-----|
| **Code Style** | ✅ 100% | 100% | Maintenir |
| **Analyse statique** | ⚠️ 17 erreurs | 0 erreur | -17 |
| **Architecture** | ⚠️ 9 violations | 0 violation | -9 |
| **Couverture tests** | 📉 14.09% | 60% | +45.91 pts |

**Verdict global :** La qualité du code est **bonne** au niveau style, mais présente des **faiblesses** en analyse statique, architecture et tests.

---

## 1️⃣ Code Style (PHP CS Fixer)

### ✅ Résultat : EXCELLENT (100%)

```
Found 0 of 373 files that can be fixed
```

**Détails :**
- **373 fichiers analysés** - Tous conformes
- Standards appliqués : PSR-12 + Symfony coding standards
- Configuration : `.php-cs-fixer.dist.php`

**Recommandations :**
- ✅ RAS - Continuer à appliquer les hooks pre-commit
- ✅ Maintenir l'exécution automatique via pre-commit hook

---

## 2️⃣ Analyse Statique (PHPStan)

### ⚠️ Résultat : 17 ERREURS DÉTECTÉES

**Configuration :**
- Niveau : 3 (sur 9)
- Strict rules : Activées
- Extensions : Doctrine, Symfony, PHPUnit

### Erreurs par fichier

#### 📁 `src/Controller/GdprController.php` (4 erreurs)

| Ligne | Erreur | Type |
|-------|--------|------|
| 53 | `UserInterface::getId()` non définie | method.notFound |
| 127 | `UserInterface::getEmail()` non définie | method.notFound |
| 175 | `UserInterface::getId()` non définie | method.notFound |
| 276 | `UserInterface::getId()` non définie | method.notFound |

**Cause :** Appel de méthodes spécifiques à `User` sur l'interface `UserInterface`.

**Solution :**
```php
// ❌ Avant
$userId = $this->getUser()->getId();

// ✅ Après
$user = $this->getUser();
assert($user instanceof User);
$userId = $user->getId();
```

---

#### 📁 `src/Controller/InvoiceController.php` (2 erreurs)

| Ligne | Erreur | Type |
|-------|--------|------|
| 497 | `Invoice::getTotalHt()` non définie | method.notFound |
| 498 | `Invoice::getTotalTtc()` non définie | method.notFound |

**Cause :** Méthodes `getTotalHt()` et `getTotalTtc()` absentes de l'entité `Invoice`.

**Solution :**
1. Ajouter les getters dans `Invoice` OU
2. Utiliser les propriétés calculées existantes

---

#### 📁 `src/Service/GdprDataExportService.php` (11 erreurs)

| Ligne | Erreur | Entité concernée |
|-------|--------|------------------|
| 77 | `User::getLastActivityAt()` | User |
| 112 | `EmploymentPeriod::getMonthlyGrossSalary()` | EmploymentPeriod |
| 115 | `EmploymentPeriod::getContractType()` | EmploymentPeriod |
| 136 | `Timesheet::getDays()` | Timesheet |
| 142-144 | `Timesheet::getProjectTask()` (×3) | Timesheet |
| 146-148 | `Timesheet::getProjectSubTask()` (×3) | Timesheet |
| 150 | `Timesheet::getCreatedAt()` | Timesheet |

**Cause :** Méthodes manquantes ou noms incorrects dans les entités.

**Solutions :**
- Ajouter les getters manquants dans les entités
- Corriger les noms de méthodes (ex: `getDays()` → `getHours()`)
- Ajouter les propriétés manquantes si nécessaire

---

### Plan d'action PHPStan

1. **Priorité HAUTE** : Corriger les 17 erreurs identifiées
2. **Priorité MOYENNE** : Augmenter le niveau PHPStan de 3 à 5
3. **Priorité BASSE** : Activer règles strictes supplémentaires

**Estimation :** 2-3 heures

---

## 3️⃣ Architecture (Deptrac)

### ⚠️ Résultat : 9 VIOLATIONS

**Configuration :** `deptrac.yaml`

```
Violations:           9
Skipped violations:   35
Uncovered:            3995
Allowed:              1532
Warnings:             0
Errors:               0
```

### Violations détectées

| Entité | Problème | Localisation |
|--------|----------|--------------|
| `AccountDeletionRequest` | Entity → Repository | Line 16 |
| `CookieConsent` | Entity → Repository | Line 16 |
| `LeadCapture` | Entity → Repository | Line 16 |
| `Provider` | Entity → Repository | Line 19 |
| `SaasProvider` | Entity → Repository | Line 18 |
| `SaasService` | Entity → Repository | Line 18 |
| `SaasSubscription` | Entity → Repository | Line 16 |
| `Subscription` | Entity → Repository | Line 17 |
| `Vendor` | Entity → Repository | Line 18 |

**Cause racine :** Annotation Doctrine `#[ORM\Entity(repositoryClass: XxxRepository::class)]`

### Analyse de l'impact

Cette violation est **acceptable** car :
1. C'est la **convention Doctrine standard**
2. L'annotation ne crée pas de couplage runtime
3. L'alternative (configuration externe) est plus complexe
4. Les entités ne font pas d'appels directs aux repositories

**Recommandation :**
```yaml
# deptrac.yaml - Ajouter skip_violations
layers:
  - name: Entity
    collectors:
      - type: className
        regex: ^App\\Entity\\.*
    skip_violations:
      # Doctrine ORM annotation is acceptable
      - App\Repository\.*Repository
```

**Estimation :** 30 minutes (configuration)

---

## 4️⃣ Couverture de Tests

### 📉 Résultat : 14.09% (Objectif : 60%)

**Métriques globales :**
```
Classes:  3.96% (13/328)
Methods:  17.25% (520/3015)
Lines:    14.09% (3486/24735)
```

### Analyse par composant

#### ✅ Services bien couverts (>80%)

| Service | Coverage | Priorité |
|---------|----------|----------|
| `DashboardReadService` | 89.69% | ✅ Maintenir |
| `OnboardingService` | 93.88% | ✅ Maintenir |
| `PerformanceReviewService` | 88.89% | ✅ Maintenir |
| `BillingService` | 100% | ✅ Parfait |
| `TimeConversionService` | 100% | ✅ Parfait |

#### ⚠️ Services critiques sous-couverts (<50%)

| Service | Coverage | Impact | Priorité |
|---------|----------|--------|----------|
| `HrMetricsService` | **0.66%** | Élevé | 🔴 URGENT |
| `SecureFileUploadService` | **0.97%** | Critique | 🔴 URGENT |
| `WorkloadPredictionService` | **34.01%** | Élevé | 🟠 Haute |
| `ProjectRiskAnalyzer` | **43.60%** | Élevé | 🟠 Haute |
| `ForecastingService` | **51.58%** | Moyen | 🟡 Moyenne |

#### 📊 Repositories sous-couverts

| Repository | Coverage | Priorité |
|------------|----------|----------|
| `VacationRepository` | **3.33%** | 🔴 URGENT |
| `UserRepository` | **16.67%** | 🔴 URGENT |
| `StaffingMetricsRepository` | **38.61%** | 🟠 Haute |
| `TimesheetRepository` | **30.00%** | 🟠 Haute |
| `ProjectRepository` | **40.71%** | 🟠 Haute |

### Plan d'action Tests

**Phase 1 : Services critiques (Priorité 🔴)**
1. `HrMetricsService` : 0.66% → 70%
2. `SecureFileUploadService` : 0.97% → 80%
3. `VacationRepository` : 3.33% → 60%
4. `UserRepository` : 16.67% → 60%

**Phase 2 : Services importants (Priorité 🟠)**
5. `WorkloadPredictionService` : 34% → 70%
6. `ProjectRiskAnalyzer` : 43.6% → 70%
7. `StaffingMetricsRepository` : 38.6% → 70%
8. `TimesheetRepository` : 30% → 70%
9. `ProjectRepository` : 40.7% → 70%

**Phase 3 : Services moyens (Priorité 🟡)**
10. `ForecastingService` : 51.6% → 70%
11. `ExcelExportService` : 71.7% → 85%

**Estimation globale :**
- Phase 1 : 1.5 jours
- Phase 2 : 2 jours
- Phase 3 : 0.5 jour
- **Total : 4 jours** (sur objectif 3-4j du Lot 11bis)

---

## 5️⃣ Audit Sécurité (À venir)

### Checklist OWASP Top 10 (2021)

- [ ] **A01:2021 – Broken Access Control**
  - Vérifier voters Symfony
  - Tester les permissions par rôle
  - Valider l'isolation des données multi-tenant

- [ ] **A02:2021 – Cryptographic Failures**
  - Audit des secrets (`.env`, Symfony Secrets)
  - Vérifier chiffrement mots de passe (bcrypt/argon2)
  - SSL/TLS configuré correctement

- [ ] **A03:2021 – Injection**
  - Requêtes Doctrine paramétrées ✅
  - Validation inputs formulaires
  - Échappement Twig automatique ✅

- [ ] **A04:2021 – Insecure Design**
  - Revue architecture (Deptrac) ⚠️ 9 violations
  - Principes SOLID respectés
  - Séparation des responsabilités

- [ ] **A05:2021 – Security Misconfiguration**
  - Headers de sécurité (CSP, HSTS, X-Frame-Options)
  - Mode debug désactivé en prod
  - Cookies sécurisés (Secure, HttpOnly, SameSite)

- [ ] **A06:2021 – Vulnerable Components**
  - `composer audit` : À exécuter
  - Dépendances à jour
  - CVE monitoring

- [ ] **A07:2021 – Authentication Failures**
  - 2FA activée ✅
  - Rate limiting login
  - Politique mots de passe forts

- [ ] **A08:2021 – Software and Data Integrity Failures**
  - Intégrité des assets (SRI)
  - Validation signatures packages
  - CSRF protection ✅

- [ ] **A09:2021 – Security Logging Failures**
  - Logs sensibles (login, modifications)
  - Retention policy
  - Alertes incidents

- [ ] **A10:2021 – Server-Side Request Forgery (SSRF)**
  - Validation URLs externes
  - Whitelist domaines autorisés

**Estimation :** 1-2 jours (audit + corrections)

---

## 6️⃣ Performance (À venir)

### Profiling Blackfire

**Parcours à profiler :**
1. Dashboard Analytics (requêtes lourdes)
2. Saisie timesheet (mutations fréquentes)
3. Liste projets avec filtres
4. Génération rapports Excel
5. Calcul métriques (star schema)

**Métriques cibles :**
- Temps de réponse < 200ms (pages simples)
- Temps de réponse < 500ms (dashboards)
- Temps de réponse < 2s (exports Excel)

**Estimation :** 1-2 jours (profiling + optimisations)

---

## 7️⃣ Infrastructure (À venir)

### Docker

**Images actuelles :**
- `php:8.4-fpm` : ~450 MB
- `nginx:alpine` : ~40 MB
- `mariadb:11.4` : ~400 MB
- `redis:alpine` : ~30 MB

**Objectifs :**
- Multi-stage builds PHP : -30% taille
- Optimisation layers cache
- Health checks configurés

**Estimation :** 1 jour

---

## 📋 Plan d'action global Lot 11bis

### Sprint 1 : Audit & Documentation (2-3j) ✅ EN COURS
- [x] Exécuter PHPStan, PHP CS Fixer, Deptrac
- [x] Mesurer couverture de tests actuelle
- [ ] Audit sécurité OWASP Top 10
- [ ] Profiling Blackfire
- [ ] Documentation architecture

### Sprint 2 : Qualité & Tests (3-4j)
- [ ] Corriger 17 erreurs PHPStan
- [ ] Tests Phase 1 : Services critiques → 70%
- [ ] Tests Phase 2 : Repositories importants → 70%
- [ ] Configuration Infection (mutation testing)

### Sprint 3 : Performance (2-3j)
- [ ] Optimiser requêtes N+1 identifiées
- [ ] Cache Redis (Doctrine + HTTP)
- [ ] Indexation base de données
- [ ] Lazy loading composants

### Sprint 4 : Sécurité (2-3j)
- [ ] Headers sécurité (CSP, HSTS)
- [ ] Rotation secrets Symfony
- [ ] Audit dépendances (composer audit)
- [ ] Tests pénétration basiques

### Sprint 5 : Infrastructure (1-2j)
- [ ] Multi-stage Docker builds
- [ ] Configuration CI/CD (GitHub Actions)
- [ ] Monitoring (logs centralisés)
- [ ] Scripts backup automatique

---

## 🎯 KPIs de réussite

| Métrique | Avant | Objectif | Mesure |
|----------|-------|----------|--------|
| **Code coverage** | 14.09% | 60% | PHPUnit |
| **Erreurs PHPStan** | 17 | 0 | PHPStan level 3 |
| **Violations Deptrac** | 9 | 0* | Deptrac |
| **Headers sécurité** | 0/5 | 5/5 | Mozilla Observatory |
| **Temps réponse moy.** | ? | <500ms | Blackfire |
| **Taille image Docker** | 450 MB | <320 MB | docker images |

*Note : Les 9 violations Entity→Repository peuvent être acceptées via `skip_violations`

---

**Prochaines étapes :**
1. ✅ Audit qualité code - TERMINÉ
2. 🔄 Audit sécurité OWASP Top 10 - EN COURS
3. ⏳ Profiling Blackfire
4. ⏳ Correction erreurs PHPStan
5. ⏳ Augmentation couverture tests

**Dernière mise à jour :** 27 décembre 2025
**Auteur :** Claude Sonnet 4.5 via Claude Code
