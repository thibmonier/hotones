<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Badge;
use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:gamification:seed-badges', description: 'Crée les badges par défaut du système de gamification')]
class GamificationSeedBadgesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
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

        $io->title('Création des badges par défaut');

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

        $badges  = $this->getDefaultBadges();
        $created = 0;

        foreach ($badges as $badgeData) {
            $badge = new Badge();
            $badge->setCompany($company);
            $badge->setName($badgeData['name']);
            $badge->setDescription($badgeData['description']);
            $badge->setIcon($badgeData['icon']);
            $badge->setCategory($badgeData['category']);
            $badge->setXpReward($badgeData['xp_reward']);
            $badge->setCriteria($badgeData['criteria'] ?? null);
            $badge->setActive(true);

            $this->entityManager->persist($badge);
            ++$created;

            $io->text(sprintf('✓ Badge créé: %s (+%d XP)', $badge->getName(), $badge->getXpReward()));
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d badges ont été créés avec succès !', $created));

        return Command::SUCCESS;
    }

    private function getDefaultBadges(): array
    {
        return [
            // Badges de démarrage
            [
                'name'        => 'Premier pas',
                'description' => 'Bienvenue dans le système de gamification ! Ce badge est automatiquement attribué.',
                'icon'        => 'bx-walk',
                'category'    => 'contribution',
                'xp_reward'   => 10,
                'criteria'    => ['total_xp' => 1],
            ],
            [
                'name'        => 'Novice',
                'description' => 'Atteignez le niveau 2',
                'icon'        => 'bx-user',
                'category'    => 'contribution',
                'xp_reward'   => 25,
                'criteria'    => ['level' => 2],
            ],
            [
                'name'        => 'Apprenti',
                'description' => 'Atteignez le niveau 5',
                'icon'        => 'bx-user-check',
                'category'    => 'contribution',
                'xp_reward'   => 50,
                'criteria'    => ['level' => 5],
            ],
            [
                'name'        => 'Expert',
                'description' => 'Atteignez le niveau 10',
                'icon'        => 'bx-user-voice',
                'category'    => 'contribution',
                'xp_reward'   => 100,
                'criteria'    => ['level' => 10],
            ],
            [
                'name'        => 'Maître',
                'description' => 'Atteignez le niveau 20',
                'icon'        => 'bx-crown',
                'category'    => 'contribution',
                'xp_reward'   => 200,
                'criteria'    => ['level' => 20],
            ],

            // Badges de satisfaction
            [
                'name'        => 'Première satisfaction',
                'description' => 'Saisissez votre première satisfaction mensuelle',
                'icon'        => 'bx-happy',
                'category'    => 'engagement',
                'xp_reward'   => 25,
                'criteria'    => ['action_count' => ['satisfaction' => 1]],
            ],
            [
                'name'        => 'Contributeur régulier',
                'description' => 'Saisissez 5 satisfactions mensuelles',
                'icon'        => 'bx-happy-heart-eyes',
                'category'    => 'engagement',
                'xp_reward'   => 50,
                'criteria'    => ['action_count' => ['satisfaction' => 5]],
            ],
            [
                'name'        => 'Contributeur assidu',
                'description' => 'Saisissez 12 satisfactions mensuelles (1 an)',
                'icon'        => 'bx-happy-beaming',
                'category'    => 'engagement',
                'xp_reward'   => 100,
                'criteria'    => ['action_count' => ['satisfaction' => 12]],
            ],
            [
                'name'        => 'Fidèle',
                'description' => 'Saisissez 24 satisfactions mensuelles (2 ans)',
                'icon'        => 'bx-heart',
                'category'    => 'anciennete',
                'xp_reward'   => 200,
                'criteria'    => ['action_count' => ['satisfaction' => 24]],
            ],

            // Badges d'XP
            [
                'name'        => 'Collectionneur',
                'description' => 'Accumulez 500 XP',
                'icon'        => 'bx-coin-stack',
                'category'    => 'performance',
                'xp_reward'   => 50,
                'criteria'    => ['total_xp' => 500],
            ],
            [
                'name'        => 'Chasseur d\'XP',
                'description' => 'Accumulez 1000 XP',
                'icon'        => 'bx-medal',
                'category'    => 'performance',
                'xp_reward'   => 100,
                'criteria'    => ['total_xp' => 1000],
            ],
            [
                'name'        => 'Légende',
                'description' => 'Accumulez 2500 XP',
                'icon'        => 'bx-trophy',
                'category'    => 'performance',
                'xp_reward'   => 250,
                'criteria'    => ['total_xp' => 2500],
            ],
            [
                'name'        => 'Champion',
                'description' => 'Accumulez 5000 XP',
                'icon'        => 'bx-crown',
                'category'    => 'performance',
                'xp_reward'   => 500,
                'criteria'    => ['total_xp' => 5000],
            ],

            // Badges spéciaux
            [
                'name'        => 'Early Adopter',
                'description' => 'Parmi les premiers à utiliser le système de gamification',
                'icon'        => 'bx-rocket',
                'category'    => 'engagement',
                'xp_reward'   => 100,
                'criteria'    => ['total_xp' => 1],
            ],
            [
                'name'        => 'Collaborateur modèle',
                'description' => 'Démontrez un engagement exceptionnel',
                'icon'        => 'bx-star',
                'category'    => 'collaboration',
                'xp_reward'   => 150,
                'criteria'    => ['level' => 15],
            ],
        ];
    }
}
