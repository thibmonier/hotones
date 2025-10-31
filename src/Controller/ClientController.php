<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\ClientContact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients')]
#[IsGranted('ROLE_INTERVENANT')]
class ClientController extends AbstractController
{
    #[Route('', name: 'client_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $clients = $em->getRepository(Client::class)->findAllOrderedByName();

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/new', name: 'client_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $client = new Client();

        if ($request->isMethod('POST')) {
            $client->setName($request->request->get('name'));
            $client->setWebsite($request->request->get('website'));
            $client->setDescription($request->request->get('description'));

            /** @var UploadedFile|null $logo */
            $logo = $request->files->get('logo');
            if ($logo instanceof UploadedFile && $logo->isValid()) {
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/clients';
                $fs        = new Filesystem();
                if (!$fs->exists($uploadDir)) {
                    $fs->mkdir($uploadDir, 0775);
                }
                $safeName = uniqid('client_', true).'.'.$logo->guessExtension();
                $logo->move($uploadDir, $safeName);
                $client->setLogoPath('/uploads/clients/'.$safeName);
            }

            $em->persist($client);
            $em->flush();

            $this->addFlash('success', 'Client créé avec succès');

            return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}', name: 'client_show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'client_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function edit(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $client->setName($request->request->get('name'));
            $client->setWebsite($request->request->get('website'));
            $client->setDescription($request->request->get('description'));

            /** @var UploadedFile|null $logo */
            $logo = $request->files->get('logo');
            if ($logo instanceof UploadedFile && $logo->isValid()) {
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/clients';
                $fs        = new Filesystem();
                if (!$fs->exists($uploadDir)) {
                    $fs->mkdir($uploadDir, 0775);
                }
                $safeName = uniqid('client_', true).'.'.$logo->guessExtension();
                $logo->move($uploadDir, $safeName);
                $client->setLogoPath('/uploads/clients/'.$safeName);
            }

            $em->flush();

            $this->addFlash('success', 'Client modifié avec succès');

            return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/delete', name: 'client_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->request->get('_token'))) {
            $em->remove($client);
            $em->flush();
            $this->addFlash('success', 'Client supprimé avec succès');
        }

        return $this->redirectToRoute('client_index');
    }

    #[Route('/{id}/contacts/new', name: 'client_contact_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function addContact(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        $contact = new ClientContact();
        $contact->setClient($client);

        if ($request->isMethod('POST')) {
            $contact->setLastName($request->request->get('last_name'));
            $contact->setFirstName($request->request->get('first_name'));
            $contact->setEmail($request->request->get('email'));
            $contact->setPhone($request->request->get('phone'));
            $contact->setMobilePhone($request->request->get('mobile_phone'));
            $contact->setPositionTitle($request->request->get('position_title'));

            $em->persist($contact);
            $em->flush();

            $this->addFlash('success', 'Contact ajouté avec succès');

            return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
        }

        return $this->render('client_contact/new.html.twig', [
            'client'  => $client,
            'contact' => $contact,
        ]);
    }
}
