<?php

namespace App\Parser;

use App\Config\TimeConfig;
use App\DTO\LogDTO;
use App\Enum\StatusEnum;

class LogFileParser
{
    private const MONTHS_MAP = [
        'janv' => 'Jan', 'janvier' => 'Jan',
        'févr' => 'Feb', 'février' => 'Feb',
        'mars' => 'Mar',
        'avr' => 'Apr', 'avril' => 'Apr',
        'mai' => 'May',
        'juin' => 'Jun',
        'juil' => 'Jul', 'juillet' => 'Jul',
        'août' => 'Aug',
        'sept' => 'Sep', 'septembre' => 'Sep',
        'oct' => 'Oct', 'octobre' => 'Oct',
        'nov' => 'Nov', 'novembre' => 'Nov',
        'déc' => 'Dec', 'décembre' => 'Dec',
    ];

    public function __construct(
        private string $logFilePath,
    ) {
    }

    /** @return LogDTO[]
     * @throws \Exception
     */
    public function parse(): array
    {
        if (!file_exists($this->logFilePath)) {
            return [];
        }

        $lines = file($this->logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $parsed = $this->prepareLog($lines);
        $grouped = $this->groupByDay($parsed);

        return $this->buildDTOs($grouped);
    }

    private function prepareLog(array $lines): array
    {
        return array_map(function ($line) {
            $parts = explode(' ', $line);

            $dateTime = $this->parseDateTimeFromLog($line);

            return [
                'date' => $dateTime->format('Y-m-d'),
                'timestamp' => $dateTime,
                'action' => trim(end($parts), '-'),
            ];
        }, $lines);
    }

    private function groupByDay(array $logs): array
    {
        $days = [];

        foreach ($logs as $log) {
            $date = $log['date'];

            if (!isset($days[$date])) {
                $days[$date] = [];
            }

            $action = $log['action'];

            if (
                !isset($days[$date][$action])
                || ('Start' === $action && $log['timestamp'] < $days[$date][$action]['timestamp'])
                || ('Stop' === $action && $log['timestamp'] > $days[$date][$action]['timestamp'])
            ) {
                $days[$date][$action] = $log;
            }
        }

        return $days;
    }

    /** @return LogDTO[]
     * @throws \Exception
     */
    private function buildDTOs(array $grouped): array
    {
        $dtos = [];

        foreach ($grouped as $date => $logs) {
            $start = $logs['Start']['timestamp'] ?? null;
            $stop = $logs['Stop']['timestamp'] ?? null;

            $minutes = 0;
            $status = StatusEnum::IN_PROGRESS;

            if ($start && $stop) {
                $interval = $start->diff($stop);
                $minutes =
                    ($interval->h * 60 + $interval->i)
                    - TimeConfig::BREAK_MINUTES;

                $status = $minutes >= TimeConfig::WORK_DAY_MINUTES
                    ? StatusEnum::COMPLETED
                    : StatusEnum::WARNING;
            }

            $dtos[] = new LogDTO(
                date: new \DateTime($date),
                startTime: $start,
                endTime: $stop,
                workedMinutes: max(0, $minutes),
                status: $status
            );
        }

        return $dtos;
    }

    private function parseDateTime(
        string $day,
        string $month,
        string $year,
        string $time,
    ): \DateTime {
        // retirer le point éventuel sur le mois
        $month = str_replace('.', '', $month);

        // convertir le mois français en anglais
        $month = self::MONTHS_MAP[$month] ?? $month;

        // créer la date sans le fuseau
        $dateString = "$day $month $year $time";

        $date = \DateTime::createFromFormat('d M Y H:i:s', $dateString);

        if (!$date) {
            throw new \RuntimeException("Impossible de parser la date : $dateString");
        }

        return $date;
    }

    private function parseDateTimeFromLog(string $line): \DateTime
    {
        $parts = explode(' ', $line);

        $day = $parts[1];
        $month = str_replace('.', '', $parts[2]);
        $month = self::MONTHS_MAP[$month] ?? $month;
        $year = $parts[3];
        $time = $parts[4];

        $dateString = "$day $month $year $time";
        $date = \DateTime::createFromFormat('d M Y H:i:s', $dateString);

        if (!$date) {
            throw new \RuntimeException("Impossible de parser la date : $dateString");
        }

        return $date;
    }
}
