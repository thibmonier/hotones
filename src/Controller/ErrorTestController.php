<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller pour tester les pages d'erreur en développement.
 * À utiliser uniquement en environnement dev !
 */
#[Route('/test-errors', name: 'test_errors_')]
#[IsGranted('ROLE_ADMIN')]
class ErrorTestController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('error_test/index.html.twig');
    }

    #[Route('/404', name: '404')]
    public function error404(): never
    {
        throw new NotFoundHttpException('Cette page n\'existe pas (test 404)');
    }

    #[Route('/403', name: '403')]
    public function error403(): never
    {
        throw new AccessDeniedHttpException('Accès interdit (test 403)');
    }

    #[Route('/500', name: '500')]
    public function error500(): never
    {
        throw new \RuntimeException('Erreur serveur simulée (test 500)');
    }
}
