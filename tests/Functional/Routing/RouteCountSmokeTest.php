<?php

declare(strict_types=1);

namespace App\Tests\Functional\Routing;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Asserts that critical route prefixes are registered after kernel boot.
 *
 * Background: the sprint-003 DDD migration moved `VacationRequestController`
 * and `VacationApprovalController` into `src/Presentation/Vacation/Controller/`,
 * but `config/routes.yaml` only scanned `src/Controller/`. The routes silently
 * disappeared from the router and every functional test under
 * `tests/Functional/Controller/Vacation/` started 404-ing — undetected for two
 * sprints because the failures were tolerated by the CI baseline.
 *
 * REFACTOR-001 added the missing loader (`presentation_controllers` stanza).
 * This smoke test guards against the same regression returning: as soon as a
 * prefix slips below its expected count, the test fails loudly at boot rather
 * than during an obscure functional test.
 *
 * If a controller is intentionally removed, lower the `>=` threshold in the
 * matching assertion. If you need to add a brand-new prefix, add an assertion
 * here.
 *
 * TEST-VACATION-FUNCTIONAL-001 (sprint-005).
 */
final class RouteCountSmokeTest extends KernelTestCase
{
    public function testVacationRequestRoutesAreRegistered(): void
    {
        $routes = $this->loadRouteNames();

        $matches = array_filter($routes, static fn (string $name): bool => str_starts_with($name, 'vacation_request_'));

        self::assertGreaterThanOrEqual(
            4,
            count($matches),
            'Expected at least 4 `vacation_request_*` routes (index, new, show, cancel). '
            . 'Got: ' . implode(', ', $matches),
        );
    }

    public function testVacationApprovalRoutesAreRegistered(): void
    {
        $routes = $this->loadRouteNames();

        $matches = array_filter($routes, static fn (string $name): bool => str_starts_with($name, 'vacation_approval_'));

        self::assertGreaterThanOrEqual(
            6,
            count($matches),
            'Expected at least 6 `vacation_approval_*` routes (index, show, approve, reject, cancel, pending_count). '
            . 'Got: ' . implode(', ', $matches),
        );
    }

    public function testCorePresentationLayerLoaderIsActive(): void
    {
        // Lightweight cross-check: ensure the `App\Presentation\…` namespace is
        // actually scanned by the router (and not just one specific controller).
        // We use the pending_count API endpoint as a sentinel — it lives in
        // `src/Presentation/Vacation/Controller/VacationApprovalController.php`
        // and would be the first one to disappear if the loader stanza were
        // removed.
        $routes = $this->loadRouteNames();

        self::assertContains(
            'vacation_approval_pending_count',
            $routes,
            'The `App\Presentation\…` route loader appears to be disabled. '
            . 'Check `config/routes.yaml` for the `presentation_controllers` stanza.',
        );
    }

    /**
     * @return list<string>
     */
    private function loadRouteNames(): array
    {
        self::bootKernel();
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        return array_keys($router->getRouteCollection()->all());
    }
}
