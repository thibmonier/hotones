<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Configure Gedmo Blameable listener for CLI context.
 *
 * In CLI commands, there is no authenticated user. This subscriber
 * sets a default system user to prevent "User not authenticated" errors.
 */
class BlameableSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'stof_doctrine_extensions.listener.blameable')]
        private readonly object $blameableListener,
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        // Find first available user as system user for CLI context
        try {
            $systemUser = $this->entityManager->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);

            if ($systemUser) {
                // Create a token for the system user to satisfy Blameable listener
                $token = new UsernamePasswordToken($systemUser, 'cli', $systemUser->getRoles());
                $this->tokenStorage->setToken($token);

                // Also set the user value directly on the listener
                $this->blameableListener->setUserValue($systemUser);
            }
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException) {
            // Table doesn't exist yet (e.g., during test database setup)
            // Silently ignore - the blameable listener will handle the absence of a user
        }
    }
}
