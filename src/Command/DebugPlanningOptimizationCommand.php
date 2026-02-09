<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ContributorRepository;
use App\Repository\StaffingMetricsRepository;
use App\Service\Planning\PlanningOptimizer;
use App\Service\Planning\TaceAnalyzer;
use App\Service\StaffingMetricsCalculationService;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:debug:planning-optimization', description: 'Debug planning optimization recommendations')]
class DebugPlanningOptimizationCommand extends Command
{
    public function __construct(
        private readonly PlanningOptimizer $optimizer,
        private readonly TaceAnalyzer $taceAnalyzer,
        private readonly ContributorRepository $contributorRepository,
        private readonly StaffingMetricsRepository $staffingMetricsRepository,
        private readonly StaffingMetricsCalculationService $staffingMetricsCalculationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'start',
            null,
            InputOption::VALUE_OPTIONAL,
            'Start date (Y-m-d)',
            'first day of this month',
        )->addOption('end', null, InputOption::VALUE_OPTIONAL, 'End date (Y-m-d)', 'last day of next month');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $startDate = new DateTime($input->getOption('start'));
        $endDate   = new DateTime($input->getOption('end'));

        $io->title(sprintf(
            'Planning Optimization Debug - Period: %s to %s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ));

        // Debug: Check active contributors
        $activeContributors = $this->contributorRepository->findBy(['active' => true]);
        $io->writeln(sprintf('<comment>Active contributors found: %d</comment>', count($activeContributors)));

        // Debug: Check staffing metrics
        $metrics = $this->staffingMetricsRepository->findByPeriod($startDate, $endDate, 'weekly');
        $io->writeln(sprintf('<comment>Staffing metrics found: %d</comment>', count($metrics)));

        if (count($activeContributors) > 0 && count($metrics) === 0) {
            $io->warning('No staffing metrics found. Calculating now...');

            // Delete existing metrics for the period
            $deleted = $this->staffingMetricsRepository->deleteForDateRange($startDate, $endDate, 'weekly');
            if ($deleted > 0) {
                $io->writeln(sprintf('<comment>Deleted %d old metrics</comment>', $deleted));
            }

            // Calculate new metrics
            $created = $this->staffingMetricsCalculationService->calculateAndStoreMetrics(
                $startDate,
                $endDate,
                'weekly',
            );
            $io->success(sprintf('Calculated %d metrics for the period', $created));
        }

        // Analyze all contributors
        $io->section('Analyzing contributors...');
        $analysis = $this->taceAnalyzer->analyzeAllContributors($startDate, $endDate);

        $io->table(['Status', 'Count'], [
            ['Critical', count($analysis['critical'])],
            ['Overloaded', count($analysis['overloaded'])],
            ['Underutilized', count($analysis['underutilized'])],
            ['Optimal', count($analysis['optimal'])],
        ]);

        // Show critical contributors
        if (count($analysis['critical']) > 0) {
            $io->section('Critical Contributors');
            $rows = [];
            foreach ($analysis['critical'] as $contrib) {
                $rows[] = [
                    $contrib['contributor']->getFullName(),
                    sprintf('%.1f%%', $contrib['tace']),
                    $contrib['status'],
                ];
            }
            $io->table(['Contributor', 'TACE', 'Status'], $rows);
        }

        // Show overloaded contributors
        if (count($analysis['overloaded']) > 0) {
            $io->section('Overloaded Contributors');
            $rows = [];
            foreach ($analysis['overloaded'] as $contrib) {
                $rows[] = [
                    $contrib['contributor']->getFullName(),
                    sprintf('%.1f%%', $contrib['tace']),
                    $contrib['status'],
                ];
            }
            $io->table(['Contributor', 'TACE', 'Status'], $rows);
        }

        // Generate recommendations
        $io->section('Generating recommendations...');
        $result = $this->optimizer->generateRecommendations($startDate, $endDate);

        $io->writeln(sprintf('Total recommendations: %d', count($result['recommendations'])));

        // Display summary
        $io->section('Summary');
        $io->table(['Metric', 'Value'], [
            ['Total recommendations', $result['summary']['total_recommendations']],
            ['High priority', $result['summary']['high_priority_count']],
            ['Medium priority', $result['summary']['medium_priority_count']],
            ['Low priority', $result['summary']['low_priority_count']],
            ['Contributors analyzed', $result['summary']['contributors_analyzed']],
            ['Critical workload', $result['summary']['critical_workload_count']],
        ]);

        // Display recommendations
        if (count($result['recommendations']) > 0) {
            $io->section(sprintf('Recommendations (%d)', count($result['recommendations'])));
            $recRows = [];
            foreach ($result['recommendations'] as $idx => $rec) {
                $recRows[] = [
                    $idx,
                    $rec['type'],
                    $rec['severity_level'],
                    $rec['priority_score'],
                    $rec['title'],
                    $rec['contributor']->getFullName(),
                ];
            }
            $io->table(['#', 'Type', 'Severity', 'Priority', 'Title', 'Contributor'], $recRows);

            // Show details of first few recommendations
            $io->section('Top 3 Recommendations Details');
            foreach (array_slice($result['recommendations'], 0, 3) as $idx => $rec) {
                $io->writeln(sprintf('<info>[%d] %s</info>', $idx, $rec['title']));
                $io->writeln(sprintf('  Contributor: %s', $rec['contributor']->getFullName()));
                $io->writeln(sprintf('  Type: %s', $rec['type']));
                $io->writeln(sprintf('  Severity: %s', $rec['severity_level']));
                $io->writeln(sprintf('  Priority: %.2f', $rec['priority_score']));
                $io->writeln(sprintf('  Description: %s', $rec['description']));
                if (isset($rec['expected_impact'])) {
                    $io->writeln(sprintf('  Expected Impact: %s', $rec['expected_impact']));
                }
                if (isset($rec['reasoning'])) {
                    $io->writeln(sprintf('  Reasoning: %s', $rec['reasoning']));
                }
                $io->newLine();
            }
        } else {
            $io->warning('No recommendations generated!');

            // Debug: Show what might be preventing recommendations
            $io->section('Debug Information');
            $io->writeln('Checking potential issues:');
            $io->writeln(sprintf('- Period: %s to %s', $startDate->format('Y-m-d'), $endDate->format('Y-m-d')));
            $io->writeln(sprintf('- Critical contributors: %d', count($analysis['critical'])));
            $io->writeln(sprintf('- Overloaded contributors: %d', count($analysis['overloaded'])));
            $io->writeln(sprintf('- Underutilized contributors: %d', count($analysis['underutilized'])));
        }

        $io->success('Debug completed');

        return Command::SUCCESS;
    }
}
