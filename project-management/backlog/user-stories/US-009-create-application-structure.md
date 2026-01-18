# US-009: Créer la structure Application avec Command/Query/Handler

**EPIC:** [EPIC-001](../epics/EPIC-001-clean-architecture-restructuring.md) - Restructuration Clean Architecture
**Priorité:** 🔴 CRITIQUE
**Points:** 3
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer la structure Application avec séparation Command/Query/Handler (CQRS)
**Afin de** organiser les Use Cases et respecter le principe de séparation des responsabilités

---

## Critères d'acceptation

### GIVEN: La structure Domain existe (US-001)

**WHEN:** Je crée la structure Application avec CQRS

**THEN:**
- [ ] Répertoires `src/Application/{Context}/UseCase/` créés pour chaque Bounded Context
- [ ] Répertoires `src/Application/{Context}/Query/` créés pour les lectures
- [ ] Répertoires `src/Application/{Context}/Command/` créés pour les mutations
- [ ] Répertoires `src/Application/{Context}/Handler/` créés pour les handlers
- [ ] Répertoires `src/Application/{Context}/DTO/` créés pour les résultats
- [ ] Répertoires `src/Application/{Context}/EventHandler/` créés pour Domain Events
- [ ] Structure créée pour: Client, User, Order, Reservation, Sejour
- [ ] Fichiers `.gitkeep` dans répertoires vides

### GIVEN: La structure Application existe

**WHEN:** J'exécute `composer dump-autoload`

**THEN:**
- [ ] Aucune erreur PSR-4
- [ ] Namespaces Application reconnus
- [ ] PHPStan passe sur les répertoires vides

### GIVEN: Templates CQRS créés

**WHEN:** Je crée un exemple complet (CreateReservation)

**THEN:**
- [ ] `CreateReservationCommand.php` créé (DTO readonly)
- [ ] `CreateReservationCommandHandler.php` créé (avec __invoke)
- [ ] `GetReservationDetailsQuery.php` créé (DTO readonly)
- [ ] `GetReservationDetailsQueryHandler.php` créé (avec __invoke)
- [ ] `ReservationDetailsDTO.php` créé (résultat Query)
- [ ] Handlers enregistrés dans `config/services.yaml`
- [ ] Tests unitaires des handlers passent

---

## Tâches techniques

### [STRUCT] Créer structure Application par Bounded Context (1.5h)

**Structure attendue:**

```
src/Application/
├── Client/
│   ├── UseCase/
│   │   ├── CreateClient/
│   │   ├── UpdateClient/
│   │   └── DeleteClient/
│   ├── Query/
│   │   ├── GetClientById/
│   │   ├── GetClientByEmail/
│   │   └── ListClients/
│   ├── DTO/
│   │   ├── ClientDTO.php
│   │   └── ClientListDTO.php
│   └── EventHandler/
│       └── SendWelcomeEmailOnClientCreated.php
│
├── User/
│   ├── UseCase/
│   │   ├── RegisterUser/
│   │   └── ChangePassword/
│   ├── Query/
│   │   ├── GetUserById/
│   │   └── AuthenticateUser/
│   └── DTO/
│       └── UserDTO.php
│
├── Order/
│   ├── UseCase/
│   │   ├── CreateOrder/
│   │   ├── AddOrderLine/
│   │   └── ConfirmOrder/
│   ├── Query/
│   │   ├── GetOrderDetails/
│   │   └── ListOrders/
│   └── DTO/
│       ├── OrderDTO.php
│       └── OrderLineDTO.php
│
├── Reservation/
│   ├── UseCase/
│   │   ├── CreateReservation/
│   │   ├── ConfirmReservation/
│   │   └── CancelReservation/
│   ├── Query/
│   │   ├── GetReservationDetails/
│   │   └── ListReservations/
│   └── DTO/
│       ├── ReservationDTO.php
│       └── ParticipantDTO.php
│
└── Sejour/
    ├── UseCase/
    │   └── PublishSejour/
    ├── Query/
    │   ├── SearchSejours/
    │   └── GetSejourAvailability/
    └── DTO/
        └── SejourDTO.php
```

**Actions:**
- Créer tous les répertoires avec `.gitkeep`
- Respecter la convention: `{Context}/UseCase/{Action}/` (un répertoire par use case)
- Créer `{Context}/Query/`, `{Context}/DTO/`, `{Context}/EventHandler/`

### [TEMPLATE] Créer template Command (0.5h)

**Template Command:**

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\UseCase\CreateReservation;

use App\Domain\Sejour\ValueObject\SejourId;
use App\Domain\Shared\ValueObject\Email;

/**
 * Command to create a new reservation.
 *
 * Commands are simple DTOs that represent the intention to change state.
 * They are immutable and contain only data (no behavior).
 */
final readonly class CreateReservationCommand
{
    /**
     * @param string $sejourId
     * @param string $clientEmail
     * @param list<array{nom: string, age: int}> $participants
     */
    public function __construct(
        public string $sejourId,
        public string $clientEmail,
        public array $participants,
    ) {}
}
```

**Caractéristiques Command:**
- ✅ `final readonly class` (immutable)
- ✅ Public properties (DTO pattern)
- ✅ Pas de validation (dans le Handler)
- ✅ Pas de logique métier
- ✅ Nommé avec un verbe d'action: `CreateReservation`, `ConfirmOrder`, `DeleteClient`
- ✅ Suffixe `Command`

### [TEMPLATE] Créer template CommandHandler (1h)

**Template CommandHandler:**

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\UseCase\CreateReservation;

use App\Domain\Reservation\Entity\Reservation;
use App\Domain\Reservation\Entity\Participant;
use App\Domain\Reservation\Factory\ReservationFactory;
use App\Domain\Reservation\Repository\ReservationRepositoryInterface;
use App\Domain\Reservation\Service\ReservationPricingService;
use App\Domain\Reservation\ValueObject\ReservationId;
use App\Domain\Sejour\Repository\SejourRepositoryInterface;
use App\Domain\Sejour\ValueObject\SejourId;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\PersonName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handler for CreateReservationCommand.
 *
 * Orchestrates the creation of a reservation by:
 * 1. Validating business rules
 * 2. Creating the domain entity
 * 3. Persisting via repository
 * 4. Dispatching domain events
 */
#[AsMessageHandler]
final readonly class CreateReservationCommandHandler
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
        private SejourRepositoryInterface $sejourRepository,
        private ReservationPricingService $pricingService,
        private MessageBusInterface $eventBus,
    ) {}

    /**
     * Execute the command.
     *
     * @throws SejourNotFoundException if sejour not found
     * @throws InvalidReservationException if business rules violated
     */
    public function __invoke(CreateReservationCommand $command): ReservationId
    {
        // 1. Validate and load dependencies
        $sejour = $this->sejourRepository->findById(
            SejourId::fromString($command->sejourId)
        );

        $clientEmail = Email::fromString($command->clientEmail);

        // 2. Create the reservation (Domain)
        $reservation = Reservation::create(
            ReservationId::generate(),
            $clientEmail,
            $sejour
        );

        // 3. Add participants
        foreach ($command->participants as $participantData) {
            $participant = Participant::create(
                ParticipantId::generate(),
                PersonName::fromString($participantData['nom']),
                $participantData['age']
            );

            $reservation->addParticipant($participant);
        }

        // 4. Calculate price (Domain Service)
        $montantTotal = $this->pricingService->calculateTotalPrice($reservation);
        $reservation->setMontantTotal($montantTotal);

        // 5. Persist
        $this->reservationRepository->save($reservation);

        // 6. Dispatch domain events
        foreach ($reservation->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        // 7. Return the ID (Commands return void or ID)
        return $reservation->getId();
    }
}
```

**Caractéristiques CommandHandler:**
- ✅ `final readonly class`
- ✅ Attribut `#[AsMessageHandler]` pour Symfony Messenger
- ✅ Méthode `__invoke()` pour invocation directe
- ✅ Retourne `void` ou ID (pas l'entité complète)
- ✅ Orchestration: Repository + Domain Services + EventBus
- ✅ Validation métier (domaine)
- ✅ Gère les exceptions métier
- ✅ Suffixe `CommandHandler`

### [TEMPLATE] Créer template Query (0.5h)

**Template Query:**

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\Query\GetReservationDetails;

use App\Domain\Reservation\ValueObject\ReservationId;

/**
 * Query to get detailed information about a reservation.
 *
 * Queries are simple DTOs that represent a request for data.
 * They are immutable and contain only search criteria.
 */
final readonly class GetReservationDetailsQuery
{
    public function __construct(
        public string $reservationId,
    ) {}

    public function getReservationId(): ReservationId
    {
        return ReservationId::fromString($this->reservationId);
    }
}
```

**Caractéristiques Query:**
- ✅ `final readonly class`
- ✅ Public properties (critères de recherche)
- ✅ Pas de logique
- ✅ Nommé avec "Get", "List", "Search", "Find"
- ✅ Suffixe `Query`

### [TEMPLATE] Créer template QueryHandler (1h)

**Template QueryHandler:**

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\Query\GetReservationDetails;

use App\Domain\Reservation\Repository\ReservationRepositoryInterface;
use App\Domain\Reservation\Exception\ReservationNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for GetReservationDetailsQuery.
 *
 * Returns a detailed DTO with all reservation information.
 */
#[AsMessageHandler]
final readonly class GetReservationDetailsQueryHandler
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
    ) {}

    /**
     * Execute the query.
     *
     * @throws ReservationNotFoundException if reservation not found
     */
    public function __invoke(GetReservationDetailsQuery $query): ReservationDetailsDTO
    {
        $reservation = $this->reservationRepository->findById(
            $query->getReservationId()
        );

        if ($reservation === null) {
            throw ReservationNotFoundException::withId($query->getReservationId());
        }

        // ✅ Convert to DTO (never return entities from Application layer)
        return ReservationDetailsDTO::fromEntity($reservation);
    }
}
```

**Caractéristiques QueryHandler:**
- ✅ `final readonly class`
- ✅ Attribut `#[AsMessageHandler]`
- ✅ Méthode `__invoke()`
- ✅ Retourne un DTO (JAMAIS une entité)
- ✅ Lecture seule (pas de save())
- ✅ Optimisation possible (queries SQL custom)
- ✅ Suffixe `QueryHandler`

### [TEMPLATE] Créer template DTO (0.5h)

**Template DTO (Data Transfer Object):**

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\Query\GetReservationDetails;

use App\Domain\Reservation\Entity\Reservation;
use App\Domain\Reservation\Entity\Participant;

/**
 * DTO for reservation details.
 *
 * DTOs are simple data structures used to transfer data
 * between Application and Presentation layers.
 * They decouple the Domain entities from external layers.
 */
final readonly class ReservationDetailsDTO
{
    /**
     * @param list<ParticipantDTO> $participants
     */
    public function __construct(
        public string $id,
        public string $clientEmail,
        public string $sejourTitre,
        public float $montantTotal,
        public string $statut,
        public array $participants,
        public string $createdAt,
    ) {}

    /**
     * Create DTO from Domain entity.
     */
    public static function fromEntity(Reservation $reservation): self
    {
        return new self(
            id: $reservation->getId()->getValue(),
            clientEmail: (string) $reservation->getClientEmail(),
            sejourTitre: $reservation->getSejour()->getTitre(),
            montantTotal: $reservation->getMontantTotal()->getAmountEuros(),
            statut: $reservation->getStatut()->value,
            participants: array_map(
                fn(Participant $p) => ParticipantDTO::fromEntity($p),
                $reservation->getParticipants()
            ),
            createdAt: $reservation->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'clientEmail' => $this->clientEmail,
            'sejourTitre' => $this->sejourTitre,
            'montantTotal' => $this->montantTotal,
            'statut' => $this->statut,
            'participants' => array_map(
                fn(ParticipantDTO $p) => $p->toArray(),
                $this->participants
            ),
            'createdAt' => $this->createdAt,
        ];
    }
}

/**
 * DTO for participant information.
 */
final readonly class ParticipantDTO
{
    public function __construct(
        public string $id,
        public string $nom,
        public int $age,
        public bool $isEnfant,
        public bool $isBebe,
    ) {}

    public static function fromEntity(Participant $participant): self
    {
        return new self(
            id: $participant->getId()->getValue(),
            nom: (string) $participant->getNom(),
            age: $participant->getAge(),
            isEnfant: $participant->isEnfant(),
            isBebe: $participant->isBebe(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'age' => $this->age,
            'isEnfant' => $this->isEnfant,
            'isBebe' => $this->isBebe,
        ];
    }
}
```

**Caractéristiques DTO:**
- ✅ `final readonly class`
- ✅ Public properties (données brutes)
- ✅ Factory `fromEntity()` pour conversion
- ✅ Méthode `toArray()` pour JSON
- ✅ Pas de logique métier
- ✅ Types primitifs (string, int, float, bool, array)
- ✅ Suffixe `DTO`

### [CONFIG] Configurer Symfony Messenger (0.5h)

**Configuration services.yaml:**

```yaml
# config/services.yaml

services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Application Layer - Handlers auto-discovery
    App\Application\:
        resource: '../src/Application'
        tags:
            - { name: messenger.message_handler }

    # Command Handlers
    App\Application\*\UseCase\*\*CommandHandler:
        tags:
            - { name: messenger.message_handler, bus: command.bus }

    # Query Handlers
    App\Application\*\Query\*\*QueryHandler:
        tags:
            - { name: messenger.message_handler, bus: query.bus }

    # Event Handlers
    App\Application\*\EventHandler\*:
        tags:
            - { name: messenger.message_handler, bus: event.bus }
```

**Configuration messenger.yaml:**

```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        # ✅ Multiple buses (Command, Query, Event)
        default_bus: command.bus

        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction

            query.bus:
                middleware:
                    - validation

            event.bus:
                middleware:
                    - allow_no_handlers

        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            # Commands synchrones par défaut
            'App\Application\*\UseCase\*\*Command': command.bus

            # Queries synchrones
            'App\Application\*\Query\*\*Query': query.bus

            # Events asynchrones
            'App\Domain\*\Event\*': async
```

### [EXAMPLE] Créer exemple complet CreateReservation (2h)

#### CreateReservationCommand.php

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\UseCase\CreateReservation;

/**
 * Command to create a new reservation.
 */
final readonly class CreateReservationCommand
{
    /**
     * @param string $sejourId
     * @param string $clientEmail
     * @param list<array{nom: string, age: int}> $participants
     */
    public function __construct(
        public string $sejourId,
        public string $clientEmail,
        public array $participants,
    ) {}
}
```

#### CreateReservationCommandHandler.php

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\UseCase\CreateReservation;

use App\Domain\Reservation\Entity\Reservation;
use App\Domain\Reservation\Entity\Participant;
use App\Domain\Reservation\Repository\ReservationRepositoryInterface;
use App\Domain\Reservation\Service\ReservationPricingService;
use App\Domain\Reservation\ValueObject\ReservationId;
use App\Domain\Reservation\ValueObject\ParticipantId;
use App\Domain\Sejour\Repository\SejourRepositoryInterface;
use App\Domain\Sejour\ValueObject\SejourId;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\PersonName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handler for CreateReservationCommand.
 */
#[AsMessageHandler]
final readonly class CreateReservationCommandHandler
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
        private SejourRepositoryInterface $sejourRepository,
        private ReservationPricingService $pricingService,
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(CreateReservationCommand $command): ReservationId
    {
        // 1. Load dependencies
        $sejour = $this->sejourRepository->findById(
            SejourId::fromString($command->sejourId)
        );

        $clientEmail = Email::fromString($command->clientEmail);

        // 2. Create reservation
        $reservation = Reservation::create(
            ReservationId::generate(),
            $clientEmail,
            $sejour
        );

        // 3. Add participants
        foreach ($command->participants as $participantData) {
            $participant = Participant::create(
                ParticipantId::generate(),
                PersonName::fromString($participantData['nom']),
                $participantData['age']
            );

            $reservation->addParticipant($participant);
        }

        // 4. Calculate price
        $montantTotal = $this->pricingService->calculateTotalPrice($reservation);
        $reservation->setMontantTotal($montantTotal);

        // 5. Persist
        $this->reservationRepository->save($reservation);

        // 6. Dispatch events
        foreach ($reservation->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        // 7. Return ID
        return $reservation->getId();
    }
}
```

#### GetReservationDetailsQuery.php

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\Query\GetReservationDetails;

/**
 * Query to get detailed information about a reservation.
 */
final readonly class GetReservationDetailsQuery
{
    public function __construct(
        public string $reservationId,
    ) {}
}
```

#### GetReservationDetailsQueryHandler.php

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\Query\GetReservationDetails;

use App\Domain\Reservation\Repository\ReservationRepositoryInterface;
use App\Domain\Reservation\ValueObject\ReservationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for GetReservationDetailsQuery.
 */
#[AsMessageHandler]
final readonly class GetReservationDetailsQueryHandler
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
    ) {}

    public function __invoke(GetReservationDetailsQuery $query): ReservationDetailsDTO
    {
        $reservationId = ReservationId::fromString($query->reservationId);

        $reservation = $this->reservationRepository->findById($reservationId);

        if ($reservation === null) {
            throw ReservationNotFoundException::withId($reservationId);
        }

        // ✅ Return DTO (never return entities)
        return ReservationDetailsDTO::fromEntity($reservation);
    }
}
```

#### ReservationDetailsDTO.php

Voir section [TEMPLATE] DTO ci-dessus.

### [EXAMPLE] Utilisation dans Controllers (0.5h)

**Controller avec CommandHandler:**

```php
<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Web;

use App\Application\Reservation\UseCase\CreateReservation\CreateReservationCommand;
use App\Application\Reservation\UseCase\CreateReservation\CreateReservationCommandHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationController extends AbstractController
{
    public function __construct(
        private readonly CreateReservationCommandHandler $createReservationHandler,
    ) {}

    #[Route('/reservations/create', name: 'reservation_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // 1. Get form data
        $data = $request->request->all();

        // 2. Create Command
        $command = new CreateReservationCommand(
            sejourId: $data['sejour_id'],
            clientEmail: $data['email'],
            participants: $data['participants'],
        );

        try {
            // 3. Execute Command (via handler)
            $reservationId = ($this->createReservationHandler)($command);

            $this->addFlash('success', 'Réservation créée avec succès');

            return $this->redirectToRoute('reservation_show', [
                'id' => (string) $reservationId,
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('reservation_new');
        }
    }
}
```

**Controller avec QueryHandler:**

```php
<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Web;

use App\Application\Reservation\Query\GetReservationDetails\GetReservationDetailsQuery;
use App\Application\Reservation\Query\GetReservationDetails\GetReservationDetailsQueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationController extends AbstractController
{
    public function __construct(
        private readonly GetReservationDetailsQueryHandler $queryHandler,
    ) {}

    #[Route('/reservations/{id}', name: 'reservation_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        // 1. Create Query
        $query = new GetReservationDetailsQuery($id);

        // 2. Execute Query
        $dto = ($this->queryHandler)($query);

        // 3. Render with DTO
        return $this->render('reservation/show.html.twig', [
            'reservation' => $dto,
        ]);
    }
}
```

### [EXAMPLE] EventHandler pour Domain Events (0.5h)

**Template EventHandler:**

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\EventHandler;

use App\Domain\Reservation\Event\ReservationConfirmedEvent;
use App\Domain\Notification\Service\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Event handler: Send confirmation email when reservation is confirmed.
 *
 * Event handlers react to domain events and trigger side effects
 * (notifications, updates, etc.).
 */
#[AsMessageHandler]
final readonly class SendConfirmationEmailOnReservationConfirmed
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
    ) {}

    public function __invoke(ReservationConfirmedEvent $event): void
    {
        // ✅ Side effect: Send email
        $this->notificationService->sendReservationConfirmation(
            $event->getReservationId()
        );

        // Event handlers return void (fire and forget)
    }
}
```

### [TEST] Créer tests des handlers (1.5h)

**Test CommandHandler:**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Reservation\UseCase\CreateReservation;

use App\Application\Reservation\UseCase\CreateReservation\CreateReservationCommand;
use App\Application\Reservation\UseCase\CreateReservation\CreateReservationCommandHandler;
use App\Domain\Reservation\Repository\ReservationRepositoryInterface;
use App\Domain\Sejour\Repository\SejourRepositoryInterface;
use App\Domain\Reservation\Service\ReservationPricingService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateReservationCommandHandlerTest extends TestCase
{
    private CreateReservationCommandHandler $handler;
    private ReservationRepositoryInterface&MockObject $reservationRepository;
    private SejourRepositoryInterface&MockObject $sejourRepository;
    private ReservationPricingService&MockObject $pricingService;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(ReservationRepositoryInterface::class);
        $this->sejourRepository = $this->createMock(SejourRepositoryInterface::class);
        $this->pricingService = $this->createMock(ReservationPricingService::class);

        $this->handler = new CreateReservationCommandHandler(
            $this->reservationRepository,
            $this->sejourRepository,
            $this->pricingService,
            $this->createMock(MessageBusInterface::class)
        );
    }

    /**
     * @test
     */
    public function it_creates_a_reservation_with_participants(): void
    {
        // Given
        $command = new CreateReservationCommand(
            sejourId: 'sejour-uuid',
            clientEmail: 'client@example.com',
            participants: [
                ['nom' => 'Jean Dupont', 'age' => 30],
                ['nom' => 'Marie Dupont', 'age' => 28],
            ]
        );

        $sejour = $this->createMockSejour();
        $this->sejourRepository
            ->method('findById')
            ->willReturn($sejour);

        $this->pricingService
            ->method('calculateTotalPrice')
            ->willReturn(Money::fromEuros(1000));

        // Expect: save called once
        $this->reservationRepository
            ->expects(self::once())
            ->method('save');

        // When
        $reservationId = ($this->handler)($command);

        // Then
        self::assertInstanceOf(ReservationId::class, $reservationId);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_sejour_not_found(): void
    {
        // Given
        $command = new CreateReservationCommand(
            sejourId: 'non-existent',
            clientEmail: 'client@example.com',
            participants: []
        );

        $this->sejourRepository
            ->method('findById')
            ->willThrowException(new SejourNotFoundException());

        // Expect
        $this->expectException(SejourNotFoundException::class);

        // When
        ($this->handler)($command);
    }
}
```

**Test QueryHandler:**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Reservation\Query\GetReservationDetails;

use App\Application\Reservation\Query\GetReservationDetails\GetReservationDetailsQuery;
use App\Application\Reservation\Query\GetReservationDetails\GetReservationDetailsQueryHandler;
use App\Application\Reservation\Query\GetReservationDetails\ReservationDetailsDTO;
use App\Domain\Reservation\Repository\ReservationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetReservationDetailsQueryHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_reservation_details_as_dto(): void
    {
        // Given
        $query = new GetReservationDetailsQuery('reservation-uuid');

        $reservation = $this->createMockReservation();
        $repository = $this->createMock(ReservationRepositoryInterface::class);
        $repository->method('findById')->willReturn($reservation);

        $handler = new GetReservationDetailsQueryHandler($repository);

        // When
        $dto = ($handler)($query);

        // Then
        self::assertInstanceOf(ReservationDetailsDTO::class, $dto);
        self::assertEquals('reservation-uuid', $dto->id);
        self::assertEquals('client@example.com', $dto->clientEmail);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_reservation_not_found(): void
    {
        // Given
        $query = new GetReservationDetailsQuery('non-existent');

        $repository = $this->createMock(ReservationRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        $handler = new GetReservationDetailsQueryHandler($repository);

        // Expect
        $this->expectException(ReservationNotFoundException::class);

        // When
        ($handler)($query);
    }
}
```

### [DOC] Documenter CQRS pattern (0.5h)

**Créer `.claude/examples/cqrs-pattern.md`:**

```markdown
# CQRS Pattern - Atoll Tourisme

## Principe

**CQRS (Command Query Responsibility Segregation)** : Séparer les mutations (Commands) des lectures (Queries).

### Commands (Write)
- Modifient l'état du système
- Retournent `void` ou ID
- Validations métier
- Transactionnelles

### Queries (Read)
- Lisent l'état du système
- Retournent des DTOs
- Optimisables (SQL custom)
- Pas de modifications

## Structure

```
Application/
└── Reservation/
    ├── UseCase/
    │   └── CreateReservation/
    │       ├── CreateReservationCommand.php
    │       └── CreateReservationCommandHandler.php
    ├── Query/
    │   └── GetReservationDetails/
    │       ├── GetReservationDetailsQuery.php
    │       ├── GetReservationDetailsQueryHandler.php
    │       └── ReservationDetailsDTO.php
    └── EventHandler/
        └── SendConfirmationEmail.php
```

## Exemple complet

Voir US-009 pour exemples détaillés.

## Règles

1. Commands retournent `void` ou ID (jamais l'entité)
2. Queries retournent des DTOs (jamais l'entité)
3. Handlers avec `__invoke()` et `#[AsMessageHandler]`
4. DTOs immutables (`readonly`)
5. Validation métier dans les Handlers
6. Un Handler = Une responsabilité

## Avantages

- ✅ Séparation claire read/write
- ✅ Optimisation indépendante (queries custom)
- ✅ Testabilité (mock facile)
- ✅ Évolutivité (nouveaux handlers sans impact)
```

### [VALIDATION] Valider structure et configuration (0.5h)

**Commandes de validation:**

```bash
# Autoload
make composer CMD="dump-autoload"

# Vérifier namespace
make phpstan

# Lister les handlers Messenger
make console CMD="debug:messenger"

# Expected output:
# command.bus
#   App\Application\Reservation\UseCase\CreateReservation\CreateReservationCommand
#     handled by App\Application\Reservation\UseCase\CreateReservation\CreateReservationCommandHandler
#
# query.bus
#   App\Application\Reservation\Query\GetReservationDetails\GetReservationDetailsQuery
#     handled by App\Application\Reservation\Query\GetReservationDetails\GetReservationDetailsQueryHandler

# Tests
make test-unit
```

---

## Définition de Done (DoD)

- [ ] Structure `src/Application/{Context}/` créée pour tous les Bounded Contexts
- [ ] Sous-répertoires `UseCase/`, `Query/`, `DTO/`, `EventHandler/` créés
- [ ] Template Command créé avec exemple
- [ ] Template CommandHandler créé avec `#[AsMessageHandler]`
- [ ] Template Query créé avec exemple
- [ ] Template QueryHandler créé avec `#[AsMessageHandler]`
- [ ] Template DTO créé avec `fromEntity()` et `toArray()`
- [ ] Exemple complet `CreateReservation` implémenté (Command + Handler + Query + QueryHandler + DTO)
- [ ] Configuration Symfony Messenger pour buses (command.bus, query.bus, event.bus)
- [ ] Configuration `services.yaml` avec auto-tagging handlers
- [ ] EventHandler template créé pour Domain Events
- [ ] `composer dump-autoload` passe sans erreur
- [ ] `debug:messenger` liste tous les handlers
- [ ] Tests unitaires des handlers passent
- [ ] Couverture tests ≥ 80% sur handlers
- [ ] PHPStan niveau max passe sur Application layer
- [ ] Documentation CQRS créée dans `.claude/examples/cqrs-pattern.md`
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(application): create Application layer structure with CQRS pattern`

---

## Notes techniques

### CQRS (Command Query Responsibility Segregation)

**Principe:** Séparer strictement les opérations de lecture (Queries) et d'écriture (Commands).

**Avantages:**
1. **Séparation des préoccupations** - Read et Write indépendants
2. **Optimisation ciblée** - Queries optimisées différemment des Commands
3. **Scalabilité** - Read replicas pour queries, Write master pour commands
4. **Testabilité** - Handlers testables unitairement avec mocks
5. **Évolutivité** - Nouveaux handlers sans impacter l'existant

### Command vs Query

| Aspect | Command | Query |
|--------|---------|-------|
| **But** | Modifier l'état | Lire l'état |
| **Retour** | `void` ou ID | DTO |
| **Side effects** | Oui (save, events) | Non |
| **Validation** | Métier stricte | Basique |
| **Transaction** | Oui | Non |
| **Cache** | Invalide | Utilise |

### Bus Separation

```
┌──────────────────────────────────────┐
│  command.bus                         │
│  - Transactionnel (doctrine_trans.)  │
│  - Validation métier                 │
│  - Un seul handler par command       │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│  query.bus                           │
│  - Lecture seule                     │
│  - Validation basique                │
│  - Optimisable (cache)               │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│  event.bus                           │
│  - Asynchrone                        │
│  - Multiple handlers possibles       │
│  - Fire and forget                   │
└──────────────────────────────────────┘
```

### Symfony Messenger Integration

**Middleware pipeline:**

```
Command → [validation] → [doctrine_transaction] → Handler → Result
Query   → [validation] → Handler → DTO
Event   → [async] → [allow_no_handlers] → Handlers (0..*)
```

**Validation middleware:**
- Valide les contraintes Symfony Validator sur Command/Query
- Échoue avant le handler si invalide

**Doctrine transaction middleware:**
- Entoure le handler dans une transaction
- Rollback automatique si exception
- Uniquement pour command.bus

### Use Case Organization

**Convention de nommage:**

```
Application/{Context}/UseCase/{Action}/
├── {Action}Command.php
└── {Action}CommandHandler.php

Application/{Context}/Query/{Action}/
├── {Action}Query.php
├── {Action}QueryHandler.php
└── {Action}DTO.php
```

**Exemples:**
- `Application/Reservation/UseCase/CreateReservation/`
- `Application/Reservation/UseCase/ConfirmReservation/`
- `Application/Reservation/Query/GetReservationDetails/`
- `Application/Client/UseCase/UpdateClientEmail/`
- `Application/Client/Query/SearchClients/`

### Testing Strategy

**Tests unitaires:**
- Mock les repositories et services
- Vérifier orchestration (appels corrects)
- Vérifier retours (ID ou DTO)
- Vérifier exceptions métier

**Tests d'intégration:**
- Base de données réelle
- Vérifier persistance
- Vérifier domain events dispatchés
- Vérifier side effects (emails, etc.)

### Anti-patterns à éviter

#### ❌ Logique métier dans Handler

```php
// MAUVAIS
public function __invoke(CreateReservationCommand $command): void
{
    $reservation = new Reservation();

    // ❌ Logique métier dans le Handler
    if (count($command->participants) > 10) {
        throw new Exception('Too many participants');
    }

    $reservation->setParticipants($command->participants);
}

// BON
public function __invoke(CreateReservationCommand $command): void
{
    $reservation = Reservation::create(/* ... */);

    // ✅ Logique métier dans le Domain
    foreach ($command->participants as $data) {
        $reservation->addParticipant(/* ... */); // Validation dans addParticipant()
    }
}
```

#### ❌ Retourner une entité depuis Query

```php
// MAUVAIS
public function __invoke(GetReservationQuery $query): Reservation
{
    return $this->repository->findById($query->id); // ❌ Retourne entité
}

// BON
public function __invoke(GetReservationQuery $query): ReservationDTO
{
    $reservation = $this->repository->findById($query->id);
    return ReservationDTO::fromEntity($reservation); // ✅ Retourne DTO
}
```

#### ❌ Command qui fait une lecture

```php
// MAUVAIS
class GetClientCommand {} // ❌ "Get" est une Query, pas Command

// BON
class GetClientQuery {}   // ✅ Query pour lecture
class UpdateClientCommand {} // ✅ Command pour mutation
```

### Validation Rules

**Commands:**
- ✅ Verbe d'action (Create, Update, Delete, Confirm, Cancel)
- ✅ Représente une intention de modification
- ✅ Immutable (`readonly`)
- ✅ Pas de logique

**Queries:**
- ✅ Verbe de lecture (Get, List, Search, Find)
- ✅ Représente une demande de données
- ✅ Immutable (`readonly`)
- ✅ Pas de logique

**Handlers:**
- ✅ Méthode `__invoke()` unique
- ✅ Attribut `#[AsMessageHandler]`
- ✅ `final readonly class`
- ✅ Orchestration uniquement (pas de logique métier)
- ✅ Gère les exceptions métier
- ✅ CommandHandler retourne `void` ou ID
- ✅ QueryHandler retourne DTO

**DTOs:**
- ✅ `final readonly class`
- ✅ Public properties
- ✅ Factory `fromEntity()`
- ✅ Méthode `toArray()` pour JSON
- ✅ Pas de logique métier
- ✅ Types primitifs uniquement

---

## Dépendances

### Bloquantes

- **US-001**: Structure Domain/Application créée (nécessite `src/Application/`)
- **US-002, US-004, US-006**: Entités Domain pures (utilisées dans handlers)
- **US-008**: Controllers déplacés (utiliseront les handlers)

### Bloque

- **US-020 à US-031**: Repository abstraction (handlers utiliseront les interfaces)
- **Tous les Use Cases futurs**: Suivront ce pattern CQRS

---

## Références

- `.claude/rules/21-cqrs.md` (CQRS pattern détaillé)
- `.claude/rules/02-architecture-clean-ddd.md` (lignes 182-265, Application layer)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 156-195, Use Cases manquants)
- **Livre:** *Implementing Domain-Driven Design* - Vaughn Vernon, Chapitre 4 (Architecture)
- **Article:** [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html) - Martin Fowler
- **Symfony Messenger:** [Documentation](https://symfony.com/doc/current/messenger.html)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |
