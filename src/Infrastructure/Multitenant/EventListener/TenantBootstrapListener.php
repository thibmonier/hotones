<?php

declare(strict_types=1);

namespace App\Infrastructure\Multitenant\EventListener;

use App\Domain\Shared\ValueObject\TenantId;
use App\Entity\User;
use App\Infrastructure\Multitenant\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Bridges the security token to TenantContext + activates the Doctrine
 * `tenant_filter` for the duration of the request.
 *
 * Runs on `kernel.request` with low priority (after Symfony Security
 * authenticates the user) but high enough to fire before any controller
 * touches Doctrine.
 *
 * Behavior:
 *   - User is authenticated and is an `App\Entity\User` instance:
 *     - Extract Company id → TenantId VO → set on TenantContext.
 *     - Enable `tenant_filter` Doctrine SQLFilter with the tenantId param.
 *   - User is anonymous or not an App\Entity\User:
 *     - Filter stays disabled (public routes don't need tenant scoping).
 *
 * Bypass for cross-tenant superadmin reports:
 *   `$em->getFilters()->disable('tenant_filter');` then re-enable.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 8)]
final class TenantBootstrapListener
{
    public function __construct(
        private readonly Security $security,
        private readonly TenantContext $tenantContext,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        // Only act on the main request to avoid double-bootstrap on sub-requests.
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            // Anonymous request → no tenant context. Filter stays disabled.
            return;
        }

        // User::getCompany() returns non-nullable Company (DB constraint).
        // Defensive guard: if id is null (transient entity), skip bootstrap.
        $companyId = $user->getCompany()->getId();
        if ($companyId === null) {
            return;
        }

        $tenantId = TenantId::fromInt($companyId);
        $this->tenantContext->setCurrentTenant($tenantId);

        $filters = $this->em->getFilters();
        if (!$filters->isEnabled('tenant_filter')) {
            $filters->enable('tenant_filter');
        }
        $filters->getFilter('tenant_filter')->setParameter('tenantId', (string) $tenantId->value);
    }
}
