<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Interface\CompanyOwnedInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\Blameable;
use Gedmo\Mapping\Annotation as Gedmo;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use SensitiveParameter;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'idx_user_company', columns: ['company_id'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_MANAGER') or object == user"),
        new GetCollection(security: "is_granted('ROLE_MANAGER')"),
        new Post(security: "is_granted('ROLE_MANAGER')"),
        new Put(security: "is_granted('ROLE_MANAGER') or object == user"),
        new Patch(security: "is_granted('ROLE_MANAGER') or object == user"),
        new Delete(security: "is_granted('ROLE_SUPERADMIN')"),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    paginationItemsPerPage: 30,
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TotpTwoFactorInterface, CompanyOwnedInterface
{
    use Blameable;

    // Rôles métier
    public const string ROLE_INTERVENANT = 'ROLE_INTERVENANT';
    public const string ROLE_CHEF_PROJET = 'ROLE_CHEF_PROJET';
    public const string ROLE_MANAGER     = 'ROLE_MANAGER';
    public const string ROLE_SUPERADMIN  = 'ROLE_SUPERADMIN';

    final public const array ROLE_HIERARCHY = [
        self::ROLE_INTERVENANT => ['ROLE_USER'],
        self::ROLE_CHEF_PROJET => [self::ROLE_INTERVENANT],
        self::ROLE_MANAGER     => [self::ROLE_CHEF_PROJET],
        self::ROLE_SUPERADMIN  => [self::ROLE_MANAGER],
    ];
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user:read'])]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:write'])]
    public string $email {
        get => $this->email;
        set {
            $this->email = $value;
        }
    }

    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    #[Groups(['user:write'])]
    #[Ignore]
    private string $password;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['user:read', 'user:write'])]
    public string $firstName {
        get => $this->firstName;
        set {
            $this->firstName = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['user:read', 'user:write'])]
    public string $lastName {
        get => $this->lastName;
        set {
            $this->lastName = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    public ?string $phone = null {
        get => $this->phone;
        set {
            $this->phone = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    public ?string $phoneWork = null {
        get => $this->phoneWork;
        set {
            $this->phoneWork = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    public ?string $phonePersonal = null {
        get => $this->phonePersonal;
        set {
            $this->phonePersonal = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $address = null {
        get => $this->address;
        set {
            $this->address = $value;
        }
    }

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    protected ?DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    protected ?DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $avatar = null {
        get => $this->avatar;
        set {
            $this->avatar = $value;
        }
    }

    // 2FA TOTP
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Ignore]
    private ?string $totpSecret = null;

    #[ORM\Column(type: 'boolean')]
    private bool $totpEnabled = false;

    // Login tracking
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $lastLoginAt = null {
        get => $this->lastLoginAt;
        set {
            $this->lastLoginAt = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    public ?string $lastLoginIp = null {
        get => $this->lastLoginIp;
        set {
            $this->lastLoginIp = $value;
        }
    }

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(#[SensitiveParameter] string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    // 2FA implementation
    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpEnabled && !empty($this->totpSecret);
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(#[SensitiveParameter] ?string $secret): self
    {
        $this->totpSecret = $secret;

        return $this;
    }

    public function setTotpEnabled(bool $enabled): self
    {
        $this->totpEnabled = $enabled;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    // Scheb 2FA v7 methods
    public function getTotpAuthenticationUsername(): string
    {
        return $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        if (!$this->isTotpAuthenticationEnabled()) {
            return null;
        }

        return new TotpConfiguration($this->totpSecret ?? '', 'sha1', 30, 6);
    }

    // Méthodes utilitaires pour les rôles
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function isIntervenant(): bool
    {
        return $this->hasRole(self::ROLE_INTERVENANT);
    }

    public function isChefProjet(): bool
    {
        return $this->hasRole(self::ROLE_CHEF_PROJET);
    }

    public function isManager(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPERADMIN);
    }

    public function getFullName(): string
    {
        return $this->firstName.' '.$this->lastName;
    }

    public function isTotpEnabled(): ?bool
    {
        return $this->totpEnabled;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    // ========================================
    // Compatibility methods for existing code
    // ========================================

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 public private(set), prefer direct access: $user->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->email.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->email = $value.
     */
    public function setEmail(string $value): self
    {
        $this->email = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->firstName.
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->lastName.
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->lastName = $value.
     */
    public function setLastName(string $value): self
    {
        $this->lastName = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->address = $value.
     */
    public function setAddress(?string $value): self
    {
        $this->address = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->avatar.
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->avatar = $value.
     */
    public function setAvatar(?string $value): self
    {
        $this->avatar = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->createdAt.
     */
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->createdAt = $value.
     */
    public function setCreatedAt(DateTimeImmutable $value): self
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->updatedAt.
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->updatedAt = $value.
     */
    public function setUpdatedAt(?DateTimeImmutable $value): self
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->lastLoginAt.
     */
    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->lastLoginAt = $value.
     */
    public function setLastLoginAt(?DateTimeImmutable $value): self
    {
        $this->lastLoginAt = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->lastLoginIp.
     */
    public function getLastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $user->lastLoginIp = $value.
     */
    public function setLastLoginIp(?string $value): self
    {
        $this->lastLoginIp = $value;

        return $this;
    }
}
