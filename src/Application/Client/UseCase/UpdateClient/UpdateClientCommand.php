<?php

declare(strict_types=1);

namespace App\Application\Client\UseCase\UpdateClient;

final readonly class UpdateClientCommand
{
    public function __construct(
        public int $clientId,
        public string $name,
        public string $serviceLevel = 'standard',
        public ?string $notes = null,
    ) {
    }
}
