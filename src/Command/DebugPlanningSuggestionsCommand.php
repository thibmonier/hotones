<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Project;
use App\Service\Planning\ProjectPlanningAssistant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:debug:planning-suggestions', description: 'Debug planning suggestions for a project')]
class DebugPlanningSuggestionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectPlanningAssistant $assistant,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('project_id', InputArgument::REQUIRED, 'The project ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectId = (int) $input->getArgument('project_id');

        $project = $this->entityManager->getRepository(Project::class)->find($projectId);

        if (!$project) {
            $io->error(sprintf('Project with ID %d not found', $projectId));

            return Command::FAILURE;
        }

        $io->title(sprintf('Planning suggestions for project: %s (ID: %d)', $project->getName(), $project->getId()));

        // Generate suggestions
        $io->section('Generating suggestions...');
        $result = $this->assistant->generateSuggestions($project);

        // Display statistics
        $io->section('Statistics');
        $io->table(['Metric', 'Value'], [
            ['Total tasks', $result['statistics']['totalTasks']],
            ['Assigned tasks', $result['statistics']['assignedTasks']],
            ['Unassigned tasks', $result['statistics']['unassignedTasks']],
            ['Average confidence', sprintf('%.2f%%', $result['statistics']['averageConfidence'] * 100)],
        ]);

        // Display suggestions
        if (count($result['suggestions']) > 0) {
            $io->section(sprintf('Suggestions (%d)', count($result['suggestions'])));
            $suggestionRows = [];
            foreach ($result['suggestions'] as $suggestion) {
                $suggestionRows[] = [
                    $suggestion['task']->getName(),
                    $suggestion['contributor']->getFullName(),
                    $suggestion['startDate']->format('Y-m-d'),
                    $suggestion['endDate']->format('Y-m-d'),
                    sprintf('%.1fh/day', $suggestion['dailyHours']),
                    sprintf('%.0f%%', $suggestion['confidence'] * 100),
                    $suggestion['reasoning'],
                ];
            }
            $io->table([
                'Task',
                'Contributor',
                'Start',
                'End',
                'Daily Hours',
                'Confidence',
                'Reasoning',
            ], $suggestionRows);
        } else {
            $io->warning('No suggestions generated');
        }

        // Display unassigned tasks
        if (count($result['unassigned']) > 0) {
            $io->section(sprintf('Unassigned tasks (%d)', count($result['unassigned'])));
            $unassignedRows = [];
            foreach ($result['unassigned'] as $task) {
                $unassignedRows[] = [
                    $task->getName(),
                    $task->getRequiredProfile()?->getName() ?? 'NONE',
                    sprintf('%.1fh', $task->getEstimatedHoursRevised() ?? $task->getEstimatedHoursSold() ?? 0),
                    $task->getStatus(),
                ];
            }
            $io->table(['Task', 'Required Profile', 'Estimated Hours', 'Status'], $unassignedRows);
        }

        // Additional debug: Check all tasks in project
        $io->section('All tasks in project');
        $allTasks = $project->getTasks();
        $taskRows = [];
        foreach ($allTasks as $task) {
            $taskRows[] = [
                $task->getId(),
                $task->getName(),
                $task->isActive() ? 'Y' : 'N',
                $task->getStatus(),
                $task->getCountsForProfitability() ? 'Y' : 'N',
                $task->getRequiredProfile()?->getName() ?? 'NONE',
                sprintf('%.1fh', $task->getEstimatedHoursRevised() ?? $task->getEstimatedHoursSold() ?? 0),
            ];
        }
        $io->table(['ID', 'Name', 'Active', 'Status', 'Counts', 'Profile', 'Hours'], $taskRows);

        $io->success('Debug completed');

        return Command::SUCCESS;
    }
}
