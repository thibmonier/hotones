<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:user:create', description: 'Create a user')]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
            ->addArgument('firstName', InputArgument::REQUIRED, 'User first name')
            ->addArgument('lastName', InputArgument::REQUIRED, 'User last name')
            ->addOption(
                'company-id',
                'c',
                InputOption::VALUE_REQUIRED,
                'Company ID (defaults to first company if not specified)',
            )
            ->addOption(
                'role',
                'r',
                InputOption::VALUE_REQUIRED,
                'User role (ROLE_INTERVENANT, ROLE_CHEF_PROJET, ROLE_MANAGER, ROLE_SUPERADMIN)',
                'ROLE_INTERVENANT',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');

        // Check if user already exists
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('User with email "%s" already exists.', $email));

            return Command::FAILURE;
        }

        // Get or find company
        $companyId = $input->getOption('company-id');
        if ($companyId) {
            $company = $this->em->getRepository(Company::class)->find($companyId);
            if (!$company) {
                $io->error(sprintf('Company with ID %d not found.', $companyId));

                return Command::FAILURE;
            }
        } else {
            // Use first available company
            $company = $this->em->getRepository(Company::class)->findOneBy([]);
            if (!$company) {
                $io->error('No company found in database. Please create a company first.');

                return Command::FAILURE;
            }
            $io->note(sprintf('Using company: %s (ID: %d)', $company->name, $company->id));
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->firstName = $input->getArgument('firstName');
        $user->lastName  = $input->getArgument('lastName');
        $user->setCompany($company);

        // Set role
        $role       = $input->getOption('role');
        $validRoles = ['ROLE_INTERVENANT', 'ROLE_CHEF_PROJET', 'ROLE_MANAGER', 'ROLE_SUPERADMIN'];
        if (!in_array($role, $validRoles, true)) {
            $io->error(sprintf('Invalid role "%s". Valid roles: %s', $role, implode(', ', $validRoles)));

            return Command::FAILURE;
        }
        $user->setRoles([$role]);

        $user->setPassword($this->hasher->hashPassword($user, $input->getArgument('password')));

        $this->em->persist($user);
        $this->em->flush();

        // Create linked Contributor automatically
        $contributor = new Contributor();
        $contributor
            ->setFirstName($user->firstName)
            ->setLastName($user->lastName)
            ->setEmail($user->email)
            ->setUser($user)
            ->setCompany($company)
            ->setActive(true);

        $this->em->persist($contributor);
        $this->em->flush();

        $io->success(sprintf(
            'User created: %s (ID: %d, Company: %s, Role: %s, Contributor ID: %d)',
            $user->email,
            $user->id,
            $company->name,
            $role,
            $contributor->id,
        ));

        return Command::SUCCESS;
    }
}
