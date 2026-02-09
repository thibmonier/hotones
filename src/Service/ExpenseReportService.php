<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ExpenseReport;
use App\Entity\Order;
use App\Entity\User;
use App\Security\CompanyContext;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class ExpenseReportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyContext $companyContext,
    ) {
    }

    /**
     * Crée une nouvelle note de frais.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, User $user): ExpenseReport
    {
        $expense = new ExpenseReport();
        $expense->setCompany($this->companyContext->getCurrentCompany());

        // Définir le contributeur
        if (isset($data['contributor'])) {
            $expense->setContributor($data['contributor']);
        }

        // Définir les champs obligatoires
        if (isset($data['expense_date'])) {
            $expense->setExpenseDate($data['expense_date']);
        }

        if (isset($data['category'])) {
            $expense->setCategory($data['category']);
        }

        if (isset($data['description'])) {
            $expense->setDescription($data['description']);
        }

        if (isset($data['amount_ht'])) {
            $expense->setAmountHT($data['amount_ht']);
        }

        if (isset($data['vat_rate'])) {
            $expense->setVatRate($data['vat_rate']);
        }

        // Champs optionnels
        if (isset($data['project'])) {
            $expense->setProject($data['project']);
        }

        if (isset($data['order'])) {
            $expense->setOrder($data['order']);
        }

        if (isset($data['file_path'])) {
            $expense->setFilePath($data['file_path']);
        }

        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        return $expense;
    }

    /**
     * Soumet une note de frais pour validation (brouillon → en_attente).
     */
    public function submit(ExpenseReport $expense): void
    {
        if (!$expense->canBeSubmitted()) {
            throw new LogicException('Cette note de frais ne peut pas être soumise.');
        }

        $expense->setStatus(ExpenseReport::STATUS_PENDING);
        $this->entityManager->flush();

        // TODO: Envoyer une notification au validateur
    }

    /**
     * Valide une note de frais.
     */
    public function validate(ExpenseReport $expense, User $validator, ?string $comment = null): void
    {
        if (!$expense->canBeValidated()) {
            throw new LogicException('Cette note de frais ne peut pas être validée.');
        }

        $expense->setStatus(ExpenseReport::STATUS_VALIDATED);
        $expense->setValidator($validator);
        $expense->setValidatedAt(new DateTime());

        if ($comment) {
            $expense->setValidationComment($comment);
        }

        $this->entityManager->flush();

        // TODO: Envoyer une notification au contributeur
    }

    /**
     * Rejette une note de frais.
     */
    public function reject(ExpenseReport $expense, User $validator, string $comment): void
    {
        if (!$expense->canBeRejected()) {
            throw new LogicException('Cette note de frais ne peut pas être rejetée.');
        }

        $expense->setStatus(ExpenseReport::STATUS_REJECTED);
        $expense->setValidator($validator);
        $expense->setValidatedAt(new DateTime());
        $expense->setValidationComment($comment);

        $this->entityManager->flush();

        // TODO: Envoyer une notification au contributeur
    }

    /**
     * Marque une note de frais comme payée.
     *
     * @param array<string, mixed> $paymentData
     */
    public function markAsPaid(ExpenseReport $expense, DateTimeInterface $paidAt, array $paymentData = []): void
    {
        if (!$expense->canBeMarkedAsPaid()) {
            throw new LogicException('Cette note de frais ne peut pas être marquée comme payée.');
        }

        $expense->setStatus(ExpenseReport::STATUS_PAID);
        $expense->setPaidAt($paidAt);

        // TODO: Stocker les données de paiement (mode, référence) si nécessaire

        $this->entityManager->flush();

        // TODO: Envoyer une notification de confirmation au contributeur
    }

    /**
     * Calcule le montant total refacturable pour un devis.
     */
    public function calculateRebillableAmount(Order $order): string
    {
        if (!$order->isExpensesRebillable()) {
            return '0.00';
        }

        return $order->getTotalRebillableExpenses();
    }

    /**
     * Met à jour une note de frais (uniquement si en brouillon).
     *
     * @param array<string, mixed> $data
     */
    public function update(ExpenseReport $expense, array $data): ExpenseReport
    {
        if (!$expense->isEditable()) {
            throw new LogicException('Cette note de frais ne peut plus être modifiée.');
        }

        // Mettre à jour les champs modifiables
        if (isset($data['expense_date'])) {
            $expense->setExpenseDate($data['expense_date']);
        }

        if (isset($data['category'])) {
            $expense->setCategory($data['category']);
        }

        if (isset($data['description'])) {
            $expense->setDescription($data['description']);
        }

        if (isset($data['amount_ht'])) {
            $expense->setAmountHT($data['amount_ht']);
        }

        if (isset($data['vat_rate'])) {
            $expense->setVatRate($data['vat_rate']);
        }

        if (isset($data['project'])) {
            $expense->setProject($data['project']);
        }

        if (isset($data['order'])) {
            $expense->setOrder($data['order']);
        }

        if (isset($data['file_path'])) {
            $expense->setFilePath($data['file_path']);
        }

        $this->entityManager->flush();

        return $expense;
    }

    /**
     * Supprime une note de frais (uniquement si en brouillon).
     */
    public function delete(ExpenseReport $expense): void
    {
        if (!$expense->isEditable()) {
            throw new LogicException('Cette note de frais ne peut plus être supprimée.');
        }

        $this->entityManager->remove($expense);
        $this->entityManager->flush();
    }

    /**
     * Validation en masse de notes de frais.
     *
     * @param array<ExpenseReport> $expenses
     */
    public function validateBatch(array $expenses, User $validator, ?string $comment = null): int
    {
        $count = 0;

        foreach ($expenses as $expense) {
            if ($expense->canBeValidated()) {
                $this->validate($expense, $validator, $comment);
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Calcule les statistiques d'un contributeur sur une période.
     *
     * @return array<string, mixed>
     */
    public function calculateContributorStats(
        int $contributorId,
        DateTimeInterface $start,
        DateTimeInterface $end,
    ): array {
        $company = $this->companyContext->getCurrentCompany();
        $qb      = $this->entityManager->createQueryBuilder();

        // Total des frais
        $total = $qb
            ->select('SUM(e.amountTTC)')
            ->from(ExpenseReport::class, 'e')
            ->where('e.contributor = :contributor')
            ->andWhere('e.expenseDate >= :start')
            ->andWhere('e.expenseDate <= :end')
            ->andWhere('e.company = :company')
            ->setParameter('contributor', $contributorId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();

        // Total validé
        $validated = $this->entityManager
            ->createQueryBuilder()
            ->select('SUM(e.amountTTC)')
            ->from(ExpenseReport::class, 'e')
            ->where('e.contributor = :contributor')
            ->andWhere('e.expenseDate >= :start')
            ->andWhere('e.expenseDate <= :end')
            ->andWhere('e.status = :status')
            ->andWhere('e.company = :company')
            ->setParameter('contributor', $contributorId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', ExpenseReport::STATUS_VALIDATED)
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();

        // Répartition par catégorie
        $byCategory = $this->entityManager
            ->createQueryBuilder()
            ->select('e.category', 'SUM(e.amountTTC) as total')
            ->from(ExpenseReport::class, 'e')
            ->where('e.contributor = :contributor')
            ->andWhere('e.expenseDate >= :start')
            ->andWhere('e.expenseDate <= :end')
            ->andWhere('e.company = :company')
            ->setParameter('contributor', $contributorId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('company', $company)
            ->groupBy('e.category')
            ->getQuery()
            ->getResult();

        return [
            'total'       => $total ? (string) $total : '0.00',
            'validated'   => $validated ? (string) $validated : '0.00',
            'by_category' => $byCategory,
        ];
    }
}
