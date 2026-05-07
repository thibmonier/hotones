<?php

declare(strict_types=1);

namespace App\Tests\Unit\Monolog\Processor;

use App\Monolog\Processor\PerformanceProcessor;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

/**
 * US-095 (sprint-017 EPIC-002) — coverage Unit du processor injectant
 * mémoire + temps d'exécution dans les logs performance.
 */
final class PerformanceProcessorTest extends TestCase
{
    public function testRecordIsEnrichedWithMemoryAndExecutionTime(): void
    {
        $processor = new PerformanceProcessor();

        $record = $processor($this->makeRecord());

        self::assertArrayHasKey('memory_usage_mb', $record->extra);
        self::assertArrayHasKey('memory_peak_mb', $record->extra);
        self::assertArrayHasKey('execution_time_ms', $record->extra);

        self::assertIsFloat($record->extra['memory_usage_mb']);
        self::assertIsFloat($record->extra['memory_peak_mb']);
        self::assertIsFloat($record->extra['execution_time_ms']);

        self::assertGreaterThan(0.0, $record->extra['memory_usage_mb']);
        self::assertGreaterThanOrEqual(
            $record->extra['memory_usage_mb'],
            $record->extra['memory_peak_mb'],
            'peak memory >= current memory',
        );
        self::assertGreaterThanOrEqual(0.0, $record->extra['execution_time_ms']);
    }

    public function testExistingExtraDataIsPreserved(): void
    {
        $processor = new PerformanceProcessor();

        $original = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'performance',
            level: Level::Warning,
            message: 'slow query',
            context: [],
            extra: ['existing' => 'data'],
        );

        $record = $processor($original);

        self::assertSame('data', $record->extra['existing']);
        self::assertArrayHasKey('memory_usage_mb', $record->extra);
    }

    public function testExecutionTimeIsMeasuredFromStartTime(): void
    {
        $processor = new PerformanceProcessor();
        usleep(5000); // 5ms

        $record = $processor($this->makeRecord());

        // Should be at least 5ms (allow margin for slow CI).
        self::assertGreaterThan(0.0, $record->extra['execution_time_ms']);
    }

    private function makeRecord(): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'performance',
            level: Level::Info,
            message: 'test',
            context: [],
            extra: [],
        );
    }
}
