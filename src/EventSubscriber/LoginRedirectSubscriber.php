<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Subscriber pour gérer la redirection après connexion.
 * Empêche la redirection vers des routes API ou non désirées.
 */
class LoginRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        // Si la réponse est déjà une redirection
        if ($response instanceof RedirectResponse) {
            $targetUrl = $response->getTargetUrl();

            // Liste des patterns à éviter dans la redirection
            $blockedPatterns = [
                '/api/',
                '/manager/conges/api/',
                '/_profiler',
                '/_wdt',
            ];

            // Vérifier si l'URL cible contient un pattern bloqué
            foreach ($blockedPatterns as $pattern) {
                if (str_contains($targetUrl, $pattern)) {
                    // Rediriger vers la page d'accueil au lieu de l'URL bloquée
                    $event->setResponse(new RedirectResponse($this->urlGenerator->generate('home')));

                    return;
                }
            }
        }

        // Si aucune redirection spécifique, laisser le comportement par défaut
    }
}
