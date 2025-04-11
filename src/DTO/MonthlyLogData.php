<?php

namespace App\DTO;

class MonthlyLogData
{
    public function __construct(
        private readonly string $monthYear,
        private readonly string $monthYearDetail,
        private readonly string $total,
        private readonly string $solde,
        private readonly array $entries
    ) {
    }

    public function getMonthYear(): string
    {
        return $this->monthYear;
    }

    public function getMonthYearDetail(): string
    {
        return $this->monthYearDetail;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function getSolde(): string
    {
        return $this->solde;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            monthYear: $data['monthYear'],
            monthYearDetail: $data['monthYearDetail'],
            total: $data['total'],
            solde: $data['solde'],
            entries: $data['entries']
        );
    }
}
