<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-test-users', description: 'Create test users for each role in the system')]
class CreateTestUsersCommand extends Command
{
    private const string DEFAULT_PASSWORD = 'password';

    private const array TEST_USERS = [
        [
            'email'     => 'intervenant@test.com',
            'firstName' => 'Jean',
            'lastName'  => 'Intervenant',
            'roles'     => ['ROLE_INTERVENANT'],
        ],
        [
            'email'     => 'chef-projet@test.com',
            'firstName' => 'Marie',
            'lastName'  => 'ChefProjet',
            'roles'     => ['ROLE_CHEF_PROJET'],
        ],
        [
            'email'     => 'manager@test.com',
            'firstName' => 'Pierre',
            'lastName'  => 'Manager',
            'roles'     => ['ROLE_MANAGER'],
        ],
        [
            'email'     => 'compta@test.com',
            'firstName' => 'Sophie',
            'lastName'  => 'Comptable',
            'roles'     => ['ROLE_COMPTA'],
        ],
        [
            'email'     => 'admin@test.com',
            'firstName' => 'Laurent',
            'lastName'  => 'Admin',
            'roles'     => ['ROLE_ADMIN'],
        ],
        [
            'email'     => 'superadmin@test.com',
            'firstName' => 'Alice',
            'lastName'  => 'SuperAdmin',
            'roles'     => ['ROLE_SUPERADMIN'],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'company-id',
            null,
            InputOption::VALUE_REQUIRED,
            'ID de la Company (utilise la première si non spécifié)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Creating test users');

        // Récupérer la Company
        $companyId = $input->getOption('company-id');
        if ($companyId) {
            $company = $this->em->getRepository(Company::class)->find($companyId);
            if (!$company) {
                $io->error(sprintf('Company avec ID %d introuvable', $companyId));

                return Command::FAILURE;
            }
        } else {
            $company = $this->em->getRepository(Company::class)->findOneBy([]);
            if (!$company) {
                $io->error('Aucune Company trouvée. Créez d\'abord une Company.');

                return Command::FAILURE;
            }
            $io->note(sprintf('Utilisation de la Company: %s (ID: %d)', $company->getName(), $company->getId()));
        }

        $io->info('Password for all users: '.self::DEFAULT_PASSWORD);

        $createdUsers = [];

        foreach (self::TEST_USERS as $userData) {
            // Check if user already exists
            $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $userData['email']]);

            if ($existingUser !== null) {
                $io->warning(sprintf(
                    'User %s already exists (ID: %d) - skipping',
                    $userData['email'],
                    $existingUser->getId(),
                ));
                continue;
            }

            // Create User
            $user = new User();
            $user->setEmail($userData['email'])->setCompany($company)->setRoles($userData['roles']);
            $user->firstName = $userData['firstName'];
            $user->lastName  = $userData['lastName'];

            $hashedPassword = $this->hasher->hashPassword($user, self::DEFAULT_PASSWORD);
            $user->setPassword($hashedPassword);

            $this->em->persist($user);
            $this->em->flush();

            // Create linked Contributor
            $contributor = new Contributor();
            $contributor->setCompany($company);
            $contributor
                ->setFirstName($userData['firstName'])
                ->setLastName($userData['lastName'])
                ->setEmail($user->email)
                ->setUser($user)
                ->setActive(true);

            $this->em->persist($contributor);
            $this->em->flush();

            $createdUsers[] = [
                'Email'          => $user->getEmail(),
                'Name'           => $user->getFullName(),
                'Roles'          => implode(', ', $userData['roles']),
                'Contributor ID' => $contributor->getId(),
            ];

            $io->success(sprintf(
                'Created user: %s (%s) - Contributor #%d',
                $user->getEmail(),
                implode(', ', $userData['roles']),
                $contributor->getId(),
            ));
        }

        if (count($createdUsers) > 0) {
            $io->newLine();
            $io->table(['Email', 'Name', 'Roles', 'Contributor ID'], array_map(array_values(...), $createdUsers));

            $io->newLine();
            $io->note([
                'All test users have been created with password: '.self::DEFAULT_PASSWORD,
                'You can now login with any of the emails listed above.',
            ]);
        } else {
            $io->info('No new users were created (all already exist)');
        }

        return Command::SUCCESS;
    }
}
