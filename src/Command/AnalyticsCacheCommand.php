<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\AnalyticsCacheService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:analytics:cache',
    description: 'Gestion du cache analytics (clear, warmup)',
)]
class AnalyticsCacheCommand extends Command
{
    public function __construct(
        private readonly AnalyticsCacheService $cacheService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('clear', 'c', InputOption::VALUE_NONE, 'Vider le cache analytics')
            ->addOption('warmup', 'w', InputOption::VALUE_NONE, 'Préchauffer le cache avec métriques courantes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('clear')) {
            $io->info('Invalidation du cache analytics...');
            $this->cacheService->invalidateAll();
            $io->success('Cache analytics vidé avec succès');
        }

        if ($input->getOption('warmup')) {
            $io->info('Préchauffage du cache analytics...');

            // Définir ici les métriques à précalculer
            $metrics = [
                // Exemple: 'total_revenue' => fn() => $this->revenueCalculator->getTotalRevenue(),
            ];

            $this->cacheService->warmup($metrics);
            $io->success(sprintf('%d métriques précalculées', count($metrics)));
        }

        if (!$input->getOption('clear') && !$input->getOption('warmup')) {
            $io->info('Utilisation: php bin/console app:analytics:cache [--clear] [--warmup]');
        }

        return Command::SUCCESS;
    }
}
