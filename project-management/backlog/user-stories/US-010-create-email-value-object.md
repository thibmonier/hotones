# US-010: Créer Value Object Email avec validation RFC 5322

**EPIC:** [EPIC-002](../epics/EPIC-002-value-objects.md) - Implémentation des Value Objects
**Priorité:** 🔴 CRITIQUE
**Points:** 2
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer un Value Object Email avec validation RFC 5322
**Afin de** centraliser la validation des emails et garantir la type safety dans le Domain

---

## Critères d'acceptation

### GIVEN: Le projet a la structure Domain/Shared/ValueObject/

**WHEN:** Je crée le Value Object Email

**THEN:**
- [ ] Classe `src/Domain/Shared/ValueObject/Email.php` créée
- [ ] Classe `final readonly` (immutable)
- [ ] Validation RFC 5322 dans le constructeur (fail-fast)
- [ ] Factory method `fromString()` statique
- [ ] Méthode `getValue()` retourne le string
- [ ] Méthode `getDomain()` extrait le domaine (après @)
- [ ] Méthode `equals()` pour comparaison par valeur
- [ ] Méthode `__toString()` pour cast string
- [ ] Aucun setter (immutabilité)
- [ ] Normalisation (lowercase, trim) dans factory

### GIVEN: Le Value Object Email existe

**WHEN:** J'exécute PHPStan niveau max sur src/Domain/Shared/

**THEN:**
- [ ] Aucune erreur PHPStan
- [ ] Aucun type mixte
- [ ] Aucune property non initialisée

### GIVEN: Le Value Object Email existe

**WHEN:** J'exécute les tests unitaires

**THEN:**
- [ ] Tests unitaires passent avec couverture ≥ 90%
- [ ] Tests de validation (emails valides/invalides)
- [ ] Tests de normalisation (lowercase, trim)
- [ ] Tests de méthode `equals()`
- [ ] Tests de méthode `getDomain()`
- [ ] Tests s'exécutent en moins de 50ms

---

## Tâches techniques

### [DOMAIN] Créer Value Object Email (1h)

**Fichier:** `src/Domain/Shared/ValueObject/Email.php`

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

/**
 * Email value object with RFC 5322 validation.
 *
 * Immutable email address representation ensuring:
 * - Valid format (RFC 5322 compliant)
 * - Normalized (lowercase, trimmed)
 * - Type safety (cannot pass string where Email expected)
 *
 * @example
 * $email = Email::fromString('john.doe@example.com');
 * echo $email->getValue();      // "john.doe@example.com"
 * echo $email->getDomain();     // "example.com"
 */
final readonly class Email
{
    /**
     * RFC 5322 simplified pattern.
     * Matches most common email formats.
     */
    private const string PATTERN = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    private function __construct(
        private string $value,
    ) {
        $this->validate();
    }

    /**
     * Create Email from string.
     *
     * Normalizes the email (lowercase, trim).
     *
     * @throws \InvalidArgumentException if email format is invalid
     */
    public static function fromString(string $value): self
    {
        // ✅ Normalize: lowercase + trim
        $normalized = strtolower(trim($value));

        return new self($normalized);
    }

    /**
     * Get the email address as string.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Extract the domain part (after @).
     *
     * @example
     * Email::fromString('user@example.com')->getDomain() // "example.com"
     */
    public function getDomain(): string
    {
        $parts = explode('@', $this->value);

        return $parts[1];
    }

    /**
     * Compare two emails by value.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Validate email format (RFC 5322).
     *
     * @throws \InvalidArgumentException if invalid format
     */
    private function validate(): void
    {
        // ✅ Fail-fast: validation in constructor
        if (preg_match(self::PATTERN, $this->value) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email address: %s', $this->value)
            );
        }

        // ✅ Additional validation: filter_var (RFC 5322)
        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email address format: %s', $this->value)
            );
        }
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
```

**Actions:**
- Créer `src/Domain/Shared/ValueObject/Email.php`
- Utiliser `final readonly class` (immutabilité PHP 8.2+)
- Validation dans constructeur (fail-fast)
- Factory `fromString()` avec normalisation (lowercase, trim)
- Méthode `equals()` pour comparaison
- Méthode `getDomain()` pour extraire domaine
- Pattern regex + filter_var() pour validation stricte

### [INFRA] Créer Doctrine Custom Type pour Email (1h)

**Fichier:** `src/Infrastructure/Persistence/Doctrine/Type/EmailType.php`

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\ValueObject\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;

/**
 * Doctrine custom type for Email value object.
 *
 * Maps Email VO to VARCHAR column in database.
 */
final class EmailType extends StringType
{
    public const string NAME = 'email';

    /**
     * Convert database value to Email VO.
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Email
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
            return Email::fromString($value);
        } catch (\InvalidArgumentException $e) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    }

    /**
     * Convert Email VO to database value.
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Email) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', Email::class]
            );
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Requires SQL comment hint for Doctrine migrations.
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

**Actions:**
- Créer `src/Infrastructure/Persistence/Doctrine/Type/EmailType.php`
- Extends `StringType` de Doctrine DBAL
- Méthode `convertToPHPValue()`: string → Email VO
- Méthode `convertToDatabaseValue()`: Email VO → string
- Gestion des valeurs NULL
- Exceptions ConversionException si type invalide

### [CONFIG] Enregistrer le Custom Type Doctrine (0.5h)

**Fichier:** `config/packages/doctrine.yaml`

**Configuration:**

```yaml
# config/packages/doctrine.yaml

doctrine:
    dbal:
        types:
            # ✅ Email custom type
            email: App\Infrastructure\Persistence\Doctrine\Type\EmailType

        mapping_types:
            email: string

    orm:
        # ... existing config
```

**Actions:**
- Ajouter type `email` dans `doctrine.dbal.types`
- Lier à la classe `EmailType`
- Ajouter mapping type `email: string`

**Validation:**

```bash
# Vérifier que le type est enregistré
make console CMD="dbal:types"

# Output attendu:
# email => App\Infrastructure\Persistence\Doctrine\Type\EmailType
```

### [TEST] Créer tests unitaires Email (1h)

**Fichier:** `tests/Unit/Domain/Shared/ValueObject/EmailTest.php`

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    /**
     * @test
     * @dataProvider validEmailProvider
     */
    public function it_creates_email_from_valid_string(string $validEmail): void
    {
        // When
        $email = Email::fromString($validEmail);

        // Then
        self::assertInstanceOf(Email::class, $email);
    }

    /**
     * @test
     */
    public function it_normalizes_email_to_lowercase(): void
    {
        // Given
        $input = 'John.Doe@EXAMPLE.COM';

        // When
        $email = Email::fromString($input);

        // Then
        self::assertEquals('john.doe@example.com', $email->getValue());
    }

    /**
     * @test
     */
    public function it_trims_whitespace(): void
    {
        // Given
        $input = '  user@example.com  ';

        // When
        $email = Email::fromString($input);

        // Then
        self::assertEquals('user@example.com', $email->getValue());
    }

    /**
     * @test
     */
    public function it_extracts_domain_part(): void
    {
        // Given
        $email = Email::fromString('john.doe@example.com');

        // When
        $domain = $email->getDomain();

        // Then
        self::assertEquals('example.com', $domain);
    }

    /**
     * @test
     */
    public function it_compares_emails_by_value(): void
    {
        // Given
        $email1 = Email::fromString('user@example.com');
        $email2 = Email::fromString('user@example.com');
        $email3 = Email::fromString('other@example.com');

        // Then
        self::assertTrue($email1->equals($email2));
        self::assertFalse($email1->equals($email3));
    }

    /**
     * @test
     */
    public function it_converts_to_string(): void
    {
        // Given
        $email = Email::fromString('user@example.com');

        // When
        $string = (string) $email;

        // Then
        self::assertEquals('user@example.com', $string);
    }

    /**
     * @test
     * @dataProvider invalidEmailProvider
     */
    public function it_throws_exception_for_invalid_email(string $invalidEmail): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address');

        // When
        Email::fromString($invalidEmail);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_empty_email(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);

        // When
        Email::fromString('');
    }

    /**
     * Valid email addresses.
     *
     * @return array<string, array{string}>
     */
    public static function validEmailProvider(): array
    {
        return [
            'simple' => ['user@example.com'],
            'with dots' => ['john.doe@example.com'],
            'with plus' => ['user+tag@example.com'],
            'with hyphen' => ['user-name@example.com'],
            'with underscore' => ['user_name@example.com'],
            'subdomain' => ['user@mail.example.com'],
            'long tld' => ['user@example.travel'],
            'numbers' => ['user123@example456.com'],
        ];
    }

    /**
     * Invalid email addresses.
     *
     * @return array<string, array{string}>
     */
    public static function invalidEmailProvider(): array
    {
        return [
            'no at sign' => ['userexample.com'],
            'no domain' => ['user@'],
            'no local part' => ['@example.com'],
            'spaces' => ['user name@example.com'],
            'double at' => ['user@@example.com'],
            'no tld' => ['user@example'],
            'starts with dot' => ['.user@example.com'],
            'ends with dot' => ['user.@example.com'],
            'special chars' => ['user!#$%@example.com'],
        ];
    }
}
```

**Actions:**
- Créer `tests/Unit/Domain/Shared/ValueObject/EmailTest.php`
- Tests de création avec emails valides (data provider)
- Tests de normalisation (lowercase, trim)
- Tests de méthodes utilitaires (getDomain, equals, __toString)
- Tests d'exceptions avec emails invalides (data provider)
- Tests edge cases (empty string, whitespace)
- Couverture ≥ 90%

### [TEST] Créer tests d'intégration Doctrine Type (0.5h)

**Fichier:** `tests/Integration/Infrastructure/Persistence/Doctrine/Type/EmailTypeTest.php`

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\ValueObject\Email;
use App\Infrastructure\Persistence\Doctrine\Type\EmailType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

final class EmailTypeTest extends TestCase
{
    private EmailType $type;
    private PostgreSQLPlatform $platform;

    protected function setUp(): void
    {
        if (!Type::hasType(EmailType::NAME)) {
            Type::addType(EmailType::NAME, EmailType::class);
        }

        $this->type = Type::getType(EmailType::NAME);
        $this->platform = new PostgreSQLPlatform();
    }

    /**
     * @test
     */
    public function it_converts_null_to_php_value(): void
    {
        // When
        $phpValue = $this->type->convertToPHPValue(null, $this->platform);

        // Then
        self::assertNull($phpValue);
    }

    /**
     * @test
     */
    public function it_converts_string_to_email_object(): void
    {
        // Given
        $databaseValue = 'user@example.com';

        // When
        $phpValue = $this->type->convertToPHPValue($databaseValue, $this->platform);

        // Then
        self::assertInstanceOf(Email::class, $phpValue);
        self::assertEquals('user@example.com', $phpValue->getValue());
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_email_from_database(): void
    {
        // Given
        $invalidDatabaseValue = 'not-an-email';

        // Expect
        $this->expectException(ConversionException::class);

        // When
        $this->type->convertToPHPValue($invalidDatabaseValue, $this->platform);
    }

    /**
     * @test
     */
    public function it_converts_email_object_to_database_value(): void
    {
        // Given
        $email = Email::fromString('user@example.com');

        // When
        $databaseValue = $this->type->convertToDatabaseValue($email, $this->platform);

        // Then
        self::assertEquals('user@example.com', $databaseValue);
    }

    /**
     * @test
     */
    public function it_converts_null_email_to_database_value(): void
    {
        // When
        $databaseValue = $this->type->convertToDatabaseValue(null, $this->platform);

        // Then
        self::assertNull($databaseValue);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_type_to_database(): void
    {
        // Given
        $invalidValue = 'plain-string';

        // Expect
        $this->expectException(ConversionException::class);

        // When
        $this->type->convertToDatabaseValue($invalidValue, $this->platform);
    }

    /**
     * @test
     */
    public function it_returns_correct_type_name(): void
    {
        // When
        $name = $this->type->getName();

        // Then
        self::assertEquals('email', $name);
    }
}
```

**Actions:**
- Créer tests d'intégration Doctrine Type
- Tests de conversion PHP → Database
- Tests de conversion Database → PHP
- Tests de valeurs NULL
- Tests d'exceptions pour types invalides
- Vérifier nom du type ('email')

### [DOC] Documenter Value Object Email (0.5h)

**Fichier:** `.claude/examples/value-object-email.md`

**Documentation:**

```markdown
# Value Object: Email

## Description

Value Object immuable représentant une adresse email validée selon RFC 5322.

## Caractéristiques

- ✅ Immutable (readonly)
- ✅ Validation RFC 5322
- ✅ Normalisation automatique (lowercase, trim)
- ✅ Type-safe (impossible de passer string à la place)
- ✅ Comparaison par valeur (equals)

## Utilisation

### Création

```php
<?php

use App\Domain\Shared\ValueObject\Email;

// ✅ Email valide
$email = Email::fromString('john.doe@example.com');

// ✅ Normalisation automatique
$email = Email::fromString('  JOHN.DOE@EXAMPLE.COM  ');
echo $email->getValue(); // "john.doe@example.com"

// ❌ Email invalide → Exception
try {
    $email = Email::fromString('invalid-email');
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage(); // "Invalid email address: invalid-email"
}
```

### Méthodes

```php
<?php

$email = Email::fromString('john.doe@example.com');

// Obtenir la valeur
echo $email->getValue();      // "john.doe@example.com"

// Extraire le domaine
echo $email->getDomain();     // "example.com"

// Comparaison
$other = Email::fromString('john.doe@example.com');
$email->equals($other);       // true

// Cast string
echo (string) $email;         // "john.doe@example.com"
```

### Utilisation dans les Entités

```php
<?php

namespace App\Domain\Client\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;

final class Client
{
    private ClientId $id;
    private Email $email; // ✅ Type fort, pas string

    private function __construct(
        ClientId $id,
        Email $email,
    ) {
        $this->id = $id;
        $this->email = $email;
    }

    public static function create(ClientId $id, Email $email): self
    {
        // ✅ Email déjà validé par le VO
        return new self($id, $email);
    }

    public function updateEmail(Email $newEmail): void
    {
        // ✅ Impossible de passer un string invalide
        $this->email = $newEmail;

        $this->recordEvent(new ClientEmailUpdatedEvent($this->id, $newEmail));
    }

    public function getEmail(): Email
    {
        return $this->email;
    }
}
```

### Persistence Doctrine

```xml
<!-- Infrastructure/Persistence/Doctrine/Mapping/Client.orm.xml -->
<doctrine-mapping>
    <entity name="App\Domain\Client\Entity\Client" table="client">
        <id name="id" type="client_id">
            <generator strategy="NONE"/>
        </id>

        <!-- ✅ Utilise le custom type 'email' -->
        <field name="email" type="email" column="email" length="255" nullable="false"/>
    </entity>
</doctrine-mapping>
```

### Tests

```php
<?php

use App\Domain\Shared\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailExampleTest extends TestCase
{
    public function test_example_usage(): void
    {
        // ✅ Création
        $email = Email::fromString('client@example.com');

        // ✅ Validation automatique
        self::assertEquals('client@example.com', $email->getValue());

        // ✅ Immutabilité
        $newEmail = Email::fromString('other@example.com');
        self::assertNotSame($email, $newEmail);

        // ✅ Comparaison
        self::assertFalse($email->equals($newEmail));
    }
}
```

## Validation Email RFC 5322

### Pattern regex utilisé

```regex
/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
```

**Explication:**
- `[a-zA-Z0-9._%+-]+` - Local part (avant @): lettres, chiffres, points, underscores, %, +, -
- `@` - Arobase obligatoire
- `[a-zA-Z0-9.-]+` - Domaine: lettres, chiffres, points, tirets
- `\.` - Point obligatoire avant TLD
- `[a-zA-Z]{2,}` - TLD: minimum 2 lettres (.fr, .com, .travel, etc.)

### Validation double

1. **Regex pattern** - Validation rapide du format
2. **filter_var(FILTER_VALIDATE_EMAIL)** - Validation RFC 5322 stricte PHP

Cette double validation garantit:
- ✅ Format correct
- ✅ Conformité RFC 5322
- ✅ Protection contre les emails malformés

### Emails acceptés

```
✅ user@example.com
✅ john.doe@example.com
✅ user+tag@example.com
✅ user_name@example.com
✅ user-name@example.com
✅ user123@example456.com
✅ user@mail.example.com
✅ user@example.travel
```

### Emails rejetés

```
❌ userexample.com (pas de @)
❌ user@ (pas de domaine)
❌ @example.com (pas de local part)
❌ user name@example.com (espace)
❌ user@@example.com (double @)
❌ user@example (pas de TLD)
❌ .user@example.com (commence par point)
❌ user.@example.com (finit par point)
```
```

**Actions:**
- Créer `.claude/examples/value-object-email.md`
- Documenter caractéristiques du VO Email
- Exemples d'utilisation (création, validation, normalisation)
- Utilisation dans les entités Domain
- Mapping Doctrine XML
- Exemples de tests
- Documentation validation RFC 5322
- Pattern regex expliqué
- Liste emails valides/invalides

### [VALIDATION] Valider avec outils qualité (0.5h)

**Commandes:**

```bash
# PHPStan niveau max
make phpstan

# Vérifier aucune erreur sur Email.php
# Output attendu: [OK] No errors

# PHP-CS-Fixer
make cs-check

# Tests unitaires
make test-unit

# Output attendu:
# tests/Unit/Domain/Shared/ValueObject/EmailTest.php
# ✅ OK (15 tests, 25 assertions)

# Coverage
make test-coverage

# Vérifier: Email.php à 100% coverage
```

**Validation:**
- [ ] PHPStan niveau max: 0 erreur
- [ ] CS-Fixer: conforme PSR-12
- [ ] Tests unitaires: 100% pass
- [ ] Coverage: ≥ 90% sur Email.php
- [ ] Doctrine Type enregistré
- [ ] Autoload fonctionne

---

## Définition de Done (DoD)

- [ ] Value Object `src/Domain/Shared/ValueObject/Email.php` créé
- [ ] Classe `final readonly` (immutabilité)
- [ ] Validation RFC 5322 dans constructeur (fail-fast)
- [ ] Factory method `fromString()` avec normalisation (lowercase, trim)
- [ ] Méthode `getValue()` retourne string
- [ ] Méthode `getDomain()` extrait domaine
- [ ] Méthode `equals()` pour comparaison par valeur
- [ ] Méthode `__toString()` pour cast string
- [ ] Aucun setter (immutabilité garantie)
- [ ] Doctrine Custom Type `EmailType` créé dans Infrastructure
- [ ] Type `email` enregistré dans `doctrine.yaml`
- [ ] Tests unitaires créés avec couverture ≥ 90%
- [ ] Tests d'intégration Doctrine Type créés
- [ ] Data providers pour emails valides/invalides
- [ ] Tests de normalisation (lowercase, trim)
- [ ] Tests de méthodes utilitaires (getDomain, equals, __toString)
- [ ] PHPStan niveau max passe sans erreur
- [ ] Aucune dépendance externe (pur PHP + SPL)
- [ ] Documentation créée dans `.claude/examples/value-object-email.md`
- [ ] Exemples d'utilisation documentés
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(domain): create Email value object with RFC 5322 validation`

---

## Notes techniques

### Pattern Value Object

Le Value Object Email suit les principes DDD:

1. **Immutabilité**
   - `final readonly class` (PHP 8.2+)
   - Aucun setter
   - Modifications créent de nouvelles instances

2. **Validation fail-fast**
   - Validation dans le constructeur
   - Impossible de créer un Email invalide
   - Exceptions claires et explicites

3. **Égalité par valeur**
   - Méthode `equals()` compare les valeurs
   - Pas d'identité (pas d'ID)
   - Deux emails avec même valeur sont égaux

4. **Factory method**
   - Constructeur `private`
   - Factory `fromString()` statique
   - Permet normalisation avant construction

5. **Type safety**
   - Impossible de passer `string` là où `Email` attendu
   - PHPStan détecte les erreurs de type
   - Contrat strict entre couches

### Avantages du Value Object Email

| Avant (string primitif) | Après (Email VO) |
|------------------------|------------------|
| `private string $email;` | `private Email $email;` |
| Validation dispersée (Controller, Form, Entity) | Validation centralisée (VO) |
| Possible de passer email invalide | Impossible (validation constructor) |
| Type faible (string) | Type fort (Email) |
| Duplication validation | Single Source of Truth |
| Erreurs runtime | Erreurs compile-time (PHPStan) |

### Normalisation Email

```php
<?php

// Input utilisateur
$input = '  John.Doe@EXAMPLE.COM  ';

// ✅ Normalisation automatique
$email = Email::fromString($input);

// Output
$email->getValue(); // "john.doe@example.com"

// Avantages:
// 1. Comparaisons cohérentes (case-insensitive)
// 2. Pas de doublons dûs à la casse
// 3. Pas d'espaces parasites
```

### Utilisation dans le Domain

```php
<?php

namespace App\Domain\Client\Entity;

// ✅ Avant
class Client
{
    private string $email; // ❌ Type primitif

    public function setEmail(string $email): void
    {
        // ❌ Validation dans l'entité (duplication)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }

        $this->email = $email;
    }
}

// ✅ Après
final class Client
{
    private Email $email; // ✅ Type fort

    public function updateEmail(Email $email): void
    {
        // ✅ Email déjà validé, pas de duplication
        $this->email = $email;

        $this->recordEvent(new ClientEmailUpdatedEvent($this->id, $email));
    }
}
```

### Migration progressive

1. **Phase 1:** Créer Email VO ← US-010 (cette US)
2. **Phase 2:** Créer Doctrine Custom Type
3. **Phase 3:** Remplacer `string $email` par `Email $email` dans entités (US-018)
4. **Phase 4:** Migration base de données (pas de changement schéma, juste mapping)

---

## Dépendances

### Bloquantes

- **US-001**: Structure Domain créée (nécessite `src/Domain/Shared/ValueObject/`)

### Bloque

- **US-002**: Extraction Client (utilisera Email VO)
- **US-004**: Extraction User (utilisera Email VO)
- **US-018**: Remplacement types primitifs (remplacera string par Email)
- **US-020**: ClientRepositoryInterface (méthode findByEmail utilisera Email VO)

---

## Références

- `.claude/rules/18-value-objects.md` (lignes 1-120, Value Object pattern)
- `.claude/rules/02-architecture-clean-ddd.md` (lignes 45-155, Domain purity)
- `.claude/examples/value-object-examples.md` (exemple Email)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 75-108, absence Value Objects)
- **Livre:** *Domain-Driven Design* - Eric Evans, Chapitre 5 (Value Objects)
- **Livre:** *Implementing Domain-Driven Design* - Vaughn Vernon, Chapitre 6 (Value Objects)
- **RFC 5322:** [Email Address Specification](https://datatracker.ietf.org/doc/html/rfc5322)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |

---

## Exemple d'usage complet

### Scénario: Création d'un Client avec Email

```php
<?php

// 1. Dans un Controller (Presentation)
$email = Email::fromString($request->request->get('email')); // ✅ Validation ici

$command = new CreateClientCommand(
    clientId: ClientId::generate(),
    name: $request->request->get('name'),
    email: $email, // ✅ Type Email (pas string)
);

$this->commandBus->dispatch($command);

// 2. Dans le CommandHandler (Application)
final readonly class CreateClientCommandHandler
{
    public function __invoke(CreateClientCommand $command): void
    {
        // ✅ email déjà validé (Email VO)
        $client = Client::create(
            $command->clientId,
            $command->name,
            $command->email // ✅ Type Email
        );

        $this->clientRepository->save($client);
    }
}

// 3. Dans l'Entité Domain
final class Client
{
    private Email $email; // ✅ Type fort

    public static function create(
        ClientId $id,
        string $name,
        Email $email // ✅ Impossible de passer string invalide
    ): self {
        return new self($id, $name, $email);
    }

    public function updateEmail(Email $newEmail): void
    {
        $oldEmail = $this->email;
        $this->email = $newEmail;

        // ✅ Domain Event
        $this->recordEvent(
            new ClientEmailUpdatedEvent($this->id, $oldEmail, $newEmail)
        );
    }
}

// 4. Persistence (Infrastructure)
// Le Doctrine Custom Type convertit automatiquement Email ↔ string
```

### Avantages démontrés

1. **Type Safety**
   ```php
   // ❌ AVANT: Possibilité d'erreur
   function sendEmail(string $email) { ... }
   sendEmail('not-an-email'); // Compile OK, erreur runtime

   // ✅ APRÈS: Erreur compile-time
   function sendEmail(Email $email) { ... }
   sendEmail('not-an-email'); // ❌ Erreur PHPStan!
   sendEmail(Email::fromString('valid@example.com')); // ✅ OK
   ```

2. **Validation centralisée**
   ```php
   // ❌ AVANT: Validation dupliquée partout
   // - Controller: validation formulaire
   // - Entity: validation setter
   // - Service: validation métier
   // → 3 endroits différents!

   // ✅ APRÈS: Validation unique
   // - Email VO: validation constructor
   // → 1 seul endroit (Single Source of Truth)
   ```

3. **Immutabilité**
   ```php
   // ❌ AVANT: Mutation accidentelle possible
   $client->email = 'changed@example.com'; // ⚠️ Bypass validation!

   // ✅ APRÈS: Immutabilité garantie
   $client->email = Email::fromString('changed@example.com'); // ❌ Erreur: propriété readonly
   $client->updateEmail(Email::fromString('changed@example.com')); // ✅ Méthode explicite
   ```

---

## Anti-patterns à éviter

### ❌ Email avec setter

```php
<?php

// ❌ MAUVAIS: Email mutable
class Email
{
    private string $value;

    public function setValue(string $value): void // ❌ Setter interdit!
    {
        $this->value = $value;
    }
}
```

### ❌ Validation dans l'entité

```php
<?php

// ❌ MAUVAIS: Validation dupliquée
final class Client
{
    private string $email; // ❌ Type primitif

    public function setEmail(string $email): void
    {
        // ❌ Validation ici (duplication)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }

        $this->email = $email;
    }
}

// ✅ BON: Validation dans le VO uniquement
final class Client
{
    private Email $email; // ✅ Type fort

    public function updateEmail(Email $email): void
    {
        // ✅ Email déjà validé, pas de duplication
        $this->email = $email;
    }
}
```

### ❌ Email avec ID

```php
<?php

// ❌ MAUVAIS: Email comme Entity (avec ID)
class Email
{
    private int $id; // ❌ Pas d'identité pour un VO
    private string $value;

    // ...
}

// ✅ BON: Email comme Value Object (pas d'ID)
final readonly class Email
{
    // ✅ Identifié par sa valeur, pas par un ID
    private function __construct(
        private string $value
    ) {}

    public function equals(self $other): bool
    {
        return $this->value === $other->value; // ✅ Comparaison par valeur
    }
}
```

---

## Checklist Value Object

- [ ] Classe `final readonly`
- [ ] Constructor `private`
- [ ] Factory method `fromString()` statique
- [ ] Validation dans constructor (fail-fast)
- [ ] Normalisation dans factory (lowercase, trim)
- [ ] Méthode `getValue()` ou getters spécifiques
- [ ] Méthode `equals()` pour comparaison
- [ ] Méthode `__toString()` si pertinent
- [ ] **Aucun setter** (immutabilité)
- [ ] Aucune dépendance externe (pur PHP)
- [ ] Tests unitaires ≥ 90% coverage
- [ ] Data providers pour validation
- [ ] Doctrine Custom Type créé
- [ ] Type enregistré dans doctrine.yaml
- [ ] Documentation avec exemples
