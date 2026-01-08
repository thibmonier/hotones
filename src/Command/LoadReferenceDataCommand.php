<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Company;
use App\Entity\Profile;
use App\Entity\Technology;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-reference-data',
    description: 'Charge les données de référence (profils et technologies) depuis les fichiers de configuration',
)]
class LoadReferenceDataCommand extends Command
{
    // Données de référence embarquées (source: docs/profiles.md et docs/technologies.md)
    private const array PROFILES = [
        'développeur fullstack',
        'développeur frontend',
        'développeur backend',
        'Lead developer',
        'chef de projet',
        'product owner',
        'scrumm master',
        'directeur de projet',
        'consultant',
        'CTO',
        'expert technique',
        'Key account manager',
        'UX designer',
        'UI designer',
        'Directeur artistique',
    ];

    private const array TECHNOLOGIES = [
        ['name' => 'Php', 'category' => 'langage'],
        ['name' => 'Symfony', 'category' => 'framework'],
        ['name' => 'Laravel', 'category' => 'framework'],
        ['name' => 'Java', 'category' => 'langage'],
        ['name' => 'React', 'category' => 'framework'],
        ['name' => 'VueJS', 'category' => 'framework'],
        ['name' => 'Angular', 'category' => 'framework'],
        ['name' => 'NextJS', 'category' => 'framework'],
        ['name' => 'NuxtJS', 'category' => 'framework'],
        ['name' => 'MariaDB', 'category' => 'Base de données relationnelle'],
        ['name' => 'MongoDB', 'category' => 'Base de données noSQL'],
        ['name' => 'ElasticSearch', 'category' => 'Moteur de recherche'],
        ['name' => 'Algolia', 'category' => 'Moteur de recherche'],
        ['name' => 'Javascript', 'category' => 'langage'],
        ['name' => 'Drupal', 'category' => 'CMS'],
        ['name' => 'Wordpress', 'category' => 'CMS'],
        ['name' => 'Ibexa', 'category' => 'CMS'],
        ['name' => 'APIPlatform', 'category' => 'framework'],
        ['name' => 'Bootstrap', 'category' => 'framework'],
        ['name' => 'Tailwind', 'category' => 'framework'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('company-id', null, InputOption::VALUE_REQUIRED, 'ID de la Company (utilise la première si non spécifié)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Chargement des données de référence');

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

        try {
            // 1. Charger les profils
            $profiles = $this->loadProfiles($io, $company);
            $io->success(count($profiles).' profils chargés');

            // 2. Charger les technologies
            $technologies = $this->loadTechnologies($io, $company);
            $io->success(count($technologies).' technologies chargées');

            $this->entityManager->flush();

            $io->success('Données de référence chargées avec succès !');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Erreur lors du chargement des données : '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function loadProfiles(SymfonyStyle $io, Company $company): array
    {
        $io->section('Chargement des profils métier');

        $profilesDescriptions = [
            'développeur fullstack' => 'Développeur maîtrisant frontend et backend',
            'développeur frontend'  => 'Spécialisé en interfaces utilisateur (React, Vue, Angular)',
            'développeur backend'   => 'Spécialisé en API et bases de données',
            'Lead developer'        => 'Développeur senior avec responsabilités techniques',
            'chef de projet'        => 'Gestion de projet et coordination équipe',
            'product owner'         => 'Responsable produit et priorisation backlog',
            'scrumm master'         => 'Animation cérémonies agiles et facilitation',
            'directeur de projet'   => 'Direction stratégique des projets',
            'consultant'            => 'Conseil et expertise métier',
            'CTO'                   => 'Direction technique',
            'expert technique'      => 'Expertise technique pointue',
            'Key account manager'   => 'Gestion grands comptes',
            'UX designer'           => 'Conception expérience utilisateur',
            'UI designer'           => 'Conception interface utilisateur',
            'Directeur artistique'  => 'Direction artistique et créative',
        ];

        $profiles = [];
        $repo     = $this->entityManager->getRepository(Profile::class);

        foreach (self::PROFILES as $profileName) {
            $profile = $repo->findOneBy(['name' => $profileName, 'company' => $company]);
            if (!$profile) {
                $profile = new Profile();
                $profile->setName($profileName);
                $profile->setCompany($company);
                $io->writeln("✓ Profil créé : $profileName");
            } else {
                $io->writeln("• Profil existant : $profileName");
            }

            // Définir la description du profil
            $profile->setDescription($profilesDescriptions[$profileName]);

            $this->entityManager->persist($profile);
            $profiles[] = $profile;
        }

        return $profiles;
    }

    private function loadTechnologies(SymfonyStyle $io, Company $company): array
    {
        $io->section('Chargement des technologies');

        // Mapping des couleurs par technologie
        $colorsMap = [
            'Php'           => '#777bb4',
            'Symfony'       => '#000000',
            'Laravel'       => '#ff2d20',
            'Java'          => '#007396',
            'React'         => '#61dafb',
            'VueJS'         => '#42b883',
            'Angular'       => '#dd0031',
            'NextJS'        => '#000000',
            'NuxtJS'        => '#00dc82',
            'MariaDB'       => '#003545',
            'MongoDB'       => '#47a248',
            'ElasticSearch' => '#005571',
            'Algolia'       => '#5468ff',
            'Javascript'    => '#f7df1e',
            'Drupal'        => '#0678be',
            'Wordpress'     => '#21759b',
            'Ibexa'         => '#f15a22',
            'APIPlatform'   => '#38a9e4',
            'Bootstrap'     => '#7952b3',
            'Tailwind'      => '#06b6d4',
        ];

        $technologies = [];
        $repo         = $this->entityManager->getRepository(Technology::class);

        foreach (self::TECHNOLOGIES as $techData) {
            $techName = $techData['name'];
            $techType = $techData['category'];

            $technology = $repo->findOneBy(['name' => $techName, 'company' => $company]);
            if (!$technology) {
                $technology = new Technology();
                $technology->setName($techName);
                $technology->setCompany($company);
                $io->writeln("✓ Technologie créée : $techName ($techType)");
            } else {
                $io->writeln("• Technologie existante : $techName");
            }

            // Mapper le type vers la catégorie
            $category = match (strtolower($techType)) {
                'langage'   => 'language',
                'framework' => 'framework',
                'base de données relationnelle', 'base de données nosql' => 'database',
                'moteur de recherche' => 'search',
                'cms'                 => 'cms',
                default               => 'tool',
            };

            $technology->setCategory($category);
            $technology->setColor($colorsMap[$techName]);
            $technology->setActive(true);

            $this->entityManager->persist($technology);
            $technologies[] = $technology;
        }

        return $technologies;
    }
}
