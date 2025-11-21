<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderPaymentSchedule;
use App\Entity\OrderSection;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Enum\OrderStatus;
use App\Event\QuoteStatusChangedEvent;
use App\Form\OrderType as OrderFormType;

use function array_key_exists;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
#[IsGranted('ROLE_CHEF_PROJET')]
class OrderController extends AbstractController
{
    #[Route('', name: 'order_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);
        if ($reset && $session) {
            $session->remove('order_filters');

            return $this->redirectToRoute('order_index');
        }

        $queryAll   = $request->query->all();
        $filterKeys = ['project', 'status'];
        $hasFilter  = count(array_intersect(array_keys($queryAll), $filterKeys)) > 0;
        $saved      = ($session && $session->has('order_filters')) ? (array) $session->get('order_filters') : [];

        $projectId = $hasFilter ? ($request->query->get('project') ?? null) : ($saved['project'] ?? null);
        $status    = $hasFilter ? ($request->query->get('status') ?? null) : ($saved['status'] ?? null);

        $project = $projectId ? $em->getRepository(Project::class)->find($projectId) : null;
        // Tri
        $sort = $hasFilter ? ($request->query->get('sort') ?? ($saved['sort'] ?? 'createdAt')) : ($saved['sort'] ?? 'createdAt');
        $dir  = $hasFilter ? ($request->query->get('dir') ?? ($saved['dir'] ?? 'DESC')) : ($saved['dir'] ?? 'DESC');

        // Pagination
        $allowedPerPage = [10, 20, 50, 100];
        $perPageParam   = (int) $request->query->get('per_page', $saved['per_page'] ?? 20);
        $perPage        = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 20;
        $page           = max(1, (int) $request->query->get('page', 1));
        $offset         = ($page - 1) * $perPage;

        $orders   = $em->getRepository(Order::class)->findWithFilters($project, $status, $sort, $dir, $perPage, $offset);
        $total    = $em->getRepository(Order::class)->countWithFilters($project, $status);
        $projects = $em->getRepository(Project::class)->findBy([], ['name' => 'ASC']);

        $pagination = [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'total_pages'  => (int) ceil($total / $perPage),
            'has_prev'     => $page > 1,
            'has_next'     => $page * $perPage < $total,
        ];

        if ($session) {
            $session->set('order_filters', [
                'project'  => $projectId,
                'status'   => $status,
                'sort'     => $sort,
                'dir'      => strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC',
                'per_page' => $perPage,
            ]);
        }

        return $this->render('order/index.html.twig', [
            'orders'          => $orders,
            'projects'        => $projects,
            'selectedProject' => $projectId,
            'selectedStatus'  => $status,
            'statusOptions'   => Order::STATUS_OPTIONS,
            'filters_query'   => ['project' => $projectId, 'status' => $status, 'sort' => $sort, 'dir' => $dir, 'per_page' => $perPage],
            'sort'            => $sort,
            'dir'             => strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC',
            'pagination'      => $pagination,
        ]);
    }

    #[Route('/new', name: 'order_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $order = new Order();

        // Pré-sélectionner le projet si fourni dans l'URL
        if ($projectId = $request->query->get('project')) {
            $project = $em->getRepository(Project::class)->find($projectId);
            if ($project) {
                $order->setProject($project);
            }
        }

        $form = $this->createForm(OrderFormType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setOrderNumber($this->generateOrderNumber($em));

            $em->persist($order);
            $em->flush();

            $this->addFlash('success', 'Devis créé avec succès');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $order = $em->getRepository(Order::class)->findOneWithRelations($id);

        if (!$order) {
            throw $this->createNotFoundException('Devis non trouvé');
        }

        // Total basé sur les sections (incluant achats et montants fixes)
        $sectionsTotal = (float) $order->calculateTotalFromSections();
        if ($sectionsTotal <= 0 && $order->getTotalAmount()) {
            // Fallback sur la colonne totalAmount si sections non définies
            $sectionsTotal = (float) $order->getTotalAmount();
        }

        // Détail prestations vs achats
        $servicesSubtotal  = 0.0; // Prestations hors achats
        $purchasesSubtotal = 0.0; // Achats attachés + lignes d'achat/direct/fixe
        foreach ($order->getSections() as $section) {
            foreach ($section->getLines() as $line) {
                // Prestations (services uniquement), sans achats attachés
                $servicesSubtotal += (float) $line->getServiceAmount();

                // Achats attachés à une ligne de service
                if ($line->getPurchaseAmount()) {
                    $purchasesSubtotal += (float) $line->getPurchaseAmount();
                }

                // Lignes d'achat direct ou montant fixe
                if (in_array($line->getType(), ['purchase', 'fixed_amount'], true) && $line->getDirectAmount()) {
                    $purchasesSubtotal += (float) $line->getDirectAmount();
                }
            }
        }

        // Contingence
        $contPct           = $order->getContingencyPercentage() ? (float) $order->getContingencyPercentage() : 0.0;
        $contingencyAmount = $sectionsTotal * ($contPct / 100.0);
        $finalTotal        = $sectionsTotal - $contingencyAmount;

        // Couverture de l'échéancier (si forfait)
        $scheduledTotal = 0.0;
        if ($order->getContractType() === 'forfait') {
            foreach ($order->getPaymentSchedules() as $s) {
                $scheduledTotal += (float) $s->computeAmount(number_format($finalTotal, 2, '.', ''));
            }
        }

        return $this->render('order/show.html.twig', [
            'order'             => $order,
            'sectionsTotal'     => $sectionsTotal,
            'servicesSubtotal'  => $servicesSubtotal,
            'purchasesSubtotal' => $purchasesSubtotal,
            'contingencyAmount' => $contingencyAmount,
            'finalAmount'       => $finalTotal,
            'scheduledTotal'    => $scheduledTotal,
            'statusOptions'     => Order::STATUS_OPTIONS,
        ]);
    }

    #[Route('/{id}/edit', name: 'order_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function edit(Request $request, int $id, EntityManagerInterface $em): Response
    {
        $order = $em->getRepository(Order::class)->findOneWithRelations($id);

        if (!$order) {
            throw $this->createNotFoundException('Devis non trouvé');
        }

        $form = $this->createForm(OrderFormType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Devis modifié avec succès');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}/sections', name: 'order_sections', methods: ['GET'])]
    public function sections(Order $order, EntityManagerInterface $em): Response
    {
        $profiles = $em->getRepository(Profile::class)->findBy(
            ['active' => true],
            ['name' => 'ASC'],
        );

        return $this->render('order/sections.html.twig', [
            'order'    => $order,
            'profiles' => $profiles,
        ]);
    }

    #[Route('/{id}/add-section', name: 'order_add_section', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function addSection(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        $section = new OrderSection();
        $section->setOrder($order);
        $section->setName($request->request->get('section_name'));
        $section->setDescription($request->request->get('section_description'));
        $section->setSortOrder($order->getSections()->count() + 1);

        $em->persist($section);
        $em->flush();

        $this->addFlash('success', 'Section ajoutée avec succès');

        return $this->redirectToRoute('order_sections', ['id' => $order->getId()]);
    }

    #[Route('/{id}/generate-tasks', name: 'order_generate_tasks', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function generateTasks(Request $request, int $id, EntityManagerInterface $em): Response
    {
        $order = $em->getRepository(Order::class)->findOneWithRelations($id);

        if (!$order) {
            throw $this->createNotFoundException('Devis non trouvé');
        }

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('generate_tasks_'.$order->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $project = $order->getProject();
        if (!$project) {
            $this->addFlash('error', 'Le devis n\'est attaché à aucun projet');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        // Parcourir toutes les sections et lignes pour générer les tâches
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($order->getSections() as $section) {
            foreach ($section->getLines() as $line) {
                // Vérifier si une tâche existe déjà pour cette ligne
                $existingTask = $em->getRepository(ProjectTask::class)->findOneBy([
                    'orderLine' => $line,
                ]);

                if ($existingTask) {
                    ++$skippedCount;
                    continue;
                }

                // Tenter de créer une tâche depuis la ligne
                $task = $line->createProjectTask($project);

                if ($task) {
                    $em->persist($task);
                    ++$createdCount;
                } else {
                    // Ligne non éligible (type purchase ou fixed_amount)
                    ++$skippedCount;
                }
            }
        }

        if ($createdCount > 0) {
            $em->flush();
        }

        // Message de feedback
        if ($createdCount > 0) {
            $this->addFlash('success', sprintf(
                '%d tâche(s) créée(s) avec succès. %d ligne(s) ignorée(s) (déjà liées ou non éligibles).',
                $createdCount,
                $skippedCount,
            ));
        } else {
            $this->addFlash('info', 'Aucune tâche créée. Toutes les lignes sont déjà liées à des tâches ou ne sont pas éligibles.');
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/{orderId}/section/{sectionId}/add-line', name: 'order_add_line', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function addLine(Request $request, int $orderId, int $sectionId, EntityManagerInterface $em): Response
    {
        $order   = $em->getRepository(Order::class)->find($orderId);
        $section = $em->getRepository(OrderSection::class)->find($sectionId);

        if (!$order || !$section || $section->getOrder() !== $order) {
            throw $this->createNotFoundException();
        }

        $line = new OrderLine();
        $line->setSection($section);
        $line->setDescription($request->request->get('line_description'));

        if ($profileId = $request->request->get('profile_id')) {
            $profile = $em->getRepository(Profile::class)->find($profileId);
            if ($profile) {
                $line->setProfile($profile);
            }
        }

        $tjm = $request->request->get('tjm');
        $line->setTjm($tjm !== '' ? (string) $tjm : null);

        $days = $request->request->get('days');
        $line->setDays($days !== '' ? (string) $days : '0');

        $purchaseAmount = $request->request->get('purchase_amount');
        $line->setPurchaseAmount($purchaseAmount !== '' ? (string) $purchaseAmount : null);

        $line->setSortOrder($section->getLines()->count() + 1);

        $em->persist($line);

        // Recalculer le total du devis
        $this->updateOrderTotals($order, $em);

        $em->flush();

        // Ajouter des informations sur la marge dans le message flash
        $marginInfo = '';
        if ($line->getProfile() && $line->getDays()) {
            $margin     = $line->getGrossMargin();
            $marginRate = $line->getMarginRate();
            $marginInfo = sprintf(' (Marge: %s€ - %s%%)',
                number_format(floatval($margin), 0, ',', ' '),
                number_format(floatval($marginRate), 1, ',', ' '),
            );
        }

        $this->addFlash('success', 'Ligne ajoutée avec succès'.$marginInfo);

        return $this->redirectToRoute('order_sections', ['id' => $orderId]);
    }

    #[Route('/{orderId}/section/{sectionId}/line/{lineId}/edit', name: 'order_edit_line', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function editLine(Request $request, int $orderId, int $sectionId, int $lineId, EntityManagerInterface $em): Response
    {
        $order   = $em->getRepository(Order::class)->find($orderId);
        $section = $em->getRepository(OrderSection::class)->find($sectionId);
        $line    = $em->getRepository(OrderLine::class)->find($lineId);

        if (!$order || !$section || !$line || $section->getOrder() !== $order || $line->getSection() !== $section) {
            throw $this->createNotFoundException();
        }

        $line->setDescription($request->request->get('line_description'));

        if ($profileId = $request->request->get('profile_id')) {
            $profile = $em->getRepository(Profile::class)->find($profileId);
            if ($profile) {
                $line->setProfile($profile);
            }
        } else {
            $line->setProfile(null);
        }

        $tjm = $request->request->get('tjm');
        $line->setTjm($tjm !== '' ? (string) $tjm : null);

        $days = $request->request->get('days');
        $line->setDays($days !== '' ? (string) $days : '0');

        $purchaseAmount = $request->request->get('purchase_amount');
        $line->setPurchaseAmount($purchaseAmount !== '' ? (string) $purchaseAmount : null);

        // Recalculer le total du devis
        $this->updateOrderTotals($order, $em);

        $em->flush();

        $this->addFlash('success', 'Ligne modifiée avec succès');

        return $this->redirectToRoute('order_sections', ['id' => $orderId]);
    }

    #[Route('/{orderId}/section/{sectionId}/line/{lineId}/delete', name: 'order_delete_line', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function deleteLine(Request $request, int $orderId, int $sectionId, int $lineId, EntityManagerInterface $em): Response
    {
        $order   = $em->getRepository(Order::class)->find($orderId);
        $section = $em->getRepository(OrderSection::class)->find($sectionId);
        $line    = $em->getRepository(OrderLine::class)->find($lineId);

        if (!$order || !$section || !$line || $section->getOrder() !== $order || $line->getSection() !== $section) {
            throw $this->createNotFoundException();
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete_line_'.$lineId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('order_sections', ['id' => $orderId]);
        }

        $em->remove($line);

        // Recalculer le total du devis
        $this->updateOrderTotals($order, $em);

        $em->flush();

        $this->addFlash('success', 'Ligne supprimée avec succès');

        return $this->redirectToRoute('order_sections', ['id' => $orderId]);
    }

    #[Route('/{id}/duplicate', name: 'order_duplicate', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function duplicate(Order $originalOrder, EntityManagerInterface $em): Response
    {
        $newOrder = new Order();
        $newOrder->setOrderNumber($this->generateOrderNumber($em));
        $newOrder->setProject($originalOrder->getProject());
        $newOrder->setStatus('draft');
        $newOrder->setDescription($originalOrder->getDescription().' (Copie)');
        $newOrder->setNotes($originalOrder->getNotes());
        $newOrder->setContingencyPercentage($originalOrder->getContingencyPercentage());

        $em->persist($newOrder);

        // Copier les sections et lignes
        foreach ($originalOrder->getSections() as $originalSection) {
            $newSection = new OrderSection();
            $newSection->setOrder($newOrder);
            $newSection->setName($originalSection->getName());
            $newSection->setDescription($originalSection->getDescription());
            $newSection->setSortOrder($originalSection->getSortOrder());

            $em->persist($newSection);

            foreach ($originalSection->getLines() as $originalLine) {
                $newLine = new OrderLine();
                $newLine->setSection($newSection);
                $newLine->setDescription($originalLine->getDescription());
                $newLine->setProfile($originalLine->getProfile());
                $newLine->setTjm($originalLine->getTjm());
                $newLine->setDays($originalLine->getDays());
                $newLine->setPurchaseAmount($originalLine->getPurchaseAmount());
                $newLine->setSortOrder($originalLine->getSortOrder());

                $em->persist($newLine);
            }
        }

        $em->flush();

        $this->addFlash('success', 'Devis dupliqué avec succès');

        return $this->redirectToRoute('order_show', ['id' => $newOrder->getId()]);
    }

    #[Route('/{id}/delete', name: 'order_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $em->remove($order);
            $em->flush();
            $this->addFlash('success', 'Devis supprimé avec succès');
        }

        return $this->redirectToRoute('order_index');
    }

    #[Route('/{id}/status', name: 'order_update_status', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function updateStatus(
        Request $request,
        Order $order,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        if (!$this->isCsrfTokenValid('status'.$order->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action non autorisée (CSRF).');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $statusString = (string) $request->request->get('status');
        if (!array_key_exists($statusString, Order::STATUS_OPTIONS)) {
            $this->addFlash('danger', 'Statut invalide.');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $oldStatus = $order->getStatus();
        $order->setStatus($statusString);
        $em->flush();

        // Dispatcher l'événement de notification si le statut a changé
        $newStatus = OrderStatus::fromString($statusString);
        if ($oldStatus !== $statusString && $newStatus && in_array($newStatus, [OrderStatus::WON, OrderStatus::LOST, OrderStatus::PENDING], true)) {
            // Déterminer les destinataires : chef de projet, commercial, KAM
            $recipients = [];
            if ($project = $order->getProject()) {
                if ($pm = $project->getProjectManager()) {
                    $recipients[] = $pm;
                }
                if ($kam = $project->getKam()) {
                    $recipients[] = $kam;
                }
                if ($sales = $project->getSalesPerson()) {
                    $recipients[] = $sales;
                }
            }

            // Retirer les doublons
            $recipients = array_unique($recipients, SORT_REGULAR);

            if (!empty($recipients)) {
                $event = new QuoteStatusChangedEvent($order, $newStatus, $recipients);
                $eventDispatcher->dispatch($event);
            }
        }

        $this->addFlash('success', 'Statut du devis mis à jour.');

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/api/profile/{id}', name: 'api_profile_info', methods: ['GET'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function getProfileInfo(Profile $profile): Response
    {
        return $this->json([
            'id'               => $profile->getId(),
            'name'             => $profile->getName(),
            'defaultDailyRate' => $profile->getDefaultDailyRate(),
            'color'            => $profile->getColor(),
        ]);
    }

    private function generateOrderNumber(EntityManagerInterface $em): string
    {
        $year  = date('Y');
        $month = date('m');

        // Trouver le dernier numéro de devis pour ce mois
        $lastOrder = $em->getRepository(Order::class)
            ->findLastOrderNumberForMonth($year, $month);

        $increment = 1;
        if ($lastOrder) {
            $lastNumber = $lastOrder->getOrderNumber();
            $increment  = (int) substr($lastNumber, -3) + 1;
        }

        return sprintf('D%s%s%03d', $year, $month, $increment);
    }

    /**
     * Met à jour automatiquement le montant total du devis.
     */
    private function updateOrderTotals(Order $order, EntityManagerInterface $em): void
    {
        $totalAmount = '0';

        foreach ($order->getSections() as $section) {
            $totalAmount = bcadd($totalAmount, $section->getTotalAmount(), 2);
        }

        $order->setTotalAmount($totalAmount);
    }

    #[Route('/{id}/schedule/add', name: 'order_schedule_add', methods: ['POST'])]
    public function addSchedule(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('schedule_add_'.$order->getId(), $request->request->get('_token'))) {
            return $this->json(['error' => 'Token CSRF invalide'], 422);
        }

        if ($order->getContractType() !== 'forfait') {
            return $this->json(['error' => 'L\'\u00e9ch\u00e9ancier n\'est disponible que pour les contrats au forfait'], 422);
        }

        $label       = (string) $request->request->get('label');
        $billingDate = (string) $request->request->get('billing_date');
        $amountType  = (string) $request->request->get('amount_type', 'percent');
        $percent     = $request->request->get('percent');
        $fixed       = $request->request->get('fixed_amount');

        if (!$billingDate) {
            $this->addFlash('danger', 'La date de facturation est requise.');

            return $this->redirectToRoute('order_edit', ['id' => $order->getId()]);
        }

        $schedule = new OrderPaymentSchedule();
        $schedule->setOrder($order)
            ->setLabel($label ?: null)
            ->setBillingDate(new DateTime($billingDate))
            ->setAmountType($amountType);

        if ($amountType === OrderPaymentSchedule::TYPE_PERCENT) {
            $schedule->setPercent($percent !== '' ? (string) $percent : '0');
        } else {
            $schedule->setFixedAmount($fixed !== '' ? (string) $fixed : '0');
        }

        $em->persist($schedule);
        $em->flush();

        // Vérifier la couverture
        [$ok, $scheduled] = $order->validatePaymentScheduleCoverage();
        if ($ok) {
            $this->addFlash('success', 'Échéance ajoutée. Couverture à 100% du devis.');
        } else {
            $this->addFlash('warning', sprintf('Échéance ajoutée. Couverture actuelle: %s€ (doit couvrir 100%% du devis).', number_format((float) $scheduled, 2, ',', ' ')));
        }

        return $this->redirectToRoute('order_edit', ['id' => $order->getId()]);
    }

    #[Route('/{orderId}/schedule/{scheduleId}/delete', name: 'order_schedule_delete', methods: ['POST'])]
    public function deleteSchedule(int $orderId, int $scheduleId, EntityManagerInterface $em): Response
    {
        $order    = $em->getRepository(Order::class)->find($orderId);
        $schedule = $em->getRepository(OrderPaymentSchedule::class)->find($scheduleId);
        if (!$order || !$schedule || $schedule->getOrder()->getId() !== $order->getId()) {
            throw $this->createNotFoundException();
        }

        $em->remove($schedule);
        $em->flush();

        [$ok, $scheduled] = $order->validatePaymentScheduleCoverage();
        if ($ok) {
            $this->addFlash('success', 'Échéance supprimée. Couverture à 100% du devis.');
        } else {
            $this->addFlash('warning', sprintf('Échéance supprimée. Couverture actuelle: %s€ (doit couvrir 100%% du devis).', number_format((float) $scheduled, 2, ',', ' ')));
        }

        return $this->redirectToRoute('order_edit', ['id' => $order->getId()]);
    }

    #[Route('/export.csv', name: 'order_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, EntityManagerInterface $em): Response
    {
        $session   = $request->getSession();
        $saved     = ($session && $session->has('order_filters')) ? (array) $session->get('order_filters') : [];
        $projectId = $request->query->get('project', $saved['project'] ?? null);
        $status    = $request->query->get('status', $saved['status'] ?? null);
        $sort      = $request->query->get('sort', $saved['sort'] ?? 'createdAt');
        $dir       = $request->query->get('dir', $saved['dir'] ?? 'DESC');

        $project = $projectId ? $em->getRepository(Project::class)->find($projectId) : null;
        $orders  = $em->getRepository(Order::class)->findWithFilters($project, $status, $sort, $dir, null, null);

        $rows   = [];
        $header = ['Numéro', 'Nom', 'Projet', 'Statut', 'Date création', 'Montant total HT'];
        $rows[] = $header;
        foreach ($orders as $o) {
            $total = (float) $o->calculateTotalFromSections();
            if ($total <= 0 && $o->getTotalAmount()) {
                $total = (float) $o->getTotalAmount();
            }
            $statusLabel = Order::STATUS_OPTIONS[$o->getStatus()] ?? $o->getStatus();
            $rows[]      = [
                $o->getOrderNumber(),
                $o->getName() ?: '',
                $o->getProject() ? $o->getProject()->getName() : '',
                $statusLabel,
                $o->getCreatedAt() ? $o->getCreatedAt()->format('Y-m-d') : '',
                number_format($total, 2, '.', ''),
            ];
        }

        // Génération CSV sécurisée
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $r) {
            fputcsv($handle, $r);
        }
        rewind($handle);
        $csv = "\xEF\xBB\xBF".stream_get_contents($handle);

        $filename = sprintf('devis_%s.csv', date('Y-m-d'));
        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
