<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\Planning;
use App\Entity\Project;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-thetribe-planning',
    description: 'Importe les plannings theTribe depuis la grille de staffing du fichier Excel',
)]
class ImportTheTribePlanningCommand extends Command
{
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

    private const array SKIP_TYPES = ['B', 'F', 'G', 'N'];

    private const array SKIP_NAMES = [
        'Absence',
        'Destaffing',
        'Congés',
        'Temps partiel',
        'Congé',
    ];

    private const array SECTION_HEADERS = ['DEVS', 'PM', 'DESIGN', 'UX', 'DATA', 'QA'];

    private const int STAFFING_START_COLUMN = 14;

    /** @var array<string, Project> */
    private array $projectCache = [];

    /** @var array<string, Contributor> */
    private array $contributorCache = [];

    /** @var array<string, true> */
    private array $missingProjects = [];

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
        $io->title('Import des plannings theTribe');

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

        // 1. Charger les caches
        $io->section('Chargement des données existantes');
        $this->loadCaches($company, $io);

        // 2. Lire le fichier Excel
        $reader      = new Xlsx();
        $spreadsheet = $reader->load($filePath);
        $sheet       = $spreadsheet->getSheetByName('Staffing');

        if (!$sheet) {
            $io->error('Feuille "Staffing" introuvable dans le fichier Excel');

            return Command::FAILURE;
        }

        // 3. Parser les dates des colonnes
        $weekDates = $this->parseWeekDates($sheet, $io);
        if ($weekDates === []) {
            $io->error('Aucune date de semaine trouvée en row 2');

            return Command::FAILURE;
        }

        // 4. Purger les plannings existants
        $io->section('Purge des plannings existants');
        $purged = $this->purgeExistingPlannings($company, $dryRun);
        $io->writeln(sprintf('  %d plannings supprimés', $purged));

        // 5. Traiter les contributeurs
        $io->section('Traitement des contributeurs');
        $stats = $this->processContributors($sheet, $weekDates, $company, $io);

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        // 6. Résumé
        $io->section('Résumé');
        $io->writeln(sprintf('  %d plannings créés pour %d contributeurs', $stats['plannings'], $stats['contributors']));
        $io->writeln(sprintf('  %d contributeurs non trouvés en base', $stats['notFound']));
        $io->writeln(sprintf('  %d projets référencés non trouvés en base (ignorés)', count($this->missingProjects)));

        if ($dryRun) {
            $io->success('Dry-run terminé - aucune donnée persistée.');
        } else {
            $io->success(sprintf('Import terminé: %d plannings créés', $stats['plannings']));
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, DateTime> colIdx => Monday date
     */
    private function parseWeekDates(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, SymfonyStyle $io): array
    {
        $highestColIdx = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $weekDates     = [];

        for ($col = self::STAFFING_START_COLUMN; $col <= $highestColIdx; ++$col) {
            $coordinate = Coordinate::stringFromColumnIndex($col);
            $cellValue  = $sheet->getCell($coordinate.'2')->getCalculatedValue();

            if ($cellValue === null) {
                continue;
            }

            if (is_numeric($cellValue) && (float) $cellValue > 40000) {
                $date            = ExcelDate::excelToDateTimeObject((int) $cellValue);
                $weekDates[$col] = DateTime::createFromInterface($date);
            }
        }

        if ($weekDates !== []) {
            $firstDate = reset($weekDates);
            $lastDate  = end($weekDates);
            $io->writeln(sprintf(
                '  %d colonnes semaines (%s → %s)',
                count($weekDates),
                $firstDate->format('Y-m-d'),
                $lastDate->format('Y-m-d'),
            ));
        }

        return $weekDates;
    }

    private function loadCaches(Company $company, SymfonyStyle $io): void
    {
        $projects = $this->entityManager->getRepository(Project::class)->findBy(['company' => $company]);
        foreach ($projects as $project) {
            $this->projectCache[mb_strtolower($project->name)] = $project;
        }

        $contributors = $this->entityManager->getRepository(Contributor::class)->findBy(['company' => $company]);
        foreach ($contributors as $contributor) {
            $key                          = mb_strtolower($contributor->firstName.' '.$contributor->lastName);
            $this->contributorCache[$key] = $contributor;
        }

        $io->writeln(sprintf('  %d projets, %d contributeurs', count($this->projectCache), count($this->contributorCache)));
    }

    private function purgeExistingPlannings(Company $company, bool $dryRun): int
    {
        $plannings = $this->entityManager->getRepository(Planning::class)->findBy(['company' => $company]);
        $count     = count($plannings);

        if (!$dryRun) {
            foreach ($plannings as $planning) {
                $this->entityManager->remove($planning);
            }
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * @param array<int, DateTime> $weekDates
     *
     * @return array{plannings: int, contributors: int, notFound: int}
     */
    private function processContributors(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $weekDates,
        Company $company,
        SymfonyStyle $io,
    ): array {
        $stats = ['plannings' => 0, 'contributors' => 0, 'notFound' => 0];

        for ($row = 3; $row <= $sheet->getHighestDataRow(); ++$row) {
            $rawName = trim((string) $sheet->getCell('A'.$row)->getValue());

            if ($rawName === '' || in_array($rawName, self::SECTION_HEADERS, true)) {
                continue;
            }

            $contributor = $this->resolveContributor($rawName);

            if (!$contributor) {
                $io->writeln(sprintf('  <comment>⚠ %s : contributeur non trouvé en base (skip)</comment>', $rawName));
                ++$stats['notFound'];

                continue;
            }

            // Collecter toutes les affectations de ce contributeur
            $assignments = $this->collectAssignments($sheet, $row, $weekDates);

            if ($assignments === []) {
                continue;
            }

            // Fusionner et créer les plannings
            $planningCount = $this->mergeAndCreatePlannings($assignments, $contributor, $company);

            if ($planningCount > 0) {
                $io->writeln(sprintf('  <info>✓</info> %s : %d plannings créés', $rawName, $planningCount));
                $stats['plannings'] += $planningCount;
                ++$stats['contributors'];
            }
        }

        return $stats;
    }

    /**
     * @param array<int, DateTime> $weekDates
     *
     * @return array<int, array{project: Project, weekStart: DateTime, weekEnd: DateTime, dailyHours: string}>
     */
    private function collectAssignments(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        array $weekDates,
    ): array {
        $assignments = [];

        foreach ($weekDates as $colIdx => $weekStart) {
            $coordinate = Coordinate::stringFromColumnIndex($colIdx);
            $cellValue  = $sheet->getCell($coordinate.$row)->getValue();

            if ($cellValue === null || trim((string) $cellValue) === '') {
                continue;
            }

            $entries = $this->parseCellEntries((string) $cellValue);

            foreach ($entries as $entry) {
                $project = $this->resolveProject($entry['name']);
                if (!$project) {
                    continue;
                }

                $dailyHours = ($entry['days'] / 5) * 8;

                $weekEnd = clone $weekStart;
                $weekEnd->modify('+4 days');

                $assignments[] = [
                    'project'    => $project,
                    'weekStart'  => clone $weekStart,
                    'weekEnd'    => $weekEnd,
                    'dailyHours' => number_format($dailyHours, 2, '.', ''),
                ];
            }
        }

        return $assignments;
    }

    /**
     * @param array<int, array{project: Project, weekStart: DateTime, weekEnd: DateTime, dailyHours: string}> $assignments
     */
    private function mergeAndCreatePlannings(array $assignments, Contributor $contributor, Company $company): int
    {
        // Grouper par projet
        $byProject = [];
        foreach ($assignments as $assignment) {
            $projectId               = $assignment['project']->getId() ?? spl_object_id($assignment['project']);
            $byProject[$projectId][] = $assignment;
        }

        $count = 0;

        foreach ($byProject as $projectAssignments) {
            // Trier par date de début
            usort($projectAssignments, static fn (array $a, array $b): int => $a['weekStart'] <=> $b['weekStart']);

            $current = null;

            foreach ($projectAssignments as $assignment) {
                if ($current === null) {
                    $current = $assignment;

                    continue;
                }

                // Vérifier si la semaine est adjacente et les heures identiques
                $expectedNext = clone $current['weekEnd'];
                $expectedNext->modify('+3 days'); // vendredi + 3 = lundi suivant

                $gap = (int) $assignment['weekStart']->diff($expectedNext)->format('%r%a');

                if (abs($gap) <= 3 && $current['dailyHours'] === $assignment['dailyHours']) {
                    // Fusionner : étendre la fin
                    $current['weekEnd'] = $assignment['weekEnd'];
                } else {
                    // Créer le planning en cours et commencer un nouveau
                    $this->createPlanning($current, $contributor, $company);
                    ++$count;
                    $current = $assignment;
                }
            }

            // Créer le dernier planning en cours
            if ($current !== null) {
                $this->createPlanning($current, $contributor, $company);
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param array{project: Project, weekStart: DateTime, weekEnd: DateTime, dailyHours: string} $data
     */
    private function createPlanning(array $data, Contributor $contributor, Company $company): void
    {
        $planning = new Planning();
        $planning->setCompany($company);
        $planning->setContributor($contributor);
        $planning->setProject($data['project']);
        $planning->startDate  = $data['weekStart'];
        $planning->endDate    = $data['weekEnd'];
        $planning->dailyHours = $data['dailyHours'];
        $planning->status     = 'confirmed';

        $this->entityManager->persist($planning);
    }

    /**
     * @return array<int, array{name: string, days: float, type: string}>
     */
    private function parseCellEntries(string $cellValue): array
    {
        $entries = [];
        $pattern = '/(.+?)\s+([\d,\.]+)\s*\(([A-Z]+)\)/u';

        if (preg_match_all($pattern, $cellValue, $allMatches, PREG_SET_ORDER)) {
            foreach ($allMatches as $matches) {
                $name = trim($matches[1]);
                $days = (float) str_replace(',', '.', $matches[2]);
                $type = $matches[3];

                $name = $this->normalizeName($name);

                if ($this->shouldSkipEntry($name, $type)) {
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
        if (isset(self::NAME_ALIASES[$name])) {
            return self::NAME_ALIASES[$name];
        }

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

    private function resolveContributor(string $rawName): ?Contributor
    {
        $parsed = $this->parseName($rawName);
        $key    = mb_strtolower($parsed['firstName'].' '.$parsed['lastName']);

        return $this->contributorCache[$key] ?? null;
    }

    private function resolveProject(string $name): ?Project
    {
        $key = mb_strtolower($name);

        if (isset($this->projectCache[$key])) {
            return $this->projectCache[$key];
        }

        $this->missingProjects[$name] = true;

        return null;
    }

    /**
     * @return array{firstName: string, lastName: string}
     */
    private function parseName(string $rawName): array
    {
        $name = preg_replace('/[\x{1F000}-\x{1FFFF}]/u', '', $rawName);
        $name = trim((string) $name);

        $parts = preg_split('/\s+/', $name);
        if ($parts === false || count($parts) < 2) {
            return ['firstName' => $name, 'lastName' => ''];
        }

        $firstName = array_shift($parts);
        $lastName  = implode(' ', $parts);

        return [
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ];
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
