<?php

namespace App\Controller;

use App\Entity\ClientContact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contacts')]
#[IsGranted('ROLE_INTERVENANT')]
class ClientContactController extends AbstractController
{
    #[Route('/{id}/edit', name: 'client_contact_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function edit(Request $request, ClientContact $contact, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $contact->setLastName($request->request->get('last_name'));
            $contact->setFirstName($request->request->get('first_name'));
            $contact->setEmail($request->request->get('email'));
            $contact->setPhone($request->request->get('phone'));
            $contact->setMobilePhone($request->request->get('mobile_phone'));
            $contact->setPositionTitle($request->request->get('position_title'));

            $em->flush();
            $this->addFlash('success', 'Contact mis à jour');

            return $this->redirectToRoute('client_show', ['id' => $contact->getClient()->getId()]);
        }

        return $this->render('client_contact/edit.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/delete', name: 'client_contact_delete', methods: ['POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function delete(Request $request, ClientContact $contact, EntityManagerInterface $em): Response
    {
        $clientId = $contact->getClient()->getId();
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) {
            $em->remove($contact);
            $em->flush();
            $this->addFlash('success', 'Contact supprimé');
        }

        return $this->redirectToRoute('client_show', ['id' => $clientId]);
    }
}
