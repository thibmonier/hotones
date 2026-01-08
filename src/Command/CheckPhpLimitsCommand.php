<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-php-limits',
    description: 'Affiche les limites PHP pour les uploads de fichiers',
    aliases: ['hotones:check-php-limits'],
)]
class CheckPhpLimitsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Limites PHP pour les uploads');

        $limits = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'memory_limit'        => ini_get('memory_limit'),
            'max_execution_time'  => ini_get('max_execution_time'),
            'max_input_time'      => ini_get('max_input_time'),
            'file_uploads'        => ini_get('file_uploads') ? 'Activé' : 'Désactivé',
            'max_file_uploads'    => ini_get('max_file_uploads'),
            'upload_tmp_dir'      => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
            'tmp_dir_writable'    => is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) ? '✓ Oui' : '✗ Non',
        ];

        $io->table(['Configuration', 'Valeur'], array_map(fn ($k, $v): array => [$k, $v], array_keys($limits), $limits));

        // Vérifications
        $io->section('Vérifications');

        $uploadMaxBytes = $this->parseSize(ini_get('upload_max_filesize'));
        $postMaxBytes   = $this->parseSize(ini_get('post_max_size'));
        $memoryBytes    = $this->parseSize(ini_get('memory_limit'));

        if ($uploadMaxBytes < 2 * 1024 * 1024) {
            $io->warning(sprintf('upload_max_filesize (%s) est inférieur à 2M - les avatars de 2M seront rejetés !', ini_get('upload_max_filesize')));
        } else {
            $io->success(sprintf('✓ upload_max_filesize (%s) est suffisant pour des fichiers de 2M', ini_get('upload_max_filesize')));
        }

        if ($postMaxBytes < $uploadMaxBytes) {
            $io->error(sprintf('post_max_size (%s) doit être >= upload_max_filesize (%s)', ini_get('post_max_size'), ini_get('upload_max_filesize')));
        } else {
            $io->success(sprintf('✓ post_max_size (%s) est correct', ini_get('post_max_size')));
        }

        if ($memoryBytes !== -1 && $memoryBytes < $postMaxBytes) {
            $io->warning(sprintf('memory_limit (%s) pourrait être insuffisant', ini_get('memory_limit')));
        }

        if (!ini_get('file_uploads')) {
            $io->error('✗ file_uploads est désactivé - les uploads sont impossibles !');
        } else {
            $io->success('✓ file_uploads est activé');
        }

        $tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        if (!is_writable($tmpDir)) {
            $io->error(sprintf('✗ Le répertoire temporaire %s n\'est pas accessible en écriture', $tmpDir));
        } else {
            $io->success(sprintf('✓ Répertoire temporaire accessible : %s', $tmpDir));
        }

        return Command::SUCCESS;
    }

    private function parseSize(string $size): int
    {
        if ($size === '-1') {
            return -1;
        }

        $unit = strtoupper(substr($size, -1));
        $num  = (int) substr($size, 0, -1);

        return match ($unit) {
            'G'     => $num * 1024 * 1024 * 1024,
            'M'     => $num * 1024 * 1024,
            'K'     => $num * 1024,
            default => (int) $size,
        };
    }
}
