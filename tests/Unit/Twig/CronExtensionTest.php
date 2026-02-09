<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\CronExtension;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CronExtensionTest extends TestCase
{
    private CronExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new CronExtension();
    }

    public static function provideHumanizeCases(): array
    {
        return [
            'every 15 minutes (fr)'    => ['*/15 * * * *', 'fr', 'toutes les 15 minutes'],
            'hourly at 00 (fr)'        => ['0 * * * *', 'fr', 'au début de chaque heure'],
            'every hour at 05 (fr)'    => ['5 * * * *', 'fr', 'chaque heure à la minute 05'],
            'weekdays step-range (fr)' => [
                '15 9-17/2 * * 1-5',
                'fr',
                'chaque lundi - vendredi toutes les 2 heures entre 09:00 et 17:00 à la minute 15',
            ],
            'months & dom (fr)' => ['0 7 15 1,7 *', 'fr', 'à 07:00 en janvier, juillet le jour 15'],
            'list hours (en)'   => ['0 9,13,17 * * *', 'en', 'at 09:00, 13:00, 17:00'],
            'every minute (en)' => ['* * * * *', 'en', 'every minute'],
        ];
    }

    #[DataProvider('provideHumanizeCases')]
    public function testHumanizeCron(string $expr, string $locale, string $expectedStartsWith): void
    {
        $actual = $this->ext->humanizeCron($expr, $locale);
        // Some descriptions include composed strings; assert start matches expected
        $this->assertStringStartsWith(
            $expectedStartsWith,
            $actual,
            sprintf(
                'Humanized cron for "%s" (%s) should start with "%s"; got "%s"',
                $expr,
                $locale,
                $expectedStartsWith,
                $actual,
            ),
        );
    }

    public function testCronNextRunReturnsDate(): void
    {
        $next = $this->ext->nextRun('*/5 * * * *', 'Europe/Paris');
        $this->assertNotNull($next);
        $this->assertInstanceOf(DateTimeImmutable::class, $next);
    }
}
