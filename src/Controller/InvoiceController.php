<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Invoice;
use App\Entity\Project;
use App\Form\InvoiceType;
use App\Repository\InvoiceRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/invoices')]
#[IsGranted('ROLE_USER')]
class InvoiceController extends AbstractController
{
    #[Route('', name: 'invoice_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);

        if ($reset && $session) {
            $session->remove('invoice_filters');

            return $this->redirectToRoute('invoice_index');
        }

        $queryAll   = $request->query->all();
        $filterKeys = ['client', 'project', 'status', 'start_date', 'end_date'];
        $hasFilter  = count(array_intersect(array_keys($queryAll), $filterKeys)) > 0;
        $saved      = ($session && $session->has('invoice_filters')) ? (array) $session->get('invoice_filters') : [];

        $clientId  = $hasFilter ? ($request->query->get('client') ?? null) : ($saved['client'] ?? null);
        $projectId = $hasFilter ? ($request->query->get('project') ?? null) : ($saved['project'] ?? null);
        $status    = $hasFilter ? ($request->query->get('status') ?? null) : ($saved['status'] ?? null);
        $startDate = $hasFilter ? ($request->query->get('start_date') ?? null) : ($saved['start_date'] ?? null);
        $endDate   = $hasFilter ? ($request->query->get('end_date') ?? null) : ($saved['end_date'] ?? null);

        $client  = $clientId ? $em->getRepository(Client::class)->find($clientId) : null;
        $project = $projectId ? $em->getRepository(Project::class)->find($projectId) : null;

        // Tri
        $sort = $hasFilter ? ($request->query->get('sort') ?? ($saved['sort'] ?? 'issuedAt')) : ($saved['sort'] ?? 'issuedAt');
        $dir  = $hasFilter ? ($request->query->get('dir') ?? ($saved['dir'] ?? 'DESC')) : ($saved['dir'] ?? 'DESC');

        // Pagination
        $allowedPerPage = [10, 20, 50, 100];
        $perPageParam   = (int) $request->query->get('per_page', $saved['per_page'] ?? 20);
        $perPage        = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 20;
        $page           = max(1, (int) $request->query->get('page', 1));
        $offset         = ($page - 1) * $perPage;

        // Requête avec filtres
        $qb = $em->getRepository(Invoice::class)->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->leftJoin('i.project', 'p')
            ->addSelect('c', 'p');

        if ($client) {
            $qb->andWhere('i.client = :client')->setParameter('client', $client);
        }
        if ($project) {
            $qb->andWhere('i.project = :project')->setParameter('project', $project);
        }
        if ($status) {
            $qb->andWhere('i.status = :status')->setParameter('status', $status);
        }
        if ($startDate) {
            $qb->andWhere('i.issuedAt >= :startDate')->setParameter('startDate', new DateTime($startDate));
        }
        if ($endDate) {
            $qb->andWhere('i.issuedAt <= :endDate')->setParameter('endDate', new DateTime($endDate));
        }

        // Tri
        $sortField = match ($sort) {
            'invoiceNumber' => 'i.invoiceNumber',
            'client'        => 'c.name',
            'project'       => 'p.name',
            'status'        => 'i.status',
            'dueDate'       => 'i.dueDate',
            'amountHt'      => 'i.amountHt',
            'amountTtc'     => 'i.amountTtc',
            default         => 'i.issuedAt',
        };
        $qb->orderBy($sortField, strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC');

        // Total - recréer les joins pour le comptage
        $qbCount = $em->createQueryBuilder()
            ->select('COUNT(i2.id)')
            ->from(Invoice::class, 'i2')
            ->leftJoin('i2.client', 'c2')
            ->leftJoin('i2.project', 'p2');

        if ($client) {
            $qbCount->andWhere('i2.client = :client')->setParameter('client', $client);
        }
        if ($project) {
            $qbCount->andWhere('i2.project = :project')->setParameter('project', $project);
        }
        if ($status) {
            $qbCount->andWhere('i2.status = :status')->setParameter('status', $status);
        }
        if ($startDate) {
            $qbCount->andWhere('i2.issuedAt >= :startDate')->setParameter('startDate', new DateTime($startDate));
        }
        if ($endDate) {
            $qbCount->andWhere('i2.issuedAt <= :endDate')->setParameter('endDate', new DateTime($endDate));
        }

        $total = (int) $qbCount->getQuery()->getSingleScalarResult();

        // Résultats paginés
        $invoices = $qb->setMaxResults($perPage)->setFirstResult($offset)->getQuery()->getResult();

        // Clients et projets pour les filtres
        $clients  = $em->getRepository(Client::class)->findBy([], ['name' => 'ASC']);
        $projects = $em->getRepository(Project::class)->findBy([], ['name' => 'ASC']);

        $pagination = [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'total_pages'  => (int) ceil($total / $perPage),
            'has_prev'     => $page > 1,
            'has_next'     => $page * $perPage < $total,
        ];

        // Sauvegarder les filtres
        if ($session) {
            $session->set('invoice_filters', [
                'client'     => $clientId,
                'project'    => $projectId,
                'status'     => $status,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'sort'       => $sort,
                'dir'        => strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC',
                'per_page'   => $perPage,
            ]);
        }

        return $this->render('invoice/index.html.twig', [
            'invoices'        => $invoices,
            'clients'         => $clients,
            'projects'        => $projects,
            'selectedClient'  => $clientId,
            'selectedProject' => $projectId,
            'selectedStatus'  => $status,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'statusOptions'   => Invoice::STATUS_OPTIONS,
            'filters_query'   => [
                'client'     => $clientId,
                'project'    => $projectId,
                'status'     => $status,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'sort'       => $sort,
                'dir'        => $dir,
                'per_page'   => $perPage,
            ],
            'sort'       => $sort,
            'dir'        => strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC',
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'invoice_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMPTA')]
    public function new(Request $request, EntityManagerInterface $em, InvoiceRepository $invoiceRepository): Response
    {
        $invoice = new Invoice();

        // Pré-remplir si client ou projet fourni dans l'URL
        if ($clientId = $request->query->get('client')) {
            $client = $em->getRepository(Client::class)->find($clientId);
            if ($client) {
                $invoice->setClient($client);
            }
        }

        if ($projectId = $request->query->get('project')) {
            $project = $em->getRepository(Project::class)->find($projectId);
            if ($project) {
                $invoice->setProject($project);
                if (!$invoice->getClient() && $project->getClient()) {
                    $invoice->setClient($project->getClient());
                }
            }
        }

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer le numéro de facture
            $invoice->setInvoiceNumber($invoiceRepository->generateNextInvoiceNumber($invoice->getIssuedAt()));

            // Calculer les montants
            $invoice->calculateAmounts();

            $em->persist($invoice);
            $em->flush();

            $this->addFlash('success', sprintf('Facture %s créée avec succès', $invoice->getInvoiceNumber()));

            return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/new.html.twig', [
            'invoice' => $invoice,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'invoice_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $invoice = $em->getRepository(Invoice::class)->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->leftJoin('i.project', 'p')
            ->leftJoin('i.order', 'o')
            ->leftJoin('i.lines', 'l')
            ->addSelect('c', 'p', 'o', 'l')
            ->where('i.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$invoice) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        return $this->render('invoice/show.html.twig', [
            'invoice'       => $invoice,
            'statusOptions' => Invoice::STATUS_OPTIONS,
        ]);
    }

    #[Route('/{id}/edit', name: 'invoice_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMPTA')]
    public function edit(Request $request, int $id, EntityManagerInterface $em): Response
    {
        $invoice = $em->getRepository(Invoice::class)->find($id);

        if (!$invoice) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        // Seules les factures en brouillon peuvent être éditées
        if ($invoice->getStatus() !== Invoice::STATUS_DRAFT) {
            $this->addFlash('error', 'Seules les factures en brouillon peuvent être modifiées');

            return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
        }

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoice->calculateAmounts();
            $em->flush();

            $this->addFlash('success', 'Facture modifiée avec succès');

            return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/edit.html.twig', [
            'invoice' => $invoice,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'invoice_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Invoice $invoice, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
        }

        // Seules les factures en brouillon peuvent être supprimées
        if ($invoice->getStatus() !== Invoice::STATUS_DRAFT) {
            $this->addFlash('error', 'Seules les factures en brouillon peuvent être supprimées');

            return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
        }

        $invoiceNumber = $invoice->getInvoiceNumber();
        $em->remove($invoice);
        $em->flush();

        $this->addFlash('success', sprintf('Facture %s supprimée avec succès', $invoiceNumber));

        return $this->redirectToRoute('invoice_index');
    }

    #[Route('/{id}/status', name: 'invoice_update_status', methods: ['POST'])]
    #[IsGranted('ROLE_COMPTA')]
    public function updateStatus(Request $request, Invoice $invoice, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('status'.$invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action non autorisée (CSRF)');

            return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
        }

        $status = (string) $request->request->get('status');
        if (!array_key_exists($status, Invoice::STATUS_OPTIONS)) {
            $this->addFlash('danger', 'Statut invalide');

            return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
        }

        $invoice->setStatus($status);

        // Si marquée comme payée, enregistrer la date de paiement
        if ($status === Invoice::STATUS_PAID && !$invoice->getPaidAt()) {
            $paidAtStr = $request->request->get('paid_at');
            $paidAt    = $paidAtStr ? new DateTime($paidAtStr) : new DateTime();
            $invoice->setPaidAt($paidAt);
        }

        $em->flush();

        $this->addFlash('success', 'Statut de la facture mis à jour');

        return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/pdf', name: 'invoice_pdf', methods: ['GET'])]
    public function pdf(Invoice $invoice, \App\Service\PdfGeneratorService $pdfGenerator): Response
    {
        // Données pour le template PDF
        $data = [
            'invoice'         => $invoice,
            'company_name'    => 'HotOnes Agency',
            'company_address' => 'Adresse de l\'entreprise',
            'company_postal'  => 'Code postal',
            'company_city'    => 'Ville',
            'company_phone'   => '01 23 45 67 89',
            'company_email'   => 'contact@hotones.com',
            'company_legal'   => 'SIRET: XXX XXX XXX XXXXX - TVA: FR XX XXX XXX XXX',
            'company_capital' => 'Capital social: 10 000 € - RCS Paris B XXX XXX XXX',
            'company_iban'    => 'FR76 XXXX XXXX XXXX XXXX XXXX XXX',
            'company_bic'     => 'XXXXFRPPXXX',
        ];

        // Générer le nom du fichier
        $filename = sprintf(
            'facture_%s_%s.pdf',
            $invoice->getInvoiceNumber(),
            (new DateTime())->format('Y-m-d'),
        );

        // Générer et retourner le PDF
        return $pdfGenerator->createPdfResponse(
            'invoice/pdf.html.twig',
            $data,
            $filename,
            inline: false, // Force le téléchargement
        );
    }

    #[Route('/{id}/pdf/preview', name: 'invoice_pdf_preview', methods: ['GET'])]
    public function pdfPreview(Invoice $invoice, \App\Service\PdfGeneratorService $pdfGenerator): Response
    {
        // Données pour le template PDF
        $data = [
            'invoice'         => $invoice,
            'company_name'    => 'HotOnes Agency',
            'company_address' => 'Adresse de l\'entreprise',
            'company_postal'  => 'Code postal',
            'company_city'    => 'Ville',
            'company_phone'   => '01 23 45 67 89',
            'company_email'   => 'contact@hotones.com',
            'company_legal'   => 'SIRET: XXX XXX XXX XXXXX - TVA: FR XX XXX XXX XXX',
            'company_capital' => 'Capital social: 10 000 € - RCS Paris B XXX XXX XXX',
            'company_iban'    => 'FR76 XXXX XXXX XXXX XXXX XXXX XXX',
            'company_bic'     => 'XXXXFRPPXXX',
        ];

        $filename = sprintf('facture_%s_preview.pdf', $invoice->getInvoiceNumber());

        // Affiche le PDF dans le navigateur
        return $pdfGenerator->createPdfResponse(
            'invoice/pdf.html.twig',
            $data,
            $filename,
            inline: true, // Affichage dans le navigateur
        );
    }
}
