<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Project;
use App\Entity\ProjectSubTask;
use App\Entity\ProjectTask;
use App\Entity\Timesheet;
use App\Repository\ContributorRepository;
use App\Repository\ProjectRepository;
use App\Repository\ProjectTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-subtasks',
    description: 'Crée des sous-tâches de test pour les projets existants et rattache des temps',
)]
class CreateTestSubTasksCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProjectRepository $projects,
        private readonly ProjectTaskRepository $tasks,
        private readonly ContributorRepository $contributors,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('per-task', null, InputOption::VALUE_OPTIONAL, 'Nombre de sous-tâches par tâche (3 par défaut)', 3)
            ->addOption('attach-timesheets', null, InputOption::VALUE_NONE, 'Affecter une partie des timesheets aux sous-tâches créées');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $perTask  = max(1, (int) $input->getOption('per-task'));
        $attachTs = (bool) $input->getOption('attach-timesheets');

        $io->title('Création de sous-tâches de test');

        $projects       = $this->projects->findAllOrderedByName();
        $activeContribs = $this->contributors->findActiveContributors();
        if (count($activeContribs) === 0) {
            $io->warning('Aucun contributeur actif — arrêt.');

            return Command::SUCCESS;
        }

        $created = 0;
        foreach ($projects as $project) {
            /** @var Project $project */
            $regularTasks = $this->tasks->findProfitableTasksByProject($project);
            foreach ($regularTasks as $task) {
                /** @var ProjectTask $task */
                if ($task->getSubTasks()->count() > 0) {
                    $io->writeln("• Sous-tâches déjà présentes pour {$project->getName()} -> {$task->getName()} (skip)");
                    continue;
                }

                // Heures de base pour découpage
                $baseHours = $task->getEstimatedHoursRevised() ?? $task->getEstimatedHoursSold() ?? 24; // fallback 3j
                $parts     = $this->buildParts($perTask, (int) $baseHours);

                for ($i = 0; $i < count($parts); ++$i) {
                    $st = new ProjectSubTask();
                    $st->setTask($task);
                    $st->setTitle($this->defaultTitleForIndex($task, $i));

                    // Assigner contributeur: celui de la tâche si présent sinon aléatoire
                    $assignee = $task->getAssignedContributor() ?: $activeContribs[array_rand($activeContribs)];
                    $st->setAssignee($assignee);

                    $st->setInitialEstimatedHours(number_format((float) $parts[$i], 2, '.', ''));
                    $st->setRemainingHours(number_format((float) $parts[$i], 2, '.', ''));

                    // Statut distribué: 0=>todo, 1=>in_progress, dernier éventuellement done
                    if ($i === (count($parts) - 1) && $parts[$i] <= ($baseHours * 0.25)) {
                        $st->setStatus(ProjectSubTask::STATUS_DONE);
                        $st->setRemainingHours('0.00');
                    } elseif ($i === 1) {
                        $st->setStatus(ProjectSubTask::STATUS_IN_PROGRESS);
                    } else {
                        $st->setStatus(ProjectSubTask::STATUS_TODO);
                    }

                    $st->setPosition($i + 1);

                    $this->em->persist($st);
                    ++$created;
                }
            }
        }

        $this->em->flush();
        $io->success("$created sous-tâches créées");

        if ($attachTs) {
            $this->attachSomeTimesheets($io);
        }

        return Command::SUCCESS;
    }

    private function defaultTitleForIndex(ProjectTask $task, int $i): string
    {
        $labels = ['Découverte', 'Implémentation', 'Revue & QA', 'Livraison'];
        $label  = $labels[$i % count($labels)];

        return sprintf('%s — %s', $task->getName(), $label);
    }

    /**
     * Découpe en parts approximativement équilibrées (au moins 2h par part).
     *
     * @return int[]
     */
    private function buildParts(int $count, int $total): array
    {
        $count = max(1, $count);
        $min   = 2;
        if ($total < $count * $min) {
            return array_fill(0, $count, (int) max(1, floor($total / $count)));
        }
        $remaining = $total - ($count * $min);
        $parts     = array_fill(0, $count, $min);
        for ($i = 0; $i < $remaining; ++$i) {
            ++$parts[$i % $count];
        }

        return $parts;
    }

    private function attachSomeTimesheets(SymfonyStyle $io): void
    {
        // Affecter ~30% des timesheets existantes à des sous-tâches du même projet
        $tsRepo = $this->em->getRepository(Timesheet::class);
        $allTs  = $tsRepo->findBy([], ['date' => 'DESC']);
        $count  = 0;
        foreach ($allTs as $ts) {
            if (random_int(1, 100) > 30) {
                continue;
            }
            /** @var Timesheet $ts */
            $project  = $ts->getProject();
            $subTasks = $this->em->getRepository(ProjectSubTask::class)->findBy(['project' => $project]);
            if (!$subTasks) {
                continue;
            }
            // Si possible, choisir une sous-tâche assignée au même contributeur
            $matching = array_values(array_filter($subTasks, fn (ProjectSubTask $st): bool => $st->getAssignee() && $st->getAssignee()->getId() === $ts->getContributor()->getId()));
            $chosen   = $matching ? $matching[array_rand($matching)] : $subTasks[array_rand($subTasks)];
            $ts->setSubTask($chosen);
            ++$count;
        }
        $this->em->flush();
        $io->writeln("✓ $count timesheets rattachées à des sous-tâches");
    }
}
