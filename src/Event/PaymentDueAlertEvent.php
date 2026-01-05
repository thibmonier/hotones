<?php

namespace App\Event;

use App\Entity\Order;
use App\Enum\NotificationType;
use DateTimeInterface;

class PaymentDueAlertEvent extends NotificationEvent
{
    public function __construct(
        private readonly Order $order,
        private readonly DateTimeInterface $dueDate,
        private readonly int $daysUntilDue,
        array $recipients
    ) {
        $title   = 'Échéance de paiement proche';
        $message = sprintf(
            'Le paiement pour le devis "%s" (#%s) est prévu dans %d jour(s) (le %s).',
            $order->getTitle(),
            $order->getReference(),
            $daysUntilDue,
            $dueDate->format('d/m/Y'),
        );

        parent::__construct(
            type: NotificationType::PAYMENT_DUE_ALERT,
            title: $title,
            message: $message,
            recipients: $recipients,
            data: [
                'order_id'        => $order->id,
                'order_reference' => $order->getReference(),
                'due_date'        => $dueDate->format('Y-m-d'),
                'days_until_due'  => $daysUntilDue,
            ],
            entityType: 'Order',
            entityId: $order->id,
        );
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getDueDate(): DateTimeInterface
    {
        return $this->dueDate;
    }

    public function getDaysUntilDue(): int
    {
        return $this->daysUntilDue;
    }
}
