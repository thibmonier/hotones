# EPIC-004: Domain Services et Domain Events

**Statut**: 📋 Backlog
**Priorité**: 🟡 HAUTE
**Effort Estimé**: 2 sprints (Phase 3)
**Business Value**: 🟡 ÉLEVÉ
**Risque Technique**: 🟠 MOYEN

---

## Vue d'ensemble

Créer les **Domain Services** pour la logique métier complexe et implémenter les **Domain Events** pour découpler les side-effects. Cette phase complète l'isolation de la logique métier et introduit l'architecture event-driven.

### Problème adressé

**Audit Report - Problem #5**: Absence d'Aggregates
- **Score actuel**: Aggregates et Repositories 1/5 ❌
- **Impact**: Incohérence des données, transactions mal gérées, invariants non garantis
- **Fichiers concernés**: Toutes les entités

**Audit Report - Checklist Phase 3**: Domain Services et Events
- Identifier et créer les Aggregates
- Extraire les Domain Services
- Implémenter les Domain Events
- Ajouter les Event Handlers dans Application

### Solution proposée

Création d'Aggregates avec Domain Services et Event-Driven Architecture :

```php
src/Domain/[Context]/
├── Entity/
│   └── [AggregateRoot].php      # Avec Domain Events
├── Service/
│   └── [BusinessLogic]Service.php
├── Event/
│   ├── [Aggregate]CreatedEvent.php
│   ├── [Aggregate]UpdatedEvent.php
│   └── [Aggregate]DeletedEvent.php
└── Exception/
    └── [Business]Exception.php

src/Application/[Context]/EventHandler/
└── [ActionOn][Event]Handler.php
```

---

## Objectifs métier

### Bénéfices attendus

1. **Cohérence des données garantie**
   - Aggregates protègent les invariants métier
   - Transactions limitées à un seul aggregate
   - Règles de gestion centralisées

2. **Architecture event-driven**
   - Découplage entre modules (Bounded Contexts)
   - Side-effects asynchrones (emails, notifications)
   - Traçabilité complète des actions métier

3. **Logique métier explicite**
   - Domain Services pour calculs complexes
   - Méthodes métier nommées (pas de setters génériques)
   - Tests unitaires simplifiés

4. **Réduction couplage temporel**
   - Communication via événements (pas d'appels directs)
   - Modules indépendants
   - Ajout de fonctionnalités sans modification code existant

---

## Exigences liées

- **REQ-006**: Aggregates et Aggregate Roots
- **REQ-007**: Domain Events
- **REQ-008**: Validation Automatisée (Deptrac - partiel)

---

## User Stories associées

### Phase 3: Aggregates (Sprint 5)

- **US-032**: Identifier les Aggregates métier (Reservation, Client, Sejour)
- **US-033**: Créer l'Aggregate Root Client avec invariants
- **US-034**: Créer l'Aggregate Root Reservation avec Participant
- **US-035**: Créer l'Aggregate Root Sejour avec disponibilités
- **US-036**: Implémenter les méthodes métier sur Reservation (confirmer, annuler)
- **US-037**: Implémenter les méthodes métier sur Client (activate, block)

### Phase 3: Domain Services (Sprint 5)

- **US-038**: Créer ReservationPricingService pour calcul prix
- **US-039**: Créer DiscountPolicy interface + implémentations
- **US-040**: Créer SejourAvailabilityService pour vérifier disponibilités

### Phase 3: Domain Events (Sprint 6)

- **US-041**: Créer base DomainEvent interface
- **US-042**: Créer événements Reservation (Created, Confirmed, Cancelled)
- **US-043**: Créer événements Client (Created, Activated, Blocked)
- **US-044**: Implémenter Event Handlers dans Application layer

---

## Critères d'acceptation (EPIC)

### Aggregates créés

- [ ] Aggregate Root `Client` avec méthodes métier (activate, block)
- [ ] Aggregate Root `Reservation` avec `Participant` (addParticipant, removeParticipant, confirmer, annuler)
- [ ] Aggregate Root `Sejour` avec gestion disponibilités
- [ ] Toutes les modifications passent par l'Aggregate Root
- [ ] Invariants validés dans les méthodes métier
- [ ] Aucun accès direct aux entités enfants

### Domain Services créés

- [ ] `ReservationPricingService` avec calcul prix + remises
- [ ] `DiscountPolicyInterface` avec 3+ implémentations (FamilyDiscount, EarlyBooking, GroupDiscount)
- [ ] `SejourAvailabilityService` pour vérifier places disponibles
- [ ] Services injectés via constructeur (DI)
- [ ] Tests unitaires avec mocks des repositories

### Domain Events implémentés

- [ ] Interface `DomainEventInterface` avec `occurredOn()`
- [ ] `ReservationCreatedEvent`, `ReservationConfirmedEvent`, `ReservationCancelledEvent`
- [ ] `ClientCreatedEvent`, `ClientActivatedEvent`, `ClientBlockedEvent`
- [ ] `SejourPublishedEvent`, `SejourAvailabilityChangedEvent`
- [ ] Tous les événements sont `final readonly class`
- [ ] Événements enregistrés dans les Aggregates (`recordEvent()`)

### Event Handlers créés (Application)

- [ ] `SendConfirmationEmailOnReservationConfirmed`
- [ ] `SendCancellationEmailOnReservationCancelled`
- [ ] `UpdateStatisticsOnReservationCreated`
- [ ] `NotifyAdminOnClientBlocked`
- [ ] Handlers asynchrones via Symfony Messenger
- [ ] Tests d'intégration pour chaque handler

### Tests

- [ ] Tests unitaires Aggregates avec InMemory repositories
- [ ] Tests unitaires Domain Services avec mocks
- [ ] Tests d'intégration Event Handlers
- [ ] Couverture code ≥ 80% sur Domain Services
- [ ] Tests rapides (< 100ms unitaires, < 1s intégration)

### Documentation

- [ ] PHPDoc pour chaque Domain Service
- [ ] Diagramme Mermaid des événements par Aggregate
- [ ] Exemples d'usage dans `.claude/examples/domain-event-examples.md`
- [ ] ADR justifiant l'architecture event-driven

---

## Métriques de succès

| Métrique | Avant | Cible | Validation |
|----------|-------|-------|------------|
| **Aggregates identifiés** | 0 | 100% | 3+ Aggregate Roots |
| **Invariants protégés** | Non | Oui | Tests unitaires vérifient |
| **Couplage temporel** | Élevé | Faible | Communication via événements |
| **Side-effects synchrones** | 100% | 0% | Emails/stats via événements async |
| **Tests sans DB** | Impossible | Possible | InMemory repos utilisés |

---

## Dépendances

### Bloquantes (doivent être faites avant)

- **EPIC-001 Phase 1**: Entités Domain pures sans Doctrine (US-002, US-004, US-006)
- **EPIC-002**: Value Objects disponibles (Email, Money, IDs - US-010, US-012, US-013)
- **EPIC-003**: Repository interfaces disponibles (US-020, US-022, US-024)

### Bloquées par cet EPIC

- **EPIC-005**: Architecture Validation → nécessite Aggregates et Events complets

---

## Risques et mitigations

### Risque 1: Aggregates trop gros (God Object)

- **Probabilité**: Moyenne
- **Impact**: Élevé
- **Mitigation**:
  - Limiter les Aggregates à leur contexte métier strict
  - Règle: Maximum 200 lignes par Aggregate Root
  - Séparer les sous-domaines (Pricing, Availability)
  - Code reviews strictes sur la taille

### Risque 2: Événements perdus (non dispatchés)

- **Probabilité**: Faible
- **Impact**: Moyen
- **Mitigation**:
  - Tests d'intégration vérifiant les événements
  - EventBus centralisé dans Application layer
  - Monitoring des événements dispatchés
  - Retry strategy Symfony Messenger

### Risque 3: Complexité Event Handlers

- **Probabilité**: Moyenne
- **Impact**: Faible
- **Mitigation**:
  - Un handler = une responsabilité (SRP)
  - Handlers asynchrones via Messenger
  - Tests unitaires avec mocks
  - Documentation des handlers

---

## Approche d'implémentation

### Stratégie: Domain-First + Event-Driven

1. **Identifier les Aggregates** (analyse métier)
2. **Créer l'Aggregate Root** avec méthodes métier (TDD)
3. **Ajouter Domain Events** dans les méthodes
4. **Créer Domain Services** pour logique complexe
5. **Implémenter Event Handlers** dans Application
6. **Valider isolation** (tests unitaires sans DB)

### Ordre d'implémentation recommandé

1. **Reservation** (aggregate complexe prioritaire) ✅ Prioritaire
2. **Client** (aggregate simple, peu de règles)
3. **Sejour** (catalogue, disponibilités)
4. **Order** (si nécessaire pour pricing)

### Template Aggregate Root

```php
<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Entity;

use App\Domain\Reservation\ValueObject\ReservationId;
use App\Domain\Reservation\ValueObject\Money;
use App\Domain\Reservation\ValueObject\ReservationStatus;
use App\Domain\Reservation\Event\ReservationCreatedEvent;
use App\Domain\Reservation\Event\ReservationConfirmedEvent;
use App\Domain\Reservation\Event\ReservationCancelledEvent;
use App\Domain\Reservation\Event\ParticipantAddedEvent;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Shared\ValueObject\Email;

/**
 * Reservation Aggregate Root.
 *
 * Manages participants and enforces business invariants.
 * All modifications MUST go through business methods.
 */
final class Reservation
{
    private ReservationId $id;
    private Email $clientEmail;
    private Money $montantTotal;
    private ReservationStatus $statut;

    /** @var list<Participant> */
    private array $participants = [];

    /** @var list<DomainEventInterface> */
    private array $domainEvents = [];

    private function __construct(
        ReservationId $id,
        Email $clientEmail,
        Money $montantTotal
    ) {
        $this->id = $id;
        $this->clientEmail = $clientEmail;
        $this->montantTotal = $montantTotal;
        $this->statut = ReservationStatus::EN_ATTENTE;
    }

    public static function create(
        ReservationId $id,
        Email $clientEmail,
        Money $montantTotal
    ): self {
        $reservation = new self($id, $clientEmail, $montantTotal);

        // ✅ Domain Event: Reservation created
        $reservation->recordEvent(
            new ReservationCreatedEvent($id, $clientEmail)
        );

        return $reservation;
    }

    /**
     * Add a participant to this reservation.
     *
     * Business rules:
     * - Maximum 10 participants per reservation
     * - Participant must have valid age (0-120)
     *
     * @throws InvalidReservationException if max participants reached
     */
    public function addParticipant(Participant $participant): void
    {
        // ✅ Invariant: Maximum 10 participants
        if (count($this->participants) >= 10) {
            throw new InvalidReservationException('Maximum 10 participants');
        }

        $participant->assignToReservation($this);
        $this->participants[] = $participant;

        // ✅ Domain Event: Participant added
        $this->recordEvent(
            new ParticipantAddedEvent($this->id, $participant->getId())
        );
    }

    /**
     * Remove a participant from this reservation.
     *
     * @throws ParticipantNotFoundException if participant not found
     */
    public function removeParticipant(ParticipantId $participantId): void
    {
        foreach ($this->participants as $key => $participant) {
            if ($participant->getId()->equals($participantId)) {
                unset($this->participants[$key]);

                $this->recordEvent(
                    new ParticipantRemovedEvent($this->id, $participantId)
                );

                return;
            }
        }

        throw ParticipantNotFoundException::withId($participantId);
    }

    /**
     * Confirm this reservation.
     *
     * Business rules:
     * - Cannot confirm cancelled reservation
     * - At least one participant required
     *
     * @throws InvalidReservationException if cannot confirm
     */
    public function confirmer(): void
    {
        if ($this->statut === ReservationStatus::ANNULEE) {
            throw new InvalidReservationException(
                'Cannot confirm cancelled reservation'
            );
        }

        if (count($this->participants) === 0) {
            throw new InvalidReservationException(
                'At least one participant required'
            );
        }

        $this->statut = ReservationStatus::CONFIRMEE;

        // ✅ Domain Event: Reservation confirmed
        $this->recordEvent(new ReservationConfirmedEvent($this->id));
    }

    /**
     * Cancel this reservation.
     *
     * Business rules:
     * - Cannot cancel completed reservation
     *
     * @throws InvalidReservationException if cannot cancel
     */
    public function annuler(string $raison): void
    {
        if ($this->statut === ReservationStatus::TERMINEE) {
            throw new InvalidReservationException(
                'Cannot cancel completed reservation'
            );
        }

        $this->statut = ReservationStatus::ANNULEE;

        // ✅ Domain Event: Reservation cancelled
        $this->recordEvent(
            new ReservationCancelledEvent($this->id, $raison)
        );
    }

    /**
     * Update total amount (called by Domain Service).
     */
    public function setMontantTotal(Money $montant): void
    {
        $this->montantTotal = $montant;
    }

    // ✅ Domain Events management
    private function recordEvent(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    // Getters
    public function getId(): ReservationId
    {
        return $this->id;
    }

    public function getClientEmail(): Email
    {
        return $this->clientEmail;
    }

    public function getMontantTotal(): Money
    {
        return $this->montantTotal;
    }

    public function getStatut(): ReservationStatus
    {
        return $this->statut;
    }

    /**
     * @return list<Participant>
     */
    public function getParticipants(): array
    {
        return $this->participants;
    }
}
```

### Template Domain Service (Pricing)

```php
<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Service;

use App\Domain\Reservation\Entity\Reservation;
use App\Domain\Reservation\Entity\Participant;
use App\Domain\Reservation\ValueObject\Money;
use App\Domain\Reservation\Pricing\DiscountPolicyInterface;
use App\Domain\Sejour\Entity\Sejour;

/**
 * Domain Service for calculating reservation prices.
 *
 * Handles complex pricing logic that doesn't belong to any single entity.
 */
final readonly class ReservationPricingService
{
    /**
     * @param iterable<DiscountPolicyInterface> $discountPolicies
     */
    public function __construct(
        private iterable $discountPolicies,
    ) {}

    public function calculateTotalPrice(Reservation $reservation, Sejour $sejour): Money
    {
        $basePrice = $this->calculateBasePrice($reservation, $sejour);

        return $this->applyDiscounts($basePrice, $reservation);
    }

    private function calculateBasePrice(Reservation $reservation, Sejour $sejour): Money
    {
        $total = Money::zero();

        foreach ($reservation->getParticipants() as $participant) {
            $participantPrice = $this->calculateParticipantPrice(
                $participant,
                $sejour->getPrixBase()
            );

            $total = $total->add($participantPrice);
        }

        return $total;
    }

    private function calculateParticipantPrice(
        Participant $participant,
        Money $basePrice
    ): Money {
        // Gratuit pour les bébés (< 3 ans)
        if ($participant->isBebe()) {
            return Money::zero();
        }

        // 50% pour les enfants (< 18 ans)
        if ($participant->isEnfant()) {
            return $basePrice->multiply(0.5);
        }

        return $basePrice;
    }

    private function applyDiscounts(Money $amount, Reservation $reservation): Money
    {
        foreach ($this->discountPolicies as $policy) {
            if ($policy->isApplicable($reservation)) {
                $amount = $policy->apply($amount, $reservation);
            }
        }

        return $amount;
    }
}
```

### Template Domain Event

```php
<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Event;

use App\Domain\Reservation\ValueObject\ReservationId;
use App\Domain\Shared\Interface\DomainEventInterface;

/**
 * Domain Event: Reservation was confirmed.
 *
 * Emitted when a reservation transitions to CONFIRMEE status.
 */
final readonly class ReservationConfirmedEvent implements DomainEventInterface
{
    public function __construct(
        private ReservationId $reservationId,
        private \DateTimeImmutable $occurredOn = new \DateTimeImmutable(),
    ) {}

    public function getReservationId(): ReservationId
    {
        return $this->reservationId;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function toArray(): array
    {
        return [
            'reservationId' => $this->reservationId->getValue(),
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }
}
```

### Template Event Handler (Application)

```php
<?php

declare(strict_types=1);

namespace App\Application\Reservation\EventHandler;

use App\Domain\Reservation\Event\ReservationConfirmedEvent;
use App\Domain\Notification\Service\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Event Handler: Send confirmation email when reservation is confirmed.
 *
 * Async handler via Symfony Messenger.
 */
#[AsMessageHandler]
final readonly class SendConfirmationEmailOnReservationConfirmed
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
    ) {}

    public function __invoke(ReservationConfirmedEvent $event): void
    {
        // ✅ Asynchronous side-effect (email sending)
        $this->notificationService->sendReservationConfirmation(
            $event->getReservationId()
        );
    }
}
```

### Symfony Service Configuration

```yaml
# config/services.yaml

services:
    # Domain Services
    App\Domain\Reservation\Service\ReservationPricingService:
        arguments:
            $discountPolicies: !tagged_iterator app.discount_policy

    # Discount Policies (tagged)
    App\Domain\Reservation\Pricing\Policy\FamilyDiscountPolicy:
        tags: ['app.discount_policy']

    App\Domain\Reservation\Pricing\Policy\EarlyBookingDiscountPolicy:
        tags: ['app.discount_policy']

    App\Domain\Reservation\Pricing\Policy\GroupDiscountPolicy:
        tags: ['app.discount_policy']

    # Event Handlers (auto-registered via AsMessageHandler attribute)
    App\Application\Reservation\EventHandler\:
        resource: '../src/Application/Reservation/EventHandler'
        tags: ['messenger.message_handler']
```

### Symfony Messenger Configuration

```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        failure_transport: failed

        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
                    max_delay: 10000

            failed: 'doctrine://default?queue_name=failed'

        routing:
            # ✅ Domain Events → Async
            'App\Domain\*\Event\*': async
```

### Validation continue

- À chaque Aggregate créé:
  - [ ] Tests unitaires avec InMemory repository
  - [ ] Invariants validés dans les méthodes
  - [ ] Domain Events enregistrés correctement
  - [ ] PHPStan niveau max passe
  - [ ] Deptrac valide sans violation

- À chaque Domain Service créé:
  - [ ] Interface si plusieurs implémentations possibles
  - [ ] Tests unitaires avec mocks
  - [ ] Injection via constructeur
  - [ ] Pas de dépendance Infrastructure

- À chaque Domain Event créé:
  - [ ] Classe `final readonly`
  - [ ] Nom au passé (`ClientCreatedEvent`, pas `CreateClientEvent`)
  - [ ] Méthode `toArray()` pour serialization
  - [ ] Event Handler correspondant créé

---

## Références

### Documentation interne

- `.claude/rules/02-architecture-clean-ddd.md` - Architecture obligatoire
- `.claude/rules/13-ddd-patterns.md` - Patterns DDD détaillés
- `.claude/rules/19-aggregates.md` - Aggregate Roots patterns
- `.claude/rules/20-domain-events.md` - Domain Events patterns
- `.claude/examples/aggregate-examples.md` - Exemples complets
- `.claude/examples/domain-event-examples.md` - Exemples événements
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` - Audit source (lignes 182-191, 392-396)

### Checklist Phase 3 (Audit Report)

**Semaine 5-6** (lignes 392-396):
- [x] **Identifier et créer les Aggregates** - **EPIC-004**
- [x] **Extraire les Domain Services** - **EPIC-004**
- [x] **Implémenter les Domain Events** - **EPIC-004**
- [x] **Ajouter les Event Handlers dans Application** - **EPIC-004**

### Ressources externes

- [Aggregates - Vaughn Vernon](https://vaughnvernon.com/aggregates/)
- [Domain Events - Martin Fowler](https://martinfowler.com/eaaDev/DomainEvent.html)
- [Event-Driven Architecture](https://martinfowler.com/articles/201701-event-driven.html)
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création EPIC | Claude (via workflow-plan) |
| 2026-01-13 | Validation priorité HAUTE | Architecture audit Phase 3 |

---

## Notes

- **Prerequis**: EPIC-001 Phase 1, EPIC-002 (VOs), EPIC-003 (Repository interfaces) doivent être complétés
- **TDD obligatoire**: Tests avec InMemory repositories AVANT implémentation
- **Event-Driven**: Tous les side-effects (emails, stats) passent par événements asynchrones
- **Aggregate Rules**: Une transaction = Un aggregate, références entre aggregates par ID uniquement
- **Naming**: Events au passé (`ReservationConfirmedEvent`), Handlers explicites (`SendEmailOn...`)
- **Messenger**: Tous les Event Handlers asynchrones via Symfony Messenger pour découplage temporel
- **Definition of Done**: Voir `/Users/tmonier/Projects/hotones/project-management/prd.md` section "Définition de Done"
