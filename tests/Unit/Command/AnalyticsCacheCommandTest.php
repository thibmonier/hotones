<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\AnalyticsCacheCommand;
use App\Service\AnalyticsCacheService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for AnalyticsCacheCommand.
 */
class AnalyticsCacheCommandTest extends TestCase
{
    private AnalyticsCacheCommand $command;
    private CommandTester $commandTester;

    /**
     * Create a fresh command bound to a new mock and store both for the test
     * to set expectations on. Called per-test (instead of setUp) so that tests
     * that don't need the mock can skip its creation entirely (PHPUnit 13
     * notice-free).
     */
    private function buildCommand(): MockObject
    {
        /** @var AnalyticsCacheService&MockObject $cacheService */
        $cacheService = $this->createMock(AnalyticsCacheService::class);
        $this->command = new AnalyticsCacheCommand($cacheService);
        $this->commandTester = new CommandTester($this->command);

        return $cacheService;
    }

    public function testExecuteWithClearOption(): void
    {
        $cacheService = $this->buildCommand();
        $cacheService->expects($this->once())->method('invalidateAll');

        $exitCode = $this->commandTester->execute([
            '--clear' => true,
        ]);

        static::assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Invalidation du cache analytics', $output);
        static::assertStringContainsString('Cache analytics vidé avec succès', $output);
    }

    public function testExecuteWithWarmupOption(): void
    {
        $cacheService = $this->buildCommand();
        $cacheService->expects($this->once())->method('warmup')->with($this->isArray());

        $exitCode = $this->commandTester->execute([
            '--warmup' => true,
        ]);

        static::assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Préchauffage du cache analytics', $output);
        static::assertStringContainsString('métriques précalculées', $output);
    }

    public function testExecuteWithBothOptions(): void
    {
        $cacheService = $this->buildCommand();
        $cacheService->expects($this->once())->method('invalidateAll');
        $cacheService->expects($this->once())->method('warmup');

        $exitCode = $this->commandTester->execute([
            '--clear' => true,
            '--warmup' => true,
        ]);

        static::assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Cache analytics vidé avec succès', $output);
        static::assertStringContainsString('métriques précalculées', $output);
    }

    public function testExecuteWithNoOptionsShowsUsage(): void
    {
        $cacheService = $this->buildCommand();
        $cacheService->expects($this->never())->method('invalidateAll');
        $cacheService->expects($this->never())->method('warmup');

        $exitCode = $this->commandTester->execute([]);

        static::assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString(
            'Utilisation: php bin/console app:analytics:cache [--clear] [--warmup]',
            $output,
        );
    }

    public function testExecuteReturnsSuccess(): void
    {
        // Pure-stub test: no expectation, just verify exit code.
        $cacheService = $this->createStub(AnalyticsCacheService::class);
        $this->command = new AnalyticsCacheCommand($cacheService);
        $this->commandTester = new CommandTester($this->command);

        $exitCode = $this->commandTester->execute(['--clear' => true]);

        static::assertEquals(Command::SUCCESS, $exitCode);
        static::assertSame(0, $exitCode);
    }

    public function testConfigureDefinesOptions(): void
    {
        // Configuration test: no service interaction, use a stub.
        $cacheService = $this->createStub(AnalyticsCacheService::class);
        $this->command = new AnalyticsCacheCommand($cacheService);

        $definition = $this->command->getDefinition();

        static::assertTrue($definition->hasOption('clear'));
        static::assertTrue($definition->hasOption('warmup'));

        // Verify shortcuts
        static::assertTrue($definition->hasShortcut('c'));
        static::assertTrue($definition->hasShortcut('w'));

        // Verify they are flags (no value required)
        static::assertFalse($definition->getOption('clear')->acceptValue());
        static::assertFalse($definition->getOption('warmup')->acceptValue());
    }

    public function testExecuteWithClearShortOption(): void
    {
        $cacheService = $this->buildCommand();
        $cacheService->expects($this->once())->method('invalidateAll');

        $this->commandTester->execute(['-c' => true]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Cache analytics vidé avec succès', $output);
    }

    public function testExecuteWithWarmupShortOption(): void
    {
        $cacheService = $this->buildCommand();
        $cacheService->expects($this->once())->method('warmup');

        $this->commandTester->execute(['-w' => true]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('métriques précalculées', $output);
    }

    public function testExecutePassesCorrectMetricsArrayToWarmup(): void
    {
        $capturedMetrics = null;

        $cacheService = $this->buildCommand();
        $cacheService
            ->expects($this->once())
            ->method('warmup')
            ->willReturnCallback(static function ($metrics) use (&$capturedMetrics): void {
                $capturedMetrics = $metrics;
            });

        $this->commandTester->execute(['--warmup' => true]);

        static::assertIsArray($capturedMetrics);
        // Current implementation has empty array
        static::assertCount(0, $capturedMetrics);
    }
}
