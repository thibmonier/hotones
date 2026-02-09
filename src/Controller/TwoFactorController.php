<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TwoFactorController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED_2FA_IN_PROGRESS')]
    public function form(Request $request): Response
    {
        return $this->render('security/2fa_form.html.twig');
    }

    #[IsGranted('IS_AUTHENTICATED_2FA_IN_PROGRESS')]
    public function check(): Response
    {
        // This will be intercepted by the 2FA bundle.
        return new Response('', 204);
    }
}
