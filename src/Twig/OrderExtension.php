<?php

namespace App\Twig;

use App\Enum\OrderStatus;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class OrderExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('order_status_label', [$this, 'getStatusLabel']),
            new TwigFilter('order_status_badge_class', [$this, 'getStatusBadgeClass']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('order_status_badge', [$this, 'renderStatusBadge'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Obtient le label d'un statut.
     */
    public function getStatusLabel(string $status): string
    {
        $orderStatus = OrderStatus::fromString($status);

        return $orderStatus?->getLabel() ?? $status;
    }

    /**
     * Obtient la classe CSS Bootstrap pour le badge d'un statut.
     */
    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'signe', 'gagne', 'termine' => 'success',
            'a_signer' => 'warning',
            'perdu'    => 'danger',
            'standby', 'abandonne' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Rend un badge HTML pour un statut.
     */
    public function renderStatusBadge(string $status): string
    {
        $label      = $this->getStatusLabel($status);
        $badgeClass = $this->getStatusBadgeClass($status);

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
        );
    }
}
