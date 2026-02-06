<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Planning;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use DateTime;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Planning>
 */
final class PlanningFactory extends PersistentObjectFactory
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
            // No authenticated user - will create new company
        }

        // Generate random date range within the next 6 months
        $startDate = $faker->dateTimeBetween('-1 month', '+3 months');
        $endDate   = (clone $startDate)->modify('+'.$faker->numberBetween(5, 30).' days');

        return [
            'company'     => $company ?? CompanyFactory::new(),
            'contributor' => ContributorFactory::new(),
            'project'     => ProjectFactory::new(),
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'dailyHours'  => $faker->randomElement(['4.00', '7.00', '8.00', '8.00', '8.00']), // Bias toward full-time
            'profile'     => null,
            'status'      => $faker->randomElement(['planned', 'planned', 'confirmed', 'confirmed', 'cancelled']),
            'notes'       => $faker->optional(0.3)->sentence(),
            'createdAt'   => new DateTime(),
        ];
    }

    public static function class(): string
    {
        return Planning::class;
    }
}
