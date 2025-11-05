<?php

namespace App\Factory;

use App\Entity\Client;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Client>
 */
final class ClientFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        return [
            'name'        => $faker->company(),
            'logoPath'    => null,
            'website'     => $faker->optional()->url(),
            'description' => $faker->optional()->paragraphs(2, true),
        ];
    }

    public static function class(): string
    {
        return Client::class;
    }
}
