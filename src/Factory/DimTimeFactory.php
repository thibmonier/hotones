<?php

namespace App\Factory;

use App\Entity\Analytics\DimTime;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DimTime>
 */
final class DimTimeFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        return [
            'date' => $faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

    public static function class(): string
    {
        return DimTime::class;
    }
}
