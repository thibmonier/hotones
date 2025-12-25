<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\User;
use App\Service\SecureFileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_MANAGER')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'admin_users')]
    public function index(EntityManagerInterface $em): Response
    {
        $users        = $em->getRepository(User::class)->findAll();
        $contributors = [];
        foreach ($users as $u) {
            $contributors[$u->getId()] = $em->getRepository(Contributor::class)->findOneBy(['user' => $u]);
        }

        return $this->render('admin/user/index.html.twig', [
            'users'        => $users,
            'contributors' => $contributors,
        ]);
    }

    /**
     * @throws RandomException
     */
    #[Route('/{id}/edit', name: 'admin_users_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        SecureFileUploadService $uploadService
    ): Response {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            // core data
            $user->setFirstName((string) $request->request->get('first_name'));
            $user->setLastName((string) $request->request->get('last_name'));
            $user->setEmail((string) $request->request->get('email'));
            $user->setPhoneWork($request->request->get('phone_work'));
            $user->setPhonePersonal($request->request->get('phone_personal'));
            $user->setAddress($request->request->get('address'));

            // roles
            $roles = $request->request->all('roles');
            $roles = array_values(array_unique(array_filter($roles, fn ($role) => $role !== null && $role !== '')));
            $user->setRoles($roles);

            $avatarFile = $request->files->get('avatar');
            if ($avatarFile instanceof UploadedFile && $avatarFile->isValid()) {
                try {
                    $filename = $uploadService->uploadImage($avatarFile, 'avatars');
                    $user->setAvatar($filename);
                } catch (Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload : '.$e->getMessage());
                }
            }

            $em->flush();
            $this->addFlash('success', 'Rôles/Avatar mis à jour');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @throws RandomException
     */
    #[Route('/new', name: 'admin_users_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        SecureFileUploadService $uploadService
    ): Response {
        if ($request->isMethod('POST')) {
            $email     = (string) $request->request->get('email');
            $password  = (string) $request->request->get('password');
            $firstName = (string) $request->request->get('first_name');
            $lastName  = (string) $request->request->get('last_name');

            $user = new User();
            $user->setEmail($email)
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setRoles(['ROLE_USER']);
            $user->setPassword($hasher->hashPassword($user, $password));
            $em->persist($user);
            $em->flush();

            // handle avatar
            $avatarFile = $request->files->get('avatar');
            if ($avatarFile instanceof UploadedFile && $avatarFile->isValid()) {
                try {
                    $filename = $uploadService->uploadImage($avatarFile, 'avatars');
                    $user->setAvatar($filename);
                    $em->flush();
                } catch (Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload : '.$e->getMessage());
                }
            }

            // auto-link contributor
            $contributor = new Contributor();
            $contributor->setFirstName($firstName)
                ->setLastName($lastName)
                ->setEmail($email)
                ->setUser($user)
                ->setActive(true);
            $em->persist($contributor);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé et collaborateur associé (#'.$contributor->getId().')');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/new.html.twig');
    }
}
