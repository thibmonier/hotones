<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
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
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
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
            $roles = array_values(array_unique(array_filter($roles)));
            $user->setRoles($roles);

            $avatarFile = $request->files->get('avatar');
            if ($avatarFile instanceof UploadedFile && $avatarFile->isValid()) {
                if (str_starts_with((string) $avatarFile->getMimeType(), 'image/')) {
                    $this->extracted($avatarFile, $user);
                } else {
                    $this->addFlash('danger', 'Format de fichier invalide pour l’avatar');
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
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
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
                if (str_starts_with((string) $avatarFile->getMimeType(), 'image/')) {
                    $this->extracted($avatarFile, $user);
                    $em->flush();
                } else {
                    $this->addFlash('danger', 'Format de fichier invalide pour l’avatar');
                }
            }

            // auto-link contributor
            $contributor = new Contributor();
            $contributor->setName(trim($firstName.' '.$lastName))
                ->setEmail($email)
                ->setUser($user)
                ->setActive(true);
            $em->persist($contributor);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé et contributeur associé (#'.$contributor->getId().')');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/new.html.twig');
    }

    /**
     * @throws RandomException
     */
    public function extracted(mixed $avatarFile, User|string $user): void
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $targetDir  = $projectDir.'/public/uploads/avatars';
        (new Filesystem())->mkdir($targetDir);
        $ext      = $avatarFile->guessExtension() ?: 'png';
        $safeName = 'u'.$user->getId().'_'.bin2hex(random_bytes(6)).'.'.$ext;
        $avatarFile->move($targetDir, $safeName);
        $user->setAvatar('/uploads/avatars/'.$safeName);
    }
}
