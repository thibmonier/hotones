<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/me', name: 'profile_me')]
    #[IsGranted('ROLE_USER')]
    public function profile(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $contributor       = $em->getRepository(\App\Entity\Contributor::class)->findOneBy(['user' => $user]);
        $employmentPeriods = [];
        if ($contributor) {
            $employmentPeriods = $em->getRepository(\App\Entity\EmploymentPeriod::class)->findByContributor($contributor);
        }

        return $this->render('profile/profile.html.twig', [
            'user'              => $user,
            'contributor'       => $contributor,
            'employmentPeriods' => $employmentPeriods,
        ]);
    }

    #[Route('/me/edit', name: 'profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function editProfile(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $user->setFirstName($request->request->get('first_name'));
            $user->setLastName($request->request->get('last_name'));
            $user->setEmail($request->request->get('email'));
            $user->setPhoneWork($request->request->get('phone_work'));
            $user->setPhonePersonal($request->request->get('phone_personal'));
            $user->setAddress($request->request->get('address'));

            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $request->files->get('avatar');
            if ($avatarFile instanceof UploadedFile && $avatarFile->isValid()) {
                $mime = $avatarFile->getMimeType();
                if (str_starts_with((string) $mime, 'image/')) {
                    $projectDir = $this->getParameter('kernel.project_dir');
                    $targetDir  = $projectDir.'/public/uploads/avatars';
                    (new Filesystem())->mkdir($targetDir);
                    $ext      = $avatarFile->guessExtension() ?: 'png';
                    $safeName = 'u'.$user->getId().'_'.bin2hex(random_bytes(6)).'.'.$ext;
                    $avatarFile->move($targetDir, $safeName);
                    $user->setAvatar('/uploads/avatars/'.$safeName);
                } else {
                    $this->addFlash('danger', 'Format de fichier invalide pour l’avatar');
                }
            }

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
            'secret'      => $user->getTotpSecret(),
        ]);
    }

    #[Route('/me/password', name: 'profile_password', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function changePassword(Request $request, EntityManagerInterface $em, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $current = (string) $request->request->get('current_password');
            $new     = (string) $request->request->get('new_password');
            $confirm = (string) $request->request->get('confirm_password');

            if (!$hasher->isPasswordValid($user, $current)) {
                $this->addFlash('danger', 'Mot de passe actuel incorrect');
            } elseif ($new === '' || $new !== $confirm) {
                $this->addFlash('danger', 'Le nouveau mot de passe et la confirmation ne correspondent pas');
            } else {
                $user->setPassword($hasher->hashPassword($user, $new));
                $em->flush();
                $this->addFlash('success', 'Mot de passe mis à jour');

                return $this->redirectToRoute('profile_me');
            }
        }

        return $this->render('profile/password.html.twig');
    }

    #[Route('/me/2fa/activate', name: 'me_2fa_activate')]
    #[IsGranted('ROLE_USER')]
    public function activate2fa(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setTotpEnabled(true);
        $em->flush();

        return $this->redirectToRoute('profile_2fa_setup');
    }

    #[Route('/me/notifications', name: 'profile_notifications', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function notifications(Request $request, EntityManagerInterface $em, \App\Repository\NotificationPreferenceRepository $prefs): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Charger les préférences existantes indexées par event type
        $current = $prefs->findByUserIndexedByEventType($user);

        if ($request->isMethod('POST')) {
            // Uniquement la préférence pour le rappel hebdo de temps pour commencer
            $inApp   = (bool) $request->request->get('timesheet_missing_weekly_inapp', false);
            $email   = (bool) $request->request->get('timesheet_missing_weekly_email', false);
            $webhook = false; // non exposé pour l'instant

            $prefs->upsert($user, \App\Enum\NotificationType::TIMESHEET_MISSING_WEEKLY, $inApp, $email, $webhook);
            $this->addFlash('success', 'Préférences de notifications mises à jour');

            return $this->redirectToRoute('profile_notifications');
        }

        return $this->render('profile/notifications.html.twig', [
            'user'    => $user,
            'current' => $current,
        ]);
    }
}
