<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

use function in_array;

class ClientServiceLevelCalculator
{
    // Paramètres configurables (à externaliser plus tard dans la config)
    private const int TOP_VIP_RANK      = 20; // Top 20 = VIP
    private const int TOP_PRIORITY_RANK = 50; // Top 50 = Prioritaire
    private const int LOW_THRESHOLD     = 5000; // < 5000€ = Basse priorité

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientRepository $clientRepository,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    /**
     * Calcule le niveau de service pour un client en mode auto.
     */
    public function calculateServiceLevel(Client $client, ?int $year = null): string
    {
        if ($year === null) {
            $year = (int) date('Y');
        }

        $totalRevenue = $this->getClientRevenueForYear($client, $year);

        // Si CA < seuil, basse priorité
        if ($totalRevenue < self::LOW_THRESHOLD) {
            return 'low';
        }

        // Obtenir le classement du client
        $rank = $this->getClientRankByRevenue($client, $year);

        // Top 20 = VIP
        if ($rank <= self::TOP_VIP_RANK) {
            return 'vip';
        }

        // Top 50 = Prioritaire
        if ($rank <= self::TOP_PRIORITY_RANK) {
            return 'priority';
        }

        // Sinon = Standard
        return 'standard';
    }

    /**
     * Recalcule et met à jour le niveau de service pour tous les clients en mode auto.
     */
    public function recalculateAllAutoClients(?int $year = null): int
    {
        $clients = $this->clientRepository->findBy(['serviceLevelMode' => 'auto']);
        $count   = 0;

        foreach ($clients as $client) {
            $newLevel = $this->calculateServiceLevel($client, $year);
            $client->setServiceLevel($newLevel);
            $this->entityManager->persist($client);
            ++$count;
        }

        $this->entityManager->flush();

        return $count;
    }

    /**
     * Obtient le CA total d'un client pour une année donnée.
     */
    private function getClientRevenueForYear(Client $client, int $year): float
    {
        // Récupérer tous les orders avec statut signé ou gagné
        $allOrders = $this->orderRepository->findAll();

        $total = 0.0;
        foreach ($allOrders as $order) {
            if (!$order->getProject()) {
                continue;
            }
            if ($order->getProject()->getClient() !== $client) {
                continue;
            }
            // Vérifier le statut (signe ou gagne)
            if (!in_array($order->getStatus(), ['signe', 'gagne'], true)) {
                continue;
            }
            if (!$order->getValidatedAt()) {
                continue;
            }
            if ((int) $order->getValidatedAt()->format('Y') !== $year) {
                continue;
            }
            $total += (float) $order->getTotalAmount();
        }

        return $total;
    }

    /**
     * Obtient le classement d'un client par CA (1 = meilleur client).
     */
    private function getClientRankByRevenue(Client $client, int $year): int
    {
        $allClients = $this->clientRepository->findAll();
        $revenues   = [];

        foreach ($allClients as $c) {
            $revenues[$c->getId()] = $this->getClientRevenueForYear($c, $year);
        }

        // Trier par CA décroissant
        arsort($revenues);

        // Trouver le rang du client
        $rank = 1;
        foreach ($revenues as $clientId => $revenue) {
            if ($clientId === $client->getId()) {
                return $rank;
            }
            ++$rank;
        }

        return $rank;
    }

    /**
     * Obtient les paramètres de configuration.
     */
    public function getConfiguration(): array
    {
        return [
            'top_vip_rank'      => self::TOP_VIP_RANK,
            'top_priority_rank' => self::TOP_PRIORITY_RANK,
            'low_threshold'     => self::LOW_THRESHOLD,
        ];
    }
}
