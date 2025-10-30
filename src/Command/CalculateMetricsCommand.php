<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Analytics\MetricsCalculationService;
use DateTime;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-metrics',
    description: 'Calcule les métriques analytics pour une période donnée',
)]
class CalculateMetricsCommand extends Command
{
    public function __construct(
        private readonly MetricsCalculationService $metricsService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('period', InputArgument::OPTIONAL, 'Période à calculer (YYYY ou YYYY-MM)', date('Y'))
            ->addOption('granularity', 'g', InputOption::VALUE_OPTIONAL, 'Granularité (monthly, quarterly, yearly)', 'monthly')
            ->addOption('force-recalculate', 'f', InputOption::VALUE_NONE, 'Force le re-calcul complet de l\'année')
            ->setHelp('
Cette commande calcule les métriques analytics pour une période donnée.

Exemples :
  app:calculate-metrics 2024                    # Calcule pour l\'année 2024
  app:calculate-metrics 2024-03                 # Calcule pour mars 2024
  app:calculate-metrics --granularity=quarterly # Calcule par trimestre
  app:calculate-metrics 2024 --force-recalculate # Recalcule entièrement 2024
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $period           = $input->getArgument('period');
        $granularity      = $input->getOption('granularity');
        $forceRecalculate = $input->getOption('force-recalculate');

        // Validation de la granularité
        if (!in_array($granularity, ['monthly', 'quarterly', 'yearly'])) {
            $io->error('Granularité invalide. Valeurs acceptées: monthly, quarterly, yearly');

            return Command::FAILURE;
        }

        try {
            // Parse la période
            if (preg_match('/^(\d{4})$/', $period, $matches)) {
                // Année complète
                $year = (int) $matches[1];

                if ($forceRecalculate) {
                    $io->info("Re-calcul complet de l'année $year...");
                    $this->metricsService->recalculateMetricsForYear($year);
                } else {
                    $io->info("Calcul des métriques pour l'année $year ($granularity)...");

                    switch ($granularity) {
                        case 'monthly':
                            for ($month = 1; $month <= 12; ++$month) {
                                $date = new DateTime("$year-$month-01");
                                $this->metricsService->calculateMetricsForPeriod($date, 'monthly');
                                $io->writeln('  ✓ '.$date->format('F Y'));
                            }
                            break;

                        case 'quarterly':
                            for ($quarter = 1; $quarter <= 4; ++$quarter) {
                                $month = ($quarter - 1) * 3 + 1;
                                $date  = new DateTime("$year-$month-01");
                                $this->metricsService->calculateMetricsForPeriod($date, 'quarterly');
                                $io->writeln("  ✓ Q$quarter $year");
                            }
                            break;

                        case 'yearly':
                            $date = new DateTime("$year-01-01");
                            $this->metricsService->calculateMetricsForPeriod($date, 'yearly');
                            $io->writeln("  ✓ Année $year");
                            break;
                    }
                }
            } elseif (preg_match('/^(\d{4})-(\d{1,2})$/', $period, $matches)) {
                // Mois spécifique
                $year  = (int) $matches[1];
                $month = (int) $matches[2];

                if ($month < 1 || $month > 12) {
                    $io->error('Mois invalide. Doit être entre 1 et 12.');

                    return Command::FAILURE;
                }

                $date = new DateTime("$year-$month-01");
                $io->info('Calcul des métriques pour '.$date->format('F Y').'...');

                $this->metricsService->calculateMetricsForPeriod($date, $granularity);
                $io->writeln('  ✓ '.$date->format('F Y'));
            } else {
                $io->error('Format de période invalide. Utilisez YYYY ou YYYY-MM');

                return Command::FAILURE;
            }

            $io->success('Calcul des métriques terminé avec succès !');

            // Statistiques
            $io->section('Prochaines étapes');
            $io->writeln('• Consultez le dashboard : /analytics/dashboard');
            $io->writeln('• Configurez une tâche cron pour un calcul automatique :');
            $io->writeln('  0 6 * * * cd /path/to/project && php bin/console app:calculate-metrics');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Erreur lors du calcul des métriques : '.$e->getMessage());

            if ($output->isVerbose()) {
                $io->writeln('Stack trace:');
                $io->writeln($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
