# EPIC-005: Validation et Conformité Architecture

**Statut**: 📋 Backlog
**Priorité**: 🟡 HAUTE
**Effort Estimé**: 1 sprint (Phase 4)
**Business Value**: 🟡 ÉLEVÉ
**Risque Technique**: 🟢 FAIBLE

---

## Vue d'ensemble

Mettre en place la **validation automatisée** de l'architecture Clean Architecture + DDD via Deptrac et corriger les erreurs de syntaxe PHP bloquant l'analyse statique.

### Problème adressé

**Audit Report - Problem #7**: Deptrac Non Validé
- **Score actuel**: Validation Deptrac 0/5 ❌
- **Impact**: Violations architecturales non détectées, dégradation progressive
- **Fichiers concernés**: `deptrac.yaml`, `src/Command/ContributorSatisfactionReminderCommand.php`

### Solution proposée

Reconfiguration complète de Deptrac pour valider les couches Domain/Application/Infrastructure/Presentation et correction des erreurs de syntaxe PHP 8.4.

```yaml
# deptrac.yaml (nouvelle configuration)
deptrac:
    paths:
        - src/

    layers:
        - name: Domain
          collectors:
              - type: directory
                value: src/Domain/.*

        - name: Application
          collectors:
              - type: directory
                value: src/Application/.*

        - name: Infrastructure
          collectors:
              - type: directory
                value: src/Infrastructure/.*

        - name: Presentation
          collectors:
              - type: directory
                value: src/Presentation/.*

    ruleset:
        Domain: []                    # ✅ Domain ne dépend de RIEN
        Application: [Domain]         # ✅ Application dépend uniquement de Domain
        Infrastructure: [Domain, Application]
        Presentation: [Application, Infrastructure, Domain]
```

---

## Objectifs métier

### Bénéfices attendus

1. **Prévention des violations architecturales**
   - Détection automatique des dépendances interdites
   - CI bloque les PRs violant les règles
   - Architecture propre maintenue dans le temps

2. **Qualité code garantie**
   - PHPStan niveau max sans erreurs
   - Deptrac 0 violation
   - CI/CD avec gates qualité

3. **Conformité aux standards projet**
   - Respect des règles `.claude/rules/02-architecture-clean-ddd.md`
   - Validation automatisée des principes SOLID
   - Documentation architecture à jour

4. **Réduction dette technique de 30%**
   - Violations détectées tôt (PR)
   - Refactoring guidé par Deptrac
   - Métriques architecture trackées

---

## Exigences liées

- **REQ-008**: Validation Automatisée (Deptrac)

---

## User Stories associées

### Phase 4: Architecture Validation (Sprint 7)

- **US-045**: Corriger erreur syntaxe PHP 8.4 dans ContributorSatisfactionReminderCommand
- **US-046**: Reconfigurer Deptrac pour valider couches Clean Architecture
- **US-047**: Créer les rules Deptrac pour Domain/Application/Infrastructure/Presentation
- **US-048**: Corriger toutes les violations Deptrac détectées
- **US-049**: Intégrer Deptrac dans le CI/CD (GitHub Actions)
- **US-050**: Créer tests d'architecture automatisés (ArchUnit-style)

---

## Critères d'acceptation (EPIC)

### Corrections syntaxe PHP

- [ ] Erreur ligne 113 dans `ContributorSatisfactionReminderCommand.php` corrigée
- [ ] PHPStan niveau max passe sans erreur
- [ ] Tous les fichiers PHP 8.4 compatibles
- [ ] Tests passent après corrections

### Configuration Deptrac

- [ ] `deptrac.yaml` reconfiguré pour Clean Architecture
- [ ] Layers définis: Domain, Application, Infrastructure, Presentation
- [ ] Ruleset configuré selon Dependency Inversion
- [ ] Collectors directory correctement configurés

### Validation architecture

- [ ] `make deptrac` passe sans violation
- [ ] Score "Validation Deptrac": 0/5 → 5/5
- [ ] Aucune dépendance Domain → Infrastructure/Presentation
- [ ] Aucune dépendance Application → Infrastructure (sauf via interfaces)

### Intégration CI/CD

- [ ] GitHub Actions workflow avec step Deptrac
- [ ] PR bloquées si Deptrac échoue
- [ ] Badge status Deptrac dans README
- [ ] Rapport Deptrac généré pour chaque build

### Tests architecture

- [ ] Tests automatisés validant les couches
- [ ] Tests vérifiant aucune annotation Doctrine dans Domain
- [ ] Tests vérifiant toutes interfaces dans Domain
- [ ] Couverture architecture 100%

---

## Métriques de succès

| Métrique | Avant | Cible | Validation |
|----------|-------|-------|------------|
| **Deptrac Violations** | Non mesuré | 0 | `make deptrac` |
| **Structure Couches** | 0/5 ❌ | 5/5 ✅ | Audit architectural |
| **Erreurs PHPStan** | 1+ | 0 | `make phpstan` |
| **Architecture Tests** | 0 | 100% | Suite tests architecture |
| **CI enforces rules** | Non | Oui | GitHub Actions passe |

---

## Dépendances

### Bloquantes (doivent être faites avant)

- **EPIC-001 Phase 1**: Structure Domain/Application/Infrastructure/Presentation créée
- **EPIC-001 Phase 2**: Use Cases créés (US-008, US-009)
- **EPIC-002**: Value Objects créés
- **EPIC-003**: Repository interfaces dans Domain
- **EPIC-004**: Domain Services et Events implémentés

### Bloquées par cet EPIC

- Aucune (dernier EPIC de la roadmap Phase 1-4)

---

## Risques et mitigations

### Risque 1: Violations Deptrac difficiles à corriger

- **Probabilité**: Moyenne
- **Impact**: Moyen
- **Mitigation**:
  - Corriger les violations une par une (progressive)
  - Baseline temporaire pour violations legacy
  - Documentation des patterns de correction
  - Pair programming sur cas complexes

### Risque 2: CI trop strict bloque les développements

- **Probabilité**: Faible
- **Impact**: Moyen
- **Mitigation**:
  - Configuration Deptrac avec warnings avant errors
  - Possibilité de skip temporaire avec justification
  - Revue régulière des règles (pas trop restrictives)

### Risque 3: Faux positifs Deptrac

- **Probabilité**: Faible
- **Impact**: Faible
- **Mitigation**:
  - Tester configuration Deptrac sur code existant
  - Ajuster les collectors si nécessaire
  - Exceptions justifiées documentées

---

## Approche d'implémentation

### Stratégie: Fix + Validate + Automate

1. **Corriger les erreurs de syntaxe PHP** (bloquantes)
2. **Reconfigurer Deptrac** pour Clean Architecture
3. **Baseline des violations existantes** (legacy)
4. **Corriger violations une par une** (progressive)
5. **Intégrer dans CI/CD** (automatisation)
6. **Créer tests d'architecture** (garantie)

### Ordre d'exécution recommandé

1. **ContributorSatisfactionReminderCommand.php** (erreur ligne 113) ✅ Prioritaire
2. **Deptrac configuration** (layers + ruleset)
3. **Deptrac baseline** (violations legacy)
4. **Correction violations Domain** (critique)
5. **Correction violations Application** (importante)
6. **Correction violations Infrastructure** (moyenne)
7. **CI/CD integration** (automatisation)
8. **Tests architecture** (validation continue)

### Template Deptrac Configuration

```yaml
# deptrac.yaml - Configuration Clean Architecture

deptrac:
    paths:
        - ./src

    exclude_files:
        - '#.*test.*#'

    layers:
        # ✅ Domain Layer (Business Logic)
        - name: Domain
          collectors:
              - type: directory
                value: src/Domain/.*

        # ✅ Application Layer (Use Cases)
        - name: Application
          collectors:
              - type: directory
                value: src/Application/.*

        # ✅ Infrastructure Layer (Technical Details)
        - name: Infrastructure
          collectors:
              - type: directory
                value: src/Infrastructure/.*

        # ✅ Presentation Layer (UI/API/CLI)
        - name: Presentation
          collectors:
              - type: directory
                value: src/Presentation/.*

    ruleset:
        # ✅ Domain ne dépend de RIEN
        Domain: []

        # ✅ Application dépend uniquement de Domain
        Application:
            - Domain

        # ✅ Infrastructure dépend de Domain et Application
        Infrastructure:
            - Domain
            - Application

        # ✅ Presentation dépend de Application, Infrastructure, Domain
        Presentation:
            - Application
            - Infrastructure
            - Domain  # Pour VOs dans les DTOs

    formatters:
        # ✅ Formatter pour CI
        github-actions: ~

        # ✅ Rapport graphique
        graphviz:
            hidden_layers: []
            groups: []

    analyser:
        types:
            - class
            - class_superglobal
            - function
```

### Template GitHub Actions CI

```yaml
# .github/workflows/architecture.yml

name: Architecture Validation

on:
  pull_request:
    branches: [main]
  push:
    branches: [main]

jobs:
  deptrac:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          tools: composer:v2

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run Deptrac
        run: vendor/bin/deptrac analyze --formatter=github-actions

      - name: Generate Deptrac report
        if: failure()
        run: vendor/bin/deptrac analyze --formatter=graphviz --output=var/deptrac.png

      - name: Upload Deptrac report
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: deptrac-report
          path: var/deptrac.png
```

### Template Architecture Tests

```php
<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Architecture Tests - Validate Clean Architecture boundaries.
 *
 * These tests complement Deptrac by checking specific constraints.
 */
final class ArchitectureTest extends TestCase
{
    /**
     * @test
     */
    public function domain_entities_should_not_have_doctrine_annotations(): void
    {
        $domainPath = __DIR__ . '/../../src/Domain';

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($domainPath)
        );

        $violations = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // ✅ Vérifier absence d'annotations Doctrine
            if (
                str_contains($content, '#[ORM\\')
                || str_contains($content, '@ORM\\')
                || str_contains($content, 'use Doctrine\ORM\Mapping')
            ) {
                $violations[] = $file->getPathname();
            }
        }

        self::assertEmpty(
            $violations,
            sprintf(
                "Domain entities MUST NOT contain Doctrine annotations.\nViolations found in:\n- %s",
                implode("\n- ", $violations)
            )
        );
    }

    /**
     * @test
     */
    public function domain_should_only_define_interfaces_for_repositories(): void
    {
        $domainPath = __DIR__ . '/../../src/Domain';

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($domainPath)
        );

        $violations = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            if (!str_contains($file->getPathname(), 'Repository')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // ✅ Vérifier que c'est une interface, pas une classe
            if (
                str_contains($content, 'class ')
                && !str_contains($content, 'interface ')
            ) {
                $violations[] = $file->getPathname();
            }
        }

        self::assertEmpty(
            $violations,
            sprintf(
                "Domain MUST only contain Repository INTERFACES, not implementations.\nViolations found in:\n- %s",
                implode("\n- ", $violations)
            )
        );
    }

    /**
     * @test
     */
    public function domain_value_objects_should_be_final_readonly(): void
    {
        $valueObjectsPath = __DIR__ . '/../../src/Domain/Shared/ValueObject';

        if (!is_dir($valueObjectsPath)) {
            self::markTestSkipped('Value Objects directory not found yet');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($valueObjectsPath)
        );

        $violations = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // ✅ Vérifier final readonly class
            if (!preg_match('/final readonly class/', $content)) {
                $violations[] = $file->getPathname();
            }
        }

        self::assertEmpty(
            $violations,
            sprintf(
                "Value Objects MUST be declared as 'final readonly class'.\nViolations found in:\n- %s",
                implode("\n- ", $violations)
            )
        );
    }

    /**
     * @test
     */
    public function infrastructure_repositories_should_implement_domain_interfaces(): void
    {
        $infraRepoPath = __DIR__ . '/../../src/Infrastructure/Persistence/Doctrine/Repository';

        if (!is_dir($infraRepoPath)) {
            self::markTestSkipped('Infrastructure Repository directory not found yet');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($infraRepoPath)
        );

        $violations = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // ✅ Vérifier implements XXXInterface
            if (!preg_match('/implements \w+RepositoryInterface/', $content)) {
                $violations[] = $file->getPathname();
            }
        }

        self::assertEmpty(
            $violations,
            sprintf(
                "Infrastructure Repositories MUST implement Domain Repository interfaces.\nViolations found in:\n- %s",
                implode("\n- ", $violations)
            )
        );
    }

    /**
     * @test
     */
    public function application_use_cases_should_depend_on_interfaces_not_implementations(): void
    {
        $useCasePath = __DIR__ . '/../../src/Application';

        if (!is_dir($useCasePath)) {
            self::markTestSkipped('Application directory not found yet');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($useCasePath)
        );

        $violations = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // ✅ Vérifier absence de DoctrineXxxRepository
            if (preg_match('/Doctrine\w+Repository/', $content)) {
                $violations[] = $file->getPathname();
            }

            // ✅ Vérifier absence de EntityManagerInterface
            if (str_contains($content, 'EntityManagerInterface')) {
                $violations[] = $file->getPathname();
            }
        }

        self::assertEmpty(
            $violations,
            sprintf(
                "Application Use Cases MUST depend on Domain interfaces, not Infrastructure implementations.\nViolations found in:\n- %s",
                implode("\n- ", $violations)
            )
        );
    }
}
```

### Correction erreur PHP 8.4

**Fichier concerné**: `src/Command/ContributorSatisfactionReminderCommand.php`

**Erreur actuelle** (ligne 113):
```
Syntax error, unexpected T_OBJECT_OPERATOR
```

**Causes possibles**:
1. Property hooks PHP 8.4 mal formés
2. Chaînage de méthodes sur property hook
3. Syntaxe incompatible avec parser Deptrac

**Approche de correction**:
1. Lire le fichier complet pour identifier la ligne 113 exacte
2. Analyser la syntaxe property hook
3. Corriger selon standards PHP 8.4
4. Valider avec `make phpstan`
5. Valider avec `make deptrac`

### Validation continue

- À chaque Pull Request:
  - [ ] PHPStan niveau max passe
  - [ ] Deptrac passe sans violation
  - [ ] Tests d'architecture passent
  - [ ] CI/CD pipeline passe

---

## Métriques de succès

| Métrique | Avant | Cible | Validation |
|----------|-------|-------|------------|
| **Deptrac Violations** | Non mesuré | 0 | `make deptrac` |
| **Validation Deptrac** | 0/5 ❌ | 5/5 ✅ | Audit architectural |
| **Erreurs PHPStan** | 1+ | 0 | `make phpstan` |
| **Architecture Tests** | 0% | 100% | Suite tests passe |
| **CI enforces boundaries** | Non | Oui | GitHub Actions status |

---

## Dépendances

### Bloquantes (doivent être faites avant)

- **EPIC-001**: Structure Domain/Application/Infrastructure/Presentation créée
- **EPIC-002**: Value Objects créés (pour tests architecture)
- **EPIC-003**: Repository interfaces dans Domain (pour validation Deptrac)
- **EPIC-004**: Domain Services créés (pour tests architecture)

### Bloquées par cet EPIC

- Aucune (EPIC final de la roadmap Phase 1-4)

---

## Risques et mitigations

### Risque 1: Violations Deptrac difficiles à corriger

- **Probabilité**: Moyenne
- **Impact**: Moyen
- **Mitigation**:
  - Baseline Deptrac pour violations legacy
  - Correction progressive (1 violation par PR)
  - Documentation des patterns de correction
  - Pair programming sur cas complexes
  - Whitelist temporaire avec commentaires justificatifs

### Risque 2: CI trop strict ralentit développement

- **Probabilité**: Faible
- **Impact**: Moyen
- **Mitigation**:
  - Warnings avant errors (mode souple initial)
  - Skip CI avec label "architecture-review-needed"
  - Revue trimestrielle des règles Deptrac
  - Balance entre stricte et praticable

### Risque 3: Faux positifs Deptrac

- **Probabilité**: Faible
- **Impact**: Faible
- **Mitigation**:
  - Tester configuration sur code existant
  - Ajuster collectors si trop larges/étroits
  - Exceptions explicites documentées dans deptrac.yaml
  - Revue des règles avec l'équipe

---

## Approche d'implémentation

### Stratégie: Fix First + Progressive Validation

1. **Corriger erreur PHP 8.4** (bloquante pour PHPStan/Deptrac)
2. **Configurer Deptrac** pour Clean Architecture layers
3. **Générer baseline** (violations existantes = legacy)
4. **Corriger violations** une par une (progressive)
5. **Intégrer CI/CD** (automated enforcement)
6. **Créer architecture tests** (automated validation)

### Étapes détaillées

#### Étape 1: Correction erreur PHP 8.4 (US-045)

```bash
# 1. Identifier l'erreur exacte
make phpstan

# 2. Lire le fichier
cat src/Command/ContributorSatisfactionReminderCommand.php | grep -A 5 -B 5 "113:"

# 3. Corriger la syntaxe

# 4. Valider
make phpstan
make test
```

#### Étape 2: Configuration Deptrac (US-046, US-047)

```bash
# 1. Backup configuration actuelle
cp deptrac.yaml deptrac.yaml.backup

# 2. Créer nouvelle configuration (voir template ci-dessus)

# 3. Tester sur code actuel
vendor/bin/deptrac analyze

# 4. Générer baseline si violations
vendor/bin/deptrac analyze --formatter=baseline --output=deptrac-baseline.yaml

# 5. Référencer baseline dans deptrac.yaml
# includes:
#     - deptrac-baseline.yaml
```

#### Étape 3: Correction violations (US-048)

**Priorisation des violations**:

1. **Critique** (Domain → Infrastructure)
   - Annotations Doctrine dans Domain entities
   - Import de classes Symfony dans Domain
   - Dépendance à EntityManagerInterface dans Domain

2. **Importante** (Application → Infrastructure implémentation)
   - Injection de DoctrineXxxRepository au lieu d'interface
   - Dépendance à EntityManagerInterface dans Use Cases

3. **Moyenne** (Infrastructure circular dependencies)
   - Dépendances circulaires entre modules Infrastructure

**Approche de correction**:
- Suivre les templates des EPICs précédents (EPIC-001, EPIC-003, EPIC-004)
- Corriger une violation à la fois avec test
- Créer PR par violation (traçabilité)
- Valider Deptrac après chaque correction

#### Étape 4: CI/CD Integration (US-049)

```bash
# 1. Créer workflow GitHub Actions (voir template ci-dessus)

# 2. Configurer formatter GitHub Actions dans deptrac.yaml
# formatters:
#     github-actions: ~

# 3. Tester en local
vendor/bin/deptrac analyze --formatter=github-actions

# 4. Commit et push
git add .github/workflows/architecture.yml deptrac.yaml
git commit -m "ci: add Deptrac validation to CI/CD"
git push
```

#### Étape 5: Architecture Tests (US-050)

```bash
# 1. Créer suite de tests architecture (voir template ci-dessus)

# 2. Exécuter les tests
vendor/bin/phpunit tests/Architecture/

# 3. Intégrer dans testsuite PHPUnit
# phpunit.xml.dist:
# <testsuite name="architecture">
#     <directory>tests/Architecture</directory>
# </testsuite>

# 4. Ajouter dans make test
# make test lance désormais les tests architecture
```

### Validation continue

- À chaque commit:
  - [ ] PHPStan niveau max passe (`make phpstan`)
  - [ ] Deptrac passe sans violation (`make deptrac`)
  - [ ] Tests architecture passent (`make test-architecture`)

- À chaque Pull Request:
  - [ ] GitHub Actions passe (PHPStan + Deptrac + Tests)
  - [ ] Aucune nouvelle violation introduite
  - [ ] Baseline Deptrac réduite (si possible)

- Hebdomadaire:
  - [ ] Revue des violations baseline
  - [ ] Plan de correction des violations legacy
  - [ ] Mise à jour documentation architecture

---

## Références

### Documentation interne

- `.claude/rules/02-architecture-clean-ddd.md` - Architecture obligatoire (lignes complètes)
- `.claude/rules/08-quality-tools.md` - Deptrac configuration (lignes 180-250)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` - Audit source (lignes 195-217, 399-402)

### Checklist Phase 4 (Audit Report)

**Semaine 7** (lignes 399-402):
- [x] **Reconfigurer Deptrac pour Clean Architecture** - **EPIC-005**
- [x] **Corriger les violations détectées** - **EPIC-005**
- [x] **Atteindre 100% de validation Deptrac** - **EPIC-005**
- [x] **Tests d'architecture automatisés** - **EPIC-005**

### Ressources externes

- [Deptrac Documentation](https://qossmic.github.io/deptrac/)
- [PHP 8.4 Release Notes](https://www.php.net/releases/8.4/en.php)
- [ArchUnit (inspiration)](https://www.archunit.org/)
- [Clean Architecture - Robert C. Martin](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création EPIC | Claude (via workflow-plan) |
| 2026-01-13 | Validation priorité HAUTE | Architecture audit score 0/5 Deptrac |

---

## Notes

- **Prerequis**: EPIC-001, EPIC-002, EPIC-003, EPIC-004 doivent être complétés
- **Erreur PHP 8.4**: Ligne 113 dans ContributorSatisfactionReminderCommand.php (prioritaire)
- **Baseline Deptrac**: Générer baseline pour violations legacy, corriger progressivement
- **CI/CD**: GitHub Actions doit bloquer les PRs avec violations
- **Architecture Tests**: Complètent Deptrac (vérifient contraintes spécifiques)
- **Documentation**: Mettre à jour README avec badge Deptrac status
- **Definition of Done**: Voir `/Users/tmonier/Projects/hotones/project-management/prd.md` section "Définition de Done"
