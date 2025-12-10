<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\FactForecast;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:forecast:generate-mock',
    description: 'Génère des prévisions simulées pour tester l\'interface',
)]
class GenerateMockForecastsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('months', 'm', InputOption::VALUE_OPTIONAL, 'Nombre de mois à générer', 12)
            ->setHelp('Cette commande génère des prévisions fictives pour tester l\'interface sans calculs lourds.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $months = (int) $input->getOption('months');

        if (!in_array($months, [3, 6, 12], true)) {
            $io->error('Le nombre de mois doit être 3, 6, ou 12');

            return Command::FAILURE;
        }

        $io->title(sprintf('Génération de %d mois de prévisions simulées', $months));

        // Clear existing forecasts
        $this->em->createQuery('DELETE FROM App\Entity\FactForecast')->execute();

        $forecasts   = [];
        $baseRevenue = 50000; // Base revenue of 50k€
        $now         = new DateTimeImmutable();

        for ($i = 1; $i <= $months; ++$i) {
            $periodStart = $now->modify("+{$i} months")->modify('first day of this month');
            $periodEnd   = $periodStart->modify('last day of this month');

            // Add some growth trend (2% per month) and seasonality
            $monthNum          = (int) $periodStart->format('n');
            $seasonalityFactor = $this->getSeasonalityFactor($monthNum);
            $trendGrowth       = 1 + ($i * 0.02); // 2% growth per month
            $predictedRevenue  = $baseRevenue * $trendGrowth * $seasonalityFactor;

            // Create 3 scenarios
            $scenarios = [
                'realistic'   => 1.00,
                'optimistic'  => 1.10,
                'pessimistic' => 0.85,
            ];

            foreach ($scenarios as $scenario => $adjustment) {
                $revenue = $predictedRevenue * $adjustment;

                $forecast = new FactForecast();
                $forecast->setPeriodStart($periodStart);
                $forecast->setPeriodEnd($periodEnd);
                $forecast->setScenario($scenario);
                $forecast->setPredictedRevenue(number_format($revenue, 2, '.', ''));

                // Confidence intervals
                $confidenceRange = $scenario === 'realistic' ? 0.15 : 0.25;
                $forecast->setConfidenceMin(number_format($revenue * (1 - $confidenceRange), 2, '.', ''));
                $forecast->setConfidenceMax(number_format($revenue * (1 + $confidenceRange), 2, '.', ''));

                $forecast->setMetadata([
                    'method'             => 'mock_data',
                    'seasonality_factor' => $seasonalityFactor,
                    'trend_growth'       => $trendGrowth,
                ]);

                $this->em->persist($forecast);
                $forecasts[] = $forecast;
            }
        }

        $this->em->flush();

        $io->success(sprintf('%d prévisions simulées générées avec succès', count($forecasts)));

        // Display summary
        $io->section('Aperçu des prévisions (Scénario réaliste)');
        $tableData          = [];
        $realisticForecasts = array_filter($forecasts, fn ($f) => $f->getScenario() === 'realistic');

        foreach ($realisticForecasts as $forecast) {
            $tableData[] = [
                $forecast->getPeriodStart()->format('M Y'),
                number_format((float) $forecast->getPredictedRevenue(), 0, ',', ' ').' €',
                number_format((float) $forecast->getConfidenceMin(), 0, ',', ' ').' €',
                number_format((float) $forecast->getConfidenceMax(), 0, ',', ' ').' €',
            ];
        }

        $io->table(
            ['Période', 'Prévision', 'Min', 'Max'],
            $tableData,
        );

        $io->note('Données simulées créées. Consultez /analytics/forecasting pour voir le résultat.');

        return Command::SUCCESS;
    }

    /**
     * Simple seasonality pattern (summer slower, Q4 stronger).
     */
    private function getSeasonalityFactor(int $month): float
    {
        $seasonality = [
            1  => 0.95, // January
            2  => 0.95, // February
            3  => 1.05, // March
            4  => 1.10, // April
            5  => 1.10, // May
            6  => 1.05, // June
            7  => 0.85, // July (summer slowdown)
            8  => 0.85, // August (summer slowdown)
            9  => 1.10, // September
            10 => 1.15, // October
            11 => 1.20, // November
            12 => 1.15, // December
        ];

        return $seasonality[$month] ?? 1.0;
    }
}
