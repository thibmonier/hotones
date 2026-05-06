<?php

declare(strict_types=1);

namespace App\Application\Project\UseCase\UpdateProject;

final readonly class UpdateProjectCommand
{
    public function __construct(
        public int $projectId,
        public string $name,
        public ?string $description = null,
        public ?string $reference = null,
        /** @var 'active'|'completed'|'cancelled'|null */
        public ?string $status = null,
    ) {
    }
}
