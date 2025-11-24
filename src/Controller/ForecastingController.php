<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ForecastingService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/forecasting')]
#[IsGranted('ROLE_MANAGER')]
class ForecastingController extends AbstractController
{
    public function __construct(
        private ForecastingService $forecastingService
    ) {
    }

    #[Route('/dashboard', name: 'forecasting_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        $horizon = (int) $request->query->get('horizon', 6);

        if (!in_array($horizon, [3, 6, 12], true)) {
            $horizon = 6;
        }

        try {
            $forecast = $this->forecastingService->forecastRevenue($horizon);

            return $this->render('forecasting/dashboard.html.twig', [
                'forecast' => $forecast,
                'horizon'  => $horizon,
            ]);
        } catch (RuntimeException $e) {
            $this->addFlash('warning', $e->getMessage());

            return $this->render('forecasting/dashboard.html.twig', [
                'forecast' => null,
                'horizon'  => $horizon,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    #[Route('/api/forecast', name: 'forecasting_api', methods: ['GET'])]
    public function apiForecast(Request $request): Response
    {
        $horizon = (int) $request->query->get('horizon', 6);

        if (!in_array($horizon, [3, 6, 12], true)) {
            return $this->json(['error' => 'Invalid horizon. Must be 3, 6, or 12'], 400);
        }

        try {
            $forecast = $this->forecastingService->forecastRevenue($horizon);

            return $this->json([
                'success' => true,
                'data'    => $forecast,
            ]);
        } catch (RuntimeException $e) {
            return $this->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);
        }
    }
}
