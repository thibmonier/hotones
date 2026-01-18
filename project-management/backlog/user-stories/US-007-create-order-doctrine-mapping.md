# US-007: Créer le mapping Doctrine XML pour Order dans Infrastructure

**EPIC:** [EPIC-001](../epics/EPIC-001-clean-architecture-restructuring.md) - Restructuration Clean Architecture
**Priorité:** 🔴 CRITIQUE
**Points:** 5
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer le mapping Doctrine XML pour Order et OrderLine dans la couche Infrastructure
**Afin de** persister l'agrégat Order tout en gardant le Domain pur et sans dépendance Doctrine

---

## Critères d'acceptation

### GIVEN: L'entité Order pure existe dans Domain (US-006)

**WHEN:** Je crée le mapping Doctrine XML pour Order et OrderLine

**THEN:**
- [ ] Fichier `src/Infrastructure/Persistence/Doctrine/Mapping/Order.orm.xml` créé
- [ ] Fichier `src/Infrastructure/Persistence/Doctrine/Mapping/OrderLine.orm.xml` créé
- [ ] Mapping utilise les Doctrine Custom Types (OrderIdType, OrderStatusType, MoneyType, etc.)
- [ ] Relation one-to-many Order → OrderLine configurée avec cascade
- [ ] Relation many-to-one OrderLine → Order configurée
- [ ] Tous les champs Order mappés (id, clientId, status, totalAmount, subtotal, discountAmount, timestamps)
- [ ] Tous les champs OrderLine mappés (id, productReference, productName, unitPrice, quantity, total)
- [ ] Indexes créés pour performance (clientId, status, createdAt, confirmedAt)
- [ ] Configuration `doctrine.yaml` mise à jour avec custom types
- [ ] Pas d'annotations Doctrine dans les entités Domain

### GIVEN: Le mapping XML existe

**WHEN:** J'exécute `make db-migrate`

**THEN:**
- [ ] Migration créée avec tables `order` et `order_line`
- [ ] Clé étrangère `order_line.order_id` vers `order.id` avec cascade
- [ ] Indexes créés (order_client_id_idx, order_status_idx, order_created_at_idx)
- [ ] Types personnalisés utilisés (uuid pour IDs, varchar pour status, decimal pour money)
- [ ] Contraintes NOT NULL respectées
- [ ] Migration exécutée sans erreur

### GIVEN: Le mapping Doctrine est configuré

**WHEN:** J'exécute les tests d'intégration

**THEN:**
- [ ] Order avec OrderLine persiste correctement
- [ ] Total recalculé après ajout/suppression de ligne
- [ ] Cascade persist/remove fonctionne pour OrderLine
- [ ] Timestamps (createdAt, updatedAt, confirmedAt, shippedAt, completedAt) gérés par Doctrine
- [ ] Status transitions persistent correctement
- [ ] Discount appliqué et persisté
- [ ] Tests passent en < 2s
- [ ] Pas de N+1 queries détectées

---

## Tâches techniques

### [INFRA] Créer Order.orm.xml (2h)

**Mapping complet:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Domain\Order\Entity\Order" table="order" repository-class="App\Infrastructure\Persistence\Doctrine\Repository\DoctrineOrderRepository">

        <!-- ID typé (OrderId Value Object) -->
        <id name="id" type="order_id">
            <generator strategy="NONE"/> <!-- UUID généré par le Domain -->
        </id>

        <!-- Client reference (ClientId Value Object) -->
        <field name="clientId" type="client_id" column="client_id" nullable="false"/>

        <!-- Status (OrderStatus enum) -->
        <field name="status" type="order_status" column="status" length="20" nullable="false"/>

        <!-- Money Value Objects (embedded) -->
        <embedded name="totalAmount" class="App\Domain\Shared\ValueObject\Money" use-column-prefix="false">
            <field name="amountCents" type="integer" column="total_amount_cents" nullable="false"/>
            <field name="currency" type="string" column="currency" length="3" nullable="false"/>
        </embedded>

        <embedded name="subtotal" class="App\Domain\Shared\ValueObject\Money" use-column-prefix="false">
            <field name="amountCents" type="integer" column="subtotal_cents" nullable="false"/>
            <field name="currency" type="string" column="subtotal_currency" length="3" nullable="false"/>
        </embedded>

        <embedded name="discountAmount" class="App\Domain\Shared\ValueObject\Money" use-column-prefix="false">
            <field name="amountCents" type="integer" column="discount_amount_cents" nullable="false"/>
            <field name="currency" type="string" column="discount_currency" length="3" nullable="false"/>
        </embedded>

        <!-- Timestamps -->
        <field name="createdAt" type="datetime_immutable" column="created_at" nullable="false"/>
        <field name="updatedAt" type="datetime_immutable" column="updated_at" nullable="false"/>
        <field name="confirmedAt" type="datetime_immutable" column="confirmed_at" nullable="true"/>
        <field name="shippedAt" type="datetime_immutable" column="shipped_at" nullable="true"/>
        <field name="completedAt" type="datetime_immutable" column="completed_at" nullable="true"/>

        <!-- Relation one-to-many Order → OrderLine -->
        <one-to-many field="lines" target-entity="App\Domain\Order\Entity\OrderLine" mapped-by="order" orphan-removal="true">
            <cascade>
                <cascade-persist/>
                <cascade-remove/>
            </cascade>
            <order-by>
                <order-by-field name="id" direction="ASC"/>
            </order-by>
        </one-to-many>

        <!-- Indexes pour performance -->
        <indexes>
            <index name="order_client_id_idx" columns="client_id"/>
            <index name="order_status_idx" columns="status"/>
            <index name="order_created_at_idx" columns="created_at"/>
            <index name="order_confirmed_at_idx" columns="confirmed_at"/>
        </indexes>

        <!-- Lifecycle callbacks (si nécessaire pour Gedmo) -->
        <lifecycle-callbacks>
            <!-- Les timestamps sont gérés par le Domain, pas Doctrine -->
        </lifecycle-callbacks>

    </entity>

</doctrine-mapping>
```

**Actions:**
- Créer `src/Infrastructure/Persistence/Doctrine/Mapping/Order.orm.xml`
- Utiliser les Custom Types pour OrderId, OrderStatus, ClientId, Money
- Configurer cascade persist/remove pour OrderLine
- Ajouter indexes pour requêtes fréquentes (findByClient, findByStatus, orderBy createdAt)
- Désactiver lifecycle callbacks (gérés par le Domain)

### [INFRA] Créer OrderLine.orm.xml (1.5h)

**Mapping OrderLine:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Domain\Order\Entity\OrderLine" table="order_line">

        <!-- ID typé (OrderLineId Value Object) -->
        <id name="id" type="order_line_id">
            <generator strategy="NONE"/> <!-- UUID généré par le Domain -->
        </id>

        <!-- Product Reference (ProductReference Value Object) -->
        <field name="productReference" type="product_reference" column="product_reference" length="20" nullable="false"/>

        <!-- Product Name (string) -->
        <field name="productName" type="string" column="product_name" length="255" nullable="false"/>

        <!-- Money Value Objects (embedded) -->
        <embedded name="unitPrice" class="App\Domain\Shared\ValueObject\Money" use-column-prefix="false">
            <field name="amountCents" type="integer" column="unit_price_cents" nullable="false"/>
            <field name="currency" type="string" column="unit_currency" length="3" nullable="false"/>
        </embedded>

        <embedded name="total" class="App\Domain\Shared\ValueObject\Money" use-column-prefix="false">
            <field name="amountCents" type="integer" column="total_cents" nullable="false"/>
            <field name="currency" type="string" column="total_currency" length="3" nullable="false"/>
        </embedded>

        <!-- Quantity -->
        <field name="quantity" type="integer" column="quantity" nullable="false"/>

        <!-- Relation many-to-one OrderLine → Order -->
        <many-to-one field="order" target-entity="App\Domain\Order\Entity\Order" inversed-by="lines">
            <join-column name="order_id" referenced-column-name="id" nullable="false" on-delete="CASCADE"/>
        </many-to-one>

        <!-- Index pour jointure -->
        <indexes>
            <index name="order_line_order_id_idx" columns="order_id"/>
            <index name="order_line_product_ref_idx" columns="product_reference"/>
        </indexes>

    </entity>

</doctrine-mapping>
```

**Actions:**
- Créer `src/Infrastructure/Persistence/Doctrine/Mapping/OrderLine.orm.xml`
- Relation bidirectionnelle Order ↔ OrderLine
- Cascade DELETE (OrderLine supprimé si Order supprimé)
- ProductReference custom type
- Money embedded pour unitPrice et total

### [INFRA] Créer les Doctrine Custom Types (2.5h)

**Types à créer:**

#### OrderIdType
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\OrderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

final class OrderIdType extends GuidType
{
    public const string NAME = 'order_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?OrderId
    {
        if ($value === null) {
            return null;
        }

        return OrderId::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof OrderId) {
            throw new \InvalidArgumentException('Expected OrderId instance');
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

#### OrderLineIdType
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\OrderLineId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

final class OrderLineIdType extends GuidType
{
    public const string NAME = 'order_line_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?OrderLineId
    {
        if ($value === null) {
            return null;
        }

        return OrderLineId::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof OrderLineId) {
            throw new \InvalidArgumentException('Expected OrderLineId instance');
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

#### OrderStatusType
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\OrderStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class OrderStatusType extends StringType
{
    public const string NAME = 'order_status';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?OrderStatus
    {
        if ($value === null) {
            return null;
        }

        return OrderStatus::from($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof OrderStatus) {
            throw new \InvalidArgumentException('Expected OrderStatus instance');
        }

        return $value->value;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

#### ProductReferenceType
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\ProductReference;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class ProductReferenceType extends StringType
{
    public const string NAME = 'product_reference';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ProductReference
    {
        if ($value === null) {
            return null;
        }

        return ProductReference::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof ProductReference) {
            throw new \InvalidArgumentException('Expected ProductReference instance');
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

**Actions:**
- Créer `src/Infrastructure/Persistence/Doctrine/Type/OrderIdType.php`
- Créer `src/Infrastructure/Persistence/Doctrine/Type/OrderLineIdType.php`
- Créer `src/Infrastructure/Persistence/Doctrine/Type/OrderStatusType.php`
- Créer `src/Infrastructure/Persistence/Doctrine/Type/ProductReferenceType.php`
- Réutiliser ClientIdType (déjà créé dans US-003)
- Réutiliser MoneyType (sera créé dans US-017 - EPIC-002)

### [INFRA] Configurer doctrine.yaml (0.5h)

**Configuration:**

```yaml
# config/packages/doctrine.yaml

doctrine:
    dbal:
        types:
            # IDs typés
            order_id: App\Infrastructure\Persistence\Doctrine\Type\OrderIdType
            order_line_id: App\Infrastructure\Persistence\Doctrine\Type\OrderLineIdType
            client_id: App\Infrastructure\Persistence\Doctrine\Type\ClientIdType

            # Enums
            order_status: App\Infrastructure\Persistence\Doctrine\Type\OrderStatusType

            # Value Objects
            product_reference: App\Infrastructure\Persistence\Doctrine\Type\ProductReferenceType
            money: App\Infrastructure\Persistence\Doctrine\Type\MoneyType
            email: App\Infrastructure\Persistence\Doctrine\Type\EmailType

    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true

        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true

        mappings:
            Order:
                is_bundle: false
                type: xml
                dir: '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/Mapping'
                prefix: 'App\Domain\Order\Entity'
                alias: Order

            Client:
                is_bundle: false
                type: xml
                dir: '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/Mapping'
                prefix: 'App\Domain\Client\Entity'
                alias: Client

            User:
                is_bundle: false
                type: xml
                dir: '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/Mapping'
                prefix: 'App\Domain\User\Entity'
                alias: User
```

**Actions:**
- Mettre à jour `config/packages/doctrine.yaml`
- Enregistrer les nouveaux custom types
- Ajouter mapping Order dans `orm.mappings`

### [INFRA] Créer la migration (0.5h)

**Migration générée:**

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260113CreateOrderTables extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order and order_line tables with aggregate relationship';
    }

    public function up(Schema $schema): void
    {
        // Create order table
        $this->addSql('
            CREATE TABLE "order" (
                id UUID NOT NULL,
                client_id UUID NOT NULL,
                status VARCHAR(20) NOT NULL,
                total_amount_cents INTEGER NOT NULL,
                currency VARCHAR(3) NOT NULL,
                subtotal_cents INTEGER NOT NULL,
                subtotal_currency VARCHAR(3) NOT NULL,
                discount_amount_cents INTEGER NOT NULL,
                discount_currency VARCHAR(3) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                shipped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
        ');

        $this->addSql('COMMENT ON COLUMN "order".id IS \'(DC2Type:order_id)\'');
        $this->addSql('COMMENT ON COLUMN "order".client_id IS \'(DC2Type:client_id)\'');
        $this->addSql('COMMENT ON COLUMN "order".status IS \'(DC2Type:order_status)\'');
        $this->addSql('COMMENT ON COLUMN "order".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".confirmed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".shipped_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".completed_at IS \'(DC2Type:datetime_immutable)\'');

        // Create order_line table
        $this->addSql('
            CREATE TABLE order_line (
                id UUID NOT NULL,
                order_id UUID NOT NULL,
                product_reference VARCHAR(20) NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                unit_price_cents INTEGER NOT NULL,
                unit_currency VARCHAR(3) NOT NULL,
                quantity INTEGER NOT NULL,
                total_cents INTEGER NOT NULL,
                total_currency VARCHAR(3) NOT NULL,
                PRIMARY KEY(id)
            )
        ');

        $this->addSql('COMMENT ON COLUMN order_line.id IS \'(DC2Type:order_line_id)\'');
        $this->addSql('COMMENT ON COLUMN order_line.order_id IS \'(DC2Type:order_id)\'');
        $this->addSql('COMMENT ON COLUMN order_line.product_reference IS \'(DC2Type:product_reference)\'');

        // Foreign key with cascade delete
        $this->addSql('
            ALTER TABLE order_line
            ADD CONSTRAINT FK_order_line_order_id
            FOREIGN KEY (order_id)
            REFERENCES "order" (id)
            ON DELETE CASCADE
            NOT DEFERRABLE INITIALLY IMMEDIATE
        ');

        // Indexes
        $this->addSql('CREATE INDEX order_client_id_idx ON "order" (client_id)');
        $this->addSql('CREATE INDEX order_status_idx ON "order" (status)');
        $this->addSql('CREATE INDEX order_created_at_idx ON "order" (created_at)');
        $this->addSql('CREATE INDEX order_confirmed_at_idx ON "order" (confirmed_at)');
        $this->addSql('CREATE INDEX order_line_order_id_idx ON order_line (order_id)');
        $this->addSql('CREATE INDEX order_line_product_ref_idx ON order_line (product_reference)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_line');
        $this->addSql('DROP TABLE "order"');
    }
}
```

**Actions:**
- Exécuter `make console CMD="doctrine:migrations:diff"`
- Vérifier la migration générée
- Exécuter `make db-migrate`
- Vérifier les tables créées avec `psql`

### [TEST] Créer tests d'intégration Doctrine (2.5h)

**Tests de persistance:**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Order\Entity\Order;
use App\Domain\Order\Entity\OrderLine;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderLineId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Order\ValueObject\ProductReference;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Money;
use App\Infrastructure\Persistence\Doctrine\Repository\DoctrineOrderRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineOrderRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(DoctrineOrderRepository::class);
    }

    /**
     * @test
     */
    public function it_persists_order_with_lines(): void
    {
        // Given
        $order = Order::create(
            OrderId::generate(),
            ClientId::generate()
        );

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
            1
        );

        // When
        $this->repository->save($order);
        $this->entityManager->clear(); // Clear identity map

        // Then
        $found = $this->repository->findById($order->getId());

        $this->assertEquals($order->getId(), $found->getId());
        $this->assertCount(2, $found->getLines());
        $this->assertEquals(250.0, $found->getTotalAmount()->getAmountEuros());
        $this->assertEquals(250.0, $found->getSubtotal()->getAmountEuros());
    }

    /**
     * @test
     */
    public function it_persists_total_recalculation(): void
    {
        // Given
        $order = Order::create(OrderId::generate(), ClientId::generate());
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            2
        );

        $this->repository->save($order);
        $this->entityManager->clear();

        // When: Add another line
        $found = $this->repository->findById($order->getId());
        $found->addLine(
            ProductReference::fromString('PROD-002'),
            'Product 2',
            Money::fromEuros(50),
            1
        );

        $this->repository->save($found);
        $this->entityManager->clear();

        // Then: Total updated
        $reloaded = $this->repository->findById($order->getId());
        $this->assertEquals(250.0, $reloaded->getTotalAmount()->getAmountEuros());
        $this->assertCount(2, $reloaded->getLines());
    }

    /**
     * @test
     */
    public function it_persists_discount_application(): void
    {
        // Given
        $order = Order::create(OrderId::generate(), ClientId::generate());
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );

        // When
        $order->applyDiscount(Money::fromEuros(10));
        $this->repository->save($order);
        $this->entityManager->clear();

        // Then
        $found = $this->repository->findById($order->getId());
        $this->assertEquals(100.0, $found->getSubtotal()->getAmountEuros());
        $this->assertEquals(10.0, $found->getDiscountAmount()->getAmountEuros());
        $this->assertEquals(90.0, $found->getTotalAmount()->getAmountEuros());
    }

    /**
     * @test
     */
    public function it_persists_status_transitions(): void
    {
        // Given
        $order = Order::create(OrderId::generate(), ClientId::generate());
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );

        // When: Lifecycle transitions
        $order->confirm();
        $this->repository->save($order);
        $this->entityManager->clear();

        // Then: CONFIRMED status persisted
        $found = $this->repository->findById($order->getId());
        $this->assertEquals(OrderStatus::CONFIRMED, $found->getStatus());
        $this->assertNotNull($found->getConfirmedAt());
        $this->assertFalse($found->isEditable());

        // When: Ship
        $found->ship();
        $this->repository->save($found);
        $this->entityManager->clear();

        // Then: SHIPPED status persisted
        $reloaded = $this->repository->findById($order->getId());
        $this->assertEquals(OrderStatus::SHIPPED, $reloaded->getStatus());
        $this->assertNotNull($reloaded->getShippedAt());
    }

    /**
     * @test
     */
    public function it_cascades_persist_for_order_lines(): void
    {
        // Given
        $order = Order::create(OrderId::generate(), ClientId::generate());
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );

        // When: Save only Order (cascade should persist OrderLine)
        $this->repository->save($order);
        $this->entityManager->clear();

        // Then: OrderLine also persisted
        $found = $this->repository->findById($order->getId());
        $this->assertCount(1, $found->getLines());
    }

    /**
     * @test
     */
    public function it_cascades_remove_for_order_lines(): void
    {
        // Given
        $order = Order::create(OrderId::generate(), ClientId::generate());
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product',
            Money::fromEuros(100),
            1
        );

        $this->repository->save($order);
        $orderId = $order->getId();
        $this->entityManager->clear();

        // When: Delete Order (cascade should delete OrderLine)
        $found = $this->repository->findById($orderId);
        $this->repository->delete($found);
        $this->entityManager->clear();

        // Then: Order and OrderLine both deleted
        $this->expectException(\App\Domain\Order\Exception\OrderNotFoundException::class);
        $this->repository->findById($orderId);
    }

    /**
     * @test
     */
    public function it_orphan_removes_detached_order_lines(): void
    {
        // Given
        $order = Order::create(OrderId::generate(), ClientId::generate());
        $order->addLine(
            ProductReference::fromString('PROD-001'),
            'Product 1',
            Money::fromEuros(100),
            1
        );
        $order->addLine(
            ProductReference::fromString('PROD-002'),
            'Product 2',
            Money::fromEuros(50),
            1
        );

        $this->repository->save($order);
        $lineId = $order->getLines()[0]->getId();
        $this->entityManager->clear();

        // When: Remove first line (orphan removal should delete from DB)
        $found = $this->repository->findById($order->getId());
        $found->removeLine($lineId);
        $this->repository->save($found);
        $this->entityManager->clear();

        // Then: Only 1 line remains in DB
        $reloaded = $this->repository->findById($order->getId());
        $this->assertCount(1, $reloaded->getLines());
    }

    /**
     * @test
     */
    public function it_loads_order_with_lines_in_single_query(): void
    {
        // Given
        $order = Order::create(OrderId::generate(), ClientId::generate());
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
            1
        );

        $this->repository->save($order);
        $this->entityManager->clear();

        // When: Load with join fetch (prevent N+1)
        $found = $this->repository->findByIdWithLines($order->getId());

        // Then: No additional queries when accessing lines
        // (verify in Symfony Profiler: should be 1 query with JOIN)
        $this->assertCount(2, $found->getLines());
        $this->assertEquals('Product 1', $found->getLines()[0]->getProductName());
        $this->assertEquals('Product 2', $found->getLines()[1]->getProductName());
    }
}
```

**Actions:**
- Créer `tests/Integration/Infrastructure/Persistence/Doctrine/Repository/DoctrineOrderRepositoryTest.php`
- Tests de persistance Order + OrderLine
- Tests de cascade persist/remove
- Tests de orphan removal
- Tests de total recalculation après modifications
- Tests de status transitions persistence
- Tests de performance (N+1 prevention)

### [INFRA] Optimiser le loading de l'agrégat (1h)

**Repository avec fetch join:**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Order\Entity\Order;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\Exception\OrderNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findById(OrderId $id): Order
    {
        $order = $this->entityManager->find(Order::class, $id->getValue());

        if (!$order instanceof Order) {
            throw OrderNotFoundException::withId($id);
        }

        return $order;
    }

    /**
     * Find Order with OrderLine collection preloaded (prevent N+1).
     */
    public function findByIdWithLines(OrderId $id): Order
    {
        $qb = $this->entityManager->createQueryBuilder();

        $order = $qb->select('o', 'l')  // ✅ Fetch join OrderLine
            ->from(Order::class, 'o')
            ->leftJoin('o.lines', 'l')
            ->where('o.id = :id')
            ->setParameter('id', $id->getValue())
            ->getQuery()
            ->getOneOrNullResult();

        if (!$order instanceof Order) {
            throw OrderNotFoundException::withId($id);
        }

        return $order;
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    public function delete(Order $order): void
    {
        $this->entityManager->remove($order);
        $this->entityManager->flush();
    }
}
```

**Actions:**
- Créer `DoctrineOrderRepository` (stub, sera complété dans US-025)
- Méthode `findByIdWithLines()` avec fetch join pour éviter N+1
- Méthode `save()` avec cascade persist automatique
- Méthode `delete()` avec cascade remove automatique

### [DOC] Documenter le mapping (0.5h)

Créer `.claude/examples/doctrine-mapping-aggregate.md`:

```markdown
# Mapping Doctrine pour Aggregates

## Exemple: Order (Aggregate Root) avec OrderLine (Entité enfant)

### Stratégie de mapping

1. **Order.orm.xml**: Configure l'Aggregate Root
2. **OrderLine.orm.xml**: Configure l'entité enfant
3. **Relation one-to-many bidirectionnelle**
4. **Cascade persist/remove**: OrderLine géré automatiquement par Order
5. **Orphan removal**: OrderLine détaché = supprimé de la DB

### Cascade operations

```xml
<one-to-many field="lines" ... orphan-removal="true">
    <cascade>
        <cascade-persist/>  <!-- Save automatique des OrderLine -->
        <cascade-remove/>   <!-- Delete automatique des OrderLine -->
    </cascade>
</one-to-many>
```

**Comportement:**
- `$order->addLine()` + `$em->persist($order)` → OrderLine aussi persisté
- `$em->remove($order)` → Toutes les OrderLine supprimées
- `$order->removeLine()` → OrderLine supprimé de la DB (orphan removal)

### Performance: Fetch Join

```php
// ❌ LENT: N+1 queries
$order = $repository->findById($id);
foreach ($order->getLines() as $line) { // +1 query
    echo $line->getProductName();
}

// ✅ RAPIDE: 1 query avec JOIN
$order = $repository->findByIdWithLines($id);
foreach ($order->getLines() as $line) { // Pas de query supplémentaire
    echo $line->getProductName();
}
```

### Lazy vs Eager Loading

Pour aggregates, **LAZY par défaut** est recommandé:
- OrderLine chargé seulement si `$order->getLines()` appelé
- Performance optimale si OrderLine pas toujours nécessaire
- Fetch join explicite quand on sait qu'on a besoin des lignes

```xml
<!-- Lazy loading (défaut) -->
<one-to-many field="lines" ... fetch="LAZY"/>

<!-- OU Eager loading (pas recommandé pour aggregates) -->
<one-to-many field="lines" ... fetch="EAGER"/>
```
```

**Actions:**
- Créer `.claude/examples/doctrine-mapping-aggregate.md`
- Documenter cascade operations
- Documenter fetch strategies
- Exemples de N+1 prevention

### [VALIDATION] Valider le mapping (0.5h)

```bash
# Valider le schéma Doctrine
make console CMD="doctrine:schema:validate"

# Output attendu:
# [OK] The mapping files are correct.
# [OK] The database schema is in sync with the mapping files.

# Vérifier les custom types
make console CMD="doctrine:mapping:info"

# Vérifier la migration
make console CMD="doctrine:migrations:status"
```

**Actions:**
- Exécuter `make console CMD="doctrine:schema:validate"`
- Vérifier aucune erreur de mapping
- Exécuter `make db-migrate`
- Vérifier tables créées correctement

---

## Définition de Done (DoD)

- [ ] `Order.orm.xml` créé dans `src/Infrastructure/Persistence/Doctrine/Mapping/`
- [ ] `OrderLine.orm.xml` créé dans `src/Infrastructure/Persistence/Doctrine/Mapping/`
- [ ] Doctrine Custom Types créés (OrderIdType, OrderLineIdType, OrderStatusType, ProductReferenceType)
- [ ] Configuration `doctrine.yaml` mise à jour
- [ ] Relation one-to-many Order → OrderLine avec cascade persist/remove
- [ ] Orphan removal activé pour OrderLine
- [ ] Indexes créés (client_id, status, created_at, confirmed_at)
- [ ] Migration générée et exécutée sans erreur
- [ ] Tables `order` et `order_line` créées dans PostgreSQL
- [ ] Tests d'intégration passent (persistance, cascade, orphan removal, total recalculation)
- [ ] Test de N+1 prevention avec fetch join
- [ ] `make console CMD="doctrine:schema:validate"` passe
- [ ] DoctrineOrderRepository stub créé (sera complété dans US-025)
- [ ] Documentation mapping aggregate créée
- [ ] Exemples d'usage documentés
- [ ] Pas d'annotations Doctrine dans le Domain
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(infrastructure): create Doctrine XML mapping for Order aggregate`

---

## Notes techniques

### Aggregate Persistence

L'Order est un **Aggregate Root**, ce qui implique:

1. **Transactional Boundary**: Order + OrderLine persistés/supprimés ensemble
2. **Cascade Operations**: Doctrine gère automatiquement OrderLine
3. **Orphan Removal**: OrderLine détaché = supprimé de la DB
4. **Single Repository**: Seul OrderRepository existe (pas de OrderLineRepository)

### Cascade Strategies

```xml
<!-- Order.orm.xml -->
<one-to-many field="lines" target-entity="OrderLine" mapped-by="order" orphan-removal="true">
    <cascade>
        <cascade-persist/>  <!-- ✅ persist() cascade aux OrderLine -->
        <cascade-remove/>   <!-- ✅ remove() cascade aux OrderLine -->
    </cascade>
</one-to-many>
```

**Comportement:**

```php
// Scenario 1: Persist Order avec OrderLine
$order = Order::create($id, $clientId);
$order->addLine($productRef, $name, $price, $qty);

$em->persist($order);  // ✅ OrderLine aussi persisté (cascade-persist)
$em->flush();

// Scenario 2: Remove Order
$em->remove($order);   // ✅ Toutes les OrderLine aussi supprimées (cascade-remove)
$em->flush();

// Scenario 3: Orphan removal
$order->removeLine($lineId);  // Détache OrderLine de la collection
$em->flush();  // ✅ OrderLine supprimé de la DB (orphan-removal)
```

### Money Persistence

Les Value Objects Money sont stockés en **embedded**:

```xml
<embedded name="totalAmount" class="App\Domain\Shared\ValueObject\Money" use-column-prefix="false">
    <field name="amountCents" type="integer" column="total_amount_cents"/>
    <field name="currency" type="string" column="currency" length="3"/>
</embedded>
```

**Résultat SQL:**

```sql
CREATE TABLE "order" (
    id UUID NOT NULL,
    total_amount_cents INTEGER NOT NULL,  -- Money.amountCents
    currency VARCHAR(3) NOT NULL,         -- Money.currency
    subtotal_cents INTEGER NOT NULL,
    subtotal_currency VARCHAR(3) NOT NULL,
    discount_amount_cents INTEGER NOT NULL,
    discount_currency VARCHAR(3) NOT NULL,
    ...
);
```

### Performance: Lazy Loading vs Fetch Join

**Lazy loading (défaut):**
```php
$order = $repository->findById($id);
// 1 query: SELECT * FROM order WHERE id = ?

$lines = $order->getLines();  // ✅ Lazy load
// +1 query: SELECT * FROM order_line WHERE order_id = ?
```

**Fetch join (optimisé):**
```php
$order = $repository->findByIdWithLines($id);
// 1 query: SELECT o.*, l.* FROM order o LEFT JOIN order_line l ON l.order_id = o.id WHERE o.id = ?

$lines = $order->getLines();  // ✅ Pas de query (déjà chargé)
```

**Recommandation:**
- `findById()`: Lazy loading (si lines pas toujours nécessaire)
- `findByIdWithLines()`: Fetch join (si lines toujours utilisé)
- Créer méthodes spécifiques selon les use cases

### Indexes Stratégiques

```sql
-- Recherche par client
CREATE INDEX order_client_id_idx ON "order" (client_id);

-- Recherche par status (tableau admin)
CREATE INDEX order_status_idx ON "order" (status);

-- Tri par date création
CREATE INDEX order_created_at_idx ON "order" (created_at);

-- Recherche commandes confirmées
CREATE INDEX order_confirmed_at_idx ON "order" (confirmed_at);

-- Join OrderLine → Order
CREATE INDEX order_line_order_id_idx ON order_line (order_id);

-- Recherche par produit
CREATE INDEX order_line_product_ref_idx ON order_line (product_reference);
```

### Exemple de requête optimisée

```php
public function findConfirmedOrdersByClient(ClientId $clientId): array
{
    return $this->createQueryBuilder('o')
        ->select('o', 'l')  // ✅ Fetch join lines
        ->leftJoin('o.lines', 'l')
        ->where('o.clientId = :clientId')
        ->andWhere('o.status = :status')
        ->setParameter('clientId', $clientId->getValue())
        ->setParameter('status', OrderStatus::CONFIRMED->value)
        ->orderBy('o.confirmedAt', 'DESC')
        ->getQuery()
        ->getResult();
}
```

**Résultat:**
- 1 seule query SQL avec JOIN
- Toutes les OrderLine déjà chargées
- Index utilisés (client_id, status, confirmed_at)

### Table Structure Preview

```sql
-- Table Order
CREATE TABLE "order" (
    id UUID NOT NULL PRIMARY KEY,
    client_id UUID NOT NULL,
    status VARCHAR(20) NOT NULL,
    total_amount_cents INTEGER NOT NULL,
    currency VARCHAR(3) NOT NULL,
    subtotal_cents INTEGER NOT NULL,
    subtotal_currency VARCHAR(3) NOT NULL,
    discount_amount_cents INTEGER NOT NULL,
    discount_currency VARCHAR(3) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    shipped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
);

-- Table OrderLine
CREATE TABLE order_line (
    id UUID NOT NULL PRIMARY KEY,
    order_id UUID NOT NULL,
    product_reference VARCHAR(20) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    unit_price_cents INTEGER NOT NULL,
    unit_currency VARCHAR(3) NOT NULL,
    quantity INTEGER NOT NULL,
    total_cents INTEGER NOT NULL,
    total_currency VARCHAR(3) NOT NULL,
    CONSTRAINT FK_order_line_order_id FOREIGN KEY (order_id)
        REFERENCES "order" (id) ON DELETE CASCADE
);
```

---

## Dépendances

### Bloquantes

- **US-006**: Entité Order pure créée (nécessite Order et OrderLine dans Domain)
- **US-017**: Doctrine Custom Types pour VOs (nécessite MoneyType - EPIC-002)

### Bloque

- **US-025**: DoctrineOrderRepository implémentation complète (nécessite mapping configuré)
- **US-009**: Structure Application (nécessite Order persistable pour Use Cases)

---

## Références

- `.claude/rules/02-architecture-clean-ddd.md` (lignes 260-320, Doctrine XML mappings)
- `.claude/rules/13-ddd-patterns.md` (lignes 15-155, Aggregates persistence)
- `.claude/rules/19-aggregates.md` (Aggregate Root persistence pattern)
- `.claude/examples/doctrine-mapping-aggregate.md` (Template aggregate mapping)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 45-73, entités couplées)
- **Doctrine XML Mapping Reference**: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/xml-mapping.html
- **Doctrine Custom Types**: https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
- US-003 (Client mapping pattern), US-005 (User mapping pattern)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |

