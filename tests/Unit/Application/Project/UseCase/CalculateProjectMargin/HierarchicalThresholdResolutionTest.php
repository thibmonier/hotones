<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\UseCase\CalculateProjectMargin;

use App\Application\Project\UseCase\CalculateProjectMargin\CalculateProjectMarginUseCase;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Sprint-023 sub-epic E US-108 — verify hierarchical threshold resolution
 * via private method `resolveThresholdPercent`.
 *
 * Sanity-check signature + visibility. Logic complète testée via tests
 * Integration end-to-end avec DB fixtures Client + Project (sprint-024+
 * si nécessaire).
 */
final class HierarchicalThresholdResolutionTest extends TestCase
{
    public function testResolveThresholdPercentMethodExists(): void
    {
        $reflection = new ReflectionClass(CalculateProjectMarginUseCase::class);

        static::assertTrue($reflection->hasMethod('resolveThresholdPercent'));
        $method = $reflection->getMethod('resolveThresholdPercent');
        static::assertTrue($method->isPrivate());
        static::assertCount(2, $method->getParameters());
    }

    public function testResolveThresholdPercentReturnType(): void
    {
        $reflection = new ReflectionClass(CalculateProjectMarginUseCase::class);
        $method = $reflection->getMethod('resolveThresholdPercent');

        $returnType = $method->getReturnType();
        static::assertNotNull($returnType);
        static::assertSame('float', (string) $returnType);
    }

    public function testResolveThresholdPercentParameterTypes(): void
    {
        $reflection = new ReflectionClass(CalculateProjectMarginUseCase::class);
        $method = $reflection->getMethod('resolveThresholdPercent');
        $params = $method->getParameters();

        static::assertSame('projectIdLegacy', $params[0]->getName());
        static::assertSame('string', (string) $params[0]->getType());

        static::assertSame('defaultPercent', $params[1]->getName());
        static::assertSame('float', (string) $params[1]->getType());
    }
}
