<?php

namespace App\Factory;

use App\Entity\Company;
use DateTime;
use Faker\Generator;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Company>
 */
final class CompanyFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        $name = $faker->unique()->company();
        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', trim($name)));

        return [
            'name'                       => $name,
            'slug'                       => $slug,
            'description'                => $faker->optional()->sentence(),
            'subscriptionTier'           => Company::TIER_PROFESSIONAL,
            'currency'                   => 'EUR',
            'structureCostCoefficient'   => '1.35',
            'employerChargesCoefficient' => '1.45',
            'annualPaidLeaveDays'        => 25,
            'annualRttDays'              => 10,
            'billingDayOfMonth'          => 1,
            'billingStartDate'           => new DateTime(),
            // owner will be set in initialize() to avoid circular dependency
        ];
    }

    #[Override]
    public function initialize(): static
    {
        // Note: Owner is not auto-created to avoid circular dependency issues
        // Tests that need an owner can set it explicitly:
        // CompanyFactory::createOne(['owner' => UserFactory::createOne()])
        return $this;
    }

    public static function class(): string
    {
        return Company::class;
    }
}
