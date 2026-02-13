<?php

namespace App\DTO;

class LeaveRequestDTO
{
    public function __construct(
        public string $start,
        public string $end,
        public string $type,
        public ?int $minutes = null,
    ) {
    }
}
