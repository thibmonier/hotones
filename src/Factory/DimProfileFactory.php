<?php

namespace App\Factory;

use App\Entity\Analytics\DimProfile;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Generator;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DimProfile>
 */
final class DimProfileFactory extends PersistentObjectFactory
{
    public function __construct(private readonly ?CompanyContext $companyContext)
    {
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
            'company'      => $company,
            'name'         => $faker->randomElement(['Développeur', 'Lead Dev', 'Chef de projet', 'Designer']),
            'isProductive' => $faker->boolean(80),
            'isActive'     => true,
            'profile'      => null, // Can be set explicitly if needed
        ];
    }

    public static function class(): string
    {
        return DimProfile::class;
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
