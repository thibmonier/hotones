<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Invoice\UseCase\CreateInvoiceDraft;

use App\Application\Invoice\UseCase\CreateInvoiceDraft\CreateInvoiceDraftCommand;
use App\Application\Invoice\UseCase\CreateInvoiceDraft\CreateInvoiceDraftUseCase;
use App\Entity\Client as FlatClient;
use App\Entity\Company as FlatCompany;
use App\Entity\Invoice as FlatInvoice;
use App\Infrastructure\Invoice\Translator\InvoiceDddToFlatTranslator;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Unit tests for `CreateInvoiceDraftUseCase`.
 *
 * Sprint-018 fix US-INVOICE-DRAFT-UC-INVOICENUMBER-INIT-FIX :
 * happy path désormais testable en Unit grâce à
 * `ReflectionProperty::isInitialized()` côté UC (bypass property hook getter
 * sur typed property non-init).
 */
#[AllowMockObjectsWithoutExpectations]
final class CreateInvoiceDraftUseCaseTest extends TestCase
{
    public function testThrowsWhenCompanyNotFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn(null);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $useCase = new CreateInvoiceDraftUseCase($em, new InvoiceDddToFlatTranslator(), $bus);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Company 999 not found');

        $useCase->execute(new CreateInvoiceDraftCommand(
            companyId: 999,
            clientId: 7,
            orderId: null,
            projectId: null,
            paymentTerms: null,
        ));
    }

    public function testThrowsWhenClientNotFound(): void
    {
        $company = new FlatCompany();
        (new ReflectionProperty(FlatCompany::class, 'id'))->setValue($company, 1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturnCallback(
            static fn (string $class, mixed $id): ?object => match ($class) {
                FlatCompany::class => $company,
                FlatClient::class => null,
                default => null,
            },
        );

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $useCase = new CreateInvoiceDraftUseCase($em, new InvoiceDddToFlatTranslator(), $bus);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Client 999 not found');

        $useCase->execute(new CreateInvoiceDraftCommand(
            companyId: 1,
            clientId: 999,
            orderId: null,
            projectId: null,
            paymentTerms: null,
        ));
    }

    public function testCommandConstructionWithAllOptionalsNull(): void
    {
        // Smoke test sur le DTO Command immutable.
        $command = new CreateInvoiceDraftCommand(
            companyId: 1,
            clientId: 7,
            orderId: null,
            projectId: null,
            paymentTerms: null,
        );

        $this->assertSame(1, $command->companyId);
        $this->assertSame(7, $command->clientId);
        $this->assertNull($command->orderId);
        $this->assertNull($command->projectId);
        $this->assertNull($command->paymentTerms);
    }

    public function testCommandConstructionWithAllFields(): void
    {
        $command = new CreateInvoiceDraftCommand(
            companyId: 1,
            clientId: 7,
            orderId: 42,
            projectId: 33,
            paymentTerms: 'Net 30',
        );

        $this->assertSame(42, $command->orderId);
        $this->assertSame(33, $command->projectId);
        $this->assertSame('Net 30', $command->paymentTerms);
    }

    public function testHappyPathPersistsAndAutoGeneratesInvoiceNumber(): void
    {
        $useCase = $this->buildUseCaseWithCompanyAndClient(persistedId: 42);

        $reservationLikeId = $useCase->execute(new CreateInvoiceDraftCommand(
            companyId: 1,
            clientId: 7,
            orderId: null,
            projectId: null,
            paymentTerms: null,
        ));

        $this->assertSame(42, $reservationLikeId->toLegacyInt());
    }

    public function testHappyPathAppliesPaymentTermsViaTranslator(): void
    {
        $persistedFlat = null;
        $useCase = $this->buildUseCaseWithCompanyAndClient(
            persistedId: 7,
            persistCapture: function (FlatInvoice $flat) use (&$persistedFlat): void {
                $persistedFlat = $flat;
            },
        );

        $useCase->execute(new CreateInvoiceDraftCommand(
            companyId: 1,
            clientId: 7,
            orderId: null,
            projectId: null,
            paymentTerms: 'Net 60 days',
        ));

        $this->assertNotNull($persistedFlat);
        $this->assertSame('Net 60 days', $persistedFlat->paymentTerms);
        $this->assertNotEmpty($persistedFlat->invoiceNumber, 'auto-generated invoice number');
        $this->assertStringStartsWith('F', $persistedFlat->invoiceNumber);
    }

    /**
     * Build a UC mock setup where Company + Client are both found.
     */
    private function buildUseCaseWithCompanyAndClient(
        int $persistedId,
        ?Closure $persistCapture = null,
    ): CreateInvoiceDraftUseCase {
        $company = new FlatCompany();
        (new ReflectionProperty(FlatCompany::class, 'id'))->setValue($company, 1);

        $client = new FlatClient();
        (new ReflectionProperty(FlatClient::class, 'id'))->setValue($client, 7);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturnCallback(
            static fn (string $class, mixed $id): ?object => match ($class) {
                FlatCompany::class => $company,
                FlatClient::class => $client,
                default => null,
            },
        );
        $em->method('persist')->willReturnCallback(
            function (object $entity) use ($persistedId, $persistCapture): void {
                if ($entity instanceof FlatInvoice) {
                    (new ReflectionProperty(FlatInvoice::class, 'id'))->setValue($entity, $persistedId);
                    if ($persistCapture !== null) {
                        $persistCapture($entity);
                    }
                }
            },
        );

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        return new CreateInvoiceDraftUseCase($em, new InvoiceDddToFlatTranslator(), $bus);
    }
}
