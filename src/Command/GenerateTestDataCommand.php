<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Analytics\DimContributor;
use App\Entity\Analytics\DimProjectType;
use App\Entity\Analytics\DimTime;
use App\Entity\Analytics\FactProjectMetrics;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-test-data',
    description: 'Génère des données de test pour les métriques analytics',
)]
class GenerateTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('year', 'y', InputOption::VALUE_OPTIONAL, 'Année pour laquelle générer les données', date('Y'))
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forcer la suppression des données existantes')
            ->setHelp('
Cette commande génère des données de test fictives pour valider le dashboard analytics.

Elle crée :
- Des dimensions temporelles (mois de l\'année)
- Des types de projets variés
- Des contributeurs avec différents rôles
- Des métriques réalistes pour chaque mois

Attention : Cette commande est uniquement pour les tests et le développement.
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $year  = (int) $input->getOption('year');
        $force = $input->getOption('force');

        $io->title('Génération de données de test pour Analytics');

        if ($force) {
            $io->warning('Suppression des données existantes...');
            $this->clearExistingData($year);
        }

        try {
            // 1. Créer les dimensions temporelles
            $io->section('Création des dimensions temporelles');
            $dimTimes = $this->createTimeDimensions($year);
            $io->writeln("✓ {$year} : ".count($dimTimes).' mois créés');

            // 2. Créer les types de projets
            $io->section('Création des types de projets');
            $projectTypes = $this->createProjectTypes();
            $io->writeln('✓ '.count($projectTypes).' types de projets créés');

            // 3. Créer les contributeurs
            $io->section('Création des contributeurs');
            $contributors = $this->createContributors();
            $io->writeln('✓ '.count($contributors).' contributeurs créés');

            // 4. Générer les métriques
            $io->section('Génération des métriques');
            $metrics = $this->generateMetrics($dimTimes, $projectTypes, $contributors);
            $io->writeln('✓ '.count($metrics).' métriques générées');

            // Sauvegarder
            $this->entityManager->flush();

            $io->success('Données de test générées avec succès !');
            $io->writeln('Accédez au dashboard : /analytics/dashboard');

            // Statistiques
            $this->displayStatistics($io, $year);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Erreur lors de la génération des données : '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function clearExistingData(int $year): void
    {
        // Supprimer les métriques existantes
        $this->entityManager->createQuery('
            DELETE FROM App\Entity\Analytics\FactProjectMetrics f 
            WHERE f.dimTime IN (
                SELECT t.id FROM App\Entity\Analytics\DimTime t 
                WHERE t.year = :year
            )
        ')->setParameter('year', $year)->execute();

        // Supprimer les dimensions temporelles
        $this->entityManager->createQuery('
            DELETE FROM App\Entity\Analytics\DimTime t 
            WHERE t.year = :year
        ')->setParameter('year', $year)->execute();
    }

    private function createTimeDimensions(int $year): array
    {
        $dimTimes = [];

        for ($month = 1; $month <= 12; ++$month) {
            $date = new DateTime("$year-$month-01");

            $dimTime = new DimTime();
            $dimTime->setDate($date);

            $this->entityManager->persist($dimTime);
            $dimTimes[] = $dimTime;
        }

        return $dimTimes;
    }

    private function createProjectTypes(): array
    {
        $types = [
            ['forfait', 'E-commerce', 'active', false],
            ['forfait', 'Brand', 'active', false],
            ['regie', 'E-commerce', 'active', false],
            ['regie', 'Brand', 'active', false],
            ['forfait', null, 'completed', false],
            ['forfait', 'E-commerce', 'active', true], // Projet interne
        ];

        $projectTypes = [];

        foreach ($types as [$projectType, $serviceCategory, $status, $isInternal]) {
            $dimProjectType = new DimProjectType();
            $dimProjectType->setProjectType($projectType)
                ->setServiceCategory($serviceCategory)
                ->setStatus($status)
                ->setIsInternal($isInternal);

            $this->entityManager->persist($dimProjectType);
            $projectTypes[] = $dimProjectType;
        }

        return $projectTypes;
    }

    private function createContributors(): array
    {
        $contributors_data = [
            ['Alice Dupont', 'project_manager'],
            ['Bob Martin', 'project_manager'],
            ['Claire Rousseau', 'sales_person'],
            ['David Moreau', 'sales_person'],
            ['Emma Bernard', 'project_director'],
            ['François Petit', 'key_account_manager'],
        ];

        $contributors = [];

        foreach ($contributors_data as [$name, $role]) {
            $contributor = new DimContributor();
            $contributor->setName($name)
                ->setRole($role)
                ->setIsActive(true);

            $this->entityManager->persist($contributor);
            $contributors[] = $contributor;
        }

        return $contributors;
    }

    private function generateMetrics(array $dimTimes, array $projectTypes, array $contributors): array
    {
        $metrics         = [];
        $projectManagers = array_filter($contributors, fn ($c) => $c->getRole() === 'project_manager');
        $salesPersons    = array_filter($contributors, fn ($c) => $c->getRole() === 'sales_person');

        foreach ($dimTimes as $dimTime) {
            foreach ($projectTypes as $projectType) {
                // Sélectionner aléatoirement un chef de projet et un commercial
                $projectManager = $projectManagers[array_rand($projectManagers)];
                $salesPerson    = $salesPersons[array_rand($salesPersons)];

                $metric = new FactProjectMetrics();
                $metric->setDimTime($dimTime)
                    ->setDimProjectType($projectType)
                    ->setDimProjectManager($projectManager)
                    ->setDimSalesPerson($salesPerson)
                    ->setGranularity('monthly');

                // Générer des valeurs réalistes
                $this->generateRealisticValues($metric, $dimTime->getMonth());

                $this->entityManager->persist($metric);
                $metrics[] = $metric;
            }
        }

        return $metrics;
    }

    private function generateRealisticValues(FactProjectMetrics $metric, int $month): void
    {
        // Variations saisonnières (moins d'activité en août et décembre)
        $seasonalFactor = match ($month) {
            8, 12 => 0.6, // Août et décembre
            1, 7 => 0.8,  // Janvier et juillet
            default => 1.0
        };

        $baseRevenue = rand(10000, 50000) * $seasonalFactor;
        $baseCosts   = $baseRevenue       * (0.6 + (rand(0, 20) / 100)); // 60-80% du CA

        $metric->setProjectCount(rand(1, 5))
            ->setActiveProjectCount(rand(1, 3))
            ->setCompletedProjectCount(rand(0, 2))
            ->setOrderCount(rand(1, 8))
            ->setPendingOrderCount(rand(0, 3))
            ->setWonOrderCount(rand(1, 5))
            ->setContributorCount(rand(2, 8))
            ->setTotalRevenue(number_format($baseRevenue, 2, '.', ''))
            ->setTotalCosts(number_format($baseCosts, 2, '.', ''))
            ->setPendingRevenue(number_format(rand(5000, 25000), 2, '.', ''))
            ->setTotalSoldDays(number_format(rand(20, 100), 2, '.', ''))
            ->setTotalWorkedDays(number_format(rand(15, 95), 2, '.', ''));

        // Calcul automatique des marges
        $metric->calculateMargins();

        // Valeur moyenne des devis
        if ($metric->getOrderCount() > 0) {
            $totalOrderValue = bcadd($metric->getTotalRevenue(), $metric->getPendingRevenue(), 2);
            $metric->setAverageOrderValue(
                bcdiv($totalOrderValue, (string) $metric->getOrderCount(), 2),
            );
        }

        // Taux d'occupation
        if (bccomp($metric->getTotalSoldDays(), '0', 2) > 0) {
            $utilizationRate = bcmul(
                bcdiv($metric->getTotalWorkedDays(), $metric->getTotalSoldDays(), 4),
                '100',
                2,
            );
            $metric->setUtilizationRate($utilizationRate);
        }
    }

    private function displayStatistics(SymfonyStyle $io, int $year): void
    {
        $io->section('Statistiques générées');

        // Total CA sur l'année
        $totalRevenue = $this->entityManager->createQuery('
            SELECT SUM(f.totalRevenue) as total 
            FROM App\Entity\Analytics\FactProjectMetrics f
            JOIN f.dimTime t
            WHERE t.year = :year AND f.granularity = :granularity
        ')
            ->setParameter('year', $year)
            ->setParameter('granularity', 'monthly')
            ->getSingleScalarResult();

        $io->writeln('💰 CA total généré : '.number_format($totalRevenue, 0, ',', ' ').'€');

        // Nombre de projets
        $totalProjects = $this->entityManager->createQuery('
            SELECT SUM(f.projectCount) as total 
            FROM App\Entity\Analytics\FactProjectMetrics f
            JOIN f.dimTime t
            WHERE t.year = :year AND f.granularity = :granularity
        ')
            ->setParameter('year', $year)
            ->setParameter('granularity', 'monthly')
            ->getSingleScalarResult();

        $io->writeln("📊 Projets total : $totalProjects");

        $io->writeln('');
        $io->writeln('🎯 Prochaines étapes :');
        $io->writeln('  • Accédez au dashboard : /analytics/dashboard');
        $io->writeln('  • Filtrez par différents critères');
        $io->writeln('  • Testez les graphiques et KPIs');
    }
}
