<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\ProjectSubTask;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Generator;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProjectSubTask>
 */
final class ProjectSubTaskFactory extends PersistentObjectFactory
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

        $initial   = (string) $faker->randomFloat(2, 1, 40);
        $remaining = $faker->boolean(70) ? (string) $faker->randomFloat(2, 0, (float) $initial) : '0.00';

        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            // No authenticated user - will create new company
        }

        return [
            'company'               => $company ?? CompanyFactory::new(),
            'project'               => ProjectFactory::random(), // will be aligned with task in initialize()
            'task'                  => ProjectTaskFactory::random(),
            'assignee'              => $faker->boolean(60) ? ContributorFactory::random() : null,
            'title'                 => $faker->sentence(4),
            'initialEstimatedHours' => $initial,
            'remainingHours'        => $remaining,
            'status'                => $faker->randomElement(['todo', 'in_progress', 'done', 'blocked']),
            'position'              => $faker->numberBetween(1, 50),
        ];
    }

    #[Override]
    public function initialize(): static
    {
        return $this->afterInstantiate(function (ProjectSubTask $subTask): void {
            // Ensure project is consistent with the task's project by using the setter
            $subTask->setTask($subTask->getTask());
        });
    }

    public static function class(): string
    {
        return ProjectSubTask::class;
    }
}
