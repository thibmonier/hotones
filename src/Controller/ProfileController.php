<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/me', name: 'profile_me')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) { 
            return $this->redirectToRoute('app_login'); 
        }

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/me/edit', name: 'profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function editProfile(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) { 
            return $this->redirectToRoute('app_login'); 
        }

        if ($request->isMethod('POST')) {
            $user->setFirstName($request->request->get('first_name'));
            $user->setLastName($request->request->get('last_name'));
            $user->setPhone($request->request->get('phone'));
            $user->setAddress($request->request->get('address'));
            
            $em->flush();
            
            $this->addFlash('success', 'Profil mis à jour avec succès');
            return $this->redirectToRoute('profile_me');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/me/2fa/setup', name: 'profile_2fa_setup')]
    #[IsGranted('ROLE_USER')]
    public function enable2fa(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) { return $this->redirectToRoute('app_login'); }

        if (!$user->getTotpSecret()) {
            $totp = TOTP::create();
            $totp->setLabel($user->getEmail());
            $totp->setIssuer('hotones');
            $user->setTotpSecret($totp->getSecret());
            $em->flush();
        }

        $totp = TOTP::create($user->getTotpSecret());
        $totp->setLabel($user->getEmail());
        $totp->setIssuer('hotones');

        return $this->render('profile/2fa_setup.html.twig', [
            'otpauth_uri' => $totp->getProvisioningUri(),
            'secret' => $user->getTotpSecret(),
        ]);
    }

    #[Route('/me/2fa/activate', name: 'me_2fa_activate')]
    #[IsGranted('ROLE_USER')]
    public function activate2fa(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            $user->setTotpEnabled(true);
            $em->flush();
        }
        return $this->redirectToRoute('profile_2fa_setup');
    }
}
