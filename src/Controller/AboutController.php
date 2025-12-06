<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class AboutController extends AbstractController
{
    #[Route('/about', name: 'about', methods: ['GET'])]
    public function index(KernelInterface $kernel): Response
    {
        // Get PHP extensions
        $extensions = [
            'bcmath'    => extension_loaded('bcmath'),
            'redis'     => extension_loaded('redis'),
            'intl'      => extension_loaded('intl'),
            'opcache'   => extension_loaded('Zend OPcache'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
        ];

        // Get environment info
        $environment = [
            'symfony_version' => Kernel::VERSION,
            'php_version'     => PHP_VERSION,
            'environment'     => $kernel->getEnvironment(),
            'debug'           => $kernel->isDebug(),
        ];

        return $this->render('about/index.html.twig', [
            'extensions'  => $extensions,
            'environment' => $environment,
        ]);
    }
}
