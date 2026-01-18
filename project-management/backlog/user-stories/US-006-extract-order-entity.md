# US-006: Extraire l'entité Order vers Domain pur

**EPIC:** [EPIC-001](../epics/EPIC-001-clean-architecture-restructuring.md) - Restructuration Clean Architecture
**Priorité:** 🔴 CRITIQUE
**Points:** 8
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** extraire l'entité Order vers la couche Domain sans annotations Doctrine
**Afin de** découpler la logique métier de commande de l'infrastructure de persistance

---

## Critères d'acceptation

### GIVEN: L'entité Order existe avec annotations Doctrine dans src/Entity/

**WHEN:** J'extrais l'entité vers Domain pur

**THEN:**
- [ ] Nouvelle entité `src/Domain/Order/Entity/Order.php` créée sans annotations Doctrine
- [ ] Entité OrderLine créée `src/Domain/Order/Entity/OrderLine.php` (partie de l'agrégat)
- [ ] Propriétés converties en Value Objects (OrderId, Money, OrderStatus, ProductReference)
- [ ] Classe marquée `final` pour respecter les bonnes pratiques
- [ ] Constructor `private` avec factory method `create()` statique
- [ ] Méthodes métier ajoutées (pas d'entité anémique)
- [ ] Domain Events enregistrés (OrderCreated, OrderConfirmed, etc.)
- [ ] Aucune dépendance à Doctrine\\ORM\\Mapping
- [ ] Aucune dépendance à Symfony (sauf pour interfaces standard)

### GIVEN: L'entité Domain pure existe

**WHEN:** J'exécute PHPStan niveau max sur src/Domain/

**THEN:**
- [ ] Aucune erreur PHPStan
- [ ] Aucune dépendance détectée vers Doctrine ou Symfony
- [ ] Deptrac valide: Domain ne dépend de rien

### GIVEN: L'entité Domain pure existe

**WHEN:** J'exécute les tests unitaires

**THEN:**
- [ ] Tests unitaires passent sans base de données
- [ ] Tests unitaires s'exécutent en moins de 100ms
- [ ] Couverture code ≥ 90% sur l'entité Order
- [ ] Tests de création, validation et méthodes métier présents
- [ ] Tests de calcul de total et application de remises

---

## Tâches techniques

### [DOMAIN] Créer l'entité Order pure (3h)

**Avant (couplé à Doctrine):**
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: OrderRepository::class)]  // ❌ Doctrine
#[ORM\Table(name: 'orders')]
class Order implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false)]
    public Client $client { get; set; }

    #[ORM\Column(type: 'string', length: 50)]
    public string $status { get; set; }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    public string $totalAmount { get; set; }

    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    public Collection $lines { get; set; }

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt { get; set; }

    // ... autres propriétés avec annotations Doctrine
}
```

**Après (Domain pur):**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Interface\DomainEventInterface;

// ✅ Entité Domain pure (pas d'annotations Doctrine)
final class Order implements AggregateRootInterface
{
    private OrderId $id;
    private ClientId $clientId;
    private OrderStatus $status;
    private Money $totalAmount;
    private Money $subtotal;
    private Money $discountAmount;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $confirmedAt = null;
    private ?\DateTimeImmutable $shippedAt = null;
    private ?\DateTimeImmutable $completedAt = null;

    /** @var list<OrderLine> */
    private array $lines = [];

    /** @var list<DomainEventInterface> */
    private array $domainEvents = [];

    private function __construct(
        OrderId $id,
        ClientId $clientId
    ) {
        $this->id = $id;
        $this->clientId = $clientId;
        $this->status = OrderStatus::DRAFT;
        $this->subtotal = Money::zero();
        $this->discountAmount = Money::zero();
        $this->totalAmount = Money::zero();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        OrderId $id,
        ClientId $clientId
    ): self {
        $order = new self($id, $clientId);

        // ✅ Domain Event
        $order->recordEvent(new OrderCreatedEvent($id, $clientId));

        return $order;
    }

    // ✅ Logique métier (pas anémique)
    public function addLine(
        ProductReference $productRef,
        string $productName,
        Money $unitPrice,
        int $quantity
    ): void {
        if ($this->status !== OrderStatus::DRAFT) {
            throw new OrderNotEditableException('Cannot modify confirmed order');
        }

        if ($quantity <= 0) {
            throw new InvalidQuantityException('Quantity must be positive');
        }

        $line = OrderLine::create(
            OrderLineId::generate(),
            $productRef,
            $productName,
            $unitPrice,
            $quantity
        );

        $this->lines[] = $line;
        $this->recalculateTotal();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new OrderLineAddedEvent($this->id, $line->getId()));
    }

    public function removeLine(OrderLineId $lineId): void
    {
        if ($this->status !== OrderStatus::DRAFT) {
            throw new OrderNotEditableException('Cannot modify confirmed order');
        }

        foreach ($this->lines as $key => $line) {
            if ($line->getId()->equals($lineId)) {
                unset($this->lines[$key]);
                $this->lines = array_values($this->lines); // Reindex
                $this->recalculateTotal();
                $this->updatedAt = new \DateTimeImmutable();

                $this->recordEvent(new OrderLineRemovedEvent($this->id, $lineId));

                return;
            }
        }

        throw OrderLineNotFoundException::withId($lineId);
    }

    public function updateLineQuantity(OrderLineId $lineId, int $newQuantity): void
    {
        if ($this->status !== OrderStatus::DRAFT) {
            throw new OrderNotEditableException('Cannot modify confirmed order');
        }

        if ($newQuantity <= 0) {
            throw new InvalidQuantityException('Quantity must be positive');
        }

        foreach ($this->lines as $line) {
            if ($line->getId()->equals($lineId)) {
                $line->updateQuantity($newQuantity);
                $this->recalculateTotal();
                $this->updatedAt = new \DateTimeImmutable();

                $this->recordEvent(new OrderLineQuantityUpdatedEvent($this->id, $lineId, $newQuantity));

                return;
            }
        }

        throw OrderLineNotFoundException::withId($lineId);
    }

    public function applyDiscount(Money $discountAmount): void
    {
        if ($this->status !== OrderStatus::DRAFT) {
            throw new OrderNotEditableException('Cannot apply discount to confirmed order');
        }

        if ($discountAmount->isGreaterThan($this->subtotal)) {
            throw new InvalidDiscountException('Discount cannot exceed subtotal');
        }

        $this->discountAmount = $discountAmount;
        $this->recalculateTotal();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new DiscountAppliedEvent($this->id, $discountAmount));
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::DRAFT) {
            throw new InvalidOrderStatusTransitionException('Can only confirm draft orders');
        }

        if (count($this->lines) === 0) {
            throw new EmptyOrderException('Cannot confirm empty order');
        }

        if ($this->totalAmount->isZero()) {
            throw new InvalidOrderException('Cannot confirm order with zero total');
        }

        $this->status = OrderStatus::CONFIRMED;
        $this->confirmedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new OrderConfirmedEvent($this->id, $this->totalAmount));
    }

    public function cancel(string $reason): void
    {
        if ($this->status === OrderStatus::CANCELLED) {
            return;
        }

        if ($this->status === OrderStatus::COMPLETED) {
            throw new InvalidOrderStatusTransitionException('Cannot cancel completed order');
        }

        $this->status = OrderStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new OrderCancelledEvent($this->id, $reason));
    }

    public function ship(): void
    {
        if ($this->status !== OrderStatus::CONFIRMED) {
            throw new InvalidOrderStatusTransitionException('Can only ship confirmed orders');
        }

        $this->status = OrderStatus::SHIPPED;
        $this->shippedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new OrderShippedEvent($this->id));
    }

    public function complete(): void
    {
        if ($this->status !== OrderStatus::SHIPPED) {
            throw new InvalidOrderStatusTransitionException('Can only complete shipped orders');
        }

        $this->status = OrderStatus::COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new OrderCompletedEvent($this->id));
    }

    private function recalculateTotal(): void
    {
        $this->subtotal = Money::zero();

        foreach ($this->lines as $line) {
            $this->subtotal = $this->subtotal->add($line->getTotal());
        }

        $this->totalAmount = $this->subtotal->subtract($this->discountAmount);
    }

    // ✅ Règles métier
    public function isEditable(): bool
    {
        return $this->status === OrderStatus::DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::CONFIRMED;
    }

    public function isShipped(): bool
    {
        return $this->status === OrderStatus::SHIPPED;
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::CANCELLED;
    }

    public function hasLines(): bool
    {
        return count($this->lines) > 0;
    }

    // Domain Events management
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
    public function getId(): OrderId
    {
        return $this->id;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getTotalAmount(): Money
    {
        return $this->totalAmount;
    }

    public function getSubtotal(): Money
    {
        return $this->subtotal;
    }

    public function getDiscountAmount(): Money
    {
        return $this->discountAmount;
    }

    /**
     * @return list<OrderLine>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }
}
```

**Actions:**
- Créer `src/Domain/Order/Entity/Order.php`
- Créer `src/Domain/Order/Entity/OrderLine.php` (entité enfant de l'agrégat)
- Supprimer toutes les annotations Doctrine
- Convertir propriétés en Value Objects (OrderId, Money, OrderStatus, ProductReference)
- Marquer la classe `final`
- Constructor `private` avec factory `create()`
- Ajouter méthodes métier (addLine, removeLine, updateQuantity, confirm, cancel, ship, complete)
- Implémenter enregistrement Domain Events
- Implémenter calcul automatique du total

### [DOMAIN] Créer l'entité OrderLine (1.5h)

```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Order\ValueObject\OrderLineId;
use App\Domain\Order\ValueObject\ProductReference;
use App\Domain\Shared\ValueObject\Money;

// ✅ Entité enfant (partie de l'agrégat Order)
final class OrderLine
{
    private OrderLineId $id;
    private ProductReference $productReference;
    private string $productName;
    private Money $unitPrice;
    private int $quantity;
    private Money $total;

    private function __construct(
        OrderLineId $id,
        ProductReference $productReference,
        string $productName,
        Money $unitPrice,
        int $quantity
    ) {
        $this->id = $id;
        $this->productReference = $productReference;
        $this->productName = $productName;
        $this->unitPrice = $unitPrice;
        $this->setQuantity($quantity);
        $this->calculateTotal();
    }

    public static function create(
        OrderLineId $id,
        ProductReference $productReference,
        string $productName,
        Money $unitPrice,
        int $quantity
    ): self {
        return new self($id, $productReference, $productName, $unitPrice, $quantity);
    }

    public function updateQuantity(int $newQuantity): void
    {
        $this->setQuantity($newQuantity);
        $this->calculateTotal();
    }

    public function updateUnitPrice(Money $newUnitPrice): void
    {
        $this->unitPrice = $newUnitPrice;
        $this->calculateTotal();
    }

    private function setQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $this->quantity = $quantity;
    }

    private function calculateTotal(): void
    {
        $this->total = $this->unitPrice->multiply($this->quantity);
    }

    // Getters
    public function getId(): OrderLineId
    {
        return $this->id;
    }

    public function getProductReference(): ProductReference
    {
        return $this->productReference;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }
}
```

### [DOMAIN] Créer les Value Objects nécessaires (2h)

- Créer `src/Domain/Order/ValueObject/OrderId.php` (UUID typé)
- Créer `src/Domain/Order/ValueObject/OrderLineId.php` (UUID typé)
- Créer `src/Domain/Order/ValueObject/OrderStatus.php` (enum)
- Créer `src/Domain/Order/ValueObject/ProductReference.php` (référence produit)
- Utiliser `src/Domain/Shared/ValueObject/Money.php` (déjà créé par US-012)

**OrderStatus Enum:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case SHIPPED = 'shipped';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canConfirm(): bool
    {
        return $this === self::DRAFT;
    }

    public function canShip(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function canComplete(): bool
    {
        return $this === self::SHIPPED;
    }

    public function canCancel(): bool
    {
        return match ($this) {
            self::DRAFT, self::CONFIRMED => true,
            self::SHIPPED, self::COMPLETED, self::CANCELLED => false,
        };
    }
}
```

**ProductReference Value Object:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

final readonly class ProductReference
{
    private const string PATTERN = '/^[A-Z0-9]{3,20}$/';

    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $value): self
    {
        return new self(strtoupper(trim($value)));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function validate(): void
    {
        if (!preg_match(self::PATTERN, $this->value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid product reference: %s (must be 3-20 alphanumeric uppercase)', $this->value)
            );
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

### [DOMAIN] Créer les Domain Events (1.5h)

- Créer `src/Domain/Order/Event/OrderCreatedEvent.php`
- Créer `src/Domain/Order/Event/OrderLineAddedEvent.php`
- Créer `src/Domain/Order/Event/OrderLineRemovedEvent.php`
- Créer `src/Domain/Order/Event/OrderLineQuantityUpdatedEvent.php`
- Créer `src/Domain/Order/Event/DiscountAppliedEvent.php`
- Créer `src/Domain/Order/Event/OrderConfirmedEvent.php`
- Créer `src/Domain/Order/Event/OrderCancelledEvent.php`
- Créer `src/Domain/Order/Event/OrderShippedEvent.php`
- Créer `src/Domain/Order/Event/OrderCompletedEvent.php`
- Tous les événements implémentent `DomainEventInterface`

**Exemple OrderConfirmedEvent:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\Interface\DomainEventInterface;

final readonly class OrderConfirmedEvent implements DomainEventInterface
{
    public function __construct(
        private OrderId $orderId,
        private Money $totalAmount,
        private \DateTimeImmutable $occurredOn = new \DateTimeImmutable(),
    ) {}

    public function getOrderId(): OrderId
    {
        return $this->orderId;
    }

    public function getTotalAmount(): Money
    {
        return $this->totalAmount;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
```

### [DOMAIN] Créer les Exceptions métier (1h)

- Créer `src/Domain/Order/Exception/OrderNotFoundException.php`
- Créer `src/Domain/Order/Exception/InvalidOrderException.php`
- Créer `src/Domain/Order/Exception/OrderNotEditableException.php`
- Créer `src/Domain/Order/Exception/EmptyOrderException.php`
- Créer `src/Domain/Order/Exception/InvalidQuantityException.php`
- Créer `src/Domain/Order/Exception/InvalidDiscountException.php`
- Créer `src/Domain/Order/Exception/InvalidOrderStatusTransitionException.php`
- Créer `src/Domain/Order/Exception/OrderLineNotFoundException.php`
- Toutes les exceptions étendent `DomainException`

### [TEST] Créer tests unitaires Domain (3h)

- Créer `tests/Unit/Domain/Order/Entity/OrderTest.php`
- Créer `tests/Unit/Domain/Order/Entity/OrderLineTest.php`
- Tests de création (factory method `create()`)
- Tests de validation (lignes vides, montants invalides)
- Tests de méthodes métier (addLine, removeLine, updateQuantity, confirm, cancel)
- Tests de calcul de total et remises
- Tests de transitions de statuts (draft→confirmed→shipped→completed, cancellation)
- Tests de Domain Events (enregistrement et pull)
- Tests d'invariants (order confirmé non modifiable)
- Couverture ≥ 90%

**Exemple de tests:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Entity;

use App\Domain\Order\Entity\Order;
use App\Domain\Order\Entity\OrderLine;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Order\ValueObject\ProductReference;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Order\Exception\OrderNotEditableException;
use App\Domain\Order\Exception\EmptyOrderException;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_order_in_draft_status(): void
    {
        // Given
        $orderId = OrderId::generate();
        $clientId = ClientId::generate();

        // When
        $order = Order::create($orderId, $clientId);

        // Then
        $this->assertEquals($orderId, $order->getId());
        $this->assertEquals($clientId, $order->getClientId());
        $this->assertEquals(OrderStatus::DRAFT, $order->getStatus());
        $this->assertTrue($order->getTotalAmount()->isZero());
        $this->assertTrue($order->isEditable());
    }

    /**
     * @test
     */
    public function it_adds_order_lines_and_recalculates_total(): void
    {
        // Given
        $order = $this->createOrder();

        // When
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product 1',
            Money::fromEuros(100),
            2
        );

        $order->addLine(
            ProductReference::fromString('PROD-002'),
            'Product 2',
            Money::fromEuros(50),
            3
        );

        // Then
        $this->assertCount(2, $order->getLines());
        $this->assertEquals(350.0, $order->getTotalAmount()->getAmountEuros());
        $this->assertEquals(350.0, $order->getSubtotal()->getAmountEuros());
    }

    /**
     * @test
     */
    public function it_applies_discount_and_updates_total(): void
    {
        // Given
        $order = $this->createOrder();
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );

        // When
        $order->applyDiscount(Money::fromEuros(10));

        // Then
        $this->assertEquals(100.0, $order->getSubtotal()->getAmountEuros());
        $this->assertEquals(10.0, $order->getDiscountAmount()->getAmountEuros());
        $this->assertEquals(90.0, $order->getTotalAmount()->getAmountEuros());
    }

    /**
     * @test
     */
    public function it_cannot_confirm_empty_order(): void
    {
        // Given
        $order = $this->createOrder();

        // Expect
        $this->expectException(EmptyOrderException::class);
        $this->expectExceptionMessage('Cannot confirm empty order');

        // When
        $order->confirm();
    }

    /**
     * @test
     */
    public function it_confirms_order_with_lines(): void
    {
        // Given
        $order = $this->createOrder();
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );

        // When
        $order->confirm();

        // Then
        $this->assertEquals(OrderStatus::CONFIRMED, $order->getStatus());
        $this->assertNotNull($order->getConfirmedAt());
        $this->assertFalse($order->isEditable());
    }

    /**
     * @test
     */
    public function it_cannot_modify_confirmed_order(): void
    {
        // Given
        $order = $this->createOrder();
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );
        $order->confirm();

        // Expect
        $this->expectException(OrderNotEditableException::class);
        $this->expectExceptionMessage('Cannot modify confirmed order');

        // When
        $order->addLine(
            ProductReference::fromString('PROD-002'),
            'Product 2',
            Money::fromEuros(50),
            1
        );
    }

    /**
     * @test
     */
    public function it_follows_status_transition_lifecycle(): void
    {
        // Given
        $order = $this->createOrder();
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );

        // When/Then: DRAFT → CONFIRMED
        $order->confirm();
        $this->assertEquals(OrderStatus::CONFIRMED, $order->getStatus());

        // CONFIRMED → SHIPPED
        $order->ship();
        $this->assertEquals(OrderStatus::SHIPPED, $order->getStatus());
        $this->assertNotNull($order->getShippedAt());

        // SHIPPED → COMPLETED
        $order->complete();
        $this->assertEquals(OrderStatus::COMPLETED, $order->getStatus());
        $this->assertNotNull($order->getCompletedAt());
    }

    /**
     * @test
     */
    public function it_can_cancel_draft_or_confirmed_order(): void
    {
        // Given
        $order = $this->createOrder();
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );

        // When: Cancel DRAFT
        $order->cancel('Client request');

        // Then
        $this->assertEquals(OrderStatus::CANCELLED, $order->getStatus());
    }

    /**
     * @test
     */
    public function it_records_domain_events(): void
    {
        // Given
        $order = $this->createOrder();
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );

        // When
        $order->confirm();
        $events = $order->pullDomainEvents();

        // Then
        $this->assertCount(2, $events); // OrderCreatedEvent + OrderConfirmedEvent
        $this->assertInstanceOf(OrderCreatedEvent::class, $events[0]);
        $this->assertInstanceOf(OrderConfirmedEvent::class, $events[1]);
    }

    private function createOrder(): Order
    {
        return Order::create(
            OrderId::generate(),
            ClientId::generate()
        );
    }
}
```

### [DOC] Documenter l'entité Domain (0.5h)

- Ajouter PHPDoc complet sur l'entité Order
- Documenter les règles métier (statuts, transitions, calculs)
- Documenter les invariants (order confirmé = immutable)
- Créer exemple d'usage dans `.claude/examples/domain-entity-order.md`

### [VALIDATION] Valider avec outils qualité (0.5h)

- Exécuter `make phpstan` sur src/Domain/
- Exécuter `make deptrac` pour vérifier isolation Domain
- Vérifier aucune dépendance vers Doctrine/Symfony

---

## Définition de Done (DoD)

- [ ] Entité `src/Domain/Order/Entity/Order.php` créée sans annotations Doctrine
- [ ] Entité `src/Domain/Order/Entity/OrderLine.php` créée (partie de l'agrégat)
- [ ] Propriétés converties en Value Objects (OrderId, OrderLineId, Money, OrderStatus, ProductReference)
- [ ] Classe `final` avec constructor `private` et factory `create()`
- [ ] Méthodes métier implémentées (addLine, removeLine, updateQuantity, applyDiscount, confirm, cancel, ship, complete)
- [ ] Calcul automatique du total (subtotal - discount)
- [ ] Domain Events enregistrés et testés (9 événements)
- [ ] Value Objects OrderId, OrderLineId, OrderStatus, ProductReference créés
- [ ] Exceptions métier créées (8 exceptions spécifiques)
- [ ] Tests unitaires Order passent avec couverture ≥ 90%
- [ ] Tests unitaires OrderLine passent avec couverture ≥ 90%
- [ ] Tests s'exécutent en moins de 100ms
- [ ] PHPStan niveau max passe sur src/Domain/Order/
- [ ] Deptrac valide: Domain ne dépend de rien
- [ ] Documentation PHPDoc complète
- [ ] Exemples d'usage documentés
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(domain): extract Order and OrderLine entities to pure Domain layer`

---

## Notes techniques

### Pattern Aggregate Root

L'entité Order est un **Aggregate Root** car:
- Elle a une identité unique (OrderId)
- Elle garantit ses invariants (total = subtotal - discount, order confirmé non modifiable)
- Elle contient des entités enfants (OrderLine) accessibles uniquement via Order
- Elle enregistre des Domain Events
- Elle est le point d'entrée pour toute modification
- Une transaction modifie UN SEUL Order (avec ses lignes)

### OrderLine comme Entité enfant

OrderLine est une **Entité** (pas un Value Object) car:
- Elle a une identité (OrderLineId)
- Elle peut être modifiée (quantity, unitPrice)
- Elle appartient à l'agrégat Order
- Elle n'existe pas indépendamment de Order

**Règle:** On ne peut PAS accéder directement aux OrderLines sans passer par Order.

```php
// ❌ INTERDIT: Accès direct à OrderLine
$orderLineRepository->find($lineId);
$orderLine->updateQuantity(5);
$orderLineRepository->save($orderLine);

// ✅ OBLIGATOIRE: Modification via Aggregate Root
$order = $orderRepository->findById($orderId);
$order->updateLineQuantity($lineId, 5);
$orderRepository->save($order);
```

### Statuts Order et transitions autorisées

```
DRAFT → CONFIRMED     ✅ (confirmation après ajout de lignes)
DRAFT → CANCELLED     ✅ (annulation avant paiement)
CONFIRMED → SHIPPED   ✅ (expédition après paiement)
CONFIRMED → CANCELLED ✅ (annulation avant expédition)
SHIPPED → COMPLETED   ✅ (livraison confirmée)
SHIPPED → CANCELLED   ❌ (interdit, nécessite retour)
COMPLETED → *         ❌ (état final)
CANCELLED → *         ❌ (état final)
```

### Règles métier

1. **Order modifiable uniquement en DRAFT**: Validé via méthodes métier
2. **Total calculé automatiquement**: Subtotal - Discount
3. **Ligne = Product × Quantity**: Total recalculé à chaque modification
4. **Order vide non confirmable**: Validation dans confirm()
5. **Remise ≤ Subtotal**: Validation dans applyDiscount()
6. **Immutabilité après confirmation**: Vérifié dans toutes les méthodes de modification
7. **Domain Events**: Toute mutation importante émet un événement
8. **Transitions de statuts contrôlées**: Méthodes métier vérifient l'état actuel

### Calcul du total

```
Subtotal = Σ (OrderLine.unitPrice × OrderLine.quantity)
Total = Subtotal - DiscountAmount

Exemple:
  Line 1: 100€ × 2 = 200€
  Line 2: 50€ × 3 = 150€
  Subtotal = 350€
  Discount = 35€ (10%)
  Total = 315€
```

### Exemple d'usage

```php
<?php

// Création
$order = Order::create(
    OrderId::generate(),
    ClientId::fromString('client-uuid')
);

// Ajout de lignes
$order->addLine(
    ProductReference::fromString('PROD-001'),
    'Séjour Ski Alpes',
    Money::fromEuros(500),
    2
);

$order->addLine(
    ProductReference::fromString('PROD-002'),
    'Assurance',
    Money::fromEuros(50),
    2
);

// Subtotal = 1100€ (500×2 + 50×2)

// Application remise
$order->applyDiscount(Money::fromEuros(110)); // 10%
// Total = 990€

// Confirmation
$order->confirm();

// Expédition
$order->ship();

// Complétion
$order->complete();

// Domain Events
$events = $order->pullDomainEvents();
// $events = [
//   OrderCreatedEvent,
//   OrderLineAddedEvent (×2),
//   DiscountAppliedEvent,
//   OrderConfirmedEvent,
//   OrderShippedEvent,
//   OrderCompletedEvent
// ]
```

---

## Dépendances

### Bloquantes

- **US-001**: Structure Domain créée (nécessite `src/Domain/Order/Entity/`)
- **EPIC-002**: Value Objects Money créé (US-012) (nécessaire pour OrderLine.unitPrice, Order.totalAmount)

### Bloque

- **US-007**: Mapping Doctrine XML Order (nécessite entité Domain pure comme source)
- **US-024**: OrderRepositoryInterface (nécessite entité Order définie)

---

## Références

- `.claude/rules/02-architecture-clean-ddd.md` (lignes 45-155, entités Domain pures)
- `.claude/rules/13-ddd-patterns.md` (lignes 15-150, Aggregates et Entities)
- `.claude/rules/19-aggregates.md` (Template Aggregate Root, OrderLine comme entité enfant)
- `.claude/rules/20-domain-events.md` (Domain Events pattern)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 45-73, problème entités couplées)
- **Livre:** *Domain-Driven Design* - Eric Evans, Chapitre 5 (Entities), Chapitre 10 (Aggregates)
- **Livre:** *Implementing Domain-Driven Design* - Vaughn Vernon, Chapitre 10 (Aggregates)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |
