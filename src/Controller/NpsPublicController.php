<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\NpsSurvey;
use App\Form\NpsResponseType;
use App\Repository\NpsSurveyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/nps')]
class NpsPublicController extends AbstractController
{
    public function __construct(
        private readonly NpsSurveyRepository $npsSurveyRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Page publique pour répondre à une enquête NPS via le token.
     */
    #[Route('/{token}', name: 'nps_public_respond', methods: ['GET', 'POST'])]
    public function respond(string $token, Request $request): Response
    {
        $survey = $this->npsSurveyRepository->findByToken($token);

        if (!$survey) {
            return $this->render('nps/public/not_found.html.twig');
        }

        // Vérifier si l'enquête a expiré
        if ($survey->isExpired()) {
            $survey->markAsExpired();
            $this->entityManager->flush();

            return $this->render('nps/public/expired.html.twig', [
                'survey' => $survey,
            ]);
        }

        // Vérifier si l'enquête a déjà été complétée
        if ($survey->getStatus() === NpsSurvey::STATUS_COMPLETED) {
            return $this->render('nps/public/already_completed.html.twig', [
                'survey' => $survey,
            ]);
        }

        // Formulaire de réponse
        $form = $this->createForm(NpsResponseType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $survey->setScore((int) $data['score']);
            $survey->setComment($data['comment'] ?? null);
            $survey->markAsCompleted();

            $this->entityManager->flush();

            return $this->render('nps/public/thank_you.html.twig', [
                'survey' => $survey,
            ]);
        }

        return $this->render('nps/public/respond.html.twig', [
            'survey' => $survey,
            'form'   => $form->createView(),
        ]);
    }
}
