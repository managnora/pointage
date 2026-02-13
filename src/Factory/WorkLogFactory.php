<?php

namespace App\Factory;

use App\DTO\LogDTO;
use App\Entity\WorkLog;
use App\Enum\LogSource;

class WorkLogFactory
{
    public static function createFromDto(LogDTO $dto): WorkLog
    {
        return new WorkLog(
            $dto->date,
            $dto->startTime,
            $dto->endTime,
            $dto->workedMinutes,
            $dto->type,
            $dto->status,
            LogSource::FILE
        );
    }
}
