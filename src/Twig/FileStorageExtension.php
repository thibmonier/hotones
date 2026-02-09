<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\SecureFileUploadService;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig pour gérer les URLs de fichiers uploadés.
 */
class FileStorageExtension extends AbstractExtension
{
    public function __construct(
        private readonly SecureFileUploadService $uploadService,
    ) {
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('avatar_url', $this->getAvatarUrl(...)),
            new TwigFunction('file_url', $this->getFileUrl(...)),
        ];
    }

    /**
     * Génère l'URL complète d'un avatar.
     *
     * @param string|null $filename Nom du fichier ou chemin complet (legacy)
     */
    public function getAvatarUrl(?string $filename): string
    {
        if (!$filename) {
            return '';
        }

        // Si c'est un ancien chemin complet (/uploads/avatars/filename.jpg)
        // extraire juste le nom du fichier
        if (str_starts_with($filename, '/uploads/avatars/')) {
            $filename = basename($filename);
        }

        return $this->uploadService->getPublicUrl($filename, 'avatars');
    }

    /**
     * Génère l'URL complète d'un fichier.
     *
     * @param string|null $filename     Nom du fichier
     * @param string      $subdirectory Sous-répertoire (avatars, expenses, etc.)
     */
    public function getFileUrl(?string $filename, string $subdirectory = 'avatars'): string
    {
        if (!$filename) {
            return '';
        }

        return $this->uploadService->getPublicUrl($filename, $subdirectory);
    }
}
