<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderSection;
use App\Entity\Project;
use App\Tests\Support\MultiTenantTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class OrderControllerPreviewTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client      = static::createClient();
        $this->testCompany = $this->createTestCompany();
        $this->testUser    = $this->authenticateTestUser($this->testCompany, ['ROLE_CHEF_PROJET']);
    }

    private function createOrderWithLines(): Order
    {
        $em      = $this->getEntityManager();
        $company = $this->getTestCompany();

        $project = new Project();
        $project->setName('Test Project');
        $project->setCompany($company);
        $project->setStatus('active');
        $project->setProjectType('forfait');
        $project->setStartDate(new \DateTime('2026-01-01'));
        $project->setEndDate(new \DateTime('2026-12-31'));
        $em->persist($project);
        $em->flush();

        $order = new Order();
        $order->setCompany($company);
        $order->setProject($project);
        $order->setName('Test Quote');
        $order->setOrderNumber('DEV-2026-001');
        $order->setStatus('a_signer');
        $order->setTotalAmount('5000');
        $em->persist($order);
        $em->flush();

        $section = new OrderSection();
        $section->setOrder($order);
        $section->setCompany($company);
        $section->setName('Development');
        $section->setSortOrder(1);
        $em->persist($section);
        $em->flush();

        $line = new OrderLine();
        $line->setSection($section);
        $line->setCompany($company);
        $line->setDescription('Backend development');
        $line->setDays('10');
        $line->setTjm('500');
        $line->setSortOrder(1);
        $em->persist($line);
        $em->flush();

        // Clear entity manager to force reload from DB
        $em->clear();

        return $em->getRepository(Order::class)->find($order->id);
    }

    private function createEmptyOrder(): Order
    {
        $em      = $this->getEntityManager();
        $company = $this->getTestCompany();

        $project = new Project();
        $project->setName('Empty Project');
        $project->setCompany($company);
        $project->setStatus('active');
        $project->setProjectType('forfait');
        $project->setStartDate(new \DateTime('2026-01-01'));
        $project->setEndDate(new \DateTime('2026-12-31'));
        $em->persist($project);

        $order = new Order();
        $order->setCompany($company);
        $order->setProject($project);
        $order->setName('Empty Quote');
        $order->setOrderNumber('DEV-2026-002');
        $order->setStatus('a_signer');
        $order->setTotalAmount('0');
        $em->persist($order);
        $em->flush();

        return $order;
    }

    private function createOrderWithEmptySections(): Order
    {
        $em      = $this->getEntityManager();
        $company = $this->getTestCompany();

        $project = new Project();
        $project->setName('Sections Project');
        $project->setCompany($company);
        $project->setStatus('active');
        $project->setProjectType('forfait');
        $project->setStartDate(new \DateTime('2026-01-01'));
        $project->setEndDate(new \DateTime('2026-12-31'));
        $em->persist($project);

        $order = new Order();
        $order->setCompany($company);
        $order->setProject($project);
        $order->setName('Sections Only Quote');
        $order->setOrderNumber('DEV-2026-003');
        $order->setStatus('a_signer');
        $order->setTotalAmount('0');
        $em->persist($order);

        $section = new OrderSection();
        $section->setOrder($order);
        $section->setCompany($company);
        $section->setName('Empty Section');
        $section->setSortOrder(1);
        $em->persist($section);

        $em->flush();

        return $order;
    }

    public function testPdfPreviewReturnsInlinePdf(): void
    {
        $order = $this->createOrderWithLines();

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/orders/'.$order->id.'/pdf/preview');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $this->client->getResponse()->headers->get('Content-Disposition'));
    }

    public function testPdfDownloadReturnsAttachment(): void
    {
        $order = $this->createOrderWithLines();

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/orders/'.$order->id.'/pdf');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment', $this->client->getResponse()->headers->get('Content-Disposition'));
    }

    public function testPdfPreviewReturns422ForEmptyOrder(): void
    {
        $order = $this->createEmptyOrder();

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/orders/'.$order->id.'/pdf/preview');

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPdfPreviewReturns422ForSectionsWithoutLines(): void
    {
        $order = $this->createOrderWithEmptySections();

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/orders/'.$order->id.'/pdf/preview');

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPdfPreviewRequiresAuthentication(): void
    {
        $order = $this->createOrderWithLines();

        // Request without loginUser
        $this->client->request('GET', '/orders/'.$order->id.'/pdf/preview');

        // Without login, should redirect or return 401/403
        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isRedirection() || $response->getStatusCode() === 401 || $response->getStatusCode() === 403,
            'Expected redirect to login or 401/403 status',
        );
    }

    public function testShowPageReturnsSuccessful(): void
    {
        $order = $this->createOrderWithLines();

        $this->client->loginUser($this->getTestUser());
        $this->client->request('GET', '/orders/'.$order->id);

        $this->assertResponseIsSuccessful();
    }
}
