<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Invoice\Persistence\Doctrine;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Entity\Invoice as DddInvoice;
use App\Domain\Invoice\Exception\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Invoice\ValueObject\InvoiceStatus;
use App\Infrastructure\Invoice\Persistence\Doctrine\DoctrineDddInvoiceRepository;
use App\Infrastructure\Invoice\Translator\InvoiceDddToFlatTranslator;
use App\Infrastructure\Invoice\Translator\InvoiceFlatToDddTranslator;
use App\Repository\InvoiceRepository as FlatInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Phase 2 ACL Invoice — only test the DDD-side guard paths and error paths
 * that don't trigger the FlatToDdd translator (which depends on protected
 * Doctrine properties not safely mockable in pure Unit tests).
 *
 * Translator-end-to-end coverage lives in functional tests using fixtures
 * loaded via Doctrine.
 */
#[AllowMockObjectsWithoutExpectations]
final class DoctrineDddInvoiceRepositoryTest extends TestCase
{
    public function testFindByIdThrowsWhenNotFound(): void
    {
        $flatRepo = $this->createMock(FlatInvoiceRepository::class);
        $flatRepo->method('find')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $this->expectException(InvoiceNotFoundException::class);
        $repo->findById(InvoiceId::generate());
    }

    public function testFindByIdOrNullReturnsNullForUuid(): void
    {
        $repo = $this->makeRepo();

        $this->assertNull($repo->findByIdOrNull(InvoiceId::generate()));
    }

    public function testFindByClientIdReturnsEmptyForUuid(): void
    {
        $repo = $this->makeRepo();

        $this->assertSame([], $repo->findByClientId(ClientId::generate()));
    }

    public function testFindByCompanyIdReturnsEmptyForUuid(): void
    {
        $repo = $this->makeRepo();

        $this->assertSame([], $repo->findByCompanyId(CompanyId::generate()));
    }

    public function testSavePureUuidThrows(): void
    {
        $repo = $this->makeRepo();

        $invoice = $this->makeUuidInvoice();

        $this->expectException(RuntimeException::class);
        $repo->save($invoice);
    }

    public function testSaveLegacyNotFoundThrows(): void
    {
        $flatRepo = $this->createMock(FlatInvoiceRepository::class);
        $flatRepo->method('find')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $invoice = $this->makeLegacyInvoice(999);

        $this->expectException(InvoiceNotFoundException::class);
        $repo->save($invoice);
    }

    public function testDeletePureUuidThrows(): void
    {
        $repo = $this->makeRepo();

        $invoice = $this->makeUuidInvoice();

        $this->expectException(RuntimeException::class);
        $repo->delete($invoice);
    }

    public function testDeleteLegacyNotFoundThrows(): void
    {
        $flatRepo = $this->createMock(FlatInvoiceRepository::class);
        $flatRepo->method('find')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $invoice = $this->makeLegacyInvoice(999);

        $this->expectException(InvoiceNotFoundException::class);
        $repo->delete($invoice);
    }

    public function testNextNumberReturnsExpectedFormat(): void
    {
        $repo = $this->makeRepo();

        $number = $repo->nextNumber(2026, 5);

        $this->assertSame('F202605001', $number->getValue());
    }

    public function testFindOverdueDelegatesToFindByStatus(): void
    {
        $flatRepo = $this->createMock(FlatInvoiceRepository::class);
        $flatRepo->method('findBy')->willReturn([]);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $this->assertSame([], $repo->findOverdue());
    }

    public function testFindByStatusEmptyResult(): void
    {
        $flatRepo = $this->createMock(FlatInvoiceRepository::class);
        $flatRepo->method('findBy')->willReturn([]);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $this->assertSame([], $repo->findByStatus(InvoiceStatus::PAID));
    }

    public function testFindByNumberReturnsNullWhenNotFound(): void
    {
        $flatRepo = $this->createMock(FlatInvoiceRepository::class);
        $flatRepo->method('findOneBy')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $this->assertNull($repo->findByNumber(InvoiceNumber::fromString('F202601001')));
    }

    private function makeUuidInvoice(): DddInvoice
    {
        return DddInvoice::create(
            InvoiceId::generate(),
            InvoiceNumber::fromString('F202601001'),
            CompanyId::generate(),
            ClientId::generate(),
        );
    }

    private function makeLegacyInvoice(int $id): DddInvoice
    {
        return DddInvoice::create(
            InvoiceId::fromLegacyInt($id),
            InvoiceNumber::fromString('F202601001'),
            CompanyId::fromLegacyInt(1),
            ClientId::fromLegacyInt(1),
        );
    }

    private function makeRepo(
        ?FlatInvoiceRepository $flatRepo = null,
        ?EntityManagerInterface $em = null,
    ): DoctrineDddInvoiceRepository {
        $flatRepo ??= $this->createMock(FlatInvoiceRepository::class);
        $em ??= $this->createMock(EntityManagerInterface::class);

        return new DoctrineDddInvoiceRepository(
            $flatRepo,
            $em,
            new InvoiceFlatToDddTranslator(),
            new InvoiceDddToFlatTranslator(),
        );
    }
}
