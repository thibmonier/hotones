<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Project\Translator;

use App\Domain\Project\ValueObject\ProjectStatus;
use App\Entity\Client as FlatClient;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Project\Translator\ProjectFlatToDddTranslator;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

final class ProjectFlatToDddTranslatorTest extends TestCase
{
    private function makeFlatProject(
        int $id,
        string $name,
        string $status = 'active',
        ?int $clientId = null,
    ): FlatProject {
        $flat = new FlatProject();
        new ReflectionProperty(FlatProject::class, 'id')->setValue($flat, $id);
        $flat->name = $name;
        $flat->status = $status;
        $flat->description = null;

        if ($clientId !== null) {
            $client = new FlatClient();
            new ReflectionProperty(FlatClient::class, 'id')->setValue($client, $clientId);
            $flat->client = $client;
        }

        return $flat;
    }

    public function testTranslateBasicFields(): void
    {
        $translator = new ProjectFlatToDddTranslator();
        $flat = $this->makeFlatProject(42, 'Acme Project', 'active', 7);

        $ddd = $translator->translate($flat);

        static::assertSame('legacy:42', $ddd->getId()->value());
        static::assertSame('Acme Project', $ddd->getName());
        static::assertSame(ProjectStatus::ACTIVE, $ddd->getStatus());
        static::assertFalse($ddd->isInternal());
        static::assertSame('legacy:7', $ddd->getClientId()->getValue());
    }

    public function testStatusMappingCompleted(): void
    {
        $translator = new ProjectFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatProject(1, 'X1', 'completed', 1));
        static::assertSame(ProjectStatus::COMPLETED, $ddd->getStatus());
    }

    public function testStatusMappingCancelled(): void
    {
        $translator = new ProjectFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatProject(1, 'X1', 'cancelled', 1));
        static::assertSame(ProjectStatus::CANCELLED, $ddd->getStatus());
    }

    public function testInternalProjectWhenNoClient(): void
    {
        $translator = new ProjectFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatProject(1, 'Internal', 'active', null));
        static::assertTrue($ddd->isInternal());
    }

    public function testTranslateUnsavedThrows(): void
    {
        $translator = new ProjectFlatToDddTranslator();
        $flat = new FlatProject();
        $flat->name = 'Pending';

        $this->expectException(RuntimeException::class);
        $translator->translate($flat);
    }

    public function testNoDomainEventsRecorded(): void
    {
        $translator = new ProjectFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatProject(1, 'X1', 'active', 1));
        static::assertSame([], $ddd->pullDomainEvents());
    }
}
