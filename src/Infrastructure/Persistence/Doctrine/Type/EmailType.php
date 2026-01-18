<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\ValueObject\Email;

/**
 * Doctrine type for Email Value Object.
 *
 * @extends AbstractStringType<Email>
 */
final class EmailType extends AbstractStringType
{
    public const string NAME = 'email';

    protected function getValueObjectClass(): string
    {
        return Email::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
