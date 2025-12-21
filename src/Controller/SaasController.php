<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SaasSubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/saas')]
#[IsGranted('ROLE_ADMIN')]
class SaasController extends AbstractController
{
    #[Route('', name: 'saas_dashboard', methods: ['GET'])]
    public function dashboard(SaasSubscriptionRepository $subscriptionRepository): Response
    {
        $activeSubscriptions   = $subscriptionRepository->findActive();
        $expiringSubscriptions = $subscriptionRepository->findExpiringInDays(30);
        $dueForRenewal         = $subscriptionRepository->findDueForRenewal();

        $stats = [
            'total_active'      => $subscriptionRepository->countActive(),
            'monthly_cost'      => $subscriptionRepository->calculateTotalMonthlyCost(),
            'yearly_cost'       => $subscriptionRepository->calculateTotalYearlyCost(),
            'expiring_soon'     => count($expiringSubscriptions),
            'due_for_renewal'   => count($dueForRenewal),
            'by_status'         => $subscriptionRepository->getStatsByStatus(),
            'by_billing_period' => $subscriptionRepository->getStatsByBillingPeriod(),
        ];

        return $this->render('saas/dashboard.html.twig', [
            'stats'                  => $stats,
            'active_subscriptions'   => $activeSubscriptions,
            'expiring_subscriptions' => $expiringSubscriptions,
            'due_for_renewal'        => $dueForRenewal,
        ]);
    }
}
