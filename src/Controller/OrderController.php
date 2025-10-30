<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderSection;
use App\Entity\Profile;
use App\Entity\Project;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $projectId = $request->query->get('project');
        $status    = $request->query->get('status');

        $project  = $projectId ? $em->getRepository(Project::class)->find($projectId) : null;
        $orders   = $em->getRepository(Order::class)->findWithFilters($project, $status);
        $projects = $em->getRepository(Project::class)->findBy([], ['name' => 'ASC']);

        return $this->render('order/index.html.twig', [
            'orders'          => $orders,
            'projects'        => $projects,
            'selectedProject' => $projectId,
            'selectedStatus'  => $status,
            'statusOptions'   => Order::STATUS_OPTIONS,
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

        if ($request->isMethod('POST')) {
            $order->setOrderNumber($this->generateOrderNumber($em));

            // Projet
            if ($projectId = $request->request->get('project_id')) {
                $project = $em->getRepository(Project::class)->find($projectId);
                if ($project) {
                    $order->setProject($project);
                }
            }

            $order->setStatus($request->request->get('status', 'draft'));
            $order->setDescription($request->request->get('description'));
            $order->setNotes($request->request->get('notes'));

            // Contingence
            $contingency = $request->request->get('contingency_percentage');
            $order->setContingencyPercentage($contingency !== '' ? (string) $contingency : null);

            if ($request->request->get('valid_until')) {
                $order->setValidUntil(new DateTime($request->request->get('valid_until')));
            }

            $em->persist($order);
            $em->flush();

            $this->addFlash('success', 'Devis créé avec succès');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $projects = $em->getRepository(Project::class)->findBy([], ['name' => 'ASC']);

        return $this->render('order/new.html.twig', [
            'order'         => $order,
            'projects'      => $projects,
            'statusOptions' => Order::STATUS_OPTIONS,
        ]);
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(Order $order, EntityManagerInterface $em): Response
    {
        // Calculer les totaux
        $totalAmount    = 0;
        $totalPurchases = 0;

        foreach ($order->getSections() as $section) {
            foreach ($section->getLines() as $line) {
                $totalAmount += $line->getTotalAmount();
                if ($line->getPurchaseAmount()) {
                    $totalPurchases += $line->getPurchaseAmount();
                }
            }
        }

        // Calcul avec contingence
        $contingencyAmount = 0;
        if ($order->getContingencyPercentage()) {
            $contingencyAmount = $totalAmount * ($order->getContingencyPercentage() / 100);
        }

        $finalAmount = $totalAmount - $contingencyAmount;

        return $this->render('order/show.html.twig', [
            'order'             => $order,
            'totalAmount'       => $totalAmount,
            'totalPurchases'    => $totalPurchases,
            'contingencyAmount' => $contingencyAmount,
            'finalAmount'       => $finalAmount,
        ]);
    }

    #[Route('/{id}/edit', name: 'order_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function edit(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            // Projet
            if ($projectId = $request->request->get('project_id')) {
                $project = $em->getRepository(Project::class)->find($projectId);
                $order->setProject($project);
            }

            $order->setStatus($request->request->get('status'));
            $order->setDescription($request->request->get('description'));
            $order->setNotes($request->request->get('notes'));

            // Contingence
            $contingency = $request->request->get('contingency_percentage');
            $order->setContingencyPercentage($contingency !== '' ? (string) $contingency : null);

            if ($request->request->get('valid_until')) {
                $order->setValidUntil(new DateTime($request->request->get('valid_until')));
            } else {
                $order->setValidUntil(null);
            }

            $em->flush();

            $this->addFlash('success', 'Devis modifié avec succès');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $projects = $em->getRepository(Project::class)->findBy([], ['name' => 'ASC']);

        return $this->render('order/edit.html.twig', [
            'order'         => $order,
            'projects'      => $projects,
            'statusOptions' => Order::STATUS_OPTIONS,
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
}
