<?php

declare(strict_types=1);

namespace App\Presentation\Vacation\Controller;

use App\Application\Vacation\Command\CancelVacation\CancelVacationCommand;
use App\Application\Vacation\Command\CancelVacation\CancelVacationHandler;
use App\Application\Vacation\Command\RequestVacation\RequestVacationCommand;
use App\Application\Vacation\Command\RequestVacation\RequestVacationHandler;
use App\Domain\Vacation\Exception\InvalidStatusTransitionException;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Presentation\Vacation\Form\VacationRequestType;
use App\Repository\ContributorRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mes-conges')]
#[IsGranted('ROLE_INTERVENANT')]
class VacationRequestController extends AbstractController
{
    public function __construct(
        private readonly VacationRepositoryInterface $vacationRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly RequestVacationHandler $requestVacationHandler,
        private readonly CancelVacationHandler $cancelVacationHandler,
    ) {
    }

    #[Route('', name: 'vacation_request_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$contributor) {
            $this->addFlash('warning', 'Aucun profil collaborateur n\'est associe a votre compte.');

            return $this->redirectToRoute('home');
        }

        $vacations = $this->vacationRepository->findByContributor($contributor);

        return $this->render('vacation_request/index.html.twig', [
            'vacations' => $vacations,
            'contributor' => $contributor,
        ]);
    }

    #[Route('/nouvelle-demande', name: 'vacation_request_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$contributor) {
            $this->addFlash('error', 'Aucun profil collaborateur n\'est associe a votre compte.');

            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(VacationRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $command = new RequestVacationCommand(
                    contributorId: $contributor->getId(),
                    startDate: DateTimeImmutable::createFromInterface($data['startDate']),
                    endDate: DateTimeImmutable::createFromInterface($data['endDate']),
                    type: $data['type'],
                    dailyHours: (string) $data['dailyHours'],
                    reason: $data['reason'],
                );

                ($this->requestVacationHandler)($command);

                $this->addFlash('success', 'Votre demande de conge a ete enregistree avec succes. Elle est en attente de validation.');

                return $this->redirectToRoute('vacation_request_index');
            } catch (InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('vacation_request/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'vacation_request_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        // Use findByIdOrNull so the company-context filter swallowing a
        // foreign vacation surfaces as 403 (access denied) rather than as an
        // uncaught VacationNotFoundException — the latter would 500 and leak
        // implementation details in the response.
        $vacation = $this->vacationRepository->findByIdOrNull(VacationId::fromString($id));

        if (
            $vacation === null
            || !$contributor
            || $vacation->getContributor()->getId() !== $contributor->getId()
        ) {
            throw $this->createAccessDeniedException('Vous n\'avez pas acces a cette demande.');
        }

        return $this->render('vacation_request/show.html.twig', [
            'vacation' => $vacation,
        ]);
    }

    #[Route('/{id}/annuler', name: 'vacation_request_cancel', methods: ['POST'])]
    public function cancel(Request $request, string $id): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        $vacation = $this->vacationRepository->findById(VacationId::fromString($id));

        if (!$contributor || $vacation->getContributor()->getId() !== $contributor->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas acces a cette demande.');
        }

        if (!$this->isCsrfTokenValid('cancel'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        try {
            ($this->cancelVacationHandler)(new CancelVacationCommand($id));
            $this->addFlash('success', 'Votre demande de conge a ete annulee.');
        } catch (InvalidStatusTransitionException) {
            $this->addFlash('error', 'Seules les demandes en attente peuvent etre annulees.');
        }

        return $this->redirectToRoute('vacation_request_index');
    }
}
