<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\RecalculateClientServiceLevelCommand;
use App\Service\ClientServiceLevelCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for RecalculateClientServiceLevelCommand.
 */
class RecalculateClientServiceLevelCommandTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $calculator;
    private RecalculateClientServiceLevelCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->calculator    = $this->createMock(ClientServiceLevelCalculator::class);
        $this->command       = new RecalculateClientServiceLevelCommand($this->calculator);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithDefaultYear(): void
    {
        $currentYear = (int) date('Y');

        $this->calculator
            ->method('getConfiguration')
            ->willReturn([
                'top_vip_rank'      => 5,
                'top_priority_rank' => 20,
                'low_threshold'     => 5000,
            ]);

        $this->calculator
            ->expects($this->once())
            ->method('recalculateAllAutoClients')
            ->with($currentYear)
            ->willReturn(15);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Recalcul des niveaux de service clients', $output);
        $this->assertStringContainsString("Année de référence : {$currentYear}", $output);
        $this->assertStringContainsString('15 client(s) en mode auto ont été mis à jour', $output);
    }

    public function testExecuteWithCustomYear(): void
    {
        $customYear = 2023;

        $this->calculator
            ->method('getConfiguration')
            ->willReturn([
                'top_vip_rank'      => 5,
                'top_priority_rank' => 20,
                'low_threshold'     => 5000,
            ]);

        $this->calculator
            ->expects($this->once())
            ->method('recalculateAllAutoClients')
            ->with($customYear)
            ->willReturn(10);

        $exitCode = $this->commandTester->execute([
            '--year' => (string) $customYear,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Année de référence : {$customYear}", $output);
    }

    public function testExecuteDisplaysConfiguration(): void
    {
        $config = [
            'top_vip_rank'      => 3,
            'top_priority_rank' => 15,
            'low_threshold'     => 10000,
        ];

        $this->calculator
            ->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($config);

        $this->calculator->method('recalculateAllAutoClients')->willReturn(0);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Configuration', $output);
        $this->assertStringContainsString('Top 3 clients → VIP', $output);
        $this->assertStringContainsString('Top 15 clients → Prioritaire', $output);
        $this->assertStringContainsString('CA < 10000€ → Basse priorité', $output);
        $this->assertStringContainsString('Autres → Standard', $output);
    }

    public function testExecuteDisplaysCorrectCount(): void
    {
        $this->calculator
            ->method('getConfiguration')
            ->willReturn([
                'top_vip_rank'      => 5,
                'top_priority_rank' => 20,
                'low_threshold'     => 5000,
            ]);

        $counts = [0, 1, 25, 100];

        foreach ($counts as $count) {
            $calculator = $this->createMock(ClientServiceLevelCalculator::class);
            $calculator
                ->method('getConfiguration')
                ->willReturn([
                    'top_vip_rank'      => 5,
                    'top_priority_rank' => 20,
                    'low_threshold'     => 5000,
                ]);
            $calculator->method('recalculateAllAutoClients')->willReturn($count);

            $command = new RecalculateClientServiceLevelCommand($calculator);
            $tester  = new CommandTester($command);

            $tester->execute([]);
            $output = $tester->getDisplay();

            $this->assertStringContainsString("{$count} client(s) en mode auto ont été mis à jour", $output);
        }
    }

    public function testExecuteReturnsSuccess(): void
    {
        $this->calculator
            ->method('getConfiguration')
            ->willReturn([
                'top_vip_rank'      => 5,
                'top_priority_rank' => 20,
                'low_threshold'     => 5000,
            ]);

        $this->calculator->method('recalculateAllAutoClients')->willReturn(5);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteCallsCalculatorMethods(): void
    {
        $this->calculator
            ->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([
                'top_vip_rank'      => 5,
                'top_priority_rank' => 20,
                'low_threshold'     => 5000,
            ]);

        $this->calculator
            ->expects($this->once())
            ->method('recalculateAllAutoClients')
            ->willReturn(0);

        $this->commandTester->execute([]);
    }

    public function testConfigureDefinesYearOption(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('year'));
        $this->assertTrue($definition->hasShortcut('y'));

        $option = $definition->getOption('year');
        $this->assertFalse($option->isValueRequired());
        $this->assertEquals(date('Y'), $option->getDefault());
    }

    public function testExecuteWithYearShortOption(): void
    {
        $customYear = 2022;

        $this->calculator
            ->method('getConfiguration')
            ->willReturn([
                'top_vip_rank'      => 5,
                'top_priority_rank' => 20,
                'low_threshold'     => 5000,
            ]);

        $this->calculator
            ->expects($this->once())
            ->method('recalculateAllAutoClients')
            ->with($customYear)
            ->willReturn(5);

        $this->commandTester->execute([
            '-y' => (string) $customYear,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString("Année de référence : {$customYear}", $output);
    }
}
