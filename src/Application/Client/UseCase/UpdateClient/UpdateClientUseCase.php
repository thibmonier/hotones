<?php

declare(strict_types=1);

namespace App\Application\Client\UseCase\UpdateClient;

use App\Domain\Client\Repository\ClientRepositoryInterface;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\CompanyName;
use App\Domain\Client\ValueObject\ServiceLevel;
use InvalidArgumentException;

/**
 * Application Layer use case — updates an existing Client via the DDD
 * aggregate. Demonstrates the full read-modify-save cycle through the
 * Anti-Corruption Layer.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 */
final readonly class UpdateClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository,
    ) {
    }

    public function execute(UpdateClientCommand $command): void
    {
        $id = ClientId::fromLegacyInt($command->clientId);
        $client = $this->repository->findById($id);

        $client->rename(CompanyName::fromString($command->name));
        $client->updateServiceLevel($this->parseServiceLevel($command->serviceLevel));
        if ($command->notes !== null) {
            $client->addNotes($command->notes);
        }

        $this->repository->save($client);
    }

    private function parseServiceLevel(string $raw): ServiceLevel
    {
        return match (strtolower($raw)) {
            'enterprise', 'vip', 'priority' => ServiceLevel::ENTERPRISE,
            'premium', 'standard' => ServiceLevel::PREMIUM,
            'low' => ServiceLevel::STANDARD,
            default => throw new InvalidArgumentException(sprintf('Unknown service level: %s', $raw)),
        };
    }
}
