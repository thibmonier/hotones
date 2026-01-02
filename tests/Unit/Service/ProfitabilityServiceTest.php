<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderSection;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\Timesheet;
use App\Service\ProfitabilityService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProfitabilityServiceTest extends TestCase
{
    private function createProjectWithRevenueAndCosts(): Project
    {
        $project = (new Project())->setName('Test Project');

        // Order with one section and one service line
        $order = (new Order())
            ->setOrderNumber('D202501-001')
            ->setStatus('signed'); // Note: service expects english statuses

        $section = (new OrderSection())
            ->setTitle('Services');

        $profile = (new Profile())
            ->setName('Dev')
            ->setDefaultDailyRate('600');

        $line = (new OrderLine())
            ->setSection($section)
            ->setDescription('Implementation')
            ->setType('service')
            ->setProfile($profile)
            ->setDailyRate('1000')
            ->setDays('5.00')
            ->setAttachedPurchaseAmount('100.00');

        $section->addLine($line);
        $order->addSection($section);
        $project->addOrder($order);

        // Project-level purchases
        $project->setPurchasesAmount('200.00');

        // Timesheets: 2 days total (16h) with CJM 400
        $contributor = (new Contributor())
            ->setFirstName('Alice')
            ->setLastName('Test');

        $employmentPeriod = (new EmploymentPeriod())
            ->setContributor($contributor)
            ->setStartDate(new DateTime('-6 months'))
            ->setCjm('400')
            ->setTjm('800');
        $contributor->addEmploymentPeriod($employmentPeriod);

        $regularTask = (new ProjectTask())
            ->setProject($project)
            ->setName('Work')
            ->setType(ProjectTask::TYPE_REGULAR)
            ->setCountsForProfitability(true);
        $project->addTask($regularTask);

        $ts1 = (new Timesheet())
            ->setContributor($contributor)
            ->setProject($project)
            ->setTask($regularTask)
            ->setDate(new DateTime('2025-01-10'))
            ->setHours('8.00');
        $ts2 = (new Timesheet())
            ->setContributor($contributor)
            ->setProject($project)
            ->setTask($regularTask)
            ->setDate(new DateTime('2025-01-11'))
            ->setHours('8.00');

        // Attach timesheets to project collection
        $project->getTimesheets()->add($ts1);
        $project->getTimesheets()->add($ts2);

        return $project;
    }

    public function testCalculateProjectProfitabilityForExternalProject(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = $this->createProjectWithRevenueAndCosts();
        $result  = $service->calculateProjectProfitability($project);

        // Revenue: 5 * 1000 with 0 contingency = 5000; method also supports contingency but none set
        // BUT calculateOrderTotal subtracts contingency if set; not set here
        // Service adds purchases: project 200 + line 100 = 300; Human cost: 16h * 400 / 8 = 800
        $this->assertSame('5000.00', $result['revenue']);
        $this->assertSame('1100.00', $result['cost']);
        $this->assertSame('3900.00', $result['margin']);
        $this->assertSame('78.00', $result['margin_rate']);
        $this->assertSame('5.00', $result['sold_days']);
        $this->assertSame('16', $result['worked_hours']);
        $this->assertSame('2.00', $result['worked_days']);
        $this->assertSame('16.00', $result['billable_hours']);
        $this->assertSame('2.00', $result['billable_days']);
        $this->assertFalse($result['is_internal']);
        $this->assertSame('0', $result['excluded_hours']);
        $this->assertSame(1, $result['orders_count']);
        $this->assertSame('200.00', $result['purchases_amount']);
    }

    public function testCalculateProjectProfitabilityForInternalProject(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = (new Project())
            ->setName('Internal')
            ->setIsInternal(true);

        $contributor = (new Contributor())
            ->setFirstName('Bob')
            ->setLastName('Test');

        $employmentPeriod = (new EmploymentPeriod())
            ->setContributor($contributor)
            ->setStartDate(new DateTime('-6 months'))
            ->setCjm('400');
        $contributor->addEmploymentPeriod($employmentPeriod);

        $ts = (new Timesheet())
            ->setContributor($contributor)
            ->setProject($project)
            ->setDate(new DateTime('2025-01-10'))
            ->setHours('8.00');
        $project->getTimesheets()->add($ts);

        $result = $service->calculateProjectProfitability($project);

        $this->assertTrue($result['is_internal']);
        $this->assertSame('0', $result['revenue']);
        $this->assertSame('0', $result['cost']);
        $this->assertSame('0', $result['margin']);
        $this->assertSame('0', $result['margin_rate']);
        $this->assertSame('1.00', $result['worked_days']);
        $this->assertSame('8', $result['worked_hours']);
    }

    public function testCalculateProjectProfitabilityWithContingency(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = (new Project())->setName('Test Project');

        // Order with contingency
        $order = (new Order())
            ->setOrderNumber('D202501-002')
            ->setStatus('signed')
            ->setContingencyPercentage('10'); // 10% contingency

        $section = (new OrderSection())->setTitle('Services');

        $profile = (new Profile())
            ->setName('Dev')
            ->setDefaultDailyRate('600');

        $line = (new OrderLine())
            ->setSection($section)
            ->setDescription('Implementation')
            ->setType('service')
            ->setProfile($profile)
            ->setDailyRate('1000')
            ->setDays('10.00');

        $section->addLine($line);
        $order->addSection($section);
        $project->addOrder($order);

        $result = $service->calculateProjectProfitability($project);

        // 10 days * 1000 = 10000, minus 10% contingency = 9000
        $this->assertSame('9000.00', $result['revenue']);
    }

    public function testCalculateProjectProfitabilityWithNonBillableTasks(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = (new Project())->setName('Test Project');

        $contributor = (new Contributor())
            ->setFirstName('Alice')
            ->setLastName('Test');

        $employmentPeriod = (new EmploymentPeriod())
            ->setContributor($contributor)
            ->setStartDate(new DateTime('-6 months'))
            ->setCjm('400');
        $contributor->addEmploymentPeriod($employmentPeriod);

        // Billable task
        $billableTask = (new ProjectTask())
            ->setProject($project)
            ->setName('Billable Work')
            ->setType(ProjectTask::TYPE_REGULAR)
            ->setCountsForProfitability(true);
        $project->addTask($billableTask);

        // Non-billable task (AVV)
        $nonBillableTask = (new ProjectTask())
            ->setProject($project)
            ->setName('AVV')
            ->setType(ProjectTask::TYPE_AVV)
            ->setCountsForProfitability(false);
        $project->addTask($nonBillableTask);

        // Billable timesheet (8h)
        $ts1 = (new Timesheet())
            ->setContributor($contributor)
            ->setProject($project)
            ->setTask($billableTask)
            ->setDate(new DateTime('2025-01-10'))
            ->setHours('8.00');
        $project->getTimesheets()->add($ts1);

        // Non-billable timesheet (4h)
        $ts2 = (new Timesheet())
            ->setContributor($contributor)
            ->setProject($project)
            ->setTask($nonBillableTask)
            ->setDate(new DateTime('2025-01-11'))
            ->setHours('4.00');
        $project->getTimesheets()->add($ts2);

        $result = $service->calculateProjectProfitability($project);

        // Total hours: 12, excluded: 4, billable: 8
        $this->assertSame('12', $result['worked_hours']);
        $this->assertSame('4.00', $result['excluded_hours']);
        $this->assertSame('8.00', $result['billable_hours']);
        $this->assertSame('1.00', $result['billable_days']);
    }

    public function testCalculateGlobalKPIsExcludesInternalProjects(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        // External project with revenue
        $externalProject = (new Project())
            ->setName('External')
            ->setIsInternal(false);

        $order = (new Order())
            ->setOrderNumber('D202501-003')
            ->setStatus('signed');

        $section = (new OrderSection())->setTitle('Services');

        $line = (new OrderLine())
            ->setSection($section)
            ->setDescription('Implementation')
            ->setType('service')
            ->setDailyRate('1000')
            ->setDays('5.00');

        $section->addLine($line);
        $order->addSection($section);
        $externalProject->addOrder($order);

        // Internal project (should be excluded)
        $internalProject = (new Project())
            ->setName('Internal')
            ->setIsInternal(true);

        $projects = [$externalProject, $internalProject];
        $result   = $service->calculateGlobalKPIs($projects);

        $this->assertSame(1, $result['external_projects_count']);
        $this->assertSame(1, $result['internal_projects_count']);
        $this->assertSame('5000.00', $result['total_revenue']);
    }

    public function testCalculateGlobalKPIsWithZeroRevenue(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $service  = new ProfitabilityService($em);
        $projects = [];
        $result   = $service->calculateGlobalKPIs($projects);

        $this->assertSame('0', $result['total_revenue']);
        $this->assertSame('0', $result['total_cost']);
        $this->assertSame('0.00', $result['total_margin']);
        $this->assertSame('0', $result['global_margin_rate']);
    }

    public function testCompareProjectForecastVsRealizedOverrun(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = (new Project())->setName('Test Project');

        // Sold: 5 days
        $order = (new Order())
            ->setOrderNumber('D202501-004')
            ->setStatus('signed');

        $section = (new OrderSection())->setTitle('Services');

        $line = (new OrderLine())
            ->setSection($section)
            ->setDescription('Implementation')
            ->setType('service')
            ->setDailyRate('1000')
            ->setDays('5.00');

        $section->addLine($line);
        $order->addSection($section);
        $project->addOrder($order);

        // Worked: 8 days (6 days billable = 48h)
        $contributor = (new Contributor())
            ->setFirstName('Alice')
            ->setLastName('Test');

        $employmentPeriod = (new EmploymentPeriod())
            ->setContributor($contributor)
            ->setStartDate(new DateTime('-6 months'))
            ->setCjm('400');
        $contributor->addEmploymentPeriod($employmentPeriod);

        $billableTask = (new ProjectTask())
            ->setProject($project)
            ->setName('Work')
            ->setType(ProjectTask::TYPE_REGULAR)
            ->setCountsForProfitability(true);
        $project->addTask($billableTask);

        // 6 timesheets of 8h each = 48h = 6 days
        for ($i = 0; $i < 6; ++$i) {
            $ts = (new Timesheet())
                ->setContributor($contributor)
                ->setProject($project)
                ->setTask($billableTask)
                ->setDate(new DateTime("2025-01-1$i"))
                ->setHours('8.00');
            $project->getTimesheets()->add($ts);
        }

        $result = $service->compareProjectForecastVsRealized($project);

        $this->assertSame('5.00', $result['sold_days']);
        $this->assertSame('6.00', $result['billable_days']);
        $this->assertSame('1.00', $result['days_overrun']); // 6 - 5 = 1
        $this->assertTrue($result['is_overrun']);
        $this->assertGreaterThan(10, floatval($result['overrun_percentage'])); // (1/5)*100 = 20%
    }

    public function testGenerateProfitabilityAlertsNegativeMargin(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = (new Project())->setName('Test Project');

        // Low revenue
        $order = (new Order())
            ->setOrderNumber('D202501-005')
            ->setStatus('signed');

        $section = (new OrderSection())->setTitle('Services');

        $line = (new OrderLine())
            ->setSection($section)
            ->setDescription('Implementation')
            ->setType('service')
            ->setDailyRate('100')
            ->setDays('1.00');

        $section->addLine($line);
        $order->addSection($section);
        $project->addOrder($order);

        // High costs
        $contributor = (new Contributor())
            ->setFirstName('Alice')
            ->setLastName('Test');

        $employmentPeriod = (new EmploymentPeriod())
            ->setContributor($contributor)
            ->setStartDate(new DateTime('-6 months'))
            ->setCjm('800'); // High cost
        $contributor->addEmploymentPeriod($employmentPeriod);

        $billableTask = (new ProjectTask())
            ->setProject($project)
            ->setName('Work')
            ->setType(ProjectTask::TYPE_REGULAR)
            ->setCountsForProfitability(true);
        $project->addTask($billableTask);

        // 10 days of work (80h)
        for ($i = 1; $i <= 10; ++$i) {
            $ts = (new Timesheet())
                ->setContributor($contributor)
                ->setProject($project)
                ->setTask($billableTask)
                ->setDate(new DateTime("2025-01-$i"))
                ->setHours('8.00');
            $project->getTimesheets()->add($ts);
        }

        $alerts = $service->generateProfitabilityAlerts($project);

        // Should have negative margin alert
        $hasNegativeMarginAlert = false;
        foreach ($alerts as $alert) {
            if ($alert['type'] === 'danger' && $alert['title'] === 'Marge négative') {
                $hasNegativeMarginAlert = true;
                break;
            }
        }

        $this->assertTrue($hasNegativeMarginAlert);
    }

    public function testFormatProfitabilityForDisplay(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $profitability = [
            'revenue'        => '5000.00',
            'cost'           => '3000.00',
            'margin'         => '2000.00',
            'margin_rate'    => '40.00',
            'sold_days'      => '5.00',
            'worked_days'    => '3.75',
            'billable_days'  => '3.75',
            'excluded_hours' => '0',
            'is_internal'    => false,
        ];

        $result = $service->formatProfitabilityForDisplay($profitability);

        $this->assertStringContainsString('5 000', $result['revenue']);
        $this->assertStringContainsString('€', $result['revenue']);
        $this->assertStringContainsString('40', $result['margin_rate']);
        $this->assertStringContainsString('%', $result['margin_rate']);
        $this->assertFalse($result['is_internal']);
    }

    public function testBuildBudgetDonut(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = $this->createProjectWithRevenueAndCosts();
        $result  = $service->buildBudgetDonut($project);

        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(3, $result['labels']);
        $this->assertCount(3, $result['data']);
        $this->assertContains('Marge', $result['labels']);
        $this->assertContains('Achats', $result['labels']);
        $this->assertContains('Coût homme', $result['labels']);
    }

    public function testMultipleOrdersCalculation(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = (new Project())->setName('Multi-order Project');

        // First order: 5 days at 1000/day
        $order1 = (new Order())
            ->setOrderNumber('D202501-006')
            ->setStatus('signed');

        $section1 = (new OrderSection())->setTitle('Phase 1');

        $line1 = (new OrderLine())
            ->setSection($section1)
            ->setDescription('Implementation')
            ->setType('service')
            ->setDailyRate('1000')
            ->setDays('5.00');

        $section1->addLine($line1);
        $order1->addSection($section1);
        $project->addOrder($order1);

        // Second order: 3 days at 1200/day
        $order2 = (new Order())
            ->setOrderNumber('D202501-007')
            ->setStatus('signed');

        $section2 = (new OrderSection())->setTitle('Phase 2');

        $line2 = (new OrderLine())
            ->setSection($section2)
            ->setDescription('Additional work')
            ->setType('service')
            ->setDailyRate('1200')
            ->setDays('3.00');

        $section2->addLine($line2);
        $order2->addSection($section2);
        $project->addOrder($order2);

        $result = $service->calculateProjectProfitability($project);

        // Total revenue: (5 * 1000) + (3 * 1200) = 5000 + 3600 = 8600
        $this->assertSame('8600.00', $result['revenue']);
        // Total sold days: 5 + 3 = 8
        $this->assertSame('8.00', $result['sold_days']);
        $this->assertSame(2, $result['orders_count']);
    }

    public function testOrderStatusFiltering(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = (new Project())->setName('Test Project');

        // Signed order (should count)
        $order1 = (new Order())
            ->setOrderNumber('D202501-008')
            ->setStatus('signed');

        $section1 = (new OrderSection())->setTitle('Services 1');

        $line1 = (new OrderLine())
            ->setSection($section1)
            ->setDescription('Implementation')
            ->setType('service')
            ->setDailyRate('1000')
            ->setDays('5.00');

        $section1->addLine($line1);
        $order1->addSection($section1);
        $project->addOrder($order1);

        // Pending order (should NOT count)
        $order2 = (new Order())
            ->setOrderNumber('D202501-009')
            ->setStatus('a_signer'); // Not signed yet

        $section2 = (new OrderSection())->setTitle('Services 2');

        $line2 = (new OrderLine())
            ->setSection($section2)
            ->setDescription('Future work')
            ->setType('service')
            ->setDailyRate('1000')
            ->setDays('10.00');

        $section2->addLine($line2);
        $order2->addSection($section2);
        $project->addOrder($order2);

        $result = $service->calculateProjectProfitability($project);

        // Only signed order should count
        $this->assertSame('5000.00', $result['revenue']);
        $this->assertSame('5.00', $result['sold_days']);
    }
}
