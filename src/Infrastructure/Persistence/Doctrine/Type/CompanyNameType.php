<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Client\ValueObject\CompanyName;

/**
 * Doctrine type for CompanyName Value Object.
 *
 * @extends AbstractStringType<CompanyName>
 */
final class CompanyNameType extends AbstractStringType
{
    public const string NAME = 'company_name';

    protected function getValueObjectClass(): string
    {
        return CompanyName::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
