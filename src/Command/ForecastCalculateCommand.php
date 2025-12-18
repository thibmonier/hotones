<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ForecastingService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:forecast:calculate',
    description: 'Génère les prévisions de CA pour les prochains mois',
)]
class ForecastCalculateCommand extends Command
{
    public function __construct(
        private readonly ForecastingService $forecastingService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('months', 'm', InputOption::VALUE_OPTIONAL, 'Nombre de mois à prévoir (3, 6, ou 12)', 12)
            ->setHelp(
                <<<'HELP'
Cette commande génère les prévisions de chiffre d'affaires pour les prochains mois.

Utilisation:
  php bin/console app:forecast:calculate
  php bin/console app:forecast:calculate --months=6
  php bin/console app:forecast:calculate -m 3

La commande utilise:
- Régression linéaire sur les 12 derniers mois
- Analyse de saisonnalité (moyenne sur 3 ans)
- Génération de 3 scénarios (réaliste, optimiste, pessimiste)

Les prévisions sont enregistrées dans la table fact_forecast.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $months = (int) $input->getOption('months');

        if (!in_array($months, [3, 6, 12], true)) {
            $io->error('Le nombre de mois doit être 3, 6, ou 12');

            return Command::FAILURE;
        }

        $io->title(sprintf('Génération des prévisions pour les %d prochains mois', $months));

        try {
            $io->section('Analyse des données historiques...');
            $io->text('- Collecte des revenus des 12 derniers mois');
            $io->text('- Calcul de la tendance (régression linéaire)');
            $io->text('- Analyse de la saisonnalité (moyennes sur 3 ans)');

            $io->section('Génération des prévisions...');
            $forecasts = $this->forecastingService->generateForecasts($months);

            $io->success(sprintf('%d prévisions générées avec succès', count($forecasts)));

            // Display summary table
            $io->section('Résumé des prévisions (Scénario réaliste)');
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
                ['Période', 'Prévision', 'Confiance Min', 'Confiance Max'],
                $tableData,
            );

            $io->note([
                'Les prévisions sont disponibles dans l\'interface web à /analytics/forecasting',
                'Utilisez app:forecast:update-accuracy pour mettre à jour la précision des prévisions passées',
            ]);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Erreur lors de la génération des prévisions : '.$e->getMessage());

            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
