<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Project;
use App\Service\ProjectRiskAnalyzer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:analyze-project-risks',
    description: 'Analyse les risques des projets actifs et affiche un rapport',
    aliases: ['hotones:analyze-project-risks'],
)]
class AnalyzeProjectRisksCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProjectRiskAnalyzer $riskAnalyzer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('critical-only', 'c', InputOption::VALUE_NONE, 'Afficher uniquement les projets critiques')
            ->addOption(
                'min-score',
                'm',
                InputOption::VALUE_REQUIRED,
                'Score minimum (afficher les projets en dessous de ce score)',
                80,
            )
            ->addOption('verbose-risks', 'r', InputOption::VALUE_NONE, 'Afficher le détail des risques');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Analyse des risques des projets actifs');

        // Options
        $criticalOnly = (bool) $input->getOption('critical-only');
        $minScore = (int) $input->getOption('min-score');
        $verboseRisks = (bool) $input->getOption('verbose-risks');

        // Récupérer tous les projets actifs ou en cours
        $io->section('Récupération des projets...');
        $projects = $this->em->getRepository(Project::class)->findBy(['status' => ['in_progress', 'active']], [
            'name' => 'ASC',
        ]);

        $io->writeln(sprintf('✓ %d projets actifs trouvés', count($projects)));

        // Analyser les projets
        $io->section('Analyse des risques...');
        $atRiskProjects = $this->riskAnalyzer->analyzeMultipleProjects($projects);

        // Filtrer selon les options
        if ($criticalOnly) {
            $atRiskProjects = array_filter($atRiskProjects, fn ($p): bool => $p['analysis']['riskLevel'] === 'critical');
            $io->writeln('Filtrage: projets critiques uniquement');
        } elseif ($minScore < 80) {
            $atRiskProjects = array_filter($atRiskProjects, fn ($p): bool => $p['analysis']['healthScore'] < $minScore);
            $io->writeln(sprintf('Filtrage: score < %d', $minScore));
        }

        // Statistiques
        $stats = [
            'total' => count($projects),
            'atRisk' => count($atRiskProjects),
            'critical' => count(array_filter(
                $atRiskProjects,
                fn ($p): bool => $p['analysis']['riskLevel'] === 'critical',
            )),
            'high' => count(array_filter($atRiskProjects, fn ($p): bool => $p['analysis']['riskLevel'] === 'high')),
            'medium' => count(array_filter($atRiskProjects, fn ($p): bool => $p['analysis']['riskLevel'] === 'medium')),
        ];

        $io->section('Résumé');
        $io->horizontalTable(['Métrique', 'Valeur'], [
            ['Projets actifs', $stats['total']],
            [
                'Projets à risque',
                sprintf(
                    '%d (%.1f%%)',
                    $stats['atRisk'],
                    $stats['total'] > 0 ? ($stats['atRisk'] / $stats['total']) * 100 : 0,
                ),
            ],
            ['Critiques', $stats['critical']],
            ['Risque élevé', $stats['high']],
            ['Risque moyen', $stats['medium']],
        ]);

        // Afficher les projets à risque
        if (count($atRiskProjects) === 0) {
            $io->success('Aucun projet à risque détecté !');

            return Command::SUCCESS;
        }

        $io->section(sprintf('Projets à risque (%d)', count($atRiskProjects)));

        // Tableau des projets
        $table = new Table($output);
        $table->setHeaders(['Score', 'Niveau', 'Projet', 'Client', 'CA', 'Progression', 'Risques']);

        foreach ($atRiskProjects as $item) {
            $project = $item['project'];
            $analysis = $item['analysis'];

            $riskLevel = match ($analysis['riskLevel']) {
                'critical' => '<error>CRITIQUE</error>',
                'high' => '<fg=red>ÉLEVÉ</>',
                'medium' => '<comment>MOYEN</>',
                default => 'FAIBLE',
            };

            $table->addRow([
                sprintf(
                    '<fg=%s>%d%%</>',
                    $analysis['healthScore'] < 40 ? 'red' : ($analysis['healthScore'] < 60 ? 'yellow' : 'white'),
                    $analysis['healthScore'],
                ),
                $riskLevel,
                $project->getName(),
                $project->getClient()?->getName() ?? '-',
                number_format((float) $project->getTotalSoldAmount(), 0, ',', ' ').'€',
                sprintf('%.0f%%', (float) $project->getGlobalProgress()),
                count($analysis['risks']),
            ]);

            // Afficher le détail des risques si demandé
            if ($verboseRisks && count($analysis['risks']) > 0) {
                foreach ($analysis['risks'] as $risk) {
                    $severityColor = match ($risk['severity']) {
                        'critical' => 'red',
                        'high' => 'yellow',
                        default => 'cyan',
                    };

                    $table->addRow([
                        '',
                        sprintf('<fg=%s>→ %s</>', $severityColor, strtoupper((string) $risk['severity'])),
                        $risk['message'],
                        '',
                        '',
                        '',
                        '',
                    ]);
                }
            }
        }

        $table->render();

        // Recommandations
        if ($stats['critical'] > 0) {
            $io->warning(sprintf(
                '%d projet(s) en état critique nécessitent une attention immédiate !',
                $stats['critical'],
            ));
        }

        if ($stats['high'] > 0) {
            $io->note(sprintf('%d projet(s) avec un risque élevé à surveiller.', $stats['high']));
        }

        // Lien vers le dashboard
        $io->writeln('');
        $io->writeln('💡 <info>Consultez le dashboard des risques pour plus de détails :</info>');
        $io->writeln('   <href=/risks/projects>/risks/projects</>');

        return Command::SUCCESS;
    }
}
