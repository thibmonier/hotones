<?php

namespace App\Factory;

use App\Entity\Client;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Client>
 */
final class ClientFactory extends PersistentObjectFactory
{
    private ?CompanyContext $companyContext = null;

    public function __construct(CompanyContext $companyContext)
    {
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
            'company'     => $company ?? CompanyFactory::new(),
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
