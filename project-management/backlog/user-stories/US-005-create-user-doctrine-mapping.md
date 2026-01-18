# US-005: Créer le mapping Doctrine XML pour User dans Infrastructure

**EPIC:** [EPIC-001](../epics/EPIC-001-clean-architecture-restructuring.md) - Restructuration Clean Architecture
**Priorité:** 🔴 CRITIQUE
**Points:** 3
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer le mapping Doctrine XML pour l'entité User dans la couche Infrastructure
**Afin de** séparer la persistance de la logique métier tout en maintenant l'intégration avec Symfony Security

---

## Critères d'acceptation

### GIVEN: L'entité User Domain pure existe (US-004)

**WHEN:** Je crée le mapping Doctrine XML dans Infrastructure

**THEN:**
- [ ] Fichier `src/Infrastructure/Persistence/Doctrine/Mapping/User.orm.xml` créé
- [ ] Mapping complet pour toutes les propriétés User
- [ ] Value Objects mappés via Doctrine Custom Types (UserId, UserRole, HashedPassword)
- [ ] Indexes créés pour performance (email, isActive, createdAt)
- [ ] Contraintes d'unicité (email unique)
- [ ] Relations mappées si nécessaire
- [ ] Configuration Doctrine mise à jour

### GIVEN: Le mapping XML existe

**WHEN:** J'exécute les commandes Doctrine

**THEN:**
- [ ] `make console CMD="doctrine:mapping:info"` affiche User entity
- [ ] `make console CMD="doctrine:schema:validate"` passe sans erreur
- [ ] Migration générée contient la table `users` correcte
- [ ] Aucune erreur de mapping détectée

### GIVEN: Le mapping est valide

**WHEN:** J'exécute les tests d'intégration

**THEN:**
- [ ] Tests de persistance passent (save/find)
- [ ] Tests d'hydratation passent (DB → Entity)
- [ ] Tests des Custom Types passent (UserId, UserRole, HashedPassword)
- [ ] Tests d'unicité email passent (contrainte respectée)
- [ ] Tests de Symfony Security passent (authentication)

---

## Tâches techniques

### [INFRA] Créer le mapping Doctrine XML (2h)

**Fichier:** `src/Infrastructure/Persistence/Doctrine/Mapping/User.orm.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Domain\User\Entity\User"
            table="users"
            repository-class="App\Infrastructure\Persistence\Doctrine\Repository\DoctrineUserRepository">

        <!-- Primary Key: UserId Value Object -->
        <id name="id" type="user_id" column="id">
            <generator strategy="NONE"/> <!-- UUID généré par Domain -->
        </id>

        <!-- PersonName Value Object (embedded) -->
        <embedded name="name" class="App\Domain\Shared\ValueObject\PersonName" use-column-prefix="false">
            <field name="firstName" column="first_name" type="string" length="100" nullable="false"/>
            <field name="lastName" column="last_name" type="string" length="100" nullable="false"/>
        </embedded>

        <!-- Email Value Object -->
        <field name="email" type="email" column="email" length="255" nullable="false" unique="true"/>

        <!-- HashedPassword Value Object (Custom Type) -->
        <field name="password" type="hashed_password" column="password" length="255" nullable="false"/>

        <!-- UserRole Value Object (Enum Custom Type) -->
        <field name="role" type="user_role" column="role" length="50" nullable="false"/>

        <!-- Active Status -->
        <field name="isActive" type="boolean" column="is_active" nullable="false"/>

        <!-- Timestamps (DateTimeImmutable) -->
        <field name="createdAt" type="datetime_immutable" column="created_at" nullable="false"/>
        <field name="updatedAt" type="datetime_immutable" column="updated_at" nullable="false"/>

        <!-- Indexes pour performance -->
        <indexes>
            <index name="idx_user_email" columns="email"/>
            <index name="idx_user_active" columns="is_active"/>
            <index name="idx_user_role" columns="role"/>
            <index name="idx_user_created_at" columns="created_at"/>
        </indexes>

        <!-- Contraintes d'unicité -->
        <unique-constraints>
            <unique-constraint name="uniq_user_email" columns="email"/>
        </unique-constraints>

    </entity>

</doctrine-mapping>
```

**Actions:**
- Créer le fichier XML dans `src/Infrastructure/Persistence/Doctrine/Mapping/`
- Mapper toutes les propriétés de l'entité User
- Utiliser les Custom Types pour Value Objects
- Définir les indexes de performance
- Définir les contraintes d'unicité
- Configurer la stratégie de génération d'ID (NONE - géré par Domain)

### [INFRA] Créer les Doctrine Custom Types (2.5h)

#### UserId Custom Type

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\User\ValueObject\UserId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

final class UserIdType extends GuidType
{
    public const NAME = 'user_id';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?UserId
    {
        if ($value === null) {
            return null;
        }

        return UserId::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof UserId) {
            throw new \InvalidArgumentException(
                sprintf('Expected UserId, got %s', get_debug_type($value))
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

#### UserRole Custom Type

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\User\ValueObject\UserRole;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class UserRoleType extends StringType
{
    public const NAME = 'user_role';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?UserRole
    {
        if ($value === null) {
            return null;
        }

        return UserRole::from($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof UserRole) {
            throw new \InvalidArgumentException(
                sprintf('Expected UserRole, got %s', get_debug_type($value))
            );
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

#### HashedPassword Custom Type

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\User\ValueObject\HashedPassword;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class HashedPasswordType extends StringType
{
    public const NAME = 'hashed_password';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?HashedPassword
    {
        if ($value === null) {
            return null;
        }

        return HashedPassword::fromHash($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof HashedPassword) {
            throw new \InvalidArgumentException(
                sprintf('Expected HashedPassword, got %s', get_debug_type($value))
            );
        }

        return $value->getHash();
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = 255;
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

**Actions:**
- Créer `UserIdType.php` (conversion UUID ↔ UserId VO)
- Créer `UserRoleType.php` (conversion string ↔ UserRole enum)
- Créer `HashedPasswordType.php` (conversion string ↔ HashedPassword VO)
- Enregistrer les types dans `config/packages/doctrine.yaml`

### [CONFIG] Configurer Doctrine pour les Custom Types (0.5h)

**Fichier:** `config/packages/doctrine.yaml`

```yaml
doctrine:
    dbal:
        types:
            # Custom Types existants
            email: App\Infrastructure\Persistence\Doctrine\Type\EmailType
            person_name: App\Infrastructure\Persistence\Doctrine\Type\PersonNameType
            client_id: App\Infrastructure\Persistence\Doctrine\Type\ClientIdType

            # ✅ Nouveaux Custom Types pour User
            user_id: App\Infrastructure\Persistence\Doctrine\Type\UserIdType
            user_role: App\Infrastructure\Persistence\Doctrine\Type\UserRoleType
            hashed_password: App\Infrastructure\Persistence\Doctrine\Type\HashedPasswordType

        mapping_types:
            enum: string

    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        report_fields_where_declared: true
        validate_xml_mapping: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true

        mappings:
            User:
                is_bundle: false
                type: xml
                dir: '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/Mapping'
                prefix: 'App\Domain\User\Entity'
                alias: User
```

**Actions:**
- Ajouter les Custom Types user_id, user_role, hashed_password
- Configurer le mapping XML pour User entities
- Valider avec `make console CMD="doctrine:mapping:info"`

### [TEST] Créer tests d'intégration Doctrine (2h)

**Fichier:** `tests/Integration/Infrastructure/Persistence/Doctrine/Repository/DoctrineUserRepositoryTest.php`

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserRole;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\PersonName;
use App\Infrastructure\Persistence\Doctrine\Repository\DoctrineUserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = self::getContainer()->get(DoctrineUserRepository::class);
    }

    /**
     * @test
     */
    public function it_persists_and_hydrates_user_entity(): void
    {
        // Given
        $user = User::create(
            UserId::generate(),
            PersonName::fromFirstAndLastName('Jean', 'Dupont'),
            Email::fromString('jean.dupont@example.com'),
            HashedPassword::fromHash('$2y$10$abcdefghijklmnopqrstuv'), // Fake bcrypt hash
            UserRole::USER
        );

        // When
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Then
        $found = $this->repository->findById($user->getId());

        $this->assertNotNull($found);
        $this->assertEquals($user->getId()->getValue(), $found->getId()->getValue());
        $this->assertEquals('Jean', $found->getName()->getFirstName());
        $this->assertEquals('Dupont', $found->getName()->getLastName());
        $this->assertEquals('jean.dupont@example.com', $found->getEmail()->getValue());
        $this->assertEquals(UserRole::USER, $found->getRole());
        $this->assertTrue($found->getIsActive());
    }

    /**
     * @test
     */
    public function it_persists_user_role_enum_correctly(): void
    {
        // Given
        $admin = User::create(
            UserId::generate(),
            PersonName::fromFirstAndLastName('Admin', 'User'),
            Email::fromString('admin@example.com'),
            HashedPassword::fromHash('$2y$10$abcdefghijklmnopqrstuv'),
            UserRole::ADMIN
        );

        // When
        $this->entityManager->persist($admin);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Then
        $found = $this->repository->findById($admin->getId());

        $this->assertEquals(UserRole::ADMIN, $found->getRole());
        $this->assertTrue($found->isAdmin());
        $this->assertTrue($found->hasRole(UserRole::USER)); // Hierarchy check
    }

    /**
     * @test
     */
    public function it_persists_hashed_password_value_object(): void
    {
        // Given
        $passwordHash = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy';
        $user = User::create(
            UserId::generate(),
            PersonName::fromFirstAndLastName('Test', 'User'),
            Email::fromString('test@example.com'),
            HashedPassword::fromHash($passwordHash),
            UserRole::USER
        );

        // When
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Then
        $found = $this->repository->findById($user->getId());

        $this->assertEquals($passwordHash, $found->getPassword()); // For Symfony Security
        $this->assertInstanceOf(HashedPassword::class, $found->getPassword()); // Domain VO
    }

    /**
     * @test
     */
    public function it_enforces_email_uniqueness_constraint(): void
    {
        // Given
        $email = Email::fromString('duplicate@example.com');

        $user1 = User::create(
            UserId::generate(),
            PersonName::fromFirstAndLastName('User', 'One'),
            $email,
            HashedPassword::fromHash('$2y$10$hash1'),
            UserRole::USER
        );

        $user2 = User::create(
            UserId::generate(),
            PersonName::fromFirstAndLastName('User', 'Two'),
            $email, // ❌ Duplicate email
            HashedPassword::fromHash('$2y$10$hash2'),
            UserRole::USER
        );

        $this->entityManager->persist($user1);
        $this->entityManager->flush();

        // Expect
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);

        // When
        $this->entityManager->persist($user2);
        $this->entityManager->flush();
    }

    /**
     * @test
     */
    public function it_updates_user_and_maintains_identity(): void
    {
        // Given
        $user = User::create(
            UserId::generate(),
            PersonName::fromFirstAndLastName('Jean', 'Dupont'),
            Email::fromString('jean@example.com'),
            HashedPassword::fromHash('$2y$10$oldhash'),
            UserRole::USER
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // When
        $found = $this->repository->findById($user->getId());
        $found->changeRole(UserRole::MANAGER);

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Then
        $updated = $this->repository->findById($user->getId());

        $this->assertEquals(UserRole::MANAGER, $updated->getRole());
        $this->assertEquals($user->getId()->getValue(), $updated->getId()->getValue()); // Same identity
    }

    /**
     * @test
     */
    public function it_persists_inactive_users(): void
    {
        // Given
        $user = User::create(
            UserId::generate(),
            PersonName::fromFirstAndLastName('Inactive', 'User'),
            Email::fromString('inactive@example.com'),
            HashedPassword::fromHash('$2y$10$hash'),
            UserRole::USER
        );

        $user->deactivate();

        // When
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Then
        $found = $this->repository->findById($user->getId());

        $this->assertFalse($found->getIsActive());
        $this->assertFalse($found->canLogin());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
```

**Actions:**
- Créer tests de persistance User
- Tester hydratation Entity depuis DB
- Tester Custom Types (UserId, UserRole, HashedPassword)
- Tester contrainte unicité email
- Tester intégration Symfony Security (authentication)
- Vérifier indexes utilisés

### [MIGRATION] Générer et tester la migration (1h)

```bash
# Générer migration
make console CMD="doctrine:migrations:diff"

# Migration générée: migrations/Version20260113XXXXXX.php
```

**SQL attendu:**

```sql
-- Migration: Create users table

CREATE TABLE users (
    id UUID NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

-- Comments pour les Custom Types
COMMENT ON COLUMN users.id IS '(DC2Type:user_id)';
COMMENT ON COLUMN users.email IS '(DC2Type:email)';
COMMENT ON COLUMN users.password IS '(DC2Type:hashed_password)';
COMMENT ON COLUMN users.role IS '(DC2Type:user_role)';

-- Indexes de performance
CREATE INDEX idx_user_email ON users (email);
CREATE INDEX idx_user_active ON users (is_active);
CREATE INDEX idx_user_role ON users (role);
CREATE INDEX idx_user_created_at ON users (created_at);

-- Contraintes d'unicité
CREATE UNIQUE INDEX uniq_user_email ON users (email);

-- Timestamps immutables
COMMENT ON COLUMN users.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN users.updated_at IS '(DC2Type:datetime_immutable)';
```

**Actions:**
- Exécuter `make console CMD="doctrine:migrations:diff"`
- Vérifier le SQL généré
- Tester la migration sur base vide: `make db-reset`
- Vérifier que la table `users` existe avec les bonnes colonnes
- Vérifier les indexes créés
- Vérifier la contrainte d'unicité sur email

### [DOC] Documenter le mapping et l'intégration Security (0.5h)

**Fichier:** `.claude/examples/doctrine-mapping-user.md`

```markdown
# Exemple: Mapping Doctrine XML pour User avec Symfony Security

## Mapping complet

L'entité User utilise Doctrine XML mapping pour séparer la persistance du domaine
tout en maintenant l'intégration avec Symfony Security.

## Custom Types créés

- `user_id`: Conversion UUID ↔ UserId Value Object
- `user_role`: Conversion string ↔ UserRole enum
- `hashed_password`: Conversion string ↔ HashedPassword Value Object

## Indexes de performance

- `idx_user_email`: Recherche par email (login)
- `idx_user_active`: Filtrage utilisateurs actifs
- `idx_user_role`: Filtrage par rôle
- `idx_user_created_at`: Tri chronologique

## Contraintes

- `uniq_user_email`: Email unique (RGPD + sécurité)

## Intégration Symfony Security

L'entité User implémente:
- `UserInterface`: Méthodes getUserIdentifier(), getRoles(), eraseCredentials()
- `PasswordAuthenticatedUserInterface`: Méthode getPassword()

Ces interfaces sont considérées comme des **standards autorisés** dans le Domain
car elles ne créent pas de couplage fort avec Symfony (contrats stables).

## Exemple d'usage

```php
// Création d'un utilisateur (Application Layer)
$user = User::create(
    UserId::generate(),
    PersonName::fromFirstAndLastName('Jean', 'Dupont'),
    Email::fromString('jean.dupont@example.com'),
    HashedPassword::fromHash($passwordHasher->hash('plainPassword')),
    UserRole::USER
);

$userRepository->save($user);

// Symfony Security utilise automatiquement:
// - $user->getUserIdentifier() → 'jean.dupont@example.com'
// - $user->getRoles() → ['ROLE_USER']
// - $user->getPassword() → '$2y$10$...'
```
```

**Actions:**
- Documenter le mapping User complet
- Expliquer les Custom Types créés
- Expliquer l'intégration avec Symfony Security
- Donner des exemples d'usage

### [VALIDATION] Valider le mapping avec outils Doctrine (0.5h)

```bash
# Valider le mapping
make console CMD="doctrine:schema:validate"

# Output attendu:
# [Mapping]  OK - The mapping files are correct.
# [Database] OK - The database schema is in sync with the mapping files.

# Vérifier les métadonnées
make console CMD="doctrine:mapping:info"

# Output attendu:
# Found 2 mapped entities:
# [OK]   App\Domain\Client\Entity\Client
# [OK]   App\Domain\User\Entity\User
```

**Actions:**
- Exécuter `doctrine:schema:validate`
- Exécuter `doctrine:mapping:info`
- Vérifier que User entity est correctement mappée
- Corriger les erreurs éventuelles

---

## Définition de Done (DoD)

- [ ] Fichier `User.orm.xml` créé dans `src/Infrastructure/Persistence/Doctrine/Mapping/`
- [ ] Mapping complet pour toutes les propriétés User
- [ ] Custom Types créés (UserIdType, UserRoleType, HashedPasswordType)
- [ ] Custom Types enregistrés dans `doctrine.yaml`
- [ ] Indexes de performance créés (email, isActive, role, createdAt)
- [ ] Contrainte d'unicité sur email
- [ ] Migration Doctrine générée
- [ ] Migration testée sur base vide
- [ ] Table `users` créée avec bonnes colonnes et types
- [ ] Tests d'intégration passent (persistance, hydratation, Custom Types)
- [ ] Tests d'unicité email passent
- [ ] Tests Symfony Security passent (authentication)
- [ ] `doctrine:schema:validate` passe sans erreur
- [ ] `doctrine:mapping:info` affiche User entity
- [ ] Documentation complète du mapping
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(infrastructure): create Doctrine XML mapping for User entity`

---

## Notes techniques

### Stratégie d'ID

L'ID est géré par le **Domain** (pas de `AUTO_INCREMENT`):

```xml
<id name="id" type="user_id" column="id">
    <generator strategy="NONE"/> <!-- UUID généré par UserId::generate() -->
</id>
```

**Justification:**
- ✅ Domain contrôle l'identité
- ✅ UUIDs universellement uniques
- ✅ Pas de dépendance à la base de données
- ✅ IDs prévisibles avant persistance

### Custom Types Doctrine

Les Value Objects nécessitent des **Doctrine Custom Types** pour conversion automatique:

| Value Object | Custom Type | Colonne DB |
|--------------|-------------|------------|
| `UserId` | `user_id` | `UUID` |
| `UserRole` | `user_role` | `VARCHAR(50)` |
| `HashedPassword` | `hashed_password` | `VARCHAR(255)` |
| `Email` | `email` | `VARCHAR(255)` |
| `PersonName` | `person_name` (embedded) | `first_name`, `last_name` |

### Embedded Value Object: PersonName

```xml
<!-- PersonName est un embedded Value Object -->
<embedded name="name" class="App\Domain\Shared\ValueObject\PersonName" use-column-prefix="false">
    <field name="firstName" column="first_name" type="string" length="100"/>
    <field name="lastName" column="last_name" type="string" length="100"/>
</embedded>
```

**Avantages:**
- ✅ Pas de table séparée (performance)
- ✅ Cohésion name first/last
- ✅ Validation centralisée dans PersonName VO

### Indexes de performance

```xml
<indexes>
    <index name="idx_user_email" columns="email"/>        <!-- Login queries -->
    <index name="idx_user_active" columns="is_active"/>   <!-- Filter active users -->
    <index name="idx_user_role" columns="role"/>          <!-- Filter by role -->
    <index name="idx_user_created_at" columns="created_at"/> <!-- Sort by date -->
</indexes>
```

**Justification:**
- `email`: Login fréquent (WHERE email = ?)
- `isActive`: Filtrage utilisateurs actifs
- `role`: Requêtes admin (filtrage par rôle)
- `createdAt`: Tri chronologique

### Contraintes d'unicité

```xml
<unique-constraints>
    <unique-constraint name="uniq_user_email" columns="email"/>
</unique-constraints>
```

**Justification:**
- ✅ Email unique pour authentification
- ✅ RGPD: Un utilisateur = un email
- ✅ Sécurité: Prévient les doublons

### Intégration Symfony Security

L'entité User implémente `UserInterface` et `PasswordAuthenticatedUserInterface`:

```php
// ✅ getUserIdentifier() retourne l'email
public function getUserIdentifier(): string
{
    return $this->email->getValue();
}

// ✅ getRoles() retourne les rôles Symfony
public function getRoles(): array
{
    return $this->role->toSymfonyRoles(); // ['ROLE_USER'] ou ['ROLE_ADMIN', 'ROLE_USER']
}

// ✅ getPassword() retourne le hash
public function getPassword(): string
{
    return $this->password->getHash();
}
```

**Note importante:**
Les interfaces Symfony Security (`UserInterface`, `PasswordAuthenticatedUserInterface`) sont considérées comme des **contrats standards autorisés** dans le Domain car:
- Contrats stables (peu de breaking changes)
- Pas de couplage fort avec Symfony (interfaces simples)
- Alternative: créer nos propres interfaces + adapter (over-engineering)

### Validation du mapping

```bash
# 1. Valider le mapping
make console CMD="doctrine:mapping:info"

# Output attendu:
# Found 2 mapped entities:
# [OK]   App\Domain\Client\Entity\Client
# [OK]   App\Domain\User\Entity\User

# 2. Valider le schéma
make console CMD="doctrine:schema:validate"

# Output attendu:
# [Mapping]  OK - The mapping files are correct.
# [Database] FAIL - The database schema is not in sync with the current mapping file.
#
# (Normal: migration pas encore exécutée)

# 3. Générer migration
make console CMD="doctrine:migrations:diff"

# 4. Exécuter migration
make db-migrate

# 5. Re-valider
make console CMD="doctrine:schema:validate"

# Output attendu:
# [Mapping]  OK - The mapping files are correct.
# [Database] OK - The database schema is in sync with the mapping files.
```

### Exemple de migration générée

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260113120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table with authentication fields';
    }

    public function up(Schema $schema): void
    {
        // ✅ Table users
        $this->addSql('CREATE TABLE users (
            id UUID NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        // ✅ Comments Doctrine
        $this->addSql('COMMENT ON COLUMN users.id IS \'(DC2Type:user_id)\'');
        $this->addSql('COMMENT ON COLUMN users.email IS \'(DC2Type:email)\'');
        $this->addSql('COMMENT ON COLUMN users.password IS \'(DC2Type:hashed_password)\'');
        $this->addSql('COMMENT ON COLUMN users.role IS \'(DC2Type:user_role)\'');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // ✅ Indexes
        $this->addSql('CREATE INDEX idx_user_email ON users (email)');
        $this->addSql('CREATE INDEX idx_user_active ON users (is_active)');
        $this->addSql('CREATE INDEX idx_user_role ON users (role)');
        $this->addSql('CREATE INDEX idx_user_created_at ON users (created_at)');

        // ✅ Contrainte unicité
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON users (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
```

### Tests de performance

```php
<?php

/**
 * @test
 */
public function it_uses_index_for_email_lookup(): void
{
    // Given: 1000 users
    for ($i = 0; $i < 1000; $i++) {
        $user = User::create(
            UserId::generate(),
            PersonName::fromFirstAndLastName("User", "$i"),
            Email::fromString("user$i@example.com"),
            HashedPassword::fromHash('$2y$10$hash'),
            UserRole::USER
        );
        $this->entityManager->persist($user);
    }
    $this->entityManager->flush();
    $this->entityManager->clear();

    // When: Search by email
    $start = microtime(true);
    $found = $this->repository->findByEmail(Email::fromString('user500@example.com'));
    $duration = microtime(true) - $start;

    // Then: ✅ Fast lookup avec index (< 10ms)
    $this->assertNotNull($found);
    $this->assertLessThan(0.01, $duration, 'Email lookup should use index and be < 10ms');
}
```

---

## Dépendances

### Bloquantes

- **US-004**: Entité User Domain pure (nécessite l'entité comme source pour le mapping)
- **EPIC-002**: Value Objects Email, PersonName créés (US-010, US-014)

### Bloque

- **US-023**: DoctrineUserRepository implémentation (nécessite mapping XML pour persister)
- **US-031**: Refactorer les services pour utiliser UserRepositoryInterface

---

## Références

- `.claude/rules/02-architecture-clean-ddd.md` (lignes 255-320, mapping Doctrine XML)
- `.claude/rules/15-doctrine-extensions.md` (Doctrine Custom Types)
- `.claude/examples/doctrine-mapping-client.md` (US-003, exemple de mapping)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 45-73, problème entités couplées)
- **Doctrine:** [XML Mapping Reference](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/xml-mapping.html)
- **Symfony Security:** [UserInterface Documentation](https://symfony.com/doc/current/security/user_provider.html)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |
