<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\BlogPostRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BlogPost - Multi-tenant blog post entity.
 *
 * Each blog post belongs to a company and has an author (User).
 * Posts can be in draft, published, or archived status.
 */
#[ORM\Entity(repositoryClass: BlogPostRepository::class)]
#[ORM\Table(name: 'blog_posts')]
#[ORM\Index(name: 'idx_blogpost_company', columns: ['company_id'])]
#[ORM\Index(name: 'idx_blogpost_status', columns: ['status'])]
#[ORM\Index(name: 'idx_blogpost_published_at', columns: ['published_at'])]
#[ORM\Index(name: 'idx_blogpost_author', columns: ['author_id'])]
#[ORM\Index(name: 'idx_blogpost_category', columns: ['category_id'])]
#[ORM\Index(name: 'idx_blogpost_image_source', columns: ['image_source'])]
#[ORM\UniqueConstraint(name: 'uniq_blogpost_company_slug', columns: ['company_id', 'slug'])]
#[ORM\HasLifecycleCallbacks]
class BlogPost implements CompanyOwnedInterface, Stringable
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    public const STATUS_OPTIONS = [
        'Brouillon' => self::STATUS_DRAFT,
        'Publié'    => self::STATUS_PUBLISHED,
        'Archivé'   => self::STATUS_ARCHIVED,
    ];

    // Image source constants
    public const IMAGE_SOURCE_EXTERNAL     = 'external';
    public const IMAGE_SOURCE_UPLOAD       = 'upload';
    public const IMAGE_SOURCE_AI_GENERATED = 'ai_generated';

    public const IMAGE_SOURCE_OPTIONS = [
        'URL externe'   => self::IMAGE_SOURCE_EXTERNAL,
        'Upload manuel' => self::IMAGE_SOURCE_UPLOAD,
        'Généré par IA' => self::IMAGE_SOURCE_AI_GENERATED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La société est obligatoire.')]
    public Company $company {
        get => $this->company;
        set {
            $this->company = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.')]
    public string $title = '' {
        get => $this->title;
        set {
            $this->title = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255)]
    #[Gedmo\Slug(fields: ['title'], unique: false, updatable: false)]
    public string $slug = '' {
        get => $this->slug;
        set {
            $this->slug = $value;
        }
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $content = null {
        get => $this->content;
        set {
            $this->content = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'L\'extrait ne peut pas dépasser {{ limit }} caractères.')]
    public ?string $excerpt = null {
        get => $this->excerpt;
        set {
            $this->excerpt = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Url(message: 'L\'URL de l\'image doit être valide.')]
    public ?string $featuredImage = null {
        get => $this->featuredImage;
        set {
            $this->featuredImage = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'Le prompt ne peut pas dépasser {{ limit }} caractères.')]
    public ?string $imagePrompt = null {
        get => $this->imagePrompt;
        set {
            $this->imagePrompt = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: [self::IMAGE_SOURCE_EXTERNAL, self::IMAGE_SOURCE_UPLOAD, self::IMAGE_SOURCE_AI_GENERATED])]
    public string $imageSource = self::IMAGE_SOURCE_EXTERNAL {
        get => $this->imageSource;
        set {
            $this->imageSource = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $imageGeneratedAt = null {
        get => $this->imageGeneratedAt;
        set {
            $this->imageGeneratedAt = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    public ?string $imageModel = null {
        get => $this->imageModel;
        set {
            $this->imageModel = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED])]
    public string $status = self::STATUS_DRAFT {
        get => $this->status;
        set {
            $this->status = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'auteur est obligatoire.')]
    public User $author {
        get => $this->author;
        set {
            $this->author = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: BlogCategory::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?BlogCategory $category = null {
        get => $this->category;
        set {
            $this->category = $value;
        }
    }

    /**
     * @var Collection<int, BlogTag>
     */
    #[ORM\ManyToMany(targetEntity: BlogTag::class, inversedBy: 'posts')]
    #[ORM\JoinTable(name: 'blog_post_tag')]
    private Collection $tags;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $publishedAt = null {
        get => $this->publishedAt;
        set {
            $this->publishedAt = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    protected ?DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    protected ?DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get excerpt or auto-generate from content.
     */
    public function getExcerpt(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        // Auto-generate excerpt from content
        $text = strip_tags($this->content ?? '');
        $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
        $text = trim($text);

        return mb_substr($text, 0, 200).(mb_strlen($text) > 200 ? '...' : '');
    }

    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?string $featuredImage): self
    {
        $this->featuredImage = $featuredImage;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getCategory(): ?BlogCategory
    {
        return $this->category;
    }

    public function setCategory(?BlogCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, BlogTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(BlogTag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(BlogTag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Check if post is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->publishedAt !== null
            && $this->publishedAt <= new DateTimeImmutable();
    }

    /**
     * Check if post is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if post is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Publish this post (set status and publishedAt).
     */
    public function publish(): self
    {
        $this->status = self::STATUS_PUBLISHED;

        if ($this->publishedAt === null) {
            $this->publishedAt = new DateTimeImmutable();
        }

        return $this;
    }

    /**
     * Archive this post.
     */
    public function archive(): self
    {
        $this->status = self::STATUS_ARCHIVED;

        return $this;
    }

    /**
     * Estimate reading time in minutes based on content.
     */
    public function getReadingTime(): int
    {
        $text      = strip_tags($this->content ?? '');
        $wordCount = str_word_count($text);

        // Average reading speed: 200 words per minute
        return max(1, (int) ceil($wordCount / 200));
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
