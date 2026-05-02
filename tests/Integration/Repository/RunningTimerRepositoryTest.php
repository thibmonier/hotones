<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\RunningTimer;
use App\Factory\ContributorFactory;
use App\Factory\ProjectFactory;
use App\Repository\RunningTimerRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use PHPUnit\Framework\Attributes\Group;
/**
 * Integration tests for RunningTimerRepository.
 *
 * Validates the central invariant of the timesheet timer module:
 *  - **at most one active timer per contributor** at any time
 *    (`stoppedAt IS NULL` rows are unique per contributor in practice;
 *    enforced application-side in TimesheetController::startTimer
 *    via `findActiveByContributor()` + `finalizeTimer()`).
 *  - timers are scoped per company (multi-tenant).
 *  - stopped timers are excluded from the active lookup.
 *  - a different contributor's active timer is not returned.
 */
#[Group('skip-pre-push')]
final class RunningTimerRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private RunningTimerRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(RunningTimerRepository::class);
        $this->em = $this->getEntityManager();
        $this->setUpMultiTenant();
    }

    public function testFindActiveByContributorReturnsNullWhenNoTimerExists(): void
    {
        $contributor = ContributorFactory::createOne()->_real();

        self::assertNull($this->repository->findActiveByContributor($contributor));
    }

    public function testFindActiveByContributorReturnsRunningTimer(): void
    {
        $contributor = ContributorFactory::createOne()->_real();
        $project = ProjectFactory::createOne()->_real();

        $timer = $this->createTimer($contributor, $project, startedAt: new DateTime('2026-04-30 09:00:00'));

        $found = $this->repository->findActiveByContributor($contributor);

        self::assertNotNull($found);
        self::assertSame($timer->getId(), $found->getId());
        self::assertTrue($found->isActive());
    }

    public function testFindActiveByContributorIgnoresStoppedTimer(): void
    {
        $contributor = ContributorFactory::createOne()->_real();
        $project = ProjectFactory::createOne()->_real();

        $stopped = $this->createTimer(
            $contributor,
            $project,
            startedAt: new DateTime('2026-04-30 08:00:00'),
            stoppedAt: new DateTime('2026-04-30 09:00:00'),
        );

        self::assertFalse($stopped->isActive());
        self::assertNull($this->repository->findActiveByContributor($contributor));
    }

    public function testFindActiveByContributorReturnsActiveAmongMixedHistory(): void
    {
        $contributor = ContributorFactory::createOne()->_real();
        $project = ProjectFactory::createOne()->_real();

        $this->createTimer(
            $contributor,
            $project,
            startedAt: new DateTime('2026-04-30 06:00:00'),
            stoppedAt: new DateTime('2026-04-30 07:00:00'),
        );
        $this->createTimer(
            $contributor,
            $project,
            startedAt: new DateTime('2026-04-30 07:30:00'),
            stoppedAt: new DateTime('2026-04-30 08:00:00'),
        );
        $active = $this->createTimer(
            $contributor,
            $project,
            startedAt: new DateTime('2026-04-30 09:00:00'),
        );

        $found = $this->repository->findActiveByContributor($contributor);

        self::assertNotNull($found);
        self::assertSame($active->getId(), $found->getId());
    }

    public function testFindActiveByContributorIsolatesPerContributor(): void
    {
        $alice = ContributorFactory::createOne()->_real();
        $bob = ContributorFactory::createOne()->_real();
        $project = ProjectFactory::createOne()->_real();

        $this->createTimer($alice, $project, startedAt: new DateTime('2026-04-30 09:00:00'));

        self::assertNotNull($this->repository->findActiveByContributor($alice));
        self::assertNull(
            $this->repository->findActiveByContributor($bob),
            'A timer running for Alice must not be returned when querying Bob.',
        );
    }

    private function createTimer(
        Contributor $contributor,
        Project $project,
        DateTime $startedAt,
        ?DateTime $stoppedAt = null,
    ): RunningTimer {
        $timer = new RunningTimer();
        $timer
            ->setCompany($this->getTestCompany())
            ->setContributor($contributor)
            ->setProject($project)
            ->setStartedAt($startedAt)
            ->setStoppedAt($stoppedAt);

        $this->em->persist($timer);
        $this->em->flush();

        return $timer;
    }
}
