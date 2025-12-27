<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\NpsMarkExpiredCommand;
use App\Repository\NpsSurveyRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for NpsMarkExpiredCommand.
 */
class NpsMarkExpiredCommandTest extends TestCase
{
    private NpsSurveyRepository $npsSurveyRepository;
    private NpsMarkExpiredCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->npsSurveyRepository = $this->createMock(NpsSurveyRepository::class);
        $this->command             = new NpsMarkExpiredCommand($this->npsSurveyRepository);
        $this->commandTester       = new CommandTester($this->command);
    }

    public function testExecuteWithExpiredSurveys(): void
    {
        $expiredCount = 5;

        $this->npsSurveyRepository
            ->expects($this->once())
            ->method('markExpiredSurveysAsExpired')
            ->willReturn($expiredCount);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Marquage des enquêtes NPS expirées', $output);
        $this->assertStringContainsString('5 enquête(s) marquée(s) comme expirée(s)', $output);
        $this->assertStringContainsString('[OK]', $output); // SymfonyStyle success message
    }

    public function testExecuteWithNoExpiredSurveys(): void
    {
        $this->npsSurveyRepository
            ->expects($this->once())
            ->method('markExpiredSurveysAsExpired')
            ->willReturn(0);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Marquage des enquêtes NPS expirées', $output);
        $this->assertStringContainsString('Aucune enquête expirée à marquer', $output);
        $this->assertStringContainsString('[INFO]', $output); // SymfonyStyle info message
    }

    public function testExecuteReturnsSuccess(): void
    {
        $this->npsSurveyRepository
            ->method('markExpiredSurveysAsExpired')
            ->willReturn(3);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertEquals(0, $exitCode); // SUCCESS is 0
    }

    public function testExecuteCallsRepositoryMethod(): void
    {
        $this->npsSurveyRepository
            ->expects($this->once())
            ->method('markExpiredSurveysAsExpired');

        $this->commandTester->execute([]);
    }

    public function testExecuteDisplaysCorrectCountInMessage(): void
    {
        $counts = [1, 10, 100];

        foreach ($counts as $count) {
            $repository = $this->createMock(NpsSurveyRepository::class);
            $repository->method('markExpiredSurveysAsExpired')->willReturn($count);

            $command = new NpsMarkExpiredCommand($repository);
            $tester  = new CommandTester($command);

            $tester->execute([]);
            $output = $tester->getDisplay();

            $this->assertStringContainsString((string) $count, $output);
            $this->assertStringContainsString('enquête(s) marquée(s)', $output);
        }
    }
}
