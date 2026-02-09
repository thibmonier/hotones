<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\Profile;
use App\Service\WorkloadPredictionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staffing')]
#[IsGranted('ROLE_MANAGER')]
class WorkloadPredictionController extends AbstractController
{
    public function __construct(
        private readonly WorkloadPredictionService $predictionService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/prediction', name: 'staffing_prediction', methods: ['GET'])]
    public function prediction(Request $request): Response
    {
        // Récupérer les filtres depuis la requête
        $profileIds     = $request->query->all('profiles');
        $contributorIds = $request->query->all('contributors');

        // Convertir en entiers
        $profileIds     = array_map(intval(...), array_filter($profileIds, fn ($v): bool => $v !== null && $v !== ''));
        $contributorIds = array_map(
            intval(...),
            array_filter($contributorIds, fn ($v): bool => $v !== null && $v !== ''),
        );

        // Analyser le pipeline avec filtres
        $analysis = $this->predictionService->analyzePipeline($profileIds, $contributorIds);

        // Récupérer tous les profils actifs pour le filtre
        $allProfiles = $this->entityManager->getRepository(Profile::class)->findBy(['active' => true], [
            'name' => 'ASC',
        ]);

        // Récupérer tous les collaborateurs actifs pour le filtre
        $allContributors = $this->entityManager->getRepository(Contributor::class)->findBy(['active' => true], [
            'firstName' => 'ASC',
        ]);

        return $this->render('staffing/prediction.html.twig', [
            'pipeline'             => $analysis['pipeline'],
            'workloadByMonth'      => $analysis['workloadByMonth'],
            'alerts'               => $analysis['alerts'],
            'totalPotentialDays'   => $analysis['totalPotentialDays'],
            'allProfiles'          => $allProfiles,
            'allContributors'      => $allContributors,
            'selectedProfiles'     => $profileIds,
            'selectedContributors' => $contributorIds,
        ]);
    }
}
