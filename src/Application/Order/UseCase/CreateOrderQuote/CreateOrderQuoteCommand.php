<?php

declare(strict_types=1);

namespace App\Application\Order\UseCase\CreateOrderQuote;

final readonly class CreateOrderQuoteCommand
{
    public function __construct(
        public int $clientId,
        public ?int $projectId,
        public string $reference,
        public string $contractType, // 'forfait' | 'regie'
        public float $amount,
        public ?string $title = null,
        public ?string $description = null,
    ) {
    }
}
