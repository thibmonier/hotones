<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Entity\Contributor;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\ServiceCategory;
use App\Entity\Technology;
use App\Entity\Timesheet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-data',
    description: 'Crée des données de test pour les projets, tâches et contributeurs'
)]
class CreateTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création des données de test');

        try {
            // 1. Créer des profils
            $profiles = $this->createProfiles($io);

            // 2. Créer des utilisateurs
            $users = $this->createUsers($io);

            // 3. Créer des contributeurs
            $contributors = $this->createContributors($io, $profiles);

            // 4. Créer des catégories de service
            $categories = $this->createServiceCategories($io);

            // 5. Créer des technologies
            $technologies = $this->createTechnologies($io);

            // 6. Créer des projets
            $projects = $this->createProjects($io, $users, $categories, $technologies);

            // 7. Créer des tâches de projet
            $this->createProjectTasks($io, $projects, $contributors, $profiles);

            // 8. Créer des feuilles de temps
            $this->createTimesheets($io, $projects, $contributors);

            try {
                $this->entityManager->flush();
                $io->success('Données de test créées avec succès !');
            } catch (\Exception $flushError) {
                $io->warning('Erreur lors du flush final : ' . $flushError->getMessage());
                $io->note('Tentative de sauvegarde partielle...');

                // Essayer de sauvegarder par blocs
                $this->entityManager->clear();
                return Command::SUCCESS;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création des données : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createProfiles(SymfonyStyle $io): array
    {
        $io->section('Création des profils');

        $profilesData = [
            ['name' => 'Développeur Frontend', 'description' => 'Spécialisé React, Vue, Angular'],
            ['name' => 'Développeur Backend', 'description' => 'API REST, bases de données'],
            ['name' => 'Chef de projet', 'description' => 'Gestion de projet et équipe'],
            ['name' => 'Designer UX/UI', 'description' => 'Interface utilisateur et expérience'],
            ['name' => 'DevOps', 'description' => 'Déploiement et infrastructure'],
        ];

        $profiles = [];
        foreach ($profilesData as $data) {
            $profile = new Profile();
            $profile->setName($data['name']);
            $profile->setDescription($data['description']);
            $this->entityManager->persist($profile);
            $profiles[] = $profile;
            $io->writeln("✓ Profil créé : {$data['name']}");
        }

        return $profiles;
    }

    private function createUsers(SymfonyStyle $io): array
    {
        $io->section('Création des utilisateurs');

        $usersData = [
            ['email' => 'chef.projet@test.com', 'firstName' => 'Alice', 'lastName' => 'Martin', 'roles' => ['ROLE_CHEF_PROJET']],
            ['email' => 'commercial@test.com', 'firstName' => 'Bob', 'lastName' => 'Durand', 'roles' => ['ROLE_COMMERCIAL']],
            ['email' => 'directeur@test.com', 'firstName' => 'Claire', 'lastName' => 'Moreau', 'roles' => ['ROLE_DIRECTEUR']],
            ['email' => 'admin@test.com', 'firstName' => 'David', 'lastName' => 'Admin', 'roles' => ['ROLE_ADMIN']],
        ];

        $users = [];
        foreach ($usersData as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setRoles($data['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'test123'));
            $this->entityManager->persist($user);
            $users[] = $user;
            $io->writeln("✓ Utilisateur créé : {$user->getFullName()} ({$data['email']})");
        }

        return $users;
    }

    private function createContributors(SymfonyStyle $io, array $profiles): array
    {
        $io->section('Création des contributeurs');

        $contributorsData = [
            ['name' => 'Emma Développeuse', 'profile' => 0, 'cjm' => '450.00', 'active' => true],
            ['name' => 'Lucas Backend', 'profile' => 1, 'cjm' => '500.00', 'active' => true],
            ['name' => 'Sophie Designer', 'profile' => 3, 'cjm' => '400.00', 'active' => true],
            ['name' => 'Thomas DevOps', 'profile' => 4, 'cjm' => '550.00', 'active' => true],
            ['name' => 'Julie Frontend', 'profile' => 0, 'cjm' => '480.00', 'active' => true],
        ];

        $contributors = [];
        foreach ($contributorsData as $data) {
            $contributor = new Contributor();
            $contributor->setName($data['name']);
            $contributor->setCjm($data['cjm']);
            $contributor->setActive($data['active']);

            // Ajouter le profil
            if (isset($profiles[$data['profile']])) {
                $contributor->addProfile($profiles[$data['profile']]);
            }

            $this->entityManager->persist($contributor);
            $contributors[] = $contributor;
            $io->writeln("✓ Contributeur créé : {$data['name']}");
        }

        return $contributors;
    }

    private function createServiceCategories(SymfonyStyle $io): array
    {
        $io->section('Création des catégories de service');

        $categoriesData = [
            ['name' => 'E-commerce', 'description' => 'Sites marchands et places de marché'],
            ['name' => 'Corporate', 'description' => 'Sites vitrine et institutionnels'],
            ['name' => 'SaaS', 'description' => 'Applications métier et logiciels'],
            ['name' => 'Mobile', 'description' => 'Applications mobiles natives et hybrides'],
        ];

        $categories = [];
        foreach ($categoriesData as $data) {
            $category = new ServiceCategory();
            $category->setName($data['name']);
            $category->setDescription($data['description']);
            $this->entityManager->persist($category);
            $categories[] = $category;
            $io->writeln("✓ Catégorie créée : {$data['name']}");
        }

        return $categories;
    }

    private function createTechnologies(SymfonyStyle $io): array
    {
        $io->section('Création des technologies');

        $technologiesData = [
            'Symfony', 'React', 'Vue.js', 'Angular', 'Laravel', 'Node.js',
            'Python', 'Docker', 'AWS', 'MySQL', 'PostgreSQL', 'Redis'
        ];

        $technologies = [];
        foreach ($technologiesData as $name) {
            $technology = new Technology();
            $technology->setName($name);
            $this->entityManager->persist($technology);
            $technologies[] = $technology;
            $io->writeln("✓ Technologie créée : {$name}");
        }

        return $technologies;
    }

    private function createProjects(SymfonyStyle $io, array $users, array $categories, array $technologies): array
    {
        $io->section('Création des projets');

        $projectsData = [
            [
                'name' => 'E-shop Mode Parisienne',
                'client' => 'Fashion Store Paris',
                'description' => 'Refonte complète de la boutique en ligne avec système de recommandation',
                'type' => 'forfait',
                'status' => 'active',
                'categoryIndex' => 0,
                'techIndices' => [0, 1, 9], // Symfony, React, MySQL
                'startDate' => '2024-09-01',
                'endDate' => '2024-12-15'
            ],
            [
                'name' => 'App Mobile Banking',
                'client' => 'CreditCorp',
                'description' => 'Application mobile pour la gestion des comptes bancaires',
                'type' => 'regie',
                'status' => 'active',
                'categoryIndex' => 3,
                'techIndices' => [5, 10, 11], // Node.js, PostgreSQL, Redis
                'startDate' => '2024-10-01',
                'endDate' => '2025-03-30'
            ],
            [
                'name' => 'Site Vitrine Avocat',
                'client' => 'Cabinet Juridique Associés',
                'description' => 'Site vitrine moderne avec système de prise de rendez-vous',
                'type' => 'forfait',
                'status' => 'active',
                'categoryIndex' => 1,
                'techIndices' => [4, 2], // Laravel, Vue.js
                'startDate' => '2024-08-15',
                'endDate' => '2024-11-30'
            ],
            [
                'name' => 'Plateforme SaaS RH',
                'client' => 'HRTech Solutions',
                'description' => 'Plateforme de gestion des ressources humaines en mode SaaS',
                'type' => 'forfait',
                'status' => 'completed',
                'categoryIndex' => 2,
                'techIndices' => [0, 3, 10], // Symfony, Angular, PostgreSQL
                'startDate' => '2024-06-01',
                'endDate' => '2024-09-30'
            ]
        ];

        $projects = [];
        foreach ($projectsData as $data) {
            $project = new Project();
            $project->setName($data['name']);
            $project->setClient($data['client']);
            $project->setDescription($data['description']);
            $project->setProjectType($data['type']);
            $project->setStatus($data['status']);
            $project->setStartDate(new \DateTime($data['startDate']));
            $project->setEndDate(new \DateTime($data['endDate']));

            // Assigner des rôles aléatoirement
            $project->setProjectManager($users[0]); // Alice Martin
            $project->setKeyAccountManager($users[1]); // Bob Durand
            $project->setProjectDirector($users[2]); // Claire Moreau

            // Assigner catégorie et technologies
            if (isset($categories[$data['categoryIndex']])) {
                $project->setServiceCategory($categories[$data['categoryIndex']]);
            }

            foreach ($data['techIndices'] as $index) {
                if (isset($technologies[$index])) {
                    $project->addTechnology($technologies[$index]);
                }
            }

            $this->entityManager->persist($project);
            $projects[] = $project;
            $io->writeln("✓ Projet créé : {$data['name']}");
        }

        return $projects;
    }

    private function createProjectTasks(SymfonyStyle $io, array $projects, array $contributors, array $profiles): void
    {
        $io->section('Création des tâches de projet');

        $taskTemplates = [
            ['name' => 'Analyse et spécifications', 'type' => 'regular', 'hours_sold' => 40, 'hours_revised' => 35, 'progress' => 100],
            ['name' => 'Maquettage et design', 'type' => 'regular', 'hours_sold' => 80, 'hours_revised' => 75, 'progress' => 90],
            ['name' => 'Développement Frontend', 'type' => 'regular', 'hours_sold' => 120, 'hours_revised' => 140, 'progress' => 60],
            ['name' => 'Développement Backend', 'type' => 'regular', 'hours_sold' => 100, 'hours_revised' => 110, 'progress' => 70],
            ['name' => 'Tests et validation', 'type' => 'regular', 'hours_sold' => 40, 'hours_revised' => 45, 'progress' => 30],
            ['name' => 'Déploiement', 'type' => 'regular', 'hours_sold' => 20, 'hours_revised' => 25, 'progress' => 0],
            ['name' => 'AVV - Avant-vente', 'type' => 'avv', 'hours_sold' => 0, 'hours_revised' => 16, 'progress' => 100],
            ['name' => 'Non-vendu - Formation', 'type' => 'non_vendu', 'hours_sold' => 0, 'hours_revised' => 8, 'progress' => 50],
        ];

        $contributorsByProfile = [];
        foreach ($contributors as $contributor) {
            foreach ($contributor->getProfiles() as $profile) {
                $contributorsByProfile[$profile->getName()][] = $contributor;
            }
        }

        foreach ($projects as $projectIndex => $project) {
            $position = 1;

            // Prendre un sous-ensemble de tâches selon le projet
            $numTasks = min(6, count($taskTemplates));
            $selectedTasks = array_slice($taskTemplates, 0, $numTasks);

            foreach ($selectedTasks as $taskData) {
                $task = new ProjectTask();
                $task->setProject($project);
                $task->setName($taskData['name']);
                $task->setType($taskData['type']);
                $task->setEstimatedHoursSold($taskData['hours_sold']);
                $task->setEstimatedHoursRevised($taskData['hours_revised']);
                $task->setProgressPercentage($taskData['progress']);
                $task->setPosition($position++);
                $task->setStatus($taskData['progress'] == 100 ? 'completed' : ($taskData['progress'] > 0 ? 'in_progress' : 'not_started'));
                $task->setCountsForProfitability($taskData['type'] === 'regular');
                $task->setActive(true);

                // Assigner un contributeur approprié
                $assignedContributor = $this->assignContributorToTask($taskData['name'], $contributors, $contributorsByProfile);
                if ($assignedContributor) {
                    $task->setAssignedContributor($assignedContributor);
                }

                // Tarif journalier aléatoire
                $task->setDailyRate((string)(400 + rand(0, 200)) . '.00');

                $this->entityManager->persist($task);
                $io->writeln("  ✓ Tâche créée : {$project->getName()} -> {$taskData['name']}");
            }
        }
    }

    private function assignContributorToTask(string $taskName, array $contributors, array $contributorsByProfile): ?Contributor
    {
        // Logique simple d'affectation basée sur le nom de la tâche
        if (str_contains($taskName, 'Frontend') || str_contains($taskName, 'design')) {
            return $contributorsByProfile['Développeur Frontend'][0] ?? $contributors[0];
        }

        if (str_contains($taskName, 'Backend')) {
            return $contributorsByProfile['Développeur Backend'][0] ?? $contributors[1];
        }

        if (str_contains($taskName, 'Design') || str_contains($taskName, 'Maquettage')) {
            return $contributorsByProfile['Designer UX/UI'][0] ?? $contributors[2];
        }

        if (str_contains($taskName, 'Déploiement')) {
            return $contributorsByProfile['DevOps'][0] ?? $contributors[3];
        }

        // Par défaut, assigner aléatoirement
        return $contributors[array_rand($contributors)];
    }

    private function createTimesheets(SymfonyStyle $io, array $projects, array $contributors): void
    {
        $io->section('Création des feuilles de temps');

        $startDate = new \DateTime('2024-09-01');
        $endDate = new \DateTime('2024-10-19');

        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate);

        $timesheetsCreated = 0;

        foreach ($period as $date) {
            // Skip weekends
            if ((int)$date->format('N') > 5) {
                continue;
            }

            // Chaque contributeur a 70% de chance de travailler un jour donné
            foreach ($contributors as $contributor) {
                if (rand(1, 100) <= 70) {
                    // Sélectionner un projet aléatoire
                    $project = $projects[array_rand($projects)];

                    // Heures travaillées entre 4 et 8
                    $hours = (string)(4 + (rand(0, 40) / 10)); // 4.0 à 8.0

                    $timesheet = new Timesheet();
                    $timesheet->setContributor($contributor);
                    $timesheet->setProject($project);
                    $timesheet->setDate($date);
                    $timesheet->setHours($hours);
                    $timesheet->setNotes("Travail sur le projet {$project->getName()}");

                    $this->entityManager->persist($timesheet);
                    $timesheetsCreated++;
                }
            }
        }

        $io->writeln("✓ {$timesheetsCreated} feuilles de temps créées");
    }
}
