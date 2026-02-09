<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use App\Entity\OrderPaymentSchedule;
use App\Entity\Project;
use App\Repository\InvoiceRepository;
use App\Repository\TimesheetRepository;
use App\Security\CompanyContext;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Service de génération automatique de factures.
 *
 * Génère des factures depuis :
 * - Devis forfait signés (OrderPaymentSchedule)
 * - Temps régie saisis mensuellement (Timesheets)
 */
class InvoiceGeneratorService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly TimesheetRepository $timesheetRepository,
        private readonly CompanyContext $companyContext,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Génère une facture depuis une échéance de paiement forfait.
     *
     * @param OrderPaymentSchedule $schedule Échéance à facturer
     * @param bool                 $autoSave Enregistrer automatiquement en BD
     *
     * @return Invoice La facture générée
     */
    public function generateFromOrderPaymentSchedule(OrderPaymentSchedule $schedule, bool $autoSave = true): Invoice
    {
        $order   = $schedule->getOrder();
        $project = $order->getProject();

        if (!$project) {
            throw new InvalidArgumentException('Order must be linked to a project to generate an invoice.');
        }

        $client = $project->getClient();
        if (!$client) {
            throw new InvalidArgumentException('Project must be linked to a client to generate an invoice.');
        }

        // Créer la facture
        $invoice = new Invoice();
        $invoice->setCompany($this->companyContext->getCurrentCompany());
        $invoice->setInvoiceNumber($this->invoiceRepository->generateNextInvoiceNumber($schedule->getBillingDate()));
        $invoice->setIssuedAt($schedule->getBillingDate());
        $invoice->setDueDate((clone $schedule->getBillingDate())->modify('+30 days')); // Échéance par défaut : 30 jours
        $invoice->setClient($client);
        $invoice->setProject($project);
        $invoice->setOrder($order);
        $invoice->setPaymentSchedule($schedule);
        $invoice->setStatus(Invoice::STATUS_DRAFT);

        // Calculer le montant de l'échéance
        $amountHt = $schedule->computeAmount($order->getTotalAmount());

        // Créer la ligne de facturation
        $line = new InvoiceLine();
        $line->setCompany($this->companyContext->getCurrentCompany());
        $line->setDescription(
            $schedule->getLabel() ?? sprintf(
                'Échéance %s - %s',
                $schedule->getBillingDate()->format('d/m/Y'),
                $order->getName() ?? $project->getName(),
            ),
        );
        $line->setQuantity('1.00');
        $line->setUnit('forfait');
        $line->setUnitPriceHt($amountHt);
        $line->setTvaRate($invoice->getTvaRate());
        $line->calculateAmounts();

        $invoice->addLine($line);

        // Calculer les totaux de la facture
        $invoice->setAmountHt($line->getTotalHt());
        $invoice->calculateAmounts();

        if ($autoSave) {
            $this->entityManager->persist($invoice);
            $this->entityManager->flush();

            $this->logger->info('Invoice generated from payment schedule', [
                'invoice_number'      => $invoice->getInvoiceNumber(),
                'payment_schedule_id' => $schedule->getId(),
                'order_id'            => $order->id,
                'amount_ht'           => $invoice->getAmountHt(),
            ]);
        }

        return $invoice;
    }

    /**
     * Génère une facture mensuelle régie depuis les temps saisis.
     *
     * @param Project           $project   Projet en régie
     * @param DateTimeInterface $startDate Début du mois (1er jour)
     * @param DateTimeInterface $endDate   Fin du mois (dernier jour)
     * @param bool              $autoSave  Enregistrer automatiquement en BD
     *
     * @return Invoice|null La facture générée, ou null si aucun temps saisi
     */
    public function generateMonthlyRegieInvoice(
        Project $project,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        bool $autoSave = true,
    ): ?Invoice {
        $client = $project->getClient();
        if (!$client) {
            throw new InvalidArgumentException('Project must be linked to a client to generate an invoice.');
        }

        // Vérifier que le projet est bien en régie
        if ($project->getProjectType() !== 'regie') {
            throw new InvalidArgumentException('Project must be of type "regie" to generate a monthly invoice.');
        }

        // Récupérer le CA mensuel (basé sur TJM contributeur)
        $monthlyRevenue = $this->timesheetRepository->getMonthlyRevenueForProjectUsingContributorTjm(
            $project,
            $startDate,
            $endDate,
        );

        if (empty($monthlyRevenue)) {
            $this->logger->warning('No timesheets found for regie invoice generation', [
                'project_id' => $project->getId(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date'   => $endDate->format('Y-m-d'),
            ]);

            return null;
        }

        $totalRevenue = '0.00';
        foreach ($monthlyRevenue as $row) {
            $totalRevenue = bcadd($totalRevenue, (string) $row['revenue'], 2);
        }

        if (bccomp($totalRevenue, '0.00', 2) === 0) {
            $this->logger->warning('Revenue is zero for regie invoice', [
                'project_id' => $project->getId(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date'   => $endDate->format('Y-m-d'),
            ]);

            return null;
        }

        // Récupérer les détails par contributeur pour les lignes de facturation
        $timesheetDetails = $this->timesheetRepository
            ->createQueryBuilder('t')
            ->select('c.id as contributor_id, c.firstName, c.lastName, SUM(t.hours) as totalHours, ep.tjm')
            ->join('t.contributor', 'c')
            ->leftJoin(
                \App\Entity\EmploymentPeriod::class,
                'ep',
                'WITH',
                'ep.contributor = c AND ep.startDate <= t.date AND (ep.endDate IS NULL OR ep.endDate >= t.date)',
            )
            ->where('t.project = :project')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->setParameter('project', $project)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('c.id, c.firstName, c.lastName, ep.tjm')
            ->getQuery()
            ->getResult();

        // Créer la facture
        $invoice = new Invoice();
        $invoice->setCompany($this->companyContext->getCurrentCompany());
        $invoice->setInvoiceNumber($this->invoiceRepository->generateNextInvoiceNumber($endDate));
        $invoice->setIssuedAt($endDate);
        $invoice->setDueDate((clone $endDate)->modify('+30 days')); // Échéance par défaut : 30 jours
        $invoice->setClient($client);
        $invoice->setProject($project);
        $invoice->setStatus(Invoice::STATUS_DRAFT);

        // Ordre d'affichage des lignes
        $displayOrder = 0;

        // Créer une ligne par contributeur
        foreach ($timesheetDetails as $detail) {
            $contributorName = trim("{$detail['firstName']} {$detail['lastName']}");
            $hours           = (float) $detail['totalHours'];
            $tjm             = $detail['tjm'] ?? '0.00';
            $days            = round($hours / 8, 2);

            // Calcul : montant HT = (heures × TJM) / 8
            $lineAmountHt = bcmul((string) $hours, bcdiv((string) $tjm, '8', 4), 2);

            $line = new InvoiceLine();
            $line->setCompany($this->companyContext->getCurrentCompany());
            $line->setDescription(sprintf(
                '%s - %s à %s (%s heures / %.2f jours)',
                $contributorName,
                $startDate->format('m/Y'),
                $project->getName(),
                number_format($hours, 2, ',', ' '),
                $days,
            ));
            $line->setQuantity((string) $days);
            $line->setUnit('jour');
            $line->setUnitPriceHt($tjm);
            $line->setTvaRate($invoice->getTvaRate());
            $line->calculateAmounts();
            $line->setDisplayOrder(++$displayOrder);

            $invoice->addLine($line);
        }

        // Calculer les totaux de la facture
        $totalHt  = '0.00';
        $totalTva = '0.00';
        $totalTtc = '0.00';

        foreach ($invoice->getLines() as $line) {
            $totalHt  = bcadd($totalHt, $line->getTotalHt(), 2);
            $totalTva = bcadd($totalTva, $line->getTvaAmount(), 2);
            $totalTtc = bcadd($totalTtc, $line->getTotalTtc(), 2);
        }

        $invoice->setAmountHt($totalHt);
        $invoice->setAmountTva($totalTva);
        $invoice->setAmountTtc($totalTtc);

        if ($autoSave) {
            $this->entityManager->persist($invoice);
            $this->entityManager->flush();

            $this->logger->info('Monthly regie invoice generated', [
                'invoice_number' => $invoice->getInvoiceNumber(),
                'project_id'     => $project->getId(),
                'amount_ht'      => $invoice->getAmountHt(),
                'period'         => sprintf('%s to %s', $startDate->format('Y-m-d'), $endDate->format('Y-m-d')),
            ]);
        }

        return $invoice;
    }

    /**
     * Génère toutes les factures régie mensuelles pour un mois donné.
     *
     * @param DateTimeInterface $month    Date dans le mois à facturer (utilise 1er et dernier jour)
     * @param bool              $autoSave Enregistrer automatiquement en BD
     *
     * @return Invoice[] Les factures générées
     */
    public function generateAllMonthlyRegieInvoices(DateTimeInterface $month, bool $autoSave = true): array
    {
        $startDate = (clone $month)->modify('first day of this month');
        $endDate   = (clone $month)->modify('last day of this month');

        // Récupérer tous les projets en régie actifs
        $regieProjects = $this->entityManager
            ->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->where('p.projectType = :type')
            ->andWhere('p.status = :status')
            ->setParameter('type', 'regie')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        $invoices = [];
        foreach ($regieProjects as $project) {
            try {
                $invoice = $this->generateMonthlyRegieInvoice($project, $startDate, $endDate, $autoSave);
                if ($invoice) {
                    $invoices[] = $invoice;
                }
            } catch (Exception $e) {
                $this->logger->error('Failed to generate regie invoice', [
                    'project_id' => $project->getId(),
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Batch regie invoice generation completed', [
            'month'            => $month->format('Y-m'),
            'invoices_created' => count($invoices),
        ]);

        return $invoices;
    }

    /**
     * Vérifie si une facture existe déjà pour une échéance donnée.
     */
    public function invoiceExistsForPaymentSchedule(OrderPaymentSchedule $schedule): bool
    {
        $count = $this->invoiceRepository
            ->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.paymentSchedule = :schedule')
            ->setParameter('schedule', $schedule)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Vérifie si une facture existe déjà pour un projet régie et un mois donné.
     */
    public function invoiceExistsForRegieMonth(Project $project, DateTimeInterface $month): bool
    {
        $startDate = (clone $month)->modify('first day of this month');
        $endDate   = (clone $month)->modify('last day of this month');

        $count = $this->invoiceRepository
            ->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.project = :project')
            ->andWhere('i.issuedAt BETWEEN :start AND :end')
            ->setParameter('project', $project)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
