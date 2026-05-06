<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Client\UseCase\CreateClient\CreateClientCommand;
use App\Application\Client\UseCase\CreateClient\CreateClientUseCase;
use App\Application\Client\UseCase\UpdateClient\UpdateClientCommand;
use App\Application\Client\UseCase\UpdateClient\UpdateClientUseCase;
use App\Domain\Client\Repository\ClientRepositoryInterface as DddClientRepositoryInterface;
use App\Domain\Client\ValueObject\ClientId as DddClientId;
use App\Entity\Client;
use App\Entity\ClientContact;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients')]
#[IsGranted('ROLE_INTERVENANT')]
class ClientController extends AbstractController
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {
    }

    #[Route('', name: 'client_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $reset = (bool) $request->query->get('reset', false);
        if ($reset) {
            $session->remove('client_filters');

            return $this->redirectToRoute('client_index');
        }

        // Charger filtres depuis la session si aucun filtre explicite n'est fourni
        $queryAll = $request->query->all();
        $filterKeys = ['search', 'service_level', 'per_page', 'sort', 'dir'];
        $hasFilter = count(array_intersect(array_keys($queryAll), $filterKeys)) > 0;
        $saved = $session->has('client_filters') ? (array) $session->get('client_filters') : [];

        // Filtres
        $search = $hasFilter ? ($request->query->get('search') ?: '') : $saved['search'] ?? '';
        $serviceLevel = $hasFilter ? ($request->query->get('service_level') ?: '') : $saved['service_level'] ?? '';

        // Tri
        $sort = $hasFilter ? ($request->query->get('sort') ?: $saved['sort'] ?? 'name') : $saved['sort'] ?? 'name';
        $dir = $hasFilter ? ($request->query->get('dir') ?: $saved['dir'] ?? 'ASC') : $saved['dir'] ?? 'ASC';

        // Pagination
        $allowedPerPage = [10, 25, 50, 100];
        $perPageParam = (int) ($hasFilter ? $request->query->get('per_page', 25) : $saved['per_page'] ?? 25);
        $perPage = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 25;

        // Sauvegarder en session
        $session->set('client_filters', [
            'search' => $search,
            'service_level' => $serviceLevel,
            'per_page' => $perPage,
            'sort' => $sort,
            'dir' => $dir,
        ]);

        // Query builder avec filtres et tri
        $qb = $em->getRepository(Client::class)->createQueryBuilder('c');

        if ($search) {
            $qb->andWhere('c.name LIKE :search OR c.website LIKE :search OR c.description LIKE :search')->setParameter(
                'search',
                '%'.$search.'%',
            );
        }

        if ($serviceLevel !== '') {
            $qb->andWhere('c.serviceLevel = :serviceLevel')->setParameter('serviceLevel', $serviceLevel);
        }

        // Tri
        $validSortFields = ['name' => 'c.name', 'serviceLevel' => 'c.serviceLevel'];
        $sortField = $validSortFields[$sort] ?? 'c.name';
        $sortDir = strtoupper((string) $dir) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($sortField, $sortDir);

        // Pagination
        $pagination = $paginator->paginate($qb->getQuery(), $request->query->getInt('page', 1), $perPage);

        return $this->render('client/index.html.twig', [
            'clients' => $pagination,
            'filters' => [
                'search' => $search,
                'service_level' => $serviceLevel,
            ],
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    #[Route('/export', name: 'client_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, EntityManagerInterface $em): Response
    {
        // Mêmes filtres que l'index
        $search = $request->query->get('search', '');
        $serviceLevel = $request->query->get('service_level', '');

        $qb = $em->getRepository(Client::class)->createQueryBuilder('c')->orderBy('c.name', 'ASC');

        if ($search) {
            $qb->andWhere('c.name LIKE :search OR c.website LIKE :search OR c.description LIKE :search')->setParameter(
                'search',
                '%'.$search.'%',
            );
        }

        if ($serviceLevel !== '') {
            $qb->andWhere('c.serviceLevel = :serviceLevel')->setParameter('serviceLevel', $serviceLevel);
        }

        $clients = $qb->getQuery()->getResult();

        // Génération CSV
        $csv = "Nom;Site web;Niveau de service;Contacts\n";
        foreach ($clients as $client) {
            $csv .= sprintf(
                "%s;%s;%s;%d\n",
                $client->getName(),
                $client->getWebsite() ?? '',
                $client->getServiceLevel() ?? '',
                $client->getContacts()->count(),
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'clients_'.date('Y-m-d').'.csv',
        ));

        return $response;
    }

    /**
     * EPIC-001 Phase 4 — sprint-012 1ère décommission legacy.
     *
     * Route principale `/clients/new` migrée vers DDD use case (était sur
     * route alternative `/new-via-ddd` depuis sprint-010 PR #148).
     *
     * Logo upload séparé sur `/clients/{id}/upload-logo` (post-création).
     *
     * @see ADR-0009 controller migration pattern (Phase 4 critères)
     * @see ADR-0008 Anti-Corruption Layer pattern
     */
    #[Route('/new', name: 'client_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function new(Request $request, CreateClientUseCase $useCase): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $command = new CreateClientCommand(
                    name: trim((string) $request->request->get('name', '')),
                    serviceLevel: (string) $request->request->get('service_level', 'standard'),
                    notes: $request->request->get('description'),
                );
                $clientId = $useCase->execute($command);

                $this->addFlash('success', 'Client créé avec succès');

                return $this->redirectToRoute('client_show', ['id' => $clientId->toLegacyInt()]);
            } catch (InvalidArgumentException $e) {
                $this->addFlash('danger', 'Validation: '.$e->getMessage());

                return $this->redirectToRoute('client_index');
            }
        }

        // GET: render form template (template existant continue de fonctionner)
        $client = new Client();
        $client->setCompany($this->companyContext->getCurrentCompany());

        return $this->render('client/new.html.twig', [
            'client' => $client,
        ]);
    }

    /**
     * EPIC-001 Phase 4 — endpoint séparé pour logo upload (séparation des concerns).
     *
     * Le UC DDD CreateClient ne gère pas le logo (filesystem side-effect).
     * Cet endpoint accepte un upload post-création.
     */
    #[Route('/{id}/upload-logo', name: 'client_upload_logo', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function uploadLogo(Client $client, Request $request, EntityManagerInterface $em): Response
    {
        /** @var UploadedFile|null $logo */
        $logo = $request->files->get('logo');
        if ($logo instanceof UploadedFile && $logo->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/clients';
            $fs = new Filesystem();
            if (!$fs->exists($uploadDir)) {
                $fs->mkdir($uploadDir, 0775);
            }
            $safeName = uniqid('client_', true).'.'.$logo->guessExtension();
            $logo->move($uploadDir, $safeName);
            $client->setLogoPath('/uploads/clients/'.$safeName);
            $em->flush();

            $this->addFlash('success', 'Logo uploadé');
        }

        return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
    }

    #[Route('/{id}', name: 'client_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        // Use repository findOneByIdForCompany() which filters by company
        $client = $em->getRepository(Client::class)->findOneByIdForCompany($id);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    /**
     * EPIC-001 Phase 3 — sprint-011 client edit migration to DDD.
     *
     * @see ADR-0009 controller migration pattern
     */
    #[Route('/{id}/edit-via-ddd', name: 'client_edit_ddd', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function editViaDdd(int $id, Request $request, UpdateClientUseCase $useCase, DddClientRepositoryInterface $repository): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $command = new UpdateClientCommand(
                    clientId: $id,
                    name: trim((string) $request->request->get('name', '')),
                    serviceLevel: (string) $request->request->get('service_level', 'standard'),
                    notes: $request->request->get('description'),
                );
                $useCase->execute($command);

                $this->addFlash('success', 'Client modifié avec succès via DDD use case');

                return $this->redirectToRoute('client_show', ['id' => $id]);
            } catch (InvalidArgumentException $e) {
                $this->addFlash('danger', 'Validation: '.$e->getMessage());
            }
        }

        // GET: read via DDD repository (proves the read path works too)
        $ddd = $repository->findByIdOrNull(DddClientId::fromLegacyInt($id));
        if ($ddd === null) {
            throw $this->createNotFoundException('Client not found');
        }

        // Render existing edit template; populate flat-shape fields from DDD
        // to keep template compatible.
        $clientView = (object) [
            'id' => $id,
            'name' => $ddd->getName()->getValue(),
            'website' => null,
            'description' => $ddd->getNotes(),
            'serviceLevel' => $ddd->getServiceLevel()->value,
            'serviceLevelMode' => 'manual',
            'logoPath' => null,
        ];

        return $this->render('client/edit.html.twig', [
            'client' => $clientView,
        ]);
    }

    /**
     * EPIC-001 Phase 3 — sprint-011 client delete migration to DDD.
     *
     * @see ADR-0009 controller migration pattern
     */
    #[Route('/{id}/delete-via-ddd', name: 'client_delete_ddd', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteViaDdd(int $id, DddClientRepositoryInterface $repository): Response
    {
        $ddd = $repository->findByIdOrNull(DddClientId::fromLegacyInt($id));
        if ($ddd === null) {
            throw $this->createNotFoundException('Client not found');
        }

        $repository->delete($ddd);

        $this->addFlash('success', 'Client supprimé via DDD use case');

        return $this->redirectToRoute('client_index');
    }

    #[Route('/{id}/edit', name: 'client_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function edit(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $client->setName($request->request->get('name'));
            $client->setWebsite($request->request->get('website'));
            $client->setDescription($request->request->get('description'));

            // Gestion du niveau de service
            $serviceLevelMode = $request->request->get('service_level_mode', 'auto');
            $client->setServiceLevelMode($serviceLevelMode);

            if ($serviceLevelMode === 'manual') {
                $client->setServiceLevel($request->request->get('service_level'));
            } else {
                // En mode auto, le niveau sera calculé plus tard par la commande
                $client->setServiceLevel(null);
            }

            /** @var UploadedFile|null $logo */
            $logo = $request->files->get('logo');
            if ($logo instanceof UploadedFile && $logo->isValid()) {
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/clients';
                $fs = new Filesystem();
                if (!$fs->exists($uploadDir)) {
                    $fs->mkdir($uploadDir, 0775);
                }
                $safeName = uniqid('client_', true).'.'.$logo->guessExtension();
                $logo->move($uploadDir, $safeName);
                $client->setLogoPath('/uploads/clients/'.$safeName);
            }

            $em->flush();

            $this->addFlash('success', 'Client modifié avec succès');

            return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/delete', name: 'client_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->request->get('_token'))) {
            $em->remove($client);
            $em->flush();
            $this->addFlash('success', 'Client supprimé avec succès');
        }

        return $this->redirectToRoute('client_index');
    }

    #[Route('/{id}/contacts/new', name: 'client_contact_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function addContact(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        $contact = new ClientContact();
        $contact->setClient($client);
        $contact->setCompany($client->getCompany());

        if ($request->isMethod('POST')) {
            $contact->setLastName($request->request->get('last_name'));
            $contact->setFirstName($request->request->get('first_name'));
            $contact->setEmail($request->request->get('email'));
            $contact->setPhone($request->request->get('phone'));
            $contact->setMobilePhone($request->request->get('mobile_phone'));
            $contact->setPositionTitle($request->request->get('position_title'));

            $em->persist($contact);
            $em->flush();

            $this->addFlash('success', 'Contact ajouté avec succès');

            return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
        }

        return $this->render('client_contact/new.html.twig', [
            'client' => $client,
            'contact' => $contact,
        ]);
    }
}
