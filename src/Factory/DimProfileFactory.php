<?php

namespace App\Factory;

use App\Entity\Analytics\DimProfile;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DimProfile>
 */
final class DimProfileFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        return [
            'name'         => $faker->randomElement(['Développeur', 'Lead Dev', 'Chef de projet', 'Designer']),
            'isProductive' => $faker->boolean(80),
            'isActive'     => true,
            'profile'      => null, // Can be set explicitly if needed
        ];
    }

    public static function class(): string
    {
        return DimProfile::class;
    }

    protected function initialize(): static
    {
        return $this;
    }
}
