<?php

namespace App\Controller\Admin;

use App\Entity\SchedulerEntry;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SchedulerController extends AbstractController
{
    #[Route('/admin/scheduler', name: 'admin_scheduler_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $entries = $em->getRepository(SchedulerEntry::class)->findBy([], ['name' => 'ASC']);

        return $this->render('scheduler/index.html.twig', [
            'entries' => $entries,
        ]);
    }

    #[Route('/admin/scheduler/new', name: 'admin_scheduler_new', methods: ['GET', 'POST'])]
    public function new(EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $entry = new SchedulerEntry();
        $form  = $this->createForm(\App\Form\SchedulerEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Decode JSON payload if provided
            $payload = $entry->getPayload();
            if (is_string($payload)) {
                $decoded = json_decode($payload, true);
                $entry->setPayload($decoded ?: null);
            }

            $em->persist($entry);
            $em->flush();
            $this->addFlash('success', 'Entrée de scheduler créée');

            return $this->redirectToRoute('admin_scheduler_index');
        }

        return $this->render('scheduler/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/scheduler/{id}/edit', name: 'admin_scheduler_edit', methods: ['GET', 'POST'])]
    public function edit(SchedulerEntry $entry, EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $form = $this->createForm(\App\Form\SchedulerEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payload = $entry->getPayload();
            if (is_string($payload)) {
                $decoded = json_decode($payload, true);
                $entry->setPayload($decoded ?: null);
            }

            $entry->setUpdatedAt(new DateTimeImmutable('now'));
            $em->flush();
            $this->addFlash('success', 'Entrée mise à jour');

            return $this->redirectToRoute('admin_scheduler_index');
        }

        return $this->render('scheduler/edit.html.twig', [
            'entry' => $entry,
            'form'  => $form->createView(),
        ]);
    }

    #[Route('/admin/scheduler/{id}/toggle', name: 'admin_scheduler_toggle', methods: ['POST'])]
    public function toggle(SchedulerEntry $entry, EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        if (!$this->isCsrfTokenValid('toggle'.$entry->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide');
        }
        $entry->setEnabled(!$entry->isEnabled());
        $entry->setUpdatedAt(new DateTimeImmutable('now'));
        $em->flush();
        $this->addFlash('success', 'État modifié');

        return $this->redirectToRoute('admin_scheduler_index');
    }

    #[Route('/admin/scheduler/{id}/delete', name: 'admin_scheduler_delete', methods: ['POST'])]
    public function delete(SchedulerEntry $entry, EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$entry->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide');
        }
        $em->remove($entry);
        $em->flush();
        $this->addFlash('success', 'Entrée supprimée');

        return $this->redirectToRoute('admin_scheduler_index');
    }
}
