<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\ContractType;

/**
 * Doctrine type for ContractType enum.
 *
 * @extends AbstractEnumType<ContractType>
 */
final class ContractTypeType extends AbstractEnumType
{
    public const string NAME = 'contract_type';

    protected function getEnumClass(): string
    {
        return ContractType::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
