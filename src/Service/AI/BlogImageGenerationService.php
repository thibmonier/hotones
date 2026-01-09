<?php

declare(strict_types=1);

namespace App\Service\AI;

use App\Entity\BlogPost;
use App\Entity\Company;
use App\Exception\BlogImageGenerationException;
use App\Exception\InvalidPromptException;
use App\Exception\OpenAIApiException;
use App\Exception\RateLimitExceededException;
use App\Service\SecureFileUploadService;
use DateTimeImmutable;
use Exception;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for generating blog featured images using OpenAI DALL-E 3.
 */
class BlogImageGenerationService
{
    private const RATE_LIMIT_PER_HOUR = 5;
    private const MIN_PROMPT_LENGTH   = 10;
    private const MAX_PROMPT_LENGTH   = 1000;
    private const MAX_IMAGE_SIZE      = 5 * 1024 * 1024; // 5 MB

    private const PROHIBITED_KEYWORDS = [
        'nude',
        'nsfw',
        'explicit',
        'gore',
        'violence',
        'violent',
        'sexual',
        'porn',
    ];

    public function __construct(
        private readonly OpenAIClientFactory $clientFactory,
        private readonly SecureFileUploadService $uploadService,
        #[Autowire(service: 'oneup_flysystem.default_filesystem')]
        private readonly FilesystemOperator $filesystem,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment = 'dev'
    ) {
    }

    /**
     * Generate image from prompt using DALL-E 3.
     *
     * @throws BlogImageGenerationException
     */
    public function generateImage(string $prompt, BlogPost $blogPost): string
    {
        // Sanitize and validate prompt
        $sanitizedPrompt = $this->sanitizePrompt($prompt);

        // Check rate limits
        $this->checkRateLimit($blogPost->company);

        try {
            // Call DALL-E 3 API
            $client   = $this->clientFactory->createClient();
            $response = $client->images()->create([
                'model'           => 'dall-e-3',
                'prompt'          => $sanitizedPrompt,
                'n'               => 1,
                'size'            => '1024x1024',
                'quality'         => 'standard',
                'response_format' => 'url',
            ]);

            $imageUrl = $response->data[0]->url ?? null;

            if ($imageUrl === null) {
                throw new OpenAIApiException('No image URL returned from DALL-E 3');
            }

            // Download and store image
            $filename  = $this->generateFilename($blogPost->slug);
            $imagePath = $this->downloadAndStoreImage($imageUrl, $filename);

            // Update blog post metadata
            $blogPost->featuredImage    = $this->uploadService->getBlogImagePublicUrl($filename);
            $blogPost->imagePrompt      = $prompt;
            $blogPost->imageSource      = BlogPost::IMAGE_SOURCE_AI_GENERATED;
            $blogPost->imageGeneratedAt = new DateTimeImmutable();
            $blogPost->imageModel       = 'dall-e-3';

            // Log success
            $this->logger->info('AI image generated successfully', [
                'company_id'     => $blogPost->company->getId(),
                'blog_post_id'   => $blogPost->id,
                'prompt'         => $prompt,
                'model'          => 'dall-e-3',
                'size'           => '1024x1024',
                'cost_usd'       => 0.040,
                'filename'       => $filename,
                'revised_prompt' => $response->data[0]->revisedPrompt ?? null,
            ]);

            return $imagePath;
        } catch (Exception $e) {
            $this->logger->error('AI image generation failed', [
                'exception'    => $e->getMessage(),
                'prompt'       => $prompt,
                'company_id'   => $blogPost->company->getId(),
                'blog_post_id' => $blogPost->id,
            ]);

            throw new OpenAIApiException(sprintf('Failed to generate image: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * Regenerate image using stored prompt.
     *
     * @throws BlogImageGenerationException
     */
    public function regenerateImage(BlogPost $blogPost): string
    {
        if ($blogPost->imagePrompt === null) {
            throw new InvalidPromptException('Cannot regenerate: no prompt stored');
        }

        if ($blogPost->imageSource !== BlogPost::IMAGE_SOURCE_AI_GENERATED) {
            throw new BlogImageGenerationException('Cannot regenerate: image was not AI-generated');
        }

        // Delete old AI-generated image
        if ($blogPost->featuredImage !== null) {
            $oldFilename = basename(parse_url($blogPost->featuredImage, PHP_URL_PATH));
            $this->uploadService->deleteBlogImage($oldFilename);

            $this->logger->info('Deleted old AI-generated image', [
                'filename'     => $oldFilename,
                'blog_post_id' => $blogPost->id,
            ]);
        }

        // Generate new image with same prompt
        return $this->generateImage($blogPost->imagePrompt, $blogPost);
    }

    /**
     * Download image from DALL-E URL and store in Flysystem.
     */
    private function downloadAndStoreImage(string $dalleUrl, string $filename): string
    {
        try {
            // Download image from temporary DALL-E URL
            $response = $this->httpClient->request('GET', $dalleUrl, [
                'timeout' => 60,
            ]);

            $imageContent = $response->getContent();

            // Validate file size
            $fileSize = strlen($imageContent);
            if ($fileSize > self::MAX_IMAGE_SIZE) {
                throw new BlogImageGenerationException(sprintf('Downloaded image exceeds size limit (%d MB)', self::MAX_IMAGE_SIZE / 1024 / 1024));
            }

            // Save to temporary file for MIME validation
            $tempPath = sys_get_temp_dir().'/'.uniqid('dalle_', true).'.png';
            file_put_contents($tempPath, $imageContent);

            // Validate MIME type
            $this->validateDownloadedImage($tempPath);

            // Store via Flysystem
            $storagePath = 'blog-images/'.$filename;
            $stream      = fopen($tempPath, 'r');

            if ($stream === false) {
                throw new BlogImageGenerationException('Failed to open temporary file for upload');
            }

            $this->filesystem->writeStream($storagePath, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            // Clean up temp file
            unlink($tempPath);

            $this->logger->info('Image downloaded and stored', [
                'filename'  => $filename,
                'size'      => $fileSize,
                'dalle_url' => $dalleUrl,
            ]);

            return $storagePath;
        } catch (Exception $e) {
            // Clean up temp file if it exists
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            throw new BlogImageGenerationException(sprintf('Failed to download and store image: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * Validate downloaded image MIME type and size.
     *
     * @throws BlogImageGenerationException
     */
    private function validateDownloadedImage(string $tempPath): void
    {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tempPath);
        finfo_close($finfo);

        if (!in_array($mimeType, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            unlink($tempPath);
            throw new BlogImageGenerationException(sprintf('Downloaded file is not a valid image (MIME: %s)', $mimeType));
        }

        $fileSize = filesize($tempPath);
        if ($fileSize > self::MAX_IMAGE_SIZE) {
            unlink($tempPath);
            throw new BlogImageGenerationException('Downloaded image exceeds size limit');
        }
    }

    /**
     * Sanitize and validate prompt for safety.
     *
     * @throws InvalidPromptException
     */
    private function sanitizePrompt(string $prompt): string
    {
        // Strip HTML tags
        $prompt = strip_tags($prompt);

        // Remove excessive whitespace
        $prompt = (string) preg_replace('/\s+/', ' ', $prompt);
        $prompt = trim($prompt);

        // Length validation
        if (strlen($prompt) < self::MIN_PROMPT_LENGTH) {
            throw new InvalidPromptException(sprintf('Prompt must be at least %d characters', self::MIN_PROMPT_LENGTH));
        }

        if (strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            throw new InvalidPromptException(sprintf('Prompt must be max %d characters', self::MAX_PROMPT_LENGTH));
        }

        // Block prohibited keywords
        foreach (self::PROHIBITED_KEYWORDS as $keyword) {
            if (stripos($prompt, $keyword) !== false) {
                throw new InvalidPromptException(sprintf('Prompt contains prohibited content: "%s"', $keyword));
            }
        }

        // Add safety suffix
        $prompt .= ', professional illustration, safe for work, high quality';

        return $prompt;
    }

    /**
     * Check rate limits (prevent abuse).
     *
     * @throws RateLimitExceededException
     */
    private function checkRateLimit(Company $company): bool
    {
        $cacheKey = sprintf('blog_ai_gen:%d:%s', $company->getId(), date('YmdH'));
        $item     = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {
            $item->set(1);
            $item->expiresAfter(3600); // 1 hour
            $this->cache->save($item);

            return true;
        }

        $count = $item->get();

        if ($count >= self::RATE_LIMIT_PER_HOUR) {
            $minutesRemaining = 60 - (int) date('i');

            $exception = new RateLimitExceededException(
                sprintf(
                    'Rate limit exceeded: %d generations per hour. Try again in %d minutes.',
                    self::RATE_LIMIT_PER_HOUR,
                    $minutesRemaining,
                ),
            );
            $exception->setRetryAfterMinutes($minutesRemaining);

            throw $exception;
        }

        $item->set($count + 1);
        $this->cache->save($item);

        return true;
    }

    /**
     * Generate unique filename for blog image.
     */
    private function generateFilename(string $slug): string
    {
        return sprintf(
            'blog-%s-%s-%s.png',
            $slug,
            date('Ymd-His'),
            uniqid(),
        );
    }
}
