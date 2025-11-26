<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\NpsSurveyRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:nps:mark-expired',
    description: 'Marque les enquêtes NPS expirées comme expirées',
)]
class NpsMarkExpiredCommand extends Command
{
    public function __construct(
        private readonly NpsSurveyRepository $npsSurveyRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Marquage des enquêtes NPS expirées');

        $count = $this->npsSurveyRepository->markExpiredSurveysAsExpired();

        if ($count > 0) {
            $io->success(sprintf('%d enquête(s) marquée(s) comme expirée(s)', $count));
        } else {
            $io->info('Aucune enquête expirée à marquer');
        }

        return Command::SUCCESS;
    }
}
