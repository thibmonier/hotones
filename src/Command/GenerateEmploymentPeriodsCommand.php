<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Repository\ContributorRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-employment-periods',
    description: 'Generate active employment periods for contributors without one',
)]
class GenerateEmploymentPeriodsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContributorRepository $contributorRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start-date', null, InputOption::VALUE_OPTIONAL, 'Start date (Y-m-d)', date('Y').'-01-01')
            ->addOption('end-date', null, InputOption::VALUE_OPTIONAL, 'End date (Y-m-d) - leave empty for open-ended contract', null)
            ->addOption('weekly-hours', null, InputOption::VALUE_OPTIONAL, 'Weekly hours', '35')
            ->addOption('work-percentage', null, InputOption::VALUE_OPTIONAL, 'Work time percentage', '100')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be created without persisting')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Create contracts even for contributors who already have one')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $startDate      = new DateTime($input->getOption('start-date'));
        $endDateStr     = $input->getOption('end-date');
        $endDate        = $endDateStr ? new DateTime($endDateStr) : null;
        $weeklyHours    = (float) $input->getOption('weekly-hours');
        $workPercentage = (float) $input->getOption('work-percentage');
        $dryRun         = $input->getOption('dry-run');
        $force          = $input->getOption('force');

        $io->title('Generate Employment Periods');

        $io->section('Configuration');
        $io->table(
            ['Parameter', 'Value'],
            [
                ['Start Date', $startDate->format('Y-m-d')],
                ['End Date', $endDate ? $endDate->format('Y-m-d') : 'Open-ended (NULL)'],
                ['Weekly Hours', $weeklyHours],
                ['Work Percentage', $workPercentage.'%'],
                ['Mode', $dryRun ? 'DRY RUN' : 'LIVE'],
            ],
        );

        // Get all contributors
        $allContributors = $this->contributorRepository->findAll();

        if (empty($allContributors)) {
            $io->warning('No contributors found in database.');

            return Command::SUCCESS;
        }

        $io->comment(sprintf('Found %d contributors total', count($allContributors)));

        // Filter contributors without active employment period
        $contributorsToProcess = [];
        $today                 = new DateTime();

        foreach ($allContributors as $contributor) {
            $hasActiveContract = $this->hasActiveEmploymentPeriod($contributor, $today);

            if (!$hasActiveContract || $force) {
                $contributorsToProcess[] = $contributor;
            }
        }

        if (empty($contributorsToProcess)) {
            $io->success('All contributors already have an active employment period!');

            return Command::SUCCESS;
        }

        $io->section(sprintf('Contributors to process: %d', count($contributorsToProcess)));

        $created = 0;

        foreach ($contributorsToProcess as $contributor) {
            $period = new EmploymentPeriod();
            $period->setContributor($contributor);
            $period->setStartDate($startDate);
            if ($endDate) {
                $period->setEndDate($endDate);
            }
            $period->setWeeklyHours($weeklyHours);
            $period->setWorkTimePercentage($workPercentage);

            // Copy profiles from contributor to employment period
            foreach ($contributor->getProfiles() as $profile) {
                $period->addProfile($profile);
            }

            if (!$dryRun) {
                $this->entityManager->persist($period);
            }

            $io->text(sprintf(
                '  - %s %s: %s → %s, %sh/week, %s%%, %d profile(s)',
                $contributor->getFirstName(),
                $contributor->getLastName(),
                $startDate->format('Y-m-d'),
                $endDate ? $endDate->format('Y-m-d') : 'ongoing',
                $weeklyHours,
                $workPercentage,
                count($contributor->getProfiles()),
            ));

            ++$created;
        }

        if (!$dryRun && $created > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Created %d employment period(s)', $created));
        } elseif ($dryRun) {
            $io->warning(sprintf('DRY RUN: Would create %d employment period(s). Use without --dry-run to persist.', $created));
        }

        return Command::SUCCESS;
    }

    private function hasActiveEmploymentPeriod(Contributor $contributor, DateTime $date): bool
    {
        $qb = $this->entityManager->getRepository(EmploymentPeriod::class)
            ->createQueryBuilder('ep')
            ->select('COUNT(ep.id)')
            ->where('ep.contributor = :contributor')
            ->andWhere('ep.startDate <= :date')
            ->andWhere('(ep.endDate IS NULL OR ep.endDate >= :date)')
            ->setParameter('contributor', $contributor)
            ->setParameter('date', $date);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
