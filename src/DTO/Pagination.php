<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class Pagination
{
    public int $totalPages;
    public bool $hasPrev;
    public bool $hasNext;

    private function __construct(
        public int $currentPage,
        public int $perPage,
        public int $total,
    ) {
        $this->totalPages = $total       > 0 ? (int) ceil($total / $perPage) : 1;
        $this->hasPrev    = $currentPage > 1;
        $this->hasNext    = ($currentPage * $perPage) < $total;
    }

    public static function create(int $page, int $perPage, int $total): self
    {
        return new self($page, $perPage, $total);
    }

    /**
     * @return array{current_page: int, per_page: int, total: int, total_pages: int, has_prev: bool, has_next: bool}
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'per_page'     => $this->perPage,
            'total'        => $this->total,
            'total_pages'  => $this->totalPages,
            'has_prev'     => $this->hasPrev,
            'has_next'     => $this->hasNext,
        ];
    }
}
