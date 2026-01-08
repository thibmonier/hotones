<?php

namespace App\Factory;

use App\Entity\ProjectTask;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Generator;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProjectTask>
 */
final class ProjectTaskFactory extends PersistentObjectFactory
{
    public function __construct(private readonly ?CompanyContext $companyContext)
    {
        parent::__construct();
    }

    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        $type = $this->pickWeighted([
            ProjectTask::TYPE_REGULAR   => 70,
            ProjectTask::TYPE_AVV       => 15,
            ProjectTask::TYPE_NON_VENDU => 15,
        ]);

        $status = $this->pickWeighted([
            'not_started' => 30,
            'in_progress' => 50,
            'completed'   => 15,
            'on_hold'     => 5,
        ]);

        // Daily rate: more often 500-900, sometimes 900-1200
        $dailyRate = $faker->boolean(70) ? (string) $faker->randomFloat(2, 500, 900) : (string) $faker->randomFloat(2, 900, 1200);

        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            // No authenticated user - will create new company
        }

        return [
            'company'                => $company ?? CompanyFactory::new(),
            'project'                => ProjectFactory::random(),
            'name'                   => $faker->sentence(3),
            'description'            => $faker->optional()->sentence(12),
            'type'                   => $type,
            'isDefault'              => $type !== ProjectTask::TYPE_REGULAR,
            'countsForProfitability' => $type === ProjectTask::TYPE_REGULAR,
            'position'               => $faker->numberBetween(1, 50),
            'active'                 => true,
            'estimatedHoursSold'     => $type === ProjectTask::TYPE_REGULAR ? $faker->numberBetween(8, 200) : null,
            'estimatedHoursRevised'  => $type === ProjectTask::TYPE_REGULAR ? $faker->optional()->numberBetween(8, 220) : null,
            'progressPercentage'     => 0, // adjusted in initialize based on status
            'assignedContributor'    => $faker->boolean(70) ? ContributorFactory::random() : null,
            'requiredProfile'        => $faker->boolean(70) ? ProfileFactory::random() : null,
            'dailyRate'              => $faker->boolean(65) ? $dailyRate : null,
            'startDate'              => $faker->optional()->dateTimeBetween('-6 months', 'now'),
            'endDate'                => $faker->optional()->dateTimeBetween('now', '+6 months'),
            'status'                 => $status,
        ];
    }

    #[Override]
    public function initialize(): static
    {
        return $this->afterInstantiate(function (ProjectTask $task): void {
            $status   = $task->getStatus();
            $progress = match ($status) {
                'completed'   => 100,
                'not_started' => 0,
                'in_progress' => random_int(10, 90),
                'on_hold'     => random_int(0, 50),
                default       => 0,
            };
            $task->setProgressPercentage($progress);
        });
    }

    private function pickWeighted(array $weights): string
    {
        $sum  = array_sum($weights);
        $rand = mt_rand(1, max(1, $sum));
        $cum  = 0;
        foreach ($weights as $value => $weight) {
            $cum += $weight;
            if ($rand <= $cum) {
                return (string) $value;
            }
        }

        return (string) array_key_first($weights);
    }

    public static function class(): string
    {
        return ProjectTask::class;
    }
}
