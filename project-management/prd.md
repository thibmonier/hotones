# Product Requirements Document (PRD)

**Projet:** hotones - Initiative de Refactoring Architectural
**Date:** 2026-01-13
**Version:** 1.0.0
**Auteur:** Équipe Technique
**Statut:** ✅ Approuvé

---

## 📋 Vue d'Ensemble

### Nom du Produit
**Architecture Refactoring Initiative - Migration Clean Architecture + DDD**

### Vision
Transformer l'architecture actuelle du projet hotones d'une architecture Symfony traditionnelle vers une **Clean Architecture + Domain-Driven Design + Hexagonal Architecture** pour garantir la maintenabilité, la testabilité et l'évolutivité à long terme.

### Contexte Business
Le projet hotones a atteint un score d'architecture de **6/25** lors de l'audit architectural, indiquant des problèmes structurels majeurs qui menacent:
- La vélocité de développement (dette technique croissante)
- La qualité du code (couplage fort, tests difficiles)
- L'onboarding de nouveaux développeurs (architecture peu claire)
- La capacité à évoluer (changements risqués, effets de bord)

---

## 🎯 Objectifs

### Objectifs Business
1. **Réduire le temps de développement** de nouvelles fonctionnalités de 30%
2. **Accélérer l'onboarding** des nouveaux développeurs (3 jours → 1 jour)
3. **Diminuer les bugs en production** de 40% via tests améliorés
4. **Faciliter la migration** vers de nouvelles technologies si nécessaire

### Objectifs Techniques
1. **Améliorer le score architectural** de 6/25 → 20+/25 (333% d'amélioration)
2. **Atteindre 80%+ de couverture de tests** avec TDD/BDD strict
3. **Validation Deptrac à 100%** pour garantir le respect des couches
4. **Éliminer le couplage** Domain ↔ Infrastructure
5. **Implémenter les patterns DDD** (Value Objects, Aggregates, Events)

---

## 👥 Utilisateurs Cibles

### Persona P-001: Développeur Backend Senior
- **Besoins**: Architecture claire, tests faciles à écrire, code maintenable
- **Douleurs actuelles**: Couplage fort Doctrine/Domain, tests complexes, effets de bord
- **Gains attendus**: Isolation Domain testable, repositories mockables, logique métier claire

### Persona P-002: Nouveau Développeur
- **Besoins**: Comprendre rapidement l'architecture, savoir où placer le code
- **Douleurs actuelles**: Structure peu claire, mélange des responsabilités
- **Gains attendus**: Couches clairement définies, documentation vivante (tests BDD)

### Persona P-003: Tech Lead / Architecte
- **Besoins**: Garantir la qualité architecturale, prévenir la dette technique
- **Douleurs actuelles**: Violations architecturales non détectées, pas de garde-fous
- **Gains attendus**: Validation Deptrac automatique, architecture documentée (ADR)

---

## 🔍 Problèmes Identifiés

### Problème #1: Absence d'Architecture en Couches ⚠️ CRITIQUE
**Impact**: Maintenance difficile, tests complexes, évolution risquée

**État actuel**:
- Aucun répertoire `Domain/`, `Application/`, `Infrastructure/`, `Presentation/`
- Structure traditionnelle Symfony (Controller/Entity/Repository/Service)
- Logique métier dispersée sans frontières claires

**Conséquences**:
- Impossible de tester le Domain sans base de données
- Changement de framework = réécriture complète
- Violations architecturales non détectées

**Fichiers concernés**: Toute la structure `src/`

### Problème #2: Entités Couplées à l'Infrastructure ⚠️ CRITIQUE
**Impact**: Domain non testable indépendamment, dépendance forte à Doctrine

**État actuel**:
```php
// src/Entity/Client.php
#[ORM\Entity(repositoryClass: ClientRepository::class)]  // ❌ Doctrine dans Domain
#[ORM\Table(name: 'clients')]
class Client implements Stringable, CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;
```

**Conséquences**:
- Entités Domain dépendent de Doctrine ORM
- Tests unitaires impossibles sans EntityManager
- Violation du Dependency Inversion Principle

**Fichiers concernés**: Toutes les entités dans `src/Entity/`

### Problème #3: Absence de Value Objects ⚠️ CRITIQUE
**Impact**: Validation dispersée, duplication de code, types primitifs non sûrs

**État actuel**:
```php
#[ORM\Column(type: 'string', length: 255)]
public string $name { get; set; }  // ❌ String simple au lieu de PersonName VO

#[ORM\Column(type: 'string', length: 255, nullable: true)]
public ?string $email { get; set; }  // ❌ String simple au lieu de Email VO
```

**Conséquences**:
- Validation email dupliquée dans Controller/Form/Entity
- Pas de garantie d'immutabilité
- Pas de type safety (string accepte n'importe quoi)

**Fichiers concernés**: Toutes les entités dans `src/Entity/`

### Problème #4: Repositories Sans Interfaces ⚠️ MAJEUR
**Impact**: Couplage fort, impossible de tester avec des mocks, violation DIP

**État actuel**:
```php
// src/Repository/ClientRepository.php
class ClientRepository extends CompanyAwareRepository  // ❌ Pas d'interface
{
    public function findAllOrderedByName(): array
    {
        return $this->createCompanyQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

**Conséquences**:
- Services dépendent directement de l'implémentation Doctrine
- Tests nécessitent une vraie base de données
- Violation du Dependency Inversion Principle

**Fichiers concernés**: Tous les repositories dans `src/Repository/`

### Problème #5: Services Couplés à EntityManager ⚠️ MOYEN
**Impact**: Tests difficiles, logique métier mélangée avec persistance

**État actuel**:
```php
// src/Service/NotificationService.php
public function __construct(
    private readonly EntityManagerInterface $em,  // ❌ Couplage à Doctrine
    private readonly NotificationRepository $notificationRepository,
    // ...
) {}
```

**Fichiers concernés**: Services dans `src/Service/`

### Problème #6: Absence d'Aggregates ⚠️ MOYEN
**Impact**: Incohérence des données, transactions mal gérées, invariants non garantis

**État actuel**:
- Aucun Aggregate Root identifié
- Pas de délimitation claire des frontières transactionnelles
- Entités modifiées directement sans passer par un Aggregate Root

### Problème #7: Deptrac Non Validé ⚠️ MINEUR
**Impact**: Violations architecturales non détectées automatiquement

**État actuel**:
- `deptrac.yaml` présent mais configuré pour architecture traditionnelle
- Validation échouée à cause d'erreurs de syntaxe PHP 8.4
- Ne valide pas les couches Clean Architecture (car elles n'existent pas)

**Erreur rencontrée**:
```
Syntax error, unexpected T_OBJECT_OPERATOR on line 113
in src/Command/ContributorSatisfactionReminderCommand.php
```

---

## 💡 Solution Proposée

### Approche: Strangler Fig Pattern
**Refactoring incrémental sans réécriture complète**

Au lieu de tout réécrire, nous allons:
1. ✅ Créer la nouvelle structure en parallèle
2. ✅ Migrer progressivement module par module
3. ✅ Maintenir la compatibilité pendant la transition
4. ✅ Valider avec tests à chaque étape

### Architecture Cible

```
src/
├── Domain/              # Coeur métier (AUCUNE dépendance externe)
│   ├── Client/
│   │   ├── Entity/Client.php                    # Aggregate Root
│   │   ├── ValueObject/ClientId.php             # Typed ID
│   │   ├── Repository/ClientRepositoryInterface.php
│   │   ├── Service/ClientDomainService.php
│   │   └── Event/ClientCreatedEvent.php
│   └── Shared/
│       └── ValueObject/
│           ├── Email.php                         # Immutable VO
│           ├── Money.php                         # Immutable VO
│           └── PhoneNumber.php                   # Immutable VO
├── Application/         # Use Cases
│   └── Client/
│       ├── CreateClient/
│       │   ├── CreateClientCommand.php
│       │   └── CreateClientHandler.php
│       └── Query/
│           └── GetClientQuery.php
├── Infrastructure/      # Détails techniques
│   ├── Persistence/
│   │   └── Doctrine/
│   │       ├── Repository/DoctrineClientRepository.php
│   │       └── Mapping/Client.orm.xml           # Mapping XML séparé
│   └── Mailer/
└── Presentation/        # UI (Controllers, Forms, CLI)
    └── Controller/ClientController.php
```

---

## 📊 Exigences Fonctionnelles

### REQ-001: Séparation des Couches
**Priorité**: 🔴 CRITIQUE
**Description**: Créer la structure Domain/Application/Infrastructure/Presentation conforme à Clean Architecture.

**Critères d'acceptation**:
- [ ] Répertoires créés avec structure conforme
- [ ] Deptrac configuré pour valider les couches
- [ ] Aucune violation de dépendance détectée

### REQ-002: Entités Domain Pures
**Priorité**: 🔴 CRITIQUE
**Description**: Extraire toutes les entités Domain sans annotations Doctrine.

**Critères d'acceptation**:
- [ ] Entités sans `#[ORM\*]` annotations
- [ ] Mappings Doctrine XML créés dans Infrastructure
- [ ] Tests unitaires Domain sans Doctrine

### REQ-003: Value Objects Immuables
**Priorité**: 🔴 CRITIQUE
**Description**: Créer des Value Objects typés pour Email, Money, PhoneNumber, IDs.

**Critères d'acceptation**:
- [ ] Classes `final readonly` avec validation constructeur
- [ ] Tests unitaires pour chaque VO (validation, immutabilité)
- [ ] Types primitifs remplacés dans les entités

### REQ-004: Repository Interfaces
**Priorité**: 🟠 HAUTE
**Description**: Abstraire tous les repositories via interfaces Domain.

**Critères d'acceptation**:
- [ ] Interfaces dans `Domain/*/Repository/`
- [ ] Implémentations Doctrine dans `Infrastructure/Persistence/Doctrine/Repository/`
- [ ] Services dépendent des interfaces (DIP)

### REQ-005: Use Cases CQRS
**Priorité**: 🟠 HAUTE
**Description**: Créer les Use Cases avec pattern Command/Query.

**Critères d'acceptation**:
- [ ] Commands pour les mutations (CreateClient, UpdateEmail, etc.)
- [ ] Queries pour les lectures (GetClient, SearchClients, etc.)
- [ ] Handlers orchestrant Domain + Infrastructure

### REQ-006: Aggregates et Invariants
**Priorité**: 🟡 MOYENNE
**Description**: Identifier et implémenter les Aggregate Roots avec protection des invariants.

**Critères d'acceptation**:
- [ ] Aggregate Roots identifiés (Client, Order, etc.)
- [ ] Invariants validés dans les méthodes métier
- [ ] Collections d'entités internes uniquement

### REQ-007: Domain Events
**Priorité**: 🟡 MOYENNE
**Description**: Implémenter les Domain Events pour déclencher les side-effects.

**Critères d'acceptation**:
- [ ] Events émis lors des mutations (ClientCreated, EmailUpdated, etc.)
- [ ] Event Handlers dans Application layer
- [ ] Symfony Messenger pour dispatch asynchrone

### REQ-008: Validation Deptrac 100%
**Priorité**: 🟢 BASSE
**Description**: Atteindre 100% de validation Deptrac sans violations.

**Critères d'acceptation**:
- [ ] `make deptrac` passe sans erreur
- [ ] Toutes les violations corrigées
- [ ] Tests d'architecture automatisés dans CI

---

## 📊 Exigences Non Fonctionnelles

### Performance
- ✅ Pas de régression de performance pendant la migration
- ✅ Tests de performance pour valider (temps de réponse < 200ms)

### Testabilité
- ✅ Couverture de code: 80%+ minimum
- ✅ Mutation Score (Infection): 80%+ minimum
- ✅ Tests Domain sans base de données

### Maintenabilité
- ✅ Complexité cyclomatique < 10 par méthode
- ✅ Méthodes < 20 lignes
- ✅ Classes < 200 lignes
- ✅ PHPStan niveau max sans erreur

### Compatibilité
- ✅ PHP 8.4 (property hooks)
- ✅ Symfony 7.2
- ✅ PostgreSQL 16
- ✅ Pas de breaking changes API

---

## 🎯 Métriques de Succès

### Métriques Principales (KPI)

| Métrique | Valeur Actuelle | Cible | Méthode de Mesure |
|----------|----------------|-------|-------------------|
| **Score Architecture** | 6/25 (24%) | 20+/25 (80%+) | Audit architectural |
| **Structure des Couches** | 0/5 | 5/5 | Deptrac validation |
| **Entités et Value Objects** | 2/5 | 5/5 | Audit entités Domain |
| **Aggregates et Repositories** | 1/5 | 5/5 | Audit repositories + tests |
| **Ports (Interfaces)** | 0/2.5 | 2.5/2.5 | Comptage interfaces Domain |
| **Adapters** | 1/2.5 | 2.5/2.5 | Audit implémentations Infrastructure |
| **Validation Deptrac** | 0/5 | 5/5 | `make deptrac` succès |

### Métriques Secondaires

| Métrique | Valeur Actuelle | Cible | Méthode de Mesure |
|----------|----------------|-------|-------------------|
| **Couverture de code** | Non mesuré | 80%+ | `make test-coverage` |
| **Mutation Score (MSI)** | Non mesuré | 80%+ | `make infection` |
| **Violations PHPStan** | Non mesuré | 0 | `make phpstan` |
| **Violations Deptrac** | 100% (échec) | 0 | `make deptrac` |
| **Temps onboarding** | 3 jours | 1 jour | Feedback équipe |
| **Vélocité sprint** | Baseline | +30% | Story points complétés |

### Indicateurs de Qualité

| Indicateur | Cible |
|------------|-------|
| Code coverage lignes | ≥ 80% |
| Code coverage branches | ≥ 75% |
| Mutation Score Indicator | ≥ 80% |
| Complexité cyclomatique | < 10 |
| Lignes par méthode | < 20 |
| Lignes par classe | < 200 |
| Dépendances par classe | < 7 |
| Duplication de code | < 3% |

---

## 🗓️ Timeline et Phases

### Phase 1: Fondations (Sprints 1-2, Semaines 1-2)
**Objectif**: Créer la structure et les premiers Value Objects

- Sprint 1 (Semaine 1):
  - Créer structure Domain/Application/Infrastructure/Presentation
  - Extraire premières entités Domain (Client, User)
  - Créer mappings Doctrine XML
  - Créer Value Objects critiques (Email, ClientId, UserId)

- Sprint 2 (Semaine 2):
  - Créer Value Objects supplémentaires (Money, PhoneNumber, Address)
  - Extraire entités restantes (Order, Product, etc.)
  - Compléter mappings Doctrine
  - Atteindre 50%+ couverture tests Domain

### Phase 2: Abstractions (Sprints 3-4, Semaines 3-4)
**Objectif**: Découpler via interfaces et Use Cases

- Sprint 3 (Semaine 3):
  - Créer interfaces Repository dans Domain
  - Implémenter repositories Doctrine dans Infrastructure
  - Refactorer premiers services pour utiliser interfaces

- Sprint 4 (Semaine 4):
  - Créer Use Cases CQRS (Commands/Queries)
  - Implémenter Command Handlers
  - Implémenter Query Handlers
  - Atteindre 70%+ couverture tests Application

### Phase 3: Domain Services et Events (Sprints 5-6, Semaines 5-6)
**Objectif**: Aggregates, Domain Services, Events

- Sprint 5 (Semaine 5):
  - Identifier Aggregates (Client, Order, etc.)
  - Implémenter Aggregate Roots avec invariants
  - Créer Domain Services (pricing, validation, etc.)

- Sprint 6 (Semaine 6):
  - Implémenter Domain Events
  - Créer Event Handlers Application
  - Configurer Symfony Messenger pour dispatch

### Phase 4: Validation (Sprint 7, Semaine 7)
**Objectif**: 100% validation et documentation

- Sprint 7 (Semaine 7):
  - Corriger erreur syntaxe PHP 8.4 (ContributorSatisfactionReminderCommand)
  - Reconfigurer Deptrac pour Clean Architecture
  - Corriger toutes violations Deptrac
  - Créer tests d'architecture automatisés
  - Atteindre 80%+ couverture totale

**Timeline Total**: 7 semaines (7 sprints d'1 semaine)

---

## 🚧 Contraintes

### Contraintes Techniques
1. **TDD/BDD Obligatoire**: Tous les changements doivent suivre RED → GREEN → REFACTOR
2. **Make Quality**: Toute modification doit passer `make quality` (PHPStan + CS-Fixer + Rector + Deptrac + Tests)
3. **Pas de Breaking Changes**: L'API publique doit rester compatible
4. **Docker Obligatoire**: Toutes les commandes via `make` (pas de commandes directes)
5. **Backward Compatibility**: Maintenir la compatibilité pendant la migration

### Contraintes Méthodologiques
1. **SCRUM Strict**: Sprints d'1 semaine, cérémonies complètes
2. **Definition of Done**: Tests 80%+, PHPStan max, Deptrac validé
3. **User Stories INVEST**: Independent, Negotiable, Valuable, Estimable, Small (≤8pts), Testable
4. **Acceptance Criteria Gherkin**: GIVEN / WHEN / THEN pour toutes les US

### Contraintes Organisationnelles
1. **Pas d'interruption du développement**: Refactoring en parallèle des features
2. **Pas de gel du code**: Accepter les features pendant le refactoring
3. **Documentation continue**: Mise à jour `.claude/rules/` à chaque changement

---

## 📈 Risques et Mitigations

### Risque #1: Refactoring trop ambitieux
**Probabilité**: 🟠 Moyenne
**Impact**: 🔴 Élevé (abandon du refactoring)

**Mitigation**:
- ✅ Strangler Fig Pattern (refactoring incrémental)
- ✅ Sprints courts (1 semaine) pour feedback rapide
- ✅ Commencer par modules simples (Client, User)
- ✅ Mesurer vélocité dès Sprint 1

### Risque #2: Régression fonctionnelle
**Probabilité**: 🟡 Faible
**Impact**: 🔴 Élevé (bugs production)

**Mitigation**:
- ✅ TDD strict (tests AVANT refactoring)
- ✅ Couverture 80%+ obligatoire
- ✅ Tests fonctionnels E2E maintenus
- ✅ CI bloque si tests échouent

### Risque #3: Conflits Git pendant migration
**Probabilité**: 🟠 Moyenne
**Impact**: 🟡 Moyen (ralentissement)

**Mitigation**:
- ✅ Branches de feature courtes (< 3 jours)
- ✅ Merge fréquent vers main
- ✅ Communication équipe sur zones touchées

### Risque #4: Baisse de vélocité features
**Probabilité**: 🟠 Moyenne
**Impact**: 🟡 Moyen (ralentissement roadmap)

**Mitigation**:
- ✅ Allouer 50% capacité sprint au refactoring
- ✅ Prioriser les refactorings à fort ROI
- ✅ Mesurer vélocité et ajuster si nécessaire

### Risque #5: Incompréhension des patterns DDD
**Probabilité**: 🟡 Faible
**Impact**: 🟠 Moyen (mauvaise implémentation)

**Mitigation**:
- ✅ Documentation complète dans `.claude/rules/`
- ✅ Exemples concrets dans `.claude/examples/`
- ✅ Code reviews systématiques
- ✅ Pair programming pour les Aggregates complexes

---

## 🎓 Définition de "Done"

### Definition of Done (DoD) - User Story

Une User Story est considérée **DONE** si et seulement si:

#### Code
- [ ] Code conforme aux standards PSR-12 + Symfony
- [ ] Principes SOLID + KISS + DRY + YAGNI respectés
- [ ] Commentaires en anglais (si nécessaire)
- [ ] Pas de `TODO` ou code commenté

#### Tests
- [ ] Tests unitaires pour logique Domain (≥ 80% coverage)
- [ ] Tests d'intégration pour repositories Infrastructure
- [ ] Tests fonctionnels pour Use Cases Application
- [ ] Tous les tests passent (GREEN)
- [ ] Mutation Score ≥ 80% (Infection)

#### Qualité
- [ ] PHPStan niveau max: 0 erreur
- [ ] PHP-CS-Fixer: appliqué et validé
- [ ] Rector: suggestions appliquées
- [ ] Deptrac: aucune violation de couche
- [ ] PHPCPD: duplication < 3%

#### Documentation
- [ ] Documentation mise à jour si nécessaire
- [ ] Exemples ajoutés dans `.claude/examples/` si pattern nouveau
- [ ] ADR créé si décision architecturale

#### Validation
- [ ] `make quality` passe sans erreur
- [ ] Code review approuvé (au moins 1 reviewer)
- [ ] Acceptance criteria Gherkin validés
- [ ] Démo fonctionnelle si applicable

### Definition of Done (DoD) - Sprint

Un Sprint est considéré **DONE** si et seulement si:

- [ ] Toutes les User Stories committed sont DONE
- [ ] Sprint Goal atteint
- [ ] Score architecture amélioré (mesurable)
- [ ] Pas de régression (tous les tests existants passent)
- [ ] Documentation mise à jour
- [ ] Rétrospective complétée avec actions
- [ ] Démo aux stakeholders réalisée

---

## 📚 Références

### Documentation Interne
- `.claude/rules/02-architecture-clean-ddd.md` - Architecture obligatoire
- `.claude/rules/13-ddd-patterns.md` - Patterns DDD détaillés
- `.claude/rules/18-value-objects.md` - Value Objects patterns
- `.claude/rules/19-aggregates.md` - Aggregates et Aggregate Roots
- `.claude/rules/20-domain-events.md` - Domain Events
- `.claude/rules/21-cqrs.md` - CQRS pattern
- `.claude/rules/07-testing-symfony.md` - TDD/BDD obligatoire

### Ressources Externes
- [Clean Architecture - Robert C. Martin](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Domain-Driven Design - Eric Evans](https://www.domainlanguage.com/ddd/)
- [Hexagonal Architecture - Alistair Cockburn](https://alistair.cockburn.us/hexagonal-architecture/)
- [Deptrac Documentation](https://qossmic.github.io/deptrac/)

### Audit Source
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` - Audit complet 2026-01-13

---

## ✅ Validation et Approbation

### Critères de Validation PRD

- [x] Objectifs business clairs et mesurables
- [x] Problèmes identifiés avec impact quantifié
- [x] Solution technique détaillée
- [x] Timeline réaliste avec phases
- [x] Métriques de succès définies (KPI)
- [x] Risques identifiés avec mitigations
- [x] Definition of Done claire et vérifiable
- [x] Contraintes documentées

### Approbation

**Product Owner**: _À approuver_
**Tech Lead**: _À approuver_
**Équipe Dev**: _À consulter_

---

**Prochaine étape**: Génération du Backlog SCRUM (EPICs + User Stories) basé sur ce PRD.
