<?php

namespace App\Factory;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use DateTime;
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
            'active'            => true,
            'user'              => null,
            'avatarFilename'    => null,
            // profiles set in fixtures as it's ManyToMany with domain rules
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Contributor $contributor) {
            // Create an active employment period with CJM/TJM
            $faker            = self::faker();
            $employmentPeriod = new EmploymentPeriod();
            $employmentPeriod
                ->setContributor($contributor)
                ->setStartDate(new DateTime('-6 months'))
                ->setCjm((float) $faker->numberBetween(300, 700))
                ->setTjm((float) $faker->numberBetween(450, 1000))
                ->setWeeklyHours(35.0);
            $contributor->addEmploymentPeriod($employmentPeriod);
        });
    }

    public static function class(): string
    {
        return Contributor::class;
    }
}
