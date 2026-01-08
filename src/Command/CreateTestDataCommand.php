<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\Planning;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\ServiceCategory;
use App\Entity\Technology;
use App\Entity\Timesheet;
use App\Entity\User;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-data',
    description: 'Crée des contributeurs réels et optionnellement des données de test (projets, devis, tâches)',
    aliases: ['hotones:create-test-data'],
)]
class CreateTestDataCommand extends Command
{
    // Répartition des contributeurs selon les profils
    private const array CONTRIBUTORS_DISTRIBUTION = [
        'développeur fullstack' => 6,
        'développeur frontend'  => 5,
        'développeur backend'   => 4,
        'chef de projet'        => 4,
        'product owner'         => 2,
        'scrumm master'         => 1,
        'consultant'            => 1,
        'Directeur artistique'  => 1,
        'UX designer'           => 1,
        'UI designer'           => 1,
    ];

    // Prénoms et noms français
    private const array FIRST_NAMES = [
        'Alice', 'Bob', 'Claire', 'David', 'Emma', 'François', 'Gabrielle', 'Hugo',
        'Isabelle', 'Julien', 'Karim', 'Léa', 'Marc', 'Nathalie', 'Olivier', 'Patricia',
        'Quentin', 'Rachel', 'Sophie', 'Thomas', 'Valérie', 'William', 'Xavier', 'Yasmine', 'Zoé',
    ];

    private const array LAST_NAMES = [
        'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Petit', 'Durand', 'Leroy',
        'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand',
        'Roux', 'Vincent', 'Fournier', 'Morel', 'Girard', 'André', 'Lefevre', 'Mercier', 'Dupont',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'with-test-data',
                null,
                InputOption::VALUE_NONE,
                'Générer également des données de test (projets, devis, tâches)',
            )
            ->addOption(
                'company-id',
                null,
                InputOption::VALUE_REQUIRED,
                'ID de la Company (utilise la première si non spécifié)',
            )
            ->setHelp('
Cette commande crée les contributeurs selon la répartition définie.
Utilisez --with-test-data pour générer également des projets, devis et tâches fictifs.

Répartition des contributeurs :
- 6 développeurs fullstack
- 5 développeurs frontend
- 4 développeurs backend
- 4 chefs de projet
- 2 product owners
- 1 scrum master
- 1 consultant
- 1 directeur artistique
- 1 UX designer
- 1 UI designer
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io           = new SymfonyStyle($input, $output);
        $withTestData = $input->getOption('with-test-data');

        $io->title('Création des données'.($withTestData ? ' de test' : ''));

        // Récupérer la Company
        $companyId = $input->getOption('company-id');
        if ($companyId) {
            $company = $this->entityManager->getRepository(Company::class)->find($companyId);
            if (!$company) {
                $io->error(sprintf('Company avec ID %d introuvable', $companyId));

                return Command::FAILURE;
            }
        } else {
            $company = $this->entityManager->getRepository(Company::class)->findOneBy([]);
            if (!$company) {
                $io->error('Aucune Company trouvée. Créez d\'abord une Company.');

                return Command::FAILURE;
            }
            $io->note(sprintf('Utilisation de la Company: %s (ID: %d)', $company->getName(), $company->getId()));
        }

        try {
            // 1. Vérifier que les données de référence existent
            $this->checkReferenceData($io);

            // 2. Créer des utilisateurs de test
            $users = $this->createUsers($io);

            // 3. Créer les contributeurs avec la bonne répartition
            $contributors = $this->createContributorsWithDistribution($io, $company);

            if ($withTestData) {
                // 4. Créer des catégories de service
                $categories = $this->createServiceCategories($io);

                // 5. Créer des clients
                $clients = $this->createClients($io, $users, $company);

                // 6. Créer des projets
                $projects = $this->createProjects($io, $clients, $users, $categories, $company);

                // 7. Créer des tâches de projet
                $this->createProjectTasks($io, $projects, $contributors, $company);

                // 9. Créer des feuilles de temps
                $this->createTimesheets($io, $projects, $contributors, $company);

                // 10. Créer du planning prévisionnel
                $this->createPlannings($io, $projects, $contributors, $company);
            }

            $this->entityManager->flush();

            $io->success('Données créées avec succès !');
            if (!$withTestData) {
                $io->note('Utilisez --with-test-data pour générer également des projets, devis et tâches fictifs.');
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Erreur lors de la création des données : '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function checkReferenceData(SymfonyStyle $io): void
    {
        $io->section('Vérification des données de référence');

        $profileCount = $this->entityManager->getRepository(Profile::class)->count([]);
        $techCount    = $this->entityManager->getRepository(Technology::class)->count([]);

        if ($profileCount === 0 || $techCount === 0) {
            $io->warning('Les données de référence (profils/technologies) sont manquantes.');
            $io->note('Exécutez d\'abord : php bin/console app:load-reference-data');
            throw new RuntimeException('Données de référence manquantes');
        }

        $io->writeln("✓ $profileCount profils et $techCount technologies trouvés");
    }

    private function createUsers(SymfonyStyle $io): array
    {
        $io->section('Création des utilisateurs de test');

        $usersData = [
            ['email' => 'chef.projet@test.com', 'firstName' => 'Alice', 'lastName' => 'Martin', 'roles' => ['ROLE_CHEF_PROJET']],
            ['email' => 'commercial@test.com', 'firstName' => 'Bob', 'lastName' => 'Durand', 'roles' => ['ROLE_COMMERCIAL']],
            ['email' => 'manager@test.com', 'firstName' => 'Claire', 'lastName' => 'Moreau', 'roles' => ['ROLE_MANAGER']],
            ['email' => 'admin@test.com', 'firstName' => 'David', 'lastName' => 'Admin', 'roles' => ['ROLE_ADMIN']],
        ];

        $users = [];
        $repo  = $this->entityManager->getRepository(User::class);
        foreach ($usersData as $data) {
            $user = $repo->findOneBy(['email' => $data['email']]);
            if (!$user) {
                $user = new User();
                $user->setEmail($data['email']);
                $io->writeln("✓ Utilisateur créé : {$data['firstName']} {$data['lastName']} ({$data['email']})");
            } else {
                $io->writeln("• Utilisateur existant : {$data['email']}");
            }
            $user->firstName = $data['firstName'];
            $user->setLastName($data['lastName']);
            $user->setRoles($data['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'test123'));
            $this->entityManager->persist($user);
            $users[] = $user;
        }

        return $users;
    }

    private function createContributorsWithDistribution(SymfonyStyle $io, Company $company): array
    {
        $io->section('Création des contributeurs selon la répartition');

        $profileRepo     = $this->entityManager->getRepository(Profile::class);
        $contributorRepo = $this->entityManager->getRepository(Contributor::class);

        $contributors = [];
        $usedNames    = [];

        foreach (self::CONTRIBUTORS_DISTRIBUTION as $profileName => $count) {
            $profile = $profileRepo->findOneBy(['name' => $profileName]);
            if (!$profile) {
                $io->warning("Profil '$profileName' introuvable, passage...");
                continue;
            }

            for ($i = 0; $i < $count; ++$i) {
                // Générer un nom unique
                do {
                    $firstName = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
                    $lastName  = self::LAST_NAMES[array_rand(self::LAST_NAMES)];
                    $fullName  = "$firstName $lastName";
                } while (in_array($fullName, $usedNames, true));

                $usedNames[] = $fullName;

                // Vérifier si le contributeur existe déjà
                $contributor = $contributorRepo->findOneBy([
                    'firstName' => $firstName,
                    'lastName'  => $lastName,
                ]);

                if (!$contributor) {
                    $contributor = new Contributor();
                    $contributor->setCompany($company);
                    $contributor->setFirstName($firstName);
                    $contributor->setLastName($lastName);
                    $io->writeln("✓ Contributeur créé : $firstName $lastName ($profileName)");
                } else {
                    $io->writeln("• Contributeur existant : $firstName $lastName");
                }

                // CJM selon le profil
                $cjm = match (true) {
                    str_contains(strtolower($profileName), 'lead')      => '600.00',
                    str_contains(strtolower($profileName), 'directeur') => '700.00',
                    str_contains(strtolower($profileName), 'senior')    => '550.00',
                    str_contains(strtolower($profileName), 'chef')      => '500.00',
                    str_contains(strtolower($profileName), 'designer')  => '450.00',
                    default                                             => '400.00',
                };

                $contributor->setCjm($cjm);
                $contributor->setTjm((string) (floatval($cjm) * 1.3)); // TJM = CJM * 1.3
                $contributor->setActive(true);

                // Ajouter le profil s'il n'est pas déjà présent
                if (!$contributor->getProfiles()->contains($profile)) {
                    $contributor->addProfile($profile);
                }

                $this->entityManager->persist($contributor);
                $contributors[] = $contributor;
            }
        }

        $io->writeln('');
        $io->writeln('✓ Total : '.count($contributors).' contributeurs créés');

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
        $repo       = $this->entityManager->getRepository(ServiceCategory::class);
        foreach ($categoriesData as $data) {
            $category = $repo->findOneBy(['name' => $data['name']]);
            if (!$category) {
                $category = new ServiceCategory();
                $category->setName($data['name']);
                $io->writeln("✓ Catégorie créée : {$data['name']}");
            } else {
                $io->writeln("• Catégorie existante : {$data['name']}");
            }
            $category->setDescription($data['description']);
            $this->entityManager->persist($category);
            $categories[] = $category;
        }

        return $categories;
    }

    private function createClients(SymfonyStyle $io, array $users, Company $company): array
    {
        $io->section('Création des clients');

        $clientsData = [
            'Fashion Store Paris',
            'CreditCorp',
            'Cabinet Juridique Associés',
            'HRTech Solutions',
            'Restaurant Le Gourmet',
        ];

        $clients = [];
        $repo    = $this->entityManager->getRepository(Client::class);
        foreach ($clientsData as $name) {
            $client = $repo->findOneBy(['name' => $name]);
            if (!$client) {
                $client = new Client();
                $client->setCompany($company);
                $client->setName($name);
                $io->writeln("✓ Client créé : $name");
            } else {
                $io->writeln("• Client existant : $name");
            }
            $this->entityManager->persist($client);
            $clients[] = $client;
        }

        return $clients;
    }

    /**
     * @throws Exception
     */
    private function createProjects(SymfonyStyle $io, array $clients, array $users, array $categories, Company $company): array
    {
        $io->section('Création des projets');

        $techRepo     = $this->entityManager->getRepository(Technology::class);
        $technologies = $techRepo->findAll();

        $projectsData = [
            [
                'name'          => 'E-shop Mode Parisienne',
                'client'        => 0,
                'description'   => 'Refonte complète de la boutique en ligne avec système de recommandation',
                'type'          => 'forfait',
                'status'        => 'active',
                'categoryIndex' => 0,
                'techNames'     => ['Symfony', 'React', 'MariaDB'],
                'startDate'     => '2024-09-01',
                'endDate'       => '2024-12-15',
            ],
            [
                'name'          => 'App Mobile Banking',
                'client'        => 1,
                'description'   => 'Application mobile pour la gestion des comptes bancaires',
                'type'          => 'regie',
                'status'        => 'active',
                'categoryIndex' => 3,
                'techNames'     => ['React', 'MongoDB'],
                'startDate'     => '2024-10-01',
                'endDate'       => '2025-03-30',
            ],
            [
                'name'          => 'Site Vitrine Avocat',
                'client'        => 2,
                'description'   => 'Site vitrine moderne avec système de prise de rendez-vous',
                'type'          => 'forfait',
                'status'        => 'active',
                'categoryIndex' => 1,
                'techNames'     => ['Laravel', 'VueJS'],
                'startDate'     => '2024-08-15',
                'endDate'       => '2024-11-30',
            ],
            [
                'name'          => 'Plateforme SaaS RH',
                'client'        => 3,
                'description'   => 'Plateforme de gestion des ressources humaines en mode SaaS',
                'type'          => 'forfait',
                'status'        => 'completed',
                'categoryIndex' => 2,
                'techNames'     => ['Symfony', 'Angular', 'MariaDB'],
                'startDate'     => '2024-06-01',
                'endDate'       => '2024-09-30',
            ],
        ];

        $projects = [];
        $repo     = $this->entityManager->getRepository(Project::class);
        foreach ($projectsData as $data) {
            $project = $repo->findOneBy(['name' => $data['name']]);
            if (!$project) {
                $project = new Project();
                $project->setCompany($company);
                $project->setName($data['name']);
                $io->writeln("✓ Projet créé : {$data['name']}");
            } else {
                $io->writeln("• Projet existant : {$data['name']}");
            }
            $project->setClient($clients[$data['client']]);
            $project->setDescription($data['description']);
            $project->setProjectType($data['type']);
            $project->setStatus($data['status']);
            $project->setStartDate(new DateTime($data['startDate']));
            $project->setEndDate(new DateTime($data['endDate']));

            // Assigner des rôles
            $project->setProjectManager($users[0]);
            $project->setKeyAccountManager($users[1]);
            $project->setProjectDirector($users[2]);

            // Assigner catégorie
            if (isset($categories[$data['categoryIndex']])) {
                $project->setServiceCategory($categories[$data['categoryIndex']]);
            }

            // Assigner technologies
            foreach ($project->getTechnologies() as $t) {
                $project->removeTechnology($t);
            }
            foreach ($data['techNames'] as $techName) {
                $tech = $techRepo->findOneBy(['name' => $techName]);
                if ($tech) {
                    $project->addTechnology($tech);
                }
            }

            $this->entityManager->persist($project);
            $projects[] = $project;
        }

        return $projects;
    }

    private function createProjectTasks(SymfonyStyle $io, array $projects, array $contributors, Company $company): void
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

        foreach ($projects as $project) {
            $position = 1;

            // Prendre un sous-ensemble de tâches selon le projet
            $numTasks      = min(6, count($taskTemplates));
            $selectedTasks = array_slice($taskTemplates, 0, $numTasks);

            $taskRepo = $this->entityManager->getRepository(ProjectTask::class);
            foreach ($selectedTasks as $taskData) {
                $task = $taskRepo->findOneBy(['project' => $project, 'name' => $taskData['name']]);
                if (!$task) {
                    $task = new ProjectTask();
                    $task->setCompany($project->getCompany());
                    $task->setProject($project);
                    $task->setName($taskData['name']);
                    $io->writeln("  ✓ Tâche créée : {$project->getName()} -> {$taskData['name']}");
                } else {
                    $io->writeln("  • Tâche existante : {$project->getName()} -> {$taskData['name']}");
                }
                $task->setType($taskData['type']);
                $task->setEstimatedHoursSold($taskData['hours_sold']);
                $task->setEstimatedHoursRevised($taskData['hours_revised']);
                $task->setProgressPercentage($taskData['progress']);
                $task->setPosition($position++);
                $task->setStatus($taskData['progress'] === 100 ? 'completed' : ($taskData['progress'] > 0 ? 'in_progress' : 'not_started'));
                $task->setCountsForProfitability($taskData['type'] === 'regular');
                $task->setActive(true);

                // Assigner un contributeur approprié
                $assignedContributor = $this->assignContributorToTask($taskData['name'], $contributors, $contributorsByProfile);
                if ($assignedContributor) {
                    $task->setAssignedContributor($assignedContributor);
                }

                // Tarif journalier aléatoire
                $task->setDailyRate(400 + random_int(0, 200).'.00');

                $this->entityManager->persist($task);
            }
        }
    }

    private function assignContributorToTask(string $taskName, array $contributors, array $contributorsByProfile): ?Contributor
    {
        // Logique d'affectation basée sur le nom de la tâche
        if (str_contains($taskName, 'Frontend')) {
            return $contributorsByProfile['développeur frontend'][0] ?? $contributors[0];
        }

        if (str_contains($taskName, 'Backend')) {
            return $contributorsByProfile['développeur backend'][0] ?? $contributors[1];
        }

        if (str_contains($taskName, 'Design') || str_contains($taskName, 'Maquettage')) {
            return $contributorsByProfile['UI designer'][0] ?? $contributorsByProfile['UX designer'][0] ?? $contributors[2];
        }

        if (str_contains($taskName, 'Analyse') || str_contains($taskName, 'spécification')) {
            return $contributorsByProfile['product owner'][0] ?? $contributorsByProfile['chef de projet'][0] ?? $contributors[0];
        }

        // Par défaut, assigner un développeur fullstack ou aléatoire
        return $contributorsByProfile['développeur fullstack'][0] ?? $contributors[array_rand($contributors)];
    }

    private function createTimesheets(SymfonyStyle $io, array $projects, array $contributors, Company $company): void
    {
        $io->section('Création des feuilles de temps');

        $startDate = new DateTime('2024-09-01');
        $endDate   = new DateTime('2024-10-31');

        $interval = new DateInterval('P1D');
        $period   = new DatePeriod($startDate, $interval, $endDate);

        $timesheetsCreated = 0;

        foreach ($period as $date) {
            // Skip weekends
            if ((int) $date->format('N') > 5) {
                continue;
            }

            // Chaque contributeur a 70% de chance de travailler un jour donné
            foreach ($contributors as $contributor) {
                if (random_int(1, 100) <= 70) {
                    // Sélectionner un projet aléatoire
                    $project = $projects[array_rand($projects)];

                    // Heures travaillées entre 4 et 8
                    $hours = (string) (4 + (random_int(0, 40) / 10)); // 4.0 à 8.0

                    $timesheet = new Timesheet();
                    $timesheet->setCompany($project->getCompany());
                    $timesheet->setContributor($contributor);
                    $timesheet->setProject($project);
                    $timesheet->setDate($date);
                    $timesheet->setHours($hours);
                    $timesheet->setNotes("Travail sur le projet {$project->getName()}");

                    $this->entityManager->persist($timesheet);
                    ++$timesheetsCreated;
                }
            }
        }

        $io->writeln("✓ $timesheetsCreated feuilles de temps créées");
    }

    private function createPlannings(SymfonyStyle $io, array $projects, array $contributors, Company $company): void
    {
        $io->section('Création des plannings prévisionnels');

        $startWindow = new DateTime('monday this week');
        $endWindow   = (clone $startWindow)->modify('+8 weeks');

        $planningCreated = 0;

        foreach ($contributors as $contributor) {
            // Chaque contributeur: 1 à 3 projets planifiés
            $num              = random_int(1, 3);
            $selectedProjects = array_rand($projects, min($num, count($projects)));
            if (!is_array($selectedProjects)) {
                $selectedProjects = [$selectedProjects];
            }

            foreach ($selectedProjects as $idx) {
                $project = $projects[$idx];

                // Générer 1 à 2 blocs de planification de 2-10 jours ouvrés
                $blocks = random_int(1, 2);
                $cursor = (clone $startWindow)->modify('+'.random_int(0, 14).' days');
                for ($b = 0; $b < $blocks; ++$b) {
                    $days  = random_int(3, 10);
                    $start = clone $cursor;
                    $end   = (clone $start)->modify('+'.max(0, $days - 1).' days');

                    $planning = new Planning();
                    $planning->setCompany($project->getCompany());
                    $planning->setContributor($contributor);
                    $planning->setProject($project);
                    $planning->setStartDate($start);
                    $planning->setEndDate($end);
                    $planning->setDailyHours((string) random_int(6, 8));
                    $planning->setStatus(random_int(0, 1) ? 'planned' : 'confirmed');
                    $planning->setNotes('Bloc prévisionnel');

                    $this->entityManager->persist($planning);
                    ++$planningCreated;

                    // avancer le curseur de 1 à 3 jours après ce bloc
                    $cursor = (clone $end)->modify('+'.random_int(1, 3).' days');
                }
            }
        }

        $io->writeln("✓ $planningCreated blocs de planning créés");
    }
}
