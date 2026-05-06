<?php

declare(strict_types=1);

namespace App\Domain\Client\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;

final readonly class ClientCreatedEvent implements DomainEventInterface
{
    public function __construct(
        private ClientId $clientId,
        private string $companyName,
        private DateTimeImmutable $occurredOn,
    ) {
    }

    public static function create(ClientId $clientId, string $companyName): self
    {
        return new self($clientId, $companyName, new DateTimeImmutable());
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
