<?php

namespace App\Factory;

use App\Entity\Analytics\DimProjectType;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DimProjectType>
 */
final class DimProjectTypeFactory extends PersistentObjectFactory
{
    private ?CompanyContext $companyContext;

    public function __construct(CompanyContext $companyContext)
    {
        parent::__construct();
        $this->companyContext = $companyContext;
    }

    protected function defaults(): array|callable
    {
        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            $company = CompanyFactory::createOne();
        }

        return [
            'company'         => $company,
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
