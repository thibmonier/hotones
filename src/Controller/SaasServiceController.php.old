<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SaasService;
use App\Form\SaasServiceType;
use App\Repository\SaasServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/saas/services')]
#[IsGranted('ROLE_ADMIN')]
class SaasServiceController extends AbstractController
{
    #[Route('', name: 'saas_service_index', methods: ['GET'])]
    public function index(
        Request $request,
        SaasServiceRepository $serviceRepository,
        PaginatorInterface $paginator
    ): Response {
        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);

        if ($reset) {
            $session->remove('saas_service_filters');

            return $this->redirectToRoute('saas_service_index');
        }

        $queryAll   = $request->query->all();
        $filterKeys = ['search', 'category', 'provider', 'active', 'per_page', 'sort', 'dir'];
        $hasFilter  = count(array_intersect(array_keys($queryAll), $filterKeys)) > 0;
        $saved      = $session->has('saas_service_filters') ? (array) $session->get('saas_service_filters') : [];

        $search       = $hasFilter ? ($request->query->get('search') ?: '') : ($saved['search'] ?? '');
        $category     = $hasFilter ? ($request->query->get('category') ?: '') : ($saved['category'] ?? '');
        $providerId   = $hasFilter ? ($request->query->get('provider') ?: '') : ($saved['provider'] ?? '');
        $activeFilter = $hasFilter ? ($request->query->get('active') ?: '') : ($saved['active'] ?? '');

        $sort = $hasFilter ? ($request->query->get('sort') ?: ($saved['sort'] ?? 'name')) : ($saved['sort'] ?? 'name');
        $dir  = $hasFilter ? ($request->query->get('dir') ?: ($saved['dir'] ?? 'ASC')) : ($saved['dir'] ?? 'ASC');

        $allowedPerPage = [10, 25, 50, 100];
        $perPageParam   = (int) ($hasFilter ? ($request->query->get('per_page', 25)) : ($saved['per_page'] ?? 25));
        $perPage        = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 25;

        $session->set('saas_service_filters', [
            'search'   => $search,
            'category' => $category,
            'provider' => $providerId,
            'active'   => $activeFilter,
            'per_page' => $perPage,
            'sort'     => $sort,
            'dir'      => $dir,
        ]);

        $qb = $serviceRepository->createQueryBuilder('s')
            ->leftJoin('s.provider', 'p')
            ->addSelect('p');

        if ($search) {
            $qb->andWhere('s.name LIKE :search OR s.description LIKE :search OR p.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($category !== '') {
            $qb->andWhere('s.category = :category')
                ->setParameter('category', $category);
        }

        if ($providerId !== '') {
            if ($providerId === 'null') {
                $qb->andWhere('s.provider IS NULL');
            } else {
                $qb->andWhere('s.provider = :provider')
                    ->setParameter('provider', $providerId);
            }
        }

        if ($activeFilter !== '') {
            $qb->andWhere('s.active = :active')
                ->setParameter('active', (bool) $activeFilter);
        }

        $validSortFields = [
            'name'     => 's.name',
            'category' => 's.category',
            'provider' => 'p.name',
        ];
        $sortField = $validSortFields[$sort] ?? 's.name';
        $sortDir   = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($sortField, $sortDir);

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            $perPage,
        );

        return $this->render('saas/service/index.html.twig', [
            'services' => $pagination,
            'filters'  => [
                'search'   => $search,
                'category' => $category,
                'provider' => $providerId,
                'active'   => $activeFilter,
            ],
            'sort'       => $sort,
            'dir'        => $dir,
            'categories' => $serviceRepository->findAllCategories(),
        ]);
    }

    #[Route('/new', name: 'saas_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $service = new SaasService();
        $form    = $this->createForm(SaasServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($service);
            $em->flush();

            $this->addFlash('success', 'Service créé avec succès.');

            return $this->redirectToRoute('saas_service_show', ['id' => $service->getId()]);
        }

        return $this->render('saas/service/new.html.twig', [
            'service' => $service,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'saas_service_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(SaasService $service): Response
    {
        return $this->render('saas/service/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/{id}/edit', name: 'saas_service_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, SaasService $service, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SaasServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Service modifié avec succès.');

            return $this->redirectToRoute('saas_service_show', ['id' => $service->getId()]);
        }

        return $this->render('saas/service/edit.html.twig', [
            'service' => $service,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'saas_service_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, SaasService $service, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), (string) $request->request->get('_token'))) {
            // Vérifier qu'il n'y a pas d'abonnements actifs
            if ($service->getSubscriptions()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce service car il a des abonnements actifs.');

                return $this->redirectToRoute('saas_service_show', ['id' => $service->getId()]);
            }

            $em->remove($service);
            $em->flush();

            $this->addFlash('success', 'Service supprimé avec succès.');
        }

        return $this->redirectToRoute('saas_service_index');
    }
}
