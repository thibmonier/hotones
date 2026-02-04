<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Technology;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

/**
 * Crée les technologies par défaut pour chaque entreprise.
 * Idempotent: ne crée pas de doublons si exécuté plusieurs fois.
 */
class TechnologyFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * Configuration des technologies par catégorie avec couleurs associées.
     */
    private const TECHNOLOGIES = [
        // Langages de programmation
        ['name' => 'PHP', 'category' => 'language', 'color' => '#777BB4'],
        ['name' => 'JavaScript', 'category' => 'language', 'color' => '#F7DF1E'],
        ['name' => 'TypeScript', 'category' => 'language', 'color' => '#3178C6'],
        ['name' => 'Python', 'category' => 'language', 'color' => '#3776AB'],
        ['name' => 'Java', 'category' => 'language', 'color' => '#ED8B00'],
        ['name' => 'C#', 'category' => 'language', 'color' => '#512BD4'],
        ['name' => 'Go', 'category' => 'language', 'color' => '#00ADD8'],
        ['name' => 'Rust', 'category' => 'language', 'color' => '#DEA584'],
        ['name' => 'Ruby', 'category' => 'language', 'color' => '#CC342D'],
        ['name' => 'Swift', 'category' => 'language', 'color' => '#F05138'],
        ['name' => 'Kotlin', 'category' => 'language', 'color' => '#7F52FF'],
        ['name' => 'Dart', 'category' => 'language', 'color' => '#0175C2'],

        // Frameworks Backend
        ['name' => 'Symfony', 'category' => 'framework', 'color' => '#000000'],
        ['name' => 'Laravel', 'category' => 'framework', 'color' => '#FF2D20'],
        ['name' => 'API Platform', 'category' => 'framework', 'color' => '#38A9B4'],
        ['name' => 'Node.js', 'category' => 'framework', 'color' => '#339933'],
        ['name' => 'Express.js', 'category' => 'framework', 'color' => '#000000'],
        ['name' => 'NestJS', 'category' => 'framework', 'color' => '#E0234E'],
        ['name' => 'Django', 'category' => 'framework', 'color' => '#092E20'],
        ['name' => 'FastAPI', 'category' => 'framework', 'color' => '#009688'],
        ['name' => 'Flask', 'category' => 'framework', 'color' => '#000000'],
        ['name' => 'Spring Boot', 'category' => 'framework', 'color' => '#6DB33F'],
        ['name' => 'ASP.NET Core', 'category' => 'framework', 'color' => '#512BD4'],
        ['name' => 'Ruby on Rails', 'category' => 'framework', 'color' => '#CC0000'],

        // Frameworks Frontend
        ['name' => 'React', 'category' => 'framework', 'color' => '#61DAFB'],
        ['name' => 'Vue.js', 'category' => 'framework', 'color' => '#4FC08D'],
        ['name' => 'Angular', 'category' => 'framework', 'color' => '#DD0031'],
        ['name' => 'Svelte', 'category' => 'framework', 'color' => '#FF3E00'],
        ['name' => 'Next.js', 'category' => 'framework', 'color' => '#000000'],
        ['name' => 'Nuxt.js', 'category' => 'framework', 'color' => '#00DC82'],
        ['name' => 'Tailwind CSS', 'category' => 'framework', 'color' => '#06B6D4'],
        ['name' => 'Bootstrap', 'category' => 'framework', 'color' => '#7952B3'],

        // Mobile
        ['name' => 'Flutter', 'category' => 'framework', 'color' => '#02569B'],
        ['name' => 'React Native', 'category' => 'framework', 'color' => '#61DAFB'],
        ['name' => 'SwiftUI', 'category' => 'framework', 'color' => '#F05138'],

        // CMS
        ['name' => 'WordPress', 'category' => 'cms', 'color' => '#21759B'],
        ['name' => 'Drupal', 'category' => 'cms', 'color' => '#0678BE'],
        ['name' => 'Magento', 'category' => 'cms', 'color' => '#EE672F'],
        ['name' => 'PrestaShop', 'category' => 'cms', 'color' => '#DF0067'],
        ['name' => 'Shopify', 'category' => 'cms', 'color' => '#7AB55C'],
        ['name' => 'Strapi', 'category' => 'cms', 'color' => '#4945FF'],
        ['name' => 'Contentful', 'category' => 'cms', 'color' => '#2478CC'],

        // Bases de données
        ['name' => 'MySQL', 'category' => 'database', 'color' => '#4479A1'],
        ['name' => 'PostgreSQL', 'category' => 'database', 'color' => '#4169E1'],
        ['name' => 'MariaDB', 'category' => 'database', 'color' => '#003545'],
        ['name' => 'MongoDB', 'category' => 'database', 'color' => '#47A248'],
        ['name' => 'Redis', 'category' => 'database', 'color' => '#DC382D'],
        ['name' => 'Elasticsearch', 'category' => 'database', 'color' => '#005571'],
        ['name' => 'SQLite', 'category' => 'database', 'color' => '#003B57'],

        // Hébergement / Cloud
        ['name' => 'AWS', 'category' => 'hosting', 'color' => '#FF9900'],
        ['name' => 'Google Cloud', 'category' => 'hosting', 'color' => '#4285F4'],
        ['name' => 'Azure', 'category' => 'hosting', 'color' => '#0078D4'],
        ['name' => 'OVH', 'category' => 'hosting', 'color' => '#123F6D'],
        ['name' => 'Scaleway', 'category' => 'hosting', 'color' => '#4F0599'],
        ['name' => 'DigitalOcean', 'category' => 'hosting', 'color' => '#0080FF'],
        ['name' => 'Vercel', 'category' => 'hosting', 'color' => '#000000'],
        ['name' => 'Netlify', 'category' => 'hosting', 'color' => '#00C7B7'],
        ['name' => 'Heroku', 'category' => 'hosting', 'color' => '#430098'],

        // Outils DevOps
        ['name' => 'Docker', 'category' => 'tool', 'color' => '#2496ED'],
        ['name' => 'Kubernetes', 'category' => 'tool', 'color' => '#326CE5'],
        ['name' => 'Git', 'category' => 'tool', 'color' => '#F05032'],
        ['name' => 'GitHub', 'category' => 'tool', 'color' => '#181717'],
        ['name' => 'GitLab', 'category' => 'tool', 'color' => '#FC6D26'],
        ['name' => 'Bitbucket', 'category' => 'tool', 'color' => '#0052CC'],
        ['name' => 'Jenkins', 'category' => 'tool', 'color' => '#D24939'],
        ['name' => 'GitHub Actions', 'category' => 'tool', 'color' => '#2088FF'],
        ['name' => 'GitLab CI', 'category' => 'tool', 'color' => '#FC6D26'],
        ['name' => 'Terraform', 'category' => 'tool', 'color' => '#7B42BC'],
        ['name' => 'Ansible', 'category' => 'tool', 'color' => '#EE0000'],

        // Outils de monitoring / Testing
        ['name' => 'Grafana', 'category' => 'tool', 'color' => '#F46800'],
        ['name' => 'Prometheus', 'category' => 'tool', 'color' => '#E6522C'],
        ['name' => 'Sentry', 'category' => 'tool', 'color' => '#362D59'],
        ['name' => 'PHPUnit', 'category' => 'tool', 'color' => '#3F9CD4'],
        ['name' => 'Jest', 'category' => 'tool', 'color' => '#C21325'],
        ['name' => 'Cypress', 'category' => 'tool', 'color' => '#17202C'],
        ['name' => 'Postman', 'category' => 'tool', 'color' => '#FF6C37'],

        // Bibliothèques
        ['name' => 'Doctrine ORM', 'category' => 'library', 'color' => '#FC6A31'],
        ['name' => 'Twig', 'category' => 'library', 'color' => '#BACF2A'],
        ['name' => 'jQuery', 'category' => 'library', 'color' => '#0769AD'],
        ['name' => 'Lodash', 'category' => 'library', 'color' => '#3492FF'],
        ['name' => 'Axios', 'category' => 'library', 'color' => '#5A29E4'],
        ['name' => 'GraphQL', 'category' => 'library', 'color' => '#E10098'],
        ['name' => 'RabbitMQ', 'category' => 'library', 'color' => '#FF6600'],
        ['name' => 'Kafka', 'category' => 'library', 'color' => '#231F20'],
    ];

    public static function getGroups(): array
    {
        return ['technologies'];
    }

    public function load(ObjectManager $manager): void
    {
        $companies = $manager->getRepository(Company::class)->findAll();

        if (empty($companies)) {
            throw new RuntimeException('No companies found. Please run AppFixtures first.');
        }

        $techRepository = $manager->getRepository(Technology::class);
        $created        = 0;
        $skipped        = 0;

        foreach ($companies as $company) {
            [$companyCreated, $companySkipped] = $this->createTechnologiesForCompany(
                $manager,
                $techRepository,
                $company,
            );
            $created += $companyCreated;
            $skipped += $companySkipped;
        }

        $manager->flush();

        echo sprintf("Technologies: %d created, %d skipped (already exist)\n", $created, $skipped);
    }

    /**
     * @param \Doctrine\ORM\EntityRepository<Technology> $techRepository
     *
     * @return array{int, int} [created, skipped]
     */
    private function createTechnologiesForCompany(
        ObjectManager $manager,
        \Doctrine\ORM\EntityRepository $techRepository,
        Company $company
    ): array {
        $created = 0;
        $skipped = 0;

        foreach (self::TECHNOLOGIES as $techData) {
            // Check if technology already exists for this company
            $existing = $techRepository->findOneBy([
                'company' => $company,
                'name'    => $techData['name'],
            ]);

            if ($existing !== null) {
                ++$skipped;

                continue;
            }

            $technology           = new Technology();
            $technology->company  = $company;
            $technology->name     = $techData['name'];
            $technology->category = $techData['category'];
            $technology->color    = $techData['color'];
            $technology->active   = true;

            $manager->persist($technology);
            ++$created;
        }

        return [$created, $skipped];
    }
}
