<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Contributor;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderSection;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\Technology;
use App\Entity\Timesheet;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-projects-2025',
    description: 'Génère des projets de test pour 2025 avec devis signés, temps passés et prévisionnels',
)]
class SeedProjects2025Command extends Command
{
    private array $orderCounters = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Nombre de projets à générer', '50')
            ->addOption('year', 'y', InputOption::VALUE_OPTIONAL, 'Année de génération', '2025');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $year  = (int) $input->getOption('year');
        $count = (int) $input->getOption('count');

        $io->title("Génération de $count projets de test pour $year");

        try {
            // Pré-requis (profils, contributeurs, technos)
            $profiles     = $this->ensureProfiles($io);
            $contributors = $this->ensureContributors($io, $profiles);
            $technos      = $this->ensureTechnologies($io);

            // Génération des projets
            $projects = $this->createProjects($io, $year, $count, $technos);

            // Pour chaque projet: devis signé + tâches + temps passés
            foreach ($projects as $project) {
                $this->createSignedOrderForProject($project, $profiles);
                $this->createTasksForProject($project, $profiles, $contributors);
                $this->createTimesheetsForProject($project, $contributors, $year);
            }

            $this->em->flush();

            $io->success('Données de test créées avec succès.');
            $io->writeln('→ Rendez-vous sur /projects pour vérifier.');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function ensureProfiles(SymfonyStyle $io): array
    {
        $repo     = $this->em->getRepository(Profile::class);
        $names    = ['Développeur Frontend', 'Développeur Backend', 'Chef de projet', 'Designer UX/UI', 'DevOps'];
        $profiles = [];
        foreach ($names as $name) {
            $p = $repo->findOneBy(['name' => $name]);
            if (!$p) {
                $p = new Profile();
                $p->setName($name)->setDescription('Profil test');
                $this->em->persist($p);
                $io->writeln("✓ Profil créé: $name");
            }
            $profiles[] = $p;
        }

        return $profiles;
    }

    private function ensureContributors(SymfonyStyle $io, array $profiles): array
    {
        $repo     = $this->em->getRepository(Contributor::class);
        $existing = $repo->findBy(['active' => true]);
        if (count($existing) >= 5) {
            return $existing;
        }
        $names = [
            ['Alice', 'Dupont'],
            ['Bob', 'Martin'],
            ['Claire', 'Rousseau'],
            ['David', 'Moreau'],
            ['Emma', 'Bernard'],
            ['François', 'Petit'],
            ['Gaël', 'Leroy'],
        ];
        $contributors = [];
        foreach ($names as $i => [$firstName, $lastName]) {
            $c = $repo->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
            if (!$c) {
                $c = new Contributor();
                $c->setFirstName($firstName)->setLastName($lastName)->setActive(true)->setCjm((string) (400 + ($i % 4) * 50).'.00');
                // Associer 1 profil
                if (isset($profiles[$i % count($profiles)])) {
                    $c->addProfile($profiles[$i % count($profiles)]);
                }
                $this->em->persist($c);
                $io->writeln("✓ Contributeur créé: $firstName $lastName");
            }
            $contributors[] = $c;
        }

        return $contributors;
    }

    private function ensureTechnologies(SymfonyStyle $io): array
    {
        $repo     = $this->em->getRepository(Technology::class);
        $existing = $repo->findBy(['active' => true]);
        if (count($existing) > 0) {
            return $existing;
        }
        $techs   = ['Symfony', 'React', 'Vue.js', 'Angular', 'Node.js', 'Docker', 'AWS', 'MySQL', 'PostgreSQL', 'Redis'];
        $created = [];
        foreach ($techs as $t) {
            $tech = $repo->findOneBy(['name' => $t]);
            if (!$tech) {
                $tech = new Technology();
                $tech->setName($t)->setCategory('tool')->setActive(true);
                $this->em->persist($tech);
                $io->writeln("✓ Technologie créée: $t");
            }
            $created[] = $tech;
        }

        return $created;
    }

    private function createProjects(SymfonyStyle $io, int $year, int $count, array $technos): array
    {
        $projects = [];
        for ($i = 1; $i <= $count; ++$i) {
            $project = new Project();
            $project->setName(sprintf('Projet Test %d #%02d', $year, $i));
            $project->setDescription('Projet de test généré automatiquement.');
            $project->setProjectType(rand(0, 1) ? 'forfait' : 'regie');
            $project->setStatus(rand(0, 10) > 7 ? 'completed' : 'active');

            $start = new DateTime(sprintf('%d-%02d-%02d', $year, rand(1, 11), rand(1, 28)));
            $end   = (clone $start)->modify('+'.rand(30, 180).' days');
            if ((int) $end->format('Y') > $year) {
                $end = new DateTime(sprintf('%d-12-15', $year));
            }
            $project->setStartDate($start);
            $project->setEndDate($end);

            // Achats globaux aléatoires
            if (rand(0, 1)) {
                $project->setPurchasesAmount((string) rand(0, 3000).'.00');
                $project->setPurchasesDescription('Achats tests (licenses, outils)');
            }

            // Associer 1-3 technologies
            $used = array_rand($technos, min(3, max(1, rand(1, 3))));
            if (!is_array($used)) {
                $used = [$used];
            }
            foreach ($used as $idx) {
                $project->addTechnology($technos[$idx]);
            }

            $this->em->persist($project);
            $projects[] = $project;
            $io->writeln('✓ Projet: '.$project->getName());
        }

        return $projects;
    }

    private function createSignedOrderForProject(Project $project, array $profiles): void
    {
        $createdAt = clone $project->getStartDate();
        $order     = new Order();
        $order->setProject($project)
            ->setOrderNumber($this->generateOrderNumberForDate($createdAt))
            ->setStatus(rand(0, 1) ? 'signe' : 'gagne')
            ->setCreatedAt($createdAt)
            ->setValidatedAt((clone $createdAt)->modify('+'.rand(1, 30).' days'));

        // Section prestations
        $section = new OrderSection();
        $section->setOrder($order)
            ->setName('Prestations')
            ->setSortOrder(1);

        // 2-4 lignes de service
        $numLines = rand(2, 4);
        for ($i = 0; $i < $numLines; ++$i) {
            $line = new OrderLine();
            $line->setSection($section)
                ->setDescription('Prestation #'.($i + 1))
                ->setPosition($i + 1)
                ->setType('service')
                ->setProfile($profiles[$i % count($profiles)])
                ->setDailyRate((string) (400 + rand(0, 250)).'.00')
                ->setDays((string) (1 + rand(1, 15)));
            // Attacher un achat ponctuel parfois
            if (rand(0, 3) === 0) {
                $line->setAttachedPurchaseAmount((string) rand(200, 1200).'.00');
            }
            $section->addLine($line);
            $this->em->persist($line);
        }

        // Éventuelle section achats
        if (rand(0, 1)) {
            $sec2 = new OrderSection();
            $sec2->setOrder($order)
                ->setName('Achats')
                ->setSortOrder(2);

            $purchase = new OrderLine();
            $purchase->setSection($sec2)
                ->setDescription('Licence annuelle')
                ->setType('fixed_amount')
                ->setPosition(1)
                ->setDirectAmount((string) rand(300, 2000).'.00');
            $sec2->addLine($purchase);

            $this->em->persist($sec2);
            $this->em->persist($purchase);
            $order->addSection($sec2);
        }

        $this->em->persist($section);
        $order->addSection($section);

        // Enregistrer le total estimé dans totalAmount
        $order->setTotalAmount($order->calculateTotalFromSections());

        $this->em->persist($order);
    }

    private function createTasksForProject(Project $project, array $profiles, array $contributors): void
    {
        $positions = 1;
        $numTasks  = rand(3, 6);
        for ($i = 0; $i < $numTasks; ++$i) {
            $task = new ProjectTask();
            $task->setProject($project)
                ->setName('Tâche #'.($i + 1))
                ->setType(ProjectTask::TYPE_REGULAR)
                ->setCountsForProfitability(true)
                ->setPosition($positions++)
                ->setStatus('in_progress')
                ->setEstimatedHoursSold(rand(16, 80))
                ->setEstimatedHoursRevised(rand(16, 100))
                ->setDailyRate((string) (450 + rand(0, 200)).'.00');
            // assigner un contrib éventuel
            if (!empty($contributors)) {
                $task->setAssignedContributor($contributors[array_rand($contributors)]);
            }
            $this->em->persist($task);
        }
    }

    private function createTimesheetsForProject(Project $project, array $contributors, int $year): void
    {
        if (empty($contributors)) {
            return;
        }
        $start = $project->getStartDate() ?: new DateTime("$year-01-01");
        $end   = $project->getEndDate() ?: new DateTime("$year-12-20");
        if ((int) $start->format('Y') < $year) {
            $start = new DateTime("$year-01-01");
        }
        if ((int) $end->format('Y') > $year) {
            $end = new DateTime("$year-12-20");
        }

        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        foreach ($period as $date) {
            // jours ouvrés uniquement
            if ((int) $date->format('N') > 5) {
                continue;
            }
            // 25% de chances qu'il y ait du temps ce jour pour ce projet
            if (rand(1, 100) > 25) {
                continue;
            }

            $timesheet = new Timesheet();
            $timesheet->setContributor($contributors[array_rand($contributors)])
                ->setProject($project)
                ->setDate($date)
                ->setHours((string) rand(4, 8))
                ->setNotes('Temps saisi automatiquement (test)');
            $this->em->persist($timesheet);
        }
    }

    private function generateOrderNumberForDate(DateTimeInterface $date): string
    {
        $year  = $date->format('Y');
        $month = $date->format('m');
        $key   = $year.$month;

        if (!isset($this->orderCounters[$key])) {
            $last = $this->em->getRepository(Order::class)
                ->findLastOrderNumberForMonth($year, $month);

            $lastIncrement = 0;
            if ($last) {
                $lastNumber    = $last->getOrderNumber();
                $lastIncrement = (int) substr($lastNumber, -3);
            }
            $this->orderCounters[$key] = $lastIncrement;
        }

        ++$this->orderCounters[$key];

        return sprintf('D%s%s%03d', $year, $month, $this->orderCounters[$key]);
    }
}
