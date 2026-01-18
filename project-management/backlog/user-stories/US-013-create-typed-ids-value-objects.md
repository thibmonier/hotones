# US-013: Créer Value Objects IDs typés (ClientId, UserId, OrderId)

**EPIC:** [EPIC-002](../epics/EPIC-002-value-objects.md) - Implémentation des Value Objects
**Priorité:** 🔴 CRITIQUE
**Points:** 3
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer des Value Objects pour les identifiants typés (ClientId, UserId, OrderId)
**Afin de** garantir la type safety et éviter les confusions d'identifiants entre entités différentes

---

## Critères d'acceptation

### GIVEN: Les entités Domain ont besoin d'identifiants typés

**WHEN:** Je crée les Value Objects ClientId, UserId, OrderId

**THEN:**
- [ ] `ClientId.php` créé dans `src/Domain/Client/ValueObject/`
- [ ] `UserId.php` créé dans `src/Domain/User/ValueObject/`
- [ ] `OrderId.php` créé dans `src/Domain/Order/ValueObject/`
- [ ] Chaque ID est une classe `final readonly`
- [ ] Factory method `generate()` avec UUID v4
- [ ] Factory method `fromString()` avec validation UUID
- [ ] Méthode `getValue()` retourne string UUID
- [ ] Méthode `equals()` pour comparaison par valeur
- [ ] Méthode `__toString()` pour casting
- [ ] Validation UUID dans le constructeur (fail-fast)
- [ ] Aucun setter (immutabilité garantie)
- [ ] Aucune dépendance à Doctrine ou Symfony

### GIVEN: Les Value Objects IDs typés existent

**WHEN:** J'exécute PHPStan niveau max sur src/Domain/

**THEN:**
- [ ] Aucune erreur PHPStan
- [ ] Type safety garantie (impossible de passer ClientId où UserId attendu)
- [ ] Deptrac valide: Domain ne dépend de rien

### GIVEN: Les Value Objects IDs typés existent

**WHEN:** J'exécute les tests unitaires

**THEN:**
- [ ] Tests unitaires passent sans base de données
- [ ] Tests de création (generate, fromString)
- [ ] Tests de validation (UUID invalide → exception)
- [ ] Tests d'égalité (equals, same UUID)
- [ ] Tests de casting (__toString)
- [ ] Couverture code ≥ 90% sur chaque ID
- [ ] Tests s'exécutent en moins de 100ms

---

## Tâches techniques

### [DOMAIN] Créer Value Object ClientId (1.5h)

**Template ClientId:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Client\ValueObject;

use Symfony\Component\Uid\Uuid;

/**
 * Client Identity Value Object.
 *
 * Represents a unique identifier for Client aggregate.
 * Uses UUID v4 for global uniqueness.
 *
 * Characteristics:
 * - Immutable (final readonly)
 * - Type-safe (cannot pass to UserId or OrderId)
 * - Validated (only valid UUIDs accepted)
 * - Equality by value
 *
 * Usage:
 * $id = ClientId::generate();
 * $id = ClientId::fromString('550e8400-e29b-41d4-a716-446655440000');
 */
final readonly class ClientId
{
    private function __construct(
        private string $value,
    ) {
        $this->validate();
    }

    /**
     * Generate a new unique ClientId.
     */
    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    /**
     * Create ClientId from UUID string.
     *
     * @param string $id UUID in RFC 4122 format
     * @throws \InvalidArgumentException if not a valid UUID
     */
    public static function fromString(string $id): self
    {
        return new self($id);
    }

    /**
     * Get the UUID value as string.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Compare with another ClientId by value.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Cast to string (returns UUID).
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Validate UUID format.
     *
     * @throws \InvalidArgumentException if invalid UUID format
     */
    private function validate(): void
    {
        if (!Uuid::isValid($this->value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid UUID format for ClientId: %s', $this->value)
            );
        }
    }
}
```

**Actions:**
- Créer `src/Domain/Client/ValueObject/ClientId.php`
- Classe `final readonly`
- Constructor `private` avec validation UUID
- Factory methods `generate()` et `fromString()`
- Méthode `equals()` pour comparaison
- Méthode `__toString()` pour casting
- Validation UUID via `Symfony\Component\Uid\Uuid::isValid()`

### [DOMAIN] Créer Value Object UserId (1h)

**Template UserId:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use Symfony\Component\Uid\Uuid;

/**
 * User Identity Value Object.
 *
 * Represents a unique identifier for User aggregate.
 * Uses UUID v4 for global uniqueness.
 */
final readonly class UserId
{
    private function __construct(
        private string $value,
    ) {
        $this->validate();
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (!Uuid::isValid($this->value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid UUID format for UserId: %s', $this->value)
            );
        }
    }
}
```

**Actions:**
- Créer `src/Domain/User/ValueObject/UserId.php`
- Même structure que ClientId
- Validation UUID

### [DOMAIN] Créer Value Object OrderId (1h)

**Template OrderId:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

use Symfony\Component\Uid\Uuid;

/**
 * Order Identity Value Object.
 *
 * Represents a unique identifier for Order aggregate.
 * Uses UUID v4 for global uniqueness.
 */
final readonly class OrderId
{
    private function __construct(
        private string $value,
    ) {
        $this->validate();
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (!Uuid::isValid($this->value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid UUID format for OrderId: %s', $this->value)
            );
        }
    }
}
```

**Actions:**
- Créer `src/Domain/Order/ValueObject/OrderId.php`
- Même structure que ClientId et UserId
- Validation UUID

### [INFRA] Créer Doctrine Custom Type pour ClientId (1h)

**Template ClientIdType:**
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Client\ValueObject\ClientId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\GuidType;

/**
 * Doctrine Custom Type for ClientId Value Object.
 *
 * Maps ClientId to UUID (GUID) database column.
 */
final class ClientIdType extends GuidType
{
    public const string NAME = 'client_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ClientId
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ClientId) {
            return $value;
        }

        if (!is_string($value)) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', 'string', ClientId::class]
            );
        }

        try {
            return ClientId::fromString($value);
        } catch (\InvalidArgumentException $e) {
            throw ConversionException::conversionFailed($value, $this->getName(), $e);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof ClientId) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', ClientId::class]
            );
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
- Créer `src/Infrastructure/Persistence/Doctrine/Type/ClientIdType.php`
- Extends `GuidType` (UUID SQL type)
- `convertToPHPValue()`: string UUID → ClientId VO
- `convertToDatabaseValue()`: ClientId VO → string UUID
- Validation via `ClientId::fromString()`
- ConversionException si type invalide

### [INFRA] Créer Doctrine Custom Type pour UserId (0.5h)

**Template UserIdType:**
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\User\ValueObject\UserId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\GuidType;

final class UserIdType extends GuidType
{
    public const string NAME = 'user_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?UserId
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UserId) {
            return $value;
        }

        if (!is_string($value)) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', 'string', UserId::class]
            );
        }

        try {
            return UserId::fromString($value);
        } catch (\InvalidArgumentException $e) {
            throw ConversionException::conversionFailed($value, $this->getName(), $e);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof UserId) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', UserId::class]
            );
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
- Créer `src/Infrastructure/Persistence/Doctrine/Type/UserIdType.php`
- Même structure que ClientIdType

### [INFRA] Créer Doctrine Custom Type pour OrderId (0.5h)

**Template OrderIdType:**
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\OrderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\GuidType;

final class OrderIdType extends GuidType
{
    public const string NAME = 'order_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?OrderId
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof OrderId) {
            return $value;
        }

        if (!is_string($value)) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', 'string', OrderId::class]
            );
        }

        try {
            return OrderId::fromString($value);
        } catch (\InvalidArgumentException $e) {
            throw ConversionException::conversionFailed($value, $this->getName(), $e);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof OrderId) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', OrderId::class]
            );
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
- Même structure que ClientIdType et UserIdType

### [CONFIG] Enregistrer les Custom Types Doctrine (0.5h)

**Configuration Doctrine:**
```yaml
# config/packages/doctrine.yaml

doctrine:
    dbal:
        types:
            # IDs typés
            client_id: App\Infrastructure\Persistence\Doctrine\Type\ClientIdType
            user_id: App\Infrastructure\Persistence\Doctrine\Type\UserIdType
            order_id: App\Infrastructure\Persistence\Doctrine\Type\OrderIdType

        mapping_types:
            client_id: guid
            user_id: guid
            order_id: guid
```

**Actions:**
- Modifier `config/packages/doctrine.yaml`
- Enregistrer les 3 custom types
- Mapper vers type SQL `guid` (UUID)

**Vérification:**
```bash
# Lister les types Doctrine
make console CMD="dbal:types"

# Doit afficher:
# client_id  App\Infrastructure\Persistence\Doctrine\Type\ClientIdType
# user_id    App\Infrastructure\Persistence\Doctrine\Type\UserIdType
# order_id   App\Infrastructure\Persistence\Doctrine\Type\OrderIdType
```

### [TEST] Créer tests unitaires ClientId (1h)

**Template ClientIdTest.php:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client\ValueObject;

use App\Domain\Client\ValueObject\ClientId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ClientIdTest extends TestCase
{
    /** @test */
    public function it_generates_a_valid_client_id(): void
    {
        // When
        $clientId = ClientId::generate();

        // Then
        self::assertInstanceOf(ClientId::class, $clientId);
        self::assertTrue(Uuid::isValid($clientId->getValue()));
    }

    /** @test */
    public function it_creates_client_id_from_valid_uuid_string(): void
    {
        // Given
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        // When
        $clientId = ClientId::fromString($uuid);

        // Then
        self::assertEquals($uuid, $clientId->getValue());
    }

    /** @test */
    public function it_throws_exception_for_invalid_uuid_format(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format for ClientId');

        // When
        ClientId::fromString('invalid-uuid-format');
    }

    /** @test */
    public function it_throws_exception_for_empty_string(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);

        // When
        ClientId::fromString('');
    }

    /** @test */
    public function it_compares_two_client_ids_by_value(): void
    {
        // Given
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $clientId1 = ClientId::fromString($uuid);
        $clientId2 = ClientId::fromString($uuid);

        // Then
        self::assertTrue($clientId1->equals($clientId2));
    }

    /** @test */
    public function it_returns_false_when_comparing_different_client_ids(): void
    {
        // Given
        $clientId1 = ClientId::generate();
        $clientId2 = ClientId::generate();

        // Then
        self::assertFalse($clientId1->equals($clientId2));
    }

    /** @test */
    public function it_casts_to_string(): void
    {
        // Given
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $clientId = ClientId::fromString($uuid);

        // Then
        self::assertEquals($uuid, (string) $clientId);
        self::assertEquals($uuid, $clientId->__toString());
    }

    /** @test */
    public function generated_ids_are_unique(): void
    {
        // When
        $id1 = ClientId::generate();
        $id2 = ClientId::generate();

        // Then
        self::assertNotEquals($id1->getValue(), $id2->getValue());
        self::assertFalse($id1->equals($id2));
    }

    /** @test */
    public function it_is_immutable(): void
    {
        // Given
        $clientId = ClientId::generate();
        $originalValue = $clientId->getValue();

        // When: Try to use reflection to modify (should fail with readonly)
        // Readonly properties prevent modification

        // Then
        self::assertEquals($originalValue, $clientId->getValue());
    }
}
```

**Actions:**
- Créer `tests/Unit/Domain/Client/ValueObject/ClientIdTest.php`
- Tests de création (generate, fromString)
- Tests de validation (UUID invalide)
- Tests d'égalité (equals)
- Tests de casting (__toString)
- Tests d'unicité (generate produit IDs différents)
- Tests d'immutabilité (readonly garantit)
- Couverture ≥ 90%

### [TEST] Créer tests unitaires UserId (0.5h)

**Template UserIdTest.php:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\ValueObject;

use App\Domain\User\ValueObject\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserIdTest extends TestCase
{
    /** @test */
    public function it_generates_a_valid_user_id(): void
    {
        $userId = UserId::generate();

        self::assertInstanceOf(UserId::class, $userId);
        self::assertTrue(Uuid::isValid($userId->getValue()));
    }

    /** @test */
    public function it_creates_user_id_from_valid_uuid_string(): void
    {
        $uuid = '660e8400-e29b-41d4-a716-446655440001';
        $userId = UserId::fromString($uuid);

        self::assertEquals($uuid, $userId->getValue());
    }

    /** @test */
    public function it_throws_exception_for_invalid_uuid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format for UserId');

        UserId::fromString('not-a-uuid');
    }

    /** @test */
    public function it_compares_two_user_ids_by_value(): void
    {
        $uuid = '660e8400-e29b-41d4-a716-446655440001';
        $userId1 = UserId::fromString($uuid);
        $userId2 = UserId::fromString($uuid);

        self::assertTrue($userId1->equals($userId2));
    }

    /** @test */
    public function it_casts_to_string(): void
    {
        $uuid = '660e8400-e29b-41d4-a716-446655440001';
        $userId = UserId::fromString($uuid);

        self::assertEquals($uuid, (string) $userId);
    }
}
```

**Actions:**
- Créer `tests/Unit/Domain/User/ValueObject/UserIdTest.php`
- Même structure que ClientIdTest
- Couverture ≥ 90%

### [TEST] Créer tests unitaires OrderId (0.5h)

**Template OrderIdTest.php:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\OrderId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrderIdTest extends TestCase
{
    /** @test */
    public function it_generates_a_valid_order_id(): void
    {
        $orderId = OrderId::generate();

        self::assertInstanceOf(OrderId::class, $orderId);
        self::assertTrue(Uuid::isValid($orderId->getValue()));
    }

    /** @test */
    public function it_creates_order_id_from_valid_uuid_string(): void
    {
        $uuid = '770e8400-e29b-41d4-a716-446655440002';
        $orderId = OrderId::fromString($uuid);

        self::assertEquals($uuid, $orderId->getValue());
    }

    /** @test */
    public function it_throws_exception_for_invalid_uuid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format for OrderId');

        OrderId::fromString('invalid');
    }

    /** @test */
    public function it_compares_two_order_ids_by_value(): void
    {
        $uuid = '770e8400-e29b-41d4-a716-446655440002';
        $orderId1 = OrderId::fromString($uuid);
        $orderId2 = OrderId::fromString($uuid);

        self::assertTrue($orderId1->equals($orderId2));
    }

    /** @test */
    public function it_casts_to_string(): void
    {
        $uuid = '770e8400-e29b-41d4-a716-446655440002';
        $orderId = OrderId::fromString($uuid);

        self::assertEquals($uuid, (string) $orderId);
    }
}
```

**Actions:**
- Créer `tests/Unit/Domain/Order/ValueObject/OrderIdTest.php`
- Même structure que ClientIdTest et UserIdTest
- Couverture ≥ 90%

### [TEST] Créer tests d'intégration Doctrine Types (1.5h)

**Template ClientIdTypeTest.php:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Client\ValueObject\ClientId;
use App\Infrastructure\Persistence\Doctrine\Type\ClientIdType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

final class ClientIdTypeTest extends TestCase
{
    private ClientIdType $type;
    private PostgreSQLPlatform $platform;

    protected function setUp(): void
    {
        if (!Type::hasType(ClientIdType::NAME)) {
            Type::addType(ClientIdType::NAME, ClientIdType::class);
        }

        $this->type = Type::getType(ClientIdType::NAME);
        $this->platform = new PostgreSQLPlatform();
    }

    /** @test */
    public function it_converts_null_to_php_value(): void
    {
        $result = $this->type->convertToPHPValue(null, $this->platform);

        self::assertNull($result);
    }

    /** @test */
    public function it_converts_valid_uuid_string_to_client_id(): void
    {
        // Given
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        // When
        $result = $this->type->convertToPHPValue($uuid, $this->platform);

        // Then
        self::assertInstanceOf(ClientId::class, $result);
        self::assertEquals($uuid, $result->getValue());
    }

    /** @test */
    public function it_converts_client_id_to_database_value(): void
    {
        // Given
        $clientId = ClientId::fromString('550e8400-e29b-41d4-a716-446655440000');

        // When
        $result = $this->type->convertToDatabaseValue($clientId, $this->platform);

        // Then
        self::assertEquals('550e8400-e29b-41d4-a716-446655440000', $result);
    }

    /** @test */
    public function it_converts_null_client_id_to_null_database_value(): void
    {
        $result = $this->type->convertToDatabaseValue(null, $this->platform);

        self::assertNull($result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_uuid_format(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue('invalid-uuid', $this->platform);
    }

    /** @test */
    public function it_throws_exception_for_invalid_type_to_database(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('not-a-client-id-object', $this->platform);
    }

    /** @test */
    public function it_has_correct_name(): void
    {
        self::assertEquals('client_id', $this->type->getName());
    }

    /** @test */
    public function it_requires_sql_comment_hint(): void
    {
        self::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    /** @test */
    public function it_returns_client_id_if_already_client_id_instance(): void
    {
        // Given
        $clientId = ClientId::generate();

        // When
        $result = $this->type->convertToPHPValue($clientId, $this->platform);

        // Then
        self::assertSame($clientId, $result);
    }
}
```

**Actions:**
- Créer `tests/Integration/Infrastructure/Persistence/Doctrine/Type/ClientIdTypeTest.php`
- Tests de conversion NULL
- Tests de conversion UUID → ClientId
- Tests de conversion ClientId → UUID
- Tests d'exception (UUID invalide, type invalide)
- Tests de métadonnées (name, SQL comment hint)
- Créer tests similaires pour UserIdType et OrderIdType
- Utiliser `PostgreSQLPlatform` pour les tests

### [DOC] Documenter les IDs typés (1h)

**Template .claude/examples/value-object-typed-ids.md:**
```markdown
# Value Objects - IDs Typés (ClientId, UserId, OrderId)

## Caractéristiques

Les **IDs typés** sont des Value Objects représentant l'identité unique d'une entité.

**Avantages:**
- ✅ **Type safety**: Impossible de passer un ClientId où un UserId est attendu
- ✅ **Validation**: UUID vérifié à la création (fail-fast)
- ✅ **Immutabilité**: `final readonly` garantit l'immuabilité
- ✅ **Égalité par valeur**: `equals()` compare les UUIDs
- ✅ **Lisibilité**: `ClientId` est plus clair que `string`
- ✅ **Refactoring safe**: PHPStan détecte les erreurs de type

## Création d'IDs

### Génération automatique (recommandé)

```php
<?php

use App\Domain\Client\ValueObject\ClientId;

// Génère un nouvel UUID v4
$clientId = ClientId::generate();
// Résultat: ClientId('550e8400-e29b-41d4-a716-446655440000')
```

### Depuis UUID existant

```php
<?php

// Depuis la base de données ou API
$clientId = ClientId::fromString('550e8400-e29b-41d4-a716-446655440000');

// Validation automatique (lance exception si UUID invalide)
try {
    $clientId = ClientId::fromString('invalid-uuid');
} catch (\InvalidArgumentException $e) {
    // "Invalid UUID format for ClientId: invalid-uuid"
}
```

## Utilisation dans les Entités

### Entity avec ID typé

```php
<?php

declare(strict_types=1);

namespace App\Domain\Client\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;

final class Client
{
    // ✅ Type safety: ID typé (pas string)
    private ClientId $id;
    private Email $email;
    private string $name;

    private function __construct(
        ClientId $id,
        Email $email,
        string $name
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
    }

    public static function create(
        ClientId $id,
        Email $email,
        string $name
    ): self {
        return new self($id, $email, $name);
    }

    // ✅ Getter retourne ClientId (pas string)
    public function getId(): ClientId
    {
        return $this->id;
    }
}
```

### Utilisation dans Use Case

```php
<?php

namespace App\Application\Client\UseCase;

use App\Domain\Client\Repository\ClientRepositoryInterface;
use App\Domain\Client\ValueObject\ClientId;

final readonly class GetClientDetailsUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(GetClientDetailsQuery $query): ClientDetailsDTO
    {
        // ✅ Type safety: fromString retourne ClientId
        $clientId = ClientId::fromString($query->clientId);

        // ✅ Repository attend ClientId (pas string)
        $client = $this->clientRepository->findById($clientId);

        return ClientDetailsDTO::fromEntity($client);
    }
}
```

## Type Safety Démonstration

### ❌ AVANT: Types primitifs (erreurs possibles)

```php
<?php

// ❌ Accepte n'importe quel string
public function findClient(string $id): ?Client
{
    return $this->clientRepository->find($id);
}

// ❌ DANGER: Confusion possible entre IDs
$userId = '550e8400-e29b-41d4-a716-446655440000';
$client = $this->findClient($userId); // ❌ Compile! Mais logiquement incorrect
```

### ✅ APRÈS: IDs typés (PHPStan détecte l'erreur)

```php
<?php

// ✅ Accepte uniquement ClientId
public function findClient(ClientId $id): ?Client
{
    return $this->clientRepository->findById($id);
}

// ✅ PHPStan erreur détectée à la compilation
$userId = UserId::generate();
$client = $this->findClient($userId); // ❌ PHPStan ERROR: Expected ClientId, got UserId
```

## Comparaison et Égalité

```php
<?php

// Génération de deux IDs
$id1 = ClientId::generate();
$id2 = ClientId::generate();

// Égalité par valeur
$id1->equals($id2); // false (UUIDs différents)

// Même UUID
$id3 = ClientId::fromString($id1->getValue());
$id1->equals($id3); // true (même UUID)

// Casting
echo $id1; // "550e8400-e29b-41d4-a716-446655440000"
$uuid = (string) $id1; // Même résultat
```

## Doctrine Mapping

### Mapping XML

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Domain\Client\Entity\Client" table="client">
        <!-- ✅ ID typé avec custom type Doctrine -->
        <id name="id" type="client_id">
            <generator strategy="NONE"/> <!-- Généré dans le Domain via ClientId::generate() -->
        </id>

        <field name="name" type="string" length="255" nullable="false"/>
        <field name="email" type="email" nullable="false"/>
    </entity>
</doctrine-mapping>
```

### Migration Doctrine

```php
<?php

// Version20250126120000.php

public function up(Schema $schema): void
{
    // ✅ Colonne UUID avec type GUID (PostgreSQL: UUID)
    $this->addSql('CREATE TABLE client (
        id UUID NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        PRIMARY KEY(id)
    )');
}
```

## Utilisation dans Repository Interface

```php
<?php

namespace App\Domain\Client\Repository;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;

interface ClientRepositoryInterface
{
    /**
     * Find client by typed ID.
     *
     * @throws ClientNotFoundException if not found
     */
    public function findById(ClientId $id): Client;

    /**
     * Save client.
     */
    public function save(Client $client): void;

    /**
     * Delete client.
     */
    public function delete(Client $client): void;
}
```

### Implémentation Doctrine Repository

```php
<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\Repository\ClientRepositoryInterface;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\Exception\ClientNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findById(ClientId $id): Client
    {
        // ✅ Doctrine Custom Type convertit automatiquement
        $client = $this->entityManager->find(Client::class, $id);

        if ($client === null) {
            throw ClientNotFoundException::withId($id);
        }

        return $client;
    }

    public function save(Client $client): void
    {
        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }
}
```

## Avantages des IDs Typés

| Aspect | String ID | Typed ID (ClientId) |
|--------|-----------|---------------------|
| **Type safety** | ❌ Aucune (string générique) | ✅ PHPStan détecte les erreurs |
| **Validation** | ❌ Manuelle partout | ✅ Automatique (constructeur) |
| **Confusion IDs** | ❌ Possible (User vs Client) | ✅ Impossible (types différents) |
| **Refactoring** | ❌ Risqué (recherche/remplacement) | ✅ Safe (PHPStan guide) |
| **Lisibilité** | ⚠️ Moyenne (`string $id`) | ✅ Excellente (`ClientId $id`) |
| **Tests** | ⚠️ Mocks complexes | ✅ Mocks simples |
| **Documentation** | ⚠️ Commentaires nécessaires | ✅ Auto-documenté |

## Anti-Patterns à Éviter

### ❌ Utiliser string partout

```php
<?php

// ❌ MAUVAIS: Types primitifs
public function transferFunds(string $fromUserId, string $toUserId, float $amount): void
{
    // Risque de confusion: lequel est lequel?
    $this->debit($toUserId, $amount); // ❌ Erreur logique! (inversé)
    $this->credit($fromUserId, $amount);
}
```

### ✅ Utiliser IDs typés

```php
<?php

// ✅ BON: IDs typés
public function transferFunds(UserId $fromUser, UserId $toUser, Money $amount): void
{
    // ✅ Types clairs, PHPStan détecterait l'inversion
    $this->debit($toUser, $amount); // ❌ PHPStan ERROR si inversé
    $this->credit($fromUser, $amount);
}
```

### ❌ Créer des IDs avec identité (mutable)

```php
<?php

// ❌ MAUVAIS: ID mutable
class ClientId
{
    private string $value;

    public function setValue(string $value): void // ❌ Setter interdit!
    {
        $this->value = $value;
    }
}
```

### ✅ IDs immutables (readonly)

```php
<?php

// ✅ BON: Immutable
final readonly class ClientId
{
    private function __construct(
        private string $value, // ✅ readonly: pas de modification
    ) {}

    // ✅ Pas de setter
}
```

### ❌ Valider les UUIDs partout

```php
<?php

// ❌ MAUVAIS: Validation dupliquée
public function findClient(string $id): ?Client
{
    if (!Uuid::isValid($id)) { // ❌ Validation répétée
        throw new InvalidArgumentException();
    }

    return $this->repository->find($id);
}

public function updateClient(string $id, string $name): void
{
    if (!Uuid::isValid($id)) { // ❌ Duplication
        throw new InvalidArgumentException();
    }

    // ...
}
```

### ✅ Validation centralisée dans le VO

```php
<?php

// ✅ BON: Validation dans ClientId
public function findClient(ClientId $id): ?Client
{
    // ✅ UUID déjà validé (impossible d'avoir un ClientId invalide)
    return $this->repository->findById($id);
}

public function updateClient(ClientId $id, string $name): void
{
    // ✅ Pas de validation nécessaire
    // ...
}
```

## Migration depuis IDs primitifs

### Phase 1: Créer les Value Objects

```php
<?php

// Créer ClientId, UserId, OrderId
```

### Phase 2: Créer Doctrine Custom Types

```php
<?php

// Créer ClientIdType, UserIdType, OrderIdType
```

### Phase 3: Migrer les Entities

```php
<?php

// AVANT
class Client
{
    private int $id; // ❌ int auto-increment

    public function getId(): int
    {
        return $this->id;
    }
}

// APRÈS
class Client
{
    private ClientId $id; // ✅ UUID typé

    public function getId(): ClientId
    {
        return $this->id;
    }
}
```

### Phase 4: Migration base de données

```sql
-- Migration: int → UUID

-- 1. Ajouter colonne UUID temporaire
ALTER TABLE client ADD COLUMN id_uuid UUID;

-- 2. Générer UUIDs pour les lignes existantes
UPDATE client SET id_uuid = gen_random_uuid();

-- 3. Mettre à jour les foreign keys (OrderLine, etc.)
ALTER TABLE order_line ADD COLUMN client_id_uuid UUID;
UPDATE order_line ol
SET client_id_uuid = c.id_uuid
FROM client c
WHERE ol.client_id = c.id;

-- 4. Supprimer anciennes colonnes
ALTER TABLE order_line DROP COLUMN client_id;
ALTER TABLE order_line RENAME COLUMN client_id_uuid TO client_id;

-- 5. Supprimer ancienne colonne id int
ALTER TABLE client DROP COLUMN id;
ALTER TABLE client RENAME COLUMN id_uuid TO id;

-- 6. Définir nouvelle primary key
ALTER TABLE client ADD PRIMARY KEY (id);
```

## Cas d'usage

### Use Case: Créer un Client

```php
<?php

namespace App\Application\Client\UseCase;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;

final readonly class CreateClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(CreateClientCommand $command): ClientId
    {
        // ✅ Générer un ID typé
        $clientId = ClientId::generate();

        // ✅ Créer le client
        $client = Client::create(
            $clientId,
            Email::fromString($command->email),
            $command->name
        );

        // ✅ Sauvegarder
        $this->clientRepository->save($client);

        // ✅ Retourner l'ID typé (pas string)
        return $clientId;
    }
}
```

### Controller: Récupérer un Client

```php
<?php

namespace App\Presentation\Controller\Api;

use App\Application\Client\UseCase\GetClientDetailsQuery;
use App\Application\Client\UseCase\GetClientDetailsUseCase;
use App\Domain\Client\ValueObject\ClientId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class ClientApiController extends AbstractController
{
    public function __construct(
        private readonly GetClientDetailsUseCase $getClientDetails,
    ) {}

    #[Route('/api/clients/{id}', name: 'api_client_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            // ✅ Convertir string → ClientId (validation automatique)
            $clientId = ClientId::fromString($id);
        } catch (\InvalidArgumentException $e) {
            // ✅ UUID invalide → 400 Bad Request
            return new JsonResponse(
                ['error' => 'Invalid client ID format'],
                400
            );
        }

        // ✅ Query avec ID typé
        $query = new GetClientDetailsQuery($clientId);
        $clientDto = $this->getClientDetails->execute($query);

        return new JsonResponse($clientDto->toArray());
    }
}
```

### Repository: Recherche par ID

```php
<?php

namespace App\Domain\Client\Repository;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;

interface ClientRepositoryInterface
{
    /**
     * Find client by typed ID.
     *
     * @param ClientId $id Typed client identifier
     * @return Client Client aggregate
     * @throws ClientNotFoundException if client not found
     */
    public function findById(ClientId $id): Client;
}

// Implémentation
final class DoctrineClientRepository implements ClientRepositoryInterface
{
    public function findById(ClientId $id): Client
    {
        // ✅ Doctrine Custom Type convertit automatiquement ClientId → UUID string
        $client = $this->entityManager->find(Client::class, $id);

        if ($client === null) {
            throw ClientNotFoundException::withId($id);
        }

        return $client;
    }
}
```

## Règles UUID

### Format UUID v4 (RFC 4122)

```
550e8400-e29b-41d4-a716-446655440000
│      │ │   │ │   │ └─ node (6 bytes)
│      │ │   │ │   └─ clock_seq (2 bytes)
│      │ │   │ └─ time_hi_and_version (2 bytes, version=4)
│      │ │   └─ time_mid (2 bytes)
│      │ └─ time_low (4 bytes)
│      └─ variant (1 byte)
└─ time_low (continued)

Format: 8-4-4-4-12 (32 caractères hexadécimaux)
```

### Avantages UUID vs Auto-increment

| Aspect | Auto-increment (int) | UUID (string) |
|--------|----------------------|---------------|
| **Unicité globale** | ❌ Uniquement par table | ✅ Globalement unique |
| **Génération** | ⚠️ Base de données | ✅ Application (Domain) |
| **Distribution** | ❌ Difficile (conflicts) | ✅ Facile (merge, sharding) |
| **Sécurité** | ❌ Prévisible (énumérable) | ✅ Non prévisible |
| **Taille** | ✅ 4 bytes (int) | ⚠️ 16 bytes (UUID) |
| **Lisibilité** | ✅ Simple (1, 2, 3) | ⚠️ Long |
| **Indexes** | ✅ Rapide | ⚠️ Légèrement plus lent |

**Recommandation Atoll Tourisme**: UUID pour tous les aggregates (Client, User, Order, Reservation)

### Performance UUID (PostgreSQL)

```sql
-- ✅ PostgreSQL: Type UUID natif (optimisé)
CREATE TABLE client (
    id UUID PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255)
);

-- ✅ Index sur UUID
CREATE INDEX idx_client_id ON client(id);

-- Performance:
-- UUID: ~0.2ms pour find by ID
-- INT:  ~0.1ms pour find by ID
-- Différence négligeable pour la plupart des cas
```

## Checklist Value Object ID

- [ ] Classe `final readonly`
- [ ] Constructor `private`
- [ ] Factory method `generate()` avec UUID v4
- [ ] Factory method `fromString()` avec validation
- [ ] Méthode `getValue(): string`
- [ ] Méthode `equals(self $other): bool`
- [ ] Méthode `__toString(): string`
- [ ] Validation UUID dans constructeur
- [ ] Doctrine Custom Type créé (extends GuidType)
- [ ] Type enregistré dans doctrine.yaml
- [ ] Tests unitaires (generate, fromString, validation, equals)
- [ ] Tests d'intégration Doctrine Type
- [ ] PHPStan niveau max passe
- [ ] Documentation avec exemples

## Ressources

- **Symfony UID Component:** https://symfony.com/doc/current/components/uid.html
- **UUID RFC 4122:** https://www.rfc-editor.org/rfc/rfc4122
- **Doctrine GUID Type:** https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#guid
- **DDD Identity Pattern:** Domain-Driven Design - Eric Evans, Chapitre 5

---

**Exemple complet d'usage:**

```php
<?php

// 1. Controller: Validation UUID
$clientId = ClientId::fromString($request->get('id')); // ✅ Valide ou lance exception

// 2. Command: ID typé
$command = new CreateClientCommand(
    ClientId::generate(), // ✅ Nouvel ID
    Email::fromString('client@example.com'),
    'Acme Corp'
);

// 3. Handler: Type safety
$client = Client::create(
    $command->clientId, // ✅ ClientId attendu
    $command->email,
    $command->name
);

// 4. Repository: Recherche par ID typé
$found = $this->clientRepository->findById($clientId); // ✅ Type safety

// 5. Comparaison
if ($found->getId()->equals($clientId)) { // ✅ Égalité par valeur
    // ...
}
```
```

**Actions:**
- Créer `.claude/examples/value-object-typed-ids.md`
- Exemples de création (generate, fromString)
- Exemples d'utilisation dans Entity, Use Case, Repository
- Démonstration type safety (PHPStan détecte confusion IDs)
- Comparaison UUID vs auto-increment
- Mapping Doctrine XML avec custom types
- Migration strategy depuis int vers UUID
- Performance considerations (PostgreSQL UUID native)
- Avantages et trade-offs
- Checklist complet

---

## Définition de Done (DoD)

- [ ] `ClientId.php` créé dans `src/Domain/Client/ValueObject/`
- [ ] `UserId.php` créé dans `src/Domain/User/ValueObject/`
- [ ] `OrderId.php` créé dans `src/Domain/Order/ValueObject/`
- [ ] Toutes les classes `final readonly`
- [ ] Constructor `private` avec validation UUID
- [ ] Factory method `generate()` avec UUID v4
- [ ] Factory method `fromString()` avec validation
- [ ] Méthode `getValue()` retourne string UUID
- [ ] Méthode `equals()` pour comparaison par valeur
- [ ] Méthode `__toString()` pour casting
- [ ] Validation UUID fail-fast (lance exception si invalide)
- [ ] `ClientIdType.php` créé dans `src/Infrastructure/Persistence/Doctrine/Type/`
- [ ] `UserIdType.php` créé dans `src/Infrastructure/Persistence/Doctrine/Type/`
- [ ] `OrderIdType.php` créé dans `src/Infrastructure/Persistence/Doctrine/Type/`
- [ ] Custom Types enregistrés dans `config/packages/doctrine.yaml`
- [ ] Tests unitaires `ClientIdTest.php` avec couverture ≥ 90%
- [ ] Tests unitaires `UserIdTest.php` avec couverture ≥ 90%
- [ ] Tests unitaires `OrderIdTest.php` avec couverture ≥ 90%
- [ ] Tests de création (generate, fromString)
- [ ] Tests de validation (UUID invalide → exception)
- [ ] Tests d'égalité (equals, same UUID)
- [ ] Tests de casting (__toString)
- [ ] Tests d'unicité (generate produit IDs différents)
- [ ] Tests d'immutabilité (readonly garantit)
- [ ] Tests d'intégration `ClientIdTypeTest.php`
- [ ] Tests d'intégration `UserIdTypeTest.php`
- [ ] Tests d'intégration `OrderIdTypeTest.php`
- [ ] Tests de conversion NULL
- [ ] Tests de conversion UUID → ID VO
- [ ] Tests de conversion ID VO → UUID
- [ ] Tests d'exception (UUID invalide, type invalide)
- [ ] PHPStan niveau max passe sur src/Domain/
- [ ] Deptrac valide: Domain ne dépend de rien
- [ ] Documentation `.claude/examples/value-object-typed-ids.md` créée
- [ ] Exemples d'utilisation dans Entity, Repository, Use Case
- [ ] Démonstration type safety (PHPStan détecte erreurs)
- [ ] Comparaison UUID vs auto-increment documentée
- [ ] Migration strategy documentée
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(domain): create typed ID value objects (ClientId, UserId, OrderId)`

---

## Notes techniques

### Pattern Identity Value Object

Les IDs typés sont des **Value Objects** représentant l'identité unique d'un Aggregate Root.

**Caractéristiques:**
1. **Immutable** - `final readonly`, pas de setter
2. **Type-safe** - PHPStan empêche confusion entre IDs
3. **Validated** - UUID vérifié à la création
4. **Equality by value** - `equals()` compare les UUIDs
5. **Self-documenting** - `ClientId` plus clair que `string`

### UUID vs Auto-increment

#### Pourquoi UUID?

1. **Génération dans le Domain** - Pas besoin de base de données
   ```php
   // ✅ Généré avant persist
   $client = Client::create(
       ClientId::generate(), // ✅ UUID généré ici
       Email::fromString('client@example.com'),
       'Acme Corp'
   );

   $this->repository->save($client); // ID déjà défini
   ```

2. **Unicité globale** - Pas de collision entre environnements
   ```php
   // ✅ Merge dev → prod sans conflit d'IDs
   // ✅ Sharding possible (plusieurs bases)
   // ✅ Pas de race condition
   ```

3. **Sécurité** - Non énumérable (pas de /api/clients/1, /api/clients/2, etc.)
   ```php
   // ❌ Auto-increment prévisible
   GET /api/clients/1
   GET /api/clients/2
   // Attaquant peut énumérer tous les clients!

   // ✅ UUID non prévisible
   GET /api/clients/550e8400-e29b-41d4-a716-446655440000
   // Impossible de deviner les autres IDs
   ```

4. **Distribution** - Réplication, merge, import/export simplifiés
   ```php
   // ✅ Import de données sans collision d'IDs
   // ✅ Synchronisation multi-datacenter
   ```

#### Trade-offs

| Aspect | Auto-increment | UUID |
|--------|----------------|------|
| **Taille** | 4 bytes (int) | 16 bytes (UUID) |
| **Index performance** | Légèrement plus rapide | Légèrement plus lent |
| **Lisibilité** | Très lisible (1, 2, 3) | Moins lisible (UUID long) |
| **Génération** | Base de données | Application |
| **Sécurité** | Énumérable | Non prévisible |
| **Distribution** | Conflits possibles | Aucun conflit |

**Recommandation Atoll Tourisme:**
- **UUID** pour tous les Aggregates (Client, User, Order, Reservation, Sejour)
- PostgreSQL type `UUID` natif (performance optimale)
- Symfony UID Component pour génération et validation

### Symfony UID Component

```bash
# Installation (déjà inclus dans Symfony 6.4+)
composer require symfony/uid
```

**API Symfony Uuid:**
```php
<?php

use Symfony\Component\Uid\Uuid;

// Génération UUID v4
$uuid = Uuid::v4(); // Objet Uuid
$uuid->toRfc4122(); // "550e8400-e29b-41d4-a716-446655440000"

// Validation
Uuid::isValid('550e8400-e29b-41d4-a716-446655440000'); // true
Uuid::isValid('invalid'); // false

// Conversion depuis string
$uuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');
```

### PostgreSQL UUID Type

```sql
-- PostgreSQL supporte UUID nativement
CREATE TABLE client (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255)
);

-- Index sur UUID (B-tree)
CREATE INDEX idx_client_id ON client(id);

-- Queries rapides
SELECT * FROM client WHERE id = '550e8400-e29b-41d4-a716-446655440000';
```

**Performance:**
- PostgreSQL UUID: ~0.2ms pour SELECT by ID
- PostgreSQL int: ~0.1ms pour SELECT by ID
- Différence négligeable (<0.1ms) pour la plupart des applications

### Type Safety avec PHPStan

```php
<?php

// Exemple de détection d'erreur

interface ClientRepositoryInterface
{
    public function findById(ClientId $id): Client;
}

interface UserRepositoryInterface
{
    public function findById(UserId $id): User;
}

// ❌ PHPStan ERROR détecté
$userId = UserId::generate();
$client = $this->clientRepository->findById($userId);
// ERROR: Parameter #1 $id of method ClientRepositoryInterface::findById()
//        expects ClientId, UserId given.

// ✅ CORRECT
$clientId = ClientId::generate();
$client = $this->clientRepository->findById($clientId); // ✅ Type match
```

**Avantages PHPStan avec IDs typés:**
- ✅ Détection erreurs à la compilation (pas en runtime)
- ✅ Refactoring safe (renommer, déplacer)
- ✅ Auto-complétion IDE améliorée
- ✅ Documentation automatique (types explicites)

---

## Dépendances

### Bloquantes

- **US-001**: Structure Domain créée (nécessite `src/Domain/Client/ValueObject/`, etc.)

### Bloque

- **US-002**: Extraction Client (utilisera ClientId)
- **US-004**: Extraction User (utilisera UserId)
- **US-006**: Extraction Order (utilisera OrderId)
- **US-020**: ClientRepositoryInterface (signature avec ClientId)
- **US-022**: UserRepositoryInterface (signature avec UserId)
- **US-024**: OrderRepositoryInterface (signature avec OrderId)

---

## Références

### Documentation interne

- `.claude/rules/18-value-objects.md` - Template Value Objects (lignes 45-85, IDs typés)
- `.claude/rules/02-architecture-clean-ddd.md` - Entités Domain avec IDs (lignes 95-145)
- `.claude/rules/13-ddd-patterns.md` - Identity pattern (lignes 15-40)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` - Audit source (lignes 75-108, Value Objects manquants)

### Ressources externes

- [UUID RFC 4122](https://www.rfc-editor.org/rfc/rfc4122)
- [Symfony UID Component](https://symfony.com/doc/current/components/uid.html)
- [Doctrine GUID Type](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#guid)
- [PostgreSQL UUID Type](https://www.postgresql.org/docs/current/datatype-uuid.html)
- **Livre:** *Domain-Driven Design* - Eric Evans, Chapitre 5 (Entities and Identity)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |

---

## Exemples d'utilisation avancés

### Aggregate Root avec ID typé

```php
<?php

namespace App\Domain\Client\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;

final class Client
{
    // ✅ ID typé (pas string)
    private ClientId $id;

    private function __construct(
        ClientId $id,
        Email $email,
        string $name
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
    }

    // ✅ Factory method avec ID typé
    public static function create(
        ClientId $id,
        Email $email,
        string $name
    ): self {
        return new self($id, $email, $name);
    }

    // ✅ Getter retourne ClientId (pas string)
    public function getId(): ClientId
    {
        return $this->id;
    }
}
```

### Repository avec ID typé

```php
<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findById(ClientId $id): Client
    {
        // ✅ Doctrine Custom Type convertit ClientId → UUID string automatiquement
        $client = $this->entityManager->find(Client::class, $id);

        if ($client === null) {
            throw ClientNotFoundException::withId($id);
        }

        return $client;
    }

    public function save(Client $client): void
    {
        // ✅ Doctrine Custom Type convertit UUID → string pour BDD
        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }

    public function nextIdentity(): ClientId
    {
        // ✅ Générer le prochain ID (pattern Repository DDD)
        return ClientId::generate();
    }
}
```

### Domain Event avec ID typé

```php
<?php

namespace App\Domain\Client\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;

/**
 * Domain Event: Client was created.
 */
final readonly class ClientCreatedEvent
{
    public function __construct(
        public ClientId $clientId, // ✅ ID typé
        public Email $email,
        public \DateTimeImmutable $occurredOn = new \DateTimeImmutable(),
    ) {}

    public function getAggregateId(): ClientId
    {
        return $this->clientId;
    }
}
```

### Use Case avec IDs typés

```php
<?php

namespace App\Application\Client\UseCase;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\User\ValueObject\UserId;
use App\Domain\Order\ValueObject\OrderId;

final readonly class AssignOrderToClientUseCase
{
    public function execute(AssignOrderToClientCommand $command): void
    {
        // ✅ Type safety: chaque ID a son type
        $clientId = ClientId::fromString($command->clientId);
        $orderId = OrderId::fromString($command->orderId);
        $userId = UserId::fromString($command->userId);

        $client = $this->clientRepository->findById($clientId);
        $order = $this->orderRepository->findById($orderId);
        $user = $this->userRepository->findById($userId);

        // ✅ PHPStan empêche de passer les IDs dans le mauvais ordre
        // $this->clientRepository->findById($orderId); // ❌ PHPStan ERROR
    }
}
```

### Exception avec ID typé

```php
<?php

namespace App\Domain\Client\Exception;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\Exception\DomainException;

final class ClientNotFoundException extends DomainException
{
    public static function withId(ClientId $id): self
    {
        return new self(
            sprintf(
                'Client with ID "%s" not found',
                $id->getValue()
            )
        );
    }
}
```

---

## Avantages des IDs Typés - Résumé

### 1. Type Safety

```php
// ❌ AVANT: string partout
public function assignOrder(string $clientId, string $orderId): void
{
    // Risque d'inversion
    $this->orderRepository->assignToClient($orderId, $clientId); // ❌ Inversé!
}

// ✅ APRÈS: IDs typés
public function assignOrder(ClientId $clientId, OrderId $orderId): void
{
    // ✅ PHPStan détecte l'inversion
    $this->orderRepository->assignToClient($orderId, $clientId); // ERROR!
}
```

### 2. Validation Centralisée

```php
// ❌ AVANT: Validation dispersée
if (!Uuid::isValid($id)) { /* ... */ }
if (!Uuid::isValid($id)) { /* ... */ } // Duplication partout

// ✅ APRÈS: Validation dans le VO
$clientId = ClientId::fromString($id); // ✅ Validation automatique
```

### 3. Self-Documenting Code

```php
// ❌ AVANT: Types primitifs
public function findClient(string $id): ?Client // Quel format? UUID? int?

// ✅ APRÈS: IDs typés
public function findClient(ClientId $id): ?Client // ✅ Clair: UUID ClientId
```

### 4. Refactoring Safe

```php
// ✅ Renommer ClientId en CustomerId
// PHPStan guide le refactoring (trouve toutes les utilisations)
// Recherche/remplacement safe
```

### 5. Prévention d'Erreurs

```php
// ❌ AVANT: Confusion possible
$userId = '550e8400-...';
$client = $this->clientRepo->find($userId); // ❌ Erreur logique non détectée

// ✅ APRÈS: PHPStan détecte
$userId = UserId::generate();
$client = $this->clientRepo->findById($userId); // ❌ PHPStan ERROR
```
