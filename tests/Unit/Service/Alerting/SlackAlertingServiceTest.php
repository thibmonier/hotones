<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Alerting;

use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[AllowMockObjectsWithoutExpectations]
final class SlackAlertingServiceTest extends TestCase
{
    public function testSendAlertReturnsTrueOn2xx(): void
    {
        $client = new MockHttpClient(new MockResponse('ok', ['http_code' => 200]));

        $service = new SlackAlertingService(
            $client,
            new NullLogger(),
            'https://hooks.slack.com/services/X/Y/Z',
        );

        static::assertTrue($service->sendAlert('Test', 'Body', AlertSeverity::INFO));
    }

    public function testSendAlertReturnsFalseOnNon2xx(): void
    {
        $client = new MockHttpClient(new MockResponse('forbidden', ['http_code' => 403]));

        $service = new SlackAlertingService(
            $client,
            new NullLogger(),
            'https://hooks.slack.com/services/X/Y/Z',
        );

        static::assertFalse($service->sendAlert('Test', 'Body'));
    }

    public function testSendAlertReturnsFalseWhenWebhookEmpty(): void
    {
        $client = new MockHttpClient(new MockResponse('ok', ['http_code' => 200]));

        $service = new SlackAlertingService(
            $client,
            new NullLogger(),
            null,
        );

        static::assertFalse($service->sendAlert('Test', 'Body'));
    }

    public function testSendAlertReturnsFalseWhenWebhookEmptyString(): void
    {
        $client = new MockHttpClient(new MockResponse('ok', ['http_code' => 200]));

        $service = new SlackAlertingService(
            $client,
            new NullLogger(),
            '   ',
        );

        static::assertFalse($service->sendAlert('Test', 'Body'));
    }

    public function testSendAlertCatchesNetworkException(): void
    {
        $client = new MockHttpClient(static function (): void {
            throw new RuntimeException('network down');
        });

        $service = new SlackAlertingService(
            $client,
            new NullLogger(),
            'https://hooks.slack.com/services/X/Y/Z',
        );

        static::assertFalse($service->sendAlert('Test', 'Body'));
    }

    public function testAllSeverityLevelsHaveEmojiAndColor(): void
    {
        foreach (AlertSeverity::cases() as $severity) {
            static::assertNotEmpty($severity->emoji());
            static::assertNotEmpty($severity->color());
        }
    }

    public function testCriticalSeverityIsDarkRed(): void
    {
        static::assertSame('#660000', AlertSeverity::CRITICAL->color());
    }
}
