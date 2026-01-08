<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogTagRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BlogTag - Global blog tag entity (not multi-tenant).
 *
 * Tags are shared across all companies and managed by SUPERADMIN.
 * Examples: "symfony", "performance", "sécurité", "devops".
 */
#[ORM\Entity(repositoryClass: BlogTagRepository::class)]
#[ORM\Table(name: 'blog_tags')]
#[ORM\Index(name: 'idx_blogtag_slug', columns: ['slug'])]
class BlogTag implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le nom du tag est obligatoire.')]
    #[Assert\Length(max: 50, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    public string $name = '' {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Gedmo\Slug(fields: ['name'], unique: true, updatable: false)]
    public string $slug = '' {
        get => $this->slug;
        set {
            $this->slug = $value;
        }
    }

    /**
     * @var Collection<int, BlogPost>
     */
    #[ORM\ManyToMany(targetEntity: BlogPost::class, mappedBy: 'tags')]
    private Collection $posts;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Gedmo\Timestampable(on: 'create')]
    protected ?DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    protected ?DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(BlogPost $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->addTag($this);
        }

        return $this;
    }

    public function removePost(BlogPost $post): self
    {
        if ($this->posts->removeElement($post)) {
            $post->removeTag($this);
        }

        return $this;
    }

    /**
     * Get count of published posts with this tag.
     */
    public function getPublishedPostCount(): int
    {
        return $this->posts->filter(
            fn (BlogPost $post) => $post->isPublished(),
        )->count();
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
