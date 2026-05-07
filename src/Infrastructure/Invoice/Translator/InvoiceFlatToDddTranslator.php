<?php

declare(strict_types=1);

namespace App\Infrastructure\Invoice\Translator;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Entity\Invoice as DddInvoice;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Invoice\ValueObject\InvoiceStatus;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Money;
use App\Entity\Invoice as FlatInvoice;
use DateTimeImmutable;
use RuntimeException;

/**
 * Anti-Corruption Layer translator (flat → DDD) for the Invoice BC.
 *
 * Stateless service.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0010 Invoice BC coexistence
 */
final class InvoiceFlatToDddTranslator
{
    public function translate(FlatInvoice $flat): DddInvoice
    {
        $id = InvoiceId::fromLegacyInt($flat->getId() ?? throw new RuntimeException('Cannot translate unsaved Invoice'));

        $number = InvoiceNumber::fromString($flat->invoiceNumber);

        $companyId = CompanyId::fromLegacyInt($flat->getCompany()->getId() ?? throw new RuntimeException('Invoice has no company'));
        $clientId = ClientId::fromLegacyInt($flat->getClient()->getId() ?? throw new RuntimeException('Invoice has no client'));

        $orderId = $flat->getOrder() !== null && $flat->getOrder()->id !== null
            ? OrderId::fromLegacyInt($flat->getOrder()->id)
            : null;
        $projectId = $flat->getProject() !== null && $flat->getProject()->getId() !== null
            ? ProjectId::fromLegacyInt($flat->getProject()->getId())
            : null;

        $status = InvoiceStatus::from($flat->status);

        return DddInvoice::reconstitute(
            id: $id,
            number: $number,
            companyId: $companyId,
            clientId: $clientId,
            orderId: $orderId,
            projectId: $projectId,
            extra: [
                'status' => $status,
                'amountHt' => Money::fromAmount((float) $flat->amountHt),
                'amountTva' => Money::fromAmount((float) $flat->amountTva),
                'amountTtc' => Money::fromAmount((float) $flat->amountTtc),
                'paymentTerms' => $flat->paymentTerms,
                'issuedAt' => DateTimeImmutable::createFromInterface($flat->issuedAt),
                'dueDate' => DateTimeImmutable::createFromInterface($flat->dueDate),
                'paidAt' => $flat->paidAt !== null ? DateTimeImmutable::createFromInterface($flat->paidAt) : null,
            ],
        );
    }
}
