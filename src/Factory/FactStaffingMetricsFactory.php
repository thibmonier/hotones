<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Analytics\FactStaffingMetrics;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use DateTime;
use Faker\Generator;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<FactStaffingMetrics>
 */
final class FactStaffingMetricsFactory extends PersistentObjectFactory
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
            'company'          => $company,
            'dimTime'          => DimTimeFactory::new(),
            'dimProfile'       => null, // Should be set explicitly to avoid unique constraint issues
            'contributor'      => null, // Can be set explicitly
            'availableDays'    => (string) $faker->numberBetween(15, 22),
            'workedDays'       => (string) $faker->numberBetween(15, 22),
            'staffedDays'      => (string) $faker->numberBetween(10, 20),
            'vacationDays'     => (string) $faker->numberBetween(0, 5),
            'plannedDays'      => (string) $faker->numberBetween(0, 10),
            'calculatedAt'     => new DateTime(),
            'granularity'      => $faker->randomElement(['weekly', 'monthly', 'quarterly']),
            'contributorCount' => $faker->numberBetween(1, 10),
        ];
    }

    public static function class(): string
    {
        return FactStaffingMetrics::class;
    }

    #[Override]
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (FactStaffingMetrics $factStaffingMetrics): void {
            // Auto-calculate metrics after instantiation
            $factStaffingMetrics->calculateMetrics();
        })->afterPersist(function (FactStaffingMetrics $factStaffingMetrics): void {
            // DimTime should already be persisted by cascade
        });
    }
}
