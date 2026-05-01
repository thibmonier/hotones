<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Client;
use App\Entity\Order;
use App\Entity\Project;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-forecast-test-data',
    description: 'Génère des données de test pour le dashboard de forecasting',
)]
class GenerateForecastTestDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'months',
            'm',
            InputOption::VALUE_REQUIRED,
            'Nombre de mois d\'historique à générer',
            24,
        )->addOption('projects-per-month', 'p', InputOption::VALUE_REQUIRED, 'Nombre de projets par mois (moyenne)', 3);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Génération de données de test pour le forecasting');

        $months = (int) $input->getOption('months');
        $projectsPerMonth = (int) $input->getOption('projects-per-month');

        // Récupérer un client et un utilisateur existants
        $client = $this->em->getRepository(Client::class)->findOneBy([]);
        if (!$client) {
            $io->error('Aucun client trouvé. Veuillez d\'abord créer au moins un client.');

            return Command::FAILURE;
        }

        $user = $this->em->getRepository(User::class)->findOneBy([]);
        if (!$user) {
            $io->error('Aucun utilisateur trouvé. Veuillez d\'abord créer au moins un utilisateur.');

            return Command::FAILURE;
        }

        $io->section(sprintf('Génération de %d mois d\'historique avec ~%d projets/mois', $months, $projectsPerMonth));

        $now = new DateTime();
        $createdCount = 0;
        $orderSequence = time() % 1000; // Partir d'un numéro basé sur le timestamp pour éviter les doublons

        // Coefficients de saisonnalité réalistes (industrie services IT)
        $seasonality = [
            1 => 0.85, // Janvier - calme post-fêtes
            2 => 0.95, // Février
            3 => 1.10, // Mars - reprise
            4 => 1.05, // Avril
            5 => 1.00, // Mai
            6 => 0.90, // Juin - ralentissement été
            7 => 0.75, // Juillet - vacances
            8 => 0.80, // Août - vacances
            9 => 1.15, // Septembre - forte reprise
            10 => 1.20, // Octobre - rush fin d'année
            11 => 1.15, // Novembre - rush fin d'année
            12 => 0.90, // Décembre - ralentissement fêtes
        ];

        for ($i = $months - 1; $i >= 0; --$i) {
            $monthDate = (clone $now)->modify("-{$i} months");
            $monthNumber = (int) $monthDate->format('n');
            $monthName = $monthDate->format('F Y');

            // Appliquer la saisonnalité
            $projectCount = (int) round($projectsPerMonth * $seasonality[$monthNumber]);

            $io->writeln(sprintf('📅 %s : génération de %d projets...', $monthName, $projectCount));

            for ($j = 0; $j < $projectCount; ++$j) {
                $project = new Project();
                $project->setCompany($client->getCompany());

                // Nom du projet
                $projectTypes = [
                    'Refonte site web',
                    'Application mobile',
                    'E-commerce',
                    'CRM custom',
                    'API REST',
                    'Migration cloud',
                    'Dashboard analytics',
                    'Portail client',
                ];
                $projectName = sprintf(
                    '%s %s %d',
                    $projectTypes[array_rand($projectTypes)],
                    $client->getName(),
                    $createdCount + 1,
                );
                $project->setName($projectName);

                // Date de début (dans le mois)
                $dayOfMonth = random_int(1, 28);
                $startDate = (clone $monthDate)->setDate(
                    (int) $monthDate->format('Y'),
                    (int) $monthDate->format('n'),
                    $dayOfMonth,
                );
                $project->setStartDate($startDate);

                // Date de fin (2-6 mois après)
                $duration = random_int(2, 6);
                $endDate = (clone $startDate)->modify("+{$duration} months");
                $project->setEndDate($endDate);

                // Statut (80% completed, 20% in_progress)
                $status = random_int(1, 10) <= 8 ? 'completed' : 'in_progress';
                $project->setStatus($status);

                // Client et managers
                $project->setClient($client);
                $project->setProjectManager($user);
                $project->setIsInternal(false);

                // Type de projet (70% forfait, 30% régie)
                $projectType = random_int(1, 10) <= 7 ? 'forfait' : 'regie';
                $project->setProjectType($projectType);

                // Montant CA (variation réaliste entre 10k et 150k)
                $baseAmount = random_int(10000, 150000);
                // Arrondir aux 5000€
                $soldAmount = round($baseAmount / 5000) * 5000;

                // Ajouter une tendance de croissance (+2% par mois en moyenne)
                $growthFactor = 1 + (($months - $i) * 0.02);
                $soldAmount = (int) ($soldAmount * $growthFactor);

                $this->em->persist($project);

                // Créer un Order signé pour le CA
                $order = new Order();
                $order->setProject($project);
                $order->setName('Devis principal');
                $order->setOrderNumber(sprintf('DEV-%s-%03d', $monthDate->format('Ym'), $orderSequence++));
                $order->setStatus(random_int(1, 10) <= 9 ? 'signe' : 'gagne'); // 90% signés, 10% gagnés
                $order->setContractType($projectType);
                $order->setTotalAmount((string) $soldAmount);
                $order->setCreatedAt(clone $startDate);

                // Date de validité (1 mois après création)
                $validUntil = (clone $startDate)->modify('+1 month');
                $order->setValidUntil($validUntil);

                $this->em->persist($order);
                ++$createdCount;

                if (($createdCount % 10) === 0) {
                    $this->em->flush();
                    $io->write('.');
                }
            }

            $io->writeln(' ✓');
        }

        $this->em->flush();

        $io->success(sprintf('✅ %d projets avec devis générés avec succès sur %d mois', $createdCount, $months));

        // Statistiques
        $io->section('Statistiques des données générées');
        $io->horizontalTable(['Métrique', 'Valeur'], [
            [
                'Période couverte',
                sprintf('%d mois (de %s à aujourd\'hui)', $months, $now->modify("-{$months} months")->format('M Y')),
            ],
            ['Projets créés', $createdCount],
            ['Devis créés', $createdCount],
            ['Projets/mois (moyenne)', round($createdCount / $months, 1)],
            [
                'Statut projets',
                sprintf('%d completed, %d in_progress', (int) ($createdCount * 0.8), (int) ($createdCount * 0.2)),
            ],
            ['Statut devis', sprintf('%d signés, %d gagnés', (int) ($createdCount * 0.9), (int) ($createdCount * 0.1))],
        ]);

        $io->note('Vous pouvez maintenant accéder au dashboard de forecasting : /forecasting/dashboard');

        return Command::SUCCESS;
    }
}
