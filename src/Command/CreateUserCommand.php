<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:user:create', description: 'Create a user')]
class CreateUserCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED)
            ->addArgument('firstName', InputArgument::REQUIRED)
            ->addArgument('lastName', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();
        $user->setEmail($input->getArgument('email'))
            ->setFirstName($input->getArgument('firstName'))
            ->setLastName($input->getArgument('lastName'))
            ->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, $input->getArgument('password')));
        $this->em->persist($user);
        $this->em->flush();

        // Create linked Contributor automatically
        $contributor = new \App\Entity\Contributor();
        $contributor->setFirstName($user->getFirstName())
            ->setLastName($user->getLastName())
            ->setEmail($user->getEmail())
            ->setUser($user)
            ->setActive(true);
        $this->em->persist($contributor);
        $this->em->flush();

        $output->writeln('User created: '.$user->getEmail().' (Contributor #'.$contributor->getId().')');

        return Command::SUCCESS;
    }
}
