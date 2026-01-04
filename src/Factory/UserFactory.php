<?php

namespace App\Factory;

use App\Entity\User;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use function Zenstruck\Foundry\lazy;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    private ?CompanyContext $companyContext = null;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        CompanyContext $companyContext
    ) {
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
            'company' => $company ?? lazy(fn () => CompanyFactory::createOne()),
            'email'   => $faker->unique()->safeEmail(),
            'roles'   => [],
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
