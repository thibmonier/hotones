<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\ExpenseReport;
use App\Form\ExpenseReportType;
use App\Repository\ContributorRepository;
use App\Repository\ExpenseReportRepository;
use App\Security\CompanyContext;
use App\Service\ExpenseReportService;
use App\Service\SecureFileUploadService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/expense-reports')]
class ExpenseReportController extends AbstractController
{
    public function __construct(
        private readonly ExpenseReportRepository $expenseRepo,
        private readonly ContributorRepository $contributorRepo,
        private readonly ExpenseReportService $service,
        private readonly EntityManagerInterface $em,
        private readonly SecureFileUploadService $uploadService,
        private readonly CompanyContext $companyContext
    ) {
    }

    #[Route('', name: 'expense_report_index', methods: ['GET'])]
    #[IsGranted('ROLE_COMPTA')]
    public function index(Request $request): Response
    {
        $filters = [
            'status'     => $request->query->get('status'),
            'category'   => $request->query->get('category'),
            'rebillable' => $request->query->get('rebillable'),
        ];

        $expenses = $this->expenseRepo->findAllWithFilters($filters);

        return $this->render('expense_report/index.html.twig', [
            'expenses' => $expenses,
        ]);
    }

    #[Route('/mine', name: 'expense_report_mine', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mine(): Response
    {
        $user        = $this->getUser();
        $contributor = $this->contributorRepo->findOneBy(['user' => $user]);
        if (!$contributor instanceof Contributor) {
            throw $this->createAccessDeniedException('Aucun contributor lié à cet utilisateur.');
        }

        $expenses = $this->expenseRepo->findByContributor($contributor);

        return $this->render('expense_report/mine.html.twig', [
            'expenses' => $expenses,
        ]);
    }

    #[Route('/new', name: 'expense_report_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $user        = $this->getUser();
        $contributor = $this->contributorRepo->findOneBy(['user' => $user]);
        if (!$contributor instanceof Contributor) {
            throw $this->createAccessDeniedException('Aucun contributor lié à cet utilisateur.');
        }

        $expense = new ExpenseReport();
        $expense->setCompany($contributor->getCompany());
        $expense->setContributor($contributor);
        $form = $this->createForm(ExpenseReportType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $receiptFile */
            $receiptFile = $form->get('receiptFile')->getData();

            if ($receiptFile) {
                try {
                    $filename = $this->uploadService->uploadDocument($receiptFile, 'expenses');
                    $expense->setFilePath($filename);
                } catch (Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du justificatif : '.$e->getMessage());
                }
            }

            $this->em->persist($expense);
            $this->em->flush();

            $this->addFlash('success', 'Note de frais créée.');

            return $this->redirectToRoute('expense_report_mine');
        }

        return $this->render('expense_report/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/pending', name: 'expense_report_pending', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function pending(): Response
    {
        $expenses = $this->expenseRepo->findPending();

        return $this->render('expense_report/pending.html.twig', [
            'expenses' => $expenses,
        ]);
    }

    #[Route('/{id}', name: 'expense_report_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(ExpenseReport $expense): Response
    {
        // Vérifier que l'utilisateur peut voir cette note de frais
        $user        = $this->getUser();
        $contributor = $this->contributorRepo->findOneBy(['user' => $user]);

        // Le propriétaire ou un comptable peut voir
        if ($expense->getContributor() !== $contributor && !$this->isGranted('ROLE_COMPTA')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('expense_report/show.html.twig', [
            'expense' => $expense,
        ]);
    }

    #[Route('/{id}/edit', name: 'expense_report_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(ExpenseReport $expense, Request $request): Response
    {
        // Seul le propriétaire peut modifier et seulement si le statut est "draft"
        $user        = $this->getUser();
        $contributor = $this->contributorRepo->findOneBy(['user' => $user]);

        if ($expense->getContributor() !== $contributor) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres notes de frais.');
        }

        if (!$expense->isEditable()) {
            $this->addFlash('error', 'Cette note de frais ne peut plus être modifiée.');

            return $this->redirectToRoute('expense_report_mine');
        }

        $form = $this->createForm(ExpenseReportType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $receiptFile */
            $receiptFile = $form->get('receiptFile')->getData();

            if ($receiptFile) {
                try {
                    $filename = $this->uploadService->uploadDocument($receiptFile, 'expenses');
                    $expense->setFilePath($filename);
                } catch (Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du justificatif : '.$e->getMessage());
                }
            }

            $this->em->flush();
            $this->addFlash('success', 'Note de frais mise à jour.');

            return $this->redirectToRoute('expense_report_mine');
        }

        return $this->render('expense_report/edit.html.twig', [
            'expense' => $expense,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/submit', name: 'expense_report_submit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function submit(ExpenseReport $expense): Response
    {
        $this->service->submit($expense);
        $this->addFlash('success', 'Note de frais soumise pour validation.');

        return $this->redirectToRoute('expense_report_mine');
    }

    #[Route('/{id}/validate', name: 'expense_report_validate', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function validateExpense(ExpenseReport $expense, Request $request): Response
    {
        $comment = $request->request->get('comment');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $this->service->validate($expense, $user, $comment ? (string) $comment : null);
        $this->addFlash('success', 'Note de frais validée.');

        return $this->redirectToRoute('expense_report_pending');
    }

    #[Route('/{id}/reject', name: 'expense_report_reject', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function rejectExpense(ExpenseReport $expense, Request $request): Response
    {
        $comment = (string) $request->request->get('comment');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $this->service->reject($expense, $user, $comment);
        $this->addFlash('info', 'Note de frais rejetée.');

        return $this->redirectToRoute('expense_report_pending');
    }

    #[Route('/{id}/pay', name: 'expense_report_pay', methods: ['POST'])]
    #[IsGranted('ROLE_COMPTA')]
    public function markAsPaid(ExpenseReport $expense): Response
    {
        $this->service->markAsPaid($expense, new DateTime());
        $this->addFlash('success', 'Note de frais marquée comme payée.');

        return $this->redirectToRoute('expense_report_index');
    }
}
