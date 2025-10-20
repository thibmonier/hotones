<?php

namespace App\Controller;

use App\Entity\Technology;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/technologies')]
#[IsGranted('ROLE_MANAGER')]
class TechnologyController extends AbstractController
{
    #[Route('', name: 'technology_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $technologies = $em->getRepository(Technology::class)->findBy([], ['name' => 'ASC']);

        return $this->render('technology/index.html.twig', [
            'technologies' => $technologies,
        ]);
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
            'cms' => 'CMS',
            'library' => 'Bibliothèque',
            'tool' => 'Outil',
            'hosting' => 'Hébergement',
            'database' => 'Base de données',
            'language' => 'Langage',
            'other' => 'Autre'
        ];
    }
}
