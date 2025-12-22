<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service de gestion sécurisée des uploads de fichiers avec Flysystem
 * - Validation stricte du type MIME réel (pas juste l'extension)
 * - Validation de la taille
 * - Nommage sécurisé
 * - Support de conversion WebP pour images
 * - Stockage via Flysystem (local en dev, S3/R2 en prod).
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
        #[Autowire(service: 'oneup_flysystem.default_filesystem')]
        private readonly FilesystemOperator $filesystem,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'env(S3_PUBLIC_URL)')]
        private readonly string $publicUrl = '',
        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment = 'dev'
    ) {
    }

    /**
     * Valide et upload un fichier image (avatar, logo, etc.).
     *
     * @throws FileException Si validation échoue
     *
     * @return string Le nom du fichier uploadé
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

        // Chemin complet dans le filesystem
        $filePath = sprintf('%s/%s', $subdirectory, $newFilename);

        try {
            $this->logger->info('Début upload image', [
                'subdirectory' => $subdirectory,
                'filename'     => $newFilename,
                'environment'  => $this->environment,
                'public_url'   => $this->publicUrl,
            ]);

            // Lecture du contenu du fichier uploadé
            $stream = fopen($file->getPathname(), 'r');
            if ($stream === false) {
                throw new FileException('Impossible de lire le fichier uploadé');
            }

            // Upload vers Flysystem (local ou S3)
            $this->filesystem->writeStream($filePath, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $this->logger->info('Upload image réussi', ['filePath' => $filePath]);

            // Conversion WebP si demandée (uniquement en local)
            if ($convertToWebP && extension_loaded('gd') && $this->environment === 'dev') {
                $this->convertToWebP($filePath);
            }

            return $newFilename;
        } catch (Exception $e) {
            $this->logger->error('Erreur upload image', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new FileException(sprintf('Impossible d\'uploader le fichier: %s', $e->getMessage()));
        }
    }

    /**
     * Valide et upload un document.
     *
     * @return string Le nom du fichier uploadé
     */
    public function uploadDocument(
        UploadedFile $file,
        string $subdirectory = 'documents'
    ): string {
        $this->validateFile($file, self::ALLOWED_DOCUMENT_MIMES);

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename);
        $newFilename      = sprintf('%s-%s.%s', $safeFilename, uniqid(), $file->guessExtension());

        $filePath = sprintf('%s/%s', $subdirectory, $newFilename);

        try {
            $this->logger->info('Début upload document', [
                'subdirectory' => $subdirectory,
                'filename'     => $newFilename,
            ]);

            $stream = fopen($file->getPathname(), 'r');
            if ($stream === false) {
                throw new FileException('Impossible de lire le fichier uploadé');
            }

            $this->filesystem->writeStream($filePath, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $this->logger->info('Upload document réussi', ['filePath' => $filePath]);

            return $newFilename;
        } catch (Exception $e) {
            $this->logger->error('Erreur upload document', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
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
     * Convertit une image en WebP pour optimisation (uniquement en local).
     */
    private function convertToWebP(string $filePath): void
    {
        if (!extension_loaded('gd')) {
            return; // GD non disponible, on skip
        }

        try {
            // Lire le fichier depuis Flysystem
            $content  = $this->filesystem->read($filePath);
            $tempFile = tempnam(sys_get_temp_dir(), 'webp_');
            file_put_contents($tempFile, $content);

            $info = getimagesize($tempFile);
            if ($info === false) {
                unlink($tempFile);

                return;
            }

            $image = match ($info[2]) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($tempFile),
                IMAGETYPE_PNG  => imagecreatefrompng($tempFile),
                IMAGETYPE_GIF  => imagecreatefromgif($tempFile),
                default        => null,
            };

            if ($image === null) {
                unlink($tempFile);

                return;
            }

            // Conversion en WebP (qualité 85%)
            $webpTempFile = tempnam(sys_get_temp_dir(), 'webp_result_');
            imagewebp($image, $webpTempFile, 85);
            imagedestroy($image);

            // Upload du fichier WebP
            $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filePath);
            $stream   = fopen($webpTempFile, 'r');
            if ($stream !== false) {
                $this->filesystem->writeStream($webpPath, $stream);
                fclose($stream);

                // Suppression de l'original si différent
                if ($webpPath !== $filePath) {
                    $this->filesystem->delete($filePath);
                }
            }

            // Nettoyage des fichiers temporaires
            unlink($tempFile);
            unlink($webpTempFile);
        } catch (Exception) {
            // En cas d'erreur, on ignore la conversion
        }
    }

    /**
     * Supprime un fichier uploadé.
     */
    public function deleteFile(string $filename, string $subdirectory): bool
    {
        $filePath = sprintf('%s/%s', $subdirectory, $filename);

        try {
            if ($this->filesystem->fileExists($filePath)) {
                $this->filesystem->delete($filePath);

                return true;
            }

            return false;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Obtient l'URL publique d'un fichier.
     */
    public function getPublicUrl(string $filename, string $subdirectory): string
    {
        $filePath = sprintf('%s/%s', $subdirectory, $filename);

        // En production avec S3, utiliser l'URL publique configurée
        if ($this->environment === 'prod' && $this->publicUrl !== '') {
            return sprintf('%s/%s', rtrim($this->publicUrl, '/'), ltrim($filePath, '/'));
        }

        // En dev, utiliser le chemin local
        return sprintf('/uploads/%s', $filePath);
    }
}
