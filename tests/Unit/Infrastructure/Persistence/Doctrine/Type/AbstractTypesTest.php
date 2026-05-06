<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Type;

use App\Infrastructure\Persistence\Doctrine\Type\AbstractEnumType;
use App\Infrastructure\Persistence\Doctrine\Type\AbstractStringType;
use App\Infrastructure\Persistence\Doctrine\Type\AbstractUuidType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;

/**
 * Test fixture: backed enum used by AbstractEnumType test.
 */
enum TestStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

/**
 * Test fixture: string-based VO.
 */
final readonly class TestStringValueObject
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

final class AbstractTypesTest extends TestCase
{
    public function testEnumTypeRoundTrip(): void
    {
        $type = new class extends AbstractEnumType {
            protected function getEnumClass(): string
            {
                return TestStatusEnum::class;
            }

            public function getName(): string
            {
                return 'test_status';
            }
        };

        $platform = $this->createMock(AbstractPlatform::class);

        $this->assertNull($type->convertToPHPValue(null, $platform));
        $this->assertNull($type->convertToPHPValue('', $platform));
        $this->assertSame(TestStatusEnum::ACTIVE, $type->convertToPHPValue('active', $platform));

        $this->assertNull($type->convertToDatabaseValue(null, $platform));
        $this->assertSame('active', $type->convertToDatabaseValue(TestStatusEnum::ACTIVE, $platform));
    }

    public function testStringTypeRoundTrip(): void
    {
        $type = new class extends AbstractStringType {
            protected function getValueObjectClass(): string
            {
                return TestStringValueObject::class;
            }

            public function getName(): string
            {
                return 'test_string_vo';
            }
        };

        $platform = $this->createMock(AbstractPlatform::class);

        $this->assertNull($type->convertToPHPValue(null, $platform));
        $this->assertNull($type->convertToPHPValue('', $platform));

        $vo = $type->convertToPHPValue('hello', $platform);
        $this->assertInstanceOf(TestStringValueObject::class, $vo);
        $this->assertSame('hello', $vo->getValue());

        $this->assertNull($type->convertToDatabaseValue(null, $platform));
        $this->assertSame('hello', $type->convertToDatabaseValue($vo, $platform));
    }

    public function testUuidTypeRoundTrip(): void
    {
        $type = new class extends AbstractUuidType {
            protected function getValueObjectClass(): string
            {
                return TestStringValueObject::class;
            }

            public function getName(): string
            {
                return 'test_uuid_vo';
            }
        };

        $platform = $this->createMock(AbstractPlatform::class);
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->assertNull($type->convertToPHPValue(null, $platform));
        $this->assertNull($type->convertToPHPValue('', $platform));

        $vo = $type->convertToPHPValue($uuid, $platform);
        $this->assertInstanceOf(TestStringValueObject::class, $vo);
        $this->assertSame($uuid, $vo->getValue());

        $this->assertNull($type->convertToDatabaseValue(null, $platform));
        $this->assertSame($uuid, $type->convertToDatabaseValue($vo, $platform));
    }
}
