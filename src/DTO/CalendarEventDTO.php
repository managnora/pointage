<?php

namespace App\DTO;

class CalendarEventDTO
{
    public function __construct(
        public string $title,
        public string $date,
        public ?string $start,
        public ?string $end,
        public string $color,
        public int $workedMinutes,
        public string $status,
        public string $type,
    ) {
    }
}
