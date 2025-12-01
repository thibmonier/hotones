<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderPaymentSchedule;
use App\Repository\BillingMarkerRepository;
use App\Repository\TimesheetRepository;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/billing')]
class BillingController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'billing_index', methods: ['GET'])]
    #[IsGranted('ROLE_COMPTA')]
    public function index(Request $request, TimesheetRepository $timesheets, BillingMarkerRepository $markersRepo): Response
    {
        $monthParam = (string) ($request->query->get('month') ?? (new DateTime('first day of this month'))->format('Y-m'));
        $month      = DateTime::createFromFormat('Y-m-d', $monthParam.'-01') ?: new DateTime('first day of this month');
        $start      = (clone $month)->modify('first day of this month')->setTime(0, 0, 0);
        $end        = (clone $month)->modify('last day of this month')->setTime(23, 59, 59);

        // Forfait: échéances dans le mois
        $schedules = $this->em->createQueryBuilder()
            ->select('s, o, p')
            ->from(OrderPaymentSchedule::class, 's')
            ->join('s.order', 'o')
            ->join('o.project', 'p')
            ->where('s.billingDate BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->orderBy('s.billingDate', 'ASC')
            ->getQuery()->getResult();

        // Régie: CA du mois par projet pour tous les devis en régie signés/gagnés/terminés
        $ordersRegie = $this->em->createQueryBuilder()
            ->select('o, p')
            ->from(Order::class, 'o')
            ->join('o.project', 'p')
            ->where('o.contractType = :ct')
            ->andWhere('o.status IN (:st)')
            ->setParameter('ct', 'regie')
            ->setParameter('st', ['gagne', 'signe', 'termine'])
            ->getQuery()->getResult();

        $regieEntries = [];
        foreach ($ordersRegie as $order) {
            $project = $order->getProject();
            $rows    = $timesheets->getMonthlyRevenueForProjectUsingContributorTjm($project, $start, $end);
            foreach ($rows as $r) {
                $regieEntries[] = [
                    'date'    => DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', (int) $r['year'], (int) $r['month'])),
                    'type'    => 'regie',
                    'order'   => $order,
                    'project' => $project,
                    'label'   => sprintf('Régie %02d/%04d', (int) $r['month'], (int) $r['year']),
                    'amount'  => (float) ($r['revenue'] ?? 0),
                    'year'    => (int) $r['year'],
                    'month'   => (int) $r['month'],
                ];
            }
        }

        // Forfait entries, calcul du montant à partir du devis (même logique que BillingService)
        $forfaitEntries = [];
        foreach ($schedules as $s) {
            $o                = $s->getOrder();
            $forfaitEntries[] = [
                'date'     => $s->getBillingDate(),
                'type'     => 'forfait',
                'order'    => $o,
                'project'  => $o->getProject(),
                'label'    => $s->getLabel() ?: 'Échéance',
                'amount'   => (float) $s->computeAmount($o->calculateTotalFromSections()),
                'schedule' => $s,
            ];
        }

        $entries = array_merge($forfaitEntries, $regieEntries);
        usort($entries, fn ($a, $b) => ($a['date'] <=> $b['date']) ?: (($a['project']->getName() ?? '') <=> ($b['project']->getName() ?? '')));

        // Récupérer les marqueurs existants pour le mois
        $monthMarkers = $markersRepo->getMonthMarkers($start, $end);

        // Navigation mois précédent/suivant
        $prev = (clone $start)->sub(new DateInterval('P1M'))->format('Y-m');
        $next = (clone $start)->add(new DateInterval('P1M'))->format('Y-m');

        return $this->render('billing/index.html.twig', [
            'entries'   => $entries,
            'month'     => $start,
            'prevMonth' => $prev,
            'nextMonth' => $next,
            'markers'   => $monthMarkers,
        ]);
    }

    #[Route('/mark/schedule/{id}', name: 'billing_mark_schedule', methods: ['POST'])]
    #[IsGranted('ROLE_COMPTA')]
    public function markSchedule(OrderPaymentSchedule $schedule, Request $request, BillingMarkerRepository $repo): Response
    {
        $bm = $repo->getOrCreateForSchedule($schedule);
        $bm->setIsIssued($request->request->getBoolean('is_issued'));
        $issuedAt = $request->request->get('issued_at');
        $paidAt   = $request->request->get('paid_at');
        $bm->setIssuedAt($issuedAt ? new DateTime($issuedAt) : null);
        $bm->setPaidAt($paidAt ? new DateTime($paidAt) : null);
        $bm->setComment($request->request->get('comment') ?: null);
        $this->em->flush();

        // Support ajax inline update
        if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'fetch') {
            return $this->json(['ok' => true]);
        }

        return $this->redirectToRoute('billing_index', ['month' => $request->query->get('month')]);
    }

    #[Route('/mark/regie/{orderId}', name: 'billing_mark_regie', methods: ['POST'])]
    #[IsGranted('ROLE_COMPTA')]
    public function markRegie(int $orderId, Request $request, BillingMarkerRepository $repo): Response
    {
        /** @var Order|null $order */
        $order = $this->em->getRepository(Order::class)->find($orderId);
        if (!$order) {
            return $this->json(['ok' => false, 'error' => 'Order not found'], 404);
        }
        $year  = (int) $request->request->get('year');
        $month = (int) $request->request->get('month');
        $bm    = $repo->getOrCreateForRegiePeriod($order, $year, $month);
        $bm->setIsIssued($request->request->getBoolean('is_issued'));
        $issuedAt = $request->request->get('issued_at');
        $paidAt   = $request->request->get('paid_at');
        $bm->setIssuedAt($issuedAt ? new DateTime($issuedAt) : null);
        $bm->setPaidAt($paidAt ? new DateTime($paidAt) : null);
        $bm->setComment($request->request->get('comment') ?: null);
        $this->em->flush();

        if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'fetch') {
            return $this->json(['ok' => true]);
        }

        return $this->redirectToRoute('billing_index', ['month' => $request->query->get('month')]);
    }
}
