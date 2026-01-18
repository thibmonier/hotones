# US-003: Créer le mapping Doctrine XML pour Client dans Infrastructure

**EPIC:** [EPIC-001](../epics/EPIC-001-clean-architecture-restructuring.md) - Restructuration Clean Architecture
**Priorité:** 🔴 CRITIQUE
**Points:** 3
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** créer le mapping Doctrine XML pour l'entité Client pure
**Afin de** séparer la configuration de persistance de la logique métier

---

## Critères d'acceptation

### GIVEN: L'entité Client pure existe dans src/Domain/Client/Entity/

**WHEN:** Je crée le mapping Doctrine XML

**THEN:**
- [ ] Fichier `src/Infrastructure/Persistence/Doctrine/Mapping/Client.orm.xml` créé
- [ ] Mapping XML valide selon le schéma Doctrine
- [ ] Tous les champs de l'entité mappés correctement
- [ ] Value Objects mappés avec Doctrine Custom Types
- [ ] Table `clients` configurée avec indexes appropriés
- [ ] Pas d'annotations Doctrine dans l'entité Domain
- [ ] Configuration valide selon `doctrine:schema:validate`

### GIVEN: Le mapping XML existe

**WHEN:** J'exécute `make db-migrate`

**THEN:**
- [ ] Migration générée automatiquement par Doctrine
- [ ] Migration crée la table `clients` avec toutes les colonnes
- [ ] Indexes créés pour email (unique) et status
- [ ] Timestamps `created_at` et `updated_at` avec defaults
- [ ] Migration s'exécute sans erreur
- [ ] `doctrine:schema:validate` passe

### GIVEN: Le mapping est configuré

**WHEN:** Je teste la persistance de l'entité Client

**THEN:**
- [ ] Repository peut sauvegarder un Client
- [ ] Repository peut récupérer un Client par ID
- [ ] Value Objects correctement hydratés (ClientId, PersonName, Email, ClientStatus)
- [ ] Timestamps automatiques fonctionnent
- [ ] Tests d'intégration passent
- [ ] Aucune erreur Doctrine au runtime

---

## Tâches techniques

### [INFRA] Créer le fichier de mapping XML (1.5h)

**Localisation:**
```
src/Infrastructure/Persistence/Doctrine/Mapping/Client.orm.xml
```

**Contenu complet:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Domain\Client\Entity\Client"
            table="clients"
            repository-class="App\Infrastructure\Persistence\Doctrine\Repository\DoctrineClientRepository">

        <!-- ID (Value Object ClientId) -->
        <id name="id" type="client_id" column="id">
            <generator strategy="NONE"/>
        </id>

        <!-- Value Object: PersonName (embedded) -->
        <embedded name="name" class="App\Domain\Shared\ValueObject\PersonName">
            <field name="value" type="string" length="255" column="name"/>
        </embedded>

        <!-- Value Object: Email (custom type) -->
        <field name="email" type="email" column="email" length="255" nullable="true" unique="true"/>

        <!-- Enum: ClientStatus (custom type) -->
        <field name="status" type="client_status" column="status" length="20"/>

        <!-- Timestamps (DateTimeImmutable) -->
        <field name="createdAt" type="datetime_immutable" column="created_at">
            <options>
                <option name="default">CURRENT_TIMESTAMP</option>
            </options>
        </field>

        <field name="updatedAt" type="datetime_immutable" column="updated_at">
            <options>
                <option name="default">CURRENT_TIMESTAMP</option>
            </options>
        </field>

        <!-- Indexes pour performance -->
        <indexes>
            <index name="idx_client_email" columns="email"/>
            <index name="idx_client_status" columns="status"/>
            <index name="idx_client_created_at" columns="created_at"/>
        </indexes>

        <!-- Unique constraint sur email -->
        <unique-constraints>
            <unique-constraint name="uniq_client_email" columns="email"/>
        </unique-constraints>

    </entity>

</doctrine-mapping>
```

**Actions:**
- Créer le répertoire `src/Infrastructure/Persistence/Doctrine/Mapping/` si inexistant
- Créer le fichier `Client.orm.xml`
- Configurer la table `clients`
- Mapper l'ID avec type custom `client_id`
- Mapper les Value Objects (PersonName embedded, Email et ClientStatus avec custom types)
- Configurer les timestamps
- Ajouter les indexes de performance
- Ajouter la contrainte d'unicité sur email

### [INFRA] Configurer Doctrine pour utiliser les mappings XML (0.5h)

**Configuration Doctrine:**

```yaml
# config/packages/doctrine.yaml

doctrine:
    dbal:
        # Custom types pour Value Objects
        types:
            client_id: App\Infrastructure\Persistence\Doctrine\Type\ClientIdType
            email: App\Infrastructure\Persistence\Doctrine\Type\EmailType
            client_status: App\Infrastructure\Persistence\Doctrine\Type\ClientStatusType
            person_name: App\Infrastructure\Persistence\Doctrine\Type\PersonNameType

    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: false

        mappings:
            # ✅ Mapping XML pour Domain entities
            Domain:
                is_bundle: false
                type: xml
                dir: '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/Mapping'
                prefix: 'App\Domain'
                alias: Domain
```

**Actions:**
- Modifier `config/packages/doctrine.yaml`
- Déclarer les Doctrine Custom Types
- Configurer le mapping XML pour le namespace `App\Domain`
- Désactiver `auto_mapping` pour éviter les annotations
- Pointer `dir` vers `src/Infrastructure/Persistence/Doctrine/Mapping/`

### [INFRA] Créer les Doctrine Custom Types (1h)

#### ClientIdType

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Client\ValueObject\ClientId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\GuidType;

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

        try {
            return ClientId::fromString($value);
        } catch (\InvalidArgumentException $e) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                'UUID v4'
            );
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ClientId) {
            return $value->getValue();
        }

        throw ConversionException::conversionFailedInvalidType(
            $value,
            $this->getName(),
            ['null', ClientId::class]
        );
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

#### ClientStatusType

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Client\ValueObject\ClientStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;

final class ClientStatusType extends StringType
{
    public const string NAME = 'client_status';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ClientStatus
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ClientStatus) {
            return $value;
        }

        try {
            return ClientStatus::from($value);
        } catch (\ValueError $e) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ClientStatus) {
            return $value->value;
        }

        throw ConversionException::conversionFailedInvalidType(
            $value,
            $this->getName(),
            ['null', ClientStatus::class]
        );
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
- Créer `src/Infrastructure/Persistence/Doctrine/Type/ClientStatusType.php`
- Utiliser `src/Infrastructure/Persistence/Doctrine/Type/EmailType.php` (créé par US-010)
- Utiliser `src/Infrastructure/Persistence/Doctrine/Type/PersonNameType.php` (créé par US-014)

### [TEST] Créer tests d'intégration pour le mapping (1h)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Mapping;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\PersonName;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ClientMappingTest extends KernelTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @test
     */
    public function it_persists_and_retrieves_client_with_xml_mapping(): void
    {
        // Given
        $clientId = ClientId::generate();
        $client = Client::create(
            $clientId,
            PersonName::fromString('Jean Dupont'),
            Email::fromString('jean.dupont@example.com')
        );

        // When
        $this->entityManager->persist($client);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Then
        $retrieved = $this->entityManager->find(Client::class, $clientId);

        self::assertNotNull($retrieved);
        self::assertEquals($clientId, $retrieved->getId());
        self::assertEquals('Jean Dupont', $retrieved->getName()->getValue());
        self::assertEquals('jean.dupont@example.com', $retrieved->getEmail()->getValue());
    }

    /**
     * @test
     */
    public function it_correctly_maps_value_objects(): void
    {
        // Given
        $client = Client::create(
            ClientId::generate(),
            PersonName::fromString('Marie Martin'),
            Email::fromString('marie@test.com')
        );

        // When
        $this->entityManager->persist($client);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $retrieved = $this->entityManager->find(Client::class, $client->getId());

        // Then
        self::assertInstanceOf(ClientId::class, $retrieved->getId());
        self::assertInstanceOf(PersonName::class, $retrieved->getName());
        self::assertInstanceOf(Email::class, $retrieved->getEmail());
        self::assertInstanceOf(ClientStatus::class, $retrieved->getStatus());
    }

    /**
     * @test
     */
    public function it_enforces_email_uniqueness(): void
    {
        // Given
        $email = Email::fromString('unique@test.com');

        $client1 = Client::create(
            ClientId::generate(),
            PersonName::fromString('Client 1'),
            $email
        );

        $client2 = Client::create(
            ClientId::generate(),
            PersonName::fromString('Client 2'),
            $email
        );

        // When
        $this->entityManager->persist($client1);
        $this->entityManager->flush();

        // Then
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);

        $this->entityManager->persist($client2);
        $this->entityManager->flush();
    }

    /**
     * @test
     */
    public function it_automatically_sets_timestamps(): void
    {
        // Given
        $client = Client::create(
            ClientId::generate(),
            PersonName::fromString('Test Client'),
            Email::fromString('timestamps@test.com')
        );

        // When
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        // Then
        self::assertInstanceOf(\DateTimeImmutable::class, $client->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $client->getUpdatedAt());
    }
}
```

**Actions:**
- Créer `tests/Integration/Infrastructure/Persistence/Doctrine/Mapping/ClientMappingTest.php`
- Tests de persistance complète (save + retrieve)
- Tests d'hydratation Value Objects
- Tests de contraintes (email unique)
- Tests des timestamps automatiques
- Couverture ≥ 90%

### [DOC] Documenter le mapping Doctrine XML (0.5h)

**Créer documentation:**
```markdown
# Documentation Mapping Doctrine XML - Client

## Configuration

Le mapping Doctrine pour l'entité `Client` est défini en XML (pas en annotations) pour respecter la Clean Architecture.

### Localisation
- **Mapping:** `src/Infrastructure/Persistence/Doctrine/Mapping/Client.orm.xml`
- **Entity:** `src/Domain/Client/Entity/Client.php`

### Value Objects mappés

| Value Object | Doctrine Type | Colonne DB |
|--------------|---------------|------------|
| ClientId | client_id (UUID) | id |
| PersonName | person_name (embedded) | name |
| Email | email | email |
| ClientStatus | client_status (enum) | status |

### Indexes

- `idx_client_email` - Pour recherche par email
- `idx_client_status` - Pour filtre par statut
- `idx_client_created_at` - Pour tri chronologique

### Contraintes

- `uniq_client_email` - Email unique (UNIQUE)
```

**Actions:**
- Créer `.claude/examples/doctrine-xml-mapping-client.md`
- Documenter la structure du mapping
- Lister les Value Objects et leurs types
- Documenter les indexes et contraintes

### [VALIDATION] Valider le mapping (0.5h)

```bash
# Valider le schéma Doctrine
make console CMD="doctrine:schema:validate"

# Output attendu:
# [OK] The mapping files are correct.
# [OK] The database schema is in sync with the mapping files.

# Générer la migration
make console CMD="doctrine:migrations:diff"

# Vérifier la migration générée
cat migrations/Version20260113XXXXXX.php

# Exécuter la migration
make db-migrate
```

**Actions:**
- Exécuter `doctrine:schema:validate`
- Vérifier aucune erreur de mapping
- Générer une migration de test
- Examiner le SQL généré
- Valider avec PHPStan niveau max

### [QUALITY] Validation architecture (0.5h)

```bash
# Valider que Domain ne dépend pas de Doctrine
make deptrac

# Output attendu:
# ✅ Domain layer: 0 violations
# ✅ Infrastructure layer: 0 violations

# Valider PHPStan
make phpstan

# Output attendu:
# [OK] No errors
```

**Actions:**
- Exécuter `make deptrac`
- Vérifier aucune violation de couche
- Exécuter `make phpstan`
- Vérifier types corrects sur tous les mappings

---

## Définition de Done (DoD)

- [ ] Fichier `Client.orm.xml` créé dans `src/Infrastructure/Persistence/Doctrine/Mapping/`
- [ ] Mapping XML valide (schéma Doctrine respecté)
- [ ] Tous les champs mappés (id, name, email, status, timestamps)
- [ ] Value Objects mappés avec Doctrine Custom Types
- [ ] Doctrine Custom Types créés (ClientIdType, ClientStatusType)
- [ ] Indexes créés (email, status, created_at)
- [ ] Contrainte unique sur email
- [ ] Configuration Doctrine dans `doctrine.yaml` mise à jour
- [ ] `doctrine:schema:validate` passe sans erreur
- [ ] Migration générée et testée
- [ ] Tests d'intégration passent avec couverture ≥ 90%
- [ ] Deptrac valide: Infrastructure dépend de Domain (pas l'inverse)
- [ ] PHPStan niveau max passe
- [ ] Documentation du mapping créée
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(infrastructure): add Doctrine XML mapping for Client entity`

---

## Notes techniques

### Mapping XML vs Annotations

**Pourquoi XML?**

1. **Séparation des responsabilités**
   - Entité Domain pure (logique métier)
   - Mapping Infrastructure (détails techniques)

2. **Respect Clean Architecture**
   - Domain ne dépend pas de Doctrine
   - Infrastructure dépend de Domain (inversion)

3. **Testabilité**
   - Tests unitaires Domain sans Doctrine
   - Tests rapides (< 100ms)

4. **Flexibilité**
   - Changement ORM facilité
   - Migrations simplifiées

**Avant (Annotations - couplage):**
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]  // ❌ Doctrine dans l'entité
#[ORM\Table(name: 'clients')]
class Client
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
}
```

**Après (XML - découplage):**

**Entité Domain:**
```php
<?php

namespace App\Domain\Client\Entity;

// ✅ Pas d'import Doctrine
final class Client
{
    private ClientId $id;
    // ...
}
```

**Mapping Infrastructure:**
```xml
<!-- src/Infrastructure/Persistence/Doctrine/Mapping/Client.orm.xml -->
<entity name="App\Domain\Client\Entity\Client" table="clients">
    <id name="id" type="client_id"/>
</entity>
```

### Doctrine Custom Types

Les **Custom Types** permettent de convertir automatiquement entre:
- **Value Object PHP** (ClientId, Email, ClientStatus)
- **Type SQL** (VARCHAR, UUID, ENUM)

**Exemple: ClientIdType**

```
PHP Entity          Doctrine Type        Database
----------          -------------        --------
ClientId     <--->  ClientIdType  <--->  UUID (string)

Save:  ClientId::getValue() -> string UUID -> DB
Load:  DB -> string UUID -> ClientId::fromString()
```

**Avantages:**
- ✅ Conversion automatique
- ✅ Type safety en PHP
- ✅ Validation à l'hydratation
- ✅ Pas de code de conversion dans les repositories

### Embedded Value Objects

Le **PersonName** est un **embedded value object**:

```xml
<embedded name="name" class="App\Domain\Shared\ValueObject\PersonName">
    <field name="value" type="string" length="255" column="name"/>
</embedded>
```

**Signifie:**
- Le Value Object `PersonName` est stocké dans la même table `clients`
- Colonne: `name`
- Pas de table séparée
- Hydratation automatique en objet `PersonName`

### Indexes de performance

**Index sur email:**
```xml
<index name="idx_client_email" columns="email"/>
```

**Pourquoi:**
- Recherche fréquente par email (`findByEmail()`)
- Performance requête: O(1) vs O(N)
- Nécessaire pour l'unicité

**Index sur status:**
```xml
<index name="idx_client_status" columns="status"/>
```

**Pourquoi:**
- Filtres par statut fréquents (`findByStatus()`)
- Statistiques par statut

**Index sur created_at:**
```xml
<index name="idx_client_created_at" columns="created_at"/>
```

**Pourquoi:**
- Tri chronologique (`ORDER BY created_at`)
- Rapports par période

### Migration générée

Exemple de migration Doctrine générée automatiquement:

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
        return 'Create clients table with Value Object mapping';
    }

    public function up(Schema $schema): void
    {
        // ✅ Table générée depuis le mapping XML
        $this->addSql('CREATE TABLE clients (
            id UUID NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )');

        // ✅ Indexes
        $this->addSql('CREATE INDEX idx_client_email ON clients (email)');
        $this->addSql('CREATE INDEX idx_client_status ON clients (status)');
        $this->addSql('CREATE INDEX idx_client_created_at ON clients (created_at)');

        // ✅ Contrainte unique
        $this->addSql('CREATE UNIQUE INDEX uniq_client_email ON clients (email)');

        // ✅ Commentaires pour les custom types
        $this->addSql('COMMENT ON COLUMN clients.id IS \'(DC2Type:client_id)\'');
        $this->addSql('COMMENT ON COLUMN clients.status IS \'(DC2Type:client_status)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE clients');
    }
}
```

---

## Dépendances

### Bloquantes

- **US-002**: Extraction Client (nécessite entité Domain pure existante)

### Bloque

- **US-004**: Extraction User (peut commencer en parallèle)
- **US-021**: DoctrineClientRepository (nécessite mapping XML pour implémenter repository)

---

## Références

- `.claude/rules/02-architecture-clean-ddd.md` (lignes 260-322, Doctrine XML mapping)
- `.claude/rules/18-value-objects.md` (lignes 250-295, Doctrine Custom Types)
- [Doctrine XML Mapping Reference](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/xml-mapping.html)
- [Doctrine Custom Types](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 45-73, problème mappings annotations)
- **Livre:** *Implementing Domain-Driven Design* - Vaughn Vernon, Chapitre 12 (Repositories)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |
