<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\DispatchMetricsRecalculationCommand;
use App\Message\RecalculateMetricsMessage;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Unit tests for DispatchMetricsRecalculationCommand.
 */
#[AllowMockObjectsWithoutExpectations]
class DispatchMetricsRecalculationCommandTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $messageBus;
    private DispatchMetricsRecalculationCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->command = new DispatchMetricsRecalculationCommand($this->messageBus);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithYearDispatchesAllMessages(): void
    {
        $year = 2024;

        // Expect 17 dispatches: 12 monthly + 4 quarterly + 1 yearly
        $this->messageBus->expects($this->exactly(17))->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $exitCode = $this->commandTester->execute([
            '--year' => (string) $year,
        ]);

        static::assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Dispatched metrics recalculation for year 2024', $output);
    }

    public function testExecuteWithYearDispatchesMonthlyMessages(): void
    {
        $year = 2023;
        $dispatchedDates = [];

        $this->messageBus
            ->method('dispatch')
            ->willReturnCallback(static function ($message) use (&$dispatchedDates) {
                if ($message instanceof RecalculateMetricsMessage) {
                    $dispatchedDates[] = [
                        'date' => $message->date,
                        'granularity' => $message->granularity,
                    ];
                }

                return new Envelope($message);
            });

        $this->commandTester->execute(['--year' => (string) $year]);

        // Verify 12 monthly messages
        $monthlyMessages = array_filter($dispatchedDates, static fn ($d): bool => $d['granularity'] === 'monthly');
        static::assertCount(12, $monthlyMessages);

        // Verify dates are correct
        $expectedMonthlyDates = [];
        for ($m = 1; $m <= 12; ++$m) {
            $expectedMonthlyDates[] = sprintf('2023-%02d-01', $m);
        }

        $actualMonthlyDates = array_column(array_values($monthlyMessages), 'date');
        sort($actualMonthlyDates);
        sort($expectedMonthlyDates);

        static::assertEquals($expectedMonthlyDates, $actualMonthlyDates);
    }

    public function testExecuteWithYearDispatchesQuarterlyMessages(): void
    {
        $year = 2023;
        $dispatchedDates = [];

        $this->messageBus
            ->method('dispatch')
            ->willReturnCallback(static function ($message) use (&$dispatchedDates) {
                if ($message instanceof RecalculateMetricsMessage) {
                    $dispatchedDates[] = [
                        'date' => $message->date,
                        'granularity' => $message->granularity,
                    ];
                }

                return new Envelope($message);
            });

        $this->commandTester->execute(['--year' => (string) $year]);

        // Verify 4 quarterly messages
        $quarterlyMessages = array_filter($dispatchedDates, static fn ($d): bool => $d['granularity'] === 'quarterly');
        static::assertCount(4, $quarterlyMessages);

        // Verify dates: Q1=01-01, Q2=04-01, Q3=07-01, Q4=10-01
        $expectedQuarterlyDates = ['2023-01-01', '2023-04-01', '2023-07-01', '2023-10-01'];
        $actualQuarterlyDates = array_column(array_values($quarterlyMessages), 'date');
        sort($actualQuarterlyDates);

        static::assertEquals($expectedQuarterlyDates, $actualQuarterlyDates);
    }

    public function testExecuteWithYearDispatchesYearlyMessage(): void
    {
        $year = 2023;
        $dispatchedDates = [];

        $this->messageBus
            ->method('dispatch')
            ->willReturnCallback(static function ($message) use (&$dispatchedDates) {
                if ($message instanceof RecalculateMetricsMessage) {
                    $dispatchedDates[] = [
                        'date' => $message->date,
                        'granularity' => $message->granularity,
                    ];
                }

                return new Envelope($message);
            });

        $this->commandTester->execute(['--year' => (string) $year]);

        // Verify 1 yearly message
        $yearlyMessages = array_filter($dispatchedDates, static fn ($d): bool => $d['granularity'] === 'yearly');
        static::assertCount(1, $yearlyMessages);
        static::assertSame('2023-01-01', array_first($yearlyMessages)['date']);
    }

    public function testExecuteWithDateDefaultGranularity(): void
    {
        $date = '2024-06-15';

        $capturedMessage = null;
        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(static function ($message) use (&$capturedMessage) {
                $capturedMessage = $message;

                return new Envelope($message);
            });

        $exitCode = $this->commandTester->execute([
            '--date' => $date,
        ]);

        static::assertEquals(Command::SUCCESS, $exitCode);
        static::assertInstanceOf(RecalculateMetricsMessage::class, $capturedMessage);
        static::assertEquals($date, $capturedMessage->date);
        static::assertSame('monthly', $capturedMessage->granularity);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Dispatched monthly metrics for 2024-06-15', $output);
    }

    public function testExecuteWithDateAndCustomGranularity(): void
    {
        $date = '2024-01-01';
        $granularity = 'quarterly';

        $capturedMessage = null;
        $this->messageBus
            ->method('dispatch')
            ->willReturnCallback(static function ($message) use (&$capturedMessage) {
                $capturedMessage = $message;

                return new Envelope($message);
            });

        $exitCode = $this->commandTester->execute([
            '--date' => $date,
            '--granularity' => $granularity,
        ]);

        static::assertEquals(Command::SUCCESS, $exitCode);
        static::assertInstanceOf(RecalculateMetricsMessage::class, $capturedMessage);
        static::assertEquals($date, $capturedMessage->date);
        static::assertEquals($granularity, $capturedMessage->granularity);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Dispatched quarterly metrics for 2024-01-01', $output);
    }

    public function testExecuteWithoutOptionsReturnsInvalid(): void
    {
        $this->messageBus->expects($this->never())->method('dispatch');

        $exitCode = $this->commandTester->execute([]);

        static::assertEquals(Command::INVALID, $exitCode);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Provide either --year or --date [--granularity]', $output);
    }

    public function testExecuteWithInvalidDateThrowsException(): void
    {
        $this->expectException(Exception::class);

        $this->commandTester->execute([
            '--date' => 'invalid-date',
        ]);
    }

    public function testConfigureDefinesAllOptions(): void
    {
        $definition = $this->command->getDefinition();

        static::assertTrue($definition->hasOption('year'));
        static::assertTrue($definition->hasOption('date'));
        static::assertTrue($definition->hasOption('granularity'));

        // Verify they are all optional (not required by Symfony, but VALUE_REQUIRED means value is needed if option is used)
        static::assertFalse(
            $definition->getOption('year')->isValueRequired() && $definition->getOption('year')->isValueOptional(),
        );
        static::assertFalse(
            $definition->getOption('date')->isValueRequired() && $definition->getOption('date')->isValueOptional(),
        );
    }

    public function testExecuteReturnsSuccessForValidInputs(): void
    {
        $this->messageBus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        // Test with year
        $exitCode = $this->commandTester->execute(['--year' => '2024']);
        static::assertEquals(Command::SUCCESS, $exitCode);

        // Test with date
        $exitCode = $this->commandTester->execute(['--date' => '2024-01-01']);
        static::assertEquals(Command::SUCCESS, $exitCode);
    }
}
