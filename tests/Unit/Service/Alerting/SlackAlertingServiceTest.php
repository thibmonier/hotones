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

        $this->assertTrue($service->sendAlert('Test', 'Body', AlertSeverity::INFO));
    }

    public function testSendAlertReturnsFalseOnNon2xx(): void
    {
        $client = new MockHttpClient(new MockResponse('forbidden', ['http_code' => 403]));

        $service = new SlackAlertingService(
            $client,
            new NullLogger(),
            'https://hooks.slack.com/services/X/Y/Z',
        );

        $this->assertFalse($service->sendAlert('Test', 'Body'));
    }

    public function testSendAlertReturnsFalseWhenWebhookEmpty(): void
    {
        $client = new MockHttpClient(new MockResponse('ok', ['http_code' => 200]));

        $service = new SlackAlertingService(
            $client,
            new NullLogger(),
            null,
        );

        $this->assertFalse($service->sendAlert('Test', 'Body'));
    }

    public function testSendAlertReturnsFalseWhenWebhookEmptyString(): void
    {
        $client = new MockHttpClient(new MockResponse('ok', ['http_code' => 200]));

        $service = new SlackAlertingService(
            $client,
            new NullLogger(),
            '   ',
        );

        $this->assertFalse($service->sendAlert('Test', 'Body'));
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

        $this->assertFalse($service->sendAlert('Test', 'Body'));
    }

    public function testAllSeverityLevelsHaveEmojiAndColor(): void
    {
        foreach (AlertSeverity::cases() as $severity) {
            $this->assertNotEmpty($severity->emoji());
            $this->assertNotEmpty($severity->color());
        }
    }

    public function testCriticalSeverityIsDarkRed(): void
    {
        $this->assertSame('#660000', AlertSeverity::CRITICAL->color());
    }
}
