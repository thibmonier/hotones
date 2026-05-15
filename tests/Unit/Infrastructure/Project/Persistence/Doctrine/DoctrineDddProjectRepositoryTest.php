<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project as DddProject;
use App\Domain\Project\Exception\ProjectNotFoundException;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Project\ValueObject\ProjectType;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Project\Persistence\Doctrine\DoctrineDddProjectRepository;
use App\Infrastructure\Project\Translator\ProjectDddToFlatTranslator;
use App\Infrastructure\Project\Translator\ProjectFlatToDddTranslator;
use App\Repository\ProjectRepository as FlatProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
final class DoctrineDddProjectRepositoryTest extends TestCase
{
    public function testFindByIdReturnsTranslatedProject(): void
    {
        $flat = $this->makeFlatProject(42, 'Project Alpha');

        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('find')->willReturnCallback(static fn (mixed $id): ?FlatProject => 42 === $id ? $flat : null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);
        $ddd = $repo->findById(ProjectId::fromLegacyInt(42));

        static::assertSame(42, $ddd->getId()->toLegacyInt());
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('find')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $this->expectException(ProjectNotFoundException::class);
        $repo->findById(ProjectId::fromLegacyInt(999));
    }

    public function testFindByIdOrNullReturnsNullForUuid(): void
    {
        $repo = $this->makeRepo();
        static::assertNull($repo->findByIdOrNull(ProjectId::generate()));
    }

    public function testFindByReferenceAlwaysReturnsNullPhase2(): void
    {
        $repo = $this->makeRepo();
        static::assertNull($repo->findByReference('REF-001'));
    }

    public function testFindByClientIdLegacy(): void
    {
        $flats = [$this->makeFlatProject(1, 'P1'), $this->makeFlatProject(2, 'P2')];

        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('findBy')->willReturn($flats);

        $repo = $this->makeRepo(flatRepo: $flatRepo);
        $projects = $repo->findByClientId(ClientId::fromLegacyInt(7));

        static::assertCount(2, $projects);
    }

    public function testFindByClientIdReturnsEmptyForUuid(): void
    {
        $repo = $this->makeRepo();
        static::assertSame([], $repo->findByClientId(ClientId::generate()));
    }

    public function testFindByStatus(): void
    {
        $flat = $this->makeFlatProject(1, 'X');

        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('findBy')->willReturn([$flat]);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        static::assertCount(1, $repo->findByStatus(ProjectStatus::ACTIVE));
        static::assertCount(1, $repo->findByStatus(ProjectStatus::COMPLETED));
        static::assertCount(1, $repo->findByStatus(ProjectStatus::CANCELLED));
    }

    public function testFindByTypeAlwaysReturnsEmptyPhase2(): void
    {
        $repo = $this->makeRepo();
        static::assertSame([], $repo->findByType(ProjectType::FORFAIT));
        static::assertSame([], $repo->findByType(ProjectType::REGIE));
    }

    public function testFindActiveDelegatesToFindByStatus(): void
    {
        $flat = $this->makeFlatProject(1, 'Active');

        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('findBy')->willReturn([$flat]);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        static::assertCount(1, $repo->findActive());
    }

    public function testFindInternalReturnsProjectsWithoutClient(): void
    {
        $flat = $this->makeFlatProject(1, 'Internal');

        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('findBy')->willReturn([$flat]);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        static::assertCount(1, $repo->findInternal());
    }

    public function testFindAll(): void
    {
        $flats = [$this->makeFlatProject(1, 'A'), $this->makeFlatProject(2, 'B')];

        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('findAll')->willReturn($flats);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        static::assertCount(2, $repo->findAll());
    }

    public function testSaveExistingLegacyProject(): void
    {
        $flat = $this->makeFlatProject(7, 'Updated');

        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('find')->willReturn($flat);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($flat);
        $em->expects($this->once())->method('flush');

        $repo = $this->makeRepo(flatRepo: $flatRepo, em: $em);

        $ddd = DddProject::create(
            ProjectId::fromLegacyInt(7),
            'Updated',
            ClientId::fromLegacyInt(1),
            ProjectType::FORFAIT,
            true,
        );
        $repo->save($ddd);
    }

    public function testSavePureUuidThrows(): void
    {
        $repo = $this->makeRepo();

        $ddd = DddProject::create(ProjectId::generate(), 'X', ClientId::fromLegacyInt(1), ProjectType::FORFAIT, true);

        $this->expectException(RuntimeException::class);
        $repo->save($ddd);
    }

    public function testSaveNotFoundThrows(): void
    {
        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('find')->willReturn(null);

        $repo = $this->makeRepo(flatRepo: $flatRepo);

        $ddd = DddProject::create(
            ProjectId::fromLegacyInt(999),
            'X',
            ClientId::fromLegacyInt(1),
            ProjectType::FORFAIT,
            true,
        );

        $this->expectException(ProjectNotFoundException::class);
        $repo->save($ddd);
    }

    public function testDeleteExistingLegacyProject(): void
    {
        $flat = $this->makeFlatProject(7, 'Doomed');

        $flatRepo = $this->createMock(FlatProjectRepository::class);
        $flatRepo->method('find')->willReturn($flat);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($flat);
        $em->expects($this->once())->method('flush');

        $repo = $this->makeRepo(flatRepo: $flatRepo, em: $em);

        $ddd = DddProject::create(
            ProjectId::fromLegacyInt(7),
            'Doomed',
            ClientId::fromLegacyInt(1),
            ProjectType::FORFAIT,
            true,
        );
        $repo->delete($ddd);
    }

    public function testDeletePureUuidThrows(): void
    {
        $repo = $this->makeRepo();

        $ddd = DddProject::create(ProjectId::generate(), 'X', ClientId::fromLegacyInt(1), ProjectType::FORFAIT, true);

        $this->expectException(RuntimeException::class);
        $repo->delete($ddd);
    }

    private function makeFlatProject(int $id, string $name): FlatProject
    {
        $flat = new FlatProject();
        new ReflectionProperty(FlatProject::class, 'id')->setValue($flat, $id);
        $flat->name = $name;
        $flat->status = 'active';
        $flat->isInternal = true;
        $flat->description = null;

        return $flat;
    }

    private function makeRepo(
        ?FlatProjectRepository $flatRepo = null,
        ?EntityManagerInterface $em = null,
    ): DoctrineDddProjectRepository {
        $flatRepo ??= $this->createMock(FlatProjectRepository::class);
        $em ??= $this->createMock(EntityManagerInterface::class);

        return new DoctrineDddProjectRepository(
            $flatRepo,
            $em,
            new ProjectFlatToDddTranslator(),
            new ProjectDddToFlatTranslator(),
        );
    }
}
