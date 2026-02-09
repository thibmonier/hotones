<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\RecalculateMetricsMessage;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:metrics:dispatch',
    description: 'Dispatch async metrics recalculation jobs (by date/granularity or full year)',
)]
final class DispatchMetricsRecalculationCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'year',
                null,
                InputOption::VALUE_REQUIRED,
                'Recalculate for the given year (dispatch monthly, quarterly, yearly)',
            )
            ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Reference date (Y-m-d)')
            ->addOption('granularity', null, InputOption::VALUE_REQUIRED, 'monthly|quarterly|yearly');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $year        = $input->getOption('year');
        $dateString  = $input->getOption('date');
        $granularity = $input->getOption('granularity') ?? 'monthly';

        if ($year) {
            $y = (int) $year;
            // monthly
            for ($m = 1; $m <= 12; ++$m) {
                $this->bus->dispatch(new RecalculateMetricsMessage(sprintf('%04d-%02d-01', $y, $m), 'monthly'));
            }
            // quarterly
            for ($q = 1; $q <= 4; ++$q) {
                $startMonth = (($q - 1) * 3) + 1;
                $this->bus->dispatch(
                    new RecalculateMetricsMessage(sprintf('%04d-%02d-01', $y, $startMonth), 'quarterly'),
                );
            }
            // yearly
            $this->bus->dispatch(new RecalculateMetricsMessage(sprintf('%04d-01-01', $y), 'yearly'));

            $output->writeln(sprintf('Dispatched metrics recalculation for year %d.', $y));

            return Command::SUCCESS;
        }

        if ($dateString) {
            // validate date
            new DateTime($dateString); // throws if invalid
            $this->bus->dispatch(new RecalculateMetricsMessage($dateString, $granularity));
            $output->writeln(sprintf('Dispatched %s metrics for %s.', $granularity, $dateString));

            return Command::SUCCESS;
        }

        $output->writeln('Provide either --year or --date [--granularity].');

        return Command::INVALID;
    }
}
