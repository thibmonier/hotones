<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BillingMarker;
use App\Entity\Order;
use App\Entity\OrderPaymentSchedule;
use App\Security\CompanyContext;
use DateTimeInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<BillingMarker>
 */
class BillingMarkerRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, BillingMarker::class, $companyContext);
    }

    /**
     * Retourne les marqueurs (par scheduleId et par (orderId, year, month)) pour accélérer le mapping dans la vue du mois.
     *
     * @return array{bySchedule: array<int, BillingMarker>, byRegie: array<string, BillingMarker>}
     */
    public function getMonthMarkers(DateTimeInterface $monthStart, DateTimeInterface $monthEnd): array
    {
        $qb = $this
            ->createCompanyQueryBuilder('m')
            ->leftJoin('m.schedule', 's')
            ->leftJoin('m.order', 'o')
            ->andWhere(
                '(s.id IS NOT NULL AND s.billingDate BETWEEN :start AND :end)'
                .' OR (o.id IS NOT NULL AND m.year = :y AND m.month = :m)',
            )
            ->setParameter('start', $monthStart)
            ->setParameter('end', $monthEnd)
            ->setParameter('y', (int) $monthStart->format('Y'))
            ->setParameter('m', (int) $monthStart->format('n'));

        $rows = $qb->getQuery()->getResult();

        $bySchedule = [];
        $byRegie    = [];
        /** @var BillingMarker $bm */
        foreach ($rows as $bm) {
            if ($bm->getSchedule()) {
                $bySchedule[$bm->getSchedule()->getId()] = $bm;
            } elseif ($bm->getOrder() && $bm->getYear() && $bm->getMonth()) {
                $key           = sprintf('%d-%04d-%02d', $bm->getOrder()->id, $bm->getYear(), $bm->getMonth());
                $byRegie[$key] = $bm;
            }
        }

        return ['bySchedule' => $bySchedule, 'byRegie' => $byRegie];
    }

    public function getOrCreateForSchedule(OrderPaymentSchedule $schedule): BillingMarker
    {
        $existing = $this->findOneBy(['schedule' => $schedule]);
        if ($existing) {
            return $existing;
        }
        $bm = new BillingMarker();
        $bm->setSchedule($schedule);
        $this->getEntityManager()->persist($bm);

        return $bm;
    }

    public function getOrCreateForRegiePeriod(Order $order, int $year, int $month): BillingMarker
    {
        $existing = $this->findOneBy(['order' => $order, 'year' => $year, 'month' => $month]);
        if ($existing) {
            return $existing;
        }
        $bm = new BillingMarker();
        $bm->setOrder($order)->setYear($year)->setMonth($month);
        $this->getEntityManager()->persist($bm);

        return $bm;
    }
}
