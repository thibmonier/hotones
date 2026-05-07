<?php

declare(strict_types=1);

namespace App\Application\Invoice\UseCase\CreateInvoiceDraft;

final readonly class CreateInvoiceDraftCommand
{
    public function __construct(
        public int $companyId,
        public int $clientId,
        public ?int $orderId = null,
        public ?int $projectId = null,
        public ?string $paymentTerms = null,
    ) {
    }
}
