<?php

namespace App\Service;

use App\Config\TimeConfig;
use App\Entity\WorkLog;
use App\Enum\WorkLogType;

class TimeCalculator
{
    public function calculateDaily(WorkLog $log): int
    {
        return match ($log->getType()) {
            WorkLogType::WORK => $log->getWorkedMinutes(),
            WorkLogType::RECOVERY => -$log->getWorkedMinutes(),
            default => 0,
        };
    }

    public function calculateMonthly(array $logs): int
    {
        return array_sum(
            array_map(fn ($l) => $this->calculateDaily($l), $logs)
        );
    }

    public function calculateRecoveryBalance(array $logs): int
    {
        $balance = 0;

        foreach ($logs as $log) {
            if (WorkLogType::WORK === $log->getType()) {
                $expected = TimeConfig::WORK_DAY_MINUTES;

                $extra = $log->getWorkedMinutes() - $expected;

                if ($extra > 0) {
                    $balance += $extra;
                } else {
                    $balance -= $extra;
                }
            }

            if (WorkLogType::RECOVERY === $log->getType()) {
                $balance -= $log->getWorkedMinutes();
            }
        }

        return $balance;
    }
}
