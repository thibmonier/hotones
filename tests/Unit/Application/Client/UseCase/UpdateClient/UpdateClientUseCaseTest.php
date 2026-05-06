<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Client\UseCase\UpdateClient;

use App\Application\Client\UseCase\UpdateClient\UpdateClientCommand;
use App\Application\Client\UseCase\UpdateClient\UpdateClientUseCase;
use App\Domain\Client\Entity\Client;
use App\Domain\Client\Repository\ClientRepositoryInterface;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\CompanyName;
use App\Domain\Client\ValueObject\ServiceLevel;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateClientUseCaseTest extends TestCase
{
    public function testUpdateExistingClient(): void
    {
        $existing = Client::create(
            ClientId::fromLegacyInt(42),
            CompanyName::fromString('Old Name'),
            ServiceLevel::STANDARD,
        );
        $existing->pullDomainEvents();

        $repo = $this->createMock(ClientRepositoryInterface::class);
        $repo->method('findById')->willReturn($existing);
        $repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Client $client): bool {
                return $client->getName()->getValue() === 'New Name'
                    && $client->getServiceLevel() === ServiceLevel::ENTERPRISE
                    && $client->getNotes() === 'Updated notes';
            }));

        $useCase = new UpdateClientUseCase($repo);
        $useCase->execute(new UpdateClientCommand(
            clientId: 42,
            name: 'New Name',
            serviceLevel: 'vip',
            notes: 'Updated notes',
        ));
    }

    public function testServiceLevelMappingPremium(): void
    {
        $existing = Client::create(
            ClientId::fromLegacyInt(1),
            CompanyName::fromString('Acme'),
        );

        $repo = $this->createMock(ClientRepositoryInterface::class);
        $repo->method('findById')->willReturn($existing);

        $useCase = new UpdateClientUseCase($repo);
        $useCase->execute(new UpdateClientCommand(
            clientId: 1,
            name: 'Acme',
            serviceLevel: 'premium',
        ));

        $this->assertSame(ServiceLevel::PREMIUM, $existing->getServiceLevel());
    }

    public function testInvalidServiceLevelRejected(): void
    {
        $existing = Client::create(
            ClientId::fromLegacyInt(1),
            CompanyName::fromString('Acme'),
        );

        $repo = $this->createMock(ClientRepositoryInterface::class);
        $repo->method('findById')->willReturn($existing);

        $useCase = new UpdateClientUseCase($repo);

        $this->expectException(InvalidArgumentException::class);
        $useCase->execute(new UpdateClientCommand(
            clientId: 1,
            name: 'Acme',
            serviceLevel: 'invalid-level',
        ));
    }
}
