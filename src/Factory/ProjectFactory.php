<?php

namespace App\Factory;

use App\Entity\Project;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Project>
 */
final class ProjectFactory extends PersistentObjectFactory
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

        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            // No authenticated user - will create new company
        }

        return [
            'name'                 => $faker->company(),
            'company'              => $company ?? CompanyFactory::new(),
            'client'               => null, // set in fixtures with ClientFactory::random()
            'description'          => $faker->optional()->paragraphs(3, true),
            'purchasesAmount'      => $faker->optional()->randomFloat(2, 100, 5000),
            'purchasesDescription' => $faker->optional()->sentence(8, true),
            'startDate'            => $faker->optional()->dateTimeBetween('-1 year', 'now'),
            'endDate'              => $faker->optional()->dateTimeBetween('now', '+1 year'),
            'status'               => $faker->randomElement(['active', 'completed', 'cancelled']),
            'isInternal'           => $faker->boolean(15),
            'projectType'          => $faker->randomElement(['forfait', 'regie']),
            'keyAccountManager'    => null,
            'projectManager'       => null,
            'projectDirector'      => null,
            'salesPerson'          => null,
        ];
    }

    public static function class(): string
    {
        return Project::class;
    }
}
