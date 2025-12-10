<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\AlertDetectionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-alerts',
    description: 'Vérifie et déclenche les alertes (budget, marge, surcharge, paiements)',
)]
class CheckAlertsCommand extends Command
{
    public function __construct(
        private readonly AlertDetectionService $alertDetectionService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification des alertes');

        $stats = $this->alertDetectionService->checkAllAlerts();

        $totalAlerts = array_sum($stats);

        // Display results table
        $io->table(
            ['Type d\'alerte', 'Nombre'],
            [
                ['Budget dépassé', $stats['budget_alerts']],
                ['Marge faible', $stats['margin_alerts']],
                ['Surcharge contributeur', $stats['overload_alerts']],
                ['Paiement proche', $stats['payment_alerts']],
                ['', ''],
                ['TOTAL', $totalAlerts],
            ],
        );

        if ($totalAlerts === 0) {
            $io->success('Aucune alerte détectée.');
        } else {
            $io->success(sprintf(
                '%d alerte%s détectée%s et dispatchée%s.',
                $totalAlerts,
                $totalAlerts > 1 ? 's' : '',
                $totalAlerts > 1 ? 's' : '',
                $totalAlerts > 1 ? 's' : '',
            ));

            $io->note('Les notifications ont été créées pour les utilisateurs concernés.');
        }

        return Command::SUCCESS;
    }
}
