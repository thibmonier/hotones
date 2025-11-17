<?php

namespace App\Factory;

use App\Entity\Profile;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Profile>
 */
final class ProfileFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = FakerFactory::create('fr_FR');

        return [
            'name'             => $faker->unique()->jobTitle(),
            'description'      => $faker->optional()->sentence(10),
            'defaultDailyRate' => (string) $faker->numberBetween(400, 900),
            'color'            => sprintf('#%06X', $faker->numberBetween(0, 0xFFFFFF)),
            'active'           => true,
        ];
    }

    public static function class(): string
    {
        return Profile::class;
    }
}
