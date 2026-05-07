<?php

declare(strict_types=1);

namespace App\Service\Alerting;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * US-094 (sprint-017 EPIC-002) — Slack alerting service for prod incidents.
 *
 * Posts incoming-webhook messages to a configured Slack channel
 * (typically `#alerts-prod`). Sentry-side alerts go directly via Sentry's
 * native Slack integration ; this service exists for application-level
 * alerts the app emits itself (quota warnings, batch failures, smoke
 * tests, etc.).
 *
 * No-op when SLACK_WEBHOOK_URL is empty (dev / CI environment).
 */
final readonly class SlackAlertingService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ?string $webhookUrl,
        private string $defaultChannel = '#alerts-prod',
    ) {
    }

    /**
     * Send a plain-text alert. Returns true if dispatched successfully.
     */
    public function sendAlert(string $title, string $body, AlertSeverity $severity = AlertSeverity::INFO): bool
    {
        if ($this->webhookUrl === null || trim($this->webhookUrl) === '') {
            $this->logger->debug('Slack alerting disabled (no webhook URL configured)', [
                'title' => $title,
                'severity' => $severity->value,
            ]);

            return false;
        }

        $emoji = $severity->emoji();
        $payload = [
            'channel' => $this->defaultChannel,
            'username' => 'HotOnes Alerts',
            'icon_emoji' => $emoji,
            'attachments' => [
                [
                    'color' => $severity->color(),
                    'title' => $emoji.' '.$title,
                    'text' => $body,
                    'ts' => time(),
                ],
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $this->webhookUrl, [
                'json' => $payload,
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('Slack alert dispatched', [
                    'title' => $title,
                    'severity' => $severity->value,
                    'status' => $statusCode,
                ]);

                return true;
            }

            $this->logger->error('Slack alert failed (non-2xx)', [
                'title' => $title,
                'status' => $statusCode,
            ]);

            return false;
        } catch (Throwable $e) {
            $this->logger->error('Slack alert exception', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
