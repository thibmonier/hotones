<?php

declare(strict_types=1);

namespace App\Command;

use Exception;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:test-s3-connection',
    description: 'Test la connexion au stockage S3/R2 et affiche les informations de configuration',
)]
class TestS3ConnectionCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'oneup_flysystem.default_filesystem')]
        private readonly FilesystemOperator $filesystem,
        #[Autowire(param: 'env(S3_PUBLIC_URL)')]
        private readonly string $publicUrl = '',
        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment = 'dev',
        #[Autowire(param: 'env(S3_BUCKET)')]
        private readonly string $bucket = '',
        #[Autowire(param: 'env(S3_ENDPOINT)')]
        private readonly string $endpoint = '',
        #[Autowire(param: 'env(S3_REGION)')]
        private readonly string $region = '',
        #[Autowire(param: 'env(FILESYSTEM_ADAPTER)')]
        private readonly string $adapter = '',
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de la connexion S3/R2');

        // Afficher la configuration
        $io->section('Configuration');
        $io->table(['Variable', 'Valeur'], [
            ['APP_ENV', $this->environment],
            ['FILESYSTEM_ADAPTER', $this->adapter],
            ['S3_BUCKET', $this->bucket ?: '(vide)'],
            ['S3_ENDPOINT', $this->endpoint ?: '(vide)'],
            ['S3_REGION', $this->region ?: '(vide)'],
            ['S3_PUBLIC_URL', $this->publicUrl ?: '(vide)'],
        ]);

        // Test 1 : Lister les fichiers
        $io->section('Test 1 : Liste des fichiers dans /avatars');
        try {
            $listing = $this->filesystem->listContents('avatars', false);
            $count   = 0;
            foreach ($listing as $item) {
                ++$count;
                if ($count <= 5) {
                    $io->writeln(sprintf('  - %s (%s)', $item->path(), $item->type()));
                }
            }
            $io->success(sprintf('✓ Listing réussi : %d fichier(s) trouvé(s)', $count));
        } catch (Exception $e) {
            $io->error(sprintf('✗ Erreur listing : %s', $e->getMessage()));
            $io->writeln('Trace : '.$e->getTraceAsString());

            return Command::FAILURE;
        }

        // Test 2 : Écrire un fichier de test
        $io->section('Test 2 : Écriture d\'un fichier de test');
        $testContent = 'Test file created at '.date('Y-m-d H:i:s');
        $testPath    = 'avatars/test-'.time().'.txt';

        try {
            $this->filesystem->write($testPath, $testContent);
            $io->success('✓ Écriture réussie : '.$testPath);
        } catch (Exception $e) {
            $io->error(sprintf('✗ Erreur écriture : %s', $e->getMessage()));
            $io->writeln('Trace : '.$e->getTraceAsString());

            return Command::FAILURE;
        }

        // Test 3 : Lire le fichier de test
        $io->section('Test 3 : Lecture du fichier de test');
        try {
            $content = $this->filesystem->read($testPath);
            if ($content === $testContent) {
                $io->success('✓ Lecture réussie : contenu correct');
            } else {
                $io->warning('⚠ Lecture réussie mais contenu différent');
            }
        } catch (Exception $e) {
            $io->error(sprintf('✗ Erreur lecture : %s', $e->getMessage()));

            return Command::FAILURE;
        }

        // Test 4 : Supprimer le fichier de test
        $io->section('Test 4 : Suppression du fichier de test');
        try {
            $this->filesystem->delete($testPath);
            $io->success('✓ Suppression réussie');
        } catch (Exception $e) {
            $io->error(sprintf('✗ Erreur suppression : %s', $e->getMessage()));

            return Command::FAILURE;
        }

        // Test 5 : Vérifier l'URL publique
        $io->section('Test 5 : URL publique');
        if ($this->environment === 'prod' && $this->publicUrl !== '') {
            $examplePath = 'avatars/example.jpg';
            $publicUrl   = sprintf('%s/%s', rtrim($this->publicUrl, '/'), $examplePath);
            $io->info('URL publique générée : '.$publicUrl);
            $io->note('Vérifiez que cette URL est accessible publiquement depuis votre navigateur');
        } else {
            $io->info('Mode développement : les fichiers sont servis localement');
        }

        $io->success('Tous les tests sont passés avec succès !');

        return Command::SUCCESS;
    }
}
