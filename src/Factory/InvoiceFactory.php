<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Invoice;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Invoice>
 */
final class InvoiceFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly ?CompanyContext $companyContext,
    ) {
        parent::__construct();
    }

    protected function defaults(): array|callable
    {
        $faker = self::faker();
        $issuedAt = $faker->dateTimeBetween('-1 year', 'now');

        $company = null;
        try {
            $company = $this->companyContext?->getCurrentCompany();
        } catch (CompanyContextMissingException) {
        }

        $amountHt = $faker->randomFloat(2, 100, 10_000);
        $tvaRate = 20.0;
        $amountTva = round($amountHt * $tvaRate / 100, 2);
        $amountTtc = round($amountHt + $amountTva, 2);

        return [
            'company' => $company ?? CompanyFactory::new(),
            'client' => ClientFactory::new(),
            'invoiceNumber' => self::generateNumber($issuedAt, $faker->numberBetween(1, 999)),
            'status' => Invoice::STATUS_PAID,
            'issuedAt' => $issuedAt,
            'dueDate' => (clone $issuedAt)->modify('+30 days'),
            'paidAt' => (clone $issuedAt)->modify('+'.$faker->numberBetween(5, 60).' days'),
            'amountHt' => (string) $amountHt,
            'amountTva' => (string) $amountTva,
            'tvaRate' => (string) $tvaRate,
            'amountTtc' => (string) $amountTtc,
        ];
    }

    /**
     * Convert DateTimeImmutable → DateTime (Doctrine `date` type compat).
     */
    public static function toMutable(DateTimeInterface $date): DateTime
    {
        if ($date instanceof DateTime) {
            return $date;
        }

        return DateTime::createFromImmutable($date);
    }

    private static function generateNumber(DateTimeInterface $date, int $sequence): string
    {
        return sprintf('F%s%03d', $date->format('Ym'), $sequence);
    }

    public static function class(): string
    {
        return Invoice::class;
    }
}
