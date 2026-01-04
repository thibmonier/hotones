<?php

namespace App\Factory;

use App\Entity\Order;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Order>
 */
final class OrderFactory extends PersistentObjectFactory
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
        $date  = DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now'));

        // Try to get company from context (for multi-tenant tests), fallback to creating new company
        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
            // No authenticated user - will create new company
        }

        $validatedAtTemp = $faker->optional()->dateTimeBetween($date, '+4 months');

        return [
            'company'     => $company ?? CompanyFactory::new(),
            'project'     => ProjectFactory::random(),
            'name'        => $faker->sentence(3),
            'description' => $faker->optional()->paragraph(2, true),
            // Mostly small contingency (0-5%), sometimes up to 10%
            'contingencyPercentage' => (string) ($faker->boolean(75) ? $faker->randomFloat(2, 0, 5) : $faker->randomFloat(2, 5, 10)),
            'validUntil'            => $faker->optional()->dateTimeBetween('now', '+3 months'),
            'orderNumber'           => Order::generateOrderNumber($date),
            'notes'                 => $faker->optional()->sentence(10),
            'contingenceAmount'     => (string) $faker->randomFloat(2, 0, 2000),
            'contingenceReason'     => $faker->optional()->sentence(8),
            // provisional, may be updated by fixtures after creating tasks/sections
            'totalAmount' => (string) $faker->randomFloat(2, 1000, 50000),
            'createdAt'   => $date,
            'validatedAt' => $validatedAtTemp ? DateTimeImmutable::createFromMutable($validatedAtTemp) : null,
            'status'      => $this->pickWeighted([
                'a_signer' => 10,
                'gagne'    => 20,
                'signe'    => 40,
                'perdu'    => 15,
                'termine'  => 10,
                'standby'  => 5,
            ]),
        ];
    }

    private function pickWeighted(array $weights): string
    {
        $sum  = array_sum($weights);
        $rand = mt_rand(1, max(1, $sum));
        $cum  = 0;
        foreach ($weights as $value => $weight) {
            $cum += $weight;
            if ($rand <= $cum) {
                return (string) $value;
            }
        }

        return (string) array_key_first($weights);
    }

    public static function class(): string
    {
        return Order::class;
    }
}
