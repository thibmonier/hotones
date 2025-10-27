<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Order;
use App\Entity\Technology;
use App\Entity\ServiceCategory;
use App\Entity\User;
use App\Entity\ProjectTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use DateTime;

#[Route('/projects')]
#[IsGranted('ROLE_INTERVENANT')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'project_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $projectRepo = $em->getRepository(Project::class);
        $projects = $projectRepo->findAllOrderedByName();
        
        // Calculer les métriques pour tous les projets
        $projectsWithMetrics = [];
        foreach ($projects as $project) {
            $metrics = $this->calculateProjectMetrics($project);
            $projectsWithMetrics[] = [
                'project' => $project,
                'metrics' => $metrics,
            ];
        }

        return $this->render('project/index.html.twig', [
            'projects_with_metrics' => $projectsWithMetrics,
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

            // Statut et type de projet
            if ($request->request->get('status')) {
                $project->setStatus($request->request->get('status'));
            }
            if ($request->request->get('project_type')) {
                $project->setProjectType($request->request->get('project_type'));
            }

            // Gestion des montants (éviter les chaînes vides)
            $purchasesAmount = $request->request->get('purchases_amount');
            $project->setPurchasesAmount($purchasesAmount !== '' ? $purchasesAmount : null);
            $project->setPurchasesDescription($request->request->get('purchases_description'));

            if ($request->request->get('start_date')) {
$project->setStartDate(new DateTime($request->request->get('start_date')));
            }
            if ($request->request->get('end_date')) {
$project->setEndDate(new DateTime($request->request->get('end_date')));
            }

            // Rôles projet (utilisateurs)
            $userRepo = $em->getRepository(User::class);
            if ($userId = $request->request->get('key_account_manager')) {
                if ($user = $userRepo->find($userId)) { $project->setKeyAccountManager($user); }
            }
            if ($userId = $request->request->get('project_manager')) {
                if ($user = $userRepo->find($userId)) { $project->setProjectManager($user); }
            }
            if ($userId = $request->request->get('project_director')) {
                if ($user = $userRepo->find($userId)) { $project->setProjectDirector($user); }
            }
            if ($userId = $request->request->get('sales_person')) {
                if ($user = $userRepo->find($userId)) { $project->setSalesPerson($user); }
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

            // Créer les tâches par défaut (AVV, Non-vendu)
            $defaultTasks = ProjectTask::createDefaultTasks($project);
            foreach ($defaultTasks as $task) {
                $em->persist($task);
            }
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
        // Calculer les métriques des devis
        $projectMetrics = $this->calculateProjectMetrics($project);
        
        return $this->render('project/show.html.twig', [
            'project' => $project,
            'metrics' => $projectMetrics,
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
            if ($request->request->get('status')) {
                $project->setStatus($request->request->get('status'));
            }
            if ($request->request->get('project_type')) {
                $project->setProjectType($request->request->get('project_type'));
            }

            if ($request->request->get('start_date')) {
$project->setStartDate(new DateTime($request->request->get('start_date')));
            }
            if ($request->request->get('end_date')) {
$project->setEndDate(new DateTime($request->request->get('end_date')));
            }

            // Rôles projet (utilisateurs)
            $userRepo = $em->getRepository(User::class);
            if (null !== $request->request->get('key_account_manager')) {
                $userId = $request->request->get('key_account_manager');
                $project->setKeyAccountManager($userId ? $userRepo->find($userId) : null);
            }
            if (null !== $request->request->get('project_manager')) {
                $userId = $request->request->get('project_manager');
                $project->setProjectManager($userId ? $userRepo->find($userId) : null);
            }
            if (null !== $request->request->get('project_director')) {
                $userId = $request->request->get('project_director');
                $project->setProjectDirector($userId ? $userRepo->find($userId) : null);
            }
            if (null !== $request->request->get('sales_person')) {
                $userId = $request->request->get('sales_person');
                $project->setSalesPerson($userId ? $userRepo->find($userId) : null);
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

    /**
     * Calcule les métriques financières du projet à partir de ses devis
     */
    private function calculateProjectMetrics(Project $project): array
    {
        $totalRevenue = '0';
        $totalDays = '0';
        $totalMargin = '0';
        $totalCost = '0';
        $totalPurchases = $project->getPurchasesAmount() ?? '0';
        $ordersByStatus = [];
        $ordersCount = 0;
        
        foreach ($project->getOrders() as $order) {
            $ordersCount++;
            
            // Compter par statut
            $status = $order->getStatus();
            if (!isset($ordersByStatus[$status])) {
                $ordersByStatus[$status] = 0;
            }
            $ordersByStatus[$status]++;
            
            // Ne compter dans les totaux que les devis signés/gagnés/terminés
            if (in_array($status, ['signed', 'won', 'completed', 'signe', 'gagne', 'termine'])) {
                // CA du devis (calculé depuis les sections)
                $orderTotal = $order->calculateTotalFromSections();
                $totalRevenue = bcadd($totalRevenue, $orderTotal, 2);
                
                // Compter les jours et calculer les marges par section
                foreach ($order->getSections() as $section) {
                    foreach ($section->getLines() as $line) {
                        // Jours vendus (seulement les lignes de service)
                        if ($line->getProfile() && $line->getDays()) {
                            $totalDays = bcadd($totalDays, $line->getDays(), 2);
                            
                            // Marge et coût estimé
                            $totalMargin = bcadd($totalMargin, $line->getGrossMargin(), 2);
                            $totalCost = bcadd($totalCost, $line->getEstimatedCost(), 2);
                        }
                        
                        // Achats de la ligne
                        if ($line->getPurchaseAmount()) {
                            $totalPurchases = bcadd($totalPurchases, $line->getPurchaseAmount(), 2);
                        }
                    }
                }
            }
        }
        
        // Calcul du taux de marge global
        $marginRate = '0';
        if (bccomp($totalRevenue, '0', 2) > 0) {
            $marginRate = bcmul(bcdiv($totalMargin, $totalRevenue, 4), '100', 2);
        }
        
        return [
            'total_revenue' => $totalRevenue,
            'total_days' => $totalDays,
            'total_margin' => $totalMargin,
            'total_cost' => $totalCost,
            'total_purchases' => $totalPurchases,
            'margin_rate' => $marginRate,
            'orders_count' => $ordersCount,
            'orders_by_status' => $ordersByStatus,
            'signed_orders_count' => array_sum(array_intersect_key($ordersByStatus, array_flip(['signed', 'won', 'completed', 'signe', 'gagne', 'termine']))),
        ];
    }
}
