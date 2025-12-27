<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Subscriber pour sécuriser le processus de login.
 *
 * Fonctionnalités :
 * - Rate limiting sur les tentatives de login (protection brute-force)
 * - Logging des échecs de connexion (audit de sécurité)
 * - Logging des connexions réussies
 */
class LoginSecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.login')]
        private readonly RateLimiterFactory $loginLimiter,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    /**
     * Gère les échecs de connexion.
     *
     * - Applique le rate limiting (5 tentatives / 15 min)
     * - Logue les tentatives échouées pour audit
     */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        // Récupérer l'email/identifiant depuis la requête
        $identifier = $request->request->get('_username', 'unknown');
        $ip         = $request->getClientIp() ?? 'unknown';

        // Créer une clé unique par IP pour le rate limiting
        $limiter = $this->loginLimiter->create($ip);

        // Consommer une tentative du rate limiter
        $limit = $limiter->consume(1);

        // Logger l'échec de connexion
        $this->logger->warning('Login attempt failed', [
            'username'           => $identifier,
            'ip'                 => $ip,
            'user_agent'         => $request->headers->get('User-Agent'),
            'remaining_attempts' => $limit->getRemainingTokens(),
            'retry_after'        => $limit->getRetryAfter()?->getTimestamp(),
        ]);

        // Si le rate limit est dépassé, bloquer
        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();

            $this->logger->error('Login rate limit exceeded', [
                'username'            => $identifier,
                'ip'                  => $ip,
                'retry_after_seconds' => $retryAfter ? $retryAfter->getTimestamp() - time() : null,
            ]);

            throw new TooManyRequestsHttpException($retryAfter?->getTimestamp(), sprintf('Trop de tentatives de connexion. Veuillez réessayer dans %d minutes.', $retryAfter ? (int) ceil(($retryAfter->getTimestamp() - time()) / 60) : 15));
        }
    }

    /**
     * Gère les connexions réussies.
     *
     * - Logue les connexions pour audit
     * - Reset du rate limiter (optionnel, commenté par défaut)
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $user = $event->getUser();
        $ip   = $request->getClientIp() ?? 'unknown';

        // Logger la connexion réussie
        $this->logger->info('User logged in successfully', [
            'username'   => $user->getUserIdentifier(),
            'ip'         => $ip,
            'user_agent' => $request->headers->get('User-Agent'),
        ]);

        // Optionnel : Reset du rate limiter après connexion réussie
        // $limiter = $this->loginLimiter->create($ip);
        // $limiter->reset();
    }
}
