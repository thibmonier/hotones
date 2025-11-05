<?php

namespace App\Factory;

use App\Entity\Timesheet;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Timesheet>
 */
final class TimesheetFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        $date = $faker->dateTimeBetween('-3 months', 'now');

        return [
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
