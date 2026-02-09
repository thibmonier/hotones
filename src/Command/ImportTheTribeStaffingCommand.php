<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\ContributorTechnology;
use App\Entity\EmployeeLevel;
use App\Entity\EmploymentPeriod;
use App\Entity\Profile;
use App\Entity\Technology;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-thetribe-staffing',
    description: 'Importe les collaborateurs theTribe depuis le fichier Excel Staffing',
)]
class ImportTheTribeStaffingCommand extends Command
{
    /**
     * Mapping des salaires annuels par type de CF et niveau.
     * Source: feuille "TJM CF 3 tribus".
     */
    private const array ANNUAL_SALARIES = [
        1  => 15000,
        2  => 34000,
        3  => 36000,
        4  => 38000,
        5  => 40000,
        6  => 42000,
        7  => 45000,
        8  => 47500,
        9  => 50000,
        10 => 55000,
        11 => 60000,
        12 => 65000,
    ];

    /**
     * TJM cible par type/niveau.
     */
    private const array TJM_MAP = [
        'dev' => [
            1  => 300,
            2  => 550,
            3  => 550,
            4  => 550,
            5  => 550,
            6  => 680,
            7  => 680,
            8  => 680,
            9  => 680,
            10 => 800,
            11 => 800,
            12 => 800,
        ],
        'pm' => [
            1  => 300,
            2  => 650,
            3  => 650,
            4  => 750,
            5  => 750,
            6  => 750,
            7  => 750,
            8  => 800,
            9  => 800,
            10 => 800,
            11 => 850,
            12 => 850,
        ],
        'design' => [
            1  => 300,
            2  => 650,
            3  => 650,
            4  => 750,
            5  => 750,
            6  => 750,
            7  => 750,
            8  => 800,
            9  => 800,
            10 => 800,
            11 => 850,
            12 => 850,
        ],
    ];

    /**
     * Mapping des noms de technos du fichier Excel vers les noms normalisés en base.
     */
    private const array TECH_NAME_ALIASES = [
        'vue'               => 'Vue.js',
        'nextjs'            => 'Next.js',
        'nuxt'              => 'Nuxt.js',
        'node'              => 'Node.js',
        'node.js (fastify)' => 'Node.js',
        'nestjs'            => 'NestJS',
        'express'           => 'Express.js',
        'typescript'        => 'TypeScript',
        'rails'             => 'Ruby on Rails',
        'k8s'               => 'Kubernetes',
        'postgresql'        => 'PostgreSQL',
    ];

    /**
     * Technologies à créer si elles n'existent pas, avec leur catégorie.
     */
    private const array NEW_TECHNOLOGIES = [
        'Bubble'         => 'no-code',
        'Framer'         => 'no-code',
        'Weweb'          => 'no-code',
        'Xano'           => 'no-code',
        'Ksaar'          => 'no-code',
        'Flutterflow'    => 'no-code',
        'Supabase'       => 'database',
        'Firebase'       => 'hosting',
        'Codemagic'      => 'tool',
        'Bitrise'        => 'tool',
        'Serverless'     => 'tool',
        'Helm'           => 'tool',
        'AdonisJS'       => 'framework',
        'Ionic'          => 'framework',
        'Android natif'  => 'framework',
        'IA'             => 'tool',
        'Éco-conception' => 'tool',
        'Cybersécurité'  => 'tool',
    ];

    /**
     * Mapping CF type → profile name en base.
     */
    private const array CF_PROFILE_MAP = [
        'dev'    => 'Développeur',
        'pm'     => 'Chef de projet',
        'design' => 'Product Designer',
    ];

    /**
     * Coefficient de charges patronales.
     */
    private const float CHARGES_RATE = 1.45;

    /**
     * Nombre de jours travaillés par an.
     */
    private const int WORKING_DAYS_PER_YEAR = 218;

    /** @var array<string, Technology> */
    private array $technologyCache = [];

    /** @var array<int, EmployeeLevel> */
    private array $levelCache = [];

    /** @var array<string, Profile> */
    private array $profileCache = [];

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
        $io->title('Import des collaborateurs theTribe');

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

        // 1. Créer les niveaux d'employés (1-12)
        $this->createEmployeeLevels($io, $company);

        // 2. Créer les technologies manquantes
        $this->createMissingTechnologies($io, $company);

        // 3. S'assurer que les profiles nécessaires existent
        $this->ensureProfiles($io, $company);

        // 4. Lire et importer les collaborateurs
        $rows = $this->readSpreadsheet($filePath, $io);
        $this->importContributors($rows, $company, $io);

        if (!$dryRun) {
            $this->entityManager->flush();
            $io->success('Import terminé avec succès !');
        } else {
            $io->success('Dry-run terminé - aucune donnée persistée.');
        }

        return Command::SUCCESS;
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

    private function createEmployeeLevels(SymfonyStyle $io, Company $company): void
    {
        $io->section('Création des niveaux d\'employés');

        $repo    = $this->entityManager->getRepository(EmployeeLevel::class);
        $created = 0;

        $levelNames = [
            1  => 'Junior 1',
            2  => 'Junior 2',
            3  => 'Junior 3',
            4  => 'Confirmé 1',
            5  => 'Confirmé 2',
            6  => 'Confirmé 3',
            7  => 'Senior 1',
            8  => 'Senior 2',
            9  => 'Senior 3',
            10 => 'Lead 1',
            11 => 'Lead 2',
            12 => 'Lead 3',
        ];

        for ($level = 1; $level <= 12; ++$level) {
            $existing = $repo->findOneBy(['company' => $company, 'level' => $level]);
            if ($existing) {
                $this->levelCache[$level] = $existing;
                $io->writeln(sprintf('  Niveau %d existant: %s', $level, $existing->name));

                continue;
            }

            $annualSalary  = self::ANNUAL_SALARIES[$level];
            $monthlySalary = $annualSalary / 12;

            $employeeLevel = new EmployeeLevel();
            $employeeLevel->setCompany($company);
            $employeeLevel->setLevel($level);
            $employeeLevel->setName($levelNames[$level]);
            $employeeLevel->setSalaryTarget((string) $annualSalary);
            $employeeLevel->setTargetTjm((string) self::TJM_MAP['dev'][$level]);

            $this->entityManager->persist($employeeLevel);
            $this->levelCache[$level] = $employeeLevel;
            ++$created;
            $io->writeln(sprintf(
                '  <info>+</info> Niveau %d créé: %s (salaire cible: %d€/an)',
                $level,
                $levelNames[$level],
                $annualSalary,
            ));
        }

        $io->writeln(sprintf('  <comment>%d niveaux créés</comment>', $created));
    }

    private function createMissingTechnologies(SymfonyStyle $io, Company $company): void
    {
        $io->section('Création des technologies manquantes');

        $repo = $this->entityManager->getRepository(Technology::class);

        // Charger toutes les technos existantes en cache
        $allTechs = $repo->findBy(['company' => $company]);
        foreach ($allTechs as $tech) {
            $this->technologyCache[mb_strtolower($tech->name)] = $tech;
        }

        $created = 0;
        foreach (self::NEW_TECHNOLOGIES as $name => $category) {
            $key = mb_strtolower($name);
            if (isset($this->technologyCache[$key])) {
                $io->writeln(sprintf('  Technologie existante: %s', $name));

                continue;
            }

            $tech = new Technology();
            $tech->setCompany($company);
            $tech->setName($name);
            $tech->setCategory($category);
            $tech->setActive(true);

            $this->entityManager->persist($tech);
            $this->technologyCache[$key] = $tech;
            ++$created;
            $io->writeln(sprintf('  <info>+</info> Technologie créée: %s (%s)', $name, $category));
        }

        $io->writeln(sprintf('  <comment>%d technologies créées</comment>', $created));
    }

    private function ensureProfiles(SymfonyStyle $io, Company $company): void
    {
        $io->section('Vérification des profils');

        $repo = $this->entityManager->getRepository(Profile::class);

        // Charger les profils existants
        $allProfiles = $repo->findBy(['company' => $company]);
        foreach ($allProfiles as $profile) {
            $this->profileCache[mb_strtolower($profile->getName())] = $profile;
        }

        foreach (self::CF_PROFILE_MAP as $type => $profileName) {
            $key = mb_strtolower($profileName);
            if (isset($this->profileCache[$key])) {
                $io->writeln(sprintf('  Profil existant: %s', $profileName));

                continue;
            }

            $profile = new Profile();
            $profile->setName($profileName);
            $profile->setCompany($company);

            $this->entityManager->persist($profile);
            $this->profileCache[$key] = $profile;
            $io->writeln(sprintf('  <info>+</info> Profil créé: %s', $profileName));
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readSpreadsheet(string $filePath, SymfonyStyle $io): array
    {
        $io->section('Lecture du fichier Excel');

        $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($filePath);
        $sheet       = $spreadsheet->getSheetByName('Staffing');

        if (!$sheet) {
            throw new RuntimeException('Feuille "Staffing" introuvable dans le fichier Excel');
        }

        $rows           = [];
        $sectionHeaders = ['DEVS', 'PM', 'DESIGN', 'UX', 'DATA', 'QA'];

        for ($row = 3; $row <= $sheet->getHighestRow(); ++$row) {
            $name = trim((string) $sheet->getCell('A'.$row)->getValue());
            $cf   = trim((string) $sheet->getCell('C'.$row)->getValue());

            if ($name === '' || $cf === '') {
                continue;
            }

            if (in_array($name, $sectionHeaders, true)) {
                continue;
            }

            $tjmRaw = $sheet->getCell('D'.$row)->getCalculatedValue();
            $jours  = $sheet->getCell('F'.$row)->getValue();

            // Skip les TJM invalides (string comme "PM 11" pour Timothée)
            $tjm = is_numeric($tjmRaw) ? (int) $tjmRaw : null;

            $rows[] = [
                'row'           => $row,
                'name'          => $name,
                'tribu'         => trim((string) $sheet->getCell('B'.$row)->getValue()),
                'cf'            => $cf,
                'tjm'           => $tjm,
                'jours'         => is_numeric($jours) ? (float) $jours : null,
                'tech_front'    => $this->parseTechList((string) $sheet->getCell('G'.$row)->getValue()),
                'tech_back'     => $this->parseTechList((string) $sheet->getCell('H'.$row)->getValue()),
                'tech_mobile'   => $this->parseTechList((string) $sheet->getCell('I'.$row)->getValue()),
                'tech_nc_front' => $this->parseTechList((string) $sheet->getCell('J'.$row)->getValue()),
                'tech_nc_back'  => $this->parseTechList((string) $sheet->getCell('K'.$row)->getValue()),
                'tech_devops'   => $this->parseTechList((string) $sheet->getCell('L'.$row)->getValue()),
            ];
        }

        $io->writeln(sprintf('  %d lignes lues', count($rows)));

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function importContributors(array $rows, Company $company, SymfonyStyle $io): void
    {
        $io->section('Import des collaborateurs');

        $created = 0;
        $skipped = 0;

        foreach ($rows as $data) {
            $parsedName = $this->parseName($data['name']);
            $parsedCf   = $this->parseCf($data['cf']);

            if (!$parsedCf) {
                $io->warning(sprintf(
                    '  Ligne %d: CF invalide "%s" pour %s - ignoré',
                    $data['row'],
                    $data['cf'],
                    $data['name'],
                ));
                ++$skipped;

                continue;
            }

            // Vérifier si le contributeur existe déjà
            $existing = $this->entityManager
                ->getRepository(Contributor::class)
                ->findOneBy([
                    'company'   => $company,
                    'firstName' => $parsedName['firstName'],
                    'lastName'  => $parsedName['lastName'],
                ]);

            if ($existing) {
                $io->writeln(sprintf(
                    '  Contributeur existant: %s %s - ignoré',
                    $parsedName['firstName'],
                    $parsedName['lastName'],
                ));
                ++$skipped;

                continue;
            }

            // Créer le contributeur
            $contributor = new Contributor();
            $contributor->setCompany($company);
            $contributor->setFirstName($parsedName['firstName']);
            $contributor->setLastName($parsedName['lastName']);
            $contributor->setActive(true);

            // Ajouter le profil
            $profile = $this->resolveProfile($parsedCf['type']);
            if ($profile) {
                $contributor->addProfile($profile);
            }

            // Notes avec info tribu
            if ($data['tribu'] !== '') {
                $contributor->setNotes(sprintf('Tribu: %s', $data['tribu']));
            }

            $this->entityManager->persist($contributor);

            // Créer la période d'emploi
            $this->createEmploymentPeriod($contributor, $company, $parsedCf, $data, $profile);

            // Associer les technologies
            $allTechs = array_merge(
                $data['tech_front'],
                $data['tech_back'],
                $data['tech_mobile'],
                $data['tech_nc_front'],
                $data['tech_nc_back'],
                $data['tech_devops'],
            );

            $this->associateTechnologies($contributor, $company, array_unique($allTechs), $io);

            ++$created;
            $jours = $data['jours'] !== null ? $data['jours'].'j/sem' : 'N/A';
            $io->writeln(sprintf(
                '  <info>+</info> %s %s - %s (niveau %d, TJM: %s€, %s)',
                $parsedName['firstName'],
                $parsedName['lastName'],
                $parsedCf['type'],
                $parsedCf['level'],
                $data['tjm'] ?? 'N/A',
                $jours,
            ));
        }

        $io->newLine();
        $io->writeln(sprintf('  <comment>%d contributeurs créés, %d ignorés</comment>', $created, $skipped));
    }

    /**
     * @param array{type: string, level: int} $parsedCf
     * @param array<string, mixed>            $data
     */
    private function createEmploymentPeriod(
        Contributor $contributor,
        Company $company,
        array $parsedCf,
        array $data,
        ?Profile $profile,
    ): void {
        $level = $parsedCf['level'];
        $type  = $parsedCf['type'];

        // Calculer TJM
        $tjm = $data['tjm'] ?? self::TJM_MAP[$type][$level] ?? null;

        // Salaire mensuel = annuel / 12
        $annualSalary  = self::ANNUAL_SALARIES[$level] ?? null;
        $monthlySalary = $annualSalary !== null ? round($annualSalary / 12, 2) : null;

        // CJM = (salaire annuel * charges) / jours travaillés par an
        $cjm = $annualSalary !== null
            ? round(($annualSalary * self::CHARGES_RATE) / self::WORKING_DAYS_PER_YEAR, 2)
            : null;

        // Temps de travail
        $jours              = $data['jours'] ?? 5.0;
        $workTimePercentage = round(($jours / 5) * 100, 2);

        $period = new EmploymentPeriod();
        $period->setCompany($company);
        $period->setContributor($contributor);
        $period->setStartDate(new DateTime('2025-01-01'));
        $period->setEndDate(null);
        $period->setWeeklyHours('35.00');
        $period->setWorkTimePercentage(number_format($workTimePercentage, 2, '.', ''));

        if ($tjm !== null) {
            $period->setTjm(number_format((float) $tjm, 2, '.', ''));
        }

        if ($monthlySalary !== null) {
            $period->setSalary(number_format($monthlySalary, 2, '.', ''));
        }

        if ($cjm !== null) {
            $period->setCjm(number_format($cjm, 2, '.', ''));
        }

        // Associer le niveau
        if (isset($this->levelCache[$level])) {
            $period->setEmployeeLevel($this->levelCache[$level]);
        }

        // Associer le profil à la période
        if ($profile) {
            $period->addProfile($profile);
        }

        $contributor->addEmploymentPeriod($period);
        $this->entityManager->persist($period);
    }

    /**
     * @param array<string> $techNames
     */
    private function associateTechnologies(
        Contributor $contributor,
        Company $company,
        array $techNames,
        SymfonyStyle $io,
    ): void {
        foreach ($techNames as $techName) {
            if ($techName === '') {
                continue;
            }

            $technology = $this->resolveTechnology($techName, $company, $io);

            $ct = new ContributorTechnology();
            $ct->setCompany($company);
            $ct->setContributor($contributor);
            $ct->setTechnology($technology);
            $ct->setSelfAssessmentLevel(ContributorTechnology::LEVEL_CONFIRMED);
            $ct->setLastUsedDate(new DateTime());
            $ct->setWantsToUse(true);

            $contributor->addContributorTechnology($ct);
            $this->entityManager->persist($ct);
        }
    }

    private function resolveTechnology(string $rawName, Company $company, SymfonyStyle $io): Technology
    {
        $normalized = trim($rawName);
        $key        = mb_strtolower($normalized);

        // Appliquer les alias
        if (isset(self::TECH_NAME_ALIASES[$key])) {
            $key = mb_strtolower(self::TECH_NAME_ALIASES[$key]);
        }

        if (isset($this->technologyCache[$key])) {
            return $this->technologyCache[$key];
        }

        // Essayer quelques variantes
        $variants = [
            $key,
            $key.'.js',
            str_replace('.js', '', $key),
            str_replace('js', '.js', $key),
        ];

        foreach ($variants as $variant) {
            if (isset($this->technologyCache[$variant])) {
                $this->technologyCache[$key] = $this->technologyCache[$variant];

                return $this->technologyCache[$variant];
            }
        }

        // Créer la technologie si elle n'existe pas
        $io->writeln(sprintf('    <comment>Technologie créée à la volée: %s</comment>', $normalized));

        $tech = new Technology();
        $tech->setCompany($company);
        $tech->setName($normalized);
        $tech->setCategory('tool');
        $tech->setActive(true);

        $this->entityManager->persist($tech);
        $this->technologyCache[$key] = $tech;

        return $tech;
    }

    /**
     * @return array{firstName: string, lastName: string}
     */
    private function parseName(string $rawName): array
    {
        // Supprimer les emojis (comme 👑)
        $name = preg_replace('/[\x{1F000}-\x{1FFFF}]/u', '', $rawName);
        $name = trim((string) $name);

        $parts = preg_split('/\s+/', $name);
        if ($parts === false || count($parts) < 2) {
            return ['firstName' => $name, 'lastName' => ''];
        }

        $firstName = array_shift($parts);
        $lastName  = implode(' ', $parts);

        // Retirer le point final si c'est une initiale (ex: "C." → "C.")
        return [
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ];
    }

    /**
     * Parse le CF (Career Framework) : "Dév 7" → ['type' => 'dev', 'level' => 7].
     *
     * @return array{type: string, level: int}|null
     */
    private function parseCf(string $cf): ?array
    {
        $cf = trim($cf);

        // "Dév 7", "Dév 12", "Dev 1"
        if (preg_match('/^D[eé]v\s*(\d{1,2})$/iu', $cf, $matches)) {
            return ['type' => 'dev', 'level' => (int) $matches[1]];
        }

        // "PM 7", "PM5"
        if (preg_match('/^PM\s*(\d{1,2})$/iu', $cf, $matches)) {
            return ['type' => 'pm', 'level' => (int) $matches[1]];
        }

        // "PrD 5", "PrD5"
        if (preg_match('/^PrD\s*(\d{1,2})$/iu', $cf, $matches)) {
            return ['type' => 'design', 'level' => (int) $matches[1]];
        }

        return null;
    }

    private function resolveProfile(string $cfType): ?Profile
    {
        $profileName = self::CF_PROFILE_MAP[$cfType] ?? null;
        if ($profileName === null) {
            return null;
        }

        return $this->profileCache[mb_strtolower($profileName)] ?? null;
    }

    /**
     * @return array<string>
     */
    private function parseTechList(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_map('trim', explode(',', $raw));
    }
}
