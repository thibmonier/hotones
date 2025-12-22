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
class SaasSubscriptionController extends AbstractController
{
    #[Route('', name: 'saas_subscription_index', methods: ['GET'])]
    public function index(
        Request $request,
        SaasSubscriptionRepository $subscriptionRepository,
        PaginatorInterface $paginator
    ): Response {
        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);

        if ($reset) {
            $session->remove('saas_subscription_filters');

            return $this->redirectToRoute('saas_subscription_index');
        }

        $queryAll   = $request->query->all();
        $filterKeys = ['search', 'status', 'billing_period', 'per_page', 'sort', 'dir'];
        $hasFilter  = count(array_intersect(array_keys($queryAll), $filterKeys)) > 0;
        $saved      = $session->has('saas_subscription_filters') ? (array) $session->get('saas_subscription_filters') : [];

        $search        = $hasFilter ? ($request->query->get('search') ?: '') : ($saved['search'] ?? '');
        $status        = $hasFilter ? ($request->query->get('status') ?: '') : ($saved['status'] ?? '');
        $billingPeriod = $hasFilter ? ($request->query->get('billing_period') ?: '') : ($saved['billing_period'] ?? '');

        $sort = $hasFilter ? ($request->query->get('sort') ?: ($saved['sort'] ?? 'nextRenewalDate')) : ($saved['sort'] ?? 'nextRenewalDate');
        $dir  = $hasFilter ? ($request->query->get('dir') ?: ($saved['dir'] ?? 'ASC')) : ($saved['dir'] ?? 'ASC');

        $allowedPerPage = [10, 25, 50, 100];
        $perPageParam   = (int) ($hasFilter ? ($request->query->get('per_page', 25)) : ($saved['per_page'] ?? 25));
        $perPage        = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 25;

        $session->set('saas_subscription_filters', [
            'search'         => $search,
            'status'         => $status,
            'billing_period' => $billingPeriod,
            'per_page'       => $perPage,
            'sort'           => $sort,
            'dir'            => $dir,
        ]);

        $qb = $subscriptionRepository->createQueryBuilder('sub')
            ->leftJoin('sub.service', 's')
            ->addSelect('s')
            ->leftJoin('s.provider', 'p')
            ->addSelect('p');

        if ($search) {
            $qb->andWhere('sub.customName LIKE :search OR s.name LIKE :search OR p.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($status !== '') {
            $qb->andWhere('sub.status = :status')
                ->setParameter('status', $status);
        }

        if ($billingPeriod !== '') {
            $qb->andWhere('sub.billingPeriod = :billingPeriod')
                ->setParameter('billingPeriod', $billingPeriod);
        }

        $validSortFields = [
            'nextRenewalDate' => 'sub.nextRenewalDate',
            'status'          => 'sub.status',
            'price'           => 'sub.price',
            'service'         => 's.name',
        ];
        $sortField = $validSortFields[$sort] ?? 'sub.nextRenewalDate';
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
            ],
            'sort' => $sort,
            'dir'  => $dir,
        ]);
    }

    #[Route('/new', name: 'saas_subscription_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $subscription = new SaasSubscription();
        $form         = $this->createForm(SaasSubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($subscription);
            $em->flush();

            $this->addFlash('success', 'Abonnement créé avec succès.');

            return $this->redirectToRoute('saas_subscription_show', ['id' => $subscription->getId()]);
        }

        return $this->render('saas/subscription/new.html.twig', [
            'subscription' => $subscription,
            'form'         => $form,
        ]);
    }

    #[Route('/{id}', name: 'saas_subscription_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(SaasSubscription $subscription): Response
    {
        return $this->render('saas/subscription/show.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    #[Route('/{id}/edit', name: 'saas_subscription_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SaasSubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Abonnement modifié avec succès.');

            return $this->redirectToRoute('saas_subscription_show', ['id' => $subscription->getId()]);
        }

        return $this->render('saas/subscription/edit.html.twig', [
            'subscription' => $subscription,
            'form'         => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'saas_subscription_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $em->remove($subscription);
            $em->flush();

            $this->addFlash('success', 'Abonnement supprimé avec succès.');
        }

        return $this->redirectToRoute('saas_subscription_index');
    }

    #[Route('/{id}/renew', name: 'saas_subscription_renew', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function renew(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('renew'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $subscription->renew();
            $em->flush();

            $this->addFlash('success', 'Abonnement renouvelé avec succès.');
        }

        return $this->redirectToRoute('saas_subscription_show', ['id' => $subscription->getId()]);
    }

    #[Route('/{id}/cancel', name: 'saas_subscription_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('cancel'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $subscription->cancel();
            $em->flush();

            $this->addFlash('success', 'Abonnement annulé avec succès.');
        }

        return $this->redirectToRoute('saas_subscription_show', ['id' => $subscription->getId()]);
    }

    #[Route('/{id}/suspend', name: 'saas_subscription_suspend', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function suspend(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('suspend'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $subscription->suspend();
            $em->flush();

            $this->addFlash('success', 'Abonnement suspendu avec succès.');
        }

        return $this->redirectToRoute('saas_subscription_show', ['id' => $subscription->getId()]);
    }

    #[Route('/{id}/reactivate', name: 'saas_subscription_reactivate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reactivate(Request $request, SaasSubscription $subscription, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('reactivate'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $subscription->reactivate();
            $em->flush();

            $this->addFlash('success', 'Abonnement réactivé avec succès.');
        }

        return $this->redirectToRoute('saas_subscription_show', ['id' => $subscription->getId()]);
    }
}
