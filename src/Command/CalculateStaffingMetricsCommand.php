<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\StaffingMetricsRepository;
use App\Service\StaffingMetricsCalculationService;
use DateInterval;
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
    name: 'app:calculate-staffing-metrics',
    description: 'Calcule les métriques de staffing (taux de staffing et TACE) pour une période donnée',
    aliases: ['hotones:calculate-staffing-metrics'],
)]
class CalculateStaffingMetricsCommand extends Command
{
    public function __construct(
        private readonly StaffingMetricsCalculationService $staffingService,
        private readonly StaffingMetricsRepository $staffingRepo
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('period', InputArgument::OPTIONAL, 'Période à calculer (YYYY ou YYYY-MM)', date('Y'))
            ->addOption('granularity', 'g', InputOption::VALUE_OPTIONAL, 'Granularité (monthly, quarterly, weekly)', 'monthly')
            ->addOption('force-recalculate', 'f', InputOption::VALUE_NONE, 'Force le re-calcul même si les données existent')
            ->addOption('range', 'r', InputOption::VALUE_OPTIONAL, 'Range en mois (-6 à +6 par défaut pour dashboard)', '12')
            ->setHelp('
Cette commande calcule les métriques de staffing pour une période donnée.
Elle génère le taux de staffing et le TACE pour tous les contributeurs actifs.

Exemples :
  app:calculate-staffing-metrics                        # Calcule pour l\'année courante
  app:calculate-staffing-metrics 2024                   # Calcule pour l\'année 2024
  app:calculate-staffing-metrics 2024-03                # Calcule pour mars 2024
  app:calculate-staffing-metrics --range=12             # Calcule les 12 derniers mois
  app:calculate-staffing-metrics --granularity=weekly   # Calcule par semaine
  app:calculate-staffing-metrics 2024 --force-recalculate # Force le recalcul
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $period           = $input->getArgument('period');
        $granularity      = $input->getOption('granularity');
        $forceRecalculate = $input->getOption('force-recalculate');
        $range            = (int) $input->getOption('range');

        // Validation de la granularité
        if (!in_array($granularity, ['monthly', 'quarterly', 'weekly'], true)) {
            $io->error('Granularité invalide. Valeurs acceptées: monthly, quarterly, weekly');

            return Command::FAILURE;
        }

        try {
            $startDate = null;
            $endDate   = null;

            // Parse la période
            if (preg_match('/^(\d{4})$/', $period, $matches)) {
                // Année complète
                $year      = (int) $matches[1];
                $startDate = new DateTime("$year-01-01");
                $endDate   = new DateTime("$year-12-31");

                $io->info("Calcul des métriques de staffing pour l'année $year ($granularity)...");
            } elseif (preg_match('/^(\d{4})-(\d{1,2})$/', $period, $matches)) {
                // Mois spécifique
                $year  = (int) $matches[1];
                $month = (int) $matches[2];

                if ($month < 1 || $month > 12) {
                    $io->error('Mois invalide. Doit être entre 1 et 12.');

                    return Command::FAILURE;
                }

                $startDate = new DateTime("$year-$month-01");
                $endDate   = clone $startDate;
                $endDate->modify('last day of this month');

                $io->info('Calcul des métriques de staffing pour '.$startDate->format('F Y').'...');
            } else {
                // Utiliser le range en mois
                $startDate = new DateTime();
                $startDate->sub(new DateInterval("P{$range}M"));
                $startDate->modify('first day of this month');

                $endDate = new DateTime();
                $endDate->modify('last day of this month');

                $io->info("Calcul des métriques de staffing pour les $range derniers mois ($granularity)...");
            }

            // Si force recalculate, supprimer les anciennes données
            if ($forceRecalculate) {
                $io->writeln('Suppression des anciennes métriques...');
                $deleted = $this->staffingRepo->deleteForDateRange($startDate, $endDate, $granularity);
                $io->info("$deleted métriques supprimées.");
            } elseif ($this->staffingRepo->existsForPeriod($startDate, $granularity)) {
                $io->warning('Des métriques existent déjà pour cette période. Utilisez --force-recalculate pour recalculer.');

                return Command::SUCCESS;
            }

            // Calculer et enregistrer les métriques
            $metricsCreated = $this->staffingService->calculateAndStoreMetrics(
                $startDate,
                $endDate,
                $granularity,
            );

            $io->success("Calcul terminé ! $metricsCreated métriques créées.");

            // Statistiques
            $io->section('Prochaines étapes');
            $io->writeln('• Consultez le dashboard de staffing : /staffing/dashboard');
            $io->writeln('• Configurez une tâche cron pour un calcul automatique :');
            $io->writeln('  0 6 * * * cd /path/to/project && php bin/console app:calculate-staffing-metrics --range=12');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Erreur lors du calcul des métriques de staffing : '.$e->getMessage());

            if ($output->isVerbose()) {
                $io->writeln('Stack trace:');
                $io->writeln($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
