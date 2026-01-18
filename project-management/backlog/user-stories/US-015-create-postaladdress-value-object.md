# US-015: Créer Value Object PostalAddress avec validation par pays

**EPIC:** [EPIC-002](../epics/EPIC-002-value-objects.md) - Implémentation des Value Objects
**Priorité:** 🔴 CRITIQUE
**Points:** 3
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer un Value Object PostalAddress pour représenter les adresses postales avec validation par pays
**Afin de** centraliser la validation des adresses, supporter les 7 pays européens (FR, EN, DE, ES, IT, NL, BE), garantir la cohérence des formats, et éviter les types primitifs dispersés

---

## Critères d'acceptation

### GIVEN: Je crée un Value Object PostalAddress

**WHEN:** Je crée l'objet avec street, postalCode, city, country

**THEN:**
- [ ] Classe `PostalAddress.php` créée dans `src/Domain/Shared/ValueObject/`
- [ ] Classe marquée `final readonly`
- [ ] Constructor `private` avec validation
- [ ] Factory method `create(street, postalCode, city, country)` statique
- [ ] Propriétés : `street`, `postalCode`, `city`, `country` (Country enum)
- [ ] Validation code postal par pays (formats différents : FR: 5 chiffres, EN: alphanumeric, DE: 5 chiffres, ES: 5 chiffres, IT: 5 chiffres, NL: 4 chiffres + 2 lettres, BE: 4 chiffres)
- [ ] Validation ville (max 100 caractères, caractères valides)
- [ ] Validation rue (max 255 caractères, non vide)
- [ ] Méthode `getFormattedAddress()` retourne adresse formatée selon pays
- [ ] Méthode `getFormattedAddressOneLine()` retourne adresse sur une ligne
- [ ] Méthode `getCountry()` retourne Country enum
- [ ] Méthode `getPostalCode()` retourne code postal
- [ ] Méthode `getCity()` retourne ville
- [ ] Méthode `getStreet()` retourne rue
- [ ] Méthode `equals(PostalAddress)` compare par valeur tous les composants
- [ ] Méthode `__toString()` retourne adresse formatée
- [ ] Aucun setter (immutabilité stricte)
- [ ] Support des 7 pays : FR, EN, DE, ES, IT, NL, BE (`.claude/rules/16-i18n.md`)

### GIVEN: Le Value Object PostalAddress existe

**WHEN:** J'exécute PHPStan niveau max sur src/Domain/Shared/ValueObject/

**THEN:**
- [ ] Aucune erreur PHPStan
- [ ] Aucune dépendance détectée vers Symfony ou Doctrine
- [ ] Types stricts utilisés (`string`, `Country`)
- [ ] Propriétés `readonly` détectées
- [ ] Pas de mutateurs (setters) détectés

### GIVEN: Le Value Object PostalAddress existe avec validation par pays

**WHEN:** J'exécute les tests unitaires

**THEN:**
- [ ] Tests de création avec `create()` passent
- [ ] Tests de validation code postal par pays passent (FR: 75001, EN: SW1A 1AA, DE: 10115, etc.)
- [ ] Tests de validation ville passent (max 100 chars, caractères valides)
- [ ] Tests de validation rue passent (max 255 chars, non vide)
- [ ] Tests de formatage multilingne `getFormattedAddress()` passent
- [ ] Tests de formatage une ligne `getFormattedAddressOneLine()` passent
- [ ] Tests de comparaison `equals()` passent
- [ ] Tests de casting `__toString()` passent
- [ ] Tests d'immutabilité (readonly) passent
- [ ] Tests avec adresses internationales passent (accents, caractères spéciaux)
- [ ] Tests de codes postaux invalides par pays lancent exceptions
- [ ] Tests de villes/rues trop longues lancent exceptions
- [ ] Couverture code ≥ 90% sur PostalAddress
- [ ] Tests s'exécutent en moins de 100ms

---

## Tâches techniques

### [DOMAIN] Créer Value Object PostalAddress (2.5h)

**Fichier:** `src/Domain/Shared/ValueObject/PostalAddress.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

/**
 * Postal address value object with country-specific validation.
 *
 * Immutable representation of a physical address supporting 7 European countries.
 *
 * @see .claude/rules/16-i18n.md Country patterns
 */
final readonly class PostalAddress
{
    private const int MAX_STREET_LENGTH = 255;
    private const int MAX_CITY_LENGTH = 100;

    private function __construct(
        private string $street,
        private string $postalCode,
        private string $city,
        private Country $country,
    ) {
        $this->validate();
    }

    /**
     * Create a postal address with explicit components.
     *
     * @throws \InvalidArgumentException if validation fails
     */
    public static function create(
        string $street,
        string $postalCode,
        string $city,
        Country $country
    ): self {
        return new self(
            self::normalizeStreet($street),
            self::normalizePostalCode($postalCode),
            self::normalizeCity($city),
            $country
        );
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    /**
     * Get formatted address (multiline, country-specific).
     *
     * Format varies by country:
     * - FR/ES/IT: Street \n PostalCode City
     * - EN: Street \n City \n PostalCode
     * - DE/NL/BE: Street \n PostalCode City
     */
    public function getFormattedAddress(): string
    {
        return match ($this->country) {
            Country::EN => sprintf(
                "%s\n%s\n%s",
                $this->street,
                $this->city,
                $this->postalCode
            ),
            default => sprintf(
                "%s\n%s %s",
                $this->street,
                $this->postalCode,
                $this->city
            ),
        };
    }

    /**
     * Get formatted address on one line.
     */
    public function getFormattedAddressOneLine(): string
    {
        return sprintf(
            '%s, %s %s, %s',
            $this->street,
            $this->postalCode,
            $this->city,
            $this->country->value
        );
    }

    /**
     * Compare addresses by value.
     */
    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->postalCode === $other->postalCode
            && $this->city === $other->city
            && $this->country === $other->country;
    }

    public function __toString(): string
    {
        return $this->getFormattedAddress();
    }

    /**
     * Normalize street: trim, collapse spaces.
     */
    private static function normalizeStreet(string $value): string
    {
        $value = trim($value);
        return preg_replace('/\s+/', ' ', $value);
    }

    /**
     * Normalize postal code: trim, uppercase, remove spaces for validation.
     */
    private static function normalizePostalCode(string $value): string
    {
        $value = trim($value);
        $value = mb_strtoupper($value, 'UTF-8');
        // Keep spaces for display (EN: "SW1A 1AA"), but validate without
        return $value;
    }

    /**
     * Normalize city: trim, capitalize, collapse spaces.
     */
    private static function normalizeCity(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
        return $value;
    }

    /**
     * Validate all components.
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        $this->validateStreet();
        $this->validatePostalCode();
        $this->validateCity();
    }

    private function validateStreet(): void
    {
        if (empty($this->street)) {
            throw new \InvalidArgumentException('Street cannot be empty');
        }

        if (mb_strlen($this->street) > self::MAX_STREET_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Street cannot exceed %d characters', self::MAX_STREET_LENGTH)
            );
        }

        // Allow letters, numbers, spaces, hyphens, apostrophes, commas, periods
        if (!preg_match('/^[\p{L}\p{N}\s\-\',\.]+$/u', $this->street)) {
            throw new \InvalidArgumentException(
                'Street contains invalid characters'
            );
        }
    }

    private function validatePostalCode(): void
    {
        if (empty($this->postalCode)) {
            throw new \InvalidArgumentException('Postal code cannot be empty');
        }

        // Validate format by country
        $pattern = $this->getPostalCodePattern($this->country);

        // Remove spaces for validation (EN postal codes have spaces)
        $codeWithoutSpaces = str_replace(' ', '', $this->postalCode);

        if (!preg_match($pattern, $codeWithoutSpaces)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid postal code format for country %s: %s',
                    $this->country->value,
                    $this->postalCode
                )
            );
        }
    }

    private function validateCity(): void
    {
        if (empty($this->city)) {
            throw new \InvalidArgumentException('City cannot be empty');
        }

        if (mb_strlen($this->city) > self::MAX_CITY_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('City cannot exceed %d characters', self::MAX_CITY_LENGTH)
            );
        }

        // Allow letters, spaces, hyphens, apostrophes (Saint-Étienne, L'Aquila)
        if (!preg_match('/^[\p{L}\s\-\']+$/u', $this->city)) {
            throw new \InvalidArgumentException(
                'City contains invalid characters'
            );
        }
    }

    /**
     * Get postal code validation pattern by country.
     */
    private function getPostalCodePattern(Country $country): string
    {
        return match ($country) {
            Country::FR => '/^[0-9]{5}$/',                      // 75001
            Country::DE => '/^[0-9]{5}$/',                      // 10115
            Country::ES => '/^[0-9]{5}$/',                      // 28001
            Country::IT => '/^[0-9]{5}$/',                      // 00100
            Country::BE => '/^[0-9]{4}$/',                      // 1000
            Country::NL => '/^[1-9][0-9]{3}[A-Z]{2}$/i',       // 1012AB
            Country::EN => '/^[A-Z]{1,2}[0-9]{1,2}[A-Z]?[0-9][A-Z]{2}$/i', // SW1A1AA
        };
    }
}
```

**Actions:**
- Créer `src/Domain/Shared/ValueObject/PostalAddress.php`
- Propriétés : `street`, `postalCode`, `city`, `country`
- Constructor `private` avec validation par pays
- Factory method `create()` avec normalisation
- Validation codes postaux par pays (7 formats différents)
- Validation ville : max 100 chars, lettres/espaces/hyphens/apostrophes (Unicode)
- Validation rue : max 255 chars, lettres/chiffres/espaces/ponctuation
- Méthode `getFormattedAddress()` avec format par pays (EN différent)
- Méthode `getFormattedAddressOneLine()` pour affichage compact
- Méthode `equals()` pour comparaison par valeur
- Méthode `__toString()` retourne adresse formatée
- Support i18n : accents (Saint-Étienne), apostrophes (L'Aquila), hyphens (Saint-Denis)

---

### [INFRA] Créer Doctrine Custom Type PostalAddressType (1h)

**Fichier:** `src/Infrastructure/Persistence/Doctrine/Type/PostalAddressType.php`

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\ValueObject\Country;
use App\Domain\Shared\ValueObject\PostalAddress;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;

/**
 * Doctrine type for PostalAddress Value Object.
 *
 * Stores address as JSON: {"street":"...", "postalCode":"...", "city":"...", "country":"FR"}
 */
final class PostalAddressType extends JsonType
{
    public const string NAME = 'postal_address';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?PostalAddress
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PostalAddress) {
            return $value;
        }

        // Decode JSON to array
        $data = parent::convertToPHPValue($value, $platform);

        if (!is_array($data)) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', 'array', PostalAddress::class]
            );
        }

        // Validate required keys
        if (!isset($data['street']) || !isset($data['postalCode']) || !isset($data['city']) || !isset($data['country'])) {
            throw ConversionException::conversionFailed(
                json_encode($data),
                $this->getName()
            );
        }

        try {
            return PostalAddress::create(
                $data['street'],
                $data['postalCode'],
                $data['city'],
                Country::from($data['country'])
            );
        } catch (\InvalidArgumentException | \ValueError $e) {
            throw ConversionException::conversionFailed(
                json_encode($data),
                $this->getName(),
                $e
            );
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof PostalAddress) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', PostalAddress::class]
            );
        }

        $data = [
            'street' => $value->getStreet(),
            'postalCode' => $value->getPostalCode(),
            'city' => $value->getCity(),
            'country' => $value->getCountry()->value,
        ];

        return parent::convertToDatabaseValue($data, $platform);
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
- Créer `src/Infrastructure/Persistence/Doctrine/Type/PostalAddressType.php`
- Étendre `JsonType` (stockage JSON natif PostgreSQL)
- Implémenter `convertToPHPValue()` : JSON → PostalAddress VO
  - Décoder JSON vers array avec parent::convertToPHPValue()
  - Valider présence des clés : street, postalCode, city, country
  - Country::from() pour convertir string → enum
  - PostalAddress::create() avec gestion des exceptions
- Implémenter `convertToDatabaseValue()` : PostalAddress VO → JSON
  - Convertir vers array avec tous les composants
  - Encoder avec parent::convertToDatabaseValue()
- Gérer `null` dans les deux directions
- ConversionException pour types invalides
- Format JSON : `{"street":"10 Rue de la Paix","postalCode":"75001","city":"Paris","country":"FR"}`

---

### [CONFIG] Enregistrer Custom Type Doctrine (0.5h)

**Fichier:** `config/packages/doctrine.yaml`

```yaml
doctrine:
    dbal:
        types:
            postal_address: App\Infrastructure\Persistence\Doctrine\Type\PostalAddressType

        mapping_types:
            postal_address: json
```

**Actions:**
- Ajouter type `postal_address` dans `doctrine.yaml`
- Mapper vers classe `PostalAddressType`
- Déclarer `mapping_types` pour migrations
- Vérifier avec : `make console CMD="dbal:types"`

---

### [TEST] Créer tests unitaires PostalAddress (2h)

**Fichier:** `tests/Unit/Domain/Shared/ValueObject/PostalAddressTest.php`

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\ValueObject\Country;
use App\Domain\Shared\ValueObject\PostalAddress;
use PHPUnit\Framework\TestCase;

final class PostalAddressTest extends TestCase
{
    /** @test */
    public function it_creates_postal_address_with_all_components(): void
    {
        // When
        $address = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        // Then
        self::assertEquals('10 Rue de la Paix', $address->getStreet());
        self::assertEquals('75001', $address->getPostalCode());
        self::assertEquals('Paris', $address->getCity());
        self::assertEquals(Country::FR, $address->getCountry());
    }

    /** @test */
    public function it_validates_french_postal_code(): void
    {
        // Valid FR: 5 digits
        $address = PostalAddress::create(
            '123 Avenue des Champs-Élysées',
            '75008',
            'Paris',
            Country::FR
        );

        self::assertEquals('75008', $address->getPostalCode());
    }

    /** @test */
    public function it_throws_exception_for_invalid_french_postal_code(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid postal code format for country FR');

        // When: Invalid format (4 digits instead of 5)
        PostalAddress::create(
            '10 Rue de la Paix',
            '7500', // ❌ Invalid
            'Paris',
            Country::FR
        );
    }

    /** @test */
    public function it_validates_english_postal_code(): void
    {
        // Valid EN: alphanumeric (SW1A 1AA)
        $address = PostalAddress::create(
            '10 Downing Street',
            'SW1A 1AA',
            'London',
            Country::EN
        );

        self::assertEquals('SW1A 1AA', $address->getPostalCode());
    }

    /** @test */
    public function it_validates_german_postal_code(): void
    {
        // Valid DE: 5 digits
        $address = PostalAddress::create(
            'Unter den Linden 1',
            '10117',
            'Berlin',
            Country::DE
        );

        self::assertEquals('10117', $address->getPostalCode());
    }

    /** @test */
    public function it_validates_dutch_postal_code(): void
    {
        // Valid NL: 1234AB format
        $address = PostalAddress::create(
            'Dam 1',
            '1012AB',
            'Amsterdam',
            Country::NL
        );

        self::assertEquals('1012AB', $address->getPostalCode());
    }

    /** @test */
    public function it_throws_exception_for_invalid_dutch_postal_code(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);

        // When: Invalid format (starts with 0)
        PostalAddress::create(
            'Dam 1',
            '0123AB', // ❌ Cannot start with 0
            'Amsterdam',
            Country::NL
        );
    }

    /** @test */
    public function it_validates_belgian_postal_code(): void
    {
        // Valid BE: 4 digits
        $address = PostalAddress::create(
            'Rue de la Loi 16',
            '1000',
            'Bruxelles',
            Country::BE
        );

        self::assertEquals('1000', $address->getPostalCode());
    }

    /** @test */
    public function it_normalizes_street(): void
    {
        // Given: Extra whitespace
        $address = PostalAddress::create(
            '  10   Rue   de   la   Paix  ',
            '75001',
            'Paris',
            Country::FR
        );

        // Then: Normalized
        self::assertEquals('10 Rue de la Paix', $address->getStreet());
    }

    /** @test */
    public function it_normalizes_city(): void
    {
        // Given: Lowercase, extra whitespace
        $address = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            '  paris  ',
            Country::FR
        );

        // Then: Capitalized, trimmed
        self::assertEquals('Paris', $address->getCity());
    }

    /** @test */
    public function it_normalizes_postal_code(): void
    {
        // Given: Lowercase EN postal code
        $address = PostalAddress::create(
            '10 Downing Street',
            'sw1a 1aa',
            'London',
            Country::EN
        );

        // Then: Uppercase
        self::assertEquals('SW1A 1AA', $address->getPostalCode());
    }

    /** @test */
    public function it_throws_exception_for_empty_street(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Street cannot be empty');

        PostalAddress::create('', '75001', 'Paris', Country::FR);
    }

    /** @test */
    public function it_throws_exception_for_empty_city(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('City cannot be empty');

        PostalAddress::create('10 Rue de la Paix', '75001', '', Country::FR);
    }

    /** @test */
    public function it_throws_exception_for_street_too_long(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Street cannot exceed 255 characters');

        PostalAddress::create(
            str_repeat('A', 256),
            '75001',
            'Paris',
            Country::FR
        );
    }

    /** @test */
    public function it_throws_exception_for_city_too_long(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('City cannot exceed 100 characters');

        PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            str_repeat('A', 101),
            Country::FR
        );
    }

    /** @test */
    public function it_throws_exception_for_invalid_street_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Street contains invalid characters');

        PostalAddress::create(
            '10 Rue <script>alert("XSS")</script>',
            '75001',
            'Paris',
            Country::FR
        );
    }

    /** @test */
    public function it_supports_accented_cities(): void
    {
        // French accents
        $address = PostalAddress::create(
            '10 Avenue de la République',
            '42000',
            'Saint-Étienne',
            Country::FR
        );

        self::assertEquals('Saint-Étienne', $address->getCity());
    }

    /** @test */
    public function it_supports_apostrophes_in_cities(): void
    {
        // Italian apostrophe
        $address = PostalAddress::create(
            'Via Roma 10',
            '67100',
            "L'Aquila",
            Country::IT
        );

        self::assertEquals("L'Aquila", $address->getCity());
    }

    /** @test */
    public function it_formats_french_address(): void
    {
        $address = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        $expected = "10 Rue de la Paix\n75001 Paris";
        self::assertEquals($expected, $address->getFormattedAddress());
    }

    /** @test */
    public function it_formats_english_address(): void
    {
        $address = PostalAddress::create(
            '10 Downing Street',
            'SW1A 1AA',
            'London',
            Country::EN
        );

        // EN format: Street \n City \n PostalCode
        $expected = "10 Downing Street\nLondon\nSW1A 1AA";
        self::assertEquals($expected, $address->getFormattedAddress());
    }

    /** @test */
    public function it_formats_address_on_one_line(): void
    {
        $address = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        $expected = '10 Rue de la Paix, 75001 Paris, FR';
        self::assertEquals($expected, $address->getFormattedAddressOneLine());
    }

    /** @test */
    public function it_compares_two_addresses_by_value(): void
    {
        $address1 = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        $address2 = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        self::assertTrue($address1->equals($address2));
    }

    /** @test */
    public function it_returns_false_when_comparing_different_addresses(): void
    {
        $address1 = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        $address2 = PostalAddress::create(
            '20 Avenue des Champs-Élysées',
            '75008',
            'Paris',
            Country::FR
        );

        self::assertFalse($address1->equals($address2));
    }

    /** @test */
    public function it_casts_to_string(): void
    {
        $address = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        $expected = "10 Rue de la Paix\n75001 Paris";
        self::assertEquals($expected, (string) $address);
    }

    /** @test */
    public function it_is_immutable(): void
    {
        $address = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        // Then: readonly class (no setters possible)
        $reflection = new \ReflectionClass($address);
        self::assertTrue($reflection->isReadOnly());
    }

    /** @test */
    public function it_validates_spanish_postal_code(): void
    {
        $address = PostalAddress::create(
            'Calle Mayor 1',
            '28013',
            'Madrid',
            Country::ES
        );

        self::assertEquals('28013', $address->getPostalCode());
    }

    /** @test */
    public function it_validates_italian_postal_code(): void
    {
        $address = PostalAddress::create(
            'Via del Corso 1',
            '00186',
            'Roma',
            Country::IT
        );

        self::assertEquals('00186', $address->getPostalCode());
    }
}
```

**Actions:**
- Créer tests pour création de base (street, postalCode, city, country)
- Créer tests de validation code postal par pays (FR: 5 digits, EN: alphanumeric SW1A 1AA, DE: 5 digits, ES: 5 digits, IT: 5 digits, NL: 1234AB, BE: 4 digits)
- Créer tests de validation ville (max 100, caractères valides, accents, apostrophes)
- Créer tests de validation rue (max 255, non vide, caractères valides)
- Créer tests de normalisation (street, postalCode uppercase, city capitalize)
- Créer tests de formatage multilignes par pays (FR vs EN différents)
- Créer tests de formatage une ligne
- Créer tests de comparaison `equals()`
- Créer tests de casting `__toString()`
- Créer tests d'immutabilité (readonly)
- Créer tests avec adresses internationales (accents : Saint-Étienne, apostrophes : L'Aquila)
- Créer tests de codes postaux invalides par pays
- Créer tests de villes/rues trop longues
- Créer tests de caractères invalides (XSS prevention)
- Couverture ≥ 90%

---

### [TEST] Créer tests d'intégration Doctrine Type (1h)

**Fichier:** `tests/Integration/Infrastructure/Persistence/Doctrine/Type/PostalAddressTypeTest.php`

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\ValueObject\Country;
use App\Domain\Shared\ValueObject\PostalAddress;
use App\Infrastructure\Persistence\Doctrine\Type\PostalAddressType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

final class PostalAddressTypeTest extends TestCase
{
    private PostalAddressType $type;
    private PostgreSQLPlatform $platform;

    protected function setUp(): void
    {
        if (!Type::hasType(PostalAddressType::NAME)) {
            Type::addType(PostalAddressType::NAME, PostalAddressType::class);
        }

        $this->type = Type::getType(PostalAddressType::NAME);
        $this->platform = new PostgreSQLPlatform();
    }

    /** @test */
    public function it_converts_null_to_php_value(): void
    {
        $result = $this->type->convertToPHPValue(null, $this->platform);

        self::assertNull($result);
    }

    /** @test */
    public function it_converts_valid_json_to_postal_address(): void
    {
        // Given
        $json = json_encode([
            'street' => '10 Rue de la Paix',
            'postalCode' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ]);

        // When
        $result = $this->type->convertToPHPValue($json, $this->platform);

        // Then
        self::assertInstanceOf(PostalAddress::class, $result);
        self::assertEquals('10 Rue de la Paix', $result->getStreet());
        self::assertEquals('75001', $result->getPostalCode());
        self::assertEquals('Paris', $result->getCity());
        self::assertEquals(Country::FR, $result->getCountry());
    }

    /** @test */
    public function it_converts_postal_address_to_database_value(): void
    {
        // Given
        $address = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        // When
        $result = $this->type->convertToDatabaseValue($address, $this->platform);

        // Then
        self::assertIsString($result);

        $decoded = json_decode($result, true);
        self::assertEquals('10 Rue de la Paix', $decoded['street']);
        self::assertEquals('75001', $decoded['postalCode']);
        self::assertEquals('Paris', $decoded['city']);
        self::assertEquals('FR', $decoded['country']);
    }

    /** @test */
    public function it_converts_null_postal_address_to_null_database_value(): void
    {
        $result = $this->type->convertToDatabaseValue(null, $this->platform);

        self::assertNull($result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_json_format(): void
    {
        // Expect
        $this->expectException(ConversionException::class);

        // When: Missing required key 'city'
        $json = json_encode([
            'street' => '10 Rue de la Paix',
            'postalCode' => '75001',
            'country' => 'FR',
        ]);

        $this->type->convertToPHPValue($json, $this->platform);
    }

    /** @test */
    public function it_throws_exception_for_invalid_type_to_database(): void
    {
        // Expect
        $this->expectException(ConversionException::class);

        // When: Wrong type (string instead of PostalAddress)
        $this->type->convertToDatabaseValue('invalid', $this->platform);
    }

    /** @test */
    public function it_has_correct_name(): void
    {
        self::assertEquals('postal_address', $this->type->getName());
    }

    /** @test */
    public function it_requires_sql_comment_hint(): void
    {
        self::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    /** @test */
    public function it_returns_postal_address_if_already_postal_address_instance(): void
    {
        // Given
        $address = PostalAddress::create(
            '10 Rue de la Paix',
            '75001',
            'Paris',
            Country::FR
        );

        // When
        $result = $this->type->convertToPHPValue($address, $this->platform);

        // Then: Same instance
        self::assertSame($address, $result);
    }

    /** @test */
    public function it_handles_english_postal_code_with_space(): void
    {
        // Given
        $json = json_encode([
            'street' => '10 Downing Street',
            'postalCode' => 'SW1A 1AA',
            'city' => 'London',
            'country' => 'EN',
        ]);

        // When
        $result = $this->type->convertToPHPValue($json, $this->platform);

        // Then
        self::assertEquals('SW1A 1AA', $result->getPostalCode());
    }

    /** @test */
    public function it_handles_addresses_with_special_characters(): void
    {
        // Given: Street with apostrophe, accented city
        $json = json_encode([
            'street' => "10 Rue de l'Église",
            'postalCode' => '42000',
            'city' => 'Saint-Étienne',
            'country' => 'FR',
        ]);

        // When
        $result = $this->type->convertToPHPValue($json, $this->platform);

        // Then
        self::assertEquals("10 Rue de l'Église", $result->getStreet());
        self::assertEquals('Saint-Étienne', $result->getCity());
    }
}
```

**Actions:**
- setUp() avec Type::addType() pour enregistrer le type
- Tests de conversion NULL → PHP et PHP → NULL
- Tests de conversion JSON valide → PostalAddress VO
- Tests de conversion PostalAddress VO → JSON
- Tests de JSON invalide (clés manquantes : street, postalCode, city, country)
- Tests de type invalide (string au lieu de PostalAddress)
- Tests de getName() retourne 'postal_address'
- Tests de requiresSQLCommentHint() retourne true
- Tests de passthrough si déjà PostalAddress instance
- Tests avec codes postaux contenant espaces (EN: "SW1A 1AA")
- Tests avec caractères spéciaux (apostrophes, accents)
- Utiliser PostgreSQLPlatform pour les tests

---

### [DOC] Documenter PostalAddress usage (0.5h)

**Fichier:** `.claude/examples/value-object-postaladdress.md`

```markdown
# Value Object: PostalAddress

## Caractéristiques

- **Type-safe**: Impossible de passer une string à la place d'un PostalAddress
- **Validé**: Code postal validé selon le pays (formats différents par pays)
- **Immutable**: `readonly` class, aucune modification possible après création
- **i18n Support**: Supporte 7 pays européens (FR, EN, DE, ES, IT, NL, BE)
- **Multiple formats**: Multilignes (standard), une ligne (compact), par pays

## Création

### Méthode create() - Création explicite

```php
use App\Domain\Shared\ValueObject\PostalAddress;
use App\Domain\Shared\ValueObject\Country;

// France
$address = PostalAddress::create(
    street: '10 Rue de la Paix',
    postalCode: '75001',
    city: 'Paris',
    country: Country::FR
);

// Angleterre (format différent)
$address = PostalAddress::create(
    street: '10 Downing Street',
    postalCode: 'SW1A 1AA',
    city: 'London',
    country: Country::EN
);

// Pays-Bas (format 1234AB)
$address = PostalAddress::create(
    street: 'Dam 1',
    postalCode: '1012AB',
    city: 'Amsterdam',
    country: Country::NL
);
```

## Validation par pays

### Codes postaux supportés

| Pays | Format | Exemple | Pattern |
|------|--------|---------|---------|
| **FR** | 5 chiffres | 75001 | `/^[0-9]{5}$/` |
| **DE** | 5 chiffres | 10115 | `/^[0-9]{5}$/` |
| **ES** | 5 chiffres | 28013 | `/^[0-9]{5}$/` |
| **IT** | 5 chiffres | 00186 | `/^[0-9]{5}$/` |
| **BE** | 4 chiffres | 1000 | `/^[0-9]{4}$/` |
| **NL** | 4 chiffres + 2 lettres | 1012AB | `/^[1-9][0-9]{3}[A-Z]{2}$/` |
| **EN** | Alphanumeric | SW1A 1AA | `/^[A-Z]{1,2}[0-9]{1,2}[A-Z]?[0-9][A-Z]{2}$/` |

### Validation automatique

```php
// ✅ Valid FR postal code
$address = PostalAddress::create(
    '123 Avenue des Champs-Élysées',
    '75008',
    'Paris',
    Country::FR
);

// ❌ Invalid FR postal code (4 digits instead of 5)
try {
    $address = PostalAddress::create(
        '10 Rue de la Paix',
        '7500', // ❌ Invalid
        'Paris',
        Country::FR
    );
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage();
    // "Invalid postal code format for country FR: 7500"
}

// ✅ Valid EN postal code (with space)
$address = PostalAddress::create(
    '10 Downing Street',
    'SW1A 1AA',
    'London',
    Country::EN
);

// ✅ Valid NL postal code (4 digits + 2 letters)
$address = PostalAddress::create(
    'Herengracht 1',
    '1012AB',
    'Amsterdam',
    Country::NL
);
```

## Formatage

### Multilignes (par pays)

```php
// France/Germany/Spain/Italy/Belgium/Netherlands: Street \n PostalCode City
$addressFR = PostalAddress::create(
    '10 Rue de la Paix',
    '75001',
    'Paris',
    Country::FR
);

echo $addressFR->getFormattedAddress();
// Output:
// 10 Rue de la Paix
// 75001 Paris

// England: Street \n City \n PostalCode
$addressEN = PostalAddress::create(
    '10 Downing Street',
    'SW1A 1AA',
    'London',
    Country::EN
);

echo $addressEN->getFormattedAddress();
// Output:
// 10 Downing Street
// London
// SW1A 1AA
```

### Une ligne (compact)

```php
$address = PostalAddress::create(
    '10 Rue de la Paix',
    '75001',
    'Paris',
    Country::FR
);

echo $address->getFormattedAddressOneLine();
// Output: 10 Rue de la Paix, 75001 Paris, FR
```

### __toString() automatique

```php
$address = PostalAddress::create(
    '10 Rue de la Paix',
    '75001',
    'Paris',
    Country::FR
);

echo $address; // Calls __toString()
// Output:
// 10 Rue de la Paix
// 75001 Paris
```

## Comparaison

### equals() - Comparaison par valeur

```php
$address1 = PostalAddress::create(
    '10 Rue de la Paix',
    '75001',
    'Paris',
    Country::FR
);

$address2 = PostalAddress::create(
    '10 Rue de la Paix',
    '75001',
    'Paris',
    Country::FR
);

if ($address1->equals($address2)) {
    echo 'Addresses are equal';
}

// ✅ Compare tous les composants: street, postalCode, city, country
```

## Usage dans une Entity

### Client avec PostalAddress

```php
<?php

namespace App\Domain\Client\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\PostalAddress;

final class Client
{
    private ClientId $id;
    private string $name;
    private Email $email;
    private PostalAddress $billingAddress;
    private ?PostalAddress $shippingAddress = null;

    public static function create(
        ClientId $id,
        string $name,
        Email $email,
        PostalAddress $billingAddress
    ): self {
        $client = new self();
        $client->id = $id;
        $client->name = $name;
        $client->email = $email;
        $client->billingAddress = $billingAddress;

        return $client;
    }

    public function updateBillingAddress(PostalAddress $address): void
    {
        $this->billingAddress = $address;
    }

    public function setShippingAddress(?PostalAddress $address): void
    {
        $this->shippingAddress = $address;
    }

    public function getBillingAddress(): PostalAddress
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): ?PostalAddress
    {
        return $this->shippingAddress;
    }
}
```

## Usage dans Doctrine Mapping

### Mapping XML avec JSON type

```xml
<!-- Infrastructure/Persistence/Doctrine/Mapping/Client.orm.xml -->
<doctrine-mapping>
    <entity name="App\Domain\Client\Entity\Client" table="client">
        <id name="id" type="client_id">
            <generator strategy="NONE"/>
        </id>

        <!-- ✅ PostalAddress stocké en JSON -->
        <field name="billingAddress"
               type="postal_address"
               column="billing_address"
               nullable="false"/>

        <field name="shippingAddress"
               type="postal_address"
               column="shipping_address"
               nullable="true"/>
    </entity>
</doctrine-mapping>
```

### SQL Schema

```sql
CREATE TABLE client (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,

    -- ✅ JSON storage for PostalAddress VO
    billing_address JSON NOT NULL,
    shipping_address JSON NULL,

    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

-- Example JSON:
-- {"street":"10 Rue de la Paix","postalCode":"75001","city":"Paris","country":"FR"}
```

## Usage dans un Use Case

### CreateClientUseCase

```php
<?php

namespace App\Application\Client\UseCase\CreateClient;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\PostalAddress;
use App\Domain\Shared\ValueObject\Country;

final readonly class CreateClientUseCase
{
    public function execute(CreateClientCommand $command): ClientId
    {
        $clientId = ClientId::generate();

        // ✅ Parse address from command
        $billingAddress = PostalAddress::create(
            street: $command->billingStreet,
            postalCode: $command->billingPostalCode,
            city: $command->billingCity,
            country: Country::from($command->billingCountry)
        );

        $client = Client::create(
            $clientId,
            $command->name,
            Email::fromString($command->email),
            $billingAddress
        );

        $this->clientRepository->save($client);

        return $clientId;
    }
}
```

## Usage dans un Controller

### Validation et parsing

```php
<?php

namespace App\Presentation\Controller\Web;

use App\Domain\Shared\ValueObject\PostalAddress;
use App\Domain\Shared\ValueObject\Country;
use Symfony\Component\HttpFoundation\Request;

final class ClientController extends AbstractController
{
    #[Route('/clients/create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        try {
            // ✅ Parse and validate address
            $billingAddress = PostalAddress::create(
                street: $request->request->get('billing_street'),
                postalCode: $request->request->get('billing_postal_code'),
                city: $request->request->get('billing_city'),
                country: Country::from($request->request->get('billing_country'))
            );

            // Pass to Use Case...

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Adresse invalide : ' . $e->getMessage());
            return $this->redirectToRoute('client_new');
        }

        // ...
    }
}
```

## Symfony Form avec validation

### ClientFormType

```php
<?php

namespace App\Presentation\Form;

use App\Domain\Shared\ValueObject\Country;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

final class ClientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('billingStreet', TextType::class, [
                'label' => 'Rue',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('billingPostalCode', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [
                    new Assert\NotBlank(),
                    // Validation dynamique par pays dans Callback
                    new Assert\Callback(function ($value, $context) {
                        $country = $context->getRoot()->get('billingCountry')->getData();
                        try {
                            PostalAddress::create(
                                '1 Rue Test', // Dummy street
                                $value,
                                'Test City', // Dummy city
                                Country::from($country)
                            );
                        } catch (\InvalidArgumentException $e) {
                            $context->buildViolation($e->getMessage())
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('billingCity', TextType::class, [
                'label' => 'Ville',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('billingCountry', ChoiceType::class, [
                'label' => 'Pays',
                'choices' => [
                    'France' => 'FR',
                    'England' => 'EN',
                    'Germany' => 'DE',
                    'Spain' => 'ES',
                    'Italy' => 'IT',
                    'Netherlands' => 'NL',
                    'Belgium' => 'BE',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]);
    }
}
```

## Domain Event avec PostalAddress

### ClientAddressUpdatedEvent

```php
<?php

namespace App\Domain\Client\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Shared\ValueObject\PostalAddress;

final readonly class ClientAddressUpdatedEvent implements DomainEventInterface
{
    public function __construct(
        public ClientId $clientId,
        public PostalAddress $oldAddress,
        public PostalAddress $newAddress,
        public \DateTimeImmutable $occurredOn = new \DateTimeImmutable(),
    ) {}

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
```

## Cas d'usage avancés

### Adresses avec caractères spéciaux

```php
// France: Accents et apostrophes
$address = PostalAddress::create(
    street: "10 Rue de l'Église",
    postalCode: '42000',
    city: 'Saint-Étienne',
    country: Country::FR
);

// Italy: Apostrophes
$address = PostalAddress::create(
    street: 'Via Roma 10',
    postalCode: '67100',
    city: "L'Aquila",
    country: Country::IT
);

// Spain: Accents
$address = PostalAddress::create(
    street: 'Calle de Alcalá 123',
    postalCode: '28009',
    city: 'Madrid',
    country: Country::ES
);
```

### Formatage contextuel

```php
$address = PostalAddress::create(
    '10 Rue de la Paix',
    '75001',
    'Paris',
    Country::FR
);

// Display on envelope/label (multiline)
echo $address->getFormattedAddress();
// 10 Rue de la Paix
// 75001 Paris

// Display in form input (one line)
echo $address->getFormattedAddressOneLine();
// 10 Rue de la Paix, 75001 Paris, FR

// Display in email template
echo $address; // __toString()
// 10 Rue de la Paix
// 75001 Paris
```

### Comparaison d'adresses

```php
$address1 = PostalAddress::create(
    '10 Rue de la Paix',
    '75001',
    'Paris',
    Country::FR
);

$address2 = PostalAddress::create(
    '10 Rue de la Paix',
    '75001',
    'Paris',
    Country::FR
);

if ($address1->equals($address2)) {
    echo 'Same address';
}

// Note: Normalization ensures "  paris  " equals "Paris"
```

## Avantages vs types primitifs

### ❌ AVANT: Types primitifs dispersés

```php
class Client
{
    private string $street;
    private string $postalCode;
    private string $city;
    private string $country;

    // Validation dispersée dans ClientService, OrderService, etc.
    // Formatage dispersé dans Twig filters, templates, etc.
    // Type safety faible (PHPStan ne détecte pas si on passe city à la place de street)
}
```

### ✅ APRÈS: PostalAddress Value Object

```php
class Client
{
    private PostalAddress $billingAddress;
    private ?PostalAddress $shippingAddress;

    // ✅ Validation centralisée dans PostalAddress (un seul endroit)
    // ✅ Formatage cohérent via getFormattedAddress()
    // ✅ Type safety: impossible de passer une string à la place de PostalAddress
    // ✅ Validation par pays automatique
}
```

## Migration depuis colonnes string

### Phase 1: Créer le Value Object

```php
// Créer PostalAddress.php (déjà fait dans cette US)
```

### Phase 2: Créer le Doctrine Type

```php
// Créer PostalAddressType.php (déjà fait dans cette US)
```

### Phase 3: Migrer les entités

```php
// AVANT
class Client
{
    #[ORM\Column(type: 'string', length: 255)]
    private string $street;

    #[ORM\Column(type: 'string', length: 10)]
    private string $postalCode;

    #[ORM\Column(type: 'string', length: 100)]
    private string $city;

    #[ORM\Column(type: 'string', length: 2)]
    private string $country;
}

// APRÈS
class Client
{
    // Removed individual columns, use PostalAddress VO
    private PostalAddress $billingAddress;
    private ?PostalAddress $shippingAddress = null;
}
```

### Phase 4: Migration base de données

```php
// Migration SQL
final class Version20260113AddPostalAddressToClient extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // 1. Add new JSON column
        $this->addSql('ALTER TABLE client ADD billing_address JSON DEFAULT NULL');

        // 2. Migrate existing data
        $this->addSql("
            UPDATE client
            SET billing_address = json_build_object(
                'street', street,
                'postalCode', postal_code,
                'city', city,
                'country', country
            )
            WHERE street IS NOT NULL
        ");

        // 3. Make NOT NULL
        $this->addSql('ALTER TABLE client ALTER COLUMN billing_address SET NOT NULL');

        // 4. Drop old columns
        $this->addSql('ALTER TABLE client DROP COLUMN street');
        $this->addSql('ALTER TABLE client DROP COLUMN postal_code');
        $this->addSql('ALTER TABLE client DROP COLUMN city');
        $this->addSql('ALTER TABLE client DROP COLUMN country');
    }

    public function down(Schema $schema): void
    {
        // Reverse migration
        $this->addSql('ALTER TABLE client ADD street VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD postal_code VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD city VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD country VARCHAR(2) DEFAULT NULL');

        $this->addSql("
            UPDATE client
            SET
                street = billing_address->>'street',
                postal_code = billing_address->>'postalCode',
                city = billing_address->>'city',
                country = billing_address->>'country'
        ");

        $this->addSql('ALTER TABLE client DROP COLUMN billing_address');
    }
}
```

## Patterns i18n

### AddressFormatter service

```php
<?php

namespace App\Domain\Shared\Service;

use App\Domain\Shared\ValueObject\Country;
use App\Domain\Shared\ValueObject\PostalAddress;

/**
 * Format addresses according to country conventions.
 */
final readonly class AddressFormatter
{
    /**
     * Format address for postal mail.
     */
    public function formatForPostalMail(PostalAddress $address): string
    {
        return match ($address->getCountry()) {
            Country::FR => $this->formatFrench($address),
            Country::EN => $this->formatEnglish($address),
            Country::DE => $this->formatGerman($address),
            default => $address->getFormattedAddress(),
        };
    }

    /**
     * Format address for invoice.
     */
    public function formatForInvoice(PostalAddress $address): string
    {
        // All countries: multiline format
        return $address->getFormattedAddress();
    }

    /**
     * Format address for shipping label.
     */
    public function formatForShippingLabel(PostalAddress $address): string
    {
        return strtoupper($address->getFormattedAddress());
    }

    private function formatFrench(PostalAddress $address): string
    {
        return sprintf(
            "%s\n%s %s\nFRANCE",
            $address->getStreet(),
            $address->getPostalCode(),
            strtoupper($address->getCity())
        );
    }

    private function formatEnglish(PostalAddress $address): string
    {
        return sprintf(
            "%s\n%s\n%s\nUNITED KINGDOM",
            $address->getStreet(),
            strtoupper($address->getCity()),
            $address->getPostalCode()
        );
    }

    private function formatGerman(PostalAddress $address): string
    {
        return sprintf(
            "%s\n%s %s\nDEUTSCHLAND",
            $address->getStreet(),
            $address->getPostalCode(),
            strtoupper($address->getCity())
        );
    }
}
```

### Format par pays

| Pays | Format Postal | Exemple |
|------|--------------|---------|
| **France** | Street \n PostalCode CITY \n FRANCE | 10 Rue de la Paix \n 75001 PARIS \n FRANCE |
| **England** | Street \n CITY \n PostalCode \n UK | 10 Downing Street \n LONDON \n SW1A 1AA \n UNITED KINGDOM |
| **Germany** | Street \n PostalCode City \n DEUTSCHLAND | Unter den Linden 1 \n 10117 BERLIN \n DEUTSCHLAND |

## Anti-patterns

### ❌ Champs séparés non validés

```php
// MAUVAIS: Validation dispersée
class ClientService
{
    public function validateAddress(string $postalCode, string $country): bool
    {
        // Duplication de la validation
        if ($country === 'FR' && !preg_match('/^[0-9]{5}$/', $postalCode)) {
            return false;
        }
        // ...
    }
}

class OrderService
{
    public function validateShippingAddress(string $postalCode): bool
    {
        // ❌ Duplication (même validation dans 2 services)
        if (!preg_match('/^[0-9]{5}$/', $postalCode)) {
            return false;
        }
        // ...
    }
}
```

### ❌ PostalAddress mutable

```php
// MAUVAIS: Mutable (violation immutabilité)
class PostalAddress
{
    private string $street;

    public function setStreet(string $street): void // ❌ Setter
    {
        $this->street = $street;
    }
}
```

### ❌ Formatage dispersé

```php
// MAUVAIS: Formatage dans les templates
{# Template A #}
{{ client.street }}<br>
{{ client.postalCode }} {{ client.city }}

{# Template B #}
{{ order.shippingStreet }}, {{ order.shippingPostalCode }} {{ order.shippingCity }}

{# ❌ Formats incohérents, duplication #}
```

## Checklist

- [ ] Classe `final readonly`
- [ ] Constructor `private` avec validation
- [ ] Factory method `create()` avec normalisation
- [ ] Validation code postal par pays (7 formats)
- [ ] Validation ville/rue (longueur, caractères valides)
- [ ] Méthodes de formatage: `getFormattedAddress()`, `getFormattedAddressOneLine()`
- [ ] Méthode `equals()` pour comparaison
- [ ] Méthode `__toString()`
- [ ] Aucun setter (immutabilité)
- [ ] Support i18n (accents, apostrophes, hyphens)
- [ ] Doctrine Custom Type créé (JSON storage)
- [ ] Type enregistré dans `doctrine.yaml`
- [ ] Tests unitaires couvrent tous les pays
- [ ] Tests d'intégration Doctrine Type
- [ ] Couverture ≥ 90%
- [ ] Documentation complète avec exemples
```

**Actions:**
- Créer `.claude/examples/value-object-postaladdress.md`
- Documenter caractéristiques : type-safe, validé par pays, immutable, i18n support, multiple formats
- Documenter création avec `create()`
- Documenter validation codes postaux par pays (table avec formats)
- Documenter formatage multilignes et une ligne
- Documenter comparaison `equals()`
- Exemples d'usage dans Entity (Client avec billingAddress/shippingAddress)
- Exemples d'usage dans Doctrine Mapping (XML avec JSON type)
- Schéma SQL avec JSON columns
- Exemples d'usage dans Use Case (CreateClientUseCase avec parsing)
- Exemples d'usage dans Controller (validation et gestion erreurs)
- Exemples Symfony Form avec validation dynamique par pays
- Exemples Domain Event (ClientAddressUpdatedEvent)
- Cas avancés : accents (Saint-Étienne), apostrophes (L'Aquila), formatage postal par pays
- Avantages vs primitifs : validation centralisée, formatage cohérent, type safety
- Migration strategy depuis colonnes string (4 phases avec SQL)
- i18n patterns : AddressFormatter service, format par pays, conventions postales
- Anti-patterns : champs séparés, mutable VO, formatage dispersé
- Checklist complète (14 items)

---

### [VALIDATION] Valider avec outils qualité (0.5h)

```bash
# PHPStan niveau max
make phpstan

# Vérifier aucune erreur dans src/Domain/Shared/ValueObject/PostalAddress.php
# Vérifier aucune erreur dans src/Infrastructure/Persistence/Doctrine/Type/PostalAddressType.php

# Deptrac
make deptrac

# Vérifier Domain/Shared/ValueObject ne dépend de rien
# Vérifier Infrastructure/Persistence/Doctrine/Type dépend uniquement de Domain

# Tests
make test

# Vérifier couverture ≥ 90% sur PostalAddress
make test-coverage
```

**Actions:**
- Exécuter `make phpstan` et corriger toute erreur
- Exécuter `make deptrac` pour valider architecture
- Exécuter `make test` pour vérifier tous les tests passent
- Exécuter `make test-coverage` pour vérifier couverture ≥ 90%
- Vérifier aucune dépendance Doctrine dans Domain/Shared/ValueObject

---

## Définition de Done (DoD)

### Code

- [ ] `src/Domain/Shared/ValueObject/PostalAddress.php` créé
- [ ] Classe marquée `final readonly`
- [ ] Constructor `private` avec validation complète
- [ ] Factory method `create(street, postalCode, city, country)` statique
- [ ] Propriétés : `street`, `postalCode`, `city`, `country` (Country enum)
- [ ] Validation code postal par pays (7 formats différents) :
  - [ ] FR: `/^[0-9]{5}$/` (75001)
  - [ ] DE: `/^[0-9]{5}$/` (10115)
  - [ ] ES: `/^[0-9]{5}$/` (28013)
  - [ ] IT: `/^[0-9]{5}$/` (00186)
  - [ ] BE: `/^[0-9]{4}$/` (1000)
  - [ ] NL: `/^[1-9][0-9]{3}[A-Z]{2}$/` (1012AB)
  - [ ] EN: `/^[A-Z]{1,2}[0-9]{1,2}[A-Z]?[0-9][A-Z]{2}$/` (SW1A 1AA)
- [ ] Validation ville : max 100 caractères, caractères valides (Unicode letters, spaces, hyphens, apostrophes)
- [ ] Validation rue : max 255 caractères, non vide, caractères valides
- [ ] Normalisation street : trim, collapse spaces
- [ ] Normalisation postalCode : trim, uppercase, preserve spaces for display
- [ ] Normalisation city : trim, capitalize, collapse spaces
- [ ] Méthode `getStreet()` retourne rue
- [ ] Méthode `getPostalCode()` retourne code postal
- [ ] Méthode `getCity()` retourne ville
- [ ] Méthode `getCountry()` retourne Country enum
- [ ] Méthode `getFormattedAddress()` retourne adresse multilignes selon pays
- [ ] Méthode `getFormattedAddressOneLine()` retourne adresse sur une ligne
- [ ] Méthode `equals(PostalAddress)` compare tous les composants
- [ ] Méthode `__toString()` retourne adresse formatée
- [ ] Aucun setter (immutabilité stricte)
- [ ] Support des 7 pays européens (FR, EN, DE, ES, IT, NL, BE)
- [ ] Support des accents dans villes (Saint-Étienne, Málaga)
- [ ] Support des apostrophes dans villes (L'Aquila, L'Hospitalet)
- [ ] Support des hyphens dans villes (Saint-Denis, Aix-en-Provence)

### Doctrine Integration

- [ ] `src/Infrastructure/Persistence/Doctrine/Type/PostalAddressType.php` créé
- [ ] Étend `JsonType` pour stockage JSON natif PostgreSQL
- [ ] Méthode `convertToPHPValue()` implémentée :
  - [ ] Gère `null` → `null`
  - [ ] Gère `PostalAddress` instance → passthrough
  - [ ] Décode JSON vers array avec `parent::convertToPHPValue()`
  - [ ] Valide présence clés : street, postalCode, city, country
  - [ ] Convertit country string → Country enum avec `Country::from()`
  - [ ] Crée PostalAddress avec `PostalAddress::create()`
  - [ ] Lance `ConversionException` si invalide
- [ ] Méthode `convertToDatabaseValue()` implémentée :
  - [ ] Gère `null` → `null`
  - [ ] Valide type PostalAddress
  - [ ] Convertit vers array avec tous les composants
  - [ ] Encode avec `parent::convertToDatabaseValue()`
  - [ ] Lance `ConversionException` si type invalide
- [ ] Méthode `getName()` retourne `'postal_address'`
- [ ] Méthode `requiresSQLCommentHint()` retourne `true`
- [ ] Type enregistré dans `config/packages/doctrine.yaml` :
  - [ ] Section `types` : `postal_address` → `PostalAddressType::class`
  - [ ] Section `mapping_types` : `postal_address` → `json`
- [ ] Vérification : `make console CMD="dbal:types"` affiche `postal_address`

### Tests Unitaires

- [ ] `tests/Unit/Domain/Shared/ValueObject/PostalAddressTest.php` créé
- [ ] Test : `it_creates_postal_address_with_all_components()`
- [ ] Test : `it_validates_french_postal_code()` (75001 valid)
- [ ] Test : `it_throws_exception_for_invalid_french_postal_code()` (7500 invalid)
- [ ] Test : `it_validates_english_postal_code()` (SW1A 1AA valid)
- [ ] Test : `it_validates_german_postal_code()` (10115 valid)
- [ ] Test : `it_validates_spanish_postal_code()` (28013 valid)
- [ ] Test : `it_validates_italian_postal_code()` (00186 valid)
- [ ] Test : `it_validates_dutch_postal_code()` (1012AB valid)
- [ ] Test : `it_throws_exception_for_invalid_dutch_postal_code()` (0123AB invalid)
- [ ] Test : `it_validates_belgian_postal_code()` (1000 valid)
- [ ] Test : `it_normalizes_street()` (trim, collapse spaces)
- [ ] Test : `it_normalizes_city()` (trim, capitalize, collapse spaces)
- [ ] Test : `it_normalizes_postal_code()` (trim, uppercase)
- [ ] Test : `it_throws_exception_for_empty_street()`
- [ ] Test : `it_throws_exception_for_empty_city()`
- [ ] Test : `it_throws_exception_for_street_too_long()` (> 255 chars)
- [ ] Test : `it_throws_exception_for_city_too_long()` (> 100 chars)
- [ ] Test : `it_throws_exception_for_invalid_street_characters()` (XSS: <script>)
- [ ] Test : `it_supports_accented_cities()` (Saint-Étienne)
- [ ] Test : `it_supports_apostrophes_in_cities()` (L'Aquila)
- [ ] Test : `it_formats_french_address()` (Street \n PostalCode City)
- [ ] Test : `it_formats_english_address()` (Street \n City \n PostalCode)
- [ ] Test : `it_formats_address_on_one_line()`
- [ ] Test : `it_compares_two_addresses_by_value()`
- [ ] Test : `it_returns_false_when_comparing_different_addresses()`
- [ ] Test : `it_casts_to_string()`
- [ ] Test : `it_is_immutable()` (readonly verification)
- [ ] Couverture code ≥ 90% sur PostalAddress
- [ ] Tests s'exécutent en moins de 100ms

### Tests d'Intégration

- [ ] `tests/Integration/Infrastructure/Persistence/Doctrine/Type/PostalAddressTypeTest.php` créé
- [ ] Test : `it_converts_null_to_php_value()`
- [ ] Test : `it_converts_valid_json_to_postal_address()`
- [ ] Test : `it_converts_postal_address_to_database_value()`
- [ ] Test : `it_converts_null_postal_address_to_null_database_value()`
- [ ] Test : `it_throws_exception_for_invalid_json_format()` (missing key)
- [ ] Test : `it_throws_exception_for_invalid_type_to_database()`
- [ ] Test : `it_has_correct_name()` (returns 'postal_address')
- [ ] Test : `it_requires_sql_comment_hint()`
- [ ] Test : `it_returns_postal_address_if_already_postal_address_instance()` (passthrough)
- [ ] Test : `it_handles_english_postal_code_with_space()` (SW1A 1AA)
- [ ] Test : `it_handles_addresses_with_special_characters()` (accents, apostrophes)
- [ ] Utilisation de `PostgreSQLPlatform` pour tests
- [ ] setUp() avec `Type::addType()` registration

### Documentation

- [ ] `.claude/examples/value-object-postaladdress.md` créé
- [ ] Section : Caractéristiques (type-safe, validé par pays, immutable, i18n, formats)
- [ ] Section : Création avec `create()`
- [ ] Section : Validation par pays (table avec formats codes postaux)
- [ ] Section : Formatage multilignes et une ligne
- [ ] Section : Comparaison `equals()`
- [ ] Section : Usage dans Entity (Client avec billingAddress/shippingAddress)
- [ ] Section : Usage dans Doctrine Mapping (XML avec JSON type)
- [ ] Section : SQL Schema (JSON columns)
- [ ] Section : Usage dans Use Case (CreateClientUseCase)
- [ ] Section : Usage dans Controller (validation et erreurs)
- [ ] Section : Symfony Form avec validation dynamique par pays
- [ ] Section : Domain Event (ClientAddressUpdatedEvent)
- [ ] Section : Cas avancés (accents, apostrophes, formatage postal par pays)
- [ ] Section : Avantages vs primitifs (validation centralisée, formatage cohérent, type safety)
- [ ] Section : Migration strategy (4 phases avec SQL)
- [ ] Section : i18n patterns (AddressFormatter service, format par pays)
- [ ] Section : Anti-patterns (champs séparés, mutable VO, formatage dispersé)
- [ ] Checklist complète

### Validation Qualité

- [ ] PHPStan niveau max passe sur `src/Domain/Shared/ValueObject/PostalAddress.php`
- [ ] PHPStan niveau max passe sur `src/Infrastructure/Persistence/Doctrine/Type/PostalAddressType.php`
- [ ] Aucune erreur de type détectée
- [ ] Deptrac valide : Domain/Shared/ValueObject ne dépend de rien
- [ ] Deptrac valide : Infrastructure/Persistence/Doctrine/Type dépend de Domain
- [ ] `make quality` passe sans erreur
- [ ] `make test` passe tous les tests
- [ ] `make test-coverage` affiche ≥ 90% sur PostalAddress
- [ ] Aucune dépendance vers Symfony ou Doctrine dans Domain
- [ ] Propriétés `readonly` détectées par PHPStan
- [ ] Pas de mutateurs (setters) détectés

### Review

- [ ] Code review effectué par Tech Lead
- [ ] Validation des patterns i18n (7 pays)
- [ ] Validation de la cohérence avec Email, PhoneNumber, Money, PersonName
- [ ] Validation de la stratégie JSON storage

### Git

- [ ] Commit avec message : `feat(domain): create PostalAddress value object with country-specific validation`
- [ ] Format Conventional Commits respecté

---

## Notes techniques

### Formats codes postaux par pays

#### France (FR)
- **Format:** 5 chiffres
- **Pattern:** `/^[0-9]{5}$/`
- **Exemples:** 75001 (Paris), 13001 (Marseille), 69001 (Lyon)

#### Angleterre (EN)
- **Format:** Alphanumeric (1-2 letters + 1-2 digits + optional letter + 1 digit + 2 letters)
- **Pattern:** `/^[A-Z]{1,2}[0-9]{1,2}[A-Z]?[0-9][A-Z]{2}$/i`
- **Exemples:** SW1A 1AA (Londres), M1 1AA (Manchester), EC1A 1BB (City of London)
- **Note:** Espaces permis dans l'affichage (SW1A 1AA), retirés pour validation

#### Allemagne (DE)
- **Format:** 5 chiffres
- **Pattern:** `/^[0-9]{5}$/`
- **Exemples:** 10115 (Berlin), 80331 (Munich), 20095 (Hamburg)

#### Espagne (ES)
- **Format:** 5 chiffres
- **Pattern:** `/^[0-9]{5}$/`
- **Exemples:** 28013 (Madrid), 08001 (Barcelona), 41001 (Sevilla)

#### Italie (IT)
- **Format:** 5 chiffres
- **Pattern:** `/^[0-9]{5}$/`
- **Exemples:** 00186 (Rome), 20121 (Milan), 50122 (Florence)

#### Pays-Bas (NL)
- **Format:** 4 chiffres + 2 lettres (espace optionnel)
- **Pattern:** `/^[1-9][0-9]{3}[A-Z]{2}$/`
- **Exemples:** 1012AB (Amsterdam), 3011AD (Rotterdam)
- **Note:** Ne peut pas commencer par 0

#### Belgique (BE)
- **Format:** 4 chiffres
- **Pattern:** `/^[0-9]{4}$/`
- **Exemples:** 1000 (Bruxelles), 2000 (Anvers), 9000 (Gand)

### Formatage par pays

#### France/Allemagne/Espagne/Italie/Belgique/Pays-Bas
```
Street
PostalCode City
```

Exemple FR:
```
10 Rue de la Paix
75001 Paris
```

#### Angleterre
```
Street
City
PostalCode
```

Exemple EN:
```
10 Downing Street
London
SW1A 1AA
```

### Normalisation automatique

1. **Street**:
   - `trim()` : retire espaces début/fin
   - `preg_replace('/\s+/', ' ')` : collapse espaces multiples en un seul

2. **PostalCode**:
   - `trim()` : retire espaces début/fin
   - `mb_strtoupper()` : uppercase (EN: "sw1a 1aa" → "SW1A 1AA")
   - Espaces préservés pour affichage (EN postal codes)
   - Espaces retirés pour validation (str_replace(' ', ''))

3. **City**:
   - `trim()` : retire espaces début/fin
   - `preg_replace('/\s+/', ' ')` : collapse espaces multiples
   - `mb_convert_case(MB_CASE_TITLE)` : capitalize (paris → Paris)

### Caractères valides

1. **Street**: Lettres (Unicode), chiffres, espaces, hyphens, apostrophes, virgules, points
   - Pattern: `/^[\p{L}\p{N}\s\-\',\.]+$/u`
   - Exemples valides: "10 Rue de la Paix", "123, Avenue des Champs-Élysées", "1.5 Rue du Commerce"

2. **City**: Lettres (Unicode), espaces, hyphens, apostrophes
   - Pattern: `/^[\p{L}\s\-\']+$/u`
   - Exemples valides: "Paris", "Saint-Étienne", "L'Aquila", "Aix-en-Provence"

3. **PostalCode**: Dépend du pays (voir patterns ci-dessus)

### Storage JSON

```json
{
  "street": "10 Rue de la Paix",
  "postalCode": "75001",
  "city": "Paris",
  "country": "FR"
}
```

**Avantages JSON vs colonnes séparées:**
- Cohésion : street, postalCode, city, country toujours ensemble
- Extensibilité : facile d'ajouter address line 2, region, etc.
- Migration simplifiée : une seule colonne JSON vs 4 colonnes string

### PostgreSQL JSON Support

```sql
-- Création table avec JSON
CREATE TABLE client (
    id UUID PRIMARY KEY,
    billing_address JSON NOT NULL,
    shipping_address JSON NULL
);

-- Exemple insert
INSERT INTO client (id, billing_address)
VALUES (
    '123e4567-e89b-12d3-a456-426614174000',
    '{"street":"10 Rue de la Paix","postalCode":"75001","city":"Paris","country":"FR"}'
);

-- Query JSON fields
SELECT
    billing_address->>'street' AS street,
    billing_address->>'postalCode' AS postal_code,
    billing_address->>'city' AS city
FROM client;

-- WHERE clause sur JSON
SELECT * FROM client
WHERE billing_address->>'city' = 'Paris';

-- Index sur JSON field
CREATE INDEX idx_client_billing_city
ON client ((billing_address->>'city'));
```

### Usage complet dans Client Entity

```php
<?php

namespace App\Domain\Client\Entity;

final class Client
{
    private ClientId $id;
    private PersonName $contactName;
    private Email $email;
    private PostalAddress $billingAddress;
    private ?PostalAddress $shippingAddress = null;

    public static function create(
        ClientId $id,
        PersonName $contactName,
        Email $email,
        PostalAddress $billingAddress
    ): self {
        $client = new self();
        $client->id = $id;
        $client->contactName = $contactName;
        $client->email = $email;
        $client->billingAddress = $billingAddress;

        $client->recordEvent(new ClientCreated(
            $id,
            $email,
            $billingAddress
        ));

        return $client;
    }

    public function updateBillingAddress(PostalAddress $newAddress): void
    {
        $oldAddress = $this->billingAddress;
        $this->billingAddress = $newAddress;

        $this->recordEvent(new ClientAddressUpdatedEvent(
            $this->id,
            $oldAddress,
            $newAddress
        ));
    }

    public function setShippingAddress(?PostalAddress $address): void
    {
        $this->shippingAddress = $address;
    }

    public function usesBillingAddressForShipping(): bool
    {
        if ($this->shippingAddress === null) {
            return true;
        }

        return $this->billingAddress->equals($this->shippingAddress);
    }

    public function getBillingAddress(): PostalAddress
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): ?PostalAddress
    {
        return $this->shippingAddress ?? $this->billingAddress;
    }
}
```

---

## Dépendances

### Bloquantes (doivent être faites avant)

- **US-001**: Structure Domain créée (nécessite `src/Domain/Shared/ValueObject/`)
- **US-011**: Country enum créé (nécessité pour propriété `country`)

### Bloque (ne peuvent être faites qu'après)

- **US-002**: Extraction Client entity (utilisera PostalAddress)
- **US-006**: Extraction Order entity (utilisera PostalAddress pour shipping)
- **US-018**: Remplacer types primitifs par VOs (migration street/postalCode/city/country → PostalAddress)

---

## Références

- `.claude/rules/02-architecture-clean-ddd.md` (lignes 45-155, Value Objects Domain purs)
- `.claude/rules/18-value-objects.md` (Template Value Object et Doctrine Custom Type)
- `.claude/rules/16-i18n.md` (lignes 13-46, Country enum et formats par pays)
- `.claude/examples/value-object-examples.md` (Money, Email, DateRange exemples)
- **EPIC-002** : Implémentation des Value Objects (lignes 214-267, Template Value Object Email)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 75-108, problème types primitifs)
- **Livre:** *Domain-Driven Design* - Eric Evans, Chapitre 5 (Value Objects)
- **Norme:** [Universal Postal Union - Addressing](https://www.upu.int/en/Universal-Postal-Union/Activities/Addressing)
- **Référence:** Postal code formats par pays (Wikipedia, official postal services)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |

---

## Notes

- **Complexité**: 3 points (validation par pays plus complexe que Email/PersonName)
- **Préalable**: Lire `.claude/rules/16-i18n.md` pour comprendre patterns i18n
- **Préalable**: Lire `.claude/rules/18-value-objects.md` pour template Value Object
- **TDD obligatoire**: Tests AVANT implémentation (RED → GREEN → REFACTOR)
- **Validation stricte**: Codes postaux vérifiés avec patterns regex par pays
- **i18n critical**: Support 7 pays européens avec formats différents
- **JSON storage**: PostgreSQL JSON natif via JsonType Doctrine
- **Normalisation**: Automatique (trim, uppercase, capitalize)
- **Formatage**: Différent par pays (EN: City avant PostalCode)
- **Immutabilité**: `final readonly class` enforce immutabilité
- **Type safety**: Impossible de passer string/array à la place de PostalAddress
- **Definition of Done**: Voir `/Users/tmonier/Projects/hotones/project-management/prd.md` section "Définition de Done"
