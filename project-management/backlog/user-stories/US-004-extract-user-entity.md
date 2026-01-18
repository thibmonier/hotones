# US-004: Extraire l'entité User vers Domain pur

**EPIC:** [EPIC-001](../epics/EPIC-001-clean-architecture-restructuring.md) - Restructuration Clean Architecture
**Priorité:** 🔴 CRITIQUE
**Points:** 5
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** extraire l'entité User vers la couche Domain sans annotations Doctrine
**Afin de** découpler la logique d'authentification et de gestion des utilisateurs de l'infrastructure de persistance

---

## Critères d'acceptation

### GIVEN: L'entité User existe avec annotations Doctrine dans src/Entity/

**WHEN:** J'extrais l'entité vers Domain pur

**THEN:**
- [ ] Nouvelle entité `src/Domain/User/Entity/User.php` créée sans annotations Doctrine
- [ ] Propriétés converties en Value Objects (Email, PersonName, UserId, UserRole)
- [ ] Classe marquée `final` pour respecter les bonnes pratiques
- [ ] Constructor `private` avec factory method `create()` statique
- [ ] Méthodes métier ajoutées pour authentification (updatePassword, changeRole, activate, deactivate)
- [ ] Domain Events enregistrés (UserCreated, UserRoleChanged, UserActivated, etc.)
- [ ] Aucune dépendance à Doctrine\ORM\Mapping
- [ ] Aucune dépendance à Symfony Security (sauf interfaces standard)
- [ ] Implémentation de UserInterface pour Symfony Security

### GIVEN: L'entité Domain pure existe

**WHEN:** J'exécute PHPStan niveau max sur src/Domain/

**THEN:**
- [ ] Aucune erreur PHPStan
- [ ] Aucune dépendance détectée vers Doctrine ou composants Symfony non autorisés
- [ ] Deptrac valide: Domain ne dépend de rien d'externe

### GIVEN: L'entité Domain pure existe

**WHEN:** J'exécute les tests unitaires

**THEN:**
- [ ] Tests unitaires passent sans base de données
- [ ] Tests unitaires s'exécutent en moins de 100ms
- [ ] Couverture code ≥ 90% sur l'entité User
- [ ] Tests de création, validation, authentification et méthodes métier présents

---

## Tâches techniques

### [DOMAIN] Créer l'entité User pure (2.5h)

**Avant (couplé à Doctrine):**
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]  // ❌ Doctrine
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    public string $email { get; set; }

    #[ORM\Column(type: 'json')]
    public array $roles { get; set; } = [];

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'boolean')]
    public bool $isActive { get; set; } = true;

    // ... autres propriétés avec annotations Doctrine

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // Clear temporary data
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
```

**Après (Domain pur):**
```php
<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserRole;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\PersonName;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Event\UserRoleChangedEvent;
use App\Domain\User\Event\UserActivatedEvent;
use App\Domain\User\Event\UserDeactivatedEvent;
use App\Domain\User\Event\UserPasswordChangedEvent;
use App\Domain\User\Exception\UserAlreadyActiveException;
use App\Domain\User\Exception\UserAlreadyInactiveException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// ✅ Entité Domain pure (pas d'annotations Doctrine ici)
// ✅ Implémente UserInterface pour Symfony Security (interface standard autorisée)
final class User implements AggregateRootInterface, UserInterface, PasswordAuthenticatedUserInterface
{
    private UserId $id;
    private PersonName $name;
    private Email $email;
    private HashedPassword $password;
    private UserRole $role;
    private bool $isActive;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    /** @var list<DomainEventInterface> */
    private array $domainEvents = [];

    private function __construct(
        UserId $id,
        PersonName $name,
        Email $email,
        HashedPassword $password,
        UserRole $role = UserRole::USER
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->isActive = true;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        UserId $id,
        PersonName $name,
        Email $email,
        HashedPassword $password,
        UserRole $role = UserRole::USER
    ): self {
        $user = new self($id, $name, $email, $password, $role);

        // ✅ Domain Event
        $user->recordEvent(new UserCreatedEvent($id, $email, $role));

        return $user;
    }

    // ✅ Logique métier: Gestion des rôles
    public function changeRole(UserRole $newRole): void
    {
        if ($this->role === $newRole) {
            return;
        }

        $oldRole = $this->role;
        $this->role = $newRole;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserRoleChangedEvent($this->id, $oldRole, $newRole));
    }

    // ✅ Logique métier: Gestion du mot de passe
    public function updatePassword(HashedPassword $newPassword): void
    {
        $this->password = $newPassword;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserPasswordChangedEvent($this->id));
    }

    // ✅ Logique métier: Activation/Désactivation
    public function activate(): void
    {
        if ($this->isActive) {
            throw new UserAlreadyActiveException($this->id);
        }

        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserActivatedEvent($this->id));
    }

    public function deactivate(): void
    {
        if (!$this->isActive) {
            throw new UserAlreadyInactiveException($this->id);
        }

        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserDeactivatedEvent($this->id));
    }

    // ✅ Règles métier
    public function canLogin(): bool
    {
        return $this->isActive;
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role || $this->role->includes($role);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    // ✅ UserInterface implementation (Symfony Security)
    public function getUserIdentifier(): string
    {
        return $this->email->getValue();
    }

    public function getRoles(): array
    {
        // Convert UserRole VO to Symfony roles array
        return $this->role->toSymfonyRoles();
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase (no plain password stored)
    }

    // ✅ PasswordAuthenticatedUserInterface implementation
    public function getPassword(): string
    {
        return $this->password->getHash();
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
    public function getId(): UserId
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

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
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
- Créer `src/Domain/User/Entity/User.php`
- Supprimer toutes les annotations Doctrine
- Convertir propriétés en Value Objects (Email, PersonName, UserId, UserRole, HashedPassword)
- Marquer la classe `final`
- Constructor `private` avec factory `create()`
- Implémenter UserInterface et PasswordAuthenticatedUserInterface (Symfony Security)
- Ajouter méthodes métier (updatePassword, changeRole, activate, deactivate, canLogin)
- Implémenter enregistrement Domain Events

### [DOMAIN] Créer les Value Objects nécessaires (2h)
- Créer `src/Domain/User/ValueObject/UserId.php` (UUID typé)
- Créer `src/Domain/User/ValueObject/UserRole.php` (enum avec hiérarchie)
- Créer `src/Domain/User/ValueObject/HashedPassword.php` (encapsulation hash)
- Utiliser `src/Domain/Shared/ValueObject/Email.php` (déjà créé par US-010)
- Utiliser `src/Domain/Shared/ValueObject/PersonName.php` (déjà créé par US-014)

### [DOMAIN] Créer les Domain Events (1h)
- Créer `src/Domain/User/Event/UserCreatedEvent.php`
- Créer `src/Domain/User/Event/UserRoleChangedEvent.php`
- Créer `src/Domain/User/Event/UserActivatedEvent.php`
- Créer `src/Domain/User/Event/UserDeactivatedEvent.php`
- Créer `src/Domain/User/Event/UserPasswordChangedEvent.php`
- Tous les événements implémentent `DomainEventInterface`

### [DOMAIN] Créer les Exceptions métier (0.5h)
- Créer `src/Domain/User/Exception/UserNotFoundException.php`
- Créer `src/Domain/User/Exception/InvalidUserException.php`
- Créer `src/Domain/User/Exception/UserAlreadyActiveException.php`
- Créer `src/Domain/User/Exception/UserAlreadyInactiveException.php`
- Créer `src/Domain/User/Exception/InvalidCredentialsException.php`
- Toutes les exceptions étendent `DomainException`

### [TEST] Créer tests unitaires Domain (2h)
- Créer `tests/Unit/Domain/User/Entity/UserTest.php`
- Tests de création (factory method `create()`)
- Tests de validation (email invalide, etc.)
- Tests de méthodes métier (updatePassword, changeRole, activate, deactivate)
- Tests de règles d'authentification (canLogin, hasRole, isAdmin)
- Tests de Domain Events (enregistrement et pull)
- Couverture ≥ 90%

### [DOC] Documenter l'entité Domain (0.5h)
- Ajouter PHPDoc complet sur l'entité User
- Documenter les règles métier (rôles, statuts, authentification)
- Créer exemple d'usage dans `.claude/examples/domain-entity-user.md`

### [VALIDATION] Valider avec outils qualité (0.5h)
- Exécuter `make phpstan` sur src/Domain/
- Exécuter `make deptrac` pour vérifier isolation Domain
- Vérifier aucune dépendance vers Doctrine/Symfony (sauf UserInterface)

---

## Définition de Done (DoD)

- [ ] Entité `src/Domain/User/Entity/User.php` créée sans annotations Doctrine
- [ ] Propriétés converties en Value Objects (Email, PersonName, UserId, UserRole, HashedPassword)
- [ ] Classe `final` avec constructor `private` et factory `create()`
- [ ] Implémentation UserInterface et PasswordAuthenticatedUserInterface
- [ ] Méthodes métier implémentées (updatePassword, changeRole, activate, deactivate, canLogin)
- [ ] Domain Events enregistrés et testés
- [ ] Value Objects UserId, UserRole et HashedPassword créés
- [ ] Exceptions métier créées (UserNotFoundException, InvalidUserException, etc.)
- [ ] Tests unitaires passent avec couverture ≥ 90%
- [ ] Tests s'exécutent en moins de 100ms
- [ ] PHPStan niveau max passe sur src/Domain/
- [ ] Deptrac valide: Domain ne dépend de rien (sauf interfaces Symfony Security)
- [ ] Documentation PHPDoc complète
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `feat(domain): extract User entity to pure Domain layer`

---

## Notes techniques

### Pattern Aggregate Root

L'entité User est un **Aggregate Root** car:
- Elle a une identité unique (UserId)
- Elle garantit ses invariants (rôles valides, email unique, authentification cohérente)
- Elle enregistre des Domain Events
- Elle est le point d'entrée pour toute modification d'utilisateur

### UserRole avec hiérarchie

```php
<?php

namespace App\Domain\User\ValueObject;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case MANAGER = 'ROLE_MANAGER';
    case ADMIN = 'ROLE_ADMIN';
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * Check if this role includes another role.
     * Example: ADMIN includes MANAGER and USER.
     */
    public function includes(self $role): bool
    {
        return match ($this) {
            self::SUPER_ADMIN => true, // Includes all roles
            self::ADMIN => in_array($role, [self::ADMIN, self::MANAGER, self::USER], true),
            self::MANAGER => in_array($role, [self::MANAGER, self::USER], true),
            self::USER => $role === self::USER,
        };
    }

    /**
     * Convert to Symfony Security roles array.
     *
     * @return list<string>
     */
    public function toSymfonyRoles(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_USER'],
            self::ADMIN => ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_USER'],
            self::MANAGER => ['ROLE_MANAGER', 'ROLE_USER'],
            self::USER => ['ROLE_USER'],
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN || $this === self::SUPER_ADMIN;
    }

    public function canManage(): bool
    {
        return $this->includes(self::MANAGER);
    }
}
```

### HashedPassword Value Object

```php
<?php

namespace App\Domain\User\ValueObject;

/**
 * Represents a hashed password.
 *
 * NEVER stores plain text passwords.
 * Hash is generated by infrastructure layer (PasswordHasher).
 */
final readonly class HashedPassword
{
    private function __construct(
        private string $hash
    ) {
        if (empty($hash)) {
            throw new \InvalidArgumentException('Password hash cannot be empty');
        }

        // Basic validation: bcrypt hashes start with $2y$ and are 60 chars
        if (!str_starts_with($hash, '$2y$') && strlen($hash) !== 60) {
            // Allow other formats (argon2, etc.) but ensure non-empty
        }
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function equals(self $other): bool
    {
        return $this->hash === $other->hash;
    }
}
```

### Règles métier d'authentification

1. **Email unique**: Validé au niveau Repository (US-023)
2. **Statut actif requis pour login**: `canLogin()` vérifie `isActive`
3. **Hiérarchie des rôles**: SUPER_ADMIN > ADMIN > MANAGER > USER
4. **Immutabilité partielle**: ID et createdAt jamais modifiés
5. **Domain Events**: Toute mutation importante émet un événement
6. **Password hashing**: Géré par l'infrastructure (pas le Domain)

### Exemple d'usage

```php
<?php

// Création d'un utilisateur
$user = User::create(
    UserId::generate(),
    PersonName::fromString('Jean Dupont'),
    Email::fromString('jean.dupont@example.com'),
    HashedPassword::fromHash('$2y$13$...'), // Hash généré par PasswordHasher
    UserRole::USER
);

// Modification du rôle
$user->changeRole(UserRole::ADMIN);

// Vérification des permissions
if ($user->hasRole(UserRole::MANAGER)) {
    // Can manage resources
}

if ($user->canLogin()) {
    // User can authenticate
}

// Désactivation
$user->deactivate();

// Domain Events
$events = $user->pullDomainEvents();
// $events = [UserCreatedEvent, UserRoleChangedEvent, UserDeactivatedEvent]
```

### Intégration avec Symfony Security

```php
<?php

// L'entité User implémente UserInterface et PasswordAuthenticatedUserInterface
// pour compatibilité avec Symfony Security sans couplage fort

// Dans un Security Voter ou Guard
public function supports(Request $request): ?bool
{
    $user = $this->getUser();

    if (!$user instanceof User) {
        return false;
    }

    return $user->canLogin(); // ✅ Règle métier Domain
}

// Dans un Controller
$this->denyAccessUnlessGranted('ROLE_ADMIN');
// Symfony vérifie via $user->getRoles() qui utilise UserRole VO
```

### Transitions de rôles autorisées

```
USER → MANAGER      ✅ (promotion)
USER → ADMIN        ✅ (promotion directe)
MANAGER → ADMIN     ✅ (promotion)
ADMIN → MANAGER     ✅ (rétrogradation)
ADMIN → USER        ✅ (rétrogradation)
SUPER_ADMIN → *     ✅ (tout autorisé)
USER → SUPER_ADMIN  ⚠️ (rare, nécessite validation)
```

### Règles de sécurité

1. **Password hashing**: TOUJOURS via PasswordHasher (infrastructure)
2. **Email unique**: Vérifié au niveau Repository avant création
3. **Roles hierarchy**: Gérée par UserRole Value Object
4. **Active status**: Requis pour authentification
5. **Password change**: Émet un événement pour invalidation sessions

---

## Dépendances

### Bloquantes
- **US-001**: Structure Domain créée (nécessite `src/Domain/User/Entity/`)

### Bloque
- **US-005**: Mapping Doctrine XML pour User (nécessite entité Domain pure comme source)
- **US-023**: UserRepositoryInterface (nécessite entité User définie)

---

## Références

- `.claude/rules/02-architecture-clean-ddd.md` (lignes 45-155, entités Domain pures)
- `.claude/rules/13-ddd-patterns.md` (lignes 15-90, Aggregates et Entities)
- `.claude/rules/19-aggregates.md` (Template Aggregate Root)
- `.claude/rules/20-domain-events.md` (Domain Events pattern)
- `.claude/rules/11-security-symfony.md` (lignes 56-145, authentification sécurisée)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 45-73, problème entités couplées)
- **Livre:** *Domain-Driven Design* - Eric Evans, Chapitre 5 (Entities)
- **Symfony Security:** [UserInterface Documentation](https://symfony.com/doc/current/security.html)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |
