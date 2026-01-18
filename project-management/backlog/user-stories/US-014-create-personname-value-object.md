# US-014: Créer Value Object PersonName (nom + prénom)

**EPIC:** [EPIC-002](../epics/EPIC-002-value-objects.md) - Implémentation des Value Objects
**Priorité:** 🔴 CRITIQUE
**Points:** 2
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer un Value Object PersonName pour représenter les noms de personnes (prénom + nom)
**Afin de** centraliser la validation et le formatage des noms, supporter l'internationalisation, et éviter les types primitifs

---

## Critères d'acceptation

### GIVEN: Le projet a la structure Domain créée (US-001)

**WHEN:** Je crée le Value Object PersonName

**THEN:**
- [ ] Classe `src/Domain/Shared/ValueObject/PersonName.php` créée
- [ ] Classe marquée `final readonly`
- [ ] Propriétés `firstName` et `lastName` privées
- [ ] Constructor `private` avec validation
- [ ] Factory method `create(firstName, lastName)` statique
- [ ] Factory method `fromString(fullName)` avec parsing intelligent
- [ ] Méthode `getFullName()` pour affichage complet
- [ ] Méthode `getFirstName()` pour prénom seul
- [ ] Méthode `getLastName()` pour nom seul
- [ ] Méthode `getInitials()` pour initiales (ex: "J.D.")
- [ ] Méthode `getLastNameFirst()` pour format "NOM Prénom"
- [ ] Méthode `equals(PersonName)` pour comparaison
- [ ] Méthode `__toString()` retourne le nom complet
- [ ] Validation: firstName et lastName non vides
- [ ] Validation: longueur max 100 caractères par composant
- [ ] Support des caractères internationaux (accents, trémas, etc.)
- [ ] Support des noms composés (Jean-Pierre, Marie-Claire)
- [ ] Support des particules (de, van, von, etc.)
- [ ] Aucun setter (immutabilité)

### GIVEN: Le Value Object PersonName existe

**WHEN:** J'exécute PHPStan niveau max sur src/Domain/

**THEN:**
- [ ] Aucune erreur PHPStan
- [ ] Type `string` remplacé par `PersonName` dans suggestions
- [ ] Aucune dépendance externe détectée

### GIVEN: Le Value Object PersonName existe

**WHEN:** J'exécute les tests unitaires

**THEN:**
- [ ] Tests unitaires passent sans dépendances externes
- [ ] Tests s'exécutent en moins de 100ms
- [ ] Couverture code ≥ 90% sur PersonName
- [ ] Tests couvrent:
  - Création via `create()`
  - Parsing via `fromString()`
  - Validation (noms vides, trop longs)
  - Formatage (fullName, initials, lastNameFirst)
  - Comparaison `equals()`
  - Noms composés (Jean-Pierre)
  - Noms avec particules (de Gaulle)
  - Noms avec accents (François, José)
  - Edge cases (un seul mot, espaces multiples)

---

## Tâches techniques

### [DOMAIN] Créer Value Object PersonName (2h)

**Avant (types primitifs):**
```php
<?php

namespace App\Entity;

class Client
{
    #[ORM\Column(type: 'string', length: 255)]
    private string $nom; // ❌ Type primitif

    #[ORM\Column(type: 'string', length: 255)]
    private string $prenom; // ❌ Type primitif

    // ❌ Validation dispersée
    public function setNom(string $nom): void
    {
        if (strlen($nom) > 100) {
            throw new \InvalidArgumentException('Nom trop long');
        }
        $this->nom = $nom;
    }

    // ❌ Formatage dupliqué
    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }
}
```

**Après (Value Object):**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

/**
 * Person Name Value Object.
 *
 * Represents a person's full name with first name and last name.
 *
 * Characteristics:
 * - Immutable (final readonly)
 * - Type-safe
 * - Validated (non-empty, max length)
 * - Supports internationalization (accents, hyphens, apostrophes)
 * - Supports compound names (Jean-Pierre, Marie-Claire)
 * - Multiple formatting options (full, initials, lastNameFirst)
 *
 * Examples:
 * - PersonName::create('Jean', 'Dupont') → "Jean Dupont"
 * - PersonName::fromString('Jean Dupont') → firstName: "Jean", lastName: "Dupont"
 * - PersonName::fromString('Jean-Pierre de la Fontaine') → "Jean-Pierre de la Fontaine"
 * - getInitials() → "J.D."
 * - getLastNameFirst() → "DUPONT Jean"
 */
final readonly class PersonName
{
    private const int MAX_LENGTH = 100;

    private function __construct(
        private string $firstName,
        private string $lastName,
    ) {
        $this->validate();
    }

    /**
     * Create a PersonName with explicit first and last name.
     *
     * @param string $firstName First name (prénom)
     * @param string $lastName Last name (nom de famille)
     * @throws \InvalidArgumentException if validation fails
     */
    public static function create(string $firstName, string $lastName): self
    {
        return new self(
            self::normalize($firstName),
            self::normalize($lastName)
        );
    }

    /**
     * Create a PersonName from a full name string.
     *
     * Parsing logic:
     * - "Jean Dupont" → firstName: "Jean", lastName: "Dupont"
     * - "Jean-Pierre de la Fontaine" → firstName: "Jean-Pierre", lastName: "de la Fontaine"
     * - "Dupont" → firstName: "", lastName: "Dupont"
     *
     * @param string $fullName Full name string
     * @throws \InvalidArgumentException if validation fails
     */
    public static function fromString(string $fullName): self
    {
        $fullName = self::normalize($fullName);

        // Split by space
        $parts = preg_split('/\s+/', $fullName);

        if (count($parts) === 1) {
            // Single name → treat as last name
            return new self('', $parts[0]);
        }

        // First part = first name, rest = last name
        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);

        return new self($firstName, $lastName);
    }

    /**
     * Get the first name.
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Get the last name.
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Get the full name in standard format "FirstName LastName".
     *
     * Example: "Jean Dupont"
     */
    public function getFullName(): string
    {
        if (empty($this->firstName)) {
            return $this->lastName;
        }

        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * Get initials.
     *
     * Example: "Jean Dupont" → "J.D."
     */
    public function getInitials(): string
    {
        $initials = '';

        if (!empty($this->firstName)) {
            $initials .= mb_strtoupper(mb_substr($this->firstName, 0, 1)) . '.';
        }

        if (!empty($this->lastName)) {
            $initials .= mb_strtoupper(mb_substr($this->lastName, 0, 1)) . '.';
        }

        return $initials;
    }

    /**
     * Get name in "LASTNAME FirstName" format (French administrative style).
     *
     * Example: "Jean Dupont" → "DUPONT Jean"
     */
    public function getLastNameFirst(): string
    {
        if (empty($this->firstName)) {
            return mb_strtoupper($this->lastName);
        }

        return mb_strtoupper($this->lastName) . ' ' . $this->firstName;
    }

    /**
     * Get formal name with title.
     *
     * Example: "M. Jean Dupont" or "Mme Marie Martin"
     */
    public function getWithTitle(string $title): string
    {
        return $title . ' ' . $this->getFullName();
    }

    /**
     * Compare with another PersonName by value.
     */
    public function equals(self $other): bool
    {
        return $this->firstName === $other->firstName
            && $this->lastName === $other->lastName;
    }

    /**
     * Cast to string (returns full name).
     */
    public function __toString(): string
    {
        return $this->getFullName();
    }

    /**
     * Normalize a name component (trim, capitalize).
     */
    private static function normalize(string $value): string
    {
        // Trim whitespace
        $value = trim($value);

        // Collapse multiple spaces
        $value = preg_replace('/\s+/', ' ', $value);

        // Capitalize first letter of each word
        // Preserves compound names like "Jean-Pierre" or "de la Fontaine"
        $value = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');

        return $value;
    }

    /**
     * Validate first and last name.
     *
     * @throws \InvalidArgumentException if validation fails
     */
    private function validate(): void
    {
        // At least last name must be present
        if (empty($this->lastName)) {
            throw new \InvalidArgumentException('Last name cannot be empty');
        }

        // Check max length for each component
        if (mb_strlen($this->firstName) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('First name cannot exceed %d characters', self::MAX_LENGTH)
            );
        }

        if (mb_strlen($this->lastName) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Last name cannot exceed %d characters', self::MAX_LENGTH)
            );
        }

        // Check for invalid characters (basic validation)
        if (!$this->isValidName($this->firstName) || !$this->isValidName($this->lastName)) {
            throw new \InvalidArgumentException(
                'Name contains invalid characters. Only letters, spaces, hyphens, and apostrophes allowed.'
            );
        }
    }

    /**
     * Validate name component characters.
     *
     * Allows: letters (with accents), spaces, hyphens, apostrophes.
     */
    private function isValidName(string $name): bool
    {
        if (empty($name)) {
            return true; // firstName can be empty
        }

        // Unicode-safe regex for names
        // Allows: letters (including accented), spaces, hyphens, apostrophes
        return preg_match('/^[\p{L}\s\-\']+$/u', $name) === 1;
    }
}
```

**Actions:**
- Créer `src/Domain/Shared/ValueObject/PersonName.php`
- Constructor `private` avec validation firstName/lastName
- Factory `create(firstName, lastName)` pour création explicite
- Factory `fromString(fullName)` avec parsing intelligent
- Méthodes de formatage: `getFullName()`, `getInitials()`, `getLastNameFirst()`
- Validation: longueur max, caractères valides, lastName obligatoire
- Support i18n: accents, traits d'union, apostrophes
- Méthode `equals()` pour comparaison
- Normalisation automatique (trim, capitalize)

### [INFRA] Créer Doctrine Custom Type PersonNameType (1h)

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\ValueObject\PersonName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;

/**
 * Doctrine Custom Type for PersonName Value Object.
 *
 * Maps PersonName to JSON database column.
 * Stores: {"firstName": "Jean", "lastName": "Dupont"}
 */
final class PersonNameType extends JsonType
{
    public const string NAME = 'person_name';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?PersonName
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PersonName) {
            return $value;
        }

        // Parent convertToPHPValue decodes JSON to array
        $data = parent::convertToPHPValue($value, $platform);

        if (!is_array($data)) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', 'array', PersonName::class]
            );
        }

        if (!isset($data['firstName']) || !isset($data['lastName'])) {
            throw ConversionException::conversionFailed(
                $value,
                $this->getName()
            );
        }

        try {
            return PersonName::create($data['firstName'], $data['lastName']);
        } catch (\InvalidArgumentException $e) {
            throw ConversionException::conversionFailed($value, $this->getName(), $e);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof PersonName) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', PersonName::class]
            );
        }

        // Convert to array, then JSON via parent
        $data = [
            'firstName' => $value->getFirstName(),
            'lastName' => $value->getLastName(),
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
- Créer `src/Infrastructure/Persistence/Doctrine/Type/PersonNameType.php`
- Extend `JsonType` (stockage JSON en base)
- `convertToPHPValue()`: JSON → PersonName VO
- `convertToDatabaseValue()`: PersonName VO → JSON
- Format JSON: `{"firstName": "Jean", "lastName": "Dupont"}`
- Gestion NULL
- `ConversionException` pour types invalides

### [CONFIG] Enregistrer Custom Type Doctrine (0.5h)

```yaml
# config/packages/doctrine.yaml

doctrine:
    dbal:
        types:
            # ... autres types existants
            person_name: App\Infrastructure\Persistence\Doctrine\Type\PersonNameType

        mapping_types:
            person_name: json
```

**Vérification:**
```bash
make console CMD="dbal:types"

# Output attendu:
# person_name  App\Infrastructure\Persistence\Doctrine\Type\PersonNameType
```

### [TEST] Créer tests unitaires PersonName (1.5h)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\ValueObject\PersonName;
use PHPUnit\Framework\TestCase;

final class PersonNameTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_person_name_with_first_and_last_name(): void
    {
        // When
        $name = PersonName::create('Jean', 'Dupont');

        // Then
        self::assertEquals('Jean', $name->getFirstName());
        self::assertEquals('Dupont', $name->getLastName());
        self::assertEquals('Jean Dupont', $name->getFullName());
    }

    /**
     * @test
     */
    public function it_creates_person_name_from_full_name_string(): void
    {
        // When
        $name = PersonName::fromString('Jean Dupont');

        // Then
        self::assertEquals('Jean', $name->getFirstName());
        self::assertEquals('Dupont', $name->getLastName());
    }

    /**
     * @test
     */
    public function it_parses_compound_first_name(): void
    {
        // When
        $name = PersonName::fromString('Jean-Pierre Dupont');

        // Then
        self::assertEquals('Jean-Pierre', $name->getFirstName());
        self::assertEquals('Dupont', $name->getLastName());
    }

    /**
     * @test
     */
    public function it_parses_name_with_particle(): void
    {
        // When
        $name = PersonName::fromString('Jean de la Fontaine');

        // Then
        self::assertEquals('Jean', $name->getFirstName());
        self::assertEquals('De La Fontaine', $name->getLastName());
    }

    /**
     * @test
     */
    public function it_handles_single_name_as_last_name(): void
    {
        // When
        $name = PersonName::fromString('Dupont');

        // Then
        self::assertEquals('', $name->getFirstName());
        self::assertEquals('Dupont', $name->getLastName());
        self::assertEquals('Dupont', $name->getFullName());
    }

    /**
     * @test
     */
    public function it_normalizes_whitespace(): void
    {
        // When
        $name = PersonName::fromString('  Jean   Dupont  ');

        // Then
        self::assertEquals('Jean Dupont', $name->getFullName());
    }

    /**
     * @test
     */
    public function it_capitalizes_names(): void
    {
        // When
        $name = PersonName::create('jean', 'dupont');

        // Then
        self::assertEquals('Jean', $name->getFirstName());
        self::assertEquals('Dupont', $name->getLastName());
    }

    /**
     * @test
     */
    public function it_supports_accented_characters(): void
    {
        // When
        $name = PersonName::create('François', 'José');

        // Then
        self::assertEquals('François', $name->getFirstName());
        self::assertEquals('José', $name->getLastName());
    }

    /**
     * @test
     */
    public function it_supports_apostrophes(): void
    {
        // When
        $name = PersonName::fromString("Jean D'Arcy");

        // Then
        self::assertEquals('Jean', $name->getFirstName());
        self::assertEquals("D'Arcy", $name->getLastName());
    }

    /**
     * @test
     */
    public function it_throws_exception_for_empty_last_name(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name cannot be empty');

        // When
        PersonName::create('Jean', '');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_empty_full_name(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);

        // When
        PersonName::fromString('');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_name_too_long(): void
    {
        // Given
        $longName = str_repeat('a', 101);

        // Expect
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 100 characters');

        // When
        PersonName::create('Jean', $longName);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_characters(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid characters');

        // When
        PersonName::create('Jean123', 'Dupont');
    }

    /**
     * @test
     */
    public function it_returns_initials(): void
    {
        // Given
        $name = PersonName::create('Jean', 'Dupont');

        // When
        $initials = $name->getInitials();

        // Then
        self::assertEquals('J.D.', $initials);
    }

    /**
     * @test
     */
    public function it_returns_initials_for_compound_name(): void
    {
        // Given
        $name = PersonName::create('Jean-Pierre', 'Dupont');

        // When
        $initials = $name->getInitials();

        // Then
        self::assertEquals('J.D.', $initials); // First letter of compound name
    }

    /**
     * @test
     */
    public function it_returns_last_name_first_format(): void
    {
        // Given
        $name = PersonName::create('Jean', 'Dupont');

        // When
        $formatted = $name->getLastNameFirst();

        // Then
        self::assertEquals('DUPONT Jean', $formatted);
    }

    /**
     * @test
     */
    public function it_compares_two_person_names_by_value(): void
    {
        // Given
        $name1 = PersonName::create('Jean', 'Dupont');
        $name2 = PersonName::create('Jean', 'Dupont');

        // Then
        self::assertTrue($name1->equals($name2));
    }

    /**
     * @test
     */
    public function it_returns_false_when_comparing_different_names(): void
    {
        // Given
        $name1 = PersonName::create('Jean', 'Dupont');
        $name2 = PersonName::create('Marie', 'Martin');

        // Then
        self::assertFalse($name1->equals($name2));
    }

    /**
     * @test
     */
    public function it_casts_to_string(): void
    {
        // Given
        $name = PersonName::create('Jean', 'Dupont');

        // When
        $result = (string) $name;

        // Then
        self::assertEquals('Jean Dupont', $result);
    }

    /**
     * @test
     */
    public function it_is_immutable(): void
    {
        // Given
        $name = PersonName::create('Jean', 'Dupont');

        // Then: No setter methods exist (verified by readonly)
        self::assertEquals('Jean', $name->getFirstName());

        // Cannot modify
        // $name->firstName = 'Pierre'; // ❌ Compilation error (readonly)
    }

    /**
     * @test
     */
    public function it_handles_names_with_multiple_particles(): void
    {
        // When
        $name = PersonName::fromString('Charles de Gaulle');

        // Then
        self::assertEquals('Charles', $name->getFirstName());
        self::assertEquals('De Gaulle', $name->getLastName());
    }

    /**
     * @test
     */
    public function it_formats_name_with_title(): void
    {
        // Given
        $name = PersonName::create('Jean', 'Dupont');

        // When
        $formatted = $name->getWithTitle('M.');

        // Then
        self::assertEquals('M. Jean Dupont', $formatted);
    }
}
```

**Couverture:**
- ✅ Création via `create()` et `fromString()`
- ✅ Parsing de noms composés (Jean-Pierre)
- ✅ Parsing de particules (de, van, von)
- ✅ Validation (vide, trop long, caractères invalides)
- ✅ Formatage (fullName, initials, lastNameFirst, withTitle)
- ✅ Normalisation (trim, capitalize)
- ✅ Support i18n (accents, apostrophes)
- ✅ Comparaison `equals()`
- ✅ Immutabilité (readonly)
- ✅ Edge cases (nom unique, espaces multiples)

**Target:** ≥ 90% code coverage, < 100ms

### [TEST] Créer tests d'intégration Doctrine Type (1h)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\ValueObject\PersonName;
use App\Infrastructure\Persistence\Doctrine\Type\PersonNameType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

final class PersonNameTypeTest extends TestCase
{
    private PersonNameType $type;
    private PostgreSQLPlatform $platform;

    protected function setUp(): void
    {
        if (!Type::hasType(PersonNameType::NAME)) {
            Type::addType(PersonNameType::NAME, PersonNameType::class);
        }

        $this->type = Type::getType(PersonNameType::NAME);
        $this->platform = new PostgreSQLPlatform();
    }

    /**
     * @test
     */
    public function it_converts_null_to_php_value(): void
    {
        // When
        $result = $this->type->convertToPHPValue(null, $this->platform);

        // Then
        self::assertNull($result);
    }

    /**
     * @test
     */
    public function it_converts_valid_json_to_person_name(): void
    {
        // Given
        $json = '{"firstName":"Jean","lastName":"Dupont"}';

        // When
        $result = $this->type->convertToPHPValue($json, $this->platform);

        // Then
        self::assertInstanceOf(PersonName::class, $result);
        self::assertEquals('Jean', $result->getFirstName());
        self::assertEquals('Dupont', $result->getLastName());
    }

    /**
     * @test
     */
    public function it_converts_person_name_to_database_value(): void
    {
        // Given
        $name = PersonName::create('Jean', 'Dupont');

        // When
        $result = $this->type->convertToDatabaseValue($name, $this->platform);

        // Then
        self::assertIsString($result);
        $decoded = json_decode($result, true);
        self::assertEquals('Jean', $decoded['firstName']);
        self::assertEquals('Dupont', $decoded['lastName']);
    }

    /**
     * @test
     */
    public function it_converts_null_person_name_to_null_database_value(): void
    {
        // When
        $result = $this->type->convertToDatabaseValue(null, $this->platform);

        // Then
        self::assertNull($result);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_json_format(): void
    {
        // Given
        $invalidJson = '{"firstName":"Jean"}'; // Missing lastName

        // Expect
        $this->expectException(ConversionException::class);

        // When
        $this->type->convertToPHPValue($invalidJson, $this->platform);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_type_to_database(): void
    {
        // Expect
        $this->expectException(ConversionException::class);

        // When
        $this->type->convertToDatabaseValue('invalid-type', $this->platform);
    }

    /**
     * @test
     */
    public function it_has_correct_name(): void
    {
        // Then
        self::assertEquals('person_name', $this->type->getName());
    }

    /**
     * @test
     */
    public function it_requires_sql_comment_hint(): void
    {
        // Then
        self::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    /**
     * @test
     */
    public function it_returns_person_name_if_already_person_name_instance(): void
    {
        // Given
        $name = PersonName::create('Jean', 'Dupont');

        // When
        $result = $this->type->convertToPHPValue($name, $this->platform);

        // Then
        self::assertSame($name, $result);
    }

    /**
     * @test
     */
    public function it_handles_empty_first_name(): void
    {
        // Given
        $json = '{"firstName":"","lastName":"Dupont"}';

        // When
        $result = $this->type->convertToPHPValue($json, $this->platform);

        // Then
        self::assertEquals('', $result->getFirstName());
        self::assertEquals('Dupont', $result->getLastName());
        self::assertEquals('Dupont', $result->getFullName());
    }

    /**
     * @test
     */
    public function it_handles_compound_names(): void
    {
        // Given
        $json = '{"firstName":"Jean-Pierre","lastName":"de la Fontaine"}';

        // When
        $result = $this->type->convertToPHPValue($json, $this->platform);

        // Then
        self::assertEquals('Jean-Pierre', $result->getFirstName());
        self::assertEquals('De La Fontaine', $result->getLastName());
    }
}
```

**Couverture:**
- ✅ Conversion NULL
- ✅ Conversion JSON → PersonName
- ✅ Conversion PersonName → JSON
- ✅ Exceptions pour formats invalides
- ✅ Nom de type correct
- ✅ SQL comment hint
- ✅ Instance passthrough
- ✅ Edge cases (firstName vide, noms composés)

### [DOC] Documenter PersonName usage (0.5h)

Créer `.claude/examples/value-object-personname.md`:

```markdown
# Value Object: PersonName

## Caractéristiques

- **Type-safe**: Cannot pass string where PersonName expected
- **Validated**: Non-empty lastName, max length 100 chars
- **Immutable**: final readonly, no setters
- **Equality by value**: equals() method
- **i18n support**: Accents, hyphens, apostrophes
- **Multiple formats**: Full name, initials, lastName first

## Création

### Méthode 1: create() avec firstName et lastName explicites

```php
<?php

use App\Domain\Shared\ValueObject\PersonName;

// Standard
$name = PersonName::create('Jean', 'Dupont');
echo $name->getFullName(); // "Jean Dupont"

// Nom composé
$name = PersonName::create('Jean-Pierre', 'Martin');
echo $name->getFullName(); // "Jean-Pierre Martin"

// Avec particule
$name = PersonName::create('Charles', 'de Gaulle');
echo $name->getFullName(); // "Charles De Gaulle"

// Nom unique (lastName seulement)
$name = PersonName::create('', 'Madonna');
echo $name->getFullName(); // "Madonna"
```

### Méthode 2: fromString() avec parsing automatique

```php
<?php

// Parsing standard
$name = PersonName::fromString('Jean Dupont');
// → firstName: "Jean", lastName: "Dupont"

// Parsing nom composé
$name = PersonName::fromString('Jean-Pierre de la Fontaine');
// → firstName: "Jean-Pierre", lastName: "De La Fontaine"

// Nom unique
$name = PersonName::fromString('Dupont');
// → firstName: "", lastName: "Dupont"

// Normalisation automatique
$name = PersonName::fromString('  jean   dupont  ');
// → "Jean Dupont"
```

## Formatage

```php
<?php

$name = PersonName::create('Jean', 'Dupont');

// Nom complet (défaut)
echo $name->getFullName(); // "Jean Dupont"

// Initiales
echo $name->getInitials(); // "J.D."

// Format administratif français (NOM Prénom)
echo $name->getLastNameFirst(); // "DUPONT Jean"

// Avec civilité
echo $name->getWithTitle('M.'); // "M. Jean Dupont"

// Cast to string
echo $name; // "Jean Dupont"
echo (string) $name; // "Jean Dupont"
```

## Comparaison

```php
<?php

$name1 = PersonName::create('Jean', 'Dupont');
$name2 = PersonName::create('Jean', 'Dupont');
$name3 = PersonName::create('Marie', 'Martin');

// Égalité par valeur
var_dump($name1->equals($name2)); // true
var_dump($name1->equals($name3)); // false

// Comparaison stricte
var_dump($name1 === $name2); // false (instances différentes)
```

## Utilisation dans une Entity

```php
<?php

declare(strict_types=1);

namespace App\Domain\Client\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\PersonName;
use App\Domain\Shared\ValueObject\Email;

final class Client
{
    private ClientId $id;
    private PersonName $name; // ✅ Value Object au lieu de string
    private Email $email;

    private function __construct(
        ClientId $id,
        PersonName $name,
        Email $email
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }

    public static function create(
        ClientId $id,
        PersonName $name,
        Email $email
    ): self {
        return new self($id, $name, $email);
    }

    // Getters
    public function getId(): ClientId
    {
        return $this->id;
    }

    public function getName(): PersonName
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    // Méthodes métier avec Value Objects
    public function updateName(PersonName $newName): void
    {
        $this->name = $newName;
        $this->recordEvent(new ClientNameUpdatedEvent($this->id, $newName));
    }
}
```

## Utilisation dans Doctrine Mapping

```xml
<!-- Infrastructure/Persistence/Doctrine/Mapping/Client.orm.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Domain\Client\Entity\Client" table="client">
        <id name="id" type="client_id">
            <generator strategy="NONE"/>
        </id>

        <!-- PersonName stocké en JSON -->
        <field name="name" type="person_name" column="name" nullable="false"/>

        <field name="email" type="email" column="email" unique="true" nullable="false"/>

        <field name="createdAt" type="datetime_immutable" column="created_at" nullable="false"/>
        <field name="updatedAt" type="datetime_immutable" column="updated_at" nullable="false"/>
    </entity>
</doctrine-mapping>
```

**SQL généré:**
```sql
CREATE TABLE client (
    id UUID NOT NULL PRIMARY KEY,
    name JSON NOT NULL,  -- {"firstName":"Jean","lastName":"Dupont"}
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);
```

## Utilisation dans un Use Case

```php
<?php

namespace App\Application\Client\UseCase\CreateClient;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\PersonName;
use App\Domain\Shared\ValueObject\Email;

final readonly class CreateClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(CreateClientCommand $command): ClientId
    {
        // ✅ Parse name from command
        $name = PersonName::fromString($command->fullName);

        // ✅ Create client with PersonName VO
        $client = Client::create(
            ClientId::generate(),
            $name,
            Email::fromString($command->email)
        );

        $this->clientRepository->save($client);

        return $client->getId();
    }
}
```

## Utilisation dans un Controller

```php
<?php

namespace App\Presentation\Controller\Web;

use App\Application\Client\UseCase\CreateClient\CreateClientCommand;
use App\Application\Client\UseCase\CreateClient\CreateClientUseCase;
use Symfony\Component\HttpFoundation\Request;

final class ClientController extends AbstractController
{
    #[Route('/clients/create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateClientUseCase $createClient
    ): Response {
        // ✅ Parse name from form
        $fullName = $request->request->get('fullName');

        try {
            $name = PersonName::fromString($fullName);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Nom invalide: ' . $e->getMessage());
            return $this->redirectToRoute('client_new');
        }

        $command = new CreateClientCommand(
            fullName: $fullName,
            email: $request->request->get('email')
        );

        $clientId = $createClient->execute($command);

        return $this->redirectToRoute('client_show', ['id' => (string) $clientId]);
    }
}
```

## Validation avec Symfony Validator

```php
<?php

namespace App\Presentation\Form;

use App\Domain\Shared\ValueObject\PersonName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ClientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 200),
                    new Assert\Callback(function (string $fullName, $context) {
                        try {
                            PersonName::fromString($fullName);
                        } catch (\InvalidArgumentException $e) {
                            $context->buildViolation($e->getMessage())
                                ->addViolation();
                        }
                    }),
                ],
            ]);
    }
}
```

## Domain Event avec PersonName

```php
<?php

namespace App\Domain\Client\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\PersonName;

final readonly class ClientNameUpdatedEvent
{
    public function __construct(
        public ClientId $clientId,
        public PersonName $oldName,
        public PersonName $newName,
        public \DateTimeImmutable $occurredOn = new \DateTimeImmutable(),
    ) {}
}
```

## Cas d'usage avancés

### Parsing intelligent avec particules

```php
<?php

// Nom avec particule française
$name = PersonName::fromString('Charles de Gaulle');
echo $name->getFirstName(); // "Charles"
echo $name->getLastName();  // "De Gaulle"

// Nom avec particule allemande
$name = PersonName::fromString('Ludwig van Beethoven');
echo $name->getFirstName(); // "Ludwig"
echo $name->getLastName();  // "Van Beethoven"

// Nom avec particule néerlandaise
$name = PersonName::fromString('Vincent van Gogh');
echo $name->getFirstName(); // "Vincent"
echo $name->getLastName();  // "Van Gogh"
```

### Noms internationaux

```php
<?php

// Français
$name = PersonName::create('François', 'Müller');
echo $name->getFullName(); // "François Müller"

// Espagnol
$name = PersonName::create('José', 'García');
echo $name->getFullName(); // "José García"

// Allemand
$name = PersonName::create('Jürgen', 'Schröder');
echo $name->getFullName(); // "Jürgen Schröder"

// Italien
$name = PersonName::create('Nicolò', "D'Angelo");
echo $name->getFullName(); // "Nicolò D'Angelo"
```

### Formatage pour différents contextes

```php
<?php

$name = PersonName::create('Jean-Pierre', 'Dupont');

// Email formel
$emailSubject = "Bonjour " . $name->getWithTitle('M.');
// "Bonjour M. Jean-Pierre Dupont"

// Liste administrative (NOM Prénom)
$list = $name->getLastNameFirst();
// "DUPONT Jean-Pierre"

// Badge/initiales
$badge = $name->getInitials();
// "J.D."

// Notification informelle
$greeting = "Bonjour " . $name->getFirstName();
// "Bonjour Jean-Pierre"
```

## Avantages

### ✅ Validation centralisée

```php
<?php

// ❌ AVANT: Validation dispersée
class ClientService
{
    public function create(string $firstName, string $lastName): void
    {
        if (empty($lastName)) {
            throw new \InvalidArgumentException('Last name required');
        }
        // ... validation dupliquée partout
    }
}

class UserService
{
    public function register(string $firstName, string $lastName): void
    {
        if (empty($lastName)) {
            throw new \InvalidArgumentException('Last name required');
        }
        // ... même validation dupliquée
    }
}

// ✅ APRÈS: Validation unique dans le VO
class ClientService
{
    public function create(PersonName $name): void
    {
        // ✅ Validation automatique à la création du VO
    }
}

class UserService
{
    public function register(PersonName $name): void
    {
        // ✅ Validation automatique
    }
}
```

### ✅ Formatage cohérent

```php
<?php

// ❌ AVANT: Formatage dupliqué
class Client
{
    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }
}

class User
{
    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom; // ❌ Duplication
    }
}

class Participant
{
    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom; // ❌ Duplication
    }
}

// ✅ APRÈS: Formatage centralisé dans le VO
class Client
{
    public function getName(): PersonName
    {
        return $this->name;
    }
}

// Usage:
$client->getName()->getFullName();
$client->getName()->getInitials();
$client->getName()->getLastNameFirst();
```

### ✅ Type safety

```php
<?php

// ❌ AVANT: Confusion possible
public function updateClient(string $id, string $name, string $email): void
{
    // ⚠️ Quelle string est quoi? Ordre des paramètres?
    $this->repository->update($email, $name, $id); // ❌ Inversion!
}

// ✅ APRÈS: Type safety
public function updateClient(
    ClientId $id,
    PersonName $name,
    Email $email
): void {
    // ✅ PHPStan détecte si ordre incorrect
    $this->repository->update($name, $email, $id);
}
```

## Migration depuis strings

### Phase 1: Créer le Value Object
```php
// ✅ Créer PersonName.php (US-014)
```

### Phase 2: Créer le Doctrine Custom Type
```php
// ✅ Créer PersonNameType.php
```

### Phase 3: Migrer les entités

**Avant:**
```php
<?php

namespace App\Entity;

class Client
{
    #[ORM\Column(type: 'string')]
    private string $nom;

    #[ORM\Column(type: 'string')]
    private string $prenom;
}
```

**Après:**
```php
<?php

namespace App\Domain\Client\Entity;

final class Client
{
    private PersonName $name; // ✅ Un seul champ VO
}
```

### Phase 4: Migration base de données

```sql
-- Étape 1: Ajouter nouvelle colonne JSON
ALTER TABLE client ADD COLUMN name JSON;

-- Étape 2: Migrer les données
UPDATE client
SET name = json_build_object(
    'firstName', prenom,
    'lastName', nom
);

-- Étape 3: Rendre NOT NULL
ALTER TABLE client ALTER COLUMN name SET NOT NULL;

-- Étape 4: Supprimer anciennes colonnes
ALTER TABLE client DROP COLUMN prenom;
ALTER TABLE client DROP COLUMN nom;
```

## Patterns i18n

### Ordre des noms par culture

```php
<?php

namespace App\Domain\Shared\Service;

use App\Domain\Shared\ValueObject\PersonName;
use App\Domain\Shared\ValueObject\Country;

final readonly class PersonNameFormatter
{
    /**
     * Format name according to country conventions.
     */
    public function formatForCountry(PersonName $name, Country $country): string
    {
        return match ($country) {
            Country::FR, Country::BE => $name->getLastNameFirst(), // "DUPONT Jean"
            Country::EN, Country::DE, Country::NL => $name->getFullName(), // "Jean Dupont"
            Country::ES, Country::IT => $name->getFullName(), // "Jean Dupont"
        };
    }

    /**
     * Format for official documents.
     */
    public function formatOfficial(PersonName $name): string
    {
        // French administrative style: "NOM Prénom"
        return $name->getLastNameFirst();
    }

    /**
     * Format for display (friendly).
     */
    public function formatDisplay(PersonName $name): string
    {
        // Standard: "Prénom NOM"
        return $name->getFullName();
    }
}
```

### Particules et noblesse

```php
<?php

// Particules françaises
$name = PersonName::fromString('Jean de la Fontaine');
echo $name->getLastName(); // "De La Fontaine"

// Particules néerlandaises
$name = PersonName::fromString('Jan van der Berg');
echo $name->getLastName(); // "Van Der Berg"

// Particules allemandes
$name = PersonName::fromString('Otto von Bismarck');
echo $name->getLastName(); // "Von Bismarck"

// Noblesse écossaise
$name = PersonName::fromString("William O'Connor");
echo $name->getLastName(); // "O'Connor"
```

## Anti-patterns

### ❌ Stocker firstName et lastName séparément

```php
<?php

// MAUVAIS: Duplication de champs
class Client
{
    private string $firstName;
    private string $lastName;

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
```

### ❌ PersonName mutable

```php
<?php

// MAUVAIS: Mutable
class PersonName
{
    public function setFirstName(string $firstName): void // ❌ Setter
    {
        $this->firstName = $firstName;
    }
}
```

### ❌ Validation dispersée

```php
<?php

// MAUVAIS: Validation dans le service
class ClientService
{
    public function create(string $firstName, string $lastName): void
    {
        if (empty($lastName)) {
            throw new \InvalidArgumentException('...');
        }
        // ... validation dupliquée partout
    }
}

// BON: Validation dans le VO
$name = PersonName::create($firstName, $lastName); // ✅ Validation automatique
```

## Checklist

- [ ] Classe `final readonly`
- [ ] Private constructor avec validation
- [ ] Factory methods `create()` et `fromString()`
- [ ] Getters: `getFirstName()`, `getLastName()`, `getFullName()`
- [ ] Méthodes formatage: `getInitials()`, `getLastNameFirst()`
- [ ] Validation: lastName obligatoire, max length
- [ ] Support i18n: accents, hyphens, apostrophes
- [ ] Méthode `equals()` pour comparaison
- [ ] Méthode `__toString()`
- [ ] Aucun setter (immutabilité)
- [ ] Doctrine Custom Type avec JSON storage
- [ ] Tests unitaires ≥ 90% coverage
- [ ] Tests d'intégration Doctrine Type
- [ ] Documentation exemples usage
```

**Actions:**
- Créer `.claude/examples/value-object-personname.md`
- Documenter caractéristiques PersonName
- Exemples création (`create`, `fromString`)
- Exemples formatage (fullName, initials, lastNameFirst)
- Exemples usage dans Entity, Use Case, Controller
- Patterns i18n (particules, accents, ordre des noms)
- Migration depuis strings
- Anti-patterns à éviter
- Checklist Value Object

### [VALIDATION] Valider avec outils qualité (0.5h)

```bash
# PHPStan niveau max
make phpstan

# Output attendu:
# [OK] No errors

# Deptrac (vérifier isolation Domain)
make deptrac

# Output attendu:
# [OK] Domain layer: 0 violations
```

---

## Définition de Done (DoD)

### Code

- [ ] Classe `src/Domain/Shared/ValueObject/PersonName.php` créée
- [ ] Classe `final readonly` avec propriétés `firstName` et `lastName`
- [ ] Constructor `private` avec validation
- [ ] Factory method `create(firstName, lastName)` statique
- [ ] Factory method `fromString(fullName)` avec parsing intelligent
- [ ] Méthode `getFirstName()` retourne le prénom
- [ ] Méthode `getLastName()` retourne le nom
- [ ] Méthode `getFullName()` retourne "Prénom Nom"
- [ ] Méthode `getInitials()` retourne initiales (ex: "J.D.")
- [ ] Méthode `getLastNameFirst()` retourne "NOM Prénom"
- [ ] Méthode `getWithTitle(title)` retourne nom avec civilité
- [ ] Méthode `equals(PersonName)` pour comparaison par valeur
- [ ] Méthode `__toString()` retourne nom complet
- [ ] Validation: lastName non vide (obligatoire)
- [ ] Validation: firstName peut être vide (noms uniques comme "Madonna")
- [ ] Validation: longueur max 100 caractères par composant
- [ ] Validation: caractères autorisés (lettres, espaces, hyphens, apostrophes, accents)
- [ ] Normalisation automatique (trim, capitalize, collapse spaces)
- [ ] Support noms composés (Jean-Pierre, Marie-Claire)
- [ ] Support particules (de, van, von, etc.)
- [ ] Support accents (François, José, Müller)
- [ ] Support apostrophes (O'Connor, D'Angelo)
- [ ] Aucun setter (immutabilité garantie)

### Doctrine Integration

- [ ] Classe `PersonNameType` créée dans `Infrastructure/Persistence/Doctrine/Type/`
- [ ] Extend `JsonType` (stockage JSON)
- [ ] Méthode `convertToPHPValue()`: JSON → PersonName
- [ ] Méthode `convertToDatabaseValue()`: PersonName → JSON
- [ ] Format JSON: `{"firstName":"Jean","lastName":"Dupont"}`
- [ ] Gestion NULL dans les deux conversions
- [ ] `ConversionException` pour types invalides
- [ ] Méthode `getName()` retourne 'person_name'
- [ ] Méthode `requiresSQLCommentHint()` retourne true
- [ ] Type enregistré dans `config/packages/doctrine.yaml`
- [ ] Vérification: `make console CMD="dbal:types"` liste `person_name`

### Tests Unitaires

- [ ] Fichier `tests/Unit/Domain/Shared/ValueObject/PersonNameTest.php` créé
- [ ] Test: `it_creates_person_name_with_first_and_last_name()`
- [ ] Test: `it_creates_person_name_from_full_name_string()`
- [ ] Test: `it_parses_compound_first_name()` (Jean-Pierre)
- [ ] Test: `it_parses_name_with_particle()` (de la Fontaine)
- [ ] Test: `it_handles_single_name_as_last_name()` (Madonna → lastName)
- [ ] Test: `it_normalizes_whitespace()` (trim, collapse)
- [ ] Test: `it_capitalizes_names()` (jean → Jean)
- [ ] Test: `it_supports_accented_characters()` (François, José)
- [ ] Test: `it_supports_apostrophes()` (D'Arcy, O'Connor)
- [ ] Test: `it_throws_exception_for_empty_last_name()`
- [ ] Test: `it_throws_exception_for_empty_full_name()`
- [ ] Test: `it_throws_exception_for_name_too_long()`
- [ ] Test: `it_throws_exception_for_invalid_characters()` (chiffres, symboles)
- [ ] Test: `it_returns_initials()` (J.D.)
- [ ] Test: `it_returns_initials_for_compound_name()` (Jean-Pierre → J.D.)
- [ ] Test: `it_returns_last_name_first_format()` (DUPONT Jean)
- [ ] Test: `it_compares_two_person_names_by_value()`
- [ ] Test: `it_returns_false_when_comparing_different_names()`
- [ ] Test: `it_casts_to_string()`
- [ ] Test: `it_is_immutable()` (readonly)
- [ ] Test: `it_handles_names_with_multiple_particles()` (de Gaulle)
- [ ] Test: `it_formats_name_with_title()` (M. Jean Dupont)
- [ ] Couverture code ≥ 90%
- [ ] Tous les tests passent en < 100ms

### Tests d'Intégration

- [ ] Fichier `tests/Integration/Infrastructure/Persistence/Doctrine/Type/PersonNameTypeTest.php` créé
- [ ] Test: `it_converts_null_to_php_value()`
- [ ] Test: `it_converts_valid_json_to_person_name()`
- [ ] Test: `it_converts_person_name_to_database_value()`
- [ ] Test: `it_converts_null_person_name_to_null_database_value()`
- [ ] Test: `it_throws_exception_for_invalid_json_format()` (missing lastName)
- [ ] Test: `it_throws_exception_for_invalid_type_to_database()`
- [ ] Test: `it_has_correct_name()` (person_name)
- [ ] Test: `it_requires_sql_comment_hint()`
- [ ] Test: `it_returns_person_name_if_already_person_name_instance()`
- [ ] Test: `it_handles_empty_first_name()`
- [ ] Test: `it_handles_compound_names()` (JSON → VO → JSON)
- [ ] Type::addType() registration dans setUp()
- [ ] Utilise PostgreSQLPlatform
- [ ] Tous les tests passent

### Documentation

- [ ] Fichier `.claude/examples/value-object-personname.md` créé
- [ ] Section: Caractéristiques (type-safe, validated, immutable, i18n)
- [ ] Section: Création (create vs fromString)
- [ ] Section: Formatage (fullName, initials, lastNameFirst, withTitle)
- [ ] Section: Comparaison (equals)
- [ ] Section: Utilisation dans Entity (Client avec PersonName)
- [ ] Section: Utilisation dans Doctrine Mapping (JSON storage)
- [ ] Section: Utilisation dans Use Case
- [ ] Section: Utilisation dans Controller avec validation
- [ ] Section: Validation avec Symfony Validator
- [ ] Section: Domain Event avec PersonName
- [ ] Section: Cas avancés (particules, noms internationaux, formatage contexte)
- [ ] Section: Avantages (validation centralisée, formatage cohérent, type safety)
- [ ] Section: Migration depuis strings (4 phases SQL)
- [ ] Section: Patterns i18n (ordre noms par pays, particules, accents)
- [ ] Section: Anti-patterns (firstName/lastName séparés, mutable, validation dispersée)
- [ ] Section: Checklist Value Object
- [ ] Exemples concrets pour chaque méthode
- [ ] Edge cases documentés

### Qualité

- [ ] PHPStan niveau max passe sur `src/Domain/Shared/ValueObject/PersonName.php`
- [ ] Aucune erreur PHPStan
- [ ] Aucune dépendance externe (Symfony, Doctrine) dans PersonName.php
- [ ] Deptrac valide: Shared/ValueObject ne dépend de rien
- [ ] PHP-CS-Fixer: code conforme PSR-12
- [ ] Rector: aucune suggestion de modernisation

### Revue

- [ ] Code review effectué par Tech Lead
- [ ] Validation parsing intelligent (particules, noms composés)
- [ ] Validation support i18n (7 pays: FR, EN, DE, ES, IT, NL, BE)
- [ ] Validation formatage cohérent
- [ ] Validation immutabilité (readonly)

### Commit

- [ ] Commit avec message: `feat(domain): create PersonName value object with i18n support`
- [ ] Message détaille: parsing intelligent, formatage multiple, support accents/particules

---

## Notes techniques

### Pattern Value Object PersonName

PersonName est un **Value Object complexe** car:
- Il encapsule **deux propriétés** (firstName, lastName)
- Il offre **plusieurs représentations** (fullName, initials, lastNameFirst)
- Il supporte **l'internationalisation** (accents, particules)
- Il centralise **validation et formatage**

### Parsing intelligent

Le parsing `fromString()` doit gérer:

#### Cas standard
```
"Jean Dupont" → firstName: "Jean", lastName: "Dupont"
```

#### Noms composés
```
"Jean-Pierre Martin" → firstName: "Jean-Pierre", lastName: "Martin"
"Marie-Claire Dubois" → firstName: "Marie-Claire", lastName: "Dubois"
```

#### Particules
```
"Charles de Gaulle" → firstName: "Charles", lastName: "De Gaulle"
"Ludwig van Beethoven" → firstName: "Ludwig", lastName: "Van Beethoven"
"Vincent van Gogh" → firstName: "Vincent", lastName: "Van Gogh"
```

#### Noms multiples
```
"Jean de la Fontaine" → firstName: "Jean", lastName: "De La Fontaine"
```

#### Nom unique
```
"Madonna" → firstName: "", lastName: "Madonna"
"Sting" → firstName: "", lastName: "Sting"
```

### Stockage Doctrine JSON

**Avantages JSON vs colonnes séparées:**
- ✅ Un seul champ en base (name JSON)
- ✅ Facile à étendre (middle name futur)
- ✅ Cohérence garantie (firstName et lastName toujours ensemble)
- ✅ Migration simplifiée (depuis nom/prenom existants)

**Format JSON:**
```json
{
  "firstName": "Jean-Pierre",
  "lastName": "de la Fontaine"
}
```

**PostgreSQL:**
```sql
-- Type JSON natif
CREATE TABLE client (
    id UUID NOT NULL PRIMARY KEY,
    name JSON NOT NULL,
    -- Exemple de valeur stockée:
    -- {"firstName":"Jean","lastName":"Dupont"}
);

-- Query avec JSON
SELECT name->>'lastName' as nom_famille
FROM client
WHERE name->>'firstName' = 'Jean';
```

### Différences culturelles

#### France/Belgique
- Format officiel: **NOM Prénom** (DUPONT Jean)
- Particules courantes: de, du, des, de la

#### Pays-Bas
- Particules: van, van der, van den, de

#### Allemagne
- Particules: von, zu, van

#### Espagne
- Double nom de famille: García López

#### Royaume-Uni/Irlande
- Particules: O', Mc, Mac

**PersonName supporte ces variations via:**
- Parsing intelligent (conserve les particules)
- Capitalisation respectueuse (De Gaulle, Van Gogh)
- Méthode `getLastNameFirst()` pour format administratif

### Exemple d'usage complet

```php
<?php

// Controller
public function create(Request $request, CreateClientUseCase $useCase): Response
{
    // Récupérer nom du formulaire
    $fullName = $request->request->get('fullName'); // "Jean Dupont"

    try {
        // Parser avec PersonName
        $name = PersonName::fromString($fullName);
    } catch (\InvalidArgumentException $e) {
        $this->addFlash('error', 'Nom invalide: ' . $e->getMessage());
        return $this->redirectToRoute('client_new');
    }

    // Command
    $command = new CreateClientCommand(
        name: $name,
        email: Email::fromString($request->request->get('email'))
    );

    // Use Case
    $clientId = $useCase->execute($command);

    return $this->redirectToRoute('client_show', ['id' => (string) $clientId]);
}

// Use Case
public function execute(CreateClientCommand $command): ClientId
{
    $client = Client::create(
        ClientId::generate(),
        $command->name, // ✅ PersonName VO
        $command->email
    );

    $this->repository->save($client);

    return $client->getId();
}

// Entity
final class Client
{
    private ClientId $id;
    private PersonName $name; // ✅ VO au lieu de string
    private Email $email;

    public static function create(
        ClientId $id,
        PersonName $name,
        Email $email
    ): self {
        $client = new self();
        $client->id = $id;
        $client->name = $name;
        $client->email = $email;

        $client->recordEvent(new ClientCreatedEvent($id, $name, $email));

        return $client;
    }

    public function getName(): PersonName
    {
        return $this->name;
    }
}

// Repository
interface ClientRepositoryInterface
{
    public function findByName(PersonName $name): ?Client;
}

// Affichage
echo $client->getName()->getFullName(); // "Jean Dupont"
echo $client->getName()->getInitials(); // "J.D."
echo $client->getName()->getLastNameFirst(); // "DUPONT Jean"
```

---

## Dépendances

### Bloquantes

- **US-001**: Structure Domain créée (nécessite `src/Domain/Shared/ValueObject/`)

### Bloque

- **US-002**: Extraction Client (utilisera PersonName pour le nom du client)
- **US-004**: Extraction User (utilisera PersonName pour nom de l'utilisateur)
- **US-018**: Remplacement types primitifs (remplacera string par PersonName)

---

## Références

### Documentation interne

- `.claude/rules/18-value-objects.md` - Template et exemples Value Objects
- `.claude/rules/16-i18n.md` - Country enum et formats internationaux (lignes 13-46)
- `.claude/examples/value-object-examples.md` - Exemples Email, Money
- `var/architecture-audit-report.md` - Audit source (lignes 75-108, problème types primitifs)

### Checklist EPIC-002 (Value Objects)

**Phase 1: Value Objects de base (Sprint 1)** (lignes 76-83):
- [x] US-010: Email ✅
- [x] US-011: PhoneNumber ✅
- [x] US-012: Money ✅
- [x] US-013: IDs typés ✅
- [x] **US-014: PersonName** ⬅️ CE TICKET

### Ressources externes

- [Value Object Pattern - Martin Fowler](https://martinfowler.com/bliki/ValueObject.html)
- [Doctrine JSON Type](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#json)
- [Unicode Regex in PHP](https://www.php.net/manual/en/regexp.reference.unicode.php)
- [Internationalization Names](https://www.w3.org/International/questions/qa-personal-names)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |

---

## Notes

- **Prerequis**: Lecture obligatoire de `.claude/rules/18-value-objects.md` avant implémentation
- **TDD obligatoire**: Cycle RED → GREEN → REFACTOR
- **Immutabilité**: Classe `final readonly`, aucun setter
- **Validation**: Fail-fast dans le constructeur
- **i18n**: Support des 7 pays (FR, EN, DE, ES, IT, NL, BE) avec accents, particules, apostrophes
- **Storage**: JSON en base (flexibilité future: middle name, suffix, etc.)
- **Parsing**: Logique simple (premier mot = firstName, reste = lastName)
- **Normalisation**: Automatique (trim, capitalize, collapse spaces)
- **Definition of Done**: Voir `/Users/tmonier/Projects/hotones/project-management/prd.md` section "Définition de Done"
