<?php

namespace App\Service;

class PaginatedResult
{
    private array $items;
    private int $totalItems;
    private int $currentPage;
    private int $itemsPerPage;
    private int $totalPages;

    public function __construct(
        array $items,
        int $totalItems,
        int $currentPage,
        int $itemsPerPage
    ) {
        $this->items = $items;
        $this->totalItems = $totalItems;
        $this->currentPage = $currentPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->totalPages = $itemsPerPage > 0 ? (int) ceil($totalItems / $itemsPerPage) : 0;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    public function getNextPage(): int
    {
        return min($this->totalPages, $this->currentPage + 1);
    }

    public function getPageRange(int $maxPages = 5): array
    {
        if ($this->totalPages <= $maxPages) {
            return range(1, $this->totalPages);
        }

        $sidePages = (int) floor(($maxPages - 1) / 2);
        $startPage = max(1, $this->currentPage - $sidePages);
        $endPage = min($this->totalPages, $startPage + $maxPages - 1);

        if ($endPage - $startPage + 1 < $maxPages) {
            $startPage = max(1, $endPage - $maxPages + 1);
        }

        return range($startPage, $endPage);
    }

    public function getFirstPageInRange(): int
    {
        return min($this->getPageRange());
    }

    public function getLastPageInRange(): int
    {
        return max($this->getPageRange());
    }

    public function shouldShowFirstPage(): bool
    {
        return $this->getFirstPageInRange() > 1;
    }

    public function shouldShowLastPage(): bool
    {
        return $this->getLastPageInRange() < $this->totalPages;
    }

    public function shouldShowLeftDots(): bool
    {
        return $this->getFirstPageInRange() > 2;
    }

    public function shouldShowRightDots(): bool
    {
        return $this->getLastPageInRange() < ($this->totalPages - 1);
    }
}
