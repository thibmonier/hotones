<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Project\Translator;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project as DddProject;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Project\ValueObject\ProjectType;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Project\Translator\ProjectDddToFlatTranslator;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ProjectDddToFlatTranslatorTest extends TestCase
{
    private function makeDdd(?ProjectStatus $status = null): DddProject
    {
        $project = DddProject::create(
            ProjectId::fromLegacyInt(42),
            'Acme Project',
            ClientId::fromLegacyInt(7),
            ProjectType::FORFAIT,
        );
        if ($status !== null) {
            // Manual status set without state machine (test only)
            (new ReflectionProperty(DddProject::class, 'status'))->setValue($project, $status);
        }

        return $project;
    }

    public function testApplyToBasicFields(): void
    {
        $translator = new ProjectDddToFlatTranslator();
        $ddd = $this->makeDdd();
        $flat = new FlatProject();

        $translator->applyTo($ddd, $flat, null);

        $this->assertSame('Acme Project', $flat->name);
        $this->assertSame('active', $flat->status); // DRAFT collapses to active
    }

    public function testStatusMappingCompleted(): void
    {
        $translator = new ProjectDddToFlatTranslator();
        $ddd = $this->makeDdd(ProjectStatus::COMPLETED);
        $flat = new FlatProject();

        $translator->applyTo($ddd, $flat, null);

        $this->assertSame('completed', $flat->status);
    }

    public function testStatusMappingCancelled(): void
    {
        $translator = new ProjectDddToFlatTranslator();
        $ddd = $this->makeDdd(ProjectStatus::CANCELLED);
        $flat = new FlatProject();

        $translator->applyTo($ddd, $flat, null);

        $this->assertSame('cancelled', $flat->status);
    }

    public function testStatusOnHoldCollapsesToActive(): void
    {
        $translator = new ProjectDddToFlatTranslator();
        $ddd = $this->makeDdd(ProjectStatus::ON_HOLD);
        $flat = new FlatProject();

        $translator->applyTo($ddd, $flat, null);

        $this->assertSame('active', $flat->status);
    }
}
