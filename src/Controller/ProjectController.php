<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Entity\Technology;
use App\Form\ProjectType as ProjectFormType;
use DateTime;
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
    public function index(Request $request, EntityManagerInterface $em, \App\Service\ProfitabilityService $profitabilityService): Response
    {
        $projectRepo = $em->getRepository(Project::class);

        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);
        if ($reset && $session) {
            $session->remove('project_filters');

            return $this->redirectToRoute('project_index');
        }

        // Charger filtres depuis la session si aucun filtre explicite n'est fourni
        $queryAll   = $request->query->all();
        $filterKeys = ['year', 'start_date', 'end_date', 'project_type', 'status', 'technology', 'per_page', 'sort', 'dir'];
        $hasFilter  = count(array_intersect(array_keys($queryAll), $filterKeys)) > 0;
        $saved      = ($session && $session->has('project_filters')) ? (array) $session->get('project_filters') : [];

        // Filtres période: année courante par défaut
        $yearParam  = $hasFilter ? ($request->query->get('year') ?? null) : ($saved['year'] ?? null);
        $year       = (int) ($yearParam ?: date('Y'));
        $startParam = $hasFilter ? ($request->query->get('start_date') ?: null) : ($saved['start_date'] ?? null);
        $endParam   = $hasFilter ? ($request->query->get('end_date') ?: null) : ($saved['end_date'] ?? null);

        $startDate = $startParam ? new DateTime($startParam) : new DateTime($year.'-01-01');
        $endDate   = $endParam ? new DateTime($endParam) : new DateTime($year.'-12-31');

        // Filtres additionnels
        $filterProjectType = $hasFilter ? ($request->query->get('project_type') ?: null) : ($saved['project_type'] ?? null);
        $filterStatus      = $hasFilter ? ($request->query->get('status') ?: null) : ($saved['status'] ?? 'active');
        $filterTechnology  = $hasFilter ? ($request->query->get('technology') ? (int) $request->query->get('technology') : null) : (isset($saved['technology']) ? (int) $saved['technology'] : null);

        // Tri
        $sort = $hasFilter ? ($request->query->get('sort') ?: ($saved['sort'] ?? 'name')) : ($saved['sort'] ?? 'name');
        $dir  = $hasFilter ? ($request->query->get('dir') ?: ($saved['dir'] ?? 'ASC')) : ($saved['dir'] ?? 'ASC');

        // Pagination
        $allowedPerPage = [10, 20, 50, 100];
        $perPageParam   = (int) ($hasFilter ? ($request->query->get('per_page', 10)) : ($saved['per_page'] ?? 10));
        $perPage        = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 10;
        $page           = max(1, (int) $request->query->get('page', 1));
        $offset         = ($page - 1) * $perPage;

        // Total
        $total = $projectRepo->countBetweenDatesFiltered($startDate, $endDate, $filterStatus, $filterProjectType, $filterTechnology);

        // Projets sur la période avec filtres (paginés)
        $projects = $projectRepo->findBetweenDatesFiltered($startDate, $endDate, $filterStatus, $filterProjectType, $filterTechnology, $sort, $dir, $perPage, $offset);

        // Agrégats SQL en batch pour éviter le parcours objet
        $projectIds = array_map(fn ($p) => $p->getId(), $projects);
        $aggregates = $projectRepo->getAggregatedMetricsFor($projectIds);

        // Construire la structure attendue par la vue
        $projectsWithMetrics = [];
        foreach ($projects as $project) {
            $pid = $project->getId();
            $agg = $aggregates[$pid] ?? [
                'total_revenue'       => '0',
                'total_margin'        => '0',
                'total_purchases'     => '0',
                'orders_count'        => 0,
                'signed_orders_count' => 0,
            ];

            // Ajouter l'achat projet (purchasesAmount)
            $projectPurchases = $project->getPurchasesAmount() ?? '0';
            $totalPurchases   = bcadd($agg['total_purchases'], $projectPurchases, 2);

            // Taux de marge
            $marginRate = '0';
            if (bccomp($agg['total_revenue'], '0', 2) > 0) {
                $marginRate = bcmul(bcdiv($agg['total_margin'], $agg['total_revenue'], 4), '100', 2);
            }

            $projectsWithMetrics[] = [
                'project' => $project,
                'metrics' => [
                    'total_revenue'       => $agg['total_revenue'],
                    'total_margin'        => $agg['total_margin'],
                    'margin_rate'         => $marginRate,
                    'total_purchases'     => $totalPurchases,
                    'orders_count'        => $agg['orders_count'],
                    'signed_orders_count' => $agg['signed_orders_count'],
                ],
            ];
        }

        // KPIs période (réel basé sur timesheets) - doivent refléter TOUT l'ensemble filtré (pas seulement la page)
        $allProjectsForKpis = $projectRepo->findBetweenDatesFiltered($startDate, $endDate, $filterStatus, $filterProjectType, $filterTechnology, null, null);
        $periodKpis         = $profitabilityService->calculatePeriodMetricsForProjects($allProjectsForKpis, $startDate, $endDate);

        $pagination = [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'total_pages'  => (int) ceil($total / $perPage),
            'has_prev'     => $page > 1,
            'has_next'     => $page * $perPage < $total,
        ];

        // Options de filtres
        $filterOptions = [
            'project_types' => $projectRepo->getDistinctProjectTypes(),
            'statuses'      => $projectRepo->getDistinctStatuses(),
            'technologies'  => $em->getRepository(Technology::class)->findBy(['active' => true], ['name' => 'ASC']),
        ];

        // Sauvegarder les filtres en session
        if ($session) {
            $session->set('project_filters', [
                'year'         => $year,
                'start_date'   => $startDate->format('Y-m-d'),
                'end_date'     => $endDate->format('Y-m-d'),
                'project_type' => $filterProjectType,
                'status'       => $filterStatus,
                'technology'   => $filterTechnology,
                'per_page'     => $perPage,
                'sort'         => $sort,
                'dir'          => strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC',
            ]);
        }

        return $this->render('project/index.html.twig', [
            'projects_with_metrics' => $projectsWithMetrics,
            'filters'               => [
                'year'         => $year,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
                'project_type' => $filterProjectType,
                'status'       => $filterStatus,
                'technology'   => $filterTechnology,
            ],
            'filter_options' => $filterOptions,
            'period_kpis'    => $periodKpis,
            'pagination'     => $pagination,
            // Filtres pour URL (types simples)
            'filters_query' => [
                'year'         => $year,
                'start_date'   => $startDate->format('Y-m-d'),
                'end_date'     => $endDate->format('Y-m-d'),
                'project_type' => $filterProjectType,
                'status'       => $filterStatus,
                'technology'   => $filterTechnology,
                'per_page'     => $perPage,
                'sort'         => $sort,
                'dir'          => $dir,
            ],
            'sort' => $sort,
            'dir'  => strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC',
        ]);
    }

    #[Route('/new', name: 'project_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $project = new Project();
        $form    = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'project_show', methods: ['GET'])]
    public function show(Request $request, int $id, EntityManagerInterface $em): Response
    {
        $project = $em->getRepository(Project::class)->findOneWithRelations($id);

        if (!$project) {
            throw $this->createNotFoundException('Projet non trouvé');
        }

        // Calculer les métriques des devis
        $projectMetrics = $this->calculateProjectMetrics($project);

        // Devis du projet: tri + pagination
        $orderRepo      = $em->getRepository(\App\Entity\Order::class);
        $allowedPerPage = [5, 10, 20, 50];
        $oSort          = (string) $request->query->get('o_sort', 'createdAt');
        $oDir           = (string) $request->query->get('o_dir', 'DESC');
        $oPerPage       = (int) $request->query->get('o_per_page', 10);
        $oPerPage       = in_array($oPerPage, $allowedPerPage, true) ? $oPerPage : 10;
        $oPage          = max(1, (int) $request->query->get('o_page', 1));
        $oOffset        = ($oPage - 1) * $oPerPage;

        $orders      = $orderRepo->findWithFilters($project, null, $oSort, $oDir, $oPerPage, $oOffset);
        $oTotal      = $orderRepo->countWithFilters($project, null);
        $oPagination = [
            'current_page' => $oPage,
            'per_page'     => $oPerPage,
            'total'        => $oTotal,
            'total_pages'  => (int) ceil($oTotal / $oPerPage),
            'has_prev'     => $oPage > 1,
            'has_next'     => $oPage * $oPerPage < $oTotal,
        ];

        return $this->render('project/show.html.twig', [
            'project'           => $project,
            'metrics'           => $projectMetrics,
            'orders'            => $orders,
            'orders_pagination' => $oPagination,
            'orders_sort'       => $oSort,
            'orders_dir'        => strtoupper($oDir) === 'ASC' ? 'ASC' : 'DESC',
            'orders_per_page'   => $oPerPage,
        ]);
    }

    #[Route('/{id}/edit', name: 'project_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function edit(Request $request, Project $project, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Projet modifié avec succès');

            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form'    => $form,
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
     * Calcule les métriques financières du projet à partir de ses devis.
     */
    private function calculateProjectMetrics(Project $project): array
    {
        $totalRevenue   = '0';
        $totalDays      = '0';
        $totalMargin    = '0';
        $totalCost      = '0';
        $totalPurchases = $project->getPurchasesAmount() ?? '0';
        $ordersByStatus = [];
        $ordersCount    = 0;

        foreach ($project->getOrders() as $order) {
            ++$ordersCount;

            // Compter par statut
            $status = $order->getStatus();
            if (!isset($ordersByStatus[$status])) {
                $ordersByStatus[$status] = 0;
            }
            ++$ordersByStatus[$status];

            // Ne compter dans les totaux que les devis signés/gagnés/terminés
            if (in_array($status, ['signed', 'won', 'completed', 'signe', 'gagne', 'termine'])) {
                // CA du devis (calculé depuis les sections)
                $orderTotal   = $order->calculateTotalFromSections();
                $totalRevenue = bcadd($totalRevenue, $orderTotal, 2);

                // Compter les jours et calculer les marges par section
                foreach ($order->getSections() as $section) {
                    foreach ($section->getLines() as $line) {
                        // Jours vendus (seulement les lignes de service)
                        if ($line->getProfile() && $line->getDays()) {
                            $totalDays = bcadd($totalDays, $line->getDays(), 2);

                            // Marge et coût estimé
                            $totalMargin = bcadd($totalMargin, $line->getGrossMargin(), 2);
                            $totalCost   = bcadd($totalCost, $line->getEstimatedCost(), 2);
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
            'total_revenue'       => $totalRevenue,
            'total_days'          => $totalDays,
            'total_margin'        => $totalMargin,
            'total_cost'          => $totalCost,
            'total_purchases'     => $totalPurchases,
            'margin_rate'         => $marginRate,
            'orders_count'        => $ordersCount,
            'orders_by_status'    => $ordersByStatus,
            'signed_orders_count' => array_sum(array_intersect_key($ordersByStatus, array_flip(['signed', 'won', 'completed', 'signe', 'gagne', 'termine']))),
        ];
    }

    #[Route('/export.csv', name: 'project_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, EntityManagerInterface $em, \App\Service\ProfitabilityService $profitabilityService): Response
    {
        $projectRepo = $em->getRepository(Project::class);
        $session     = $request->getSession();
        $saved       = ($session && $session->has('project_filters')) ? (array) $session->get('project_filters') : [];

        // Lire filtres depuis requête ou session
        $year       = (int) $request->query->get('year', $saved['year'] ?? date('Y'));
        $startParam = $request->query->get('start_date', $saved['start_date'] ?? null);
        $endParam   = $request->query->get('end_date', $saved['end_date'] ?? null);
        $startDate  = $startParam ? new DateTime($startParam) : new DateTime($year.'-01-01');
        $endDate    = $endParam ? new DateTime($endParam) : new DateTime($year.'-12-31');

        $filterProjectType = $request->query->get('project_type', $saved['project_type'] ?? null) ?: null;
        $filterStatus      = $request->query->get('status', $saved['status'] ?? null) ?: null;
        $filterTechnology  = $request->query->getInt('technology', $saved['technology'] ?? 0) ?: null;
        $sort              = (string) $request->query->get('sort', $saved['sort'] ?? 'name');
        $dir               = (string) $request->query->get('dir', $saved['dir'] ?? 'ASC');

        $projects = $projectRepo->findBetweenDatesFiltered($startDate, $endDate, $filterStatus, $filterProjectType, $filterTechnology, $sort, $dir, null, null);

        // CSV headers
        $rows   = [];
        $header = ['Nom', 'Client', 'Type', 'Statut', 'Catégorie', 'Technologies'];
        $rows[] = $header;

        foreach ($projects as $p) {
            $techs = [];
            foreach ($p->getTechnologies() as $t) {
                $techs[] = $t->getName();
            }
            $rows[] = [
                $p->getName(),
                $p->getClient() ? $p->getClient()->getName() : '',
                $p->getProjectType() ?: '',
                $p->getStatus() ?: '',
                $p->getServiceCategory() ? $p->getServiceCategory()->getName() : '',
                implode('|', $techs),
            ];
        }

        // Génération CSV sécurisée
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $r) {
            fputcsv($handle, $r);
        }
        rewind($handle);
        $csv = "\xEF\xBB\xBF".stream_get_contents($handle);

        $filename = sprintf('projets_%s.csv', date('Y-m-d'));
        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
