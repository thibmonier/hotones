<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\WorkloadPredictionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staffing')]
#[IsGranted('ROLE_MANAGER')]
class WorkloadPredictionController extends AbstractController
{
    public function __construct(
        private WorkloadPredictionService $predictionService
    ) {
    }

    #[Route('/prediction', name: 'staffing_prediction', methods: ['GET'])]
    public function prediction(): Response
    {
        $analysis = $this->predictionService->analyzePipeline();

        return $this->render('staffing/prediction.html.twig', [
            'pipeline'           => $analysis['pipeline'],
            'workloadByMonth'    => $analysis['workloadByMonth'],
            'alerts'             => $analysis['alerts'],
            'totalPotentialDays' => $analysis['totalPotentialDays'],
        ]);
    }
}
