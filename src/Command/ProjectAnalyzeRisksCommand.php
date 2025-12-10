<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ProjectRiskAnalyzer;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:project:analyze-risks',
    description: 'Analyse la santé de tous les projets actifs et calcule leurs scores',
)]
class ProjectAnalyzeRisksCommand extends Command
{
    public function __construct(
        private readonly ProjectRiskAnalyzer $riskAnalyzer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(<<<'HELP'
Cette commande analyse la santé de tous les projets actifs et calcule leurs scores.

Utilisation:
  php bin/console app:project:analyze-risks

Pour chaque projet, la commande calcule:
- Score de budget (40%) : suivi des dépassements budgétaires
- Score de planning (30%) : détection des retards
- Score de vélocité (20%) : activité de l'équipe
- Score de qualité (10%) : marge et qualité de livraison

Les scores sont enregistrés dans la table project_health_score.

Niveaux de santé:
- Healthy (>80) : Projet en bonne santé
- Warning (50-80) : Projet nécessitant une attention
- Critical (<50) : Projet en danger, action immédiate requise
HELP
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Analyse de la santé des projets actifs');

        try {
            $io->section('Analyse en cours...');

            $healthScores = $this->riskAnalyzer->analyzeAllActiveProjects();

            $io->success(sprintf('%d projets analysés avec succès', count($healthScores)));

            // Display summary by health level
            $healthy  = 0;
            $warning  = 0;
            $critical = 0;

            foreach ($healthScores as $healthScore) {
                match ($healthScore->getHealthLevel()) {
                    'healthy'  => $healthy++,
                    'warning'  => $warning++,
                    'critical' => $critical++,
                    default    => null,
                };
            }

            $io->section('Résumé');
            $io->horizontalTable(
                ['Sains', 'Alerte', 'Critiques', 'Total'],
                [[$healthy, $warning, $critical, count($healthScores)]],
            );

            // Display critical projects details
            if ($critical > 0) {
                $io->section('Projets critiques nécessitant une action immédiate');
                $criticalData = [];

                foreach ($healthScores as $healthScore) {
                    if ($healthScore->getHealthLevel() === 'critical') {
                        $project        = $healthScore->getProject();
                        $criticalData[] = [
                            $project->getName(),
                            $healthScore->getScore().'/100',
                            'Budget: '.$healthScore->getBudgetScore(),
                            'Planning: '.$healthScore->getTimelineScore(),
                            count($healthScore->getRecommendations() ?: []).' recommandations',
                        ];
                    }
                }

                $io->table(
                    ['Projet', 'Score', 'Budget', 'Planning', 'Recommandations'],
                    $criticalData,
                );

                $io->warning([
                    sprintf('%d projet(s) en état critique !', $critical),
                    'Consultez la page /projects/at-risk pour plus de détails',
                ]);
            }

            // Display warning projects
            if ($warning > 0) {
                $io->section('Projets en alerte');
                $warningData = [];

                foreach ($healthScores as $healthScore) {
                    if ($healthScore->getHealthLevel() === 'warning') {
                        $project       = $healthScore->getProject();
                        $warningData[] = [
                            $project->getName(),
                            $healthScore->getScore().'/100',
                        ];
                    }
                }

                $io->table(['Projet', 'Score'], $warningData);

                $io->note(sprintf('%d projet(s) nécessitent une surveillance', $warning));
            }

            // Success message
            if ($critical === 0 && $warning === 0) {
                $io->success('Tous les projets sont en bonne santé !');
            }

            $io->note([
                'Les scores sont disponibles dans l\'interface web à /projects/at-risk',
                'Cette commande peut être exécutée quotidiennement via le scheduler',
            ]);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Erreur lors de l\'analyse des projets : '.$e->getMessage());

            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
