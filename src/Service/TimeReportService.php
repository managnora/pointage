<?php

namespace App\Service;

use App\DTO\CalendarEventDTO;
use App\Entity\WorkLog;
use App\Enum\WorkLogType;
use App\Repository\WorkLogRepository;

class TimeReportService
{
    public function __construct(
        private readonly WorkLogRepository $workLogRepository,
        private readonly TimeCalculator $calculator,
    ) {
    }

    public function getMonthlyReport(int $year, int $month): array
    {
        $logs = $this->workLogRepository->findByMonth($year, $month);

        return [
            'logs' => $this->mapLogs($logs),
            'workedMinutes' => $this->calculator->calculateMonthly($logs),
            'balance' => $this->calculator->calculateRecoveryBalance($logs),
        ];
    }

    private function mapLogs(array $logs): array
    {
        return array_map(function (WorkLog $w) {
            return $this->singleLog($w);
        }, $logs);
    }

    private function buildDateTime(?\DateTimeInterface $date, ?\DateTimeInterface $time): ?string
    {
        if (!$date || !$time) {
            return null;
        }

        return $date->format('Y-m-d').'T'.$time->format('H:i:s');
    }

    private function getColorByType(WorkLogType $type): string
    {
        return match ($type) {
            WorkLogType::WORK => '#4CAF50',
            WorkLogType::LEAVE,
            WorkLogType::ABSENCE => '#F44336',
            WorkLogType::RECOVERY => '#2196F3',
            WorkLogType::HOLIDAY => '#9E9E9E',
        };
    }

    public function singleLog(WorkLog $w): CalendarEventDTO
    {
        return new CalendarEventDTO(
            title: "Worked {$w->getWorkedMinutes()} min",
            date: $w->getDate()->format('Y-m-d'),
            start: $this->buildDateTime($w->getDate(), $w->getStartTime()),
            end: $this->buildDateTime($w->getDate(), $w->getEndTime()),
            color: $this->getColorByType($w->getType()),
            workedMinutes: $w->getWorkedMinutes(),
            status: $w->getStatus()->value,
            type: $w->getType()->value,
        );
    }
}
