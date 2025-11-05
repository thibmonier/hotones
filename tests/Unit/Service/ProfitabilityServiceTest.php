<?php

namespace App\Tests\Unit\Service;

use App\Entity\Contributor;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderSection;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\Timesheet;
use App\Service\ProfitabilityService;
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
            ->setName('Alice')
            ->setCjm('400')
            ->setTjm('800');

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
            ->setDate(new \DateTime('2025-01-10'))
            ->setHours('8.00');
        $ts2 = (new Timesheet())
            ->setContributor($contributor)
            ->setProject($project)
            ->setTask($regularTask)
            ->setDate(new \DateTime('2025-01-11'))
            ->setHours('8.00');

        // Attach timesheets to project collection
        $project->getTimesheets()->add($ts1);
        $project->getTimesheets()->add($ts2);

        return $project;
    }

    public function testCalculateProjectProfitabilityForExternalProject(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = $this->createProjectWithRevenueAndCosts();
        $result = $service->calculateProjectProfitability($project);

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
        $em = $this->createMock(EntityManagerInterface::class);
        $service = new ProfitabilityService($em);

        $project = (new Project())
            ->setName('Internal')
            ->setIsInternal(true);

        $contributor = (new Contributor())
            ->setName('Bob')
            ->setCjm('400');

        $ts = (new Timesheet())
            ->setContributor($contributor)
            ->setProject($project)
            ->setDate(new \DateTime('2025-01-10'))
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
}
