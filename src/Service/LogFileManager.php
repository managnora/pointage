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

        /** @var MonthlyLogData $item */
        foreach ($paginatedItems as $index => $item) {
            $entries = $item->getEntries();

            $filledEntries = $this->fillMissingWorkingDays($entries);

            // Trier par date croissante
            usort($filledEntries, function ($a, $b) {
                return $b->getStartTime() <=> $a->getStartTime();
            });

            $totalMinutes = $this->calculateTotalMinutes($filledEntries);
            $soldeMinutes = $this->calculateSoldeMinutes(
                monthYear: $item->getMonthYear(),
                totalMinutes: $totalMinutes,
                entriesCount: count($filledEntries)
            );

            $paginatedItems[$index] = MonthlyLogData::fromArray([
                'monthYear'        => $item->getMonthYear(),
                'monthYearDetail'  => $item->getMonthYearDetail(),
                'total'            => $this->formatDuration($totalMinutes),
                'solde'            => $this->formatDuration($soldeMinutes),
                'entries'          => $filledEntries,
            ]);
        }

        return new PaginatedResult(
            items: $paginatedItems,
            totalItems: $totalItems,
            currentPage: $page,
            itemsPerPage: $itemsPerPage
        );
    }

    private function calculateTotalMinutes(array $entries): int
    {
        $total = 0;

        /** @var Log $log */
        foreach ($entries as $log) {

            // Si on a un startTime et un endTime → calcul direct
            if ($log->getStartTime() instanceof \DateTime && $log->getEndTime() instanceof \DateTime) {

                $interval = $log->getStartTime()->diff($log->getEndTime());
                $minutes = ($interval->h * 60) + $interval->i;

                $total += $minutes;
                continue;
            }

            // Sinon fallback : parser between "07h 56"
            if ($log->getBetween() && preg_match('/(\d+)h\s*(\d+)/', $log->getBetween(), $match)) {
                $total += ((int)$match[1] * 60) + (int)$match[2];
            }
        }

        return $total;
    }

    private function calculateSoldeMinutes(string $monthYear, int $totalMinutes, int $entriesCount): int
    {
        $currentMonth = (new \DateTime())->format('Y-m');

        // minutes attendues pour ce nombre de jours
        $expected = $this->convertNbrDaysToMinutes($entriesCount);

        // mois courant → on ajoute la journée en cours
        if ($monthYear === $currentMonth) {
            $expected += $this->convertNbrDaysToMinutes(1);
        }

        return $totalMinutes - $expected;
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

    private function getWorkingDaysOfMonth(int $year, int $month): array
    {
        $start = new \DateTime("$year-$month-01");
        $end = (clone $start)->modify('last day of this month');

        $today = new \DateTime();

        // Si le mois/année == mois/année courant → fin = aujourd'hui
        if ($year === (int)$today->format('Y') && $month === (int)$today->format('m')) {
            $end = $today;
        }

        // Récupérer les jours fériés
        $holidays = $this->getHolidays($year);

        $workingDays = [];

        while ($start <= $end) {
            $weekday = (int)$start->format('N');
            $dateStr = $start->format('Y-m-d');
            if ($weekday < 6 && !in_array($dateStr, $holidays)) {
                $workingDays[] = $start->format('d M Y');
            }
            $start->modify('+1 day');
        }

        return $workingDays;
    }

    private function fillMissingWorkingDays(array $logs): array
    {
        if (empty($logs)) {
            return $logs;
        }

        $firstDate = $logs[array_key_first($logs)]->getStartTime();
        $year = (int)$firstDate->format('Y');
        $month = (int)$firstDate->format('m');

        $workingDays = $this->getWorkingDaysOfMonth($year, $month);

        $existingDates = [];
        foreach ($logs as $log) {
            $existingDates[$log->getStartTime()->format('d M Y')] = $log;
        }

        $completed = [];
        foreach ($workingDays as $dateStr) {
            if (isset($existingDates[$dateStr])) {
                $completed[] = $existingDates[$dateStr];
            } else {
                $dateObj = \DateTime::createFromFormat('d M Y', $dateStr);

                $completed[] = (new Log(
                    $dateStr,
                    $dateObj,
                    $dateObj,
                    '00h 00',
                    StatusEnum::ALERT
                ))->setCreatedAt($dateObj);
            }
        }

        return $completed;
    }

    private function getHolidays(int $year): array
    {
        // Jours fériés fixes
        $holidays = [
            "$year-01-01", // Nouvel an
            "$year-03-08", // Journée de la femme
            "$year-03-29", // Jour des martyrs
            "$year-05-01", // Travail
            "$year-06-26", // Indépendance
            "$year-08-15", // Assomption
            "$year-11-01", // Toussaint
            "$year-12-25", // Noël
        ];

        // Jours fériés variables (calcul à partir de la date de Pâques)
        $easter = easter_date($year);
        $easterDate = (new \DateTime("@$easter"))->setTimezone(new \DateTimeZone('Indian/Antananarivo'));

        // Lundi de Pâques = Pâques + 1 jour
        $lundiPaques = (clone $easterDate)->modify('+1 day')->format('Y-m-d');

        // Lundi de Pentecôte = Pâques + 50 jours
        $pentecote = (clone $easterDate)->modify('+50 days')->format('Y-m-d');

        $holidays[] = $lundiPaques;
        $holidays[] = $pentecote;

        return $holidays;
    }
}
