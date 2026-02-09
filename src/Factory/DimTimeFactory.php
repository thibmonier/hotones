<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Analytics\DimTime;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DimTime>
 */
final class DimTimeFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly ?CompanyContext $companyContext,
    ) {
        parent::__construct();
    }

    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            $company = CompanyFactory::createOne();
        }

        return [
            'company' => $company,
            'date'    => $faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

    public static function class(): string
    {
        return DimTime::class;
    }
}
