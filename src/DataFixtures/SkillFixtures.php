<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SkillFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $skills = [
            // Compétences techniques - Langages
            ['name' => 'PHP', 'category' => 'technique', 'description' => 'Langage de programmation orienté serveur'],
            [
                'name'        => 'JavaScript',
                'category'    => 'technique',
                'description' => 'Langage de programmation orienté client et serveur',
            ],
            ['name' => 'TypeScript', 'category' => 'technique', 'description' => 'Sur-ensemble typé de JavaScript'],
            ['name' => 'Python', 'category' => 'technique', 'description' => 'Langage de programmation polyvalent'],
            ['name' => 'Java', 'category' => 'technique', 'description' => 'Langage de programmation orienté objet'],
            ['name' => 'C#', 'category' => 'technique', 'description' => 'Langage de programmation Microsoft'],
            ['name' => 'Go', 'category' => 'technique', 'description' => 'Langage de programmation compilé de Google'],
            ['name' => 'Rust', 'category' => 'technique', 'description' => 'Langage de programmation système'],
            ['name' => 'Ruby', 'category' => 'technique', 'description' => 'Langage de programmation dynamique'],
            ['name' => 'Swift', 'category' => 'technique', 'description' => 'Langage de programmation pour iOS/macOS'],

            // Compétences techniques - Frameworks Backend
            ['name' => 'Symfony', 'category' => 'technique', 'description' => 'Framework PHP pour applications web'],
            ['name' => 'Laravel', 'category' => 'technique', 'description' => 'Framework PHP élégant'],
            [
                'name'        => 'Node.js',
                'category'    => 'technique',
                'description' => 'Environnement d\'exécution JavaScript côté serveur',
            ],
            [
                'name'        => 'Express.js',
                'category'    => 'technique',
                'description' => 'Framework web minimaliste pour Node.js',
            ],
            [
                'name'        => 'NestJS',
                'category'    => 'technique',
                'description' => 'Framework Node.js progressif pour applications serveur',
            ],
            ['name' => 'Django', 'category' => 'technique', 'description' => 'Framework web Python de haut niveau'],
            ['name' => 'Flask', 'category' => 'technique', 'description' => 'Micro-framework web Python'],
            [
                'name'        => 'Spring Boot',
                'category'    => 'technique',
                'description' => 'Framework Java pour applications Spring',
            ],
            [
                'name'        => 'ASP.NET Core',
                'category'    => 'technique',
                'description' => 'Framework .NET pour applications web',
            ],
            ['name' => 'Ruby on Rails', 'category' => 'technique', 'description' => 'Framework web Ruby'],

            // Compétences techniques - Frameworks Frontend
            [
                'name'        => 'React',
                'category'    => 'technique',
                'description' => 'Bibliothèque JavaScript pour interfaces utilisateur',
            ],
            ['name' => 'Vue.js', 'category' => 'technique', 'description' => 'Framework JavaScript progressif'],
            ['name' => 'Angular', 'category' => 'technique', 'description' => 'Framework TypeScript de Google'],
            ['name' => 'Svelte', 'category' => 'technique', 'description' => 'Framework JavaScript compilé'],
            [
                'name'        => 'Next.js',
                'category'    => 'technique',
                'description' => 'Framework React avec rendu côté serveur',
            ],
            [
                'name'        => 'Nuxt.js',
                'category'    => 'technique',
                'description' => 'Framework Vue.js avec rendu côté serveur',
            ],

            // Compétences techniques - Bases de données
            [
                'name'        => 'MySQL',
                'category'    => 'technique',
                'description' => 'Système de gestion de base de données relationnelle',
            ],
            [
                'name'        => 'PostgreSQL',
                'category'    => 'technique',
                'description' => 'Base de données relationnelle avancée',
            ],
            ['name' => 'MariaDB', 'category' => 'technique', 'description' => 'Fork de MySQL'],
            [
                'name'        => 'MongoDB',
                'category'    => 'technique',
                'description' => 'Base de données NoSQL orientée document',
            ],
            ['name' => 'Redis', 'category' => 'technique', 'description' => 'Base de données clé-valeur en mémoire'],
            [
                'name'        => 'Elasticsearch',
                'category'    => 'technique',
                'description' => 'Moteur de recherche et d\'analyse',
            ],
            ['name' => 'Doctrine ORM', 'category' => 'technique', 'description' => 'ORM pour PHP'],

            // Compétences techniques - DevOps & Infrastructure
            ['name' => 'Docker', 'category' => 'technique', 'description' => 'Plateforme de conteneurisation'],
            ['name' => 'Kubernetes', 'category' => 'technique', 'description' => 'Orchestration de conteneurs'],
            ['name' => 'CI/CD', 'category' => 'technique', 'description' => 'Intégration et déploiement continus'],
            ['name' => 'Git', 'category' => 'technique', 'description' => 'Système de contrôle de version'],
            ['name' => 'GitHub Actions', 'category' => 'technique', 'description' => 'Plateforme CI/CD de GitHub'],
            ['name' => 'GitLab CI', 'category' => 'technique', 'description' => 'Plateforme CI/CD de GitLab'],
            ['name' => 'AWS', 'category' => 'technique', 'description' => 'Services cloud Amazon'],
            ['name' => 'Azure', 'category' => 'technique', 'description' => 'Services cloud Microsoft'],
            ['name' => 'Google Cloud', 'category' => 'technique', 'description' => 'Services cloud Google'],
            ['name' => 'Terraform', 'category' => 'technique', 'description' => 'Infrastructure as Code'],
            ['name' => 'Ansible', 'category' => 'technique', 'description' => 'Automatisation de configuration'],

            // Compétences techniques - Testing
            ['name' => 'PHPUnit', 'category' => 'technique', 'description' => 'Framework de tests unitaires PHP'],
            ['name' => 'Jest', 'category' => 'technique', 'description' => 'Framework de tests JavaScript'],
            ['name' => 'Cypress', 'category' => 'technique', 'description' => 'Tests E2E pour applications web'],
            ['name' => 'Selenium', 'category' => 'technique', 'description' => 'Automatisation de tests navigateur'],
            ['name' => 'Postman', 'category' => 'technique', 'description' => 'Tests d\'API'],

            // Compétences techniques - Autres
            ['name' => 'API REST', 'category' => 'technique', 'description' => 'Architecture d\'API RESTful'],
            ['name' => 'GraphQL', 'category' => 'technique', 'description' => 'Langage de requête pour API'],
            ['name' => 'Microservices', 'category' => 'technique', 'description' => 'Architecture microservices'],
            ['name' => 'RabbitMQ', 'category' => 'technique', 'description' => 'Message broker'],
            [
                'name'        => 'WebSockets',
                'category'    => 'technique',
                'description' => 'Communication bidirectionnelle temps réel',
            ],

            // Soft Skills
            [
                'name'        => 'Communication',
                'category'    => 'soft_skill',
                'description' => 'Capacité à communiquer efficacement',
            ],
            [
                'name'        => 'Travail en équipe',
                'category'    => 'soft_skill',
                'description' => 'Collaboration avec les autres',
            ],
            ['name' => 'Leadership', 'category' => 'soft_skill', 'description' => 'Capacité à diriger et motiver'],
            [
                'name'        => 'Résolution de problèmes',
                'category'    => 'soft_skill',
                'description' => 'Analyse et résolution de problèmes complexes',
            ],
            ['name' => 'Adaptabilité', 'category' => 'soft_skill', 'description' => 'Flexibilité face au changement'],
            ['name' => 'Gestion du temps', 'category' => 'soft_skill', 'description' => 'Organisation et priorisation'],
            ['name' => 'Créativité', 'category' => 'soft_skill', 'description' => 'Pensée innovante et créative'],
            ['name' => 'Esprit critique', 'category' => 'soft_skill', 'description' => 'Analyse critique et réflexion'],
            ['name' => 'Empathie', 'category' => 'soft_skill', 'description' => 'Compréhension des autres'],
            [
                'name'        => 'Prise de décision',
                'category'    => 'soft_skill',
                'description' => 'Capacité à décider efficacement',
            ],
            [
                'name'        => 'Présentation',
                'category'    => 'soft_skill',
                'description' => 'Capacité à présenter devant un public',
            ],
            ['name' => 'Négociation', 'category' => 'soft_skill', 'description' => 'Compétences en négociation'],

            // Méthodologies
            ['name' => 'Agile', 'category' => 'methodologie', 'description' => 'Méthodologie agile de développement'],
            ['name' => 'Scrum', 'category' => 'methodologie', 'description' => 'Framework Scrum'],
            ['name' => 'Kanban', 'category' => 'methodologie', 'description' => 'Méthode Kanban'],
            ['name' => 'Lean', 'category' => 'methodologie', 'description' => 'Méthodologie Lean'],
            ['name' => 'DevOps', 'category' => 'methodologie', 'description' => 'Culture et pratiques DevOps'],
            ['name' => 'TDD', 'category' => 'methodologie', 'description' => 'Test-Driven Development'],
            ['name' => 'BDD', 'category' => 'methodologie', 'description' => 'Behavior-Driven Development'],
            ['name' => 'DDD', 'category' => 'methodologie', 'description' => 'Domain-Driven Design'],
            ['name' => 'SOLID', 'category' => 'methodologie', 'description' => 'Principes SOLID de conception'],
            ['name' => 'Design Patterns', 'category' => 'methodologie', 'description' => 'Patrons de conception'],
            ['name' => 'Clean Code', 'category' => 'methodologie', 'description' => 'Principes de code propre'],
            ['name' => 'Code Review', 'category' => 'methodologie', 'description' => 'Revue de code'],

            // Langues
            ['name' => 'Français', 'category' => 'langue', 'description' => 'Langue française'],
            ['name' => 'Anglais', 'category' => 'langue', 'description' => 'Langue anglaise'],
            ['name' => 'Espagnol', 'category' => 'langue', 'description' => 'Langue espagnole'],
            ['name' => 'Allemand', 'category' => 'langue', 'description' => 'Langue allemande'],
            ['name' => 'Italien', 'category' => 'langue', 'description' => 'Langue italienne'],
            ['name' => 'Portugais', 'category' => 'langue', 'description' => 'Langue portugaise'],
            ['name' => 'Chinois', 'category' => 'langue', 'description' => 'Langue chinoise'],
            ['name' => 'Japonais', 'category' => 'langue', 'description' => 'Langue japonaise'],
        ];

        foreach ($skills as $skillData) {
            $skill = new Skill();
            $skill->setName($skillData['name']);
            $skill->setCategory($skillData['category']);
            $skill->setDescription($skillData['description']);
            $skill->setActive(true);

            $manager->persist($skill);
        }

        $manager->flush();
    }
}
