<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\OnboardingTemplate;
use App\Entity\Profile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OnboardingTemplateFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Template for Developers
        $developerTemplate = new OnboardingTemplate();
        $developerTemplate->setName('Onboarding Développeur');
        $developerTemplate->setDescription('Parcours d\'intégration pour les développeurs');
        $developerTemplate->setProfile($this->getProfileByName($manager, 'Développeur'));
        $developerTemplate->setTasks($this->getDeveloperTasks());
        $developerTemplate->setActive(true);
        $manager->persist($developerTemplate);

        // Template for Project Managers
        $pmTemplate = new OnboardingTemplate();
        $pmTemplate->setName('Onboarding Chef de Projet');
        $pmTemplate->setDescription('Parcours d\'intégration pour les chefs de projet');
        $pmTemplate->setProfile($this->getProfileByName($manager, 'Chef de projet'));
        $pmTemplate->setTasks($this->getProjectManagerTasks());
        $pmTemplate->setActive(true);
        $manager->persist($pmTemplate);

        // Template for Sales
        $salesTemplate = new OnboardingTemplate();
        $salesTemplate->setName('Onboarding Commercial');
        $salesTemplate->setDescription('Parcours d\'intégration pour les commerciaux');
        $salesTemplate->setProfile($this->getProfileByName($manager, 'Commercial'));
        $salesTemplate->setTasks($this->getSalesTasks());
        $salesTemplate->setActive(true);
        $manager->persist($salesTemplate);

        // Default template (no specific profile)
        $defaultTemplate = new OnboardingTemplate();
        $defaultTemplate->setName('Onboarding Standard');
        $defaultTemplate->setDescription('Parcours d\'intégration par défaut pour tous les profils');
        $defaultTemplate->setProfile(null);
        $defaultTemplate->setTasks($this->getDefaultTasks());
        $defaultTemplate->setActive(true);
        $manager->persist($defaultTemplate);

        $manager->flush();
    }

    /**
     * @return array<class-string>
     */
    public function getDependencies(): array
    {
        return [];
    }

    private function getProfileByName(ObjectManager $manager, string $name): ?Profile
    {
        return $manager->getRepository(Profile::class)->findOneBy(['name' => $name]);
    }

    /**
     * @return array<array{title: string, description: string, order: int, assigned_to: string, type: string, days_after_start: int}>
     */
    private function getDeveloperTasks(): array
    {
        return [
            // Week 1
            [
                'title'            => 'Accueil et présentation de l\'équipe',
                'description'      => 'Tour de table avec l\'équipe technique et présentation du projet en cours',
                'order'            => 1,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 1,
            ],
            [
                'title'            => 'Configuration du poste de travail',
                'description'      => 'Installation des outils (IDE, Docker, Git, etc.)',
                'order'            => 2,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 1,
            ],
            [
                'title'            => 'Accès aux repositories Git',
                'description'      => 'Demander les accès aux repositories principaux et cloner les projets',
                'order'            => 3,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 2,
            ],
            [
                'title'            => 'Setup environnement local',
                'description'      => 'Lancer les projets en local et vérifier que tout fonctionne',
                'order'            => 4,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 3,
            ],
            [
                'title'            => 'Lecture de la documentation technique',
                'description'      => 'Parcourir la documentation d\'architecture et les guidelines de code',
                'order'            => 5,
                'assigned_to'      => 'contributor',
                'type'             => 'lecture',
                'days_after_start' => 4,
            ],
            [
                'title'            => 'Formation aux outils et frameworks',
                'description'      => 'Session de formation sur Symfony, React, et les outils utilisés',
                'order'            => 6,
                'assigned_to'      => 'manager',
                'type'             => 'formation',
                'days_after_start' => 5,
            ],

            // Week 2
            [
                'title'            => 'Correction de bugs simples',
                'description'      => 'Prendre en charge 2-3 bugs simples pour se familiariser avec le code',
                'order'            => 7,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 8,
            ],
            [
                'title'            => 'Premier code review',
                'description'      => 'Soumettre une première PR et participer au processus de review',
                'order'            => 8,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 10,
            ],
            [
                'title'            => 'Participation au Daily Meeting',
                'description'      => 'Assister et présenter son avancement lors du daily meeting',
                'order'            => 9,
                'assigned_to'      => 'contributor',
                'type'             => 'meeting',
                'days_after_start' => 8,
            ],

            // Week 3-4
            [
                'title'            => 'Développement d\'une feature complète',
                'description'      => 'Prendre en charge une user story de bout en bout',
                'order'            => 10,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 15,
            ],
            [
                'title'            => 'Formation aux tests unitaires',
                'description'      => 'Session sur les bonnes pratiques de tests et couverture de code',
                'order'            => 11,
                'assigned_to'      => 'manager',
                'type'             => 'formation',
                'days_after_start' => 18,
            ],
            [
                'title'            => 'Point d\'étape avec le manager',
                'description'      => 'Bilan du premier mois et ajustements éventuels',
                'order'            => 12,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 30,
            ],
        ];
    }

    /**
     * @return array<array{title: string, description: string, order: int, assigned_to: string, type: string, days_after_start: int}>
     */
    private function getProjectManagerTasks(): array
    {
        return [
            // Week 1
            [
                'title'            => 'Accueil et présentation de l\'équipe',
                'description'      => 'Rencontrer l\'équipe projet et les parties prenantes',
                'order'            => 1,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 1,
            ],
            [
                'title'            => 'Accès aux outils de gestion de projet',
                'description'      => 'Obtenir les accès à Jira, Confluence, GitHub, etc.',
                'order'            => 2,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 1,
            ],
            [
                'title'            => 'Lecture de la documentation clients',
                'description'      => 'Parcourir les fiches clients et historique des projets',
                'order'            => 3,
                'assigned_to'      => 'contributor',
                'type'             => 'lecture',
                'days_after_start' => 2,
            ],
            [
                'title'            => 'Formation aux outils PM',
                'description'      => 'Session de formation sur les outils et processus projet',
                'order'            => 4,
                'assigned_to'      => 'manager',
                'type'             => 'formation',
                'days_after_start' => 3,
            ],
            [
                'title'            => 'Shadowing d\'un projet en cours',
                'description'      => 'Observer la gestion d\'un projet existant pendant 1 semaine',
                'order'            => 5,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 5,
            ],

            // Week 2-3
            [
                'title'            => 'Participation aux réunions clients',
                'description'      => 'Assister aux réunions clients en tant qu\'observateur',
                'order'            => 6,
                'assigned_to'      => 'contributor',
                'type'             => 'meeting',
                'days_after_start' => 10,
            ],
            [
                'title'            => 'Prise en main d\'un petit projet',
                'description'      => 'Gérer un projet de petite envergure en autonomie',
                'order'            => 7,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 15,
            ],
            [
                'title'            => 'Formation à la méthodologie Agile',
                'description'      => 'Session approfondie sur Scrum/Kanban et nos pratiques',
                'order'            => 8,
                'assigned_to'      => 'manager',
                'type'             => 'formation',
                'days_after_start' => 12,
            ],

            // Week 4
            [
                'title'            => 'Point d\'étape avec le manager',
                'description'      => 'Bilan du premier mois et feedback',
                'order'            => 9,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 30,
            ],
        ];
    }

    /**
     * @return array<array{title: string, description: string, order: int, assigned_to: string, type: string, days_after_start: int}>
     */
    private function getSalesTasks(): array
    {
        return [
            // Week 1
            [
                'title'            => 'Accueil et présentation de l\'équipe commerciale',
                'description'      => 'Rencontrer l\'équipe commerciale et les managers',
                'order'            => 1,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 1,
            ],
            [
                'title'            => 'Accès au CRM et outils commerciaux',
                'description'      => 'Configuration des accès à Salesforce/Pipedrive',
                'order'            => 2,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 1,
            ],
            [
                'title'            => 'Formation aux produits et services',
                'description'      => 'Session complète sur notre offre et positionnement',
                'order'            => 3,
                'assigned_to'      => 'manager',
                'type'             => 'formation',
                'days_after_start' => 2,
            ],
            [
                'title'            => 'Lecture des supports commerciaux',
                'description'      => 'Parcourir les pitch decks, cas clients et argumentaires',
                'order'            => 4,
                'assigned_to'      => 'contributor',
                'type'             => 'lecture',
                'days_after_start' => 3,
            ],
            [
                'title'            => 'Shadowing commercial senior',
                'description'      => 'Accompagner un commercial senior pendant 1 semaine',
                'order'            => 5,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 5,
            ],

            // Week 2-3
            [
                'title'            => 'Participation aux RDV prospects',
                'description'      => 'Assister aux rendez-vous prospects en observation',
                'order'            => 6,
                'assigned_to'      => 'contributor',
                'type'             => 'meeting',
                'days_after_start' => 10,
            ],
            [
                'title'            => 'Formation techniques de vente',
                'description'      => 'Session sur les techniques de closing et objections',
                'order'            => 7,
                'assigned_to'      => 'manager',
                'type'             => 'formation',
                'days_after_start' => 12,
            ],
            [
                'title'            => 'Premiers RDV en autonomie',
                'description'      => 'Mener 2-3 RDV prospects en autonomie',
                'order'            => 8,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 18,
            ],

            // Week 4
            [
                'title'            => 'Point d\'étape avec le manager',
                'description'      => 'Bilan du premier mois et fixation des objectifs',
                'order'            => 9,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 30,
            ],
        ];
    }

    /**
     * @return array<array{title: string, description: string, order: int, assigned_to: string, type: string, days_after_start: int}>
     */
    private function getDefaultTasks(): array
    {
        return [
            [
                'title'            => 'Accueil et tour de l\'entreprise',
                'description'      => 'Visite des locaux et présentation des équipes',
                'order'            => 1,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 1,
            ],
            [
                'title'            => 'Configuration du poste de travail',
                'description'      => 'Installation des outils et accès aux systèmes',
                'order'            => 2,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 1,
            ],
            [
                'title'            => 'Lecture du guide de l\'employé',
                'description'      => 'Parcourir le livret d\'accueil et le règlement intérieur',
                'order'            => 3,
                'assigned_to'      => 'contributor',
                'type'             => 'lecture',
                'days_after_start' => 2,
            ],
            [
                'title'            => 'Formation aux outils internes',
                'description'      => 'Session de formation sur les outils collaboratifs',
                'order'            => 4,
                'assigned_to'      => 'manager',
                'type'             => 'formation',
                'days_after_start' => 3,
            ],
            [
                'title'            => 'Rencontre avec les différents services',
                'description'      => 'Présentation et échange avec chaque département',
                'order'            => 5,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 7,
            ],
            [
                'title'            => 'Point d\'étape',
                'description'      => 'Bilan de la première semaine',
                'order'            => 6,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 7,
            ],
            [
                'title'            => 'Prise en main des missions',
                'description'      => 'Débuter les premières missions en autonomie',
                'order'            => 7,
                'assigned_to'      => 'contributor',
                'type'             => 'action',
                'days_after_start' => 10,
            ],
            [
                'title'            => 'Bilan du premier mois',
                'description'      => 'Point complet avec le manager sur le premier mois',
                'order'            => 8,
                'assigned_to'      => 'manager',
                'type'             => 'meeting',
                'days_after_start' => 30,
            ],
        ];
    }
}
