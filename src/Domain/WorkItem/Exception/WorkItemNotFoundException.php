<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Exception;

use App\Domain\WorkItem\ValueObject\WorkItemId;
use RuntimeException;

final class WorkItemNotFoundException extends RuntimeException
{
    public static function withId(WorkItemId $id): self
    {
        return new self(sprintf('WorkItem not found: %s', $id->getValue()));
    }
}
