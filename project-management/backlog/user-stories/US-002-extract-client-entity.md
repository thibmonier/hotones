# US-002: Extraire l'entité Client vers Domain pur

**EPIC:** [EPIC-001](../epics/EPIC-001-clean-architecture-restructuring.md) - Restructuration Clean Architecture
**Priorité:** 🔴 CRITIQUE
**Points:** 5
**Sprint:** Sprint 1
**Statut:** ✅ Done

---

## Description

**En tant que** développeur
**Je veux** extraire l'entité Client vers la couche Domain sans annotations Doctrine
**Afin de** découpler la logique métier de l'infrastructure de persistance

---

## Critères d'acceptation

### GIVEN: L'entité Client existe avec annotations Doctrine dans src/Entity/

**WHEN:** J'extrais l'entité vers Domain pur

**THEN:**
- [ ] Nouvelle entité `src/Domain/Client/Entity/Client.php` créée sans annotations Doctrine
- [ ] Propriétés converties en Value Objects (Email, PersonName, ClientId)
- [ ] Classe marquée `final` pour respecter les bonnes pratiques
- [ ] Constructor `private` avec factory method `create()` statique
- [ ] Méthodes métier ajoutées (pas d'entité anémique)
- [ ] Domain Events enregistrés (ClientCreated, etc.)
- [ ] Aucune dépendance à Doctrine\ORM\Mapping
- [ ] Aucune dépendance à Symfony (sauf pour interfaces standard)

### GIVEN: L'entité Domain pure existe

**WHEN:** J'exécute PHPStan niveau max sur src/Domain/

**THEN:**
- [ ] Aucune erreur PHPStan
- [ ] Aucune dépendance détectée vers Doctrine ou Symfony
- [ ] Deptrac valide: Domain ne dépend de rien

### GIVEN: L'entité Domain pure existe

**WHEN:** J'exécute les tests unitaires

**THEN:**
- [ ] Tests unitaires passent sans base de données
- [ ] Tests unitaires s'exécutent en moins de 100ms
- [ ] Couverture code ≥ 90% sur l'entité Client
- [ ] Tests de création, validation et méthodes métier présents

---

## Tâches techniques

### [DOMAIN] Créer l'entité Client pure (2h)

**Avant (couplé à Doctrine):**
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]  // ❌ Doctrine
#[ORM\Table(name: 'clients')]
class Client implements Stringable, CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name { get; set; }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $email { get; set; }

    // ... autres propriétés avec annotations Doctrine
}
```

**Après (Domain pur):**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Client\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\ClientStatus;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\PersonName;
use App\Domain\Shared\Interface\AggregateRootInterface;

// ✅ Entité Domain pure (pas d'annotations Doctrine)
final class Client implements AggregateRootInterface
{
    private ClientId $id;
    private PersonName $name;
    private ?Email $email;
    private ClientStatus $status;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    /** @var list<DomainEventInterface> */
    private array $domainEvents = [];

    private function __construct(
        ClientId $id,
        PersonName $name,
        ?Email $email = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->status = ClientStatus::PROSPECT;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        ClientId $id,
        PersonName $name,
        ?Email $email = null
    ): self {
        $client = new self($id, $name, $email);

        // ✅ Domain Event
        $client->recordEvent(new ClientCreatedEvent($id, $email));

        return $client;
    }

    // ✅ Logique métier (pas anémique)
    public function updateEmail(Email $email): void
    {
        $oldEmail = $this->email;
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new ClientEmailUpdatedEvent($this->id, $oldEmail, $email));
    }

    public function activate(): void
    {
        if ($this->status === ClientStatus::ACTIVE) {
            return;
        }

        $this->status = ClientStatus::ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new ClientActivatedEvent($this->id));
    }

    public function block(string $reason): void
    {
        if ($this->status === ClientStatus::BLOCKED) {
            return;
        }

        $this->status = ClientStatus::BLOCKED;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new ClientBlockedEvent($this->id, $reason));
    }

    public function isActive(): bool
    {
        return $this->status === ClientStatus::ACTIVE;
    }

    public function isBlocked(): bool
    {
        return $this->status === ClientStatus::BLOCKED;
    }

    // Domain Events management
    private function recordEvent(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
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

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getStatus(): ClientStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
```

**Actions:**
- Créer `src/Domain/Client/Entity/Client.php`
- Supprimer toutes les annotations Doctrine
- Convertir propriétés en Value Objects (Email, PersonName, ClientId)
- Marquer la classe `final`
- Constructor `private` avec factory `create()`
- Ajouter méthodes métier (activate, block, updateEmail)
- Implémenter enregistrement Domain Events

### [DOMAIN] Créer les Value Objects nécessaires (1.5h)
- Créer `src/Domain/Client/ValueObject/ClientId.php` (UUID typé)
- Créer `src/Domain/Client/ValueObject/ClientStatus.php` (enum)
- Utiliser `src/Domain/Shared/ValueObject/Email.php` (déjà créé par US-010)
- Utiliser `src/Domain/Shared/ValueObject/PersonName.php` (déjà créé par US-014)

### [DOMAIN] Créer les Domain Events (1h)
- Créer `src/Domain/Client/Event/ClientCreatedEvent.php`
- Créer `src/Domain/Client/Event/ClientEmailUpdatedEvent.php`
- Créer `src/Domain/Client/Event/ClientActivatedEvent.php`
- Créer `src/Domain/Client/Event/ClientBlockedEvent.php`
- Tous les événements implémentent `DomainEventInterface`

### [DOMAIN] Créer les Exceptions métier (0.5h)
- Créer `src/Domain/Client/Exception/ClientNotFoundException.php`
- Créer `src/Domain/Client/Exception/InvalidClientException.php`
- Toutes les exceptions étendent `DomainException`

### [TEST] Créer tests unitaires Domain (2h)
- Créer `tests/Unit/Domain/Client/Entity/ClientTest.php`
- Tests de création (factory method `create()`)
- Tests de validation (email invalide, etc.)
- Tests de méthodes métier (activate, block, updateEmail)
- Tests de Domain Events (enregistrement et pull)
- Couverture ≥ 90%

### [DOC] Documenter l'entité Domain (0.5h)
- Ajouter PHPDoc complet sur l'entité Client
- Documenter les règles métier (statuts, transitions)
- Créer exemple d'usage dans `.claude/examples/domain-entity-client.md`

### [VALIDATION] Valider avec outils qualité (0.5h)
- Exécuter `make phpstan` sur src/Domain/
- Exécuter `make deptrac` pour vérifier isolation Domain
- Vérifier aucune dépendance vers Doctrine/Symfony

---

## Définition de Done (DoD)

- [ ] Entité `src/Domain/Client/Entity/Client.php` créée sans annotations Doctrine
- [ ] Propriétés converties en Value Objects (Email, PersonName, ClientId, ClientStatus)
- [ ] Classe `final` avec constructor `private` et factory `create()`
- [ ] Méthodes métier implémentées (activate, block, updateEmail)
- [ ] Domain Events enregistrés et testés
- [ ] Value Objects ClientId et ClientStatus créés
- [ ] Exceptions métier créées (ClientNotFoundException, InvalidClientException)
- [ ] Tests unitaires passent avec couverture ≥ 90%
- [ ] Tests s'exécutent en moins de 100ms
- [ ] PHPStan niveau max passe sur src/Domain/
- [ ] Deptrac valide: Domain ne dépend de rien
- [ ] Documentation PHPDoc complète
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(domain): extract Client entity to pure Domain layer`

---

## Notes techniques

### Pattern Aggregate Root

L'entité Client est un **Aggregate Root** car:
- Elle a une identité unique (ClientId)
- Elle garantit ses invariants (statuts valides, email unique)
- Elle enregistre des Domain Events
- Elle est le point d'entrée pour toute modification

### Statuts Client

```php
<?php

namespace App\Domain\Client\ValueObject;

enum ClientStatus: string
{
    case PROSPECT = 'prospect';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';

    public function canActivate(): bool
    {
        return $this !== self::BLOCKED;
    }

    public function canBlock(): bool
    {
        return $this !== self::BLOCKED;
    }
}
```

### Transitions de statuts autorisées

```
PROSPECT → ACTIVE     ✅ (activation)
PROSPECT → BLOCKED    ✅ (blocage immédiat)
ACTIVE → INACTIVE     ✅ (désactivation)
ACTIVE → BLOCKED      ✅ (blocage)
INACTIVE → ACTIVE     ✅ (réactivation)
INACTIVE → BLOCKED    ✅ (blocage)
BLOCKED → ACTIVE      ❌ (interdit, nécessite déblocage manuel admin)
```

### Règles métier

1. **Email unique**: Validé au niveau Repository (US-020)
2. **Statut cohérent**: Transitions contrôlées via méthodes métier
3. **Immutabilité partielle**: ID et createdAt jamais modifiés
4. **Domain Events**: Toute mutation importante émet un événement

### Exemple d'usage

```php
<?php

// Création
$client = Client::create(
    ClientId::generate(),
    PersonName::fromString('Jean Dupont'),
    Email::fromString('jean.dupont@example.com')
);

// Modification
$client->updateEmail(Email::fromString('nouveau@example.com'));
$client->activate();

// Domain Events
$events = $client->pullDomainEvents();
// $events = [ClientCreatedEvent, ClientEmailUpdatedEvent, ClientActivatedEvent]
```

---

## Dépendances

### Bloquantes
- **US-001**: Structure Domain créée (nécessite `src/Domain/Client/Entity/`)

### Bloque
- **US-003**: Mapping Doctrine XML (nécessite entité Domain pure comme source)
- **US-020**: ClientRepositoryInterface (nécessite entité Client définie)

---

## Références

- `.claude/rules/02-architecture-clean-ddd.md` (lignes 45-155, entités Domain pures)
- `.claude/rules/13-ddd-patterns.md` (lignes 15-90, Aggregates et Entities)
- `.claude/rules/19-aggregates.md` (Template Aggregate Root)
- `.claude/rules/20-domain-events.md` (Domain Events pattern)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 45-73, problème entités couplées)
- **Livre:** *Domain-Driven Design* - Eric Evans, Chapitre 5 (Entities)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |
