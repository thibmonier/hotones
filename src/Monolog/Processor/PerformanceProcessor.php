<?php

declare(strict_types=1);

namespace App\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Enriches log records with performance metrics:
 * - Memory usage (current and peak)
 * - Execution time since request start.
 */
class PerformanceProcessor implements ProcessorInterface
{
    private float $startTime;

    public function __construct()
    {
        $this->startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $executionTime = microtime(true) - $this->startTime;

        $extra = [
            'memory_usage_mb'   => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb'    => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'execution_time_ms' => round($executionTime * 1000, 2),
        ];

        return $record->with(extra: array_merge($record->extra, $extra));
    }
}
