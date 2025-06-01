<?php

namespace App\Service;

use App\DTO\MonthlyLogData;
use App\Model\Log;
use App\Model\StatusEnum;

class LogFileManager
{
    private const BREAK_TIME = 1;
    private const WORK_DAY_MINUTES = 480;
    private const MONTHS_MAP = [
        'janv' => 'Jan', 'févr' => 'Feb', 'mars' => 'Mar',
        'avr' => 'Apr', 'mai' => 'May', 'juin' => 'Jun',
        'juil' => 'Jul', 'août' => 'Aug', 'sept' => 'Sep',
        'oct' => 'Oct', 'nov' => 'Nov', 'déc' => 'Dec',
    ];

    private string $logFilePath;

    /**
     * @param string $logFilePath
     */
    public function __construct(string $logFilePath)
    {
        $this->logFilePath = $logFilePath;
    }


    public function execute(int $page = 1, int $itemsPerPage = 3): PaginatedResult
    {
        $processedLogs = $this->processLogs();

        return $this->paginateResults($processedLogs, $page, $itemsPerPage);
    }

    private function processLogs(): array
    {
        $rawLogs = $this->readLogFile($this->logFilePath);
        $groupedLogs = $this->prepareGroupLogByDaily($rawLogs);
        $processedLogs = $this->processGroupedLogs($groupedLogs);
        $monthlyTotals = $this->calculateMonthlyTotals($processedLogs);

        return $this->formatResults($monthlyTotals);
    }

    private function processGroupedLogs(array $groupedLogs): array
    {
        $logs = $this->prepare($groupedLogs);

        // Tri par date décroissante
        usort($logs, fn ($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $logs;
    }

    private function calculateMonthlyTotals(array $processedLogs): array
    {
        return $this->groupDataByMonthYear($processedLogs);
    }

    private function formatResults(array $monthlyTotals): array
    {
        return $this->formatTotalMinutes($monthlyTotals);
    }

    private function paginateResults(array $results, int $page, int $itemsPerPage): PaginatedResult
    {
        $totalItems = count($results);
        $offset = ($page - 1) * $itemsPerPage;
        $paginatedItems = array_slice($results, $offset, $itemsPerPage);

        return new PaginatedResult(
            items: $paginatedItems,
            totalItems: $totalItems,
            currentPage: $page,
            itemsPerPage: $itemsPerPage
        );
    }

    private function readLogFile(string $logFile): array
    {
        if (!file_exists($logFile)) {
            return [];
        }

        $logLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return $this->prepareLog($logLines);
    }

    private function prepareLog(array $logLines): array
    {
        return array_map(function ($line) {
            $parts = explode(' ', $line);
            $dateTime = $this->parseDateTime($parts[1], $parts[2], $parts[3], $parts[4]);

            return [
                'date' => "{$parts[1]} {$parts[2]} {$parts[3]}",
                'time' => $dateTime->format('H:i'),
                'action' => trim(end($parts), '-'),
                'timestamp' => $dateTime,
            ];
        }, $logLines);
    }

    private function parseDateTime(string $date, string $month, string $year, string $time): \DateTime
    {
        $month = str_replace('.', '', $month);
        $dateString = "$date $month $year $time";
        $replacedString = str_replace(
            array_keys(self::MONTHS_MAP),
            array_values(self::MONTHS_MAP),
            $dateString
        );

        return \DateTime::createFromFormat('d M Y H:i:s', $replacedString);
    }

    private function prepareGroupLogByDaily(array $logs): array
    {
        return array_reduce($logs, function ($logsByDate, $log) {
            $date = $log['date'];
            $action = $log['action'];

            if (!isset($logsByDate[$date])) {
                $logsByDate[$date] = [];
            }

            if (isset($logsByDate[$date][$action])) {
                $shouldUpdate = ('Start' === $action && $logsByDate[$date][$action]['timestamp'] > $log['timestamp'])
                    || ('Stop' === $action && $logsByDate[$date][$action]['timestamp'] < $log['timestamp']);

                if ($shouldUpdate) {
                    $logsByDate[$date][$action] = $log;
                }
            } else {
                $logsByDate[$date][$action] = $log;
            }

            return $logsByDate;
        }, []);
    }

    private function prepare(array $logsByDate): array
    {
        setlocale(LC_TIME, 'fr_FR.utf8');

        return array_map(function ($date, $dayLogs) {
            $startTime = $dayLogs['Start']['timestamp'] ?? null;
            $stopTime = $dayLogs['Stop']['timestamp'] ?? null;
            $timeStatus = $this->calculateDifferenceTimeAndStatus($startTime, $stopTime);

            return (new Log(
                strftime('%d %b %Y', $startTime->getTimestamp()),
                $startTime,
                $stopTime,
                $timeStatus['differenceTime'],
                $timeStatus['status']
            ))->setCreatedAt($startTime);
        }, array_keys($logsByDate), $logsByDate);
    }

    private function calculateDifferenceTimeAndStatus(?\DateTime $startTime, ?\DateTime $stopTime): array
    {
        if (!$startTime || !$stopTime) {
            return ['differenceTime' => '-', 'status' => StatusEnum::IN_PROGRESS];
        }

        $interval = $startTime->diff($stopTime);
        $hours = $interval->h - self::BREAK_TIME;
        $minutes = $interval->i;

        return [
            'differenceTime' => sprintf('%02dh %02d', $hours, $minutes),
            'status' => $this->determineStatus($hours),
        ];
    }

    private function determineStatus(int $hours): StatusEnum
    {
        if ($hours >= 8) {
            return StatusEnum::COMPLETED;
        }
        if ($hours < 6) {
            return StatusEnum::ALERT;
        }

        return StatusEnum::WARNING;
    }

    private function convertToMinutes(string $timeString): int
    {
        if ('-' === $timeString) {
            return 0;
        }

        [$hours, $minutes] = sscanf($timeString, '%dh %d');

        return ($hours * 60) + $minutes;
    }

    private function groupDataByMonthYear(array $data): array
    {
        return array_reduce($data, function ($totals, $entry) {
            $monthYear = $entry->getStartTime()->format('Y-m');

            if (!isset($totals[$monthYear])) {
                $totals[$monthYear] = ['entries' => [], 'totals' => 0];
            }

            if ('-' !== $entry->getBetween()) {
                $totals[$monthYear]['totals'] += $this->convertToMinutes($entry->getBetween());
            }

            $totals[$monthYear]['entries'][] = $entry;

            return $totals;
        }, []);
    }

    private function formatTotalMinutes(array $totals): array
    {
        $currentMonthYear = (new \DateTime())->format('Y-m');

        return array_map(function ($monthYear, $total) use ($currentMonthYear) {
            $dayMinutes = $this->convertNbrDaysToMinutes(count($total['entries']));
            $currentDayMinutes = $this->convertNbrDaysToMinutes(1);

            // Calcul du solde selon si c'est le mois courant ou pas
            $isCurrentMonth = $monthYear === $currentMonthYear;
            $difference = $total['totals'] - $dayMinutes;

            if ($isCurrentMonth) {
                $difference += $currentDayMinutes;
            }

            return MonthlyLogData::fromArray([
                'monthYear' => $monthYear,
                'monthYearDetail' => $this->formatMonthYear($monthYear),
                'total' => $this->formatDuration($total['totals']),
                'solde' => $this->formatDuration($difference),
                'entries' => $total['entries'],
            ]);
        }, array_keys($totals), $totals);
    }

    private function formatDuration(int $totalMinutes): string
    {
        $workDays = intdiv($totalMinutes, self::WORK_DAY_MINUTES);
        $remainingMinutes = $totalMinutes % self::WORK_DAY_MINUTES;
        $hours = intdiv($remainingMinutes, 60);
        $minutes = $remainingMinutes % 60;

        return sprintf('%dj %dh %02dmn', $workDays, $hours, $minutes);
    }

    private function convertNbrDaysToMinutes(int $days): int
    {
        return $days * self::WORK_DAY_MINUTES;
    }

    private function formatMonthYear(string $dateString): string
    {
        $date = \DateTime::createFromFormat('Y-m', $dateString);

        return $date ? $date->format('F Y') : $dateString;
    }
}
