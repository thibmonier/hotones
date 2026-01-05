<?php

namespace App\Factory;

use App\Entity\OrderTask;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<OrderTask>
 */
final class OrderTaskFactory extends PersistentObjectFactory
{
    private ?CompanyContext $companyContext;

    public function __construct(CompanyContext $companyContext)
    {
        parent::__construct();
        $this->companyContext = $companyContext;
    }

    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        $days = (string) $faker->randomFloat(2, 1, 20);
        $rate = (string) $faker->randomFloat(2, 400, 1200);

        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            // No authenticated user - will create new company
        }

        return [
            'company'       => $company ?? CompanyFactory::new(),
            'order'         => OrderFactory::random(),
            'name'          => $faker->sentence(3),
            'description'   => $faker->optional()->sentence(12),
            'profile'       => ProfileFactory::random(),
            'soldDays'      => $days,
            'soldDailyRate' => $rate,
            // totalAmount computed in initialize via setters
        ];
    }

    public function initialize(): static
    {
        return $this->afterInstantiate(function (OrderTask $task): void {
            // Re-apply setters to compute totalAmount via entity logic
            $task->setSoldDays($task->getSoldDays());
            $task->setSoldDailyRate($task->getSoldDailyRate());
        });
    }

    public static function class(): string
    {
        return OrderTask::class;
    }
}
