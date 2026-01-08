<?php

declare(strict_types=1);

namespace App\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Enriches log records with contextual information:
 * - Request ID (for correlating logs from the same request)
 * - User email and ID (if authenticated)
 * - Request method and URI
 * - Client IP address
 * - Session ID
 * - Environment.
 */
class ContextProcessor implements ProcessorInterface
{
    private ?string $requestId = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly string $environment,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        // Generate or retrieve request ID for log correlation
        if ($request !== null) {
            if ($this->requestId === null) {
                $this->requestId = $this->generateRequestId();
            }

            $extra = [
                'request_id'     => $this->requestId,
                'request_method' => $request->getMethod(),
                'request_uri'    => $request->getRequestUri(),
                'client_ip'      => $request->getClientIp(),
                'environment'    => $this->environment,
            ];

            // Add session ID if available
            if ($request->hasSession() && $request->getSession()->isStarted()) {
                $extra['session_id'] = substr($request->getSession()->getId(), 0, 8); // First 8 chars for privacy
            }

            // Add user context if authenticated
            $user = $this->security->getUser();
            if ($user !== null) {
                $extra['user_email'] = $user->getUserIdentifier();
                if (method_exists($user, 'getId')) {
                    $extra['user_id'] = $user->getId();
                }
            }

            return $record->with(extra: array_merge($record->extra, $extra));
        }

        // For CLI/non-HTTP contexts
        return $record->with(extra: array_merge($record->extra, [
            'environment' => $this->environment,
            'context'     => 'cli',
        ]));
    }

    /**
     * Generate a unique request ID for log correlation.
     */
    private function generateRequestId(): string
    {
        return sprintf(
            '%s-%s',
            date('YmdHis'),
            bin2hex(random_bytes(4)),
        );
    }
}
