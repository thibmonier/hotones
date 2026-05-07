<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Client\Persistence\Doctrine;

use App\Domain\Client\Entity\Client as DddClient;
use App\Domain\Client\Exception\ClientNotFoundException;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\CompanyName;
use App\Domain\Client\ValueObject\ServiceLevel;
use App\Domain\Shared\ValueObject\Email;
use App\Entity\Client as FlatClient;
use App\Entity\Company;
use App\Infrastructure\Client\Persistence\Doctrine\DoctrineDddClientRepository;
use App\Infrastructure\Client\Translator\ClientDddToFlatTranslator;
use App\Infrastructure\Client\Translator\ClientFlatToDddTranslator;
use App\Repository\ClientRepository as FlatClientRepository;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
final class DoctrineDddClientRepositoryTest extends TestCase
{
    public function testFindByIdReturnsTranslatedAggregate(): void
    {
        $flat = $this->makeFlatClient(42, 'Acme');

        $flatRepo = $this->createMock(FlatClientRepository::class);
        $flatRepo->method('find')->willReturnCallback(
            static fn (mixed $id): ?FlatClient => 42 === $id ? $flat : null,
        );

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $ddd = $repo->findById(ClientId::fromLegacyInt(42));

        $this->assertSame(42, $ddd->getId()->toLegacyInt());
        $this->assertSame('Acme', $ddd->getName()->getValue());
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $flatRepo = $this->createMock(FlatClientRepository::class);
        $flatRepo->method('find')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $this->expectException(ClientNotFoundException::class);
        $repo->findById(ClientId::fromLegacyInt(999));
    }

    public function testFindByIdOrNullReturnsNullForUuidId(): void
    {
        $repo = $this->makeRepo();

        $result = $repo->findByIdOrNull(ClientId::generate());

        $this->assertNull($result);
    }

    public function testFindByIdOrNullReturnsNullWhenFlatMissing(): void
    {
        $flatRepo = $this->createMock(FlatClientRepository::class);
        $flatRepo->method('find')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $this->assertNull($repo->findByIdOrNull(ClientId::fromLegacyInt(99)));
    }

    public function testFindByEmailAlwaysReturnsNullPhase2(): void
    {
        $repo = $this->makeRepo();

        $this->assertNull($repo->findByEmail(Email::fromString('test@example.com')));
    }

    public function testFindAllTranslatesAllFlatClients(): void
    {
        $flat1 = $this->makeFlatClient(1, 'Alpha');
        $flat2 = $this->makeFlatClient(2, 'Beta');

        $flatRepo = $this->createMock(FlatClientRepository::class);
        $flatRepo->method('findAllForCurrentCompany')->willReturn([$flat1, $flat2]);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $clients = $repo->findAll();

        $this->assertCount(2, $clients);
        $this->assertSame(1, $clients[0]->getId()->toLegacyInt());
        $this->assertSame(2, $clients[1]->getId()->toLegacyInt());
    }

    public function testFindActiveDelegatesToFindAll(): void
    {
        $flat = $this->makeFlatClient(7, 'Active');

        $flatRepo = $this->createMock(FlatClientRepository::class);
        $flatRepo->method('findAllForCurrentCompany')->willReturn([$flat]);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $this->assertCount(1, $repo->findActive());
    }

    public function testSaveExistingLegacyClient(): void
    {
        $flat = $this->makeFlatClient(7, 'Old');

        $flatRepo = $this->createMock(FlatClientRepository::class);
        $flatRepo->method('find')->willReturn($flat);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($flat);
        $em->expects($this->once())->method('flush');

        $repo = $this->makeRepo(flatRepo: $flatRepo, em: $em);

        $ddd = DddClient::create(
            ClientId::fromLegacyInt(7),
            CompanyName::fromString('Updated'),
            ServiceLevel::ENTERPRISE,
        );
        $repo->save($ddd);
    }

    public function testSaveLegacyClientNotFoundThrows(): void
    {
        $flatRepo = $this->createMock(FlatClientRepository::class);
        $flatRepo->method('find')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $ddd = DddClient::create(
            ClientId::fromLegacyInt(999),
            CompanyName::fromString("Acme"),
        );

        $this->expectException(ClientNotFoundException::class);
        $repo->save($ddd);
    }

    public function testSavePureUuidThrowsRuntimeException(): void
    {
        $repo = $this->makeRepo();

        $ddd = DddClient::create(
            ClientId::generate(),
            CompanyName::fromString('Future'),
        );

        $this->expectException(RuntimeException::class);
        $repo->save($ddd);
    }

    public function testDeleteExistingLegacyClient(): void
    {
        $flat = $this->makeFlatClient(7, 'Doomed');

        $flatRepo = $this->createMock(FlatClientRepository::class);
        $flatRepo->method('find')->willReturn($flat);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($flat);
        $em->expects($this->once())->method('flush');

        $repo = $this->makeRepo(flatRepo: $flatRepo, em: $em);

        $ddd = DddClient::create(
            ClientId::fromLegacyInt(7),
            CompanyName::fromString('Doomed'),
        );
        $repo->delete($ddd);
    }

    public function testDeletePureUuidThrowsRuntimeException(): void
    {
        $repo = $this->makeRepo();

        $ddd = DddClient::create(
            ClientId::generate(),
            CompanyName::fromString("Acme"),
        );

        $this->expectException(RuntimeException::class);
        $repo->delete($ddd);
    }

    public function testDeleteLegacyClientNotFoundThrows(): void
    {
        $flatRepo = $this->createMock(FlatClientRepository::class);
        $flatRepo->method('find')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $ddd = DddClient::create(
            ClientId::fromLegacyInt(999),
            CompanyName::fromString("Acme"),
        );

        $this->expectException(ClientNotFoundException::class);
        $repo->delete($ddd);
    }

    private function makeFlatClient(int $id, string $name): FlatClient
    {
        $flat = new FlatClient();
        (new ReflectionProperty(FlatClient::class, 'id'))->setValue($flat, $id);
        $flat->name = $name;
        $flat->serviceLevel = 'standard';
        $flat->description = null;

        return $flat;
    }

    private function makeRepo(
        ?FlatClientRepository $flatRepo = null,
        ?EntityManagerInterface $em = null,
    ): DoctrineDddClientRepository {
        $flatRepo ??= $this->createMock(FlatClientRepository::class);
        $em ??= $this->createMock(EntityManagerInterface::class);

        $companyContext = $this->createMock(CompanyContext::class);
        $companyContext->method('getCurrentCompany')->willReturn(new Company());

        return new DoctrineDddClientRepository(
            $flatRepo,
            $em,
            $companyContext,
            new ClientFlatToDddTranslator(),
            new ClientDddToFlatTranslator(),
        );
    }
}
