<?php

namespace App\Controller;

use App\Entity\ServiceCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/service-categories')]
#[IsGranted('ROLE_MANAGER')]
class ServiceCategoryController extends AbstractController
{
    #[Route('', name: 'service_category_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $serviceCategories = $em->getRepository(ServiceCategory::class)->findBy([], ['name' => 'ASC']);

        return $this->render('service_category/index.html.twig', [
            'serviceCategories' => $serviceCategories,
        ]);
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
