<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\WorkItem\Controller;

use App\Presentation\WorkItem\Controller\WeeklyTimesheetController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Sprint-021 US-102 — sanity-check du controller (routes + roles +
 * configuration). Tests Functional end-to-end requièrent DB + sessions =
 * suite Functional sprint-021+ post-merge.
 */
final class WeeklyTimesheetControllerTest extends TestCase
{
    public function testControllerHasRolePrefix(): void
    {
        $reflection = new ReflectionClass(WeeklyTimesheetController::class);
        $attrs = $reflection->getAttributes(IsGranted::class);

        self::assertNotEmpty($attrs, 'Controller must require ROLE_INTERVENANT');
        self::assertSame('ROLE_INTERVENANT', $attrs[0]->getArguments()[0]);
    }

    public function testControllerRoutePrefix(): void
    {
        $reflection = new ReflectionClass(WeeklyTimesheetController::class);
        $attrs = $reflection->getAttributes(Route::class);

        self::assertNotEmpty($attrs);
        $route = $attrs[0]->newInstance();
        self::assertSame('/timesheet/week', $route->path);
    }

    public function testIndexRouteAcceptsIsoWeekFormat(): void
    {
        $reflection = new ReflectionClass(WeeklyTimesheetController::class);
        $method = $reflection->getMethod('index');
        $attrs = $method->getAttributes(Route::class);

        self::assertNotEmpty($attrs);
        $route = $attrs[0]->newInstance();
        self::assertSame('weekly_timesheet_index', $route->name);
        self::assertSame(['GET'], $route->methods);
    }

    public function testSaveRouteAcceptsPost(): void
    {
        $reflection = new ReflectionClass(WeeklyTimesheetController::class);
        $method = $reflection->getMethod('save');
        $attrs = $method->getAttributes(Route::class);

        self::assertNotEmpty($attrs);
        $route = $attrs[0]->newInstance();
        self::assertSame('weekly_timesheet_save', $route->name);
        self::assertSame(['POST'], $route->methods);
        self::assertSame('/save', $route->path);
    }

    public function testIsoWeekParserMethodSignature(): void
    {
        $reflection = new ReflectionClass(WeeklyTimesheetController::class);
        $method = $reflection->getMethod('parseIsoWeek');

        self::assertCount(1, $method->getParameters());
        self::assertSame('week', $method->getParameters()[0]->getName());
        self::assertTrue($method->isPrivate());
    }
}
