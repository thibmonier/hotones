<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\UseCase\UpdateProject;

use App\Application\Project\UseCase\UpdateProject\UpdateProjectCommand;
use App\Application\Project\UseCase\UpdateProject\UpdateProjectUseCase;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Project\ValueObject\ProjectType;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateProjectUseCaseTest extends TestCase
{
    public function testUpdateExistingProject(): void
    {
        $existing = Project::create(
            ProjectId::fromLegacyInt(42),
            'Old Name',
            ClientId::fromLegacyInt(7),
            ProjectType::FORFAIT,
        );
        $existing->pullDomainEvents();

        $repo = $this->createMock(ProjectRepositoryInterface::class);
        $repo->method('findById')->willReturn($existing);
        $repo
            ->expects($this->once())
            ->method('save')
            ->with(static::callback(
                static fn (Project $p): bool => $p->getName() === 'New Name' && $p->getDescription() === 'New description',
            ));

        $useCase = new UpdateProjectUseCase($repo);
        $useCase->execute(new UpdateProjectCommand(projectId: 42, name: 'New Name', description: 'New description'));
    }

    public function testStatusTransition(): void
    {
        $existing = Project::create(ProjectId::fromLegacyInt(1), 'X', ClientId::fromLegacyInt(1), ProjectType::FORFAIT);

        $repo = $this->createMock(ProjectRepositoryInterface::class);
        $repo->method('findById')->willReturn($existing);

        $useCase = new UpdateProjectUseCase($repo);
        $useCase->execute(new UpdateProjectCommand(projectId: 1, name: 'X', status: 'active'));

        static::assertSame(ProjectStatus::ACTIVE, $existing->getStatus());
    }

    public function testInvalidStatusRejected(): void
    {
        $existing = Project::create(ProjectId::fromLegacyInt(1), 'X', ClientId::fromLegacyInt(1), ProjectType::FORFAIT);

        $repo = $this->createMock(ProjectRepositoryInterface::class);
        $repo->method('findById')->willReturn($existing);

        $useCase = new UpdateProjectUseCase($repo);

        $this->expectException(InvalidArgumentException::class);
        $useCase->execute(new UpdateProjectCommand(projectId: 1, name: 'X', status: 'unknown-status'));
    }
}
