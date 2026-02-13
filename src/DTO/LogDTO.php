<?php

namespace App\DTO;

use App\Enum\StatusEnum;
use App\Enum\WorkLogType;

final class LogDTO
{
    public function __construct(
        public \DateTimeInterface $date,
        public ?\DateTimeInterface $startTime,
        public ?\DateTimeInterface $endTime,
        public int $workedMinutes,
        public StatusEnum $status,
        public ?WorkLogType $type = WorkLogType::WORK,
    ) {
    }
}
