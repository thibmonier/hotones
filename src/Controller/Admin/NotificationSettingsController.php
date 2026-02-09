<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\NotificationSettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/notifications')]
#[IsGranted('ROLE_MANAGER')]
class NotificationSettingsController extends AbstractController
{
    #[Route('/settings', name: 'admin_notification_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request, NotificationSettingRepository $repo): Response
    {
        $currentTolerance = (float) $repo->getValue(
            NotificationSettingRepository::KEY_TIMESHEET_WEEKLY_TOLERANCE,
            0.15,
        );

        if ($request->isMethod('POST')) {
            $t   = (string) $request->request->get('timesheet_weekly_tolerance', '0.15');
            $val = max(0.0, min(0.5, (float) $t)); // clamp 0..0.5
            $repo->setValue(NotificationSettingRepository::KEY_TIMESHEET_WEEKLY_TOLERANCE, $val);
            $this->addFlash('success', 'Tolérance mise à jour');

            return $this->redirectToRoute('admin_notification_settings');
        }

        return $this->render('admin/notifications/settings.html.twig', [
            'timesheet_weekly_tolerance' => $currentTolerance,
        ]);
    }
}
