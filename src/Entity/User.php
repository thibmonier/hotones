<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
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
class User implements UserInterface, PasswordAuthenticatedUserInterface, TotpTwoFactorInterface
{
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
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:write'])]
    private string $email;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    #[Groups(['user:write'])]
    private string $password;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['user:read', 'user:write'])]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['user:read', 'user:write'])]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $phone = null; // legacy, conservé pour compat

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $phoneWork = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $phonePersonal = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null; // adresse personnelle

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar = null; // path/URL

    // 2FA TOTP
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(type: 'boolean')]
    private bool $totpEnabled = false;

    // Timestamps
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    // Login tracking
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $lastLoginIp = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
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

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhoneWork(): ?string
    {
        return $this->phoneWork;
    }

    public function setPhoneWork(?string $phoneWork): self
    {
        $this->phoneWork = $phoneWork;

        return $this;
    }

    public function getPhonePersonal(): ?string
    {
        return $this->phonePersonal;
    }

    public function setPhonePersonal(?string $phonePersonal): self
    {
        $this->phonePersonal = $phonePersonal;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
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

    public function setTotpSecret(?string $secret): self
    {
        $this->totpSecret = $secret;

        return $this;
    }

    public function setTotpEnabled(bool $enabled): self
    {
        $this->totpEnabled = $enabled;

        return $this;
    }

    // Timestamps getters/setters
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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
        return in_array($role, $this->getRoles());
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

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getLastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }

    public function setLastLoginIp(?string $lastLoginIp): self
    {
        $this->lastLoginIp = $lastLoginIp;

        return $this;
    }
}
