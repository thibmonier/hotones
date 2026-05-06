<?php

declare(strict_types=1);

namespace App\Application\Client\UseCase\CreateClient;

/**
 * Application Layer command — input DTO for CreateClientUseCase.
 *
 * Immutable. Constructed by controllers / API processors / CLI commands
 * and passed to the use case via dependency injection.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 */
final readonly class CreateClientCommand
{
    public function __construct(
        public string $name,
        public string $serviceLevel = 'standard',
        public ?string $notes = null,
    ) {
    }
}
