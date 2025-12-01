<?php

namespace App\Controller;

use App\Entity\ServiceCategory;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/service-categories')]
#[IsGranted('ROLE_MANAGER')]
class ServiceCategoryController extends AbstractController
{
    #[Route('', name: 'service_category_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        // Filtres
        $search = $request->query->get('search', '');
        $active = $request->query->get('active', '');

        // Query builder avec filtres
        $qb = $em->getRepository(ServiceCategory::class)->createQueryBuilder('sc')
            ->orderBy('sc.name', 'ASC');

        if ($search) {
            $qb->andWhere('sc.name LIKE :search OR sc.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($active !== '') {
            $qb->andWhere('sc.active = :active')
                ->setParameter('active', (bool) $active);
        }

        // Pagination
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            $request->query->getInt('per_page', 25),
        );

        return $this->render('service_category/index.html.twig', [
            'serviceCategories' => $pagination,
            'filters'           => [
                'search' => $search,
                'active' => $active,
            ],
        ]);
    }

    #[Route('/export', name: 'service_category_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, EntityManagerInterface $em): Response
    {
        // Mêmes filtres que l'index
        $search = $request->query->get('search', '');
        $active = $request->query->get('active', '');

        $qb = $em->getRepository(ServiceCategory::class)->createQueryBuilder('sc')
            ->orderBy('sc.name', 'ASC');

        if ($search) {
            $qb->andWhere('sc.name LIKE :search OR sc.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($active !== '') {
            $qb->andWhere('sc.active = :active')
                ->setParameter('active', (bool) $active);
        }

        $serviceCategories = $qb->getQuery()->getResult();

        // Génération CSV
        $csv = "Nom;Description;Couleur;Projets;Statut\n";
        foreach ($serviceCategories as $category) {
            $csv .= sprintf(
                "%s;%s;%s;%d;%s\n",
                $category->getName(),
                $category->getDescription() ?? '',
                $category->getColor()       ?? '',
                $category->getProjects()->count(),
                $category->getActive() ? 'Actif' : 'Inactif',
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'categories_service_'.date('Y-m-d').'.csv',
        ));

        return $response;
    }

    #[Route('/new', name: 'service_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $serviceCategory = new ServiceCategory();

        if ($request->isMethod('POST')) {
            $serviceCategory->setName($request->request->get('name'));
            $serviceCategory->setDescription($request->request->get('description'));
            $serviceCategory->setColor($request->request->get('color'));
            $serviceCategory->setActive($request->request->get('active', false));

            $em->persist($serviceCategory);
            $em->flush();

            $this->addFlash('success', 'Catégorie de service créée avec succès');

            return $this->redirectToRoute('service_category_index');
        }

        return $this->render('service_category/new.html.twig', [
            'serviceCategory' => $serviceCategory,
        ]);
    }

    #[Route('/{id}', name: 'service_category_show', methods: ['GET'])]
    public function show(ServiceCategory $serviceCategory): Response
    {
        return $this->render('service_category/show.html.twig', [
            'serviceCategory' => $serviceCategory,
        ]);
    }

    #[Route('/{id}/edit', name: 'service_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ServiceCategory $serviceCategory, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $serviceCategory->setName($request->request->get('name'));
            $serviceCategory->setDescription($request->request->get('description'));
            $serviceCategory->setColor($request->request->get('color'));
            $serviceCategory->setActive($request->request->get('active', false));

            $em->flush();

            $this->addFlash('success', 'Catégorie de service modifiée avec succès');

            return $this->redirectToRoute('service_category_index');
        }

        return $this->render('service_category/edit.html.twig', [
            'serviceCategory' => $serviceCategory,
        ]);
    }

    #[Route('/{id}/delete', name: 'service_category_delete', methods: ['POST'])]
    public function delete(Request $request, ServiceCategory $serviceCategory, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$serviceCategory->getId(), $request->request->get('_token'))) {
            $em->remove($serviceCategory);
            $em->flush();
            $this->addFlash('success', 'Catégorie de service supprimée avec succès');
        }

        return $this->redirectToRoute('service_category_index');
    }
}
