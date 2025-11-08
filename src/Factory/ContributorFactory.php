<?php

namespace App\Factory;

use App\Entity\Contributor;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Contributor>
 */
final class ContributorFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        return [
            'firstName'         => $faker->firstName(),
            'lastName'          => $faker->lastName(),
            'email'             => $faker->optional()->safeEmail(),
            'phonePersonal'     => $faker->optional()->phoneNumber(),
            'phoneProfessional' => $faker->optional()->phoneNumber(),
            'address'           => $faker->optional()->address(),
            'notes'             => $faker->optional()->sentence(12),
            'cjm'               => (string) $faker->numberBetween(300, 700),
            'tjm'               => (string) $faker->numberBetween(450, 1000),
            'active'            => true,
            'user'              => null,
            'avatarFilename'    => null,
            // profiles set in fixtures as it's ManyToMany with domain rules
        ];
    }

    public static function class(): string
    {
        return Contributor::class;
    }
}
