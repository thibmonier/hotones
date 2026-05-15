<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\CheckAlertsCommand;
use App\Service\AlertDetectionService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for CheckAlertsCommand.
 */
#[AllowMockObjectsWithoutExpectations]
class CheckAlertsCommandTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $alertDetectionService;
    private CheckAlertsCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->alertDetectionService = $this->createMock(AlertDetectionService::class);
        $this->command = new CheckAlertsCommand($this->alertDetectionService);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithNoAlerts(): void
    {
        $stats = [
            'budget_alerts' => 0,
            'margin_alerts' => 0,
            'overload_alerts' => 0,
            'payment_alerts' => 0,
        ];

        $this->alertDetectionService->expects($this->once())->method('checkAllAlerts')->willReturn($stats);

        $exitCode = $this->commandTester->execute([]);

        static::assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Vérification des alertes', $output);
        static::assertStringContainsString('Aucune alerte détectée', $output);
        static::assertStringNotContainsString('Les notifications ont été créées', $output);
    }

    public function testExecuteWithSingleAlert(): void
    {
        $stats = [
            'budget_alerts' => 1,
            'margin_alerts' => 0,
            'overload_alerts' => 0,
            'payment_alerts' => 0,
        ];

        $this->alertDetectionService->method('checkAllAlerts')->willReturn($stats);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        // Singular form
        static::assertStringContainsString('1 alerte détectée et dispatchée', $output);
        static::assertStringContainsString('Les notifications ont été créées', $output);
    }

    public function testExecuteWithMultipleAlerts(): void
    {
        $stats = [
            'budget_alerts' => 5,
            'margin_alerts' => 3,
            'overload_alerts' => 2,
            'payment_alerts' => 4,
        ];

        $this->alertDetectionService->method('checkAllAlerts')->willReturn($stats);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        // Plural form - total is 14
        static::assertStringContainsString('14 alertes détectées et dispatchées', $output);
        static::assertStringContainsString('Les notifications ont été créées', $output);
    }

    public function testExecuteDisplaysTableWithAllAlertTypes(): void
    {
        $stats = [
            'budget_alerts' => 2,
            'margin_alerts' => 1,
            'overload_alerts' => 3,
            'payment_alerts' => 0,
        ];

        $this->alertDetectionService->method('checkAllAlerts')->willReturn($stats);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        // Verify table headers and values
        static::assertStringContainsString("Type d'alerte", $output);
        static::assertStringContainsString('Nombre', $output);
        static::assertStringContainsString('Budget dépassé', $output);
        static::assertStringContainsString('Marge faible', $output);
        static::assertStringContainsString('Surcharge contributeur', $output);
        static::assertStringContainsString('Paiement proche', $output);
        static::assertStringContainsString('TOTAL', $output);

        // Verify counts appear in output
        static::assertStringContainsString('2', $output); // budget_alerts
        static::assertStringContainsString('1', $output); // margin_alerts
        static::assertStringContainsString('3', $output); // overload_alerts
        static::assertStringContainsString('6', $output); // total
    }

    public function testExecuteCalculatesTotalCorrectly(): void
    {
        $testCases = [
            [
                'stats' => ['budget_alerts' => 0, 'margin_alerts' => 0, 'overload_alerts' => 0, 'payment_alerts' => 0],
                'total' => 0,
            ],
            [
                'stats' => ['budget_alerts' => 1, 'margin_alerts' => 2, 'overload_alerts' => 3, 'payment_alerts' => 4],
                'total' => 10,
            ],
            [
                'stats' => [
                    'budget_alerts' => 10,
                    'margin_alerts' => 5,
                    'overload_alerts' => 0,
                    'payment_alerts' => 15,
                ],
                'total' => 30,
            ],
        ];

        foreach ($testCases as $testCase) {
            $service = $this->createStub(AlertDetectionService::class);
            $service->method('checkAllAlerts')->willReturn($testCase['stats']);

            $command = new CheckAlertsCommand($service);
            $tester = new CommandTester($command);

            $tester->execute([]);
            $output = $tester->getDisplay();

            static::assertStringContainsString((string) $testCase['total'], $output);
        }
    }

    public function testExecuteReturnsSuccess(): void
    {
        $this->alertDetectionService
            ->method('checkAllAlerts')
            ->willReturn([
                'budget_alerts' => 0,
                'margin_alerts' => 0,
                'overload_alerts' => 0,
                'payment_alerts' => 0,
            ]);

        $exitCode = $this->commandTester->execute([]);

        static::assertEquals(Command::SUCCESS, $exitCode);
        static::assertSame(0, $exitCode);
    }

    public function testExecuteCallsAlertDetectionService(): void
    {
        $this->alertDetectionService
            ->expects($this->once())
            ->method('checkAllAlerts')
            ->willReturn([
                'budget_alerts' => 0,
                'margin_alerts' => 0,
                'overload_alerts' => 0,
                'payment_alerts' => 0,
            ]);

        $this->commandTester->execute([]);
    }

    public function testExecuteHandlesAllStatKeys(): void
    {
        $stats = [
            'budget_alerts' => 1,
            'margin_alerts' => 2,
            'overload_alerts' => 3,
            'payment_alerts' => 4,
        ];

        $this->alertDetectionService->method('checkAllAlerts')->willReturn($stats);

        // Should not throw any errors accessing array keys
        $exitCode = $this->commandTester->execute([]);

        static::assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteUsesCorrectPluralFormsForTwoAlerts(): void
    {
        $stats = [
            'budget_alerts' => 2,
            'margin_alerts' => 0,
            'overload_alerts' => 0,
            'payment_alerts' => 0,
        ];

        $this->alertDetectionService->method('checkAllAlerts')->willReturn($stats);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        // Plural form for 2 alerts
        static::assertStringContainsString('2 alertes détectées et dispatchées', $output);
    }
}
