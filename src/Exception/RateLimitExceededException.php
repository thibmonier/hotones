<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when rate limit for AI image generation is exceeded.
 */
class RateLimitExceededException extends BlogImageGenerationException
{
    private int $retryAfterMinutes = 60;

    public function getRetryAfterMinutes(): int
    {
        return $this->retryAfterMinutes;
    }

    public function setRetryAfterMinutes(int $minutes): self
    {
        $this->retryAfterMinutes = $minutes;

        return $this;
    }
}
