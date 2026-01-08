<?php

namespace App\Factory;

use App\Entity\Timesheet;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Timesheet>
 */
final class TimesheetFactory extends PersistentObjectFactory
{
    public function __construct(private readonly ?CompanyContext $companyContext)
    {
        parent::__construct();
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();

        $date = $faker->dateTimeBetween('-3 months', 'now');

        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            // No authenticated user - will create new company
        }

        return [
            'company'     => $company ?? CompanyFactory::new(),
            'contributor' => ContributorFactory::random(),
            'project'     => ProjectFactory::random(),
            'task'        => null,
            'subTask'     => null,
            'date'        => $date,
            'hours'       => $this->pickWeightedHours($faker),
            'notes'       => $faker->optional()->sentence(10),
        ];
    }

    private function pickWeightedHours(Generator $faker): string
    {
        // 60% between 7.0 and 8.0, 30% between 4.0 and 7.0, 10% between 0.5 and 3.5
        $r = mt_rand(1, 100);
        if ($r <= 60) {
            return (string) $faker->randomFloat(2, 7.0, 8.0);
        }
        if ($r <= 90) {
            return (string) $faker->randomFloat(2, 4.0, 7.0);
        }

        return (string) $faker->randomFloat(2, 0.5, 3.5);
    }

    public static function class(): string
    {
        return Timesheet::class;
    }
}
