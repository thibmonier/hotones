<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ClientServiceLevelCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:client:recalculate-service-level',
    description: 'Recalcule automatiquement le niveau de service des clients en mode auto',
)]
class RecalculateClientServiceLevelCommand extends Command
{
    public function __construct(
        private readonly ClientServiceLevelCalculator $calculator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('year', 'y', InputOption::VALUE_OPTIONAL, 'Année pour le calcul du CA', date('Y'))->setHelp(
            'Cette commande recalcule le niveau de service de tous les clients en mode automatique basé sur leur CA.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $year = (int) $input->getOption('year');

        $io->title('Recalcul des niveaux de service clients');
        $io->text("Année de référence : {$year}");

        $config = $this->calculator->getConfiguration();
        $io->section('Configuration');
        $io->listing([
            "Top {$config['top_vip_rank']} clients → VIP",
            "Top {$config['top_priority_rank']} clients → Prioritaire",
            "CA < {$config['low_threshold']}€ → Basse priorité",
            'Autres → Standard',
        ]);

        $io->section('Traitement');
        $count = $this->calculator->recalculateAllAutoClients($year);

        $io->success("{$count} client(s) en mode auto ont été mis à jour.");

        return Command::SUCCESS;
    }
}
