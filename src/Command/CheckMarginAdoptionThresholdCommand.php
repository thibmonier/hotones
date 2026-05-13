<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\Project\Alerting\CheckMarginAdoptionRedThresholdHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Daily cron command — check margin adoption red threshold + fire Slack alert
 * after 7 consecutive days below the red threshold.
 *
 * Sprint-024 US-112 T-112-04. À exécuter en cron quotidien (matin) :
 *
 *   0 9 * * *  bin/console app:kpi:check-margin-adoption-threshold
 */
#[AsCommand(
    name: 'app:kpi:check-margin-adoption-threshold',
    description: 'Vérifie le seuil rouge persistant 7 jours de l\'adoption marge + alerte Slack si déclenché',
)]
final class CheckMarginAdoptionThresholdCommand extends Command
{
    public function __construct(
        private readonly CheckMarginAdoptionRedThresholdHandler $checker,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        ($this->checker)();

        $io->success('Margin adoption threshold check completed.');

        return Command::SUCCESS;
    }
}
