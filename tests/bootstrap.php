<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Force tests to run in the "test" environment
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

if (method_exists(Dotenv::class, 'bootEnv')) {
    new Dotenv()->bootEnv(dirname(__DIR__).'/.env');
}

// Override DATABASE_URL for tests to use SQLite (no Docker dependency)
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = 'sqlite:///%kernel.project_dir%/var/test.db';
putenv('DATABASE_URL=sqlite:///%kernel.project_dir%/var/test.db');

if (!empty($_SERVER['APP_DEBUG'])) {
    umask(0000);
}
