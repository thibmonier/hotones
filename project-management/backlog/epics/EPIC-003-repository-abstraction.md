# EPIC-003: Abstraction des Repositories

**Statut**: 📋 Backlog
**Priorité**: 🟡 HAUTE
**Effort Estimé**: 2 sprints (Phase 2)
**Business Value**: 🟡 ÉLEVÉ
**Risque Technique**: 🟢 FAIBLE

---

## Vue d'ensemble

Créer des **interfaces Repository** dans le Domain Layer et déplacer les implémentations Doctrine vers l'Infrastructure. Cette abstraction permet le respect du **Dependency Inversion Principle** et améliore drastiquement la testabilité.

### Problème adressé

**Audit Report - Problem #4**: Repositories Sans Interfaces
- **Score actuel**: Aggregates et Repositories 1/5 ❌
- **Impact**: Couplage fort, impossible de tester avec des mocks, violation DIP
- **Fichiers concernés**: `src/Repository/ClientRepository.php`, tous les repositories

### Solution proposée

Création d'interfaces Repository dans le Domain et implémentations dans l'Infrastructure :

```php
src/Domain/Client/Repository/
└── ClientRepositoryInterface.php

src/Infrastructure/Persistence/Doctrine/Repository/
└── DoctrineClientRepository.php (implements ClientRepositoryInterface)
```

---

## Objectifs métier

### Bénéfices attendus

1. **Testabilité améliorée**
   - Tests avec mocks facilités
   - Tests unitaires du Domain sans base de données
   - Tests rapides (< 100ms)

2. **Respect du Dependency Inversion Principle**
   - Domain dépend d'abstractions (interfaces)
   - Infrastructure dépend de Domain
   - Inversion correcte des dépendances

3. **Flexibilité d'implémentation**
   - Changement Doctrine → autre ORM simplifié
   - Implémentations multiples possibles (Doctrine, InMemory, MongoDB)
   - Tests avec InMemoryRepository

4. **Découplage framework**
   - Domain indépendant de Doctrine
   - Pas d'annotations ORM dans Domain
   - Migrations facilitées

---

## Exigences liées

- **REQ-004**: Repository Interfaces dans Domain
- **REQ-005**: Dependency Inversion Principle

---

## User Stories associées

### Phase 2: Repository Interfaces (Sprint 3)

- **US-020**: Créer ClientRepositoryInterface dans Domain
- **US-021**: Implémenter DoctrineClientRepository dans Infrastructure
- **US-022**: Créer UserRepositoryInterface dans Domain
- **US-023**: Implémenter DoctrineUserRepository dans Infrastructure
- **US-024**: Créer OrderRepositoryInterface dans Domain
- **US-025**: Implémenter DoctrineOrderRepository dans Infrastructure

### Phase 2: Repository Avancés (Sprint 4)

- **US-026**: Créer ReservationRepositoryInterface dans Domain
- **US-027**: Implémenter DoctrineReservationRepository dans Infrastructure
- **US-028**: Créer SejourRepositoryInterface dans Domain
- **US-029**: Implémenter DoctrineSejourRepository dans Infrastructure
- **US-030**: Créer InMemoryRepositories pour tests
- **US-031**: Refactorer les services pour utiliser les interfaces

---

## Critères d'acceptation (EPIC)

### Interfaces créées (Domain)

- [ ] `ClientRepositoryInterface` avec méthodes CRUD + queries métier
- [ ] `UserRepositoryInterface` avec authentification
- [ ] `OrderRepositoryInterface` avec recherche avancée
- [ ] `ReservationRepositoryInterface` avec filtres statut
- [ ] `SejourRepositoryInterface` avec disponibilités
- [ ] Toutes les interfaces utilisent des Value Objects (IDs typés)

### Implémentations créées (Infrastructure)

- [ ] `DoctrineClientRepository` implémente interface
- [ ] `DoctrineUserRepository` implémente interface
- [ ] `DoctrineOrderRepository` implémente interface
- [ ] `DoctrineReservationRepository` implémente interface
- [ ] `DoctrineSejourRepository` implémente interface
- [ ] Tous les repositories utilisent `EntityManagerInterface`

### Service Layer Migration

- [ ] Services dépendent des **interfaces** (pas implémentations)
- [ ] Injection via constructeur (constructor injection)
- [ ] Aucun service ne dépend directement de `EntityManagerInterface`
- [ ] Tous les Use Cases utilisent Repository interfaces

### Tests

- [ ] Tests unitaires avec InMemoryRepositories
- [ ] Tests d'intégration avec DoctrineRepositories
- [ ] Couverture code ≥ 80% sur repositories
- [ ] Tests rapides (< 100ms unitaires, < 1s intégration)

### Documentation

- [ ] PHPDoc pour chaque méthode d'interface
- [ ] Exemples d'usage dans `.claude/examples/repository-examples.md`
- [ ] ADR justifiant l'abstraction des repositories

---

## Métriques de succès

| Métrique | Avant | Cible | Validation |
|----------|-------|-------|------------|
| **Repository Interfaces** | 0 | 100% | Toutes interfaces dans Domain |
| **Services couplés à EM** | 100% | 0% | Aucun service ne dépend de EntityManager |
| **Testabilité Domain** | Faible | Élevée | Tests unitaires sans DB |
| **Deptrac violations** | Non mesuré | 0 | `make deptrac` passe |
| **Couverture repositories** | Non mesuré | ≥ 80% | `make test-coverage` |

---

## Dépendances

### Bloquantes (doivent être faites avant)

- **EPIC-001 Phase 1**: Structure Domain créée (US-001)
- **EPIC-002**: IDs typés disponibles (ClientId, UserId, OrderId - US-013)
- **EPIC-002**: Value Objects Email, Money créés (US-010, US-012)

### Bloquées par cet EPIC

- **EPIC-004**: Domain Services → utilise les Repository interfaces
- **EPIC-001 Phase 2**: Use Cases (US-009) → nécessite repositories abstraits

---

## Risques et mitigations

### Risque 1: Services existants cassés

- **Probabilité**: Élevée
- **Impact**: Moyen
- **Mitigation**:
  - Migration progressive service par service
  - Tests d'intégration pour chaque service migré
  - Rollback possible via Git
  - Utiliser Symfony compiler passes pour configuration

### Risque 2: Confusion Doctrine Repository vs Domain Repository

- **Probabilité**: Moyenne
- **Impact**: Faible
- **Mitigation**:
  - Nommage clair: `DoctrineClientRepository` vs `ClientRepositoryInterface`
  - Documentation des responsabilités
  - Code reviews strictes
  - Formation équipe sur DIP

### Risque 3: Performance impactée

- **Probabilité**: Faible
- **Impact**: Faible
- **Mitigation**:
  - Benchmarks avant/après migration
  - Optimisation QueryBuilder Doctrine maintenue
  - Fetch joins conservés dans implémentations
  - Tests de performance automatisés

---

## Approche d'implémentation

### Stratégie: Interface-First + TDD

1. **Créer l'interface** dans Domain avec méthodes métier
2. **Écrire les tests** avec mock de l'interface (RED)
3. **Implémenter** DoctrineRepository dans Infrastructure (GREEN)
4. **Créer InMemoryRepository** pour tests unitaires
5. **Migrer les services** pour utiliser l'interface
6. **Valider Deptrac** (aucune violation)

### Ordre de migration recommandé

1. **ClientRepository** (simple, peu de méthodes) ✅ Prioritaire
2. **UserRepository** (authentication, relations modérées)
3. **OrderRepository** (plus complexe, nombreuses queries)
4. **ReservationRepository** (aggregate complexe)
5. **SejourRepository** (catalogue, disponibilités)

### Template Interface Repository

```php
<?php

declare(strict_types=1);

namespace App\Domain\Client\Repository;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;

/**
 * Repository interface for Client aggregate.
 *
 * Domain layer defines WHAT operations are needed.
 * Infrastructure layer defines HOW they are implemented.
 */
interface ClientRepositoryInterface
{
    /**
     * Find a client by its unique identifier.
     *
     * @throws ClientNotFoundException if client does not exist
     */
    public function findById(ClientId $id): Client;

    /**
     * Find a client by email address.
     *
     * @return Client|null Client if found, null otherwise
     */
    public function findByEmail(Email $email): ?Client;

    /**
     * Find all clients ordered by name.
     *
     * @return list<Client>
     */
    public function findAllOrderedByName(): array;

    /**
     * Save a client (create or update).
     */
    public function save(Client $client): void;

    /**
     * Delete a client.
     */
    public function delete(Client $client): void;

    /**
     * Check if a client with given email exists.
     */
    public function existsByEmail(Email $email): bool;
}
```

### Template Implémentation Doctrine

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\Repository\ClientRepositoryInterface;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\Exception\ClientNotFoundException;
use App\Domain\Shared\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineClientRepository implements ClientRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Client::class);
    }

    public function findById(ClientId $id): Client
    {
        $client = $this->repository->find($id->getValue());

        if (!$client instanceof Client) {
            throw ClientNotFoundException::withId($id);
        }

        return $client;
    }

    public function findByEmail(Email $email): ?Client
    {
        return $this->repository->createQueryBuilder('c')
            ->where('c.email = :email')
            ->setParameter('email', $email->getValue())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllOrderedByName(): array
    {
        return $this->repository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Client $client): void
    {
        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }

    public function delete(Client $client): void
    {
        $this->entityManager->remove($client);
        $this->entityManager->flush();
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->repository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.email = :email')
            ->setParameter('email', $email->getValue())
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
```

### Template InMemory Repository (Tests)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Persistence\InMemory\Repository;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\Repository\ClientRepositoryInterface;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\Exception\ClientNotFoundException;
use App\Domain\Shared\ValueObject\Email;

/**
 * In-memory repository for testing purposes.
 *
 * Fast, isolated, no database dependency.
 */
final class InMemoryClientRepository implements ClientRepositoryInterface
{
    /** @var array<string, Client> */
    private array $clients = [];

    public function findById(ClientId $id): Client
    {
        $client = $this->clients[$id->getValue()] ?? null;

        if (!$client instanceof Client) {
            throw ClientNotFoundException::withId($id);
        }

        return $client;
    }

    public function findByEmail(Email $email): ?Client
    {
        foreach ($this->clients as $client) {
            if ($client->getEmail()->equals($email)) {
                return $client;
            }
        }

        return null;
    }

    public function findAllOrderedByName(): array
    {
        $clients = $this->clients;

        usort($clients, fn(Client $a, Client $b) =>
            strcmp($a->getName(), $b->getName())
        );

        return $clients;
    }

    public function save(Client $client): void
    {
        $this->clients[$client->getId()->getValue()] = $client;
    }

    public function delete(Client $client): void
    {
        unset($this->clients[$client->getId()->getValue()]);
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Clear all data (for test isolation).
     */
    public function clear(): void
    {
        $this->clients = [];
    }
}
```

### Symfony Service Configuration

```yaml
# config/services.yaml

services:
    # Repository interfaces auto-wiring
    App\Domain\Client\Repository\ClientRepositoryInterface:
        class: App\Infrastructure\Persistence\Doctrine\Repository\DoctrineClientRepository

    App\Domain\User\Repository\UserRepositoryInterface:
        class: App\Infrastructure\Persistence\Doctrine\Repository\DoctrineUserRepository

    App\Domain\Order\Repository\OrderRepositoryInterface:
        class: App\Infrastructure\Persistence\Doctrine\Repository\DoctrineOrderRepository

    App\Domain\Reservation\Repository\ReservationRepositoryInterface:
        class: App\Infrastructure\Persistence\Doctrine\Repository\DoctrineReservationRepository

    App\Domain\Sejour\Repository\SejourRepositoryInterface:
        class: App\Infrastructure\Persistence\Doctrine\Repository\DoctrineSejourRepository
```

### Migration d'un Service

#### Avant (couplé à Doctrine)

```php
<?php

namespace App\Service;

use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;

// ❌ Couplage à l'implémentation Doctrine
class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClientRepository $clientRepository,
    ) {}

    public function notifyClient(int $clientId): void
    {
        // ❌ Dépend de l'implémentation concrète
        $client = $this->clientRepository->find($clientId);

        // ...
    }
}
```

#### Après (interface Domain)

```php
<?php

namespace App\Application\Notification\Service;

use App\Domain\Client\Repository\ClientRepositoryInterface;
use App\Domain\Client\ValueObject\ClientId;

// ✅ Dépend de l'abstraction (interface)
final readonly class NotificationService
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function notifyClient(ClientId $clientId): void
    {
        // ✅ Dépend de l'interface, pas de l'implémentation
        $client = $this->clientRepository->findById($clientId);

        // ...
    }
}
```

### Validation continue

- À chaque Repository créé:
  - [ ] Interface dans `Domain/[Context]/Repository/`
  - [ ] Implémentation Doctrine dans `Infrastructure/Persistence/Doctrine/Repository/`
  - [ ] InMemory implementation dans `tests/Infrastructure/Persistence/InMemory/Repository/`
  - [ ] Tests unitaires avec InMemory (< 100ms)
  - [ ] Tests d'intégration avec Doctrine (< 1s)
  - [ ] Service configuré dans `config/services.yaml`
  - [ ] PHPStan niveau max passe
  - [ ] Deptrac valide sans violation

---

## Références

### Documentation interne

- `.claude/rules/02-architecture-clean-ddd.md` - Architecture obligatoire (lignes 325-375)
- `.claude/rules/04-solid-principles.md` - DIP (Dependency Inversion Principle)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` - Audit source (lignes 112-155, 325-375)

### Checklist Phase 2 (Audit Report)

**Semaine 3-4** (lignes 386-390):
- [x] **Créer toutes les interfaces Repository dans Domain** - **EPIC-003**
- [x] **Implémenter les repositories Doctrine dans Infrastructure** - **EPIC-003**
- [x] **Refactorer les services pour utiliser les interfaces** - **EPIC-003**
- [ ] Créer les Use Cases (Commands/Queries + Handlers) - **EPIC-001 Phase 2**

### Ressources externes

- [Repository Pattern - Martin Fowler](https://martinfowler.com/eaaCatalog/repository.html)
- [Dependency Inversion Principle](https://en.wikipedia.org/wiki/Dependency_inversion_principle)
- [Symfony Service Container](https://symfony.com/doc/current/service_container.html)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création EPIC | Claude (via workflow-plan) |
| 2026-01-13 | Validation priorité HAUTE | Architecture audit score 1/5 Repositories |

---

## Notes

- **Prerequis**: EPIC-001 Phase 1 (Domain structure) ET EPIC-002 (IDs typés) doivent être complétés
- **TDD obligatoire**: Tests avec InMemoryRepository AVANT implémentation Doctrine
- **DIP obligatoire**: Services ne doivent JAMAIS dépendre directement de EntityManagerInterface
- **Nommage**: Interfaces suffixées par `Interface`, implémentations préfixées par techno (`Doctrine`, `InMemory`)
- **Configuration**: Auto-wiring Symfony pour injection automatique des implémentations
- **Tests**: InMemory pour tests unitaires (rapides), Doctrine pour tests intégration
- **Definition of Done**: Voir `/Users/tmonier/Projects/hotones/project-management/prd.md` section "Définition de Done"
