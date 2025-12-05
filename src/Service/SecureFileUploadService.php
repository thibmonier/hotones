<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service de gestion sécurisée des uploads de fichiers
 * - Validation stricte du type MIME réel (pas juste l'extension)
 * - Validation de la taille
 * - Nommage sécurisé
 * - Support de conversion WebP pour images.
 */
class SecureFileUploadService
{
    private const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2 Mo

    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const ALLOWED_DOCUMENT_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $uploadsDirectory
    ) {
    }

    /**
     * Valide et upload un fichier image (avatar, logo, etc.).
     *
     * @throws FileException Si validation échoue
     */
    public function uploadImage(
        UploadedFile $file,
        string $subdirectory = 'avatars',
        bool $convertToWebP = false
    ): string {
        // Validation du fichier
        $this->validateFile($file, self::ALLOWED_IMAGE_MIMES);

        // Génération du nom de fichier sécurisé
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename);
        $extension        = $convertToWebP ? 'webp' : $file->guessExtension();
        $newFilename      = sprintf('%s-%s.%s', $safeFilename, uniqid(), $extension);

        // Création du répertoire si nécessaire
        $targetDirectory = sprintf('%s/%s', $this->uploadsDirectory, $subdirectory);
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        // Upload du fichier
        try {
            $file->move($targetDirectory, $newFilename);

            // Conversion WebP si demandée
            if ($convertToWebP && extension_loaded('gd')) {
                $this->convertToWebP($targetDirectory.'/'.$newFilename);
            }

            return $newFilename;
        } catch (Exception $e) {
            throw new FileException(sprintf('Impossible d\'uploader le fichier: %s', $e->getMessage()));
        }
    }

    /**
     * Valide et upload un document.
     */
    public function uploadDocument(
        UploadedFile $file,
        string $subdirectory = 'documents'
    ): string {
        $this->validateFile($file, self::ALLOWED_DOCUMENT_MIMES);

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename);
        $newFilename      = sprintf('%s-%s.%s', $safeFilename, uniqid(), $file->guessExtension());

        $targetDirectory = sprintf('%s/%s', $this->uploadsDirectory, $subdirectory);
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        try {
            $file->move($targetDirectory, $newFilename);

            return $newFilename;
        } catch (Exception $e) {
            throw new FileException(sprintf('Impossible d\'uploader le document: %s', $e->getMessage()));
        }
    }

    /**
     * Validation stricte du fichier.
     *
     * @throws FileException
     */
    private function validateFile(UploadedFile $file, array $allowedMimes): void
    {
        // Vérification de la taille
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new FileException(sprintf('Le fichier est trop volumineux (%.2f Mo). Taille maximale: %.2f Mo', $file->getSize() / 1024 / 1024, self::MAX_FILE_SIZE / 1024 / 1024));
        }

        // Vérification du type MIME réel (pas juste l'extension)
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file->getPathname());
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimes, true)) {
            throw new FileException(sprintf('Type de fichier non autorisé: %s. Types acceptés: %s', $mimeType, implode(', ', $allowedMimes)));
        }
    }

    /**
     * Convertit une image en WebP pour optimisation.
     */
    private function convertToWebP(string $filePath): void
    {
        if (!extension_loaded('gd')) {
            return; // GD non disponible, on skip
        }

        $info = getimagesize($filePath);
        if ($info === false) {
            return;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG  => imagecreatefrompng($filePath),
            IMAGETYPE_GIF  => imagecreatefromgif($filePath),
            default        => null,
        };

        if ($image === null) {
            return;
        }

        // Conversion en WebP (qualité 85%)
        $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filePath);
        imagewebp($image, $webpPath, 85);
        imagedestroy($image);

        // Suppression de l'original
        if ($webpPath !== $filePath && file_exists($webpPath)) {
            unlink($filePath);
        }
    }

    /**
     * Supprime un fichier uploadé.
     */
    public function deleteFile(string $filename, string $subdirectory): bool
    {
        $filePath = sprintf('%s/%s/%s', $this->uploadsDirectory, $subdirectory, $filename);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }
}
