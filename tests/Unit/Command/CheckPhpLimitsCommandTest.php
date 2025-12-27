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
        $this->command       = new CheckPhpLimitsCommand();
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteReturnsSuccess(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteDisplaysTitle(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Limites PHP pour les uploads', $output);
    }

    public function testExecuteDisplaysPhpConfigurationTable(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        // Verify table headers and common config keys
        $this->assertStringContainsString('Configuration', $output);
        $this->assertStringContainsString('Valeur', $output);
        $this->assertStringContainsString('upload_max_filesize', $output);
        $this->assertStringContainsString('post_max_size', $output);
        $this->assertStringContainsString('memory_limit', $output);
        $this->assertStringContainsString('max_execution_time', $output);
        $this->assertStringContainsString('file_uploads', $output);
    }

    public function testExecuteDisplaysVerificationSection(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Vérifications', $output);
    }

    public function testParseSizeWithMinusOne(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '-1');

        $this->assertEquals(-1, $result);
    }

    public function testParseSizeWithGigabytes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '2G');

        $this->assertEquals(2 * 1024 * 1024 * 1024, $result);
    }

    public function testParseSizeWithMegabytes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '128M');

        $this->assertEquals(128 * 1024 * 1024, $result);
    }

    public function testParseSizeWithKilobytes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '512K');

        $this->assertEquals(512 * 1024, $result);
    }

    public function testParseSizeWithPlainBytes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '1024');

        $this->assertEquals(1024, $result);
    }

    public function testParseSizeWithZero(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('parseSize');

        $result = $method->invoke($this->command, '0');

        $this->assertEquals(0, $result);
    }

    public function testParseSizeWithLowercaseUnits(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('parseSize');

        // parseSize converts to uppercase internally
        $resultG = $method->invoke($this->command, '1g');
        $resultM = $method->invoke($this->command, '2m');
        $resultK = $method->invoke($this->command, '3k');

        $this->assertEquals(1 * 1024 * 1024 * 1024, $resultG);
        $this->assertEquals(2 * 1024 * 1024, $resultM);
        $this->assertEquals(3 * 1024, $resultK);
    }

    public function testParseSizeWithVariousSizes(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('parseSize');

        $testCases = [
            ['input' => '1G', 'expected' => 1073741824],
            ['input' => '10M', 'expected' => 10485760],
            ['input' => '100K', 'expected' => 102400],
            ['input' => '500', 'expected' => 500],
            ['input' => '2048M', 'expected' => 2147483648],
        ];

        foreach ($testCases as $case) {
            $result = $method->invoke($this->command, $case['input']);
            $this->assertEquals(
                $case['expected'],
                $result,
                "Failed for input: {$case['input']}",
            );
        }
    }

    public function testExecuteAlwaysReturnsSuccessRegardlessOfConfiguration(): void
    {
        // Command should return SUCCESS even if PHP limits are not optimal
        // (it's a diagnostic command, not a validation command that fails)
        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertEquals(0, $exitCode);
    }
}
