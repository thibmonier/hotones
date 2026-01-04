<?php

namespace App\Factory;

use App\Entity\Profile;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Profile>
 */
final class ProfileFactory extends PersistentObjectFactory
{
    private ?CompanyContext $companyContext = null;

    public function __construct(CompanyContext $companyContext)
    {
        parent::__construct();
        $this->companyContext = $companyContext;
    }

    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = FakerFactory::create('fr_FR');

        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            // No authenticated user - will create new company
        }

        return [
            'name'             => $faker->unique()->jobTitle(),
            'description'      => $faker->optional()->sentence(10),
            'defaultDailyRate' => (string) $faker->numberBetween(400, 900),
            'color'            => sprintf('#%06X', $faker->numberBetween(0, 0xFFFFFF)),
            'active'           => true,
            'company'          => $company ?? CompanyFactory::new(),
        ];
    }

    public static function class(): string
    {
        return Profile::class;
    }
}
