<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginListener
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        // Update login tracking fields
        $user->setLastLoginAt(new DateTimeImmutable());
        $user->setLastLoginIp($request->getClientIp());

        $this->em->flush();
    }
}
