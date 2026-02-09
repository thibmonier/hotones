<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\ProjectTechnology;
use App\Entity\Technology;
use App\Entity\User;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Transliterator;

#[AsCommand(
    name: 'app:import-thetribe-notion',
    description: 'Enrichit les projets existants depuis le CSV Notion (statut, dates, rôles, technologies)',
)]
class ImportTheTribeNotionCommand extends Command
{
    /**
     * Mapping des noms de projets CSV Notion → noms en base (créés par ImportTheTribeProjectsCommand).
     */
    private const array PROJECT_ALIASES = [
        '88jobs-jobpublic'         => '88 Jobs',
        '88 Jobs / Job Public'     => '88 Jobs',
        'Paperclub'                => 'Paper.club',
        'La Presse Libre / LPL'    => 'La Presse Libre',
        'Homeopath'                => 'Homéopath',
        'Cogite - AIMother'        => 'MotherAI',
        'BiddingSport'             => 'Bidding Sport',
        'Bidding sport'            => 'Bidding Sport',
        'Université de Nantes'     => 'Univ Nantes',
        'HomeExchange RC'          => 'HE RC Lot-2',
        'Home Exchange Days'       => 'HE Days',
        'Senek / eskimoz'          => 'Senek',
        'Mutuelle Just / MJ'       => 'Mutuelle Just',
        'Université Lorraine / UL' => 'Univ Lorraine',
        'AKIVI'                    => 'Akivi',
        'HE RC-Lot-2'              => 'HE RC Lot-2',
        'HE RC-Lot2'               => 'HE RC Lot-2',
        'Qonti AUDIT'              => 'Qonti Audit',
        'Quonti AUDIT'             => 'Qonti Audit',
        'La Presse libre'          => 'La Presse Libre',
        'UnivNantes'               => 'Univ Nantes',
        'Paper.Club'               => 'Paper.club',
        'Paper Club'               => 'Paper.club',
        'Paper'                    => 'Paper.club',
        'AI Mother'                => 'MotherAI',
    ];

    /**
     * Mapping des statuts CSV → statuts en base.
     */
    private const array STATUS_MAP = [
        'En cours' => 'active',
        'Terminé'  => 'completed',
        'Archivé'  => 'cancelled',
        'En pause' => 'active',
    ];

    /**
     * Mapping des noms de technologies CSV → noms normalisés en base.
     */
    private const array TECH_ALIASES = [
        'Node'          => 'Node.js',
        'Express'       => 'Express.js',
        'Nextjs'        => 'Next.js',
        'Next.js'       => 'Next.js',
        'Vue'           => 'Vue.js',
        'Strapi'        => 'Strapi',
        'Symfony'       => 'Symfony',
        'Laravel'       => 'Laravel',
        'Django'        => 'Django',
        'Flask'         => 'Flask',
        'React'         => 'React',
        'Svelte'        => 'Svelte',
        'Ionic'         => 'Ionic',
        'Flutter'       => 'Flutter',
        'React Native'  => 'React Native',
        'Bubble'        => 'Bubble',
        'Weweb'         => 'WeWeb',
        'WeWeb'         => 'WeWeb',
        'Bolt'          => 'Bolt',
        'Supabase'      => 'Supabase',
        'NestJS'        => 'NestJS',
        'Nuxt'          => 'Nuxt.js',
        'Nuxt.js'       => 'Nuxt.js',
        'Ruby on Rails' => 'Ruby on Rails',
        'Rails'         => 'Ruby on Rails',
        'TypeScript'    => 'TypeScript',
        'Xano'          => 'Xano',
        'Flutterflow'   => 'Flutterflow',
        'Ksaar'         => 'Ksaar',
        'Firebase'      => 'Firebase',
        'AdonisJS'      => 'AdonisJS',
    ];

    /** @var array<string, Project> Clé = nom normalisé en lowercase */
    private array $projectCache = [];

    /** @var array<string, User> Clé = "prénom nom" en lowercase */
    private array $userCache = [];

    /** @var array<string, Contributor> Clé = "prénom nom" en lowercase */
    private array $contributorCache = [];

    /** @var array<string, Technology> Clé = nom en lowercase */
    private array $technologyCache = [];

    private int $usersCreated = 0;

    private int $usersReused = 0;

    private int $techAssociationsCreated = 0;

    private int $projectsEnriched = 0;

    private int $projectsSkipped = 0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin vers le fichier CSV Notion')
            ->addOption('company-id', null, InputOption::VALUE_REQUIRED, 'ID de la Company')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule l\'import sans persister');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Notion → enrichissement projets');

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

        // 1. Charger les données existantes
        $this->loadCaches($company, $io);

        // 2. Parser et traiter le CSV
        $rows = $this->parseCsv($filePath, $io);
        if ($rows === null) {
            return Command::FAILURE;
        }

        $io->section(sprintf('Traitement des %d lignes CSV', count($rows)));

        foreach ($rows as $row) {
            $this->processRow($row, $company, $io);
        }

        // 3. Afficher les users créés
        if ($this->usersCreated > 0) {
            $io->newLine();
            $io->section('Utilisateurs créés');
        }

        // 4. Résumé
        $io->newLine();
        $io->section('Résumé');
        $io->writeln(sprintf('  %d projets enrichis sur %d lignes CSV', $this->projectsEnriched, count($rows)));
        $io->writeln(sprintf(
            '  %d users créés, %d users existants réutilisés',
            $this->usersCreated,
            $this->usersReused,
        ));
        $io->writeln(sprintf('  %d associations ProjectTechnology créées', $this->techAssociationsCreated));
        $io->writeln(sprintf('  %d lignes CSV sans correspondance en base', $this->projectsSkipped));

        if (!$dryRun) {
            $this->entityManager->flush();
            $io->success('Import terminé avec succès !');
        } else {
            $io->success('Dry-run terminé - aucune donnée ne sera persistée.');
        }

        return Command::SUCCESS;
    }

    private function loadCaches(Company $company, SymfonyStyle $io): void
    {
        $io->section('Chargement des données existantes');

        // Projects
        $projects = $this->entityManager->getRepository(Project::class)->findBy(['company' => $company]);
        foreach ($projects as $project) {
            $this->projectCache[mb_strtolower($project->name)] = $project;
        }

        // Users
        $users = $this->entityManager->getRepository(User::class)->findBy(['company' => $company]);
        foreach ($users as $user) {
            $key                   = mb_strtolower($user->firstName.' '.$user->lastName);
            $this->userCache[$key] = $user;
        }

        // Contributors
        $contributors = $this->entityManager->getRepository(Contributor::class)->findBy(['company' => $company]);
        foreach ($contributors as $contributor) {
            $key                          = mb_strtolower($contributor->firstName.' '.$contributor->lastName);
            $this->contributorCache[$key] = $contributor;
        }

        // Technologies
        $technologies = $this->entityManager->getRepository(Technology::class)->findBy(['company' => $company]);
        foreach ($technologies as $technology) {
            $this->technologyCache[mb_strtolower($technology->name)] = $technology;
        }

        $io->writeln(sprintf(
            '  %d projets, %d users, %d contributeurs, %d technologies',
            count($this->projectCache),
            count($this->userCache),
            count($this->contributorCache),
            count($this->technologyCache),
        ));
    }

    /**
     * @return array<int, array<string, string>>|null
     */
    private function parseCsv(string $filePath, SymfonyStyle $io): ?array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $io->error('Impossible d\'ouvrir le fichier CSV');

            return null;
        }

        // Lire le header pour construire l'index des colonnes
        $header = fgetcsv($handle);
        if ($header === false) {
            $io->error('Fichier CSV vide ou header invalide');
            fclose($handle);

            return null;
        }

        $columnIndex = [];
        foreach ($header as $i => $col) {
            // Supprimer le BOM UTF-8 éventuel sur la première colonne
            $col                     = preg_replace('/^\x{FEFF}/u', '', trim($col));
            $columnIndex[trim($col)] = $i;
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($columnIndex as $colName => $idx) {
                $row[$colName] = trim($data[$idx] ?? '');
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param array<string, string> $row
     */
    private function processRow(array $row, Company $company, SymfonyStyle $io): void
    {
        $csvName = $row['Projet'] ?? '';
        if ($csvName === '') {
            return;
        }

        // 1. Matcher le projet
        $project = $this->resolveProject($csvName);
        if (!$project) {
            $io->writeln(sprintf('  <comment>⚠</comment> %s : projet non trouvé en base (skip)', $csvName));
            ++$this->projectsSkipped;

            return;
        }

        $updates = [];

        // 2. Statut
        $csvStatus = $row['📍 Statut'] ?? '';
        if ($csvStatus !== '' && isset(self::STATUS_MAP[$csvStatus])) {
            $project->status = self::STATUS_MAP[$csvStatus];
            $updates[]       = 'statut='.$project->status;
        }

        // 3. Dates
        $startDate = $this->parseDate($row['🗓️ Début contrat'] ?? '');
        if ($startDate !== null) {
            $project->startDate = $startDate;
            $updates[]          = 'dates';
        }

        $endDate = $this->parseDate($row['🗓️ Fin contrat'] ?? '');
        if ($endDate !== null) {
            $project->endDate = $endDate;
            if (!in_array('dates', $updates, true)) {
                $updates[] = 'dates';
            }
        }

        // 4. Rôles projet (si plusieurs noms séparés par virgule, prendre le premier)
        $pmName = $this->extractFirstPerson($row['🤓 PO/PM/CDP build'] ?? '');
        if ($pmName !== '') {
            $pmUser = $this->resolveUser($pmName, $company, $io);
            if ($pmUser) {
                $project->projectManager = $pmUser;
                $updates[]               = 'PM='.$pmName;
            }
        }

        $leadName = $this->extractFirstPerson($row['😏 Lead dev build'] ?? '');
        if ($leadName !== '') {
            $leadUser = $this->resolveUser($leadName, $company, $io);
            if ($leadUser) {
                $project->projectDirector = $leadUser;
                $updates[]                = 'Lead='.$leadName;
            }
        }

        $salesName = $this->extractFirstPerson($row['💸 Sales'] ?? '');
        if ($salesName !== '') {
            $salesUser = $this->resolveUser($salesName, $company, $io);
            if ($salesUser) {
                $project->salesPerson = $salesUser;
                $updates[]            = 'Sales='.$salesName;
            }
        }

        // 5. Technologies
        $techColumns = [
            '⚙️ Technos web back',
            '🖼️ Techno web front',
            '📱 Technos mobile',
            '⚙️ Technos no-code back',
            '🖼️ Technos no-code front',
        ];

        $techCount = 0;
        foreach ($techColumns as $colName) {
            $rawTechs = $row[$colName] ?? '';
            if ($rawTechs === '') {
                continue;
            }

            $techNames = array_map('trim', explode(',', $rawTechs));
            foreach ($techNames as $techName) {
                if ($techName === '') {
                    continue;
                }

                if ($this->associateTechnology($project, $techName, $company, $io)) {
                    ++$techCount;
                }
            }
        }

        if ($techCount > 0) {
            $updates[] = $techCount.' techno'.($techCount > 1 ? 's' : '');
        }

        if (count($updates) > 0) {
            $io->writeln(sprintf('  <info>✓</info> %s : %s', $project->name, implode(', ', $updates)));
            ++$this->projectsEnriched;
        } else {
            $io->writeln(sprintf('  <info>✓</info> %s : aucune modification', $project->name));
            ++$this->projectsEnriched;
        }
    }

    private function extractFirstPerson(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        // Si plusieurs personnes séparées par virgule, prendre la première
        if (str_contains($raw, ',')) {
            $parts = explode(',', $raw);

            return trim($parts[0]);
        }

        return $raw;
    }

    private function resolveProject(string $csvName): ?Project
    {
        // 1. Normaliser via aliases
        $normalizedName = self::PROJECT_ALIASES[$csvName] ?? $csvName;

        // 2. Lookup exact (case-insensitive)
        $key = mb_strtolower($normalizedName);
        if (isset($this->projectCache[$key])) {
            return $this->projectCache[$key];
        }

        // 3. Essayer le nom CSV original en lowercase
        $originalKey = mb_strtolower($csvName);
        if (isset($this->projectCache[$originalKey])) {
            return $this->projectCache[$originalKey];
        }

        // 4. Essayer un lookup case-insensitive sur les aliases inversés
        foreach (self::PROJECT_ALIASES as $alias => $dbName) {
            if (mb_strtolower($alias) === $originalKey) {
                $dbKey = mb_strtolower($dbName);
                if (isset($this->projectCache[$dbKey])) {
                    return $this->projectCache[$dbKey];
                }
            }
        }

        return null;
    }

    private function resolveUser(string $fullName, Company $company, SymfonyStyle $io): ?User
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return null;
        }

        $cacheKey = mb_strtolower($fullName);

        // 1. Déjà dans le cache utilisateur
        if (isset($this->userCache[$cacheKey])) {
            ++$this->usersReused;

            return $this->userCache[$cacheKey];
        }

        // 2. Parser le nom
        $parsed = $this->parsePersonName($fullName);

        // 3. Chercher un contributeur existant
        $contributorKey = mb_strtolower($parsed['firstName'].' '.$parsed['lastName']);
        $contributor    = $this->contributorCache[$contributorKey] ?? null;

        if ($contributor !== null && $contributor->user !== null) {
            // Contributeur avec User existant
            $this->userCache[$cacheKey] = $contributor->user;
            ++$this->usersReused;

            return $contributor->user;
        }

        // 4. Créer le User
        $user = $this->createUser($parsed['firstName'], $parsed['lastName'], $company, $io);

        // 5. Lier au contributeur existant si trouvé
        if ($contributor !== null) {
            $contributor->user = $user;
            $io->writeln(sprintf('  <info>+</info> User créé: %s (lié au contributeur existant)', $user->email));
        } else {
            // Créer aussi un Contributor
            $newContributor            = new Contributor();
            $newContributor->company   = $company;
            $newContributor->firstName = $parsed['firstName'];
            $newContributor->lastName  = $parsed['lastName'];
            $newContributor->setActive(true);
            $newContributor->user = $user;

            $this->entityManager->persist($newContributor);
            $this->contributorCache[$contributorKey] = $newContributor;

            $io->writeln(sprintf('  <info>+</info> User + Contributeur créés: %s', $user->email));
        }

        $this->userCache[$cacheKey] = $user;
        ++$this->usersCreated;

        return $user;
    }

    private function createUser(string $firstName, string $lastName, Company $company, SymfonyStyle $io): User
    {
        $email = $this->generateEmail($firstName, $lastName);

        // Vérifier si un user avec cet email existe déjà (autre company ou autre combinaison nom)
        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $existing;
        }

        $user            = new User();
        $user->email     = $email;
        $user->firstName = $firstName;
        $user->lastName  = $lastName;
        $user->setCompany($company);
        $user->setRoles(['ROLE_INTERVENANT']);
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));

        $this->entityManager->persist($user);

        return $user;
    }

    private function generateEmail(string $firstName, string $lastName): string
    {
        $transliterator = Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
        $first          = mb_strtolower($transliterator->transliterate($firstName));
        $last           = mb_strtolower($transliterator->transliterate($lastName));

        return str_replace(' ', '-', $first).'.'.str_replace(' ', '-', $last).'@thetribe.io';
    }

    /**
     * @return array{firstName: string, lastName: string}
     */
    private function parsePersonName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName));
        if ($parts === false || count($parts) < 2) {
            return ['firstName' => trim($fullName), 'lastName' => ''];
        }

        // Le dernier mot est le nom de famille
        $lastName  = array_pop($parts);
        $firstName = implode(' ', $parts);

        return [
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ];
    }

    private function associateTechnology(Project $project, string $techName, Company $company, SymfonyStyle $io): bool
    {
        // 1. Normaliser
        $normalized = self::TECH_ALIASES[$techName] ?? $techName;
        $key        = mb_strtolower($normalized);

        // 2. Chercher en base
        $technology = $this->technologyCache[$key] ?? null;

        // Essayer le nom original
        if ($technology === null) {
            $originalKey = mb_strtolower($techName);
            $technology  = $this->technologyCache[$originalKey] ?? null;
        }

        if ($technology === null) {
            $io->writeln(sprintf('    <comment>⚠ Technologie non trouvée: %s</comment>', $techName));

            return false;
        }

        // 3. Vérifier si l'association existe déjà
        foreach ($project->getProjectTechnologies() as $pt) {
            if ($pt->getTechnology()->getId() === $technology->getId()) {
                return false; // Déjà associée
            }
        }

        // 4. Créer l'association
        $pt = new ProjectTechnology();
        $pt->setCompany($company);
        $pt->setProject($project);
        $pt->setTechnology($technology);

        $project->addProjectTechnology($pt);
        $this->entityManager->persist($pt);

        ++$this->techAssociationsCreated;

        return true;
    }

    private function parseDate(string $dateString): ?DateTimeInterface
    {
        $dateString = trim($dateString);
        if ($dateString === '') {
            return null;
        }

        // Format CSV Notion : "October 3, 2025"
        $date = DateTime::createFromFormat('F j, Y', $dateString);
        if ($date !== false) {
            $date->setTime(0, 0);

            return $date;
        }

        // Essayer aussi "F j, Y" avec des variantes de mois français
        $frenchMonths = [
            'janvier'   => 'January',
            'février'   => 'February',
            'mars'      => 'March',
            'avril'     => 'April',
            'mai'       => 'May',
            'juin'      => 'June',
            'juillet'   => 'July',
            'août'      => 'August',
            'septembre' => 'September',
            'octobre'   => 'October',
            'novembre'  => 'November',
            'décembre'  => 'December',
        ];

        $lower = mb_strtolower($dateString);
        foreach ($frenchMonths as $fr => $en) {
            $lower = str_replace($fr, $en, $lower);
        }

        $date = DateTime::createFromFormat('F j, Y', $lower);
        if ($date !== false) {
            $date->setTime(0, 0);

            return $date;
        }

        return null;
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
