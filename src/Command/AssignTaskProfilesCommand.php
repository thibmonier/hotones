<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ProfileRepository;
use App\Repository\ProjectTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:assign-task-profiles',
    description: 'Automatically assign profiles to project tasks based on their name',
    aliases: ['hotones:assign-task-profiles'],
)]
class AssignTaskProfilesCommand extends Command
{
    private array $profileMapping = [
        // Keywords => Profile name
        'frontend'       => 'développeur frontend',
        'front-end'      => 'développeur frontend',
        'react'          => 'développeur frontend',
        'vue'            => 'développeur frontend',
        'angular'        => 'développeur frontend',
        'ui'             => 'développeur frontend',
        'interface'      => 'développeur frontend',
        'backend'        => 'développeur backend',
        'back-end'       => 'développeur backend',
        'api'            => 'développeur backend',
        'serveur'        => 'développeur backend',
        'base de'        => 'développeur backend',
        'database'       => 'développeur backend',
        'fullstack'      => 'développeur fullstack',
        'full-stack'     => 'développeur fullstack',
        'développement'  => 'développeur fullstack',
        'dev '           => 'développeur fullstack',
        'design'         => 'UI designer',
        'maquette'       => 'UI designer',
        'graphique'      => 'UI designer',
        'ux'             => 'UX designer',
        'ergonomie'      => 'UX designer',
        'wireframe'      => 'UX designer',
        'prototype'      => 'UX designer',
        'test'           => 'développeur fullstack',
        'recette'        => 'développeur fullstack',
        'validation'     => 'développeur fullstack',
        'qa'             => 'développeur fullstack',
        'deploy'         => 'Lead developer',
        'déploiement'    => 'Lead developer',
        'infrastructure' => 'Lead developer',
        'devops'         => 'Lead developer',
        'analyse'        => 'product owner',
        'spécification'  => 'product owner',
        'cahier'         => 'product owner',
        'conception'     => 'product owner',
        'gestion'        => 'chef de projet',
        'coordination'   => 'chef de projet',
        'planning'       => 'chef de projet',
        'suivi'          => 'chef de projet',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectTaskRepository $taskRepository,
        private readonly ProfileRepository $profileRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'project-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Only assign profiles to tasks of a specific project',
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be assigned without persisting')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Override existing profile assignments');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectId = $input->getOption('project-id');
        $dryRun    = $input->getOption('dry-run');
        $force     = $input->getOption('force');

        $io->title('Assign Profiles to Project Tasks');

        // Load all profiles into memory for quick lookup
        $profiles = [];
        foreach ($this->profileRepository->findAll() as $profile) {
            $profiles[mb_strtolower($profile->getName())] = $profile;
        }

        // Get tasks to process
        if ($projectId) {
            $tasks = $this->taskRepository
                ->createQueryBuilder('t')
                ->where('t.project = :projectId')
                ->setParameter('projectId', $projectId)
                ->getQuery()
                ->getResult();
            $io->comment(sprintf('Processing tasks for project #%d', $projectId));
        } else {
            $tasks = $this->taskRepository->findAll();
            $io->comment('Processing all tasks in database');
        }

        if (empty($tasks)) {
            $io->warning('No tasks found.');

            return Command::SUCCESS;
        }

        $io->comment(sprintf('Found %d task(s) to analyze', count($tasks)));

        $assigned = 0;
        $skipped  = 0;
        $noMatch  = 0;

        foreach ($tasks as $task) {
            // Skip if task already has a profile and not forcing
            if ($task->getRequiredProfile() !== null && !$force) {
                ++$skipped;
                continue;
            }

            $profile = $this->findMatchingProfile($task->name, $profiles);

            if ($profile === null) {
                ++$noMatch;
                $io->text(sprintf('  ⚠ No match: "%s"', $task->name));
                continue;
            }

            if (!$dryRun) {
                $task->setRequiredProfile($profile);
                $this->entityManager->persist($task);
            }

            ++$assigned;
            $io->text(sprintf('  ✓ %s"%s" → %s', $dryRun ? '[DRY RUN] ' : '', $task->name, $profile->getName()));
        }

        if (!$dryRun && $assigned > 0) {
            $this->entityManager->flush();
        }

        $io->newLine();
        $io->section('Summary');
        $io->table(['Status', 'Count'], [
            ['Assigned',                      $assigned],
            ['Skipped (already has profile)', $skipped],
            ['No match found',                $noMatch],
            ['Total processed',               count($tasks)],
        ]);

        if ($dryRun && $assigned > 0) {
            $io->warning('DRY RUN mode - no changes were persisted. Remove --dry-run to apply changes.');
        } elseif ($assigned > 0) {
            $io->success(sprintf('Successfully assigned profiles to %d task(s)', $assigned));
        }

        if ($noMatch > 0) {
            $io->note('Some tasks could not be matched automatically. You may need to assign profiles manually.');
        }

        return Command::SUCCESS;
    }

    private function findMatchingProfile(string $taskName, array $profiles): mixed
    {
        $taskNameLower = mb_strtolower($taskName);

        // Try to match keywords in task name
        foreach ($this->profileMapping as $keyword => $profileName) {
            if (str_contains($taskNameLower, (string) $keyword)) {
                $profileNameLower = mb_strtolower((string) $profileName);
                if (isset($profiles[$profileNameLower])) {
                    return $profiles[$profileNameLower];
                }
            }
        }

        return null;
    }
}
