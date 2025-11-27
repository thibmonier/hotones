<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class AvatarController extends AbstractController
{
    #[Route('/avatars/{filename}', name: 'avatar_show', methods: ['GET'])]
    public function show(string $filename): Response
    {
        $avatarsDirectory = $this->getParameter('avatars_directory');

        if (!is_string($avatarsDirectory)) {
            throw new NotFoundHttpException('Avatar directory not configured');
        }

        $filePath = $avatarsDirectory.'/'.$filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new NotFoundHttpException('Avatar not found');
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', mime_content_type($filePath) ?: 'image/jpeg');
        $response->setMaxAge(3600 * 24 * 30); // Cache for 30 days
        $response->setPublic();

        return $response;
    }
}
