<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\NpsSurvey;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NpsMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    /**
     * Envoie une enquête NPS par email.
     */
    public function sendNpsSurvey(NpsSurvey $survey): void
    {
        $surveyUrl = $this->urlGenerator->generate(
            'nps_public_respond',
            ['token' => $survey->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = new TemplatedEmail()
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($survey->getRecipientEmail(), $survey->getRecipientName() ?? ''))
            ->subject('Votre avis compte : partagez votre expérience avec nous')
            ->htmlTemplate('emails/nps_survey.html.twig')
            ->context([
                'survey'        => $survey,
                'surveyUrl'     => $surveyUrl,
                'recipientName' => $survey->getRecipientName(),
                'projectName'   => $survey->getProject()->getName(),
                'expiresAt'     => $survey->getExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un rappel pour une enquête NPS en attente.
     */
    public function sendNpsReminder(NpsSurvey $survey): void
    {
        $surveyUrl = $this->urlGenerator->generate(
            'nps_public_respond',
            ['token' => $survey->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = new TemplatedEmail()
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($survey->getRecipientEmail(), $survey->getRecipientName() ?? ''))
            ->subject('Rappel : votre avis nous intéresse')
            ->htmlTemplate('emails/nps_reminder.html.twig')
            ->context([
                'survey'        => $survey,
                'surveyUrl'     => $surveyUrl,
                'recipientName' => $survey->getRecipientName(),
                'projectName'   => $survey->getProject()->getName(),
                'expiresAt'     => $survey->getExpiresAt(),
            ]);

        $this->mailer->send($email);
    }
}
