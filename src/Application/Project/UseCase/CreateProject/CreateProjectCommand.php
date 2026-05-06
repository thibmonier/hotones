<?php

declare(strict_types=1);

namespace App\Application\Project\UseCase\CreateProject;

final readonly class CreateProjectCommand
{
    public function __construct(
        public string $name,
        public ?int $clientId,
        public string $projectType = 'forfait',
        public bool $isInternal = false,
        public ?string $description = null,
    ) {
    }
}
