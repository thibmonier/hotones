<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Client;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderSection;
use App\Entity\Profile;
use App\Repository\ContributorRepository;
use App\Repository\OrderRepository;
use App\Service\WorkloadPredictionService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Basic unit tests for WorkloadPredictionService.
 * Note: Full integration tests should be created in tests/Integration/.
 */
#[AllowMockObjectsWithoutExpectations]
class WorkloadPredictionServiceTest extends TestCase
{
    public function testAnalyzePipelineReturnsCorrectStructure(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $contributorRepository = $this->createStub(ContributorRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $orderRepository->expects($this->once())->method('findBy')->with(['status' => 'a_signer'])->willReturn([]);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository, $entityManager);
        $result = $service->analyzePipeline();

        // Check structure
        static::assertIsArray($result);
        static::assertArrayHasKey('pipeline', $result);
        static::assertArrayHasKey('workloadByMonth', $result);
        static::assertArrayHasKey('alerts', $result);
        static::assertArrayHasKey('totalPotentialDays', $result);
    }

    public function testAnalyzePipelineAcceptsFilters(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $contributorRepository = $this->createStub(ContributorRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $orderRepository->expects($this->once())->method('findBy')->willReturn([]);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository, $entityManager);

        // Should accept profile and contributor filters without error
        $result = $service->analyzePipeline([1, 2], [5, 6]);

        static::assertIsArray($result);
        static::assertArrayHasKey('pipeline', $result);
    }

    public function testAnalyzePipelineWithOrdersContainingLines(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $contributorRepository = $this->createStub(ContributorRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $profile = new Profile();
        $profile->setName('Développeur Frontend');

        $line = new OrderLine();
        $line->setDays('15.5');
        $line->setDailyRate('500');
        $line->setProfile($profile);

        $section = new OrderSection();
        $section->setTitle('Phase 1');
        $section->addLine($line);

        $client = new Client();
        $client->setName('Test Client');

        $order = new Order();
        $order->setOrderNumber('DEV-2025-001');
        $order->setName('Projet Test');
        $order->setStatus('a_signer');
        $order->setCreatedAt(new DateTimeImmutable());
        $order->addSection($section);

        $orderRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'a_signer'])
            ->willReturn([$order]);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository, $entityManager);
        $result = $service->analyzePipeline();

        static::assertNotEmpty($result['pipeline']);
        static::assertGreaterThan(0, $result['totalPotentialDays']);
        // Note: actual calculation may include conversion probability adjustments
        static::assertGreaterThanOrEqual(10, $result['totalPotentialDays']);
    }

    public function testAnalyzePipelineFiltersOrdersByProfile(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $contributorRepository = $this->createStub(ContributorRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $profile1 = new Profile();
        $profile1->setName('Développeur');
        $reflectionProfile1 = new ReflectionClass($profile1);
        $idProperty = $reflectionProfile1->getProperty('id');
        $idProperty->setValue($profile1, 1);

        $profile2 = new Profile();
        $profile2->setName('Designer');
        $reflectionProfile2 = new ReflectionClass($profile2);
        $idProperty = $reflectionProfile2->getProperty('id');
        $idProperty->setValue($profile2, 2);

        $line1 = new OrderLine();
        $line1->setDays('10');
        $line1->setDailyRate('500');
        $line1->setProfile($profile1);

        $line2 = new OrderLine();
        $line2->setDays('5');
        $line2->setDailyRate('600');
        $line2->setProfile($profile2);

        $section = new OrderSection();
        $section->setTitle('Phase 1');
        $section->addLine($line1);
        $section->addLine($line2);

        $order = new Order();
        $order->setOrderNumber('DEV-2025-001');
        $order->setStatus('a_signer');
        $order->addSection($section);

        $orderRepository->expects($this->once())->method('findBy')->willReturn([$order]);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository, $entityManager);

        // Filter only profile 1
        $result = $service->analyzePipeline([1], []);

        // Should only count days from profile 1 (with conversion probability)
        static::assertGreaterThan(5, $result['totalPotentialDays']);
        static::assertLessThan(11, $result['totalPotentialDays']);
    }

    public function testAnalyzePipelineGroupsWorkloadByMonth(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $contributorRepository = $this->createStub(ContributorRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $profile = new Profile();
        $profile->setName('Développeur');

        $line = new OrderLine();
        $line->setDays('20');
        $line->setDailyRate('500');
        $line->setProfile($profile);

        $section = new OrderSection();
        $section->setTitle('Phase 1');
        $section->addLine($line);

        $order = new Order();
        $order->setOrderNumber('DEV-2025-001');
        $order->setStatus('a_signer');
        $order->setCreatedAt(new DateTimeImmutable());
        $order->addSection($section);

        $orderRepository->expects($this->once())->method('findBy')->willReturn([$order]);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository, $entityManager);
        $result = $service->analyzePipeline();

        static::assertIsArray($result['workloadByMonth']);
    }

    // -----------------------------------------------------------------
    // T-TC1-02b coverage push
    // -----------------------------------------------------------------

    public function testAnalyzePipelineIncludeConfirmedFlagFetchesConfirmedOrders(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $contributorRepository = $this->createStub(ContributorRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        // includeConfirmed=true → service queries both confirmed statuses + pending.
        $orderRepository->expects($this->exactly(2))->method('findBy')->willReturnOnConsecutiveCalls([], []);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository, $entityManager);
        $result = $service->analyzePipeline([], [], true);

        static::assertIsArray($result);
        static::assertArrayHasKey('pipeline', $result);
    }

    public function testAnalyzePipelineAppliesAgePenaltyOnOldOrder(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $contributorRepository = $this->createStub(ContributorRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $profile = new Profile();
        $profile->setName('Senior Dev');

        $line = new OrderLine();
        $line->setDays('5');
        $line->setDailyRate('500');
        $line->setProfile($profile);

        $section = new OrderSection();
        $section->setTitle('Phase 1');
        $section->addLine($line);

        // Order is 90 days old → -20 probability penalty branch (daysOld > 60).
        $order = new Order();
        $order->setOrderNumber('OLD-2025-001');
        $order->setStatus('a_signer');
        $order->setCreatedAt(new DateTimeImmutable('-90 days'));
        $order->addSection($section);

        $orderRepository->method('findBy')->willReturn([$order]);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository, $entityManager);
        $result = $service->analyzePipeline();

        static::assertNotEmpty($result['pipeline']);
        $entry = $result['pipeline'][0];
        static::assertLessThan(50, $entry['winProbability']);
    }

    public function testAnalyzePipelineHandlesEmptyContributorRepositoryForCapacity(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $contributorRepository = $this->createStub(ContributorRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $profile = new Profile();
        $profile->setName('Dev');

        $line = new OrderLine();
        $line->setDays('25');
        $line->setDailyRate('500');
        $line->setProfile($profile);

        $section = new OrderSection();
        $section->setTitle('Phase 1');
        $section->addLine($line);

        $order = new Order();
        $order->setOrderNumber('CAP-2025-001');
        $order->setStatus('a_signer');
        $order->setCreatedAt(new DateTimeImmutable());
        $order->addSection($section);

        $orderRepository->method('findBy')->willReturn([$order]);
        // Empty active contributors → team capacity falls back to 20 days/month.
        $contributorRepository->method('findBy')->willReturn([]);

        $service = new WorkloadPredictionService($orderRepository, $contributorRepository, $entityManager);
        $result = $service->analyzePipeline();

        static::assertIsArray($result['workloadByMonth']);
        static::assertIsArray($result['alerts']);
    }
}
