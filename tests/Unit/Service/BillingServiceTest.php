<?php

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Entity\OrderPaymentSchedule;
use App\Entity\Project;
use App\Repository\TimesheetRepository;
use App\Service\BillingService;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BillingServiceTest extends TestCase
{
    private BillingService $billingService;
    private TimesheetRepository|\PHPUnit\Framework\MockObject\MockObject $timesheetRepositoryMock;

    protected function setUp(): void
    {
        $this->timesheetRepositoryMock = $this->createMock(TimesheetRepository::class);
        $this->billingService          = new BillingService($this->timesheetRepositoryMock);
    }

    public function testBuildProjectBillingRecapWithForfaitOrder()
    {
        $project = new Project();
        $order   = new Order();
        $order->setContractType('forfait');
        $order->setProject($project);

        $schedule1 = new OrderPaymentSchedule();
        $schedule1->setBillingDate(new DateTime('2023-01-15'));
        $schedule1->setLabel('First payment');

        $schedule2 = new OrderPaymentSchedule();
        $schedule2->setBillingDate(new DateTime('2023-02-15'));
        $schedule2->setLabel('Second payment');

        $order->addPaymentSchedule($schedule1);
        $order->addPaymentSchedule($schedule2);

        $project->addOrder($order);

        // Mock the calculateTotalFromSections method
        $order->setTotalAmount(10000.00);

        $result = $this->billingService->buildProjectBillingRecap($project);

        $this->assertCount(2, $result);
        $this->assertEquals('2023-01-15', $result[0]['date']->format('Y-m-d'));
        $this->assertEquals('First payment', $result[0]['label']);
        $this->assertEquals('forfait', $result[0]['type']);
        $this->assertEquals('2023-02-15', $result[1]['date']->format('Y-m-d'));
        $this->assertEquals('Second payment', $result[1]['label']);
        $this->assertEquals('forfait', $result[1]['type']);
    }

    public function testBuildProjectBillingRecapWithRegieOrder()
    {
        $project = new Project();
        $order   = new Order();
        $order->setContractType('regie');
        $order->setProject($project);

        $project->addOrder($order);

        // Mock timesheet repository
        $mockData = [
            ['year' => 2023, 'month' => 1, 'revenue' => 5000.00],
            ['year' => 2023, 'month' => 2, 'revenue' => 7500.00],
        ];

        $this->timesheetRepositoryMock
            ->method('getMonthlyRevenueForProjectUsingContributorTjm')
            ->willReturn($mockData);

        $result = $this->billingService->buildProjectBillingRecap($project);

        $this->assertCount(2, $result);
        $this->assertEquals('2023-01-01', $result[0]['date']->format('Y-m-d'));
        $this->assertEquals('Régie 01/2023', $result[0]['label']);
        $this->assertEquals(5000.00, $result[0]['amount']);
        $this->assertEquals('regie', $result[0]['type']);
        $this->assertEquals('2023-02-01', $result[1]['date']->format('Y-m-d'));
        $this->assertEquals('Régie 02/2023', $result[1]['label']);
        $this->assertEquals(7500.00, $result[1]['amount']);
        $this->assertEquals('regie', $result[1]['type']);
    }

    public function testBuildProjectBillingRecapWithMixedOrders()
    {
        $project = new Project();

        // Forfait order
        $forfaitOrder = new Order();
        $forfaitOrder->setContractType('forfait');
        $forfaitOrder->setProject($project);
        $forfaitOrder->setTotalAmount(12000.00);

        $schedule = new OrderPaymentSchedule();
        $schedule->setBillingDate(new DateTime('2023-01-15'));
        $schedule->setLabel('Forfait payment');
        $forfaitOrder->addPaymentSchedule($schedule);

        // Regie order
        $regieOrder = new Order();
        $regieOrder->setContractType('regie');
        $regieOrder->setProject($project);

        $project->addOrder($forfaitOrder);
        $project->addOrder($regieOrder);

        // Mock timesheet repository for regie
        $mockData = [
            ['year' => 2023, 'month' => 2, 'revenue' => 3000.00],
        ];

        $this->timesheetRepositoryMock
            ->method('getMonthlyRevenueForProjectUsingContributorTjm')
            ->willReturn($mockData);

        $result = $this->billingService->buildProjectBillingRecap($project);

        $this->assertCount(2, $result);
        $this->assertEquals('2023-01-15', $result[0]['date']->format('Y-m-d'));
        $this->assertEquals('forfait', $result[0]['type']);
        $this->assertEquals('2023-02-01', $result[1]['date']->format('Y-m-d'));
        $this->assertEquals('regie', $result[1]['type']);
    }

    public function testBuildProjectBillingRecapEmptyProject()
    {
        $project = new Project();

        $result = $this->billingService->buildProjectBillingRecap($project);

        $this->assertEmpty($result);
    }

    public function testBuildProjectBillingRecapSortingByDate()
    {
        $project = new Project();
        $order   = new Order();
        $order->setContractType('forfait');
        $order->setProject($project);
        $order->setTotalAmount(10000.00);

        $schedule1 = new OrderPaymentSchedule();
        $schedule1->setBillingDate(new DateTime('2023-03-15'));
        $schedule1->setLabel('March payment');

        $schedule2 = new OrderPaymentSchedule();
        $schedule2->setBillingDate(new DateTime('2023-01-15'));
        $schedule2->setLabel('January payment');

        $schedule3 = new OrderPaymentSchedule();
        $schedule3->setBillingDate(new DateTime('2023-02-15'));
        $schedule3->setLabel('February payment');

        $order->addPaymentSchedule($schedule1);
        $order->addPaymentSchedule($schedule2);
        $order->addPaymentSchedule($schedule3);

        $project->addOrder($order);

        $result = $this->billingService->buildProjectBillingRecap($project);

        $this->assertCount(3, $result);
        $this->assertEquals('2023-01-15', $result[0]['date']->format('Y-m-d'));
        $this->assertEquals('2023-02-15', $result[1]['date']->format('Y-m-d'));
        $this->assertEquals('2023-03-15', $result[2]['date']->format('Y-m-d'));
    }

    public function testBuildProjectBillingRecapWithEmptyScheduleLabel()
    {
        $project = new Project();
        $order   = new Order();
        $order->setContractType('forfait');
        $order->setProject($project);
        $order->setTotalAmount(10000.00);

        $schedule = new OrderPaymentSchedule();
        $schedule->setBillingDate(new DateTime('2023-01-15'));
        $schedule->setLabel(''); // Empty label

        $order->addPaymentSchedule($schedule);
        $project->addOrder($order);

        $result = $this->billingService->buildProjectBillingRecap($project);

        $this->assertCount(1, $result);
        $this->assertEquals('Échéance', $result[0]['label']); // Should default to 'Échéance'
    }

    public function testBuildProjectBillingRecapWithNullRevenue()
    {
        $project    = new Project();
        $regieOrder = new Order();
        $regieOrder->setContractType('regie');
        $regieOrder->setProject($project);

        $project->addOrder($regieOrder);

        // Mock timesheet with null revenue
        $mockData = [
            ['year' => 2023, 'month' => 3, 'revenue' => null],
        ];

        $this->timesheetRepositoryMock
            ->method('getMonthlyRevenueForProjectUsingContributorTjm')
            ->willReturn($mockData);

        $result = $this->billingService->buildProjectBillingRecap($project);

        $this->assertCount(1, $result);
        $this->assertEquals(0.0, $result[0]['amount']); // Null revenue becomes 0.0
    }

    public function testBuildProjectBillingRecapWithEmptyTimesheetData()
    {
        $project    = new Project();
        $regieOrder = new Order();
        $regieOrder->setContractType('regie');
        $regieOrder->setProject($project);

        $project->addOrder($regieOrder);

        // Mock empty timesheet data
        $this->timesheetRepositoryMock
            ->method('getMonthlyRevenueForProjectUsingContributorTjm')
            ->willReturn([]);

        $result = $this->billingService->buildProjectBillingRecap($project);

        $this->assertEmpty($result);
    }

    public function testBuildProjectBillingRecapArrayStructure()
    {
        $project = new Project();
        $order   = new Order();
        $order->setContractType('forfait');
        $order->setProject($project);
        $order->setTotalAmount(5000.00);

        $schedule = new OrderPaymentSchedule();
        $schedule->setBillingDate(new DateTime('2023-01-15'));
        $schedule->setLabel('Test Payment');

        $order->addPaymentSchedule($schedule);
        $project->addOrder($order);

        $result = $this->billingService->buildProjectBillingRecap($project);

        $this->assertCount(1, $result);

        // Verify exact array structure
        $entry = $result[0];
        $this->assertArrayHasKey('date', $entry);
        $this->assertArrayHasKey('label', $entry);
        $this->assertArrayHasKey('amount', $entry);
        $this->assertArrayHasKey('type', $entry);
        $this->assertArrayHasKey('order', $entry);

        // Verify types
        $this->assertInstanceOf(DateTimeInterface::class, $entry['date']);
        $this->assertIsString($entry['label']);
        $this->assertIsFloat($entry['amount']);
        $this->assertEquals('forfait', $entry['type']);
        $this->assertSame($order, $entry['order']);
    }
}
