# US-011: Créer Value Object PhoneNumber avec formats internationaux

**EPIC:** [EPIC-002](../epics/EPIC-002-value-objects.md) - Implémentation des Value Objects
**Priorité:** 🔴 CRITIQUE
**Points:** 3
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer un Value Object PhoneNumber avec validation des formats internationaux
**Afin de** centraliser la validation des numéros de téléphone pour les 7 pays supportés (FR, EN, DE, ES, IT, NL, BE)

---

## Critères d'acceptation

### GIVEN: Le projet nécessite la validation de numéros de téléphone pour 7 pays

**WHEN:** Je crée le Value Object PhoneNumber

**THEN:**
- [ ] Value Object `src/Domain/Shared/ValueObject/PhoneNumber.php` créé
- [ ] Classe `final readonly` (immutabilité)
- [ ] Validation pour 7 pays: FR, EN, DE, ES, IT, NL, BE
- [ ] Factory method `fromString()` avec détection automatique du pays
- [ ] Factory method `fromCountryAndNumber()` pour format explicite
- [ ] Méthode `getValue()` retourne le numéro normalisé
- [ ] Méthode `getCountry()` retourne le pays
- [ ] Méthode `getFormatted()` retourne le numéro formaté selon le pays
- [ ] Méthode `equals()` pour comparaison par valeur
- [ ] Méthode `__toString()` retourne le format international
- [ ] Aucun setter (immutabilité garantie)
- [ ] Enum `Country` créé avec les 7 pays supportés
- [ ] Validation fail-fast dans le constructeur

### GIVEN: Le Value Object PhoneNumber existe

**WHEN:** J'exécute PHPStan niveau max sur src/Domain/Shared/ValueObject/

**THEN:**
- [ ] Aucune erreur PHPStan
- [ ] Type `PhoneNumber` strict (pas de `string` primitif)
- [ ] Aucune dépendance externe (pure PHP)
- [ ] Immutabilité vérifiée (`readonly`)

### GIVEN: Le Value Object PhoneNumber existe

**WHEN:** J'exécute les tests unitaires

**THEN:**
- [ ] Tests passent pour les 7 pays (FR, EN, DE, ES, IT, NL, BE)
- [ ] Couverture code ≥ 90%
- [ ] Tests s'exécutent en moins de 100ms
- [ ] Tests de validation pour chaque pays
- [ ] Tests de formatage pour chaque pays
- [ ] Tests de normalisation (suppression espaces, tirets, parenthèses)
- [ ] Tests d'exceptions pour formats invalides
- [ ] Data providers avec formats valides/invalides par pays

---

## Tâches techniques

### [DOMAIN] Créer Value Object PhoneNumber (2h)

**Fichier:** `src/Domain/Shared/ValueObject/PhoneNumber.php`

**Code complet:**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use App\Domain\Shared\Enum\Country;

/**
 * Phone Number value object with international validation.
 *
 * Supports 7 countries: FR, EN, DE, ES, IT, NL, BE
 * Stores normalized format, provides country-specific formatting.
 *
 * Immutable, validated on creation (fail-fast).
 */
final readonly class PhoneNumber
{
    /**
     * Validation patterns per country.
     *
     * Patterns validate normalized numbers (digits only, with optional + prefix).
     */
    private const array PATTERNS = [
        Country::FR->value => '/^\+33[1-9]\d{8}$/',           // +33612345678
        Country::EN->value => '/^\+44[1-9]\d{9,10}$/',        // +441234567890
        Country::DE->value => '/^\+49[1-9]\d{9,10}$/',        // +491234567890
        Country::ES->value => '/^\+34[6-9]\d{8}$/',           // +34612345678
        Country::IT->value => '/^\+39\d{9,10}$/',             // +39123456789
        Country::NL->value => '/^\+31[1-9]\d{8}$/',           // +31612345678
        Country::BE->value => '/^\+32[1-9]\d{7,8}$/',         // +32123456789
    ];

    /**
     * Display formats per country.
     */
    private const array FORMATS = [
        Country::FR->value => '%s %s %s %s %s',               // +33 6 12 34 56 78
        Country::EN->value => '%s %s %s %s',                  // +44 1234 567890
        Country::DE->value => '%s %s %s',                     // +49 123 4567890
        Country::ES->value => '%s %s %s %s',                  // +34 612 34 56 78
        Country::IT->value => '%s %s %s',                     // +39 123 456789
        Country::NL->value => '%s %s %s %s %s',               // +31 6 12 34 56 78
        Country::BE->value => '%s %s %s %s',                  // +32 123 45 67 89
    ];

    private function __construct(
        private string $value,
        private Country $country,
    ) {
        $this->validate();
    }

    /**
     * Creates PhoneNumber from string with automatic country detection.
     *
     * @param string $value Phone number (international format: +33612345678)
     *
     * @throws \InvalidArgumentException if format is invalid or country not detected
     */
    public static function fromString(string $value): self
    {
        $normalized = self::normalize($value);
        $country = self::detectCountry($normalized);

        return new self($normalized, $country);
    }

    /**
     * Creates PhoneNumber with explicit country.
     *
     * @param Country $country Country enum
     * @param string $number Phone number (with or without + prefix)
     *
     * @throws \InvalidArgumentException if format is invalid for the country
     */
    public static function fromCountryAndNumber(Country $country, string $number): self
    {
        $normalized = self::normalize($number);

        // Ensure country prefix
        if (!str_starts_with($normalized, '+')) {
            $normalized = $country->getPhonePrefix() . $normalized;
        }

        return new self($normalized, $country);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    /**
     * Returns formatted phone number for display.
     *
     * Examples:
     * - FR: +33 6 12 34 56 78
     * - EN: +44 1234 567890
     * - DE: +49 123 4567890
     */
    public function getFormatted(): string
    {
        $digits = str_split(ltrim($this->value, '+'));

        return match ($this->country) {
            Country::FR => sprintf(
                '+%s%s %s %s %s %s %s',
                $digits[0], $digits[1],
                $digits[2],
                $digits[3] . $digits[4],
                $digits[5] . $digits[6],
                $digits[7] . $digits[8],
                $digits[9] . $digits[10]
            ),
            Country::EN => sprintf(
                '+%s%s %s',
                $digits[0] . $digits[1],
                implode('', array_slice($digits, 2, 4)),
                implode('', array_slice($digits, 6))
            ),
            Country::DE => sprintf(
                '+%s%s %s %s',
                $digits[0] . $digits[1],
                implode('', array_slice($digits, 2, 3)),
                implode('', array_slice($digits, 5))
            ),
            default => $this->value,
        };
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value
            && $this->country === $other->country;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Normalizes phone number: removes spaces, dashes, parentheses.
     *
     * Examples:
     * - "06 12 34 56 78" → "0612345678"
     * - "+33 (0)6 12-34-56-78" → "+33612345678"
     * - "+33.6.12.34.56.78" → "+33612345678"
     */
    private static function normalize(string $value): string
    {
        // Remove spaces, dashes, parentheses, dots
        $normalized = preg_replace('/[\s\-\(\)\.]/', '', $value);

        // Remove (0) in French format: +33(0)6 → +336
        $normalized = str_replace('(0)', '', $normalized);

        return $normalized;
    }

    /**
     * Detects country from phone number prefix.
     *
     * @throws \InvalidArgumentException if country cannot be detected
     */
    private static function detectCountry(string $normalizedNumber): Country
    {
        foreach (Country::cases() as $country) {
            if (str_starts_with($normalizedNumber, $country->getPhonePrefix())) {
                return $country;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Cannot detect country from phone number: %s', $normalizedNumber)
        );
    }

    /**
     * Validates phone number format for the country.
     *
     * @throws \InvalidArgumentException if format is invalid
     */
    private function validate(): void
    {
        $pattern = self::PATTERNS[$this->country->value] ?? null;

        if ($pattern === null) {
            throw new \InvalidArgumentException(
                sprintf('No validation pattern for country: %s', $this->country->value)
            );
        }

        if (preg_match($pattern, $this->value) !== 1) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid phone number for country %s: %s (expected format: %s)',
                    $this->country->value,
                    $this->value,
                    $this->getExpectedFormat()
                )
            );
        }
    }

    private function getExpectedFormat(): string
    {
        return match ($this->country) {
            Country::FR => '+33612345678 (mobile) or +33123456789 (fixed)',
            Country::EN => '+441234567890',
            Country::DE => '+491234567890',
            Country::ES => '+34612345678',
            Country::IT => '+39123456789',
            Country::NL => '+31612345678',
            Country::BE => '+32123456789',
        };
    }
}
```

---

### [DOMAIN] Créer Enum Country (1h)

**Fichier:** `src/Domain/Shared/Enum/Country.php`

**Code complet:**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enum;

/**
 * Supported countries for Atoll Tourisme.
 *
 * Each country has:
 * - ISO code (FR, EN, DE, etc.)
 * - Phone prefix (+33, +44, etc.)
 * - VAT rate
 * - Currency
 */
enum Country: string
{
    case FR = 'FR'; // France
    case EN = 'EN'; // England
    case DE = 'DE'; // Germany (Allemagne)
    case ES = 'ES'; // Spain (Espagne)
    case IT = 'IT'; // Italy (Italie)
    case NL = 'NL'; // Netherlands (Pays-Bas)
    case BE = 'BE'; // Belgium (Belgique)

    /**
     * Returns phone prefix for the country.
     */
    public function getPhonePrefix(): string
    {
        return match ($this) {
            self::FR => '+33',
            self::EN => '+44',
            self::DE => '+49',
            self::ES => '+34',
            self::IT => '+39',
            self::NL => '+31',
            self::BE => '+32',
        };
    }

    /**
     * Returns VAT rate for the country.
     */
    public function getVATRate(): float
    {
        return match ($this) {
            self::FR => 0.20,
            self::EN => 0.20,
            self::DE => 0.19,
            self::ES => 0.21,
            self::IT => 0.22,
            self::NL => 0.21,
            self::BE => 0.21,
        };
    }

    /**
     * Returns currency for the country.
     */
    public function getCurrency(): Currency
    {
        return match ($this) {
            self::EN => Currency::GBP,
            default => Currency::EUR,
        };
    }

    /**
     * Returns locale for the country.
     */
    public function getLocale(): string
    {
        return match ($this) {
            self::FR => 'fr_FR',
            self::EN => 'en_GB',
            self::DE => 'de_DE',
            self::ES => 'es_ES',
            self::IT => 'it_IT',
            self::NL => 'nl_NL',
            self::BE => 'fr_BE',
        };
    }

    /**
     * Returns country name in French.
     */
    public function getNameFr(): string
    {
        return match ($this) {
            self::FR => 'France',
            self::EN => 'Angleterre',
            self::DE => 'Allemagne',
            self::ES => 'Espagne',
            self::IT => 'Italie',
            self::NL => 'Pays-Bas',
            self::BE => 'Belgique',
        };
    }
}
```

**Fichier:** `src/Domain/Shared/Enum/Currency.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enum;

/**
 * Supported currencies.
 */
enum Currency: string
{
    case EUR = 'EUR'; // Euro
    case GBP = 'GBP'; // British Pound
}
```

---

### [INFRA] Créer Doctrine Custom Type pour PhoneNumber (1.5h)

**Fichier:** `src/Infrastructure/Persistence/Doctrine/Type/PhoneNumberType.php`

**Code complet:**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\Enum\Country;
use App\Domain\Shared\ValueObject\PhoneNumber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;

/**
 * Doctrine Custom Type for PhoneNumber Value Object.
 *
 * Stores phone number as JSON: {"number": "+33612345678", "country": "FR"}
 */
final class PhoneNumberType extends StringType
{
    public const string NAME = 'phone_number';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?PhoneNumber
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
            $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['number'], $data['country'])) {
                throw new \InvalidArgumentException('Missing number or country in JSON');
            }

            return PhoneNumber::fromCountryAndNumber(
                Country::from($data['country']),
                $data['number']
            );
        } catch (\InvalidArgumentException|\JsonException $e) {
            throw ConversionException::conversionFailed($value, $this->getName(), $e);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof PhoneNumber) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', PhoneNumber::class]
            );
        }

        return json_encode([
            'number' => $value->getValue(),
            'country' => $value->getCountry()->value,
        ], JSON_THROW_ON_ERROR);
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

**Pourquoi JSON ?**
- Stocke le numéro ET le pays (nécessaire pour formatage)
- Évite de créer une table séparée
- Facilite les migrations (VARCHAR simple)

---

### [CONFIG] Enregistrer le Custom Type Doctrine (0.5h)

**Fichier:** `config/packages/doctrine.yaml`

```yaml
doctrine:
    dbal:
        types:
            # ✅ Email custom type (US-010)
            email: App\Infrastructure\Persistence\Doctrine\Type\EmailType

            # ✅ PhoneNumber custom type
            phone_number: App\Infrastructure\Persistence\Doctrine\Type\PhoneNumberType

        mapping_types:
            email: string
            phone_number: string
```

**Vérification:**

```bash
# Lister les types Doctrine
make console CMD="dbal:types"

# Output attendu:
# email            App\Infrastructure\Persistence\Doctrine\Type\EmailType
# phone_number     App\Infrastructure\Persistence\Doctrine\Type\PhoneNumberType
```

---

### [TEST] Créer tests unitaires PhoneNumber (2h)

**Fichier:** `tests/Unit/Domain/Shared/ValueObject/PhoneNumberTest.php`

**Code complet:**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\Enum\Country;
use App\Domain\Shared\ValueObject\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    /**
     * @test
     * @dataProvider validPhoneNumberProvider
     */
    public function it_creates_phone_number_from_valid_string(
        string $input,
        Country $expectedCountry,
        string $expectedNormalized
    ): void {
        $phoneNumber = PhoneNumber::fromString($input);

        self::assertInstanceOf(PhoneNumber::class, $phoneNumber);
        self::assertEquals($expectedCountry, $phoneNumber->getCountry());
        self::assertEquals($expectedNormalized, $phoneNumber->getValue());
    }

    /**
     * @test
     */
    public function it_creates_phone_number_with_explicit_country(): void
    {
        $phoneNumber = PhoneNumber::fromCountryAndNumber(Country::FR, '612345678');

        self::assertEquals(Country::FR, $phoneNumber->getCountry());
        self::assertEquals('+33612345678', $phoneNumber->getValue());
    }

    /**
     * @test
     */
    public function it_normalizes_phone_number_removing_spaces_and_dashes(): void
    {
        $input = '+33 6 12-34-56-78';
        $phoneNumber = PhoneNumber::fromString($input);

        self::assertEquals('+33612345678', $phoneNumber->getValue());
    }

    /**
     * @test
     */
    public function it_removes_parentheses_from_french_format(): void
    {
        $input = '+33 (0)6 12 34 56 78';
        $phoneNumber = PhoneNumber::fromString($input);

        self::assertEquals('+33612345678', $phoneNumber->getValue());
    }

    /**
     * @test
     * @dataProvider formattedPhoneNumberProvider
     */
    public function it_formats_phone_number_for_display(
        string $input,
        string $expectedFormatted
    ): void {
        $phoneNumber = PhoneNumber::fromString($input);
        $formatted = $phoneNumber->getFormatted();

        self::assertStringContainsString($expectedFormatted, $formatted);
    }

    /**
     * @test
     */
    public function it_compares_phone_numbers_by_value(): void
    {
        $phone1 = PhoneNumber::fromString('+33612345678');
        $phone2 = PhoneNumber::fromString('+33 6 12 34 56 78'); // Same, normalized
        $phone3 = PhoneNumber::fromString('+33698765432');      // Different

        self::assertTrue($phone1->equals($phone2));
        self::assertFalse($phone1->equals($phone3));
    }

    /**
     * @test
     */
    public function it_converts_to_string_for_casting(): void
    {
        $phoneNumber = PhoneNumber::fromString('+33612345678');

        self::assertEquals('+33612345678', (string) $phoneNumber);
    }

    /**
     * @test
     * @dataProvider invalidPhoneNumberProvider
     */
    public function it_throws_exception_for_invalid_phone_number(
        string $invalidPhone,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        PhoneNumber::fromString($invalidPhone);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_country_cannot_be_detected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot detect country from phone number');

        PhoneNumber::fromString('+99612345678'); // Invalid country code
    }

    /**
     * @test
     * @dataProvider allCountriesProvider
     */
    public function it_validates_phone_numbers_for_all_supported_countries(
        Country $country,
        string $validNumber
    ): void {
        $phoneNumber = PhoneNumber::fromCountryAndNumber($country, $validNumber);

        self::assertEquals($country, $phoneNumber->getCountry());
    }

    // Data Providers

    public static function validPhoneNumberProvider(): array
    {
        return [
            'France mobile' => ['+33612345678', Country::FR, '+33612345678'],
            'France fixed' => ['+33123456789', Country::FR, '+33123456789'],
            'France with spaces' => ['+33 6 12 34 56 78', Country::FR, '+33612345678'],
            'France with (0)' => ['+33 (0)6 12 34 56 78', Country::FR, '+33612345678'],
            'England' => ['+441234567890', Country::EN, '+441234567890'],
            'Germany' => ['+491234567890', Country::DE, '+491234567890'],
            'Spain' => ['+34612345678', Country::ES, '+34612345678'],
            'Italy' => ['+39123456789', Country::IT, '+39123456789'],
            'Netherlands' => ['+31612345678', Country::NL, '+31612345678'],
            'Belgium' => ['+32123456789', Country::BE, '+32123456789'],
        ];
    }

    public static function formattedPhoneNumberProvider(): array
    {
        return [
            'France' => ['+33612345678', '+33 6 12 34 56 78'],
            'England' => ['+441234567890', '+44'],
            'Germany' => ['+491234567890', '+49'],
        ];
    }

    public static function invalidPhoneNumberProvider(): array
    {
        return [
            'too short FR' => ['+3361234567', 'Invalid phone number for country FR'],
            'too long FR' => ['+336123456789', 'Invalid phone number for country FR'],
            'starts with 0 FR' => ['+33012345678', 'Invalid phone number for country FR'],
            'invalid EN' => ['+44012345678', 'Invalid phone number for country EN'],
            'invalid DE' => ['+49012345678', 'Invalid phone number for country DE'],
            'no prefix' => ['612345678', 'Cannot detect country'],
            'empty' => ['', 'Cannot detect country'],
        ];
    }

    public static function allCountriesProvider(): array
    {
        return [
            'France' => [Country::FR, '+33612345678'],
            'England' => [Country::EN, '+441234567890'],
            'Germany' => [Country::DE, '+491234567890'],
            'Spain' => [Country::ES, '+34612345678'],
            'Italy' => [Country::IT, '+39123456789'],
            'Netherlands' => [Country::NL, '+31612345678'],
            'Belgium' => [Country::BE, '+32123456789'],
        ];
    }
}
```

---

### [TEST] Créer tests d'intégration Doctrine Type (1h)

**Fichier:** `tests/Integration/Infrastructure/Persistence/Doctrine/Type/PhoneNumberTypeTest.php`

**Code complet:**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\Enum\Country;
use App\Domain\Shared\ValueObject\PhoneNumber;
use App\Infrastructure\Persistence\Doctrine\Type\PhoneNumberType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTypeTest extends TestCase
{
    private PhoneNumberType $type;
    private PostgreSQLPlatform $platform;

    protected function setUp(): void
    {
        if (!Type::hasType(PhoneNumberType::NAME)) {
            Type::addType(PhoneNumberType::NAME, PhoneNumberType::class);
        }

        $this->type = Type::getType(PhoneNumberType::NAME);
        $this->platform = new PostgreSQLPlatform();
    }

    /**
     * @test
     */
    public function it_converts_json_string_to_phone_number_object(): void
    {
        $databaseValue = '{"number":"+33612345678","country":"FR"}';

        $phpValue = $this->type->convertToPHPValue($databaseValue, $this->platform);

        self::assertInstanceOf(PhoneNumber::class, $phpValue);
        self::assertEquals('+33612345678', $phpValue->getValue());
        self::assertEquals(Country::FR, $phpValue->getCountry());
    }

    /**
     * @test
     */
    public function it_converts_phone_number_object_to_json_string(): void
    {
        $phoneNumber = PhoneNumber::fromString('+33612345678');

        $databaseValue = $this->type->convertToDatabaseValue($phoneNumber, $this->platform);

        $decoded = json_decode($databaseValue, true);
        self::assertEquals('+33612345678', $decoded['number']);
        self::assertEquals('FR', $decoded['country']);
    }

    /**
     * @test
     */
    public function it_handles_null_value_to_php(): void
    {
        $phpValue = $this->type->convertToPHPValue(null, $this->platform);

        self::assertNull($phpValue);
    }

    /**
     * @test
     */
    public function it_handles_null_value_to_database(): void
    {
        $databaseValue = $this->type->convertToDatabaseValue(null, $this->platform);

        self::assertNull($databaseValue);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_type_to_database(): void
    {
        $invalidValue = 'plain-string';

        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue($invalidValue, $this->platform);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_malformed_json(): void
    {
        $malformedJson = '{"number":"+33612345678"}'; // Missing country

        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue($malformedJson, $this->platform);
    }

    /**
     * @test
     */
    public function it_has_correct_name(): void
    {
        self::assertEquals('phone_number', $this->type->getName());
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

---

### [DOC] Documenter Value Object PhoneNumber (1h)

**Fichier:** `.claude/examples/value-object-phonenumber.md`

**Contenu:**

```markdown
# Value Object: PhoneNumber

## Caractéristiques

Le Value Object `PhoneNumber` représente un numéro de téléphone avec validation internationale.

**Propriétés:**
- **Immutable** (`final readonly class`)
- **Validated** (fail-fast dans le constructeur)
- **Type-safe** (impossible de passer un `string` où `PhoneNumber` est attendu)
- **Country-aware** (validation et formatage par pays)
- **Normalized** (supprime espaces, tirets, parenthèses)
- **Comparable** (méthode `equals()` par valeur)

**Pays supportés:**
- 🇫🇷 France (FR): +33612345678
- 🇬🇧 Angleterre (EN): +441234567890
- 🇩🇪 Allemagne (DE): +491234567890
- 🇪🇸 Espagne (ES): +34612345678
- 🇮🇹 Italie (IT): +39123456789
- 🇳🇱 Pays-Bas (NL): +31612345678
- 🇧🇪 Belgique (BE): +32123456789

---

## Création

### Détection automatique du pays

```php
<?php

use App\Domain\Shared\ValueObject\PhoneNumber;

// ✅ Détection automatique depuis le préfixe
$phone = PhoneNumber::fromString('+33612345678');
// Country: FR (auto-détecté)

$phone = PhoneNumber::fromString('+441234567890');
// Country: EN (auto-détecté)

// ✅ Gère les formats avec espaces/tirets
$phone = PhoneNumber::fromString('+33 6 12-34-56-78');
// Normalisé: +33612345678

// ✅ Gère le (0) français
$phone = PhoneNumber::fromString('+33 (0)6 12 34 56 78');
// Normalisé: +33612345678
```

### Pays explicite

```php
<?php

use App\Domain\Shared\Enum\Country;

// ✅ Pays explicite
$phone = PhoneNumber::fromCountryAndNumber(Country::FR, '612345678');
// Ajoute automatiquement le préfixe: +33612345678

$phone = PhoneNumber::fromCountryAndNumber(Country::DE, '+491234567890');
// Utilise le numéro tel quel
```

---

## Méthodes

### getValue() - Numéro normalisé

```php
<?php

$phone = PhoneNumber::fromString('+33 6 12-34-56-78');
echo $phone->getValue();
// Output: +33612345678
```

### getCountry() - Pays détecté

```php
<?php

$phone = PhoneNumber::fromString('+33612345678');
$country = $phone->getCountry();
// Country: FR (enum)

echo $country->value;
// Output: "FR"

echo $country->getNameFr();
// Output: "France"
```

### getFormatted() - Formatage par pays

```php
<?php

// France: +33 6 12 34 56 78
$phoneFR = PhoneNumber::fromString('+33612345678');
echo $phoneFR->getFormatted();
// Output: "+33 6 12 34 56 78"

// Angleterre: +44 1234 567890
$phoneEN = PhoneNumber::fromString('+441234567890');
echo $phoneEN->getFormatted();
// Output: "+44 1234 567890"

// Allemagne: +49 123 4567890
$phoneDE = PhoneNumber::fromString('+491234567890');
echo $phoneDE->getFormatted();
// Output: "+49 123 4567890"
```

### equals() - Comparaison par valeur

```php
<?php

$phone1 = PhoneNumber::fromString('+33612345678');
$phone2 = PhoneNumber::fromString('+33 6 12 34 56 78'); // Normalisé identique
$phone3 = PhoneNumber::fromString('+33698765432');      // Différent

$phone1->equals($phone2); // true
$phone1->equals($phone3); // false
```

### __toString() - Cast string

```php
<?php

$phone = PhoneNumber::fromString('+33612345678');

echo $phone;
// Output: +33612345678

echo "Téléphone: " . $phone;
// Output: Téléphone: +33612345678
```

---

## Utilisation dans une entité

### Client entity

```php
<?php

namespace App\Domain\Client\Entity;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\PhoneNumber;

final class Client
{
    private ClientId $id;
    private Email $email;
    private PhoneNumber $phoneNumber; // ✅ Type fort

    public static function create(
        ClientId $id,
        Email $email,
        PhoneNumber $phoneNumber // ✅ Impossible de passer un string invalide
    ): self {
        $client = new self();
        $client->id = $id;
        $client->email = $email;
        $client->phoneNumber = $phoneNumber;

        return $client;
    }

    public function updatePhoneNumber(PhoneNumber $newPhoneNumber): void
    {
        $oldPhoneNumber = $this->phoneNumber;
        $this->phoneNumber = $newPhoneNumber;

        $this->recordEvent(
            new ClientPhoneNumberUpdatedEvent($this->id, $oldPhoneNumber, $newPhoneNumber)
        );
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phoneNumber;
    }
}
```

---

## Persistence avec Doctrine

### Mapping XML

```xml
<!-- Infrastructure/Persistence/Doctrine/Mapping/Client.orm.xml -->
<doctrine-mapping>
    <entity name="App\Domain\Client\Entity\Client" table="client">
        <id name="id" type="client_id"/>

        <field name="email" type="email" nullable="false"/>

        <!-- ✅ PhoneNumber stocké en JSON -->
        <field name="phoneNumber" type="phone_number" nullable="true" column="phone_number"/>
    </entity>
</doctrine-mapping>
```

**Stockage en base:**

```sql
-- Table: client
-- Colonne: phone_number (TEXT)
-- Valeur: {"number":"+33612345678","country":"FR"}
```

---

## Tests

### Test unitaire

```php
<?php

use App\Domain\Shared\ValueObject\PhoneNumber;
use App\Domain\Shared\Enum\Country;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    /**
     * @test
     */
    public function it_validates_french_mobile_number(): void
    {
        $phone = PhoneNumber::fromString('+33612345678');

        self::assertEquals('+33612345678', $phone->getValue());
        self::assertEquals(Country::FR, $phone->getCountry());
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_french_number(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number for country FR');

        PhoneNumber::fromString('+33012345678'); // ❌ Starts with 0
    }
}
```

---

## Formats de validation par pays

### France (FR)

**Pattern:** `+33[1-9]\d{8}`

**Valides:**
- `+33612345678` (mobile: 06, 07)
- `+33123456789` (fixe: 01, 02, 03, 04, 05, 09)

**Invalides:**
- `+33012345678` (commence par 0)
- `+3361234567` (trop court)
- `+336123456789` (trop long)

### Angleterre (EN)

**Pattern:** `+44[1-9]\d{9,10}`

**Valides:**
- `+441234567890` (10 chiffres après +44)
- `+4412345678901` (11 chiffres après +44)

**Invalides:**
- `+44012345678` (commence par 0)
- `+4412345` (trop court)

### Allemagne (DE)

**Pattern:** `+49[1-9]\d{9,10}`

**Valides:**
- `+491234567890` (10 chiffres)
- `+4912345678901` (11 chiffres)

**Invalides:**
- `+49012345678` (commence par 0)

### Espagne (ES)

**Pattern:** `+34[6-9]\d{8}`

**Valides:**
- `+34612345678` (mobile: 6, 7)
- `+34812345678` (fixe: 8, 9)

**Invalides:**
- `+34512345678` (commence par 5)

### Italie (IT)

**Pattern:** `+39\d{9,10}`

**Valides:**
- `+39123456789` (9 chiffres)
- `+391234567890` (10 chiffres)

### Pays-Bas (NL)

**Pattern:** `+31[1-9]\d{8}`

**Valides:**
- `+31612345678` (mobile: 6)
- `+31201234567` (fixe: 10, 20, 30, etc.)

**Invalides:**
- `+31012345678` (commence par 0)

### Belgique (BE)

**Pattern:** `+32[1-9]\d{7,8}`

**Valides:**
- `+3212345678` (8 chiffres: fixe)
- `+32123456789` (9 chiffres: mobile)

**Invalides:**
- `+32012345678` (commence par 0)

---

## Exemple d'usage complet

### 1. Controller (Presentation)

```php
<?php

use App\Domain\Shared\ValueObject\PhoneNumber;

// ✅ Validation à la création
try {
    $phoneNumber = PhoneNumber::fromString($request->request->get('phone'));
} catch (\InvalidArgumentException $e) {
    $this->addFlash('error', 'Numéro de téléphone invalide');
    return $this->redirectToRoute('client_form');
}

$command = new CreateClientCommand(
    clientId: ClientId::generate(),
    name: $request->request->get('name'),
    email: Email::fromString($request->request->get('email')),
    phoneNumber: $phoneNumber, // ✅ Type PhoneNumber (not string)
);
```

### 2. CommandHandler (Application)

```php
<?php

final readonly class CreateClientCommandHandler
{
    public function __invoke(CreateClientCommand $command): void
    {
        // ✅ phoneNumber déjà validé (PhoneNumber VO)
        $client = Client::create(
            $command->clientId,
            $command->name,
            $command->email,
            $command->phoneNumber // ✅ Type PhoneNumber
        );

        $this->clientRepository->save($client);
    }
}
```

### 3. Entity (Domain)

```php
<?php

final class Client
{
    private PhoneNumber $phoneNumber; // ✅ Strong type

    public function updatePhoneNumber(PhoneNumber $newPhoneNumber): void
    {
        $oldPhoneNumber = $this->phoneNumber;
        $this->phoneNumber = $newPhoneNumber;

        $this->recordEvent(
            new ClientPhoneNumberUpdatedEvent($this->id, $oldPhoneNumber, $newPhoneNumber)
        );
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phoneNumber;
    }
}
```

### 4. Persistence (Infrastructure)

```php
<?php

// ✅ Doctrine Custom Type gère automatiquement la conversion
$client = $this->clientRepository->findById($clientId);

// Lecture: JSON → PhoneNumber VO
$phoneNumber = $client->getPhoneNumber();
echo $phoneNumber->getFormatted();
// Output: "+33 6 12 34 56 78"

// Écriture: PhoneNumber VO → JSON
$client->updatePhoneNumber(PhoneNumber::fromString('+33698765432'));
$this->clientRepository->save($client);
// Stocké: {"number":"+33698765432","country":"FR"}
```

---

## Avantages du Value Object PhoneNumber

| Avant (string) | Après (PhoneNumber VO) |
|---------------|------------------------|
| `private ?string $phoneNumber` | `private ?PhoneNumber $phoneNumber` |
| Validation dispersée (Controller, Entity, Form) | Validation centralisée (PhoneNumber VO) |
| Numéros invalides possibles en BDD | Impossible (validation constructor) |
| Type faible (`string`) | Type fort (`PhoneNumber`) |
| Pas de formatage cohérent | Formatage par pays (`getFormatted()`) |
| Duplication validation par pays | Single Source of Truth |
| Erreurs runtime | Erreurs compile-time (PHPStan) |

---

## Anti-patterns à éviter

### ❌ PhoneNumber avec setter

```php
<?php

// MAUVAIS: Mutable
class PhoneNumber
{
    private string $value;

    public function setValue(string $value): void // ❌ Setter
    {
        $this->value = $value;
    }
}
```

### ❌ Validation dans l'entité

```php
<?php

// MAUVAIS: Duplication de validation
class Client
{
    private string $phoneNumber;

    public function setPhoneNumber(string $phoneNumber): void
    {
        // ❌ Validation ici = duplication
        if (!preg_match('/^\+33[1-9]\d{8}$/', $phoneNumber)) {
            throw new \InvalidArgumentException('Invalid phone');
        }

        $this->phoneNumber = $phoneNumber;
    }
}
```

### ❌ PhoneNumber avec ID

```php
<?php

// MAUVAIS: Value Object ne doit PAS avoir d'identité
class PhoneNumber
{
    private int $id; // ❌ Pas d'ID pour un Value Object

    private string $value;
}
```

---

## Stratégie de migration

### Phase 1: Créer PhoneNumber VO (US-011) ← Cette User Story

```php
<?php

// Créer:
// - src/Domain/Shared/ValueObject/PhoneNumber.php
// - src/Domain/Shared/Enum/Country.php
// - tests/Unit/Domain/Shared/ValueObject/PhoneNumberTest.php
```

### Phase 2: Créer Doctrine Custom Type

```php
<?php

// Créer:
// - src/Infrastructure/Persistence/Doctrine/Type/PhoneNumberType.php
// Configurer:
// - config/packages/doctrine.yaml
```

### Phase 3: Remplacer string par PhoneNumber (US-018)

```php
<?php

// AVANT
class Client
{
    private ?string $phoneNumber = null;
}

// APRÈS
class Client
{
    private ?PhoneNumber $phoneNumber = null;
}
```

### Phase 4: Migration base de données

```sql
-- Pas de changement de schéma nécessaire
-- Mapping XML utilise le custom type:
-- <field name="phoneNumber" type="phone_number" nullable="true"/>
```

---

## Checklist Value Object PhoneNumber

- [ ] Classe `final readonly`
- [ ] Validation dans le constructeur (fail-fast)
- [ ] Factory methods: `fromString()`, `fromCountryAndNumber()`
- [ ] Méthodes: `getValue()`, `getCountry()`, `getFormatted()`, `equals()`
- [ ] Aucun setter (immutabilité)
- [ ] Normalisation: supprime espaces, tirets, parenthèses, (0)
- [ ] Validation pour les 7 pays (FR, EN, DE, ES, IT, NL, BE)
- [ ] Enum `Country` avec méthodes utilitaires
- [ ] Type Doctrine custom créé avec stockage JSON
- [ ] Tests unitaires couvrant tous les pays
- [ ] Tests d'intégration Doctrine Type
- [ ] Documentation complète avec exemples

---

## Références

- `.claude/rules/18-value-objects.md` - Template Value Object
- `.claude/rules/16-i18n.md` - Country enum et formats (lignes 13-46)
- **Livre:** *Domain-Driven Design* - Eric Evans, Chapitre 5 (Value Objects)
- **Livre:** *Implementing Domain-Driven Design* - Vaughn Vernon, Chapitre 6
- **Library:** libphonenumber (Google) - Référence pour formats internationaux
```

---

### [VALIDATION] Valider avec outils qualité (0.5h)

**Commandes:**

```bash
# PHPStan niveau max
make phpstan

# Output attendu:
# [OK] No errors

# CS-Fixer
make cs-fix

# Deptrac (vérifier isolation Domain)
make deptrac

# Output attendu:
# ✅ Domain layer: 0 violations (PhoneNumber ne dépend de rien)

# Tests unitaires
make test-unit

# Output attendu:
# OK (25 tests, 45 assertions)
# Code Coverage: 95%

# Tests d'intégration
make test-integration

# Output attendu:
# OK (8 tests, 12 assertions)
```

---

## Définition de Done (DoD)

- [ ] Value Object `src/Domain/Shared/ValueObject/PhoneNumber.php` créé
- [ ] Enum `src/Domain/Shared/Enum/Country.php` créé avec 7 pays
- [ ] Enum `src/Domain/Shared/Enum/Currency.php` créé (EUR, GBP)
- [ ] Classe `final readonly` (immutabilité)
- [ ] Validation par pays dans le constructeur (fail-fast)
- [ ] Factory method `fromString()` avec détection automatique pays
- [ ] Factory method `fromCountryAndNumber()` pour pays explicite
- [ ] Normalisation (supprime espaces, tirets, parenthèses, (0))
- [ ] Méthode `getValue()` retourne numéro normalisé
- [ ] Méthode `getCountry()` retourne l'enum Country
- [ ] Méthode `getFormatted()` retourne formatage par pays
- [ ] Méthode `equals()` pour comparaison par valeur
- [ ] Méthode `__toString()` pour cast string
- [ ] Aucun setter (immutabilité garantie)
- [ ] Patterns de validation pour 7 pays (FR, EN, DE, ES, IT, NL, BE)
- [ ] Doctrine Custom Type `PhoneNumberType` créé avec stockage JSON
- [ ] Type `phone_number` enregistré dans `doctrine.yaml`
- [ ] Tests unitaires avec couverture ≥ 90%
- [ ] Data providers pour formats valides/invalides de chaque pays
- [ ] Tests de normalisation (espaces, tirets, (0))
- [ ] Tests de formatage pour chaque pays
- [ ] Tests d'intégration Doctrine Type créés
- [ ] Tests de conversion PHP ↔ Database (JSON)
- [ ] Tests de gestion NULL
- [ ] PHPStan niveau max passe sur src/Domain/Shared/
- [ ] Aucune dépendance externe (pure PHP + enum)
- [ ] Documentation `.claude/examples/value-object-phonenumber.md` créée
- [ ] Exemples d'utilisation documentés pour les 7 pays
- [ ] Patterns de validation documentés
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(domain): create PhoneNumber value object with international validation`

---

## Notes techniques

### Formats internationaux supportés

#### France (+33)
- **Mobile:** +33 6XX XX XX XX ou +33 7XX XX XX XX
- **Fixe:** +33 1XX XX XX XX à +33 5XX XX XX XX, +33 9XX XX XX XX
- **Pattern:** `+33[1-9]\d{8}` (9 chiffres après +33, ne commence pas par 0)
- **Normalisation:** Supprime (0) de +33(0)6

#### Angleterre (+44)
- **Mobile et fixe:** +44 XXXX XXXXXX
- **Pattern:** `+44[1-9]\d{9,10}` (10-11 chiffres après +44)
- **Variabilité:** Numéros de 10 ou 11 chiffres

#### Allemagne (+49)
- **Mobile et fixe:** +49 XXX XXXXXXX
- **Pattern:** `+49[1-9]\d{9,10}` (10-11 chiffres)

#### Espagne (+34)
- **Mobile:** +34 6XX XX XX XX ou +34 7XX XX XX XX
- **Fixe:** +34 8XX XX XX XX ou +34 9XX XX XX XX
- **Pattern:** `+34[6-9]\d{8}` (commence par 6-9)

#### Italie (+39)
- **Mobile et fixe:** +39 XXX XXXXXX
- **Pattern:** `+39\d{9,10}` (9-10 chiffres, pas de restriction premier chiffre)

#### Pays-Bas (+31)
- **Mobile:** +31 6 XX XX XX XX
- **Fixe:** +31 XX XXX XX XX
- **Pattern:** `+31[1-9]\d{8}` (9 chiffres après +31)

#### Belgique (+32)
- **Mobile:** +32 4XX XX XX XX (9 chiffres)
- **Fixe:** +32 XX XX XX XX (8 chiffres)
- **Pattern:** `+32[1-9]\d{7,8}` (8-9 chiffres)

### Normalisation des numéros

Le processus de normalisation supprime:
- **Espaces:** `06 12 34 56 78` → `0612345678`
- **Tirets:** `06-12-34-56-78` → `0612345678`
- **Parenthèses:** `(0)6` → `6`
- **Points:** `06.12.34.56.78` → `0612345678`
- **Format français (0):** `+33 (0)6` → `+336`

**Exemple complet:**
```
Input:  "+33 (0)6 12-34-56-78"
Step 1: Remove spaces/dashes → "+33(0)612345678"
Step 2: Remove (0) → "+33612345678"
Output: "+33612345678"
```

### Stockage JSON Doctrine

**Pourquoi JSON?**
- Stocke le numéro ET le pays (nécessaire pour formatage)
- Évite de créer une table dédiée
- Facilite les migrations (colonne VARCHAR/TEXT)
- Sérialisation/désérialisation automatique

**Format stocké:**
```json
{
  "number": "+33612345678",
  "country": "FR"
}
```

### Enum Country - Utilité multi-usage

L'enum `Country` sera réutilisé dans:
- ✅ PhoneNumber (validation et formatage)
- ✅ PostalAddress (US-015: codes postaux par pays)
- ✅ CompanyIdentifier (US-016: SIRET, VAT par pays)
- ✅ Money (currency par pays)
- ✅ TaxCalculator (TVA par pays)
- ✅ i18n (locale par pays)

### Alternative: libphonenumber-for-php

**Note:** Pour une validation **encore plus stricte**, utiliser `giggsey/libphonenumber-for-php`:

```bash
make composer CMD="require giggsey/libphonenumber-for-php"
```

```php
<?php

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

$phoneUtil = PhoneNumberUtil::getInstance();

try {
    $phoneProto = $phoneUtil->parse($input, $country->value);

    if (!$phoneUtil->isValidNumber($phoneProto)) {
        throw new \InvalidArgumentException('Invalid phone number');
    }

    $normalized = $phoneUtil->format($phoneProto, PhoneNumberFormat::E164);
    // Output: +33612345678

} catch (\libphonenumber\NumberParseException $e) {
    throw new \InvalidArgumentException('Invalid phone number format');
}
```

**Pour cette User Story, nous utilisons des regex simples (pas de dépendance externe).**
L'utilisation de `libphonenumber-for-php` peut être considérée dans une US ultérieure si la validation stricte est nécessaire.

---

## Dépendances

### Bloquantes (doivent être faites avant)

- **US-001**: Structure Domain créée (nécessite `src/Domain/Shared/ValueObject/` et `src/Domain/Shared/Enum/`)

### Bloque (cette US doit être faite avant)

- **US-002**: Extraction Client (utilisera PhoneNumber)
- **US-004**: Extraction User (utilisera PhoneNumber)
- **US-015**: PostalAddress VO (réutilise Country enum)
- **US-018**: Remplacement types primitifs par VOs

---

## Références

- `.claude/rules/18-value-objects.md` - Template Value Object (lignes 75-150, PhoneNumber example)
- `.claude/rules/16-i18n.md` - Country enum et formats internationaux (lignes 13-46)
- `.claude/rules/02-architecture-clean-ddd.md` - Domain purity (lignes 45-155)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` - Problème types primitifs (lignes 75-108)
- **Livre:** *Domain-Driven Design* - Eric Evans, Chapitre 5 (Value Objects)
- **Livre:** *Implementing Domain-Driven Design* - Vaughn Vernon, Chapitre 6 (Value Objects)
- **Library:** libphonenumber (Google) - Référence validation téléphone internationale

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |
