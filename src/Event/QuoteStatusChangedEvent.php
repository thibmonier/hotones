<?php

namespace App\Event;

use App\Entity\Order;
use App\Enum\NotificationType;
use App\Enum\OrderStatus;
use InvalidArgumentException;

class QuoteStatusChangedEvent extends NotificationEvent
{
    public function __construct(
        private readonly Order $order,
        private readonly OrderStatus $newStatus,
        array $recipients
    ) {
        $type = match ($newStatus) {
            OrderStatus::WON     => NotificationType::QUOTE_WON,
            OrderStatus::LOST    => NotificationType::QUOTE_LOST,
            OrderStatus::PENDING => NotificationType::QUOTE_TO_SIGN,
            default              => throw new InvalidArgumentException('Invalid status for notification: '.$newStatus->value),
        };

        $title = match ($newStatus) {
            OrderStatus::WON     => 'Devis gagné !',
            OrderStatus::LOST    => 'Devis perdu',
            OrderStatus::PENDING => 'Nouveau devis à signer',
            default              => '',
        };

        $message = match ($newStatus) {
            OrderStatus::WON => sprintf(
                'Le devis "%s" (#%s) a été gagné pour un montant de %s €.',
                $order->getTitle(),
                $order->getReference(),
                number_format($order->calculateTotal(), 2, ',', ' '),
            ),
            OrderStatus::LOST => sprintf(
                'Le devis "%s" (#%s) a été perdu.',
                $order->getTitle(),
                $order->getReference(),
            ),
            OrderStatus::PENDING => sprintf(
                'Un nouveau devis "%s" (#%s) est en attente de signature pour un montant de %s €.',
                $order->getTitle(),
                $order->getReference(),
                number_format($order->calculateTotal(), 2, ',', ' '),
            ),
            default => '',
        };

        parent::__construct(
            type: $type,
            title: $title,
            message: $message,
            recipients: $recipients,
            data: [
                'order_id'        => $order->getId(),
                'order_reference' => $order->getReference(),
                'status'          => $newStatus->value,
                'amount'          => $order->calculateTotal(),
            ],
            entityType: 'Order',
            entityId: $order->getId(),
        );
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getNewStatus(): OrderStatus
    {
        return $this->newStatus;
    }
}
