<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\SecureFileUploadService;
use Exception;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

/**
 * Unit tests for SecureFileUploadService.
 *
 * Coverage: All public methods + security validation
 * P0 Priority: 0.97% → 80%+ coverage target
 */
class SecureFileUploadServiceTest extends TestCase
{
    private SecureFileUploadService $service;
    private \PHPUnit\Framework\MockObject\MockObject $slugger;
    private \PHPUnit\Framework\MockObject\MockObject $filesystem;
    private \PHPUnit\Framework\MockObject\MockObject $logger;

    protected function setUp(): void
    {
        $this->slugger    = $this->createMock(SluggerInterface::class);
        $this->filesystem = $this->createMock(FilesystemOperator::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        // Default service instance (dev environment)
        $this->service = new SecureFileUploadService(
            $this->slugger,
            $this->filesystem,
            $this->logger,
            publicUrl: '',
            environment: 'dev',
        );
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testUploadImageWithValidJpegSucceeds(): void
    {
        // Given: a valid JPEG file
        $file = $this->createValidUploadedFile('test.jpg', 'image/jpeg', 1024);

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('test')
            ->willReturn(new UnicodeString('test'));

        $this->filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->matchesRegularExpression('#avatars/test-[a-z0-9]+\.jpg#'),
                $this->anything(),
            );

        // When: upload image
        $result = $this->service->uploadImage($file);

        // Then: filename returned
        $this->assertMatchesRegularExpression('/^test-[a-z0-9]+\.jpg$/', $result);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testUploadImageWithValidPngSucceeds(): void
    {
        // Given: a valid PNG file
        $file = $this->createValidUploadedFile('logo.png', 'image/png', 2048);

        $this->slugger->method('slug')->willReturn(new UnicodeString('logo'));
        $this->filesystem->expects($this->once())->method('writeStream');

        // When: upload image to custom subdirectory
        $result = $this->service->uploadImage($file, 'logos');

        // Then: filename with PNG extension
        $this->assertMatchesRegularExpression('/^logo-[a-z0-9]+\.png$/', $result);
    }

    /**
     * @group secure_upload
     * @group p0
     * @group security
     */
    public function testUploadImageWithFileTooLargeThrowsException(): void
    {
        // Given: a file larger than 2MB (2097152 bytes)
        $file = $this->createValidUploadedFile('huge.jpg', 'image/jpeg', 3 * 1024 * 1024);

        // When/Then: exception thrown for oversized file
        $this->expectException(FileException::class);
        $this->expectExceptionMessageMatches('/trop volumineux/');

        $this->service->uploadImage($file);
    }

    /**
     * @group secure_upload
     * @group p0
     * @group security
     */
    public function testUploadImageWithInvalidMimeTypeThrowsException(): void
    {
        // Given: a file with invalid MIME type (text/plain pretending to be image)
        $file = $this->createMock(UploadedFile::class);
        $file->method('getSize')->willReturn(1024);
        $file->method('getClientOriginalName')->willReturn('malicious.jpg');

        // Create a real temp file with text content
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'This is not an image');
        $file->method('getPathname')->willReturn($tempFile);

        try {
            // When/Then: exception thrown for invalid MIME type
            $this->expectException(FileException::class);
            $this->expectExceptionMessageMatches('/Type de fichier non autorisé/');

            $this->service->uploadImage($file);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testUploadImageWithFilesystemErrorThrowsException(): void
    {
        // Given: a valid file but filesystem throws exception
        $file = $this->createValidUploadedFile('test.jpg', 'image/jpeg', 1024);

        $this->slugger->method('slug')->willReturn(new UnicodeString('test'));
        $this->filesystem
            ->method('writeStream')
            ->willThrowException(new Exception('Filesystem error'));

        // When/Then: exception thrown when filesystem fails
        $this->expectException(FileException::class);
        $this->expectExceptionMessageMatches('/Impossible d\'uploader le fichier/');

        $this->service->uploadImage($file);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testUploadDocumentWithValidPdfSucceeds(): void
    {
        // Given: a valid PDF file
        $file = $this->createValidUploadedFile('contract.pdf', 'application/pdf', 512 * 1024);

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('contract')
            ->willReturn(new UnicodeString('contract'));

        $this->filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->matchesRegularExpression('#documents/contract-[a-z0-9]+\.pdf#'),
                $this->anything(),
            );

        // When: upload document
        $result = $this->service->uploadDocument($file);

        // Then: filename returned
        $this->assertMatchesRegularExpression('/^contract-[a-z0-9]+\.pdf$/', $result);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testUploadDocumentWithMultiplePdfsGeneratesUniqueNames(): void
    {
        // Given: two PDF files with same name
        $file1 = $this->createValidUploadedFile('report.pdf', 'application/pdf', 128 * 1024);
        $file2 = $this->createValidUploadedFile('report.pdf', 'application/pdf', 256 * 1024);

        $this->slugger->method('slug')->willReturn(new UnicodeString('report'));
        $this->filesystem->method('writeStream');

        // When: upload both documents
        $result1 = $this->service->uploadDocument($file1, 'reports');
        $result2 = $this->service->uploadDocument($file2, 'reports');

        // Then: different filenames generated
        $this->assertNotEquals($result1, $result2);
        $this->assertStringStartsWith('report-', $result1);
        $this->assertStringStartsWith('report-', $result2);
    }

    /**
     * @group secure_upload
     * @group p0
     * @group security
     */
    public function testUploadDocumentWithImageFileThrowsException(): void
    {
        // Given: an image file (not allowed for documents)
        $file = $this->createValidUploadedFile('image.jpg', 'image/jpeg', 1024);

        // When/Then: exception thrown for wrong file type
        $this->expectException(FileException::class);
        $this->expectExceptionMessageMatches('/Type de fichier non autorisé/');

        $this->service->uploadDocument($file);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testDeleteFileWhenFileExistsReturnsTrue(): void
    {
        // Given: a file that exists
        $this->filesystem
            ->expects($this->once())
            ->method('fileExists')
            ->with('avatars/test-123.jpg')
            ->willReturn(true);

        $this->filesystem
            ->expects($this->once())
            ->method('delete')
            ->with('avatars/test-123.jpg');

        // When: delete file
        $result = $this->service->deleteFile('test-123.jpg', 'avatars');

        // Then: true returned
        $this->assertTrue($result);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testDeleteFileWhenFileDoesNotExistReturnsFalse(): void
    {
        // Given: a file that does not exist
        $this->filesystem
            ->expects($this->once())
            ->method('fileExists')
            ->with('documents/missing.pdf')
            ->willReturn(false);

        $this->filesystem
            ->expects($this->never())
            ->method('delete');

        // When: attempt to delete non-existent file
        $result = $this->service->deleteFile('missing.pdf', 'documents');

        // Then: false returned
        $this->assertFalse($result);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testDeleteFileWhenFilesystemThrowsExceptionReturnsFalse(): void
    {
        // Given: filesystem throws exception
        $this->filesystem
            ->method('fileExists')
            ->willThrowException(new Exception('Filesystem error'));

        // When: attempt to delete file
        $result = $this->service->deleteFile('test.jpg', 'avatars');

        // Then: false returned (error handled gracefully)
        $this->assertFalse($result);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testGetPublicUrlInDevEnvironmentReturnsLocalPath(): void
    {
        // Given: dev environment (default)
        // service already initialized with environment: 'dev'

        // When: get public URL
        $url = $this->service->getPublicUrl('avatar-123.jpg', 'avatars');

        // Then: local path returned
        $this->assertSame('/uploads/avatars/avatar-123.jpg', $url);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testGetPublicUrlInProdEnvironmentReturnsS3Url(): void
    {
        // Given: production environment with S3 public URL
        $service = new SecureFileUploadService(
            $this->slugger,
            $this->filesystem,
            $this->logger,
            publicUrl: 'https://cdn.example.com',
            environment: 'prod',
        );

        // When: get public URL
        $url = $service->getPublicUrl('document-456.pdf', 'documents');

        // Then: S3 URL returned
        $this->assertSame('https://cdn.example.com/documents/document-456.pdf', $url);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testGetPublicUrlInProdWithTrailingSlashHandlesCorrectly(): void
    {
        // Given: production with trailing slash in publicUrl
        $service = new SecureFileUploadService(
            $this->slugger,
            $this->filesystem,
            $this->logger,
            publicUrl: 'https://cdn.example.com/',
            environment: 'prod',
        );

        // When: get public URL
        $url = $service->getPublicUrl('file.jpg', 'images');

        // Then: no double slashes in URL
        $this->assertSame('https://cdn.example.com/images/file.jpg', $url);
        $this->assertStringNotContainsString('//', substr($url, 8)); // Skip https://
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testUploadImageSanitizesFilename(): void
    {
        // Given: a file with unsafe characters in name
        $file = $this->createValidUploadedFile(
            '../../../etc/passwd.jpg', // Path traversal attempt
            'image/jpeg',
            1024,
        );

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('passwd') // pathinfo extracts only filename, not path
            ->willReturn(new UnicodeString('passwd-sanitized')); // Slugger sanitizes

        $this->filesystem->expects($this->once())->method('writeStream');

        // When: upload image
        $result = $this->service->uploadImage($file);

        // Then: filename is sanitized (no path traversal, pathinfo stripped the path)
        $this->assertMatchesRegularExpression('/^passwd-sanitized-[a-z0-9]+\.jpg$/', $result);
        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);
    }

    /**
     * @group secure_upload
     * @group p0
     */
    public function testUploadImageGeneratesUniqueFilenames(): void
    {
        // Given: same file uploaded twice
        $file1 = $this->createValidUploadedFile('test.jpg', 'image/jpeg', 1024);
        $file2 = $this->createValidUploadedFile('test.jpg', 'image/jpeg', 1024);

        $this->slugger->method('slug')->willReturn(new UnicodeString('test'));
        $this->filesystem->method('writeStream');

        // When: upload twice
        $result1 = $this->service->uploadImage($file1);
        $result2 = $this->service->uploadImage($file2);

        // Then: different filenames (thanks to uniqid())
        $this->assertNotEquals($result1, $result2);
        $this->assertStringStartsWith('test-', $result1);
        $this->assertStringStartsWith('test-', $result2);
    }

    // ========== Helper Methods ==========

    /**
     * Creates a valid mock UploadedFile for testing.
     */
    private function createValidUploadedFile(string $filename, string $mimeType, int $size): UploadedFile
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getSize')->willReturn($size);
        $file->method('getClientOriginalName')->willReturn($filename);
        $file->method('guessExtension')->willReturn(pathinfo($filename, PATHINFO_EXTENSION));

        // Create a real temp file with appropriate content based on MIME type
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload_');

        // Generate minimal valid file content based on MIME type
        $content = match (true) {
            str_starts_with($mimeType, 'image/jpeg')      => $this->createMinimalJpeg(),
            str_starts_with($mimeType, 'image/png')       => $this->createMinimalPng(),
            str_starts_with($mimeType, 'application/pdf') => '%PDF-1.4',
            default                                       => 'test content',
        };

        file_put_contents($tempFile, $content);
        $file->method('getPathname')->willReturn($tempFile);

        // Clean up temp file after test
        register_shutdown_function(static function () use ($tempFile): void {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        });

        return $file;
    }

    /**
     * Creates minimal valid JPEG file content.
     */
    private function createMinimalJpeg(): string
    {
        // Minimal JPEG header + data
        return base64_decode('
            /9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0a
            HBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIy
            MjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIA
            AhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEB
            AQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwABmX/9k=
        ');
    }

    /**
     * Creates minimal valid PNG file content.
     */
    private function createMinimalPng(): string
    {
        // Minimal PNG header + IEND chunk
        return base64_decode('
            iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9
            awAAAABJRU5ErkJggg==
        ');
    }
}
