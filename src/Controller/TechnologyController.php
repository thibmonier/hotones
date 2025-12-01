<?php

namespace App\Controller;

use App\Entity\Technology;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/technologies')]
#[IsGranted('ROLE_MANAGER')]
class TechnologyController extends AbstractController
{
    #[Route('', name: 'technology_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        // Filtres
        $search   = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $active   = $request->query->get('active', '');

        // Query builder avec filtres
        $qb = $em->getRepository(Technology::class)->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC');

        if ($search) {
            $qb->andWhere('t.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($category) {
            $qb->andWhere('t.category = :category')
                ->setParameter('category', $category);
        }

        if ($active !== '') {
            $qb->andWhere('t.active = :active')
                ->setParameter('active', (bool) $active);
        }

        // Pagination
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            $request->query->getInt('per_page', 25),
        );

        return $this->render('technology/index.html.twig', [
            'technologies' => $pagination,
            'categories'   => $this->getTechnologyCategories(),
            'filters'      => [
                'search'   => $search,
                'category' => $category,
                'active'   => $active,
            ],
        ]);
    }

    #[Route('/export', name: 'technology_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, EntityManagerInterface $em): Response
    {
        // Mêmes filtres que l'index
        $search   = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $active   = $request->query->get('active', '');

        $qb = $em->getRepository(Technology::class)->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC');

        if ($search) {
            $qb->andWhere('t.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($category) {
            $qb->andWhere('t.category = :category')
                ->setParameter('category', $category);
        }

        if ($active !== '') {
            $qb->andWhere('t.active = :active')
                ->setParameter('active', (bool) $active);
        }

        $technologies = $qb->getQuery()->getResult();

        // Génération CSV
        $csv = "Nom;Catégorie;Couleur;Projets;Statut\n";
        foreach ($technologies as $tech) {
            $csv .= sprintf(
                "%s;%s;%s;%d;%s\n",
                $tech->getName(),
                $this->getTechnologyCategories()[$tech->getCategory()] ?? $tech->getCategory(),
                $tech->getColor()                                      ?? '',
                $tech->getProjects()->count(),
                $tech->getActive() ? 'Actif' : 'Inactif',
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'technologies_'.date('Y-m-d').'.csv',
        ));

        return $response;
    }

    #[Route('/new', name: 'technology_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $technology = new Technology();

        if ($request->isMethod('POST')) {
            $technology->setName($request->request->get('name'));
            $technology->setCategory($request->request->get('category'));
            $technology->setColor($request->request->get('color'));
            $technology->setActive($request->request->get('active', false));

            $em->persist($technology);
            $em->flush();

            $this->addFlash('success', 'Technologie créée avec succès');

            return $this->redirectToRoute('technology_index');
        }

        return $this->render('technology/new.html.twig', [
            'technology' => $technology,
            'categories' => $this->getTechnologyCategories(),
        ]);
    }

    #[Route('/{id}', name: 'technology_show', methods: ['GET'])]
    public function show(Technology $technology): Response
    {
        return $this->render('technology/show.html.twig', [
            'technology' => $technology,
        ]);
    }

    #[Route('/{id}/edit', name: 'technology_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Technology $technology, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $technology->setName($request->request->get('name'));
            $technology->setCategory($request->request->get('category'));
            $technology->setColor($request->request->get('color'));
            $technology->setActive($request->request->get('active', false));

            $em->flush();

            $this->addFlash('success', 'Technologie modifiée avec succès');

            return $this->redirectToRoute('technology_index');
        }

        return $this->render('technology/edit.html.twig', [
            'technology' => $technology,
            'categories' => $this->getTechnologyCategories(),
        ]);
    }

    #[Route('/{id}/delete', name: 'technology_delete', methods: ['POST'])]
    public function delete(Request $request, Technology $technology, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$technology->getId(), $request->request->get('_token'))) {
            $em->remove($technology);
            $em->flush();
            $this->addFlash('success', 'Technologie supprimée avec succès');
        }

        return $this->redirectToRoute('technology_index');
    }

    private function getTechnologyCategories(): array
    {
        return [
            'framework' => 'Framework',
            'cms'       => 'CMS',
            'library'   => 'Bibliothèque',
            'tool'      => 'Outil',
            'hosting'   => 'Hébergement',
            'database'  => 'Base de données',
            'language'  => 'Langage',
            'other'     => 'Autre',
        ];
    }
}
