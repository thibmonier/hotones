<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Service de gestion des relances automatiques de factures.
 *
 * Envoie des relances à J+30, J+45, J+60 après l'échéance.
 */
class InvoiceReminderService
{
    private const array REMINDER_DELAYS = [30, 45, 60];

    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $fromEmail = 'facturation@hotones.com',
        private readonly string $fromName = 'HotOnes Agency - Facturation',
    ) {
    }

    /**
     * Traite toutes les relances nécessaires.
     *
     * @return array{sent: int, skipped: int, errors: int}
     */
    public function processAllReminders(bool $dryRun = false): array
    {
        $stats = [
            'sent'    => 0,
            'skipped' => 0,
            'errors'  => 0,
        ];

        $invoices = $this->invoiceRepository->findInvoicesNeedingReminder();

        foreach ($invoices as $invoice) {
            try {
                $result = $this->sendReminder($invoice, $dryRun);
                if ($result) {
                    ++$stats['sent'];
                } else {
                    ++$stats['skipped'];
                }
            } catch (Exception $e) {
                ++$stats['errors'];
                $this->logger->error('Failed to send invoice reminder', [
                    'invoice_id' => $invoice->getId(),
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Envoie une relance pour une facture spécifique.
     */
    public function sendReminder(Invoice $invoice, bool $dryRun = false): bool
    {
        // Vérifier si la facture nécessite une relance
        $daysLate = $this->getDaysLate($invoice);
        if ($daysLate === null || !in_array($daysLate, self::REMINDER_DELAYS, true)) {
            return false;
        }

        // Vérifier si la relance a déjà été envoyée
        if ($this->hasReminderBeenSent($invoice, $daysLate)) {
            $this->logger->info('Reminder already sent', [
                'invoice_id' => $invoice->getId(),
                'days_late'  => $daysLate,
            ]);

            return false;
        }

        if ($dryRun) {
            $this->logger->info('[DRY-RUN] Would send reminder', [
                'invoice_id'     => $invoice->getId(),
                'invoice_number' => $invoice->getInvoiceNumber(),
                'days_late'      => $daysLate,
                'client'         => $invoice->getClient()->getName(),
            ]);

            return true;
        }

        // Envoyer l'email de relance
        $this->sendReminderEmail($invoice, $daysLate);

        // Marquer la relance comme envoyée
        $this->markReminderAsSent($invoice, $daysLate);

        $this->logger->info('Invoice reminder sent', [
            'invoice_id'     => $invoice->getId(),
            'invoice_number' => $invoice->getInvoiceNumber(),
            'days_late'      => $daysLate,
            'client'         => $invoice->getClient()->getName(),
        ]);

        return true;
    }

    /**
     * Envoie l'email de relance.
     */
    private function sendReminderEmail(Invoice $invoice, int $daysLate): void
    {
        $client = $invoice->getClient();

        // Déterminer le type de relance
        $reminderType = match ($daysLate) {
            30      => 'first',
            45      => 'second',
            60      => 'final',
            default => 'final',
        };

        // Récupérer l'email du premier contact actif du client
        $contacts = $client->getContacts()->filter(fn ($contact): bool => $contact->getEmail() !== null);
        if ($contacts->isEmpty()) {
            return; // Pas de contact avec email, impossible d'envoyer la relance
        }
        $primaryContact = $contacts->first();

        $email = new TemplatedEmail()
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($primaryContact->getEmail())
            ->subject(sprintf('Relance facture %s - %s', $invoice->getInvoiceNumber(), $client->getName()))
            ->htmlTemplate('emails/invoice_reminder.html.twig')
            ->context([
                'invoice'       => $invoice,
                'client'        => $client,
                'days_late'     => $daysLate,
                'reminder_type' => $reminderType,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Marque une relance comme envoyée dans les notes internes.
     */
    private function markReminderAsSent(Invoice $invoice, int $daysLate): void
    {
        $now         = new DateTime();
        $reminderLog = sprintf('[RELANCE J+%d] Envoyée le %s', $daysLate, $now->format('d/m/Y à H:i'));

        $currentNotes = $invoice->getInternalNotes() ?? '';
        $updatedNotes = trim($currentNotes."\n".$reminderLog);

        $invoice->setInternalNotes($updatedNotes);
        $this->entityManager->flush();
    }

    /**
     * Vérifie si une relance a déjà été envoyée.
     */
    private function hasReminderBeenSent(Invoice $invoice, int $daysLate): bool
    {
        $notes = $invoice->getInternalNotes();
        if (!$notes) {
            return false;
        }

        $pattern = sprintf('/\[RELANCE J\+%d\]/', $daysLate);

        return preg_match($pattern, $notes) === 1;
    }

    /**
     * Calcule le nombre de jours de retard.
     */
    private function getDaysLate(Invoice $invoice): ?int
    {
        if ($invoice->getStatus() === Invoice::STATUS_PAID || $invoice->getStatus() === Invoice::STATUS_CANCELLED) {
            return null;
        }

        $today   = new DateTime();
        $dueDate = $invoice->getDueDate();

        if ($dueDate >= $today) {
            return null;
        }

        return (int) $today->diff($dueDate)->days;
    }

    /**
     * Récupère les statistiques des relances.
     *
     * @return array{total_reminders: int, by_delay: array<int, int>}
     */
    public function getReminderStats(): array
    {
        $allInvoices = $this->invoiceRepository->findAll();

        $stats = [
            'total_reminders' => 0,
            'by_delay'        => [
                30 => 0,
                45 => 0,
                60 => 0,
            ],
        ];

        foreach ($allInvoices as $invoice) {
            foreach (self::REMINDER_DELAYS as $delay) {
                if ($this->hasReminderBeenSent($invoice, $delay)) {
                    ++$stats['total_reminders'];
                    ++$stats['by_delay'][$delay];
                }
            }
        }

        return $stats;
    }
}
