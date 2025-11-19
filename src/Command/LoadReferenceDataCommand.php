<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Profile;
use App\Entity\Technology;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-reference-data',
    description: 'Charge les données de référence (profils et technologies) depuis les fichiers de configuration',
)]
class LoadReferenceDataCommand extends Command
{
    private const PROFILES_FILE     = 'docs/profiles.md';
    private const TECHNOLOGIES_FILE = 'docs/technologies.md';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        ?string $projectDir = null
    ) {
        parent::__construct();
        // If not provided, use the default project directory
        $this->projectDir = $projectDir ?? dirname(__DIR__, 2);
    }

    private string $projectDir;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Chargement des données de référence');

        try {
            // 1. Charger les profils
            $profiles = $this->loadProfiles($io);
            $io->success(count($profiles).' profils chargés');

            // 2. Charger les technologies
            $technologies = $this->loadTechnologies($io);
            $io->success(count($technologies).' technologies chargées');

            $this->entityManager->flush();

            $io->success('Données de référence chargées avec succès !');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Erreur lors du chargement des données : '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function loadProfiles(SymfonyStyle $io): array
    {
        $io->section('Chargement des profils métier');

        $filePath = $this->projectDir.'/'.self::PROFILES_FILE;
        if (!file_exists($filePath)) {
            throw new RuntimeException("Fichier des profils introuvable : $filePath");
        }

        $content = file_get_contents($filePath);
        $lines   = explode("\n", $content);

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

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || !str_starts_with($line, '- ')) {
                continue;
            }

            $profileName = trim(substr($line, 2));
            if (empty($profileName)) {
                continue;
            }

            $profile = $repo->findOneBy(['name' => $profileName]);
            if (!$profile) {
                $profile = new Profile();
                $profile->setName($profileName);
                $io->writeln("✓ Profil créé : $profileName");
            } else {
                $io->writeln("• Profil existant : $profileName");
            }

            // Définir la description du profil
            $description = $profilesDescriptions[$profileName] ?? '';
            if ($description) {
                $profile->setDescription($description);
            }

            $this->entityManager->persist($profile);
            $profiles[] = $profile;
        }

        return $profiles;
    }

    private function loadTechnologies(SymfonyStyle $io): array
    {
        $io->section('Chargement des technologies');

        $filePath = $this->projectDir.'/'.self::TECHNOLOGIES_FILE;
        if (!file_exists($filePath)) {
            throw new RuntimeException("Fichier des technologies introuvable : $filePath");
        }

        $content = file_get_contents($filePath);
        $lines   = explode("\n", $content);

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

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || !str_starts_with($line, '- ')) {
                continue;
            }

            // Format: "- TechName (type)"
            if (!preg_match('/^- (.+?) \((.+?)\)$/', $line, $matches)) {
                continue;
            }

            $techName = trim($matches[1]);
            $techType = trim($matches[2]);

            if (empty($techName)) {
                continue;
            }

            $technology = $repo->findOneBy(['name' => $techName]);
            if (!$technology) {
                $technology = new Technology();
                $technology->setName($techName);
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
            $technology->setColor($colorsMap[$techName] ?? null);
            $technology->setActive(true);

            $this->entityManager->persist($technology);
            $technologies[] = $technology;
        }

        return $technologies;
    }
}
