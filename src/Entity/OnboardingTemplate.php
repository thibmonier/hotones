<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OnboardingTemplateRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnboardingTemplateRepository::class)]
#[ORM\Table(name: 'onboarding_templates')]
#[ORM\HasLifecycleCallbacks]
class OnboardingTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Profile $profile = null;

    #[ORM\Column(type: Types::JSON)]
    private array $tasks = []; // Array of task definitions

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, OnboardingTask>
     */
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: OnboardingTask::class)]
    private Collection $onboardingTasks;

    public function __construct()
    {
        $this->createdAt       = new DateTimeImmutable();
        $this->updatedAt       = new DateTimeImmutable();
        $this->onboardingTasks = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function setTasks(array $tasks): self
    {
        $this->tasks = $tasks;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, OnboardingTask>
     */
    public function getOnboardingTasks(): Collection
    {
        return $this->onboardingTasks;
    }

    public function addOnboardingTask(OnboardingTask $onboardingTask): self
    {
        if (!$this->onboardingTasks->contains($onboardingTask)) {
            $this->onboardingTasks->add($onboardingTask);
            $onboardingTask->setTemplate($this);
        }

        return $this;
    }

    public function removeOnboardingTask(OnboardingTask $onboardingTask): self
    {
        if ($this->onboardingTasks->removeElement($onboardingTask)) {
            if ($onboardingTask->getTemplate() === $this) {
                $onboardingTask->setTemplate(null);
            }
        }

        return $this;
    }

    /**
     * Get number of tasks in template.
     */
    public function getTaskCount(): int
    {
        return count($this->tasks);
    }
}
