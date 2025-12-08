<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Vacation;
use App\Message\VacationNotificationMessage;
use App\Repository\ContributorRepository;
use App\Repository\VacationRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manager/conges')]
#[IsGranted('ROLE_MANAGER')]
class VacationApprovalController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VacationRepository $vacationRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route('', name: 'vacation_approval_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Récupérer le contributeur associé au manager
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$managerContributor) {
            $this->addFlash('warning', 'Aucun profil contributeur n\'est associé à votre compte.');

            return $this->redirectToRoute('home');
        }

        // Récupérer les demandes de congés des contributeurs gérés
        $managedContributors = $managerContributor->getManagedContributors();

        $pendingVacations = [];
        $allVacations     = [];

        foreach ($managedContributors as $contributor) {
            $contributorVacations = $this->vacationRepository->findBy(
                ['contributor' => $contributor],
                ['createdAt' => 'DESC'],
            );

            foreach ($contributorVacations as $vacation) {
                $allVacations[] = $vacation;
                if ($vacation->getStatus() === 'pending') {
                    $pendingVacations[] = $vacation;
                }
            }
        }

        return $this->render('vacation_approval/index.html.twig', [
            'pending_vacations' => $pendingVacations,
            'all_vacations'     => $allVacations,
            'manager'           => $managerContributor,
        ]);
    }

    #[Route('/{id}', name: 'vacation_approval_show', methods: ['GET'])]
    public function show(Vacation $vacation): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Vérifier que la demande concerne un contributeur géré par ce manager
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);
        if (!$managerContributor || $vacation->getContributor()->getManager() !== $managerContributor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette demande.');
        }

        return $this->render('vacation_approval/show.html.twig', [
            'vacation' => $vacation,
        ]);
    }

    #[Route('/{id}/approuver', name: 'vacation_approval_approve', methods: ['POST'])]
    public function approve(Request $request, Vacation $vacation): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Vérifier que la demande concerne un contributeur géré par ce manager
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);
        if (!$managerContributor || $vacation->getContributor()->getManager() !== $managerContributor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette demande.');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('approve'.$vacation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // On ne peut approuver que les demandes en attente
        if ($vacation->getStatus() !== 'pending') {
            $this->addFlash('error', 'Seules les demandes en attente peuvent être approuvées.');

            return $this->redirectToRoute('vacation_approval_show', ['id' => $vacation->getId()]);
        }

        $vacation->setStatus('approved');
        $vacation->setApprovedAt(new DateTime());
        $vacation->setApprovedBy($user);
        $this->entityManager->flush();

        // Envoyer une notification au contributeur
        $this->messageBus->dispatch(new VacationNotificationMessage($vacation->getId(), 'approved'));

        $this->addFlash('success', 'La demande de congé a été approuvée.');

        return $this->redirectToRoute('vacation_approval_index');
    }

    #[Route('/{id}/rejeter', name: 'vacation_approval_reject', methods: ['POST'])]
    public function reject(Request $request, Vacation $vacation): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Vérifier que la demande concerne un contributeur géré par ce manager
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);
        if (!$managerContributor || $vacation->getContributor()->getManager() !== $managerContributor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette demande.');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('reject'.$vacation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // On ne peut rejeter que les demandes en attente
        if ($vacation->getStatus() !== 'pending') {
            $this->addFlash('error', 'Seules les demandes en attente peuvent être rejetées.');

            return $this->redirectToRoute('vacation_approval_show', ['id' => $vacation->getId()]);
        }

        $vacation->setStatus('rejected');
        $this->entityManager->flush();

        // Envoyer une notification au contributeur
        $this->messageBus->dispatch(new VacationNotificationMessage($vacation->getId(), 'rejected'));

        $this->addFlash('success', 'La demande de congé a été rejetée.');

        return $this->redirectToRoute('vacation_approval_index');
    }

    #[Route('/api/pending-count', name: 'vacation_approval_pending_count', methods: ['GET'])]
    public function pendingCount(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Récupérer le contributeur associé au manager
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$managerContributor) {
            return $this->json(['count' => 0, 'vacations' => []]);
        }

        // Récupérer les demandes de congés des contributeurs gérés
        $managedContributors = $managerContributor->getManagedContributors();

        $pendingVacations = [];

        foreach ($managedContributors as $contributor) {
            $contributorVacations = $this->vacationRepository->findBy(
                ['contributor' => $contributor, 'status' => 'pending'],
                ['createdAt' => 'DESC'],
                5, // Limite à 5 pour l'affichage dans le dropdown
            );

            foreach ($contributorVacations as $vacation) {
                $pendingVacations[] = [
                    'id'           => $vacation->getId(),
                    'contributor'  => $vacation->getContributor()->getFullName(),
                    'type'         => $vacation->getTypeLabel(),
                    'start_date'   => $vacation->getStartDate()->format('d/m/Y'),
                    'end_date'     => $vacation->getEndDate()->format('d/m/Y'),
                    'working_days' => $vacation->getNumberOfWorkingDays(),
                    'url'          => $this->generateUrl('vacation_approval_show', ['id' => $vacation->getId()]),
                ];
            }
        }

        // Compter toutes les demandes en attente
        $totalCount = 0;
        foreach ($managedContributors as $contributor) {
            $totalCount += $this->vacationRepository->count(['contributor' => $contributor, 'status' => 'pending']);
        }

        return $this->json([
            'count'     => $totalCount,
            'vacations' => $pendingVacations,
        ]);
    }
}
