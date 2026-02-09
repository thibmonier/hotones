<?php

declare(strict_types=1);

namespace App\Controller;

use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour servir les avatars.
 * En développement : sert les fichiers depuis le stockage local via Flysystem
 * En production avec S3 : peut rediriger vers l'URL publique ou servir via stream.
 */
class AvatarController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'oneup_flysystem.default_filesystem')]
        private readonly FilesystemOperator $filesystem,
        #[Autowire(param: 'env(S3_PUBLIC_URL)')]
        private readonly string $publicUrl = '',
        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment = 'dev',
    ) {
    }

    #[Route('/avatars/{filename}', name: 'avatar_show', methods: ['GET'])]
    public function show(string $filename): Response
    {
        $filePath = 'avatars/'.$filename;

        // Vérifier que le fichier existe
        if (!$this->filesystem->fileExists($filePath)) {
            throw new NotFoundHttpException('Avatar not found');
        }

        // En production avec S3, rediriger vers l'URL publique
        if ($this->environment === 'prod' && $this->publicUrl !== '') {
            return $this->redirect(sprintf('%s/%s', rtrim($this->publicUrl, '/'), $filePath), 301);
        }

        // En développement ou si pas d'URL publique, servir le fichier via stream
        $mimeType = $this->filesystem->mimeType($filePath);

        $response = new StreamedResponse(function () use ($filePath): void {
            $stream = $this->filesystem->readStream($filePath);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        });

        $response->headers->set('Content-Type', $mimeType);
        $response->setMaxAge(3600 * 24 * 30); // Cache for 30 days
        $response->setPublic();

        return $response;
    }
}
