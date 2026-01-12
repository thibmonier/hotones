<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublicController extends AbstractController
{
    #[Route('/', name: 'public_homepage', options: ['sitemap' => true])]
    public function homepage(): Response
    {
        // Redirect authenticated users to the app
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        return $this->render('public/homepage.html.twig');
    }

    #[Route('/features', name: 'public_features', options: ['sitemap' => true])]
    public function features(): Response
    {
        return $this->render('public/features.html.twig');
    }

    #[Route('/features/time-tracking', name: 'public_features_time_tracking', options: ['sitemap' => true])]
    public function featureTimeTracking(): Response
    {
        return $this->render('public/features/time_tracking.html.twig');
    }

    #[Route('/features/project-management', name: 'public_features_project_management', options: ['sitemap' => true])]
    public function featureProjectManagement(): Response
    {
        return $this->render('public/features/project_management.html.twig');
    }

    #[Route('/features/analytics', name: 'public_features_analytics', options: ['sitemap' => true])]
    public function featureAnalytics(): Response
    {
        return $this->render('public/features/analytics.html.twig');
    }

    #[Route('/features/planning', name: 'public_features_planning', options: ['sitemap' => true])]
    public function featurePlanning(): Response
    {
        return $this->render('public/features/planning.html.twig');
    }

    #[Route('/pricing', name: 'public_pricing', options: ['sitemap' => true])]
    public function pricing(): Response
    {
        return $this->render('public/pricing.html.twig');
    }

    #[Route('/public/about', name: 'public_about', options: ['sitemap' => true])]
    public function publicAbout(): Response
    {
        return $this->render('public/about.html.twig');
    }

    #[Route('/legal', name: 'public_legal', options: ['sitemap' => true])]
    public function legal(): Response
    {
        return $this->render('public/legal.html.twig');
    }

    #[Route('/contact', name: 'public_contact', options: ['sitemap' => true])]
    public function contact(Request $request): Response
    {
        // For now, just display the form
        // TODO: Implement actual contact form submission if needed
        return $this->render('public/contact.html.twig');
    }
}
