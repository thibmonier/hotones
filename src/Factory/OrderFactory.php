<?php

namespace App\Factory;

use App\Entity\Order;
use Faker\Generator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Order>
 */
final class OrderFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        /** @var Generator $faker */
        $faker = self::faker();
        $date  = $faker->dateTimeBetween('-1 year', 'now');

        return [
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
            'validatedAt' => $faker->optional()->dateTimeBetween($date, '+4 months'),
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
