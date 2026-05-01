<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Fixture;

use App\Event\NotificationEvent;

/**
 * Concrete NotificationEvent for the integration chain test.
 *
 * Used to dispatch a real event through the EventDispatcher so the
 * subscriber → service → DB chain can be observed end-to-end.
 */
final class StubNotificationEvent extends NotificationEvent
{
}
