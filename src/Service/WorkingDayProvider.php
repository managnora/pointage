<?php

namespace App\Service;

class WorkingDayProvider
{
    public function getWorkingDays(int $year, int $month): array
    {
        $start = new \DateTime("$year-$month-01");
        $end = (clone $start)->modify('last day of this month');

        $days = [];
        while ($start <= $end) {
            if ((int) $start->format('N') < 6) {
                $days[] = clone $start;
            }
            $start->modify('+1 day');
        }

        return $days;
    }
}
