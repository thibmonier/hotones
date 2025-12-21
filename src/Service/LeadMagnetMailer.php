<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\LeadCapture;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LeadMagnetMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    /**
     * Envoie l'email de bienvenue avec le lien de téléchargement du guide KPIs.
     */
    public function sendGuideKpisEmail(LeadCapture $lead): void
    {
        $downloadUrl = $this->urlGenerator->generate(
            'lead_magnet_download_guide_kpis',
            ['email' => base64_encode($lead->getEmail())],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $thankYouUrl = $this->urlGenerator->generate(
            'lead_magnet_thank_you',
            ['email' => base64_encode($lead->getEmail())],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($lead->getEmail(), $lead->getFullName()))
            ->subject('🎁 Votre guide "15 KPIs pour Agences Web" est prêt !')
            ->htmlTemplate('emails/lead_magnet/guide_kpis.html.twig')
            ->textTemplate('emails/lead_magnet/guide_kpis.txt.twig')
            ->context([
                'lead'        => $lead,
                'firstName'   => $lead->getFirstName(),
                'downloadUrl' => $downloadUrl,
                'thankYouUrl' => $thankYouUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de nurturing J+1 : "Avez-vous lu le guide ?".
     */
    public function sendNurturingDay1(LeadCapture $lead): void
    {
        $analyticsUrl = $this->urlGenerator->generate(
            'public_features_analytics',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($lead->getEmail(), $lead->getFullName()))
            ->subject('💡 Avez-vous consulté votre guide KPIs ?')
            ->htmlTemplate('emails/lead_magnet/nurturing_day1.html.twig')
            ->textTemplate('emails/lead_magnet/nurturing_day1.txt.twig')
            ->context([
                'lead'         => $lead,
                'firstName'    => $lead->getFirstName(),
                'analyticsUrl' => $analyticsUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de nurturing J+3 : "Découvrez comment HotOnes automatise ces KPIs".
     */
    public function sendNurturingDay3(LeadCapture $lead): void
    {
        $analyticsUrl = $this->urlGenerator->generate(
            'public_features_analytics',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $pricingUrl = $this->urlGenerator->generate(
            'public_pricing',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($lead->getEmail(), $lead->getFullName()))
            ->subject('🚀 Comment automatiser le calcul de vos KPIs ?')
            ->htmlTemplate('emails/lead_magnet/nurturing_day3.html.twig')
            ->textTemplate('emails/lead_magnet/nurturing_day3.txt.twig')
            ->context([
                'lead'         => $lead,
                'firstName'    => $lead->getFirstName(),
                'analyticsUrl' => $analyticsUrl,
                'pricingUrl'   => $pricingUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de nurturing J+7 : "Essai gratuit 14 jours".
     */
    public function sendNurturingDay7(LeadCapture $lead): void
    {
        $pricingUrl = $this->urlGenerator->generate(
            'public_pricing',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($lead->getEmail(), $lead->getFullName()))
            ->subject('🎯 Testez HotOnes gratuitement pendant 14 jours')
            ->htmlTemplate('emails/lead_magnet/nurturing_day7.html.twig')
            ->textTemplate('emails/lead_magnet/nurturing_day7.txt.twig')
            ->context([
                'lead'       => $lead,
                'firstName'  => $lead->getFirstName(),
                'pricingUrl' => $pricingUrl,
            ]);

        $this->mailer->send($email);
    }
}
