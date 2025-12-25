<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Vacation;
use App\Form\VacationRequestType;
use App\Message\VacationNotificationMessage;
use App\Repository\ContributorRepository;
use App\Repository\VacationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mes-conges')]
#[IsGranted('ROLE_INTERVENANT')]
class VacationRequestController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VacationRepository $vacationRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route('', name: 'vacation_request_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Récupérer le collaborateur associé à l'utilisateur
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$contributor) {
            $this->addFlash('warning', 'Aucun profil collaborateur n\'est associé à votre compte.');

            return $this->redirectToRoute('home');
        }

        // Récupérer toutes les demandes de congés du collaborateur
        $vacations = $this->vacationRepository->findBy(
            ['contributor' => $contributor],
            ['createdAt' => 'DESC'],
        );

        return $this->render('vacation_request/index.html.twig', [
            'vacations'   => $vacations,
            'contributor' => $contributor,
        ]);
    }

    #[Route('/nouvelle-demande', name: 'vacation_request_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Récupérer le collaborateur associé à l'utilisateur
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$contributor) {
            $this->addFlash('error', 'Aucun profil collaborateur n\'est associé à votre compte.');

            return $this->redirectToRoute('home');
        }

        $vacation = new Vacation();
        $vacation->setContributor($contributor);
        $vacation->setStatus('pending');

        $form = $this->createForm(VacationRequestType::class, $vacation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Valider que la date de fin est après la date de début
            if ($vacation->getEndDate() < $vacation->getStartDate()) {
                $this->addFlash('error', 'La date de fin doit être postérieure à la date de début.');

                return $this->render('vacation_request/new.html.twig', [
                    'form' => $form,
                ]);
            }

            $this->entityManager->persist($vacation);
            $this->entityManager->flush();

            // Envoyer une notification au manager
            $this->messageBus->dispatch(new VacationNotificationMessage($vacation->getId(), 'created'));

            $this->addFlash('success', 'Votre demande de congé a été enregistrée avec succès. Elle est en attente de validation.');

            return $this->redirectToRoute('vacation_request_index');
        }

        return $this->render('vacation_request/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'vacation_request_show', methods: ['GET'])]
    public function show(Vacation $vacation): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Vérifier que la demande appartient bien à l'utilisateur connecté
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);
        if (!$contributor || $vacation->getContributor()->getId() !== $contributor->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette demande.');
        }

        return $this->render('vacation_request/show.html.twig', [
            'vacation' => $vacation,
        ]);
    }

    #[Route('/{id}/annuler', name: 'vacation_request_cancel', methods: ['POST'])]
    public function cancel(Request $request, Vacation $vacation): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Vérifier que la demande appartient bien à l'utilisateur connecté
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);
        if (!$contributor || $vacation->getContributor()->getId() !== $contributor->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette demande.');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('cancel'.$vacation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // On ne peut annuler que les demandes en attente
        if ($vacation->getStatus() !== 'pending') {
            $this->addFlash('error', 'Seules les demandes en attente peuvent être annulées.');

            return $this->redirectToRoute('vacation_request_show', ['id' => $vacation->getId()]);
        }

        $vacation->setStatus('cancelled');
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre demande de congé a été annulée.');

        return $this->redirectToRoute('vacation_request_index');
    }
}
