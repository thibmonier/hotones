<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiDocController extends AbstractController
{
    #[Route('/api/documentation', name: 'api_docs_ui', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('api/swagger.html.twig', [
            'title'      => 'HotOnes API - Documentation',
            'apiDocsUrl' => '/api/docs.json',
        ]);
    }
}
