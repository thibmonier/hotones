<?php

namespace App\Factory;

use App\Entity\Project;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Project>
 */
final class ProjectFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        return [
            'name'                 => $faker->catchPhrase(),
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
