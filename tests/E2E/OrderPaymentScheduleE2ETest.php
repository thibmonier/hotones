<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderSection;
use App\Factory\ProfileFactory;
use App\Factory\ProjectFactory;
use App\Factory\UserFactory;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group e2e
 */
class OrderPaymentScheduleE2ETest extends PantherTestCase
{
    use Factories;
    use ResetDatabase;

    private function createOrderData(): Order
    {
        $project = ProjectFactory::createOne(['status' => 'active']);
        $profile = ProfileFactory::createOne(['name' => 'Dev', 'defaultDailyRate' => '600']);

        // Build a minimal order with one section/line totaling 5000€
        $order = new Order();
        $order->setOrderNumber('DE2E-001')->setProject($project)->setStatus('a_signer')->setContractType('forfait');

        $section = new OrderSection()
            ->setOrder($order)
            ->setTitle('Section 1');

        $line = new OrderLine()
            ->setSection($section)
            ->setDescription('Implémentation')
            ->setType('service')
            ->setProfile($profile)
            ->setDailyRate('1000')
            ->setDays('5'); // 5000€

        // Wire up
        $order->addSection($section);
        $section->addLine($line);

        // Persist via Doctrine
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($order);
        $em->persist($section);
        $em->persist($line);
        $em->flush();

        return $order;
    }

    public function testAddPaymentSchedulePercent(): void
    {
        $client = static::createPantherClient();
        $user   = UserFactory::createOne(['roles' => ['ROLE_CHEF_PROJET'], 'password' => 'password']);
        $order  = $this->createOrderData();

        // Login
        $crawler = $client->request('GET', '/login');
        $form    = $crawler
            ->filter('form')
            ->form([
                '_username' => $user->getEmail(),
                '_password' => 'password',
            ]);
        $client->submit($form);

        // Go to edit order
        $client->request('GET', '/orders/'.$order->getId().'/edit');
        $client->waitFor('form');

        // Fill schedule form (50% on a date)
        $client->executeScript('document.querySelector(\'input[name="billing_date"]\').value = "2025-01-15";');
        $client->getCrawler()->filter('select[name="amount_type"]')->selectOption('percent');
        $client->executeScript('document.querySelector(\'input[name="percent"]\').value = "50";');

        // Submit the schedule form
        $formNode = $client->getCrawler()->filter('form[action$="/schedule/add"]')->first();
        $client->submit($formNode->form());

        // Expect row appears in schedule table and coverage shows 50%
        $client->waitFor('#amount_type'); // ensure reloaded
        $this->assertGreaterThan(0, $client->getCrawler()->filter('table tbody tr')->count());
        $this->assertSelectorTextContains('.alert', 'Couverture:');
    }
}
