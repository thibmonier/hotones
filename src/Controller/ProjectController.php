<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Order;
use App\Entity\Technology;
use App\Entity\ServiceCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projects')]
#[IsGranted('ROLE_INTERVENANT')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'project_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $projectRepo = $em->getRepository(Project::class);
        $projects = $projectRepo->findAllOrderedByName();

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/new', name: 'project_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $project = new Project();

        if ($request->isMethod('POST')) {
            $project->setName($request->request->get('name'));
            $project->setClient($request->request->get('client'));
            $project->setDescription($request->request->get('description'));
            $project->setIsInternal((bool)$request->request->get('is_internal'));

            // Gestion des montants (éviter les chaînes vides)
            $purchasesAmount = $request->request->get('purchases_amount');
            $project->setPurchasesAmount($purchasesAmount !== '' ? $purchasesAmount : null);
            $project->setPurchasesDescription($request->request->get('purchases_description'));

            if ($request->request->get('start_date')) {
                $project->setStartDate(new \DateTime($request->request->get('start_date')));
            }
            if ($request->request->get('end_date')) {
                $project->setEndDate(new \DateTime($request->request->get('end_date')));
            }

            // Service Category
            if ($serviceCategoryId = $request->request->get('service_category')) {
                $serviceCategory = $em->getRepository(ServiceCategory::class)->find($serviceCategoryId);
                if ($serviceCategory) {
                    $project->setServiceCategory($serviceCategory);
                }
            }

            // Technologies
            $technologyIds = $request->request->all('technologies');
            if (!empty($technologyIds)) {
                foreach ($technologyIds as $techId) {
                    $technology = $em->getRepository(Technology::class)->find($techId);
                    if ($technology) {
                        $project->addTechnology($technology);
                    }
                }
            }

            $em->persist($project);
            $em->flush();

            $this->addFlash('success', 'Projet créé avec succès');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        $technologies = $em->getRepository(Technology::class)->findBy(['active' => true], ['name' => 'ASC']);
        $serviceCategories = $em->getRepository(ServiceCategory::class)->findBy(['active' => true], ['name' => 'ASC']);

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'technologies' => $technologies,
            'service_categories' => $serviceCategories,
        ]);
    }

    #[Route('/{id}', name: 'project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/edit', name: 'project_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function edit(Request $request, Project $project, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $project->setName($request->request->get('name'));
            $project->setClient($request->request->get('client'));
            $project->setDescription($request->request->get('description'));
            $project->setIsInternal((bool)$request->request->get('is_internal'));

            // Gestion des montants (éviter les chaînes vides)
            $purchasesAmount = $request->request->get('purchases_amount');
            $project->setPurchasesAmount($purchasesAmount !== '' ? $purchasesAmount : null);
            $project->setPurchasesDescription($request->request->get('purchases_description'));
            $project->setStatus($request->request->get('status'));

            if ($request->request->get('start_date')) {
                $project->setStartDate(new \DateTime($request->request->get('start_date')));
            }
            if ($request->request->get('end_date')) {
                $project->setEndDate(new \DateTime($request->request->get('end_date')));
            }

            // Service Category
            if ($serviceCategoryId = $request->request->get('service_category')) {
                $serviceCategory = $em->getRepository(ServiceCategory::class)->find($serviceCategoryId);
                $project->setServiceCategory($serviceCategory);
            } else {
                $project->setServiceCategory(null);
            }

            // Technologies
            $project->getTechnologies()->clear();
            $technologyIds = $request->request->all('technologies');
            if (!empty($technologyIds)) {
                foreach ($technologyIds as $techId) {
                    $technology = $em->getRepository(Technology::class)->find($techId);
                    if ($technology) {
                        $project->addTechnology($technology);
                    }
                }
            }

            $em->flush();

            $this->addFlash('success', 'Projet modifié avec succès');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        $technologies = $em->getRepository(Technology::class)->findBy(['active' => true], ['name' => 'ASC']);
        $serviceCategories = $em->getRepository(ServiceCategory::class)->findBy(['active' => true], ['name' => 'ASC']);

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'technologies' => $technologies,
            'service_categories' => $serviceCategories,
        ]);
    }

    #[Route('/{id}/orders', name: 'project_orders', methods: ['GET'])]
    public function orders(Project $project): Response
    {
        return $this->render('project/orders.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/delete', name: 'project_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(Request $request, Project $project, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $em->remove($project);
            $em->flush();
            $this->addFlash('success', 'Projet supprimé avec succès');
        }

        return $this->redirectToRoute('project_index');
    }
}
