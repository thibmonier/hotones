<?php

namespace App\Factory;

use App\Entity\Analytics\DimProjectType;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DimProjectType>
 */
final class DimProjectTypeFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'projectType'     => self::faker()->randomElement(['forfait', 'regie']),
            'serviceCategory' => self::faker()->optional(0.7)->randomElement(['Brand', 'E-commerce', 'Autre']),
            'status'          => self::faker()->randomElement(['active', 'completed', 'cancelled']),
            'isInternal'      => self::faker()->boolean(20), // 20% chance d'être interne
        ];
    }

    public static function class(): string
    {
        return DimProjectType::class;
    }
}
