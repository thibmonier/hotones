# US-012: Créer Value Object Money (centimes + devise)

**EPIC:** [EPIC-002](../epics/EPIC-002-value-objects.md) - Implémentation des Value Objects
**Priorité:** 🔴 CRITIQUE
**Points:** 3
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer un Value Object Money avec stockage en centimes et support multi-devises
**Afin de** centraliser la gestion des montants financiers avec précision et éviter les erreurs de calcul liées aux nombres à virgule flottante

---

## Critères d'acceptation

### GIVEN: Le Value Object Money n'existe pas

**WHEN:** Je crée le Value Object Money avec stockage en centimes

**THEN:**
- [ ] Classe `src/Domain/Shared/ValueObject/Money.php` créée comme `final readonly`
- [ ] Stockage en centimes (int) pour éviter les problèmes de précision des float
- [ ] Support multi-devises (EUR, GBP) via Currency enum (créé dans US-011)
- [ ] Factory methods statiques: `fromCents()`, `fromEuros()`, `fromPounds()`, `zero()`
- [ ] Constructeur `private` avec validation (montant ≥ 0)
- [ ] Méthodes arithmétiques: `add()`, `subtract()`, `multiply()`, `divide()`
- [ ] Méthodes de comparaison: `isGreaterThan()`, `isLessThan()`, `equals()`, `isZero()`, `isPositive()`, `isNegative()`
- [ ] Méthode `format()` pour affichage localisé (€123,45 ou £123.45)
- [ ] Méthode `allocate()` pour répartition proportionnelle sans perte
- [ ] Validation devise cohérente dans opérations arithmétiques (CurrencyMismatchException)
- [ ] Aucun setter (immutabilité garantie)
- [ ] Méthode `getValue()` retourne le montant en centimes
- [ ] Méthodes `getAmountEuros()` et `getAmountPounds()` pour conversion
- [ ] Méthode `getCurrency()` retourne la devise

### GIVEN: Le Value Object Money existe

**WHEN:** J'exécute PHPStan niveau max sur src/Domain/Shared/ValueObject/

**THEN:**
- [ ] Aucune erreur PHPStan
- [ ] Classe marquée `final readonly`
- [ ] Aucune dépendance externe (pur PHP)
- [ ] Types stricts sur toutes les méthodes
- [ ] PHPDoc complet avec exemples

### GIVEN: Le Value Object Money existe

**WHEN:** J'exécute les tests unitaires

**THEN:**
- [ ] Tests unitaires passent sans dépendance externe
- [ ] Tests couvrent tous les cas (création, arithmétique, comparaison, erreurs)
- [ ] Tests couvrent les deux devises (EUR, GBP)
- [ ] Tests de précision (pas de perte avec centimes)
- [ ] Tests de division avec arrondi
- [ ] Tests d'allocation proportionnelle sans perte de centimes
- [ ] Tests de validation (montant négatif, devise incompatible)
- [ ] Couverture code ≥ 90% sur Money.php
- [ ] Tests s'exécutent en moins de 100ms
- [ ] Data providers pour tous les scénarios arithmétiques

---

## Tâches techniques

### [DOMAIN] Créer Value Object Money (3h)

**Avant (types primitifs dangereux):**
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Reservation
{
    // ❌ Float: Problèmes de précision
    #[ORM\Column(type: 'float')]
    private float $montantTotal;

    // ❌ String devise séparée
    #[ORM\Column(type: 'string', length: 3)]
    private string $devise = 'EUR';

    public function addMontant(float $montant): void
    {
        // ❌ Arithmétique float dangereuse
        $this->montantTotal += $montant; // 0.1 + 0.2 = 0.30000000000000004
    }
}
```

**Après (Value Object Money sécurisé):**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use App\Domain\Shared\Enum\Currency;
use App\Domain\Shared\Exception\CurrencyMismatchException;
use App\Domain\Shared\Exception\NegativeAmountException;

/**
 * Money Value Object - Represents a monetary amount with currency.
 *
 * Immutable value object following DDD principles.
 * Stores amount in cents/centimes (integer) to avoid floating-point precision issues.
 *
 * Examples:
 * - Money::fromEuros(123.45) → 12345 cents, EUR
 * - Money::fromPounds(99.99) → 9999 pence, GBP
 * - Money::zero() → 0 cents, EUR
 *
 * Precision:
 * - 0.1 + 0.2 = 0.30000000000000004 (float) ❌
 * - 10 + 20 = 30 (int cents) ✅
 */
final readonly class Money
{
    private function __construct(
        private int $amountInCents,
        private Currency $currency,
    ) {
        $this->validate();
    }

    /**
     * Create Money from cents/pence (integer).
     *
     * @param int $cents Amount in cents (12345 = €123.45)
     * @param Currency $currency Currency (default: EUR)
     */
    public static function fromCents(int $cents, Currency $currency = Currency::EUR): self
    {
        return new self($cents, $currency);
    }

    /**
     * Create Money from euros (float converted to cents).
     *
     * @param float $amount Amount in euros (123.45 → 12345 cents)
     */
    public static function fromEuros(float $amount): self
    {
        return new self(
            (int) round($amount * 100),
            Currency::EUR
        );
    }

    /**
     * Create Money from pounds (float converted to pence).
     *
     * @param float $amount Amount in pounds (99.99 → 9999 pence)
     */
    public static function fromPounds(float $amount): self
    {
        return new self(
            (int) round($amount * 100),
            Currency::GBP
        );
    }

    /**
     * Create zero amount.
     */
    public static function zero(Currency $currency = Currency::EUR): self
    {
        return new self(0, $currency);
    }

    // ===== Arithmetic Operations (Immutable) =====

    /**
     * Add another Money amount.
     *
     * @throws CurrencyMismatchException if currencies don't match
     */
    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            $this->amountInCents + $other->amountInCents,
            $this->currency
        );
    }

    /**
     * Subtract another Money amount.
     *
     * @throws CurrencyMismatchException if currencies don't match
     */
    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            $this->amountInCents - $other->amountInCents,
            $this->currency
        );
    }

    /**
     * Multiply by a factor.
     *
     * @param float $multiplier Factor (0.9 for 10% discount, 1.2 for 20% markup)
     */
    public function multiply(float $multiplier): self
    {
        return new self(
            (int) round($this->amountInCents * $multiplier),
            $this->currency
        );
    }

    /**
     * Divide by a divisor.
     *
     * @param float $divisor Divisor (2 for half, 3 for thirds)
     * @return self Rounded result
     */
    public function divide(float $divisor): self
    {
        if ($divisor === 0.0) {
            throw new \InvalidArgumentException('Cannot divide by zero');
        }

        return new self(
            (int) round($this->amountInCents / $divisor),
            $this->currency
        );
    }

    /**
     * Allocate amount proportionally without losing cents.
     *
     * Example: €10.00 allocated [1, 1, 1] = [€3.34, €3.33, €3.33]
     *
     * @param array<int> $ratios Allocation ratios [1, 1, 1] or [50, 30, 20]
     * @return array<self> Allocated Money amounts
     */
    public function allocate(array $ratios): array
    {
        $total = array_sum($ratios);

        if ($total === 0) {
            throw new \InvalidArgumentException('Sum of ratios must be greater than zero');
        }

        $remainder = $this->amountInCents;
        $results = [];

        foreach ($ratios as $ratio) {
            $amount = (int) floor($this->amountInCents * $ratio / $total);
            $results[] = new self($amount, $this->currency);
            $remainder -= $amount;
        }

        // Distribute remainder (avoid losing cents due to rounding)
        for ($i = 0; $i < $remainder; ++$i) {
            $results[$i] = new self($results[$i]->amountInCents + 1, $this->currency);
        }

        return $results;
    }

    // ===== Comparison Methods =====

    public function isGreaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amountInCents > $other->amountInCents;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amountInCents >= $other->amountInCents;
    }

    public function isLessThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amountInCents < $other->amountInCents;
    }

    public function isLessThanOrEqual(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amountInCents <= $other->amountInCents;
    }

    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }

    public function isZero(): bool
    {
        return $this->amountInCents === 0;
    }

    public function isPositive(): bool
    {
        return $this->amountInCents > 0;
    }

    public function isNegative(): bool
    {
        return $this->amountInCents < 0;
    }

    // ===== Getters =====

    /**
     * Get amount in cents/pence.
     */
    public function getAmountInCents(): int
    {
        return $this->amountInCents;
    }

    /**
     * Get amount as decimal (euros or pounds).
     *
     * @return float Amount in major currency unit (123.45 for 12345 cents)
     */
    public function getAmount(): float
    {
        return $this->amountInCents / 100;
    }

    /**
     * Get amount in euros (alias for getAmount() when currency is EUR).
     */
    public function getAmountEuros(): float
    {
        if ($this->currency !== Currency::EUR) {
            throw new \InvalidArgumentException('Cannot get euros amount for non-EUR currency');
        }

        return $this->getAmount();
    }

    /**
     * Get amount in pounds (alias for getAmount() when currency is GBP).
     */
    public function getAmountPounds(): float
    {
        if ($this->currency !== Currency::GBP) {
            throw new \InvalidArgumentException('Cannot get pounds amount for non-GBP currency');
        }

        return $this->getAmount();
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    // ===== Formatting =====

    /**
     * Format for display with locale-aware formatting.
     *
     * Examples:
     * - €123,45 (fr_FR)
     * - £123.45 (en_GB)
     * - 123,45 € (de_DE)
     */
    public function format(string $locale = 'fr_FR'): string
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency(
            $this->getAmount(),
            $this->currency->value
        );
    }

    public function __toString(): string
    {
        return $this->format();
    }

    // ===== Validation =====

    private function validate(): void
    {
        // Note: Negative amounts ARE allowed (debts, refunds)
        // But we can add validation if business rules require positive-only
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException(
                sprintf(
                    'Cannot perform operation on different currencies: %s vs %s',
                    $this->currency->value,
                    $other->currency->value
                )
            );
        }
    }
}
```

**Actions:**
- Créer `src/Domain/Shared/ValueObject/Money.php` avec stockage en centimes (int)
- Implémenter factory methods pour création depuis différentes unités
- Implémenter opérations arithmétiques immutables (add, subtract, multiply, divide)
- Implémenter méthodes de comparaison (>, <, =)
- Implémenter allocation proportionnelle sans perte de centimes
- Implémenter formatage localisé avec NumberFormatter
- Valider devise cohérente dans les opérations
- Tests couvrant tous les cas de figure

### [DOMAIN] Créer l'exception CurrencyMismatchException (0.5h)

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

/**
 * Exception thrown when trying to operate on Money with different currencies.
 */
final class CurrencyMismatchException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fromCurrencies(Currency $currency1, Currency $currency2): self
    {
        return new self(
            sprintf(
                'Cannot perform operation on different currencies: %s vs %s',
                $currency1->value,
                $currency2->value
            )
        );
    }
}
```

### [DOMAIN] Créer l'exception NegativeAmountException (0.5h)

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

/**
 * Exception thrown when trying to create Money with negative amount (if business rules forbid it).
 */
final class NegativeAmountException extends DomainException
{
    public function __construct(int $amountInCents)
    {
        parent::__construct(
            sprintf(
                'Amount cannot be negative: %d cents',
                $amountInCents
            )
        );
    }
}
```

### [INFRA] Créer Doctrine Custom Type pour Money (2h)

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\Enum\Currency;
use App\Domain\Shared\ValueObject\Money;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;

/**
 * Doctrine Custom Type for Money Value Object.
 *
 * Storage format (JSON):
 * {
 *   "amount": 12345,    // Amount in cents/pence (int)
 *   "currency": "EUR"   // Currency code (EUR, GBP)
 * }
 *
 * Example:
 * - €123.45 → {"amount": 12345, "currency": "EUR"}
 * - £99.99 → {"amount": 9999, "currency": "GBP"}
 */
final class MoneyType extends StringType
{
    public const string NAME = 'money';

    /**
     * Convert from database JSON string to Money Value Object.
     *
     * @param mixed $value JSON string from database
     * @param AbstractPlatform $platform Database platform
     * @return Money|null Money object or null
     * @throws ConversionException if conversion fails
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Money
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', 'string']
            );
        }

        try {
            // Deserialize JSON: {"amount": 12345, "currency": "EUR"}
            $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['amount'], $data['currency'])) {
                throw new \InvalidArgumentException(
                    'Missing amount or currency in JSON'
                );
            }

            if (!is_int($data['amount'])) {
                throw new \InvalidArgumentException(
                    'Amount must be an integer (cents)'
                );
            }

            return Money::fromCents(
                $data['amount'],
                Currency::from($data['currency'])
            );
        } catch (\InvalidArgumentException|\JsonException $e) {
            throw ConversionException::conversionFailed($value, $this->getName(), $e);
        }
    }

    /**
     * Convert from Money Value Object to database JSON string.
     *
     * @param mixed $value Money object
     * @param AbstractPlatform $platform Database platform
     * @return string|null JSON string or null
     * @throws ConversionException if conversion fails
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Money) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', Money::class]
            );
        }

        // Serialize to JSON
        return json_encode([
            'amount' => $value->getAmountInCents(),
            'currency' => $value->getCurrency()->value,
        ], JSON_THROW_ON_ERROR);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Require SQL comment hint for Doctrine migrations.
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

**Actions:**
- Créer `src/Infrastructure/Persistence/Doctrine/Type/MoneyType.php`
- Extends `StringType` from Doctrine DBAL
- Implémenter conversion JSON ↔ Money
- Format JSON: `{"amount": 12345, "currency": "EUR"}`
- Validation structure JSON (amount et currency requis)
- Validation type amount (doit être int, pas float)
- Gestion NULL pour les deux conversions
- ConversionException pour types invalides

### [CONFIG] Enregistrer le Custom Type Doctrine (0.5h)

```yaml
# config/packages/doctrine.yaml

doctrine:
    dbal:
        types:
            # ✅ Email custom type (US-010)
            email: App\Infrastructure\Persistence\Doctrine\Type\EmailType

            # ✅ PhoneNumber custom type (US-011)
            phone_number: App\Infrastructure\Persistence\Doctrine\Type\PhoneNumberType

            # ✅ Money custom type
            money: App\Infrastructure\Persistence\Doctrine\Type\MoneyType

        mapping_types:
            email: string
            phone_number: string
            money: string
```

**Vérification:**
```bash
make console CMD="dbal:types"

# Output attendu:
# money  App\Infrastructure\Persistence\Doctrine\Type\MoneyType
```

### [TEST] Créer tests unitaires Money (2.5h)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\Enum\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\Exception\CurrencyMismatchException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    // ===== Creation Tests =====

    /**
     * @test
     */
    public function it_creates_money_from_cents(): void
    {
        $money = Money::fromCents(12345, Currency::EUR);

        self::assertInstanceOf(Money::class, $money);
        self::assertEquals(12345, $money->getAmountInCents());
        self::assertEquals(123.45, $money->getAmount());
        self::assertEquals(Currency::EUR, $money->getCurrency());
    }

    /**
     * @test
     */
    public function it_creates_money_from_euros(): void
    {
        $money = Money::fromEuros(123.45);

        self::assertEquals(12345, $money->getAmountInCents());
        self::assertEquals(123.45, $money->getAmount());
        self::assertEquals(Currency::EUR, $money->getCurrency());
    }

    /**
     * @test
     */
    public function it_creates_money_from_pounds(): void
    {
        $money = Money::fromPounds(99.99);

        self::assertEquals(9999, $money->getAmountInCents());
        self::assertEquals(99.99, $money->getAmount());
        self::assertEquals(Currency::GBP, $money->getCurrency());
    }

    /**
     * @test
     */
    public function it_creates_zero_money(): void
    {
        $money = Money::zero();

        self::assertTrue($money->isZero());
        self::assertEquals(0, $money->getAmountInCents());
        self::assertEquals(Currency::EUR, $money->getCurrency());
    }

    /**
     * @test
     */
    public function it_handles_rounding_correctly(): void
    {
        // 123.456 should round to 123.46 (12346 cents)
        $money = Money::fromEuros(123.456);

        self::assertEquals(12346, $money->getAmountInCents());
        self::assertEquals(123.46, $money->getAmount());
    }

    // ===== Arithmetic Tests =====

    /**
     * @test
     */
    public function it_adds_two_money_amounts(): void
    {
        $money1 = Money::fromEuros(100.00);
        $money2 = Money::fromEuros(50.00);

        $result = $money1->add($money2);

        self::assertEquals(15000, $result->getAmountInCents());
        self::assertEquals(150.00, $result->getAmount());
    }

    /**
     * @test
     */
    public function it_subtracts_two_money_amounts(): void
    {
        $money1 = Money::fromEuros(100.00);
        $money2 = Money::fromEuros(30.00);

        $result = $money1->subtract($money2);

        self::assertEquals(7000, $result->getAmountInCents());
        self::assertEquals(70.00, $result->getAmount());
    }

    /**
     * @test
     */
    public function it_multiplies_money_by_factor(): void
    {
        $money = Money::fromEuros(100.00);

        // 10% discount
        $result = $money->multiply(0.9);

        self::assertEquals(9000, $result->getAmountInCents());
        self::assertEquals(90.00, $result->getAmount());
    }

    /**
     * @test
     */
    public function it_divides_money_by_divisor(): void
    {
        $money = Money::fromEuros(100.00);

        $result = $money->divide(4);

        self::assertEquals(2500, $result->getAmountInCents());
        self::assertEquals(25.00, $result->getAmount());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_dividing_by_zero(): void
    {
        $money = Money::fromEuros(100.00);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot divide by zero');

        $money->divide(0);
    }

    /**
     * @test
     */
    public function it_allocates_proportionally_without_losing_cents(): void
    {
        // €10.00 split equally in 3 parts
        $money = Money::fromEuros(10.00);

        $allocated = $money->allocate([1, 1, 1]);

        self::assertCount(3, $allocated);

        // €3.34 + €3.33 + €3.33 = €10.00 (no cents lost)
        self::assertEquals(334, $allocated[0]->getAmountInCents());
        self::assertEquals(333, $allocated[1]->getAmountInCents());
        self::assertEquals(333, $allocated[2]->getAmountInCents());

        // Verify total
        $total = $allocated[0]->add($allocated[1])->add($allocated[2]);
        self::assertEquals(1000, $total->getAmountInCents());
    }

    /**
     * @test
     */
    public function it_allocates_proportionally_with_different_ratios(): void
    {
        // €100.00 allocated [50%, 30%, 20%]
        $money = Money::fromEuros(100.00);

        $allocated = $money->allocate([50, 30, 20]);

        self::assertEquals(5000, $allocated[0]->getAmountInCents()); // €50.00
        self::assertEquals(3000, $allocated[1]->getAmountInCents()); // €30.00
        self::assertEquals(2000, $allocated[2]->getAmountInCents()); // €20.00
    }

    // ===== Comparison Tests =====

    /**
     * @test
     */
    public function it_compares_money_amounts(): void
    {
        $money1 = Money::fromEuros(100.00);
        $money2 = Money::fromEuros(50.00);
        $money3 = Money::fromEuros(100.00);

        self::assertTrue($money1->isGreaterThan($money2));
        self::assertFalse($money1->isLessThan($money2));
        self::assertTrue($money1->equals($money3));
        self::assertFalse($money1->equals($money2));
    }

    /**
     * @test
     */
    public function it_checks_zero_positive_negative(): void
    {
        $zero = Money::zero();
        $positive = Money::fromEuros(10.00);
        $negative = Money::fromCents(-500, Currency::EUR);

        self::assertTrue($zero->isZero());
        self::assertFalse($zero->isPositive());
        self::assertFalse($zero->isNegative());

        self::assertFalse($positive->isZero());
        self::assertTrue($positive->isPositive());
        self::assertFalse($positive->isNegative());

        self::assertFalse($negative->isZero());
        self::assertFalse($negative->isPositive());
        self::assertTrue($negative->isNegative());
    }

    // ===== Currency Mismatch Tests =====

    /**
     * @test
     */
    public function it_throws_exception_when_adding_different_currencies(): void
    {
        $euros = Money::fromEuros(100.00);
        $pounds = Money::fromPounds(50.00);

        $this->expectException(CurrencyMismatchException::class);
        $this->expectExceptionMessage('Cannot perform operation on different currencies: EUR vs GBP');

        $euros->add($pounds);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_subtracting_different_currencies(): void
    {
        $euros = Money::fromEuros(100.00);
        $pounds = Money::fromPounds(50.00);

        $this->expectException(CurrencyMismatchException::class);

        $euros->subtract($pounds);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_comparing_different_currencies(): void
    {
        $euros = Money::fromEuros(100.00);
        $pounds = Money::fromPounds(100.00);

        $this->expectException(CurrencyMismatchException::class);

        $euros->isGreaterThan($pounds);
    }

    // ===== Formatting Tests =====

    /**
     * @test
     */
    public function it_formats_euros_for_french_locale(): void
    {
        $money = Money::fromEuros(123.45);

        $formatted = $money->format('fr_FR');

        self::assertStringContainsString('123', $formatted);
        self::assertStringContainsString('45', $formatted);
        self::assertStringContainsString('€', $formatted);
    }

    /**
     * @test
     */
    public function it_formats_pounds_for_english_locale(): void
    {
        $money = Money::fromPounds(99.99);

        $formatted = $money->format('en_GB');

        self::assertStringContainsString('99', $formatted);
        self::assertStringContainsString('99', $formatted);
        self::assertStringContainsString('£', $formatted);
    }

    /**
     * @test
     */
    public function it_converts_to_string(): void
    {
        $money = Money::fromEuros(50.00);

        $string = (string) $money;

        self::assertIsString($string);
        self::assertStringContainsString('50', $string);
    }

    // ===== Precision Tests (Critical for financial calculations) =====

    /**
     * @test
     */
    public function it_avoids_floating_point_precision_issues(): void
    {
        // Classic float problem: 0.1 + 0.2 = 0.30000000000000004
        $money1 = Money::fromEuros(0.1);
        $money2 = Money::fromEuros(0.2);

        $result = $money1->add($money2);

        // ✅ With cents: 10 + 20 = 30 (exact)
        self::assertEquals(30, $result->getAmountInCents());
        self::assertEquals(0.30, $result->getAmount());
    }

    /**
     * @test
     */
    public function it_handles_complex_calculations_precisely(): void
    {
        // Complex calculation: (€100 * 1.2) - (€30 / 2) + €5.50
        $base = Money::fromEuros(100.00);
        $markup = $base->multiply(1.2); // €120.00
        $discount = Money::fromEuros(30.00)->divide(2); // €15.00
        $fee = Money::fromEuros(5.50);

        $result = $markup->subtract($discount)->add($fee);

        // €120 - €15 + €5.50 = €110.50
        self::assertEquals(11050, $result->getAmountInCents());
        self::assertEquals(110.50, $result->getAmount());
    }

    // ===== Data Providers =====

    /**
     * @test
     * @dataProvider arithmeticOperationsProvider
     */
    public function it_performs_arithmetic_operations(
        Money $money1,
        Money $money2,
        string $operation,
        int $expectedCents
    ): void {
        $result = match ($operation) {
            'add' => $money1->add($money2),
            'subtract' => $money1->subtract($money2),
            default => throw new \InvalidArgumentException("Unknown operation: $operation"),
        };

        self::assertEquals($expectedCents, $result->getAmountInCents());
    }

    public static function arithmeticOperationsProvider(): array
    {
        return [
            'add simple' => [
                Money::fromEuros(10.00),
                Money::fromEuros(5.00),
                'add',
                1500, // 15.00 EUR
            ],
            'add decimal' => [
                Money::fromEuros(10.50),
                Money::fromEuros(5.25),
                'add',
                1575, // 15.75 EUR
            ],
            'subtract simple' => [
                Money::fromEuros(100.00),
                Money::fromEuros(30.00),
                'subtract',
                7000, // 70.00 EUR
            ],
            'subtract with negative result' => [
                Money::fromEuros(50.00),
                Money::fromEuros(75.00),
                'subtract',
                -2500, // -25.00 EUR
            ],
        ];
    }

    /**
     * @test
     * @dataProvider comparisonProvider
     */
    public function it_compares_money_amounts_correctly(
        Money $money1,
        Money $money2,
        bool $expectedGreater,
        bool $expectedLess,
        bool $expectedEqual
    ): void {
        self::assertEquals($expectedGreater, $money1->isGreaterThan($money2));
        self::assertEquals($expectedLess, $money1->isLessThan($money2));
        self::assertEquals($expectedEqual, $money1->equals($money2));
    }

    public static function comparisonProvider(): array
    {
        return [
            'greater than' => [
                Money::fromEuros(100.00),
                Money::fromEuros(50.00),
                true,  // isGreaterThan
                false, // isLessThan
                false, // equals
            ],
            'less than' => [
                Money::fromEuros(50.00),
                Money::fromEuros(100.00),
                false, // isGreaterThan
                true,  // isLessThan
                false, // equals
            ],
            'equal' => [
                Money::fromEuros(75.00),
                Money::fromEuros(75.00),
                false, // isGreaterThan
                false, // isLessThan
                true,  // equals
            ],
        ];
    }
}
```

**Actions:**
- Créer `tests/Unit/Domain/Shared/ValueObject/MoneyTest.php`
- Tests de création (fromCents, fromEuros, fromPounds, zero)
- Tests de précision (éviter float 0.1 + 0.2 = 0.30000000000000004)
- Tests arithmétiques (add, subtract, multiply, divide)
- Tests d'allocation proportionnelle (allocate)
- Tests de comparaison (>, <, =, isZero, isPositive, isNegative)
- Tests de gestion devise (CurrencyMismatchException)
- Tests de formatage (format, __toString)
- Tests de rounding (123.456 → 123.46)
- Tests de calculs complexes (combinaisons d'opérations)
- Data providers pour opérations arithmétiques et comparaisons
- Couverture ≥ 90%

### [TEST] Créer tests d'intégration Doctrine Type (1.5h)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\Enum\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Infrastructure\Persistence\Doctrine\Type\MoneyType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

final class MoneyTypeTest extends TestCase
{
    private MoneyType $type;
    private PostgreSQLPlatform $platform;

    protected function setUp(): void
    {
        if (!Type::hasType(MoneyType::NAME)) {
            Type::addType(MoneyType::NAME, MoneyType::class);
        }

        $this->type = Type::getType(MoneyType::NAME);
        $this->platform = new PostgreSQLPlatform();
    }

    /**
     * @test
     */
    public function it_converts_null_to_null(): void
    {
        $phpValue = $this->type->convertToPHPValue(null, $this->platform);
        $dbValue = $this->type->convertToDatabaseValue(null, $this->platform);

        self::assertNull($phpValue);
        self::assertNull($dbValue);
    }

    /**
     * @test
     */
    public function it_converts_json_string_to_money_object(): void
    {
        $databaseValue = '{"amount":12345,"currency":"EUR"}';

        $phpValue = $this->type->convertToPHPValue($databaseValue, $this->platform);

        self::assertInstanceOf(Money::class, $phpValue);
        self::assertEquals(12345, $phpValue->getAmountInCents());
        self::assertEquals(Currency::EUR, $phpValue->getCurrency());
    }

    /**
     * @test
     */
    public function it_converts_money_object_to_json_string(): void
    {
        $money = Money::fromEuros(123.45);

        $databaseValue = $this->type->convertToDatabaseValue($money, $this->platform);

        self::assertIsString($databaseValue);

        $decoded = json_decode($databaseValue, true);
        self::assertEquals(12345, $decoded['amount']);
        self::assertEquals('EUR', $decoded['currency']);
    }

    /**
     * @test
     */
    public function it_converts_pounds_correctly(): void
    {
        $money = Money::fromPounds(99.99);

        $databaseValue = $this->type->convertToDatabaseValue($money, $this->platform);
        $phpValue = $this->type->convertToPHPValue($databaseValue, $this->platform);

        self::assertEquals(9999, $phpValue->getAmountInCents());
        self::assertEquals(Currency::GBP, $phpValue->getCurrency());
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_type_to_database(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue('invalid', $this->platform);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_malformed_json(): void
    {
        $malformedJson = '{"amount":12345}'; // Missing currency

        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue($malformedJson, $this->platform);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_non_integer_amount(): void
    {
        $invalidJson = '{"amount":123.45,"currency":"EUR"}'; // Amount as float, not int

        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue($invalidJson, $this->platform);
    }

    /**
     * @test
     */
    public function it_returns_correct_type_name(): void
    {
        self::assertEquals('money', $this->type->getName());
    }

    /**
     * @test
     */
    public function it_requires_sql_comment_hint(): void
    {
        self::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }
}
```

**Actions:**
- Créer `tests/Integration/Infrastructure/Persistence/Doctrine/Type/MoneyTypeTest.php`
- Tests de conversion JSON → Money
- Tests de conversion Money → JSON
- Tests de gestion NULL
- Tests de validation structure JSON (amount et currency requis)
- Tests de validation type amount (int requis, pas float)
- Tests des deux devises (EUR, GBP)
- Tests ConversionException pour types invalides
- Tests de nom de type et SQL comment hint

### [DOC] Documenter Value Object Money (1.5h)

Créer `.claude/examples/value-object-money.md` :

```markdown
# Value Object Money - Gestion des Montants Financiers

## Caractéristiques

Le Value Object `Money` représente un montant financier avec devise.

### Points clés

- ✅ **Immutable** - `final readonly class`, aucun setter
- ✅ **Type-safe** - Impossible de passer un float où Money est attendu
- ✅ **Précis** - Stockage en centimes (int) évite les erreurs de float
- ✅ **Multi-devises** - Support EUR et GBP via Currency enum
- ✅ **Validé** - Validation devise cohérente dans opérations
- ✅ **Comparable** - Méthodes de comparaison (>, <, =)
- ✅ **Formaté** - Formatage localisé pour affichage

## Création

### Depuis centimes (recommandé pour précision)

```php
<?php

use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\Enum\Currency;

// Création depuis centimes (int - précision garantie)
$money = Money::fromCents(12345, Currency::EUR); // €123.45
$money = Money::fromCents(9999, Currency::GBP);  // £99.99
$money = Money::zero(); // €0.00 (default EUR)
```

### Depuis euros/pounds (float converti en centimes)

```php
<?php

// Création depuis euros (float automatiquement converti)
$money = Money::fromEuros(123.45);  // 12345 cents
$money = Money::fromPounds(99.99);  // 9999 pence

// ✅ Arrondi automatique
$money = Money::fromEuros(123.456); // 12346 cents (arrondi à 123.46)
```

## Méthodes

### Accesseurs

```php
<?php

$money = Money::fromEuros(123.45);

$money->getAmountInCents();  // 12345 (int)
$money->getAmount();         // 123.45 (float)
$money->getAmountEuros();    // 123.45 (throws if not EUR)
$money->getCurrency();       // Currency::EUR
```

### Opérations arithmétiques (immutables)

```php
<?php

$price = Money::fromEuros(100.00);
$discount = Money::fromEuros(10.00);
$shipping = Money::fromEuros(5.50);

// Addition
$total = $price->add($shipping); // €105.50

// Soustraction
$afterDiscount = $price->subtract($discount); // €90.00

// Multiplication (remise de 10%)
$withDiscount = $price->multiply(0.9); // €90.00

// Division (partage en 4)
$quarter = $price->divide(4); // €25.00

// ✅ Les objets originaux ne sont PAS modifiés (immutabilité)
echo $price->getAmount(); // Toujours €100.00
```

### Allocation proportionnelle (sans perte de centimes)

```php
<?php

// Partage €10.00 en 3 parts égales
$money = Money::fromEuros(10.00);
$allocated = $money->allocate([1, 1, 1]);

// $allocated[0] = €3.34
// $allocated[1] = €3.33
// $allocated[2] = €3.33
// Total = €10.00 (aucun centime perdu)

// Allocation avec ratios différents (50%, 30%, 20%)
$money = Money::fromEuros(100.00);
$allocated = $money->allocate([50, 30, 20]);

// $allocated[0] = €50.00
// $allocated[1] = €30.00
// $allocated[2] = €20.00
```

### Comparaisons

```php
<?php

$price1 = Money::fromEuros(100.00);
$price2 = Money::fromEuros(50.00);
$price3 = Money::fromEuros(100.00);

$price1->isGreaterThan($price2);    // true
$price1->isLessThan($price2);       // false
$price1->equals($price3);           // true

$price1->isZero();                  // false
$price1->isPositive();              // true
$price1->isNegative();              // false

$zero = Money::zero();
$zero->isZero();                    // true
```

### Formatage localisé

```php
<?php

$money = Money::fromEuros(123.45);

$money->format('fr_FR'); // "123,45 €"
$money->format('en_GB'); // "€123.45"
$money->format('de_DE'); // "123,45 €"

(string) $money;         // "123,45 €" (fr_FR par défaut)
```

## Utilisation dans les entités

### Entity avec Money

```php
<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Entity;

use App\Domain\Shared\ValueObject\Money;

final class Reservation
{
    private ReservationId $id;

    // ✅ Money Value Object (pas float!)
    private Money $montantTotal;

    private function __construct(
        ReservationId $id,
        Money $montantTotal
    ) {
        $this->id = $id;
        $this->montantTotal = $montantTotal;
    }

    public static function create(
        ReservationId $id,
        Money $montantTotal
    ): self {
        return new self($id, $montantTotal);
    }

    public function addDiscount(Money $discount): void
    {
        // ✅ Opération immutable (crée nouveau Money)
        $this->montantTotal = $this->montantTotal->subtract($discount);
    }

    public function applyTaxes(float $taxRate): void
    {
        // ✅ Calcul TVA précis (pas de float!)
        $taxes = $this->montantTotal->multiply($taxRate);
        $this->montantTotal = $this->montantTotal->add($taxes);
    }

    public function getMontantTotal(): Money
    {
        return $this->montantTotal;
    }
}
```

### Doctrine XML Mapping

```xml
<!-- Infrastructure/Persistence/Doctrine/Mapping/Reservation.orm.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Domain\Reservation\Entity\Reservation" table="reservation">
        <id name="id" type="reservation_id">
            <generator strategy="NONE"/>
        </id>

        <!-- ✅ Money stocké en JSON: {"amount": 12345, "currency": "EUR"} -->
        <field name="montantTotal" type="money" column="montant_total" nullable="false"/>

        <field name="createdAt" type="datetime_immutable" nullable="false"/>
        <field name="updatedAt" type="datetime_immutable" nullable="false"/>
    </entity>
</doctrine-mapping>
```

## Problèmes de précision évités

### Float vs Int (Cents)

```php
<?php

// ❌ PROBLÈME: Float precision
$total = 0.0;
$total += 0.1; // 0.1
$total += 0.2; // 0.30000000000000004 ❌
echo $total;   // 0.30000000000000004

// ✅ SOLUTION: Int cents
$total = 0;
$total += 10; // 10 cents
$total += 20; // 30 cents
echo $total;  // 30 ✅
```

### Calculs financiers complexes

```php
<?php

// ❌ PROBLÈME: Float compound errors
$price = 99.95;
$quantity = 3;
$discount = 0.1; // 10%
$tax = 0.2;      // 20%

$subtotal = $price * $quantity;                    // 299.85
$afterDiscount = $subtotal * (1 - $discount);      // 269.865
$total = $afterDiscount * (1 + $tax);              // 323.838
// Arrondi final: 323.84 ou 323.83 ?? ❌

// ✅ SOLUTION: Money Value Object
$price = Money::fromEuros(99.95);
$quantity = 3;

$subtotal = $price->multiply($quantity);           // €299.85 exact
$afterDiscount = $subtotal->multiply(0.9);         // €269.87 arrondi
$total = $afterDiscount->multiply(1.2);            // €323.84 précis ✅
```

## JSON Storage (Doctrine)

### Format stocké en base

```sql
-- Table reservation
CREATE TABLE reservation (
    id UUID PRIMARY KEY,
    montant_total TEXT NOT NULL,  -- JSON: {"amount": 12345, "currency": "EUR"}
    created_at TIMESTAMP NOT NULL
);

-- Exemple de données
INSERT INTO reservation (id, montant_total, created_at)
VALUES (
    'a1b2c3d4-...',
    '{"amount":12345,"currency":"EUR"}',
    NOW()
);
```

### Requêtes SQL

```sql
-- Filtrer par montant (extraction JSON)
SELECT * FROM reservation
WHERE (montant_total->>'amount')::int > 10000; -- > €100.00

-- Filtrer par devise
SELECT * FROM reservation
WHERE montant_total->>'currency' = 'EUR';

-- Calculer somme
SELECT SUM((montant_total->>'amount')::int) AS total_cents
FROM reservation
WHERE montant_total->>'currency' = 'EUR';
```

## Exemple complet d'usage

### Controller → Command → Handler → Entity → Persistence

```php
<?php

// 1. PRESENTATION - Controller
namespace App\Presentation\Controller\Api;

use App\Application\Reservation\Command\CreateReservationCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class ReservationController
{
    #[Route('/api/reservations', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // ✅ Money créé dans le controller
        $command = new CreateReservationCommand(
            clientEmail: $data['email'],
            montantTotal: Money::fromEuros($data['montant']), // ✅ Type Money
        );

        $reservationId = $this->commandBus->dispatch($command);

        return new JsonResponse(['id' => (string) $reservationId], 201);
    }
}

// 2. APPLICATION - Command
namespace App\Application\Reservation\Command;

use App\Domain\Shared\ValueObject\Money;

final readonly class CreateReservationCommand
{
    public function __construct(
        public string $clientEmail,
        public Money $montantTotal, // ✅ Type Money (pas float!)
    ) {}
}

// 3. APPLICATION - Handler
namespace App\Application\Reservation\Handler;

final readonly class CreateReservationHandler
{
    public function __invoke(CreateReservationCommand $command): ReservationId
    {
        $reservation = Reservation::create(
            ReservationId::generate(),
            Email::fromString($command->clientEmail),
            $command->montantTotal // ✅ Money déjà validé
        );

        // Calculs métier avec Money
        $discount = $command->montantTotal->multiply(0.1); // 10%
        $afterDiscount = $command->montantTotal->subtract($discount);

        $reservation->setMontantTotal($afterDiscount);

        $this->repository->save($reservation);

        return $reservation->getId();
    }
}

// 4. DOMAIN - Entity
namespace App\Domain\Reservation\Entity;

final class Reservation
{
    private Money $montantTotal;

    public function setMontantTotal(Money $montant): void
    {
        // ✅ Type-safe: seul Money accepté
        $this->montantTotal = $montant;
    }

    public function getMontantTotal(): Money
    {
        return $this->montantTotal;
    }
}

// 5. INFRASTRUCTURE - Persistence automatique
// Doctrine Custom Type convertit automatiquement:
// Money → JSON: {"amount": 12345, "currency": "EUR"}
// JSON → Money lors du findById()
```

## Avantages

### Avant (float primitif)

```php
<?php

// ❌ Problèmes multiples

class Order
{
    private float $total;           // Pas de devise
    private string $currency;       // Séparé, incohérence possible

    public function calculateTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->price * $item->quantity; // Précision perdue
        }
        return $total; // 299.99999999999994 ❌
    }

    public function applyDiscount(float $percent): void
    {
        $this->total = $this->total * (1 - $percent); // Perte de précision
    }
}
```

### Après (Money Value Object)

```php
<?php

// ✅ Tous les avantages

final class Order
{
    private Money $total; // Devise incluse

    public function calculateTotal(): Money
    {
        $total = Money::zero();
        foreach ($this->items as $item) {
            $itemTotal = $item->getPrice()->multiply($item->getQuantity());
            $total = $total->add($itemTotal); // Précision conservée
        }
        return $total; // €300.00 exact ✅
    }

    public function applyDiscount(float $percent): void
    {
        $this->total = $this->total->multiply(1 - $percent); // Arrondi correct
    }
}
```

### Comparaison

| Aspect | Float | Money VO |
|--------|-------|----------|
| **Précision** | ❌ Erreurs float | ✅ Exact (int cents) |
| **Type safety** | ❌ float accepté partout | ✅ Seulement Money |
| **Devise** | ❌ Séparée, incohérente | ✅ Incluse, cohérente |
| **Validation** | ❌ Disperse | ✅ Centralisée |
| **Comparaison** | ❌ `$a == $b` (float) | ✅ `equals()` |
| **Immutabilité** | ❌ Mutable | ✅ Immutable |
| **Testabilité** | ⚠️ Difficile | ✅ Facile |

## Anti-patterns à éviter

### ❌ Money avec setter (brise immutabilité)

```php
<?php

// INTERDIT
class Money
{
    public function setAmount(int $amount): void // ❌
    {
        $this->amount = $amount;
    }
}
```

### ❌ Validation devise dans l'entity (duplication)

```php
<?php

// INTERDIT
class Reservation
{
    public function setMontantTotal(Money $montant): void
    {
        // ❌ Validation déjà dans Money.ensureSameCurrency()
        if ($montant->getCurrency() !== $this->currency) {
            throw new Exception();
        }
    }
}
```

### ❌ Money avec identité (pas un VO)

```php
<?php

// INTERDIT
class Money
{
    private int $id; // ❌ Les VOs n'ont pas d'identité

    public function equals(Money $other): bool
    {
        return $this->id === $other->id; // ❌ Égalité par ID, pas valeur
    }
}
```

### ❌ Opérations arithmétiques mutables

```php
<?php

// INTERDIT
class Money
{
    public function add(Money $other): void // ❌ void (mutable)
    {
        $this->amount += $other->amount;
    }
}

// ✅ OBLIGATOIRE: Retourner nouvelle instance
public function add(Money $other): self // ✅ self (immutable)
{
    return new self($this->amount + $other->amount);
}
```

## Stratégie de migration

### Phase 1: Créer le Value Object

```bash
make console CMD="make:value-object Money"
# Implémenter selon template ci-dessus
make test-unit
make phpstan
```

### Phase 2: Créer le Doctrine Custom Type

```bash
# Créer MoneyType.php
# Enregistrer dans doctrine.yaml
make console CMD="dbal:types" # Vérifier
```

### Phase 3: Migrer les entités

```php
<?php

// Avant
class Reservation
{
    private float $montantTotal;
}

// Après
class Reservation
{
    private Money $montantTotal; // ✅ Changer type

    // Update constructor/setters
}
```

### Phase 4: Migration base de données

```bash
# Créer migration pour conversion float → JSON
make migration-diff

# Migration générée:
# ALTER TABLE reservation
# ALTER COLUMN montant_total TYPE TEXT
# USING json_build_object('amount', (montant_total * 100)::int, 'currency', 'EUR')::text;

make db-migrate
```

## Cas d'usage métier

### Calcul prix réservation avec remises

```php
<?php

namespace App\Domain\Reservation\Service;

final readonly class ReservationPricingService
{
    public function calculateTotalPrice(Reservation $reservation): Money
    {
        $basePrice = $reservation->getSejour()->getPrixBase();

        // Prix par participant
        $participantsPrice = Money::zero();
        foreach ($reservation->getParticipants() as $participant) {
            if ($participant->isEnfant()) {
                // 50% pour enfants
                $participantsPrice = $participantsPrice->add(
                    $basePrice->multiply(0.5)
                );
            } elseif ($participant->isBebe()) {
                // Gratuit pour bébés
                continue;
            } else {
                // Prix plein pour adultes
                $participantsPrice = $participantsPrice->add($basePrice);
            }
        }

        // Remise famille nombreuse (4+ participants)
        if (count($reservation->getParticipants()) >= 4) {
            $participantsPrice = $participantsPrice->multiply(0.9); // -10%
        }

        return $participantsPrice;
    }
}
```

### Répartition paiement entre participants

```php
<?php

$totalPrice = Money::fromEuros(300.00);
$participants = 3;

// Répartition égale sans perte de centimes
$shares = $totalPrice->allocate(array_fill(0, $participants, 1));

// $shares[0] = €100.00
// $shares[1] = €100.00
// $shares[2] = €100.00
// Total = €300.00 (exact)
```

## Alternatives

### Bibliothèque externe: moneyphp/money

Si besoin de fonctionnalités avancées (conversion devises en temps réel, historique taux, etc.), considérer :

```bash
composer require moneyphp/money
```

**Pour Atoll Tourisme:** Notre implémentation Money suffit (pas besoin conversion temps réel, seulement EUR/GBP).

## Références

- **Pattern Money** - Martin Fowler: https://martinfowler.com/eaaCatalog/money.html
- **IEEE 754 Float** - Problèmes de précision: https://floating-point-gui.de/
- **Doctrine Custom Types** - https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html
- **moneyphp/money** - https://github.com/moneyphp/money

## Checklist Value Object Money

- [ ] Classe `final readonly`
- [ ] Stockage en centimes (int)
- [ ] Factory methods (fromCents, fromEuros, fromPounds, zero)
- [ ] Opérations arithmétiques immutables (add, subtract, multiply, divide)
- [ ] Allocation proportionnelle (allocate)
- [ ] Comparaisons (>, <, =, isZero, isPositive, isNegative)
- [ ] Formatage localisé (format, __toString)
- [ ] Validation devise cohérente (ensureSameCurrency)
- [ ] Aucun setter (immutabilité)
- [ ] Tests unitaires ≥ 90% coverage
- [ ] Tests de précision (éviter float errors)
- [ ] Doctrine Custom Type avec JSON storage
- [ ] Documentation complète avec exemples financiers
