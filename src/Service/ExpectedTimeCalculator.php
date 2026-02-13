<?php

namespace App\Service;

use App\Config\TimeConfig;

class ExpectedTimeCalculator
{

    public function __construct(
        private readonly WorkingDayProvider $provider,
    ) {
    }

    public function calculate(int $year, int $month, array $leaves): int
    {
        $days = $this->provider->getWorkingDays($year, $month);
        $minutes = count($days) * TimeConfig::WORK_DAY_MINUTES;

        foreach ($leaves as $leave) {
            $minutes -= $leave->getMinutes() ?? TimeConfig::WORK_DAY_MINUTES;
        }

        return max(0, $minutes);
    }
}
