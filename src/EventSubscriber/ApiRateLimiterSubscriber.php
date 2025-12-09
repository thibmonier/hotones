<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class ApiRateLimiterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.api')]
        private readonly RateLimiterFactory $apiLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        // Appliquer le rate limiter uniquement sur les routes API
        if (!$this->isApiRoute($path)) {
            return;
        }

        // Créer une clé unique basée sur l'utilisateur OU l'IP
        $limiterKey = $this->getLimiterKey($request);

        // Vérifier la limite
        $limiter = $this->apiLimiter->create($limiterKey);
        $limit   = $limiter->consume(1);

        // Ajouter les headers de rate limiting
        $event->getResponse()?->headers->add([
            'X-RateLimit-Limit'     => (string) $limit->getLimit(),
            'X-RateLimit-Remaining' => (string) $limit->getRemainingTokens(),
            'X-RateLimit-Reset'     => (string) $limit->getRetryAfter()->getTimestamp(),
        ]);

        // Si la limite est atteinte, retourner une erreur 429
        if (!$limit->isAccepted()) {
            $response = new JsonResponse([
                'error'       => 'Too Many Requests',
                'message'     => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $limit->getRetryAfter()->getTimestamp(),
            ], Response::HTTP_TOO_MANY_REQUESTS);

            $response->headers->add([
                'X-RateLimit-Limit'     => (string) $limit->getLimit(),
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset'     => (string) $limit->getRetryAfter()->getTimestamp(),
                'Retry-After'           => (string) $limit->getRetryAfter()->getTimestamp(),
            ]);

            $event->setResponse($response);
        }
    }

    private function isApiRoute(string $path): bool
    {
        // Routes à limiter
        $apiPatterns = [
            '/api/',
            '/tasks/api/',
        ];

        foreach ($apiPatterns as $pattern) {
            if (str_starts_with($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function getLimiterKey($request): string
    {
        // Priorité 1 : Utiliser l'ID utilisateur si authentifié
        $user = $request->getUser();
        if ($user) {
            return 'user_'.$user;
        }

        // Priorité 2 : Utiliser l'adresse IP
        $ip = $request->getClientIp();

        return 'ip_'.($ip ?: 'unknown');
    }
}
