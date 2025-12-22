<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SaasSubscription;
use App\Form\SaasSubscriptionType;
use App\Repository\SaasSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/saas/subscriptions')]
#[IsGranted('ROLE_ADMIN')]
class SubscriptionController extends AbstractController
{
    #[Route('', name: 'subscription_index', methods: ['GET'])]
    public function index(
        Request $request,
        SaasSubscriptionRepository $subscriptionRepository,
        PaginatorInterface $paginator
    ): Response {
        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);

        if ($reset) {
            $session->remove('subscription_filters');

            return $this->redirectToRoute('subscription_index');
        }

        $queryAll   = $request->query->all();
        $filterKeys = ['search', 'status', 'billing_period', 'category', 'vendor', 'provider', 'per_page', 'sort', 'dir'];
        $hasFilter  = count(array_intersect(array_keys($queryAll), $filterKeys)) > 0;
        $saved      = $session->has('subscription_filters') ? (array) $session->get('subscription_filters') : [];

        $search        = $hasFilter ? ($request->query->get('search') ?: '') : ($saved['search'] ?? '');
        $status        = $hasFilter ? ($request->query->get('status') ?: '') : ($saved['status'] ?? '');
        $billingPeriod = $hasFilter ? ($request->query->get('billing_period') ?: '') : ($saved['billing_period'] ?? '');
        $category      = $hasFilter ? ($request->query->get('category') ?: '') : ($saved['category'] ?? '');
        $vendorId      = $hasFilter ? ($request->query->get('vendor') ?: '') : ($saved['vendor'] ?? '');
        $providerId    = $hasFilter ? ($request->query->get('provider') ?: '') : ($saved['provider'] ?? '');

        $sort = $hasFilter ? ($request->query->get('sort') ?: ($saved['sort'] ?? 'nextRenewalDate')) : ($saved['sort'] ?? 'nextRenewalDate');
        $dir  = $hasFilter ? ($request->query->get('dir') ?: ($saved['dir'] ?? 'ASC')) : ($saved['dir'] ?? 'ASC');

        $allowedPerPage = [10, 25, 50, 100];
        $perPageParam   = (int) ($hasFilter ? ($request->query->get('per_page', 25)) : ($saved['per_page'] ?? 25));
        $perPage        = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 25;

        $session->set('subscription_filters', [
            'search'         => $search,
            'status'         => $status,
            'billing_period' => $billingPeriod,
            'category'       => $category,
            'vendor'         => $vendorId,
            'provider'       => $providerId,
            'per_page'       => $perPage,
            'sort'           => $sort,
            'dir'            => $dir,
        ]);

        $qb = $subscriptionRepository->createQueryBuilder('s')
            ->leftJoin('s.service', 'srv')
            ->addSelect('srv')
            ->leftJoin('srv.provider', 'p')
            ->addSelect('p');

        if ($search) {
            $qb->andWhere('s.customName LIKE :search OR srv.name LIKE :search OR p.name LIKE :search OR s.notes LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($status !== '') {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $status);
        }

        if ($billingPeriod !== '') {
            $qb->andWhere('s.billingPeriod = :billingPeriod')
                ->setParameter('billingPeriod', $billingPeriod);
        }

        if ($category !== '') {
            $qb->andWhere('srv.category = :category')
                ->setParameter('category', $category);
        }

        if ($vendorId !== '') {
            $qb->andWhere('s.service = :service')
                ->setParameter('service', $vendorId);
        }

        if ($providerId !== '') {
            if ($providerId === 'null') {
                $qb->andWhere('srv.provider IS NULL');
            } else {
                $qb->andWhere('srv.provider = :provider')
                    ->setParameter('provider', $providerId);
            }
        }

        $validSortFields = [
            'nextRenewalDate' => 's.nextRenewalDate',
            'status'          => 's.status',
            'price'           => 's.price',
            'name'            => 'srv.name',
            'vendor'          => 'srv.name',
            'category'        => 'srv.category',
        ];
        $sortField = $validSortFields[$sort] ?? 's.nextRenewalDate';
        $sortDir   = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($sortField, $sortDir);

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            $perPage,
        );

        return $this->render('saas/subscription/index.html.twig', [
            'subscriptions' => $pagination,
            'filters'       => [
                'search'         => $search,
                'status'         => $status,
                'billing_period' => $billingPeriod,
                'category'       => $category,
                'vendor'         => $vendorId,
                'provider'       => $providerId,
            ],
            'sort' => $sort,
            'dir'  => $dir,
        ]);
    }

    #[Route('/new', name: 'subscription_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $subscription = new SaasSubscription();
        $form         = $this->createForm(SaasSubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($subscription);
            $em->flush();

            $this->addFlash('success', 'Abonnement créé avec succès.');

            return $this->redirectToRoute('subscription_show', ['id' => $subscription->getId()]);
        }

        return $this->render('saas/subscription/new.html.twig', [
            'subscription' => $subscription,
            'form'         => $form,
        ]);
    }

    #[Route('/{id}', name: 'subscription_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(SaasSubscription $subscription): Response
    {
        return $this->render('saas/subscription/show.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    #[Route('/{id}/edit', name: 'subscription_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SaasSubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Abonnement modifié avec succès.');

            return $this->redirectToRoute('subscription_show', ['id' => $subscription->getId()]);
        }

        return $this->render('saas/subscription/edit.html.twig', [
            'subscription' => $subscription,
            'form'         => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'subscription_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $em->remove($subscription);
            $em->flush();

            $this->addFlash('success', 'Abonnement supprimé avec succès.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    #[Route('/{id}/renew', name: 'subscription_renew', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function renew(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('renew'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $subscription->renew();
            $em->flush();

            $this->addFlash('success', 'Abonnement renouvelé avec succès.');
        }

        return $this->redirectToRoute('subscription_show', ['id' => $subscription->getId()]);
    }

    #[Route('/{id}/cancel', name: 'subscription_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('cancel'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $subscription->cancel();
            $em->flush();

            $this->addFlash('success', 'Abonnement annulé avec succès.');
        }

        return $this->redirectToRoute('subscription_show', ['id' => $subscription->getId()]);
    }

    #[Route('/{id}/suspend', name: 'subscription_suspend', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function suspend(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('suspend'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $subscription->suspend();
            $em->flush();

            $this->addFlash('success', 'Abonnement suspendu avec succès.');
        }

        return $this->redirectToRoute('subscription_show', ['id' => $subscription->getId()]);
    }

    #[Route('/{id}/reactivate', name: 'subscription_reactivate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reactivate(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('reactivate'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $subscription->reactivate();
            $em->flush();

            $this->addFlash('success', 'Abonnement réactivé avec succès.');
        }

        return $this->redirectToRoute('subscription_show', ['id' => $subscription->getId()]);
    }
}
