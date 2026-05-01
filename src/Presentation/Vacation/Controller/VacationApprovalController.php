<?php

declare(strict_types=1);

namespace App\Presentation\Vacation\Controller;

use App\Application\Vacation\Command\ApproveVacation\ApproveVacationCommand;
use App\Application\Vacation\Command\ApproveVacation\ApproveVacationHandler;
use App\Application\Vacation\Command\CancelVacation\CancelVacationCommand;
use App\Application\Vacation\Command\CancelVacation\CancelVacationHandler;
use App\Application\Vacation\Command\RejectVacation\RejectVacationCommand;
use App\Application\Vacation\Command\RejectVacation\RejectVacationHandler;
use App\Domain\Vacation\Exception\InvalidStatusTransitionException;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Repository\ContributorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manager/conges')]
#[IsGranted('ROLE_MANAGER')]
class VacationApprovalController extends AbstractController
{
    public function __construct(
        private readonly VacationRepositoryInterface $vacationRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly ApproveVacationHandler $approveVacationHandler,
        private readonly RejectVacationHandler $rejectVacationHandler,
        private readonly CancelVacationHandler $cancelVacationHandler,
    ) {
    }

    #[Route('', name: 'vacation_approval_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$managerContributor) {
            $this->addFlash('warning', 'Aucun profil collaborateur n\'est associe a votre compte.');

            return $this->redirectToRoute('home');
        }

        $managedContributors = $managerContributor->getManagedContributors();

        $pendingVacations = $this->vacationRepository->findPendingForContributors(
            $managedContributors->toArray(),
        );

        $allVacations = [];
        foreach ($managedContributors as $contributor) {
            $contributorVacations = $this->vacationRepository->findByContributor($contributor);
            foreach ($contributorVacations as $vacation) {
                $allVacations[] = $vacation;
            }
        }

        return $this->render('vacation_approval/index.html.twig', [
            'pending_vacations' => $pendingVacations,
            'all_vacations' => $allVacations,
            'manager' => $managerContributor,
        ]);
    }

    #[Route('/{id}', name: 'vacation_approval_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);

        $vacation = $this->vacationRepository->findById(VacationId::fromString($id));

        if (!$managerContributor || $vacation->getContributor()->getManager() !== $managerContributor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas acces a cette demande.');
        }

        return $this->render('vacation_approval/show.html.twig', [
            'vacation' => $vacation,
        ]);
    }

    #[Route('/{id}/approuver', name: 'vacation_approval_approve', methods: ['POST'])]
    public function approve(Request $request, string $id): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);

        $vacation = $this->vacationRepository->findById(VacationId::fromString($id));

        if (!$managerContributor || $vacation->getContributor()->getManager() !== $managerContributor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas acces a cette demande.');
        }

        if (!$this->isCsrfTokenValid('approve'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        try {
            ($this->approveVacationHandler)(new ApproveVacationCommand($id, $user->getId()));
            $this->addFlash('success', 'La demande de conge a ete approuvee.');
        } catch (InvalidStatusTransitionException) {
            $this->addFlash('error', 'Seules les demandes en attente peuvent etre approuvees.');
        }

        return $this->redirectToRoute('vacation_approval_index');
    }

    #[Route('/{id}/rejeter', name: 'vacation_approval_reject', methods: ['POST'])]
    public function reject(Request $request, string $id): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);

        $vacation = $this->vacationRepository->findById(VacationId::fromString($id));

        if (!$managerContributor || $vacation->getContributor()->getManager() !== $managerContributor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas acces a cette demande.');
        }

        if (!$this->isCsrfTokenValid('reject'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $rejectionReason = $request->request->get('rejection_reason');
        $rejectionReason = is_string($rejectionReason) ? trim($rejectionReason) : null;
        $rejectionReason = $rejectionReason === '' ? null : $rejectionReason;

        try {
            ($this->rejectVacationHandler)(new RejectVacationCommand($id, $rejectionReason));
            $this->addFlash('success', 'La demande de conge a ete rejetee.');
        } catch (InvalidStatusTransitionException) {
            $this->addFlash('error', 'Seules les demandes en attente peuvent etre rejetees.');
        }

        return $this->redirectToRoute('vacation_approval_index');
    }

    #[Route('/{id}/annuler', name: 'vacation_approval_cancel', methods: ['POST'])]
    public function cancel(Request $request, string $id): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);

        $vacation = $this->vacationRepository->findById(VacationId::fromString($id));

        if (!$managerContributor || $vacation->getContributor()->getManager() !== $managerContributor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas acces a cette demande.');
        }

        if (!$this->isCsrfTokenValid('cancel-manager'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        try {
            ($this->cancelVacationHandler)(new CancelVacationCommand($id));
            $this->addFlash('success', 'La demande de conge a ete annulee.');
        } catch (InvalidStatusTransitionException) {
            $this->addFlash('error', 'Cette demande ne peut plus etre annulee.');
        }

        return $this->redirectToRoute('vacation_approval_index');
    }

    #[Route('/api/pending-count', name: 'vacation_approval_pending_count', methods: ['GET'])]
    public function pendingCount(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$managerContributor) {
            return $this->json(['count' => 0, 'vacations' => []]);
        }

        $managedContributors = $managerContributor->getManagedContributors();
        $pendingVacations = $this->vacationRepository->findPendingForContributors(
            $managedContributors->toArray(),
        );

        $vacationData = [];
        foreach (array_slice($pendingVacations, 0, 5) as $vacation) {
            $vacationData[] = [
                'id' => $vacation->getId()->getValue(),
                'contributor' => $vacation->getContributor()->getFullName(),
                'type' => $vacation->getTypeLabel(),
                'start_date' => $vacation->getStartDate()->format('d/m/Y'),
                'end_date' => $vacation->getEndDate()->format('d/m/Y'),
                'working_days' => $vacation->getNumberOfWorkingDays(),
                'url' => $this->generateUrl('vacation_approval_show', ['id' => $vacation->getId()->getValue()]),
            ];
        }

        return $this->json([
            'count' => count($pendingVacations),
            'vacations' => $vacationData,
        ]);
    }
}
