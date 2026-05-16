<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Project\UseCase\CalculateProjectMargin;

use App\Application\Project\UseCase\CalculateProjectMargin\CalculateProjectMarginCommand;
use App\Application\Project\UseCase\CalculateProjectMargin\CalculateProjectMarginUseCase;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\Event\MarginThresholdExceededEvent;
use App\Domain\Project\Event\ProjectMarginRecalculatedEvent;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectType;
use App\Domain\WorkItem\Entity\WorkItem;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CalculateProjectMarginUseCaseTest extends TestCase
{
    public function testNoOpWhenProjectNotFound(): void
    {
        $projectRepo = $this->createMock(ProjectRepositoryInterface::class);
        $projectRepo->method('findByIdOrNull')->willReturn(null);
        $projectRepo->expects(self::never())->method('save');

        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $em = $this->makeStubEntityManager(0.0);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(static::stringContains('not found'));

        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects(self::never())->method('dispatch');

        $useCase = new CalculateProjectMarginUseCase($projectRepo, $workItemRepo, $em, $eventBus, $logger);

        $useCase->execute(new CalculateProjectMarginCommand(projectIdLegacy: 'legacy:42'));
    }

    public function testDispatchesEventWhenMarginBelowThreshold(): void
    {
        $project = $this->makeProject();

        $projectRepo = $this->createMock(ProjectRepositoryInterface::class);
        $projectRepo->method('findByIdOrNull')->willReturn($project);
        $projectRepo->expects(self::once())->method('save')->with($project);

        $workItem = $this->makeWorkItem(7.0, costRate: 100.0, billedRate: 0.0);
        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->method('findByProject')->willReturn([$workItem]);

        // facture total = 800 €, cout = 700 €, marge = 100/800 = 12.5 %
        // mais avec threshold 15 %, marge < 15 → event dispatched
        $em = $this->makeStubEntityManager(800.0);

        $logger = $this->createMock(LoggerInterface::class);
        $eventBus = $this->createMock(MessageBusInterface::class);
        // Sprint-026 US-117 T-117-03 : dispatch systématique de
        // ProjectMarginRecalculatedEvent + MarginThresholdExceededEvent quand sous seuil.
        $eventBus->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static fn (object $e): Envelope => new Envelope($e));

        $useCase = new CalculateProjectMarginUseCase($projectRepo, $workItemRepo, $em, $eventBus, $logger);

        $useCase->execute(new CalculateProjectMarginCommand(
            projectIdLegacy: (string) $project->getId(),
            thresholdPercent: 15.0,
        ));
    }

    public function testNoEventWhenMarginAboveThreshold(): void
    {
        $project = $this->makeProject();

        $projectRepo = $this->createMock(ProjectRepositoryInterface::class);
        $projectRepo->method('findByIdOrNull')->willReturn($project);

        $workItem = $this->makeWorkItem(5.0, costRate: 100.0, billedRate: 0.0);
        $workItemRepo = $this->createMock(WorkItemRepositoryInterface::class);
        $workItemRepo->method('findByProject')->willReturn([$workItem]);

        // facture = 1000 €, cout = 500 €, marge = 500/1000 = 50 % > 10 %
        $em = $this->makeStubEntityManager(1000.0);

        $logger = $this->createMock(LoggerInterface::class);
        $eventBus = $this->createMock(MessageBusInterface::class);
        // Sprint-026 US-117 T-117-03 : ProjectMarginRecalculatedEvent dispatché
        // systématiquement (1×), pas de MarginThresholdExceededEvent au-dessus du seuil.
        $eventBus->expects(self::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(ProjectMarginRecalculatedEvent::class))
            ->willReturnCallback(static fn (object $e): Envelope => new Envelope($e));

        $useCase = new CalculateProjectMarginUseCase($projectRepo, $workItemRepo, $em, $eventBus, $logger);

        $useCase->execute(new CalculateProjectMarginCommand(
            projectIdLegacy: (string) $project->getId(),
            thresholdPercent: 10.0,
        ));
    }

    private function makeProject(): Project
    {
        return Project::create(
            id: ProjectId::generate(),
            name: 'Test Project',
            clientId: ClientId::generate(),
            projectType: ProjectType::FORFAIT,
        );
    }

    private function makeWorkItem(float $hours, float $costRate, float $billedRate): WorkItem
    {
        return WorkItem::create(
            id: WorkItemId::generate(),
            projectId: ProjectId::generate(),
            contributorId: ContributorId::fromLegacyInt(42),
            workedOn: new DateTimeImmutable('2026-05-12'),
            hours: WorkedHours::fromFloat($hours),
            costRate: HourlyRate::fromAmount($costRate),
            billedRate: HourlyRate::fromAmount(max($billedRate, 0.01)),
        );
    }

    /**
     * Stub EntityManager that returns the given facture total (€) for the
     * Invoice query. Uses Doctrine QueryBuilder mock chain.
     */
    private function makeStubEntityManager(float $factureTotalEuros): EntityManagerInterface
    {
        $factureDecimalString = number_format($factureTotalEuros, 2, '.', '');

        // Query is final → mock directly
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSingleScalarResult'])
            ->getMock();
        $query->method('getSingleScalarResult')->willReturn($factureDecimalString);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        return $em;
    }
}
