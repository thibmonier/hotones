<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * US-096 (sprint-018 EPIC-002) — correlation ID exposé côté frontend.
 *
 * Pour chaque requête HTTP :
 *   1. Lit le header `X-Request-Id` entrant (si fourni par CDN / load balancer)
 *   2. Sinon génère un ID unique format `<YmdHis>-<8 hex>` (compat ContextProcessor)
 *   3. Stocke dans `Request::attributes->request_id` pour usage applicatif
 *   4. Expose en réponse via header `X-Request-Id`
 *
 * Le frontend peut lire ce header (devtools network ou JS) et le ré-injecter
 * dans les requêtes suivantes pour corréler une trace bout-en-bout côté logs
 * JSON Render et Sentry.
 *
 * Synchronisé avec `ContextProcessor` : si `request_id` présent dans Request
 * attributes, le processor le réutilise au lieu de générer une nouvelle valeur.
 */
final readonly class RequestIdSubscriber implements EventSubscriberInterface
{
    public const string HEADER_NAME = 'X-Request-Id';
    public const string ATTRIBUTE_NAME = 'request_id';

    private const string DATE_FORMAT = 'YmdHis';
    private const int RANDOM_BYTES = 4;
    private const int MAX_HEADER_LENGTH = 128;

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité haute pour set le request_id avant tout autre listener
            KernelEvents::REQUEST => ['onKernelRequest', 256],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $incoming = $request->headers->get(self::HEADER_NAME);

        $requestId = $this->isValidIncoming($incoming) ? $incoming : $this->generate();

        $request->attributes->set(self::ATTRIBUTE_NAME, $requestId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $request->attributes->get(self::ATTRIBUTE_NAME);

        if (!is_string($requestId) || $requestId === '') {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set(self::HEADER_NAME, $requestId);
    }

    private function isValidIncoming(?string $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        if (strlen($value) > self::MAX_HEADER_LENGTH) {
            return false;
        }

        // Whitelist : alphanumeric + tirets + underscores + points uniquement
        // (évite injection via headers comme `X-Request-Id: foo\r\nSet-Cookie: ...`)
        return preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1;
    }

    private function generate(): string
    {
        return sprintf(
            '%s-%s',
            date(self::DATE_FORMAT),
            bin2hex(random_bytes(self::RANDOM_BYTES)),
        );
    }
}
