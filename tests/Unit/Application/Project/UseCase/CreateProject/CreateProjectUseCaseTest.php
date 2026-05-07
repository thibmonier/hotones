<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\UseCase\CreateProject;

use App\Application\Project\UseCase\CreateProject\CreateProjectCommand;
use App\Application\Project\UseCase\CreateProject\CreateProjectUseCase;
use App\Entity\Client as FlatClient;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Project\Translator\ProjectDddToFlatTranslator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
final class CreateProjectUseCaseTest extends TestCase
{
    public function testCreatePersistsAndReturnsLegacyId(): void
    {
        $useCase = $this->makeUseCase(persistedId: 99);

        $id = $useCase->execute(new CreateProjectCommand(
            name: 'New Project',
            clientId: null,
            isInternal: true,
        ));

        $this->assertTrue($id->isLegacy());
        $this->assertSame(99, $id->toLegacyInt());
    }

    public function testProjectTypeForfait(): void
    {
        $persistedFlat = null;
        $useCase = $this->makeUseCase(persistedId: 1, persistCapture: function (FlatProject $flat) use (&$persistedFlat) {
            $persistedFlat = clone $flat;
        });

        $useCase->execute(new CreateProjectCommand(
            name: 'P1',
            clientId: null,
            projectType: 'forfait',
            isInternal: true,
        ));

        $this->assertNotNull($persistedFlat);
        $this->assertSame('P1', $persistedFlat->name);
    }

    public function testProjectTypeRegie(): void
    {
        $useCase = $this->makeUseCase(persistedId: 1);

        $id = $useCase->execute(new CreateProjectCommand(
            name: 'P2',
            clientId: null,
            projectType: 'regie',
            isInternal: true,
        ));

        $this->assertSame(1, $id->toLegacyInt());
    }

    public function testProjectTypeAlternativeSpellingsAccepted(): void
    {
        $useCase = $this->makeUseCase(persistedId: 1);

        // Anglais aussi accepté
        $useCase->execute(new CreateProjectCommand(
            name: 'P3',
            clientId: null,
            projectType: 'fixed_price',
            isInternal: true,
        ));
        $useCase->execute(new CreateProjectCommand(
            name: 'P4',
            clientId: null,
            projectType: 'time_and_material',
            isInternal: true,
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testInvalidProjectTypeRejected(): void
    {
        $useCase = $this->makeUseCase(persistedId: 1);

        $this->expectException(InvalidArgumentException::class);
        $useCase->execute(new CreateProjectCommand(
            name: 'X',
            clientId: null,
            projectType: 'invalid',
            isInternal: true,
        ));
    }

    public function testDescriptionApplied(): void
    {
        $persistedFlat = null;
        $useCase = $this->makeUseCase(persistedId: 1, persistCapture: function (FlatProject $flat) use (&$persistedFlat) {
            $persistedFlat = clone $flat;
        });

        $useCase->execute(new CreateProjectCommand(
            name: 'Project',
            clientId: null,
            isInternal: true,
            description: 'Some details',
        ));

        $this->assertSame('Some details', $persistedFlat->description);
    }

    public function testExternalProjectResolvesClient(): void
    {
        $client = new FlatClient();
        (new ReflectionProperty(FlatClient::class, 'id'))->setValue($client, 42);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturnCallback(
            static fn (string $class, mixed $id): ?FlatClient => FlatClient::class === $class && 42 === $id ? $client : null,
        );
        $em->method('persist')->willReturnCallback(function (FlatProject $flat): void {
            (new ReflectionProperty(FlatProject::class, 'id'))->setValue($flat, 7);
        });
        $em->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $useCase = new CreateProjectUseCase($em, $this->makeCompanyContext(), new ProjectDddToFlatTranslator(), $bus);

        $id = $useCase->execute(new CreateProjectCommand(
            name: 'External',
            clientId: 42,
            isInternal: false,
        ));

        $this->assertSame(7, $id->toLegacyInt());
    }

    /**
     * @param callable(FlatProject): void|null $persistCapture
     */
    private function makeUseCase(int $persistedId, ?callable $persistCapture = null): CreateProjectUseCase
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (FlatProject $flat) use ($persistedId, $persistCapture): void {
            (new ReflectionProperty(FlatProject::class, 'id'))->setValue($flat, $persistedId);
            if ($persistCapture !== null) {
                $persistCapture($flat);
            }
        });
        $em->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        return new CreateProjectUseCase($em, $this->makeCompanyContext(), new ProjectDddToFlatTranslator(), $bus);
    }

    private function makeCompanyContext(): \App\Security\CompanyContext
    {
        $ctx = $this->createMock(\App\Security\CompanyContext::class);
        $ctx->method('getCurrentCompany')->willReturn(new \App\Entity\Company());

        return $ctx;
    }
}
