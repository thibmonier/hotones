<?php

namespace App\Factory;

use App\Entity\User;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();

        return [
            'email' => $faker->unique()->safeEmail(),
            'roles' => [],
            // Set a plain password; it will be hashed in initialize().
            'password'      => 'password',
            'firstName'     => $faker->firstName(),
            'lastName'      => $faker->lastName(),
            'phone'         => $faker->optional()->phoneNumber(),
            'phoneWork'     => $faker->optional()->phoneNumber(),
            'phonePersonal' => $faker->optional()->phoneNumber(),
            'address'       => $faker->optional()->address(),
            'avatar'        => null,
            'totpSecret'    => null,
            'totpEnabled'   => false,
        ];
    }

    public function initialize(): static
    {
        return $this
            ->afterInstantiate(function (User $user): void {
                // Hash the plain password set in defaults
                $hashed = $this->passwordHasher->hashPassword($user, $user->getPassword());
                $user->setPassword($hashed);
            })
        ;
    }

    public static function class(): string
    {
        return User::class;
    }
}
