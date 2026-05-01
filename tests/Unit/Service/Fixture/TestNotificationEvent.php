<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Fixture;

use App\Event\NotificationEvent;

/**
 * Concrete NotificationEvent used by NotificationServiceTest to instantiate
 * the abstract base class with controlled payloads.
 */
final class TestNotificationEvent extends NotificationEvent
{
}
