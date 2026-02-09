<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Factory\ContributorFactory;
use App\Factory\ProjectFactory;
use App\Factory\ProjectTaskFactory;
use App\Factory\UserFactory;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group e2e
 */
class TimesheetE2ETest extends PantherTestCase
{
    use Factories;
    use ResetDatabase;

    public function testCompleteTimesheetFlow(): void
    {
        $client = static::createPantherClient();

        // Créer les données de test
        $user = UserFactory::createOne([
            'roles'    => ['ROLE_USER', 'ROLE_INTERVENANT'],
            'password' => 'password',
        ]);

        $contributor = ContributorFactory::createOne([
            'user'      => $user,
            'firstName' => 'Jean',
            'lastName'  => 'Dupont',
        ]);

        $project1 = ProjectFactory::createOne([
            'name'   => 'Projet Test E2E',
            'status' => 'in_progress',
        ]);

        $task1 = ProjectTaskFactory::createOne([
            'project' => $project1,
            'name'    => 'Développement',
        ]);

        $project2 = ProjectFactory::createOne([
            'name'   => 'Projet Test 2',
            'status' => 'in_progress',
        ]);

        // 1. Login
        $crawler = $client->request('GET', '/login');
        $client->waitFor('form');

        $form = $crawler
            ->filter('form')
            ->form([
                '_username' => $user->getEmail(),
                '_password' => 'password',
            ]);
        $client->submit($form);
        $client->waitFor('body');
        sleep(1);

        // 2. Naviguer vers la page de saisie des temps
        $client->request('GET', '/timesheet');
        $client->waitFor('.page-title-box');
        $this->assertSelectorTextContains('h4', 'Saisie des temps');

        // 3. Vérifier que la grille hebdomadaire est affichée
        $client->waitFor('#timesheet-table');
        $this->assertSelectorExists('#timesheet-table');

        // 4. Vérifier la présence du bouton de conversion heures/jours
        $this->assertSelectorExists('#toggle-mode-btn');

        // 5. Vérifier la présence du bouton "Vue Calendrier"
        $this->assertSelectorTextContains('a.btn', 'Vue Calendrier');

        // 6. Vérifier la présence du bouton "Dupliquer semaine"
        $this->assertSelectorTextContains('button.btn', 'Dupliquer semaine');

        // 7. Cliquer sur le bouton de conversion pour passer en jours
        $toggleBtn = $client->findElement(\Facebook\WebDriver\WebDriverBy::id('toggle-mode-btn'));
        $toggleBtn->click();
        sleep(1); // Attendre que le changement soit appliqué

        // Vérifier que le texte du bouton a changé
        $this->assertSelectorTextContains('#toggle-mode-text', 'Afficher en heures');

        // 8. Re-cliquer pour revenir en heures
        $toggleBtn->click();
        sleep(1);
        $this->assertSelectorTextContains('#toggle-mode-text', 'Afficher en jours');

        // 9. Naviguer vers la vue calendrier
        $calendarLink = $client->findElement(\Facebook\WebDriver\WebDriverBy::linkText('Vue Calendrier'));
        $calendarLink->click();
        $client->waitFor('#calendar');

        // Vérifier que la vue calendrier est chargée
        $this->assertSelectorExists('#calendar');
        $this->assertSelectorTextContains('h4', 'Calendrier de saisie des temps');

        // 10. Vérifier la présence du bouton "Vue Grille"
        $this->assertSelectorTextContains('a.btn', 'Vue Grille');

        // 11. Revenir à la vue grille
        $gridLink = $client->findElement(\Facebook\WebDriver\WebDriverBy::linkText('Vue Grille'));
        $gridLink->click();
        $client->waitFor('#timesheet-table');

        // Vérifier qu'on est bien revenu à la grille
        $this->assertSelectorExists('#timesheet-table');
    }

    public function testTimesheetCalendarInteraction(): void
    {
        $client = static::createPantherClient();

        // Créer les données de test
        $user = UserFactory::createOne([
            'roles'    => ['ROLE_USER', 'ROLE_INTERVENANT'],
            'password' => 'password',
        ]);

        $contributor = ContributorFactory::createOne(['user' => $user]);
        $project     = ProjectFactory::createOne(['name' => 'Projet Calendrier']);

        // Login
        $crawler = $client->request('GET', '/login');
        $client->waitFor('form');

        $form = $crawler
            ->filter('form')
            ->form([
                '_username' => $user->getEmail(),
                '_password' => 'password',
            ]);
        $client->submit($form);
        $client->waitFor('body');
        sleep(1);

        // Naviguer vers la vue calendrier
        $client->request('GET', '/timesheet/calendar');
        $client->waitFor('#calendar');

        // Vérifier que le calendrier FullCalendar est initialisé
        // FullCalendar ajoute la classe fc sur le conteneur
        sleep(2); // Attendre que FullCalendar s'initialise
        $this->assertSelectorExists('.fc');
        $this->assertSelectorExists('.fc-toolbar');

        // Vérifier les boutons de navigation du calendrier
        $this->assertSelectorExists('.fc-prev-button');
        $this->assertSelectorExists('.fc-next-button');
        $this->assertSelectorExists('.fc-today-button');
    }

    public function testDuplicateWeekModal(): void
    {
        $client = static::createPantherClient();

        // Créer les données de test
        $user = UserFactory::createOne([
            'roles'    => ['ROLE_USER', 'ROLE_INTERVENANT'],
            'password' => 'password',
        ]);

        $contributor = ContributorFactory::createOne(['user' => $user]);

        // Login
        $crawler = $client->request('GET', '/login');
        $client->waitFor('form');

        $form = $crawler
            ->filter('form')
            ->form([
                '_username' => $user->getEmail(),
                '_password' => 'password',
            ]);
        $client->submit($form);
        $client->waitFor('body');
        sleep(1);

        // Naviguer vers la page de saisie
        $client->request('GET', '/timesheet');
        $client->waitFor('#timesheet-table');

        // Cliquer sur le bouton "Dupliquer semaine"
        $duplicateBtn = $client->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector(
            '[data-bs-target="#duplicateWeekModal"]',
        ));
        $duplicateBtn->click();
        sleep(1); // Attendre que la modal s'ouvre

        // Vérifier que la modal est visible
        $client->waitFor('#duplicateWeekModal.show');
        $this->assertSelectorExists('#duplicateWeekModal.show');

        // Vérifier la présence des champs de la modal
        $this->assertSelectorExists('#source-week');
        $this->assertSelectorExists('#target-week');
        $this->assertSelectorExists('#confirm-duplicate-btn');

        // Vérifier le texte d'information
        $this->assertSelectorTextContains('.alert-info', 'Les projets, tâches et heures seront copiés');

        // Fermer la modal en cliquant sur Annuler
        $cancelBtn = $client->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector(
            '#duplicateWeekModal .btn-secondary',
        ));
        $cancelBtn->click();
        sleep(1);

        // Vérifier que la modal est fermée
        $this->assertSelectorNotExists('#duplicateWeekModal.show');
    }

    public function testWeekNavigation(): void
    {
        $client = static::createPantherClient();

        // Créer les données de test
        $user = UserFactory::createOne([
            'roles'    => ['ROLE_USER', 'ROLE_INTERVENANT'],
            'password' => 'password',
        ]);

        $contributor = ContributorFactory::createOne(['user' => $user]);

        // Login
        $crawler = $client->request('GET', '/login');
        $client->waitFor('form');

        $form = $crawler
            ->filter('form')
            ->form([
                '_username' => $user->getEmail(),
                '_password' => 'password',
            ]);
        $client->submit($form);
        $client->waitFor('body');
        sleep(1);

        // Naviguer vers la page de saisie
        $client->request('GET', '/timesheet');
        $client->waitFor('#timesheet-table');

        // Récupérer le texte de l'en-tête de la semaine actuelle
        $currentWeekText = $client->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.card-title'))->getText();

        // Cliquer sur "Semaine précédente"
        $prevWeekLink = $client->findElement(\Facebook\WebDriver\WebDriverBy::linkText('Semaine précédente'));
        $prevWeekLink->click();
        $client->waitFor('#timesheet-table');
        sleep(1);

        // Vérifier que l'en-tête a changé
        $newWeekText = $client->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.card-title'))->getText();
        $this->assertNotEquals($currentWeekText, $newWeekText);

        // Cliquer sur "Semaine suivante" pour revenir
        $nextWeekLink = $client->findElement(\Facebook\WebDriver\WebDriverBy::linkText('Semaine suivante'));
        $nextWeekLink->click();
        $client->waitFor('#timesheet-table');
        sleep(1);

        // Vérifier qu'on est revenu à la semaine initiale
        $backToCurrentText = $client
            ->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.card-title'))
            ->getText();
        $this->assertEquals($currentWeekText, $backToCurrentText);
    }
}
