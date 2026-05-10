<?php

declare(strict_types=1);

namespace App\Application\Project\UseCase\CalculateProjectMargin;

use App\Domain\Project\Event\MarginThresholdExceededEvent;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use App\Entity\Invoice as FlatInvoice;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * EPIC-003 Phase 3 (sprint-022 US-104 ADR-0016 A-8) — UC `CalculateProjectMargin`.
 *
 * Orchestration :
 * 1. Load Project via ProjectRepositoryInterface
 * 2. Load WorkItems via WorkItemRepositoryInterface (sum WorkItem.cost)
 * 3. Load Invoices payées via flat repository (sum Invoice.totalAmount where
 *    status = 'paid' for project) — Invoice DDD pas couplé Project Domain
 *    sprint-022, ACL via flat entity
 * 4. Project::setMargeSnapshot(coutTotal, factureTotal) — fige snapshot
 * 5. Save Project
 * 6. Si marge < threshold → dispatch `MarginThresholdExceededEvent` US-103
 *    (handler async `SendMarginAlertOnThresholdExceeded` consume + Slack alert)
 *
 * Idempotent : recalcul produit même résultat sauf changement WorkItems/Invoices.
 *
 * Pattern strangler fig (AT-3.3 ADR-0016) — UC dispatch nouveau Domain Event,
 * legacy `LowMarginAlertEvent` supprimé sprint-022 US-105 (refactor
 * AlertDetectionService).
 */
final readonly class CalculateProjectMarginUseCase
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private WorkItemRepositoryInterface $workItemRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $eventBus,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(CalculateProjectMarginCommand $command): void
    {
        $projectId = ProjectId::fromString($command->projectIdLegacy);
        $project = $this->projectRepository->findByIdOrNull($projectId);

        if ($project === null) {
            $this->logger->warning('Project not found for margin calculation', [
                'project_id' => $command->projectIdLegacy,
            ]);

            return;
        }

        $coutTotal = $this->calculateCoutTotal($projectId);
        $factureTotal = $this->calculateFactureTotal($command->projectIdLegacy);

        $project->setMargeSnapshot($coutTotal, $factureTotal);
        $this->projectRepository->save($project);

        $this->logger->info('Project margin calculated', [
            'project_id' => $command->projectIdLegacy,
            'cout_total_eur' => $coutTotal->getAmount(),
            'facture_total_eur' => $factureTotal->getAmount(),
            'marge_percent' => $project->getMargePercent(),
            'threshold_percent' => $command->thresholdPercent,
        ]);

        if ($project->hasMargeBelowThreshold($command->thresholdPercent)) {
            $marge = $project->getMargePercent() ?? 0.0;
            $event = MarginThresholdExceededEvent::create(
                projectId: $projectId,
                projectName: $project->getName(),
                costTotal: $coutTotal,
                invoicedPaidTotal: $factureTotal,
                marginPercent: $marge,
                thresholdPercent: $command->thresholdPercent,
            );
            $this->eventBus->dispatch($event);
        }
    }

    private function calculateCoutTotal(ProjectId $projectId): Money
    {
        $workItems = $this->workItemRepository->findByProject($projectId);

        $total = Money::zero();
        foreach ($workItems as $workItem) {
            $total = $total->add($workItem->cost());
        }

        return $total;
    }

    /**
     * Sum invoice paid amounts (TTC) for project. Uses legacy flat Invoice
     * entity (Invoice ↔ Project link not in Domain Phase 3 sprint-022).
     *
     * `amountTtc` est decimal string (ex '1234.56') — cast float pour somme.
     */
    private function calculateFactureTotal(string $projectIdLegacy): Money
    {
        $sumDecimalString = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(i.amountTtc), 0) AS total_decimal')
            ->from(FlatInvoice::class, 'i')
            ->andWhere('i.project = :projectId')
            ->andWhere('i.status = :status')
            ->setParameter('projectId', $projectIdLegacy)
            ->setParameter('status', 'paid')
            ->getQuery()
            ->getSingleScalarResult();

        $sumFloat = (float) (is_string($sumDecimalString) ? $sumDecimalString : '0');

        return Money::fromAmount($sumFloat);
    }
}
