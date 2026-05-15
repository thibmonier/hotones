<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\EmployeeLevel;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use InvalidArgumentException;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<EmployeeLevel>
 */
final class EmployeeLevelFactory extends PersistentObjectFactory
{
    /**
     * Configuration par défaut des 12 niveaux avec fourchettes salariales.
     */
    private const array LEVELS_CONFIG = [
        1 => [
            'name' => 'Junior 1',
            'salaryMin' => 28_000,
            'salaryMax' => 32_000,
            'salaryTarget' => 30_000,
            'tjm' => 350,
            'color' => '#90CAF9',
        ],
        2 => [
            'name' => 'Junior 2',
            'salaryMin' => 32_000,
            'salaryMax' => 36_000,
            'salaryTarget' => 34_000,
            'tjm' => 400,
            'color' => '#64B5F6',
        ],
        3 => [
            'name' => 'Junior 3',
            'salaryMin' => 36_000,
            'salaryMax' => 40_000,
            'salaryTarget' => 38_000,
            'tjm' => 450,
            'color' => '#42A5F5',
        ],
        4 => [
            'name' => 'Confirmé 1',
            'salaryMin' => 40_000,
            'salaryMax' => 45_000,
            'salaryTarget' => 42_500,
            'tjm' => 500,
            'color' => '#81C784',
        ],
        5 => [
            'name' => 'Confirmé 2',
            'salaryMin' => 45_000,
            'salaryMax' => 50_000,
            'salaryTarget' => 47_500,
            'tjm' => 550,
            'color' => '#66BB6A',
        ],
        6 => [
            'name' => 'Confirmé 3',
            'salaryMin' => 50_000,
            'salaryMax' => 55_000,
            'salaryTarget' => 52_500,
            'tjm' => 600,
            'color' => '#4CAF50',
        ],
        7 => [
            'name' => 'Senior 1',
            'salaryMin' => 55_000,
            'salaryMax' => 62_000,
            'salaryTarget' => 58_500,
            'tjm' => 650,
            'color' => '#FFB74D',
        ],
        8 => [
            'name' => 'Senior 2',
            'salaryMin' => 62_000,
            'salaryMax' => 70_000,
            'salaryTarget' => 66_000,
            'tjm' => 700,
            'color' => '#FFA726',
        ],
        9 => [
            'name' => 'Senior 3',
            'salaryMin' => 70_000,
            'salaryMax' => 78_000,
            'salaryTarget' => 74_000,
            'tjm' => 750,
            'color' => '#FF9800',
        ],
        10 => [
            'name' => 'Lead 1',
            'salaryMin' => 78_000,
            'salaryMax' => 88_000,
            'salaryTarget' => 83_000,
            'tjm' => 850,
            'color' => '#E57373',
        ],
        11 => [
            'name' => 'Lead 2',
            'salaryMin' => 88_000,
            'salaryMax' => 100_000,
            'salaryTarget' => 94_000,
            'tjm' => 950,
            'color' => '#EF5350',
        ],
        12 => [
            'name' => 'Expert',
            'salaryMin' => 100_000,
            'salaryMax' => 120_000,
            'salaryTarget' => 110_000,
            'tjm' => 1100,
            'color' => '#F44336',
        ],
    ];

    public function __construct(
        private readonly ?CompanyContext $companyContext,
    ) {
        parent::__construct();
    }

    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = FakerFactory::create('fr_FR');

        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            // No authenticated user - will create new company
        }

        // Random level by default
        $level = $faker->numberBetween(1, 12);
        $config = self::LEVELS_CONFIG[$level];

        return [
            'level' => $level,
            'name' => $config['name'],
            'description' => $faker->optional()->sentence(10),
            'salaryMin' => (string) $config['salaryMin'],
            'salaryMax' => (string) $config['salaryMax'],
            'salaryTarget' => (string) $config['salaryTarget'],
            'targetTjm' => (string) $config['tjm'],
            'color' => $config['color'],
            'active' => true,
            'company' => $company ?? CompanyFactory::new(),
        ];
    }

    /**
     * Crée un niveau spécifique avec les valeurs par défaut.
     */
    public function withLevel(int $level): self
    {
        if ($level < 1 || $level > 12) {
            throw new InvalidArgumentException('Level must be between 1 and 12');
        }

        $config = self::LEVELS_CONFIG[$level];

        return $this->with([
            'level' => $level,
            'name' => $config['name'],
            'salaryMin' => (string) $config['salaryMin'],
            'salaryMax' => (string) $config['salaryMax'],
            'salaryTarget' => (string) $config['salaryTarget'],
            'targetTjm' => (string) $config['tjm'],
            'color' => $config['color'],
        ]);
    }

    /**
     * Crée tous les 12 niveaux pour une entreprise.
     *
     * @return EmployeeLevel[]
     */
    public static function createAllLevels(array $attributes = []): array
    {
        $levels = [];
        for ($i = 1; $i <= 12; ++$i) {
            $levels[] = self::new()->withLevel($i)->create($attributes);
        }

        return $levels;
    }

    public static function class(): string
    {
        return EmployeeLevel::class;
    }
}
