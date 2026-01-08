<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\LeadCapture;
use App\Repository\LeadCaptureRepository;

use function count;

use Doctrine\ORM\EntityManagerInterface;

use function in_array;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller pour le CRM HotOnes (gestion des leads capturés via lead magnet).
 *
 * Cette section est dédiée à la gestion des leads HotOnes lui-même,
 * à ne pas confondre avec les clients de l'agence qui utilise HotOnes.
 */
#[Route('/admin/crm')]
#[IsGranted('ROLE_ADMIN')]
class CrmLeadController extends AbstractController
{
    public function __construct(
        private readonly LeadCaptureRepository $leadCaptureRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Liste des leads capturés avec filtres et pagination.
     */
    #[Route('/leads', name: 'admin_crm_leads_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page    = max(1, $request->query->getInt('page', 1));
        $perPage = 50;

        // Filtres
        $status           = $request->query->get('status');
        $source           = $request->query->get('source');
        $marketingConsent = $request->query->get('marketing_consent');
        $hasDownloaded    = $request->query->get('has_downloaded');
        $search           = $request->query->get('search');

        // QueryBuilder avec filtres
        $qb = $this->leadCaptureRepository->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('l.status = :status')
                ->setParameter('status', $status);
        }

        if ($source) {
            $qb->andWhere('l.source = :source')
                ->setParameter('source', $source);
        }

        if ($marketingConsent !== null && $marketingConsent !== '') {
            $qb->andWhere('l.marketingConsent = :marketingConsent')
                ->setParameter('marketingConsent', (bool) $marketingConsent);
        }

        if ($hasDownloaded !== null && $hasDownloaded !== '') {
            if ($hasDownloaded) {
                $qb->andWhere('l.downloadedAt IS NOT NULL');
            } else {
                $qb->andWhere('l.downloadedAt IS NULL');
            }
        }

        if ($search) {
            $qb->andWhere('l.email LIKE :search OR l.firstName LIKE :search OR l.lastName LIKE :search OR l.company LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        // Pagination
        $query = $qb->getQuery();
        $total = count($query->getResult());
        $leads = $query
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getResult();

        $totalPages = (int) ceil($total / $perPage);

        return $this->render('admin/crm/leads/index.html.twig', [
            'leads'      => $leads,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
            'filters'    => [
                'status'            => $status,
                'source'            => $source,
                'marketing_consent' => $marketingConsent,
                'has_downloaded'    => $hasDownloaded,
                'search'            => $search,
            ],
        ]);
    }

    /**
     * Détail d'un lead avec timeline de communication.
     */
    #[Route('/leads/{id}', name: 'admin_crm_leads_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $lead = $this->leadCaptureRepository->find($id);

        if (!$lead) {
            throw $this->createNotFoundException('Lead non trouvé');
        }

        // Timeline d'événements
        $timeline = $this->buildTimeline($lead);

        return $this->render('admin/crm/leads/show.html.twig', [
            'lead'     => $lead,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Changer le statut d'un lead.
     */
    #[Route('/leads/{id}/status', name: 'admin_crm_leads_update_status', methods: ['POST'])]
    public function updateStatus(int $id, Request $request): Response
    {
        $lead = $this->leadCaptureRepository->find($id);

        if (!$lead) {
            throw $this->createNotFoundException('Lead non trouvé');
        }

        $newStatus = $request->request->get('status');

        if (!in_array($newStatus, [
            LeadCapture::STATUS_NEW,
            LeadCapture::STATUS_NURTURING,
            LeadCapture::STATUS_QUALIFIED,
            LeadCapture::STATUS_CONVERTED,
            LeadCapture::STATUS_LOST,
        ], true)) {
            $this->addFlash('error', 'Statut invalide');

            return $this->redirectToRoute('admin_crm_leads_show', ['id' => $id]);
        }

        $lead->setStatus($newStatus);
        $this->entityManager->flush();

        $this->addFlash('success', 'Statut mis à jour avec succès');

        return $this->redirectToRoute('admin_crm_leads_show', ['id' => $id]);
    }

    /**
     * Ajouter des notes internes à un lead.
     */
    #[Route('/leads/{id}/notes', name: 'admin_crm_leads_update_notes', methods: ['POST'])]
    public function updateNotes(int $id, Request $request): Response
    {
        $lead = $this->leadCaptureRepository->find($id);

        if (!$lead) {
            throw $this->createNotFoundException('Lead non trouvé');
        }

        $notes = $request->request->get('notes');
        $lead->setInternalNotes($notes);
        $this->entityManager->flush();

        $this->addFlash('success', 'Notes mises à jour avec succès');

        return $this->redirectToRoute('admin_crm_leads_show', ['id' => $id]);
    }

    /**
     * Export CSV des leads.
     */
    #[Route('/leads/export/csv', name: 'admin_crm_leads_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        // Récupérer tous les leads (avec filtres si fournis)
        $status = $request->query->get('status');
        $source = $request->query->get('source');

        $qb = $this->leadCaptureRepository->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('l.status = :status')
                ->setParameter('status', $status);
        }

        if ($source) {
            $qb->andWhere('l.source = :source')
                ->setParameter('source', $source);
        }

        $leads = $qb->getQuery()->getResult();

        // Créer le CSV
        $csv   = [];
        $csv[] = [
            'ID',
            'Prénom',
            'Nom',
            'Email',
            'Entreprise',
            'Téléphone',
            'Source',
            'Statut',
            'Consentement Marketing',
            'Téléchargé',
            'Nombre Téléchargements',
            'Date Téléchargement',
            'Email J+1',
            'Email J+3',
            'Email J+7',
            'Date Création',
            'Jours depuis création',
        ];

        foreach ($leads as $lead) {
            $csv[] = [
                $lead->getId(),
                $lead->getFirstName(),
                $lead->getLastName(),
                $lead->getEmail(),
                $lead->getCompany() ?? '',
                $lead->getPhone()   ?? '',
                $lead->getSource(),
                $lead->getStatus(),
                $lead->hasMarketingConsent() ? 'Oui' : 'Non',
                $lead->hasDownloaded() ? 'Oui' : 'Non',
                $lead->getDownloadCount(),
                $lead->getDownloadedAt() ? $lead->getDownloadedAt()->format('Y-m-d H:i:s') : '',
                $lead->getNurturingDay1SentAt() ? $lead->getNurturingDay1SentAt()->format('Y-m-d H:i:s') : '',
                $lead->getNurturingDay3SentAt() ? $lead->getNurturingDay3SentAt()->format('Y-m-d H:i:s') : '',
                $lead->getNurturingDay7SentAt() ? $lead->getNurturingDay7SentAt()->format('Y-m-d H:i:s') : '',
                $lead->getCreatedAt()->format('Y-m-d H:i:s'),
                $lead->getDaysSinceCreation(),
            ];
        }

        // Créer la réponse
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="leads-hotones-'.date('Y-m-d').'.csv"');

        $output = fopen('php://temp', 'r+');

        // BOM UTF-8 pour Excel
        fwrite($output, "\xEF\xBB\xBF");

        foreach ($csv as $row) {
            fputcsv($output, $row, ';', escape: '\\');
        }

        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }

    /**
     * Page des statistiques CRM globales.
     */
    #[Route('/statistics', name: 'admin_crm_statistics', methods: ['GET'])]
    public function statistics(): Response
    {
        $stats = $this->leadCaptureRepository->getStats();

        // Statistiques par source
        $statsBySourceRaw = $this->leadCaptureRepository->countBySource();

        // Convertir en format tableau pour le template
        $statsBySource = [];
        foreach ($statsBySourceRaw as $source => $count) {
            $statsBySource[] = [
                'source' => $source,
                'count'  => $count,
            ];
        }

        // Statistiques par statut
        $qb = $this->leadCaptureRepository->createQueryBuilder('l')
            ->select('l.status, COUNT(l.id) as count')
            ->groupBy('l.status');
        $statsByStatus = $qb->getQuery()->getResult();

        // Leads récents (7 derniers jours)
        $recentLeads = $this->leadCaptureRepository->findRecentLeads(7);

        // Taux de conversion par source
        $conversionBySource = [];
        foreach ($statsBySourceRaw as $source => $totalBySource) {
            $convertedQb = $this->leadCaptureRepository->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.source = :source')
                ->andWhere('l.status = :status')
                ->setParameter('source', $source)
                ->setParameter('status', LeadCapture::STATUS_CONVERTED);

            $converted = (int) $convertedQb->getQuery()->getSingleScalarResult();

            $conversionBySource[$source] = [
                'total'     => $totalBySource,
                'converted' => $converted,
                'rate'      => $totalBySource > 0 ? round(($converted / $totalBySource) * 100, 2) : 0,
            ];
        }

        return $this->render('admin/crm/statistics.html.twig', [
            'stats'                => $stats,
            'stats_by_source'      => $statsBySource,
            'stats_by_status'      => $statsByStatus,
            'recent_leads'         => $recentLeads,
            'conversion_by_source' => $conversionBySource,
        ]);
    }

    /**
     * Construction de la timeline d'événements pour un lead.
     */
    private function buildTimeline(LeadCapture $lead): array
    {
        $timeline = [];

        // Création du lead
        $timeline[] = [
            'date'        => $lead->getCreatedAt(),
            'type'        => 'created',
            'icon'        => 'bx-user-plus',
            'color'       => 'info',
            'title'       => 'Lead capturé',
            'description' => sprintf('Via %s', $lead->getSource()),
        ];

        // Téléchargement
        if ($lead->getDownloadedAt()) {
            $timeline[] = [
                'date'        => $lead->getDownloadedAt(),
                'type'        => 'downloaded',
                'icon'        => 'bx-download',
                'color'       => 'success',
                'title'       => 'Guide téléchargé',
                'description' => sprintf('%d téléchargement(s)', $lead->getDownloadCount()),
            ];
        }

        // Emails de nurturing
        if ($lead->getNurturingDay1SentAt()) {
            $timeline[] = [
                'date'        => $lead->getNurturingDay1SentAt(),
                'type'        => 'nurturing_day1',
                'icon'        => 'bx-envelope',
                'color'       => 'primary',
                'title'       => 'Email J+1 envoyé',
                'description' => 'Premier email de nurturing',
            ];
        }

        if ($lead->getNurturingDay3SentAt()) {
            $timeline[] = [
                'date'        => $lead->getNurturingDay3SentAt(),
                'type'        => 'nurturing_day3',
                'icon'        => 'bx-envelope',
                'color'       => 'primary',
                'title'       => 'Email J+3 envoyé',
                'description' => 'Cas pratique envoyé',
            ];
        }

        if ($lead->getNurturingDay7SentAt()) {
            $timeline[] = [
                'date'        => $lead->getNurturingDay7SentAt(),
                'type'        => 'nurturing_day7',
                'icon'        => 'bx-envelope',
                'color'       => 'primary',
                'title'       => 'Email J+7 envoyé',
                'description' => 'Proposition d\'essai HotOnes',
            ];
        }

        // Trier par date décroissante
        usort($timeline, fn ($a, $b): int => $b['date'] <=> $a['date']);

        return $timeline;
    }
}
