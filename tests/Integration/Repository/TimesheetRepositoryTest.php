<?php

namespace App\Tests\Integration\Repository;

use App\Factory\ContributorFactory;
use App\Factory\ProjectFactory;
use App\Factory\TimesheetFactory;
use App\Repository\TimesheetRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TimesheetRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testGetTotalHoursForMonth(): void
    {
        self::bootKernel();
        $repo = static::getContainer()->get(TimesheetRepository::class);

        $contributor = ContributorFactory::createOne();
        $project     = ProjectFactory::createOne(['status' => 'active']);

        // Create 3 entries in April 2024 and 1 in May 2024
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new \DateTime('2024-04-02'),
            'hours'       => '8.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new \DateTime('2024-04-10'),
            'hours'       => '7.50',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new \DateTime('2024-04-20'),
            'hours'       => '4.00',
        ]);
        TimesheetFactory::createOne([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => new \DateTime('2024-05-01'),
            'hours'       => '8.00',
        ]);

        $start = new \DateTime('2024-04-01');
        $end   = new \DateTime('2024-04-30');
        $sum   = $repo->getTotalHoursForMonth($start, $end);

        $this->assertEquals(19.5, $sum, '', 0.001);
    }
}
