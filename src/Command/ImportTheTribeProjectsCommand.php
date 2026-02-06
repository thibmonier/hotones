<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Order;
use App\Entity\Project;
use App\Repository\OrderRepository;

use function assert;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-thetribe-projects',
    description: 'Importe les projets et devis theTribe depuis le fichier Excel Staffing',
)]
class ImportTheTribeProjectsCommand extends Command
{
    /**
     * Normalisation des noms de projets (variantes trouvees dans le fichier).
     */
    private const array NAME_ALIASES = [
        'AKIVI'                => 'Akivi',
        'HE RC-Lot-2'          => 'HE RC Lot-2',
        'HE RC-Lot2'           => 'HE RC Lot-2',
        'Homeopath'            => 'Homéopath',
        'Qonti AUDIT'          => 'Qonti Audit',
        'Quonti AUDIT'         => 'Qonti Audit',
        '88jobs'               => '88 Jobs',
        '88 jobs'              => '88 Jobs',
        '88 jobs / Job Public' => '88 Jobs',
        'La Presse libre'      => 'La Presse Libre',
        'UnivNantes'           => 'Univ Nantes',
        'Paper.Club'           => 'Paper.club',
        'Paper Club'           => 'Paper.club',
        'Paper'                => 'Paper.club',
        'AI Mother'            => 'MotherAI',
    ];

    /**
     * Types a ignorer lors du parsing.
     */
    private const array SKIP_TYPES = ['B', 'F', 'N'];

    /**
     * Noms d'entrees speciales a ignorer.
     */
    private const array SKIP_NAMES = [
        'Absence',
        'Destaffing',
        'Congés',
        'Temps partiel',
        'Congé',
    ];

    /**
     * Priorite des types pour determiner le statut Order (plus eleve = prioritaire).
     */
    private const array TYPE_PRIORITY = [
        'V'  => 6,
        'TO' => 5,
        'O'  => 4,
        'NF' => 3,
        'R'  => 2,
        'AV' => 1,
    ];

    /**
     * Mapping type → Order status.
     */
    private const array TYPE_TO_ORDER_STATUS = [
        'V'  => 'signe',
        'TO' => 'signe',
        'O'  => 'gagne',
        'NF' => 'signe',
        'R'  => 'a_signer',
        'AV' => 'a_signer',
    ];

    /**
     * TJM moyen par defaut pour estimer les montants.
     */
    private const string DEFAULT_TJM = '650.00';

    /**
     * Premiere colonne de staffing (AR = index 43 en 0-based).
     */
    private const int STAFFING_START_COLUMN = 43;

    /** @var array<string, Client> */
    private array $clientCache = [];

    /** @var array<string, Project> */
    private array $projectCache = [];

    private int $orderIncrement = 0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin vers le fichier Excel Staffing')
            ->addOption('company-id', null, InputOption::VALUE_REQUIRED, 'ID de la Company')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule l\'import sans persister');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import des projets theTribe');

        $filePath = $input->getArgument('file');
        $dryRun   = $input->getOption('dry-run');

        if (!file_exists($filePath)) {
            $io->error(sprintf('Fichier introuvable: %s', $filePath));

            return Command::FAILURE;
        }

        $company = $this->resolveCompany($input, $io);
        if (!$company) {
            return Command::FAILURE;
        }

        $io->note(sprintf('Company: %s (ID: %d)', $company->getName(), $company->getId()));

        if ($dryRun) {
            $io->warning('Mode dry-run activé - aucune donnée ne sera persistée');
        }

        // 1. Lire et parser les cellules de staffing
        $assignments = $this->parseStaffingCells($filePath, $io);

        // 2. Agreger par projet unique
        $aggregated = $this->aggregateByProject($assignments, $io);

        // 3. Creer les entites
        $stats = $this->createEntities($aggregated, $company, $io);

        if (!$dryRun) {
            $this->entityManager->flush();
            $io->success(sprintf(
                'Import terminé: %d clients, %d projets, %d devis créés',
                $stats['clients'],
                $stats['projects'],
                $stats['orders'],
            ));
        } else {
            $io->success(sprintf(
                'Dry-run terminé: %d clients, %d projets, %d devis seraient créés',
                $stats['clients'],
                $stats['projects'],
                $stats['orders'],
            ));
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array{name: string, days: float, type: string, contributor: string, row: int}>
     */
    private function parseStaffingCells(string $filePath, SymfonyStyle $io): array
    {
        $io->section('Lecture du fichier Excel');

        $reader      = new Xlsx();
        $spreadsheet = $reader->load($filePath);
        $sheet       = $spreadsheet->getSheetByName('Staffing');

        if (!$sheet) {
            throw new RuntimeException('Feuille "Staffing" introuvable dans le fichier Excel');
        }

        $highestColumn  = $sheet->getHighestDataColumn();
        $highestColIdx  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        $sectionHeaders = ['DEVS', 'PM', 'DESIGN', 'UX', 'DATA', 'QA'];

        $assignments = [];
        $warnings    = 0;

        for ($row = 3; $row <= $sheet->getHighestDataRow(); ++$row) {
            $contributorName = trim((string) $sheet->getCell('A'.$row)->getValue());

            if ($contributorName === '' || in_array($contributorName, $sectionHeaders, true)) {
                continue;
            }

            for ($col = self::STAFFING_START_COLUMN; $col <= $highestColIdx; ++$col) {
                $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cellValue  = $sheet->getCell($coordinate.$row)->getValue();
                if ($cellValue === null || trim((string) $cellValue) === '') {
                    continue;
                }

                $parsed = $this->parseCellEntries((string) $cellValue);
                foreach ($parsed as $entry) {
                    if ($entry === null) {
                        ++$warnings;

                        continue;
                    }

                    $assignments[] = [
                        'name'        => $entry['name'],
                        'days'        => $entry['days'],
                        'type'        => $entry['type'],
                        'contributor' => $contributorName,
                        'row'         => $row,
                    ];
                }
            }
        }

        $io->writeln(sprintf('  %d affectations parsées (%d warnings)', count($assignments), $warnings));

        return $assignments;
    }

    /**
     * Parse une cellule multi-lignes en entrees individuelles.
     *
     * @return array<int, array{name: string, days: float, type: string}|null>
     */
    private function parseCellEntries(string $cellValue): array
    {
        $entries = [];

        // Utiliser preg_match_all pour gerer les cas ou plusieurs entrees
        // sont sur la meme ligne (ex: "Bidding Sport 2,5 (V) Paper.club 5 (V)")
        $pattern = '/(.+?)\s+([\d,\.]+)\s*\(([A-Z]+)\)/u';
        if (preg_match_all($pattern, $cellValue, $allMatches, PREG_SET_ORDER)) {
            foreach ($allMatches as $matches) {
                $name = trim($matches[1]);
                $days = (float) str_replace(',', '.', $matches[2]);
                $type = $matches[3];

                // Normaliser le nom
                $name = $this->normalizeName($name);

                // Ignorer les entrees speciales
                if ($this->shouldSkipEntry($name, $type)) {
                    $entries[] = null;

                    continue;
                }

                $entries[] = [
                    'name' => $name,
                    'days' => $days,
                    'type' => $type,
                ];
            }
        }

        return $entries;
    }

    private function normalizeName(string $name): string
    {
        // Lookup exact
        if (isset(self::NAME_ALIASES[$name])) {
            return self::NAME_ALIASES[$name];
        }

        // Lookup case-insensitive
        foreach (self::NAME_ALIASES as $alias => $normalized) {
            if (mb_strtolower($alias) === mb_strtolower($name)) {
                return $normalized;
            }
        }

        return $name;
    }

    private function shouldSkipEntry(string $name, string $type): bool
    {
        if (in_array($type, self::SKIP_TYPES, true)) {
            return true;
        }

        foreach (self::SKIP_NAMES as $skipName) {
            if (mb_strtolower($name) === mb_strtolower($skipName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{name: string, days: float, type: string, contributor: string, row: int}> $assignments
     *
     * @return array<string, array{name: string, types: array<string, bool>, totalDays: float, primaryType: string}>
     */
    private function aggregateByProject(array $assignments, SymfonyStyle $io): array
    {
        $io->section('Agrégation par projet');

        $projects = [];

        foreach ($assignments as $assignment) {
            $key = $assignment['name'];

            if (!isset($projects[$key])) {
                $projects[$key] = [
                    'name'      => $assignment['name'],
                    'types'     => [],
                    'totalDays' => 0.0,
                ];
            }

            $projects[$key]['types'][$assignment['type']] = true;
            $projects[$key]['totalDays'] += $assignment['days'];
        }

        // Determiner le type principal pour chaque projet
        foreach ($projects as $key => $project) {
            $projects[$key]['primaryType'] = $this->determinePrimaryType(array_keys($project['types']));
        }

        // Tri alphabetique
        ksort($projects);

        // Afficher le resume
        $typeCounts = [];
        foreach ($projects as $project) {
            $type              = $project['primaryType'];
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }

        $io->writeln(sprintf('  %d projets uniques trouvés', count($projects)));

        $typeLabels = [
            'V'  => 'vendus (V)',
            'TO' => 'TMA (TO)',
            'O'  => 'devis gagnés (O)',
            'NF' => 'non facturés (NF)',
            'R'  => 'devis probables (R)',
            'AV' => 'avant-vente (AV)',
            'G'  => 'internes (G)',
        ];

        foreach ($typeLabels as $type => $label) {
            if (isset($typeCounts[$type])) {
                $io->writeln(sprintf('    - %d %s', $typeCounts[$type], $label));
            }
        }

        return $projects;
    }

    /**
     * @param array<int, string> $types
     */
    private function determinePrimaryType(array $types): string
    {
        $best     = $types[0];
        $bestPrio = self::TYPE_PRIORITY[$best] ?? 0;

        foreach ($types as $type) {
            $prio = self::TYPE_PRIORITY[$type] ?? 0;
            if ($prio > $bestPrio) {
                $best     = $type;
                $bestPrio = $prio;
            }
        }

        return $best;
    }

    /**
     * @param array<string, array{name: string, types: array<string, bool>, totalDays: float, primaryType: string}> $aggregated
     *
     * @return array{clients: int, projects: int, orders: int}
     */
    private function createEntities(array $aggregated, Company $company, SymfonyStyle $io): array
    {
        $this->loadExistingCaches($company);
        $this->initOrderIncrement($company);

        $stats = ['clients' => 0, 'projects' => 0, 'orders' => 0];

        // Clients
        $io->section('Création des clients');
        foreach ($aggregated as $data) {
            if ($data['primaryType'] === 'G') {
                continue; // Pas de client pour les projets internes
            }

            if ($this->findOrCreateClient($data['name'], $company, $io)) {
                ++$stats['clients'];
            }
        }

        // Projects
        $io->section('Création des projets');
        foreach ($aggregated as $data) {
            if ($this->findOrCreateProject($data, $company, $io)) {
                ++$stats['projects'];
            }
        }

        // Orders
        $io->section('Création des devis');
        foreach ($aggregated as $data) {
            if ($data['primaryType'] === 'G') {
                continue; // Pas de devis pour les projets internes
            }

            if ($this->createOrder($data, $company, $io)) {
                ++$stats['orders'];
            }
        }

        return $stats;
    }

    private function loadExistingCaches(Company $company): void
    {
        // Charger les clients existants
        $clients = $this->entityManager->getRepository(Client::class)->findBy(['company' => $company]);
        foreach ($clients as $client) {
            $this->clientCache[mb_strtolower($client->name)] = $client;
        }

        // Charger les projets existants
        $projects = $this->entityManager->getRepository(Project::class)->findBy(['company' => $company]);
        foreach ($projects as $project) {
            $this->projectCache[mb_strtolower($project->name)] = $project;
        }
    }

    private function initOrderIncrement(Company $company): void
    {
        $orderRepo = $this->entityManager->getRepository(Order::class);
        assert($orderRepo instanceof OrderRepository);

        $year  = date('Y');
        $month = date('m');

        $lastOrder = $orderRepo->findLastOrderNumberForMonth($year, $month);
        if ($lastOrder) {
            // Extraire le numero incremental du dernier devis
            $number               = $lastOrder->orderNumber;
            $suffix               = substr($number, 7); // Apres "D202602"
            $this->orderIncrement = (int) $suffix;
        }
    }

    /**
     * @return bool true si un nouveau client a ete cree
     */
    private function findOrCreateClient(string $name, Company $company, SymfonyStyle $io): bool
    {
        $key = mb_strtolower($name);

        if (isset($this->clientCache[$key])) {
            return false;
        }

        $client          = new Client();
        $client->company = $company;
        $client->name    = $name;

        $this->entityManager->persist($client);
        $this->clientCache[$key] = $client;

        $io->writeln(sprintf('  <info>+</info> Client créé: %s', $name));

        return true;
    }

    /**
     * @param array{name: string, types: array<string, bool>, totalDays: float, primaryType: string} $data
     *
     * @return bool true si un nouveau projet a ete cree
     */
    private function findOrCreateProject(array $data, Company $company, SymfonyStyle $io): bool
    {
        $key = mb_strtolower($data['name']);

        if (isset($this->projectCache[$key])) {
            $io->writeln(sprintf('  Projet existant: %s - ignoré', $data['name']));

            return false;
        }

        $isInternal = $data['primaryType'] === 'G';
        $isRegie    = $data['primaryType'] === 'TO';

        $project              = new Project();
        $project->company     = $company;
        $project->name        = $data['name'];
        $project->status      = 'active';
        $project->isInternal  = $isInternal;
        $project->projectType = $isRegie ? 'regie' : 'forfait';

        if (!$isInternal) {
            $clientKey = mb_strtolower($data['name']);
            if (isset($this->clientCache[$clientKey])) {
                $project->client = $this->clientCache[$clientKey];
            }
        }

        $this->entityManager->persist($project);
        $this->projectCache[$key] = $project;

        $typeLabel = $isInternal ? 'interne' : ($isRegie ? 'régie' : 'forfait');
        $io->writeln(sprintf('  <info>+</info> Projet créé: %s (%s, actif)', $data['name'], $typeLabel));

        return true;
    }

    /**
     * @param array{name: string, types: array<string, bool>, totalDays: float, primaryType: string} $data
     *
     * @return bool true si un nouveau devis a ete cree
     */
    private function createOrder(array $data, Company $company, SymfonyStyle $io): bool
    {
        $projectKey = mb_strtolower($data['name']);
        $project    = $this->projectCache[$projectKey] ?? null;

        if (!$project) {
            $io->warning(sprintf('  Projet introuvable pour le devis: %s', $data['name']));

            return false;
        }

        // Verifier si le projet a deja un devis (deduplication)
        $existingOrders = $this->entityManager->getRepository(Order::class)->findBy([
            'company' => $company,
            'project' => $project,
        ]);
        if (count($existingOrders) > 0) {
            $io->writeln(sprintf('  Devis existant pour %s - ignoré', $data['name']));

            return false;
        }

        $orderNumber = $this->generateNextOrderNumber();
        $status      = self::TYPE_TO_ORDER_STATUS[$data['primaryType']] ?? 'a_signer';
        $isRegie     = $data['primaryType'] === 'TO';
        $totalAmount = bcmul(number_format($data['totalDays'], 2, '.', ''), self::DEFAULT_TJM, 2);

        $order               = new Order();
        $order->company      = $company;
        $order->project      = $project;
        $order->name         = $data['name'];
        $order->orderNumber  = $orderNumber;
        $order->status       = $status;
        $order->contractType = $isRegie ? 'regie' : 'forfait';
        $order->totalAmount  = $totalAmount;

        $this->entityManager->persist($order);

        $statusLabel = Order::STATUS_OPTIONS[$status];
        $io->writeln(sprintf(
            '  <info>+</info> Devis %s créé: %s (%s, %.1fj, %s€)',
            $orderNumber,
            $data['name'],
            $statusLabel,
            $data['totalDays'],
            number_format((float) $totalAmount, 2, ',', ' '),
        ));

        return true;
    }

    private function generateNextOrderNumber(): string
    {
        ++$this->orderIncrement;

        $year  = date('Y');
        $month = date('m');

        return sprintf('D%s%s%03d', $year, $month, $this->orderIncrement);
    }

    private function resolveCompany(InputInterface $input, SymfonyStyle $io): ?Company
    {
        $companyId = $input->getOption('company-id');

        if ($companyId) {
            $company = $this->entityManager->getRepository(Company::class)->find($companyId);
            if (!$company) {
                $io->error(sprintf('Company avec ID %s introuvable', $companyId));

                return null;
            }

            return $company;
        }

        $company = $this->entityManager->getRepository(Company::class)->findOneBy([]);
        if (!$company) {
            $io->error('Aucune Company trouvée. Créez d\'abord une Company.');

            return null;
        }

        return $company;
    }
}
