<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\CheckPhpLimitsCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for CheckPhpLimitsCommand.
 */
class CheckPhpLimitsCommandTest extends TestCase
{
    private CheckPhpLimitsCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->command = new CheckPhpLimitsCommand();
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteReturnsSuccess(): void
    {
        $exitCode = $this->commandTester->execute([]);

        static::assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteDisplaysTitle(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Limites PHP pour les uploads', $output);
    }

    public function testExecuteDisplaysPhpConfigurationTable(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        // Verify table headers and common config keys
        static::assertStringContainsString('Configuration', $output);
        static::assertStringContainsString('Valeur', $output);
        static::assertStringContainsString('upload_max_filesize', $output);
        static::assertStringContainsString('post_max_size', $output);
        static::assertStringContainsString('memory_limit', $output);
        static::assertStringContainsString('max_execution_time', $output);
        static::assertStringContainsString('file_uploads', $output);
    }

    public function testExecuteDisplaysVerificationSection(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('Vérifications', $output);
    }

    public function testParseSizeWithMinusOne(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '-1');

        static::assertEquals(-1, $result);
    }

    public function testParseSizeWithGigabytes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '2G');

        static::assertEquals(2 * 1024 * 1024 * 1024, $result);
    }

    public function testParseSizeWithMegabytes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '128M');

        static::assertEquals(128 * 1024 * 1024, $result);
    }

    public function testParseSizeWithKilobytes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '512K');

        static::assertEquals(512 * 1024, $result);
    }

    public function testParseSizeWithPlainBytes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '1024');

        static::assertSame(1024, $result);
    }

    public function testParseSizeWithZero(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '0');

        static::assertSame(0, $result);
    }

    public function testParseSizeWithLowercaseUnits(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('parseSize');

        // parseSize converts to uppercase internally
        $resultG = $method->invoke($this->command, '1g');
        $resultM = $method->invoke($this->command, '2m');
        $resultK = $method->invoke($this->command, '3k');

        static::assertEquals(1024 * 1024 * 1024, $resultG);
        static::assertEquals(2 * 1024 * 1024, $resultM);
        static::assertEquals(3 * 1024, $resultK);
    }

    public function testParseSizeWithVariousSizes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('parseSize');

        $testCases = [
            ['input' => '1G', 'expected' => 1_073_741_824],
            ['input' => '10M', 'expected' => 10_485_760],
            ['input' => '100K', 'expected' => 102_400],
            ['input' => '500', 'expected' => 500],
            ['input' => '2048M', 'expected' => 2_147_483_648],
        ];

        foreach ($testCases as $case) {
            $result = $method->invoke($this->command, $case['input']);
            static::assertEquals($case['expected'], $result, "Failed for input: {$case['input']}");
        }
    }

    public function testExecuteAlwaysReturnsSuccessRegardlessOfConfiguration(): void
    {
        // Command should return SUCCESS even if PHP limits are not optimal
        // (it's a diagnostic command, not a validation command that fails)
        $exitCode = $this->commandTester->execute([]);

        static::assertEquals(Command::SUCCESS, $exitCode);
        static::assertSame(0, $exitCode);
    }
}
