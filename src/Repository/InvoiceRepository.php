<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Invoice;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Génère le prochain numéro de facture pour un mois donné.
     * Format : F[année][mois][incrément sur 3 chiffres].
     * Exemple : F202501001, F202501002, etc.
     */
    public function generateNextInvoiceNumber(DateTimeInterface $date): string
    {
        $year   = $date->format('Y');
        $month  = $date->format('m');
        $prefix = sprintf('F%s%s', $year, $month);

        // Récupérer le dernier numéro du mois
        $qb = $this->createQueryBuilder('i');
        $qb->select('i.invoiceNumber')
            ->where('i.invoiceNumber LIKE :prefix')
            ->setParameter('prefix', $prefix.'%')
            ->orderBy('i.invoiceNumber', 'DESC')
            ->setMaxResults(1);

        $lastInvoiceNumber = $qb->getQuery()->getOneOrNullResult();

        if ($lastInvoiceNumber === null) {
            // Premier numéro du mois
            return $prefix.'001';
        }

        // Extraire l'incrément et ajouter 1
        $lastNumber = (int) substr($lastInvoiceNumber['invoiceNumber'], -3);
        $nextNumber = $lastNumber + 1;

        return $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Récupère les factures en retard (non payées après la date d'échéance).
     *
     * @return Invoice[]
     */
    public function findOverdueInvoices(): array
    {
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.status != :statusPaid')
            ->andWhere('i.status != :statusCancelled')
            ->andWhere('i.dueDate < CURRENT_DATE()')
            ->setParameter('statusPaid', Invoice::STATUS_PAID)
            ->setParameter('statusCancelled', Invoice::STATUS_CANCELLED)
            ->orderBy('i.dueDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les factures à échéance proche (dans les N jours).
     *
     * @return Invoice[]
     */
    public function findUpcomingInvoices(int $days = 30): array
    {
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.status = :statusSent')
            ->andWhere('i.dueDate BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), :days, \'day\')')
            ->setParameter('statusSent', Invoice::STATUS_SENT)
            ->setParameter('days', $days)
            ->orderBy('i.dueDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les factures par statut.
     *
     * @return Invoice[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->setParameter('status', $status)
            ->orderBy('i.issuedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les factures d'un client.
     *
     * @return Invoice[]
     */
    public function findByClient(int $clientId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('i.issuedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le CA facturé pour une période.
     */
    public function calculateTotalRevenue(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): string
    {
        $qb = $this->createQueryBuilder('i');
        $qb->select('SUM(i.amountHt) as total')
            ->where('i.status IN (:statuses)')
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_PAID, Invoice::STATUS_OVERDUE]);

        if ($startDate) {
            $qb->andWhere('i.issuedAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('i.issuedAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (string) $result : '0.00';
    }

    /**
     * Calcule le CA encaissé pour une période.
     */
    public function calculatePaidRevenue(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): string
    {
        $qb = $this->createQueryBuilder('i');
        $qb->select('SUM(i.amountHt) as total')
            ->where('i.status = :statusPaid')
            ->setParameter('statusPaid', Invoice::STATUS_PAID);

        if ($startDate) {
            $qb->andWhere('i.paidAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('i.paidAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (string) $result : '0.00';
    }

    /**
     * Calcule le délai moyen de paiement en jours.
     */
    public function calculateAveragePaymentDelay(): float
    {
        $qb = $this->createQueryBuilder('i');
        $qb->select('AVG(DATEDIFF(i.paidAt, i.issuedAt)) as avgDelay')
            ->where('i.status = :statusPaid')
            ->setParameter('statusPaid', Invoice::STATUS_PAID);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Récupère les factures nécessitant une relance.
     * Relance J+30, J+45, J+60 après l'échéance.
     *
     * @return Invoice[]
     */
    public function findInvoicesNeedingReminder(): array
    {
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.status = :statusSent OR i.status = :statusOverdue')
            ->andWhere('i.dueDate < CURRENT_DATE()')
            ->andWhere('(DATEDIFF(CURRENT_DATE(), i.dueDate) = 30 OR DATEDIFF(CURRENT_DATE(), i.dueDate) = 45 OR DATEDIFF(CURRENT_DATE(), i.dueDate) = 60)')
            ->setParameter('statusSent', Invoice::STATUS_SENT)
            ->setParameter('statusOverdue', Invoice::STATUS_OVERDUE)
            ->orderBy('i.dueDate', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
