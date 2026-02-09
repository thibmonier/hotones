<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Contributor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:debug:task-assignment', description: 'Affiche les tâches assignées à un contributeur')]
class DebugTaskAssignmentCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('contributor_id', InputArgument::OPTIONAL, 'ID du contributeur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io            = new SymfonyStyle($input, $output);
        $contributorId = $input->getArgument('contributor_id');

        if ($contributorId) {
            $contributor = $this->em->getReference(Contributor::class, $contributorId);
            if (!$contributor) {
                $io->error("Contributeur #$contributorId non trouvé");

                return Command::FAILURE;
            }
            $this->showContributorTasks($io, $contributor);
        } else {
            $this->showAllContributorsWithTasks($io);
        }

        return Command::SUCCESS;
    }

    private function showContributorTasks(SymfonyStyle $io, Contributor $contributor): void
    {
        $io->title("Tâches assignées à {$contributor->getName()} (ID: {$contributor->getId()})");

        $io->section('Informations du contributeur');
        $io->table(['Champ', 'Valeur'], [
            ['ID', $contributor->getId()],
            ['Nom', $contributor->getName()],
            ['Prénom', $contributor->getFirstName()],
            ['Nom de famille', $contributor->getLastName()],
            ['Email', $contributor->getEmail() ?? 'N/A'],
            ['Actif', $contributor->isActive() ? 'Oui' : 'Non'],
            ['Utilisateur lié', $contributor->getUser() ? $contributor->getUser()->getEmail() : 'Aucun'],
        ]);

        // Récupérer les tâches assignées
        $tasks = $this->em
            ->createQueryBuilder()
            ->select('t', 'p')
            ->from(\App\Entity\ProjectTask::class, 't')
            ->join('t.project', 'p')
            ->where('t.assignedContributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();

        $io->section(sprintf('Tâches assignées (%d)', count($tasks)));

        if (empty($tasks)) {
            $io->warning('Aucune tâche assignée');

            return;
        }

        $rows = [];
        foreach ($tasks as $task) {
            $rows[] = [
                $task->getId(),
                $task->getProject()->getName(),
                $task->getName(),
                $task->getActive() ? '✓' : '✗',
                $task->getProject()->getStatus(),
                $task->getStatus(),
            ];
        }

        $io->table(['ID', 'Projet', 'Tâche', 'Active', 'Statut Projet', 'Statut Tâche'], $rows);
    }

    private function showAllContributorsWithTasks(SymfonyStyle $io): void
    {
        $io->title('Contributeurs avec tâches assignées');

        $results = $this->em
            ->createQueryBuilder()
            ->select('c.id', 'c.firstName', 'c.lastName', 'c.active', 'COUNT(t.id) as taskCount')
            ->from(Contributor::class, 'c')
            ->leftJoin(\App\Entity\ProjectTask::class, 't', 'WITH', 't.assignedContributor = c.id')
            ->groupBy('c.id')
            ->having('COUNT(t.id) > 0')
            ->orderBy('taskCount', 'DESC')
            ->getQuery()
            ->getResult();

        if (empty($results)) {
            $io->warning('Aucun contributeur avec des tâches assignées');

            return;
        }

        $rows = [];
        foreach ($results as $row) {
            $rows[] = [
                $row['id'],
                trim($row['firstName'].' '.$row['lastName']),
                $row['taskCount'],
                $row['active'] ? 'Oui' : 'Non',
            ];
        }

        $io->table(['ID', 'Nom', 'Nb tâches', 'Actif'], $rows);

        $io->note('Utilisez: php bin/console app:debug:task-assignment <ID> pour voir le détail');
    }
}
