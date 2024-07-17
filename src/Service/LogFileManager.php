<?php

namespace App\Service;

use App\Model\Log;
use App\Model\StatusEnum;

class LogFileManager
{
    // 1 heure de pause
    public const BREAK_TIME = 1;

    /**
     * @return array
     */
    public function execute(): array
    {
        $logFile = '/home/alvin/logfile.txt';
        $logsData = [];
        if (file_exists($logFile)) {
            $logLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logsData = $this->prepareLog($logLines);
        }
        $logGroupByDate = $this->prepareGroupLogByDaily($logsData);
        $intermediateLogs = $this->prepare($logGroupByDate);

        // Trier les logs par date
        usort($intermediateLogs, function ($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        return $intermediateLogs;
    }

    /**
     * @param array $logLines
     * @return array
     */
    private function prepareLog(array $logLines): array
    {
        $logs = [];
        foreach ($logLines as $line) {
            $parts = explode(' ', $line);
            $date = $parts[1];
            $month = str_replace('.', '', $parts[2]);
            $year = $parts[3];
            $time = $parts[4];
            $action = end($parts);
            $dateString = "$date $month $year $time";
            $replacedString = str_replace(['janv', 'févr', 'mars', 'avr', 'mai', 'juin', 'juil', 'août', 'sept', 'oct', 'nov', 'déc'],
                ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                $dateString);
            $dateTime = \DateTime::createFromFormat('d M Y H:i:s', $replacedString);
            $logs[] = [
                'date' => "$date $month $year",
                'time' => $dateTime->format('H:i'),
                'action' => trim($action, '-'),
                'timestamp' => $dateTime,
            ];
        }

        return $logs;
    }

    /**
     * @param array $logs
     * @return array
     */
    private function prepareGroupLogByDaily(array $logs): array
    {
        $logsByDate = [];
        foreach ($logs as $log) {
            $date = $log['date'];
            $action = $log['action'];

            // Initialisation des tableaux si nécessaire
            if (!isset($logsByDate[$date])) {
                $logsByDate[$date] = [];
            }

            // Ajout du log en fonction de l'action
            $actionLogs = &$logsByDate[$date]; // Référence au tableau pour éviter la répétition

            if ($actionLogs && isset($actionLogs[$action])) {
                $lastLog = $actionLogs[$action]; // Le dernier log enregistré
                if (('Start' === $action && $lastLog['timestamp'] > $log['timestamp'])
                    || ('Stop' === $action && $lastLog['timestamp'] < $log['timestamp'])) {
                    $actionLogs[$action] = $log;
                }
            } else {
                $actionLogs[$action] = $log;
            }
        }

        return $logsByDate;
    }

    /**
     * @param array $logsByDate
     * @return array
     */
    private function prepare(array $logsByDate): array
    {
        setlocale(LC_TIME, 'fr_FR.utf8');
        // Calculer la différence d'heures par jour
        $result = [];
        foreach ($logsByDate as $date => $dayLogs) {
            $startTime = $dayLogs['Start']['timestamp'] ?? null;
            $stopTime = $dayLogs['Stop']['timestamp'] ?? null;

            $differenceTimeAndStatus = $this->calculateDifferenceTimeAndStatus($startTime, $stopTime);

            $result[] = (new Log(
                strftime('%d %b %Y', $startTime->getTimestamp()),
                $startTime,
                $stopTime,
                $differenceTimeAndStatus['differenceTime'],
                $differenceTimeAndStatus['status'],
            ))->setCreatedAt($startTime);
        }

        return $result;
    }

    /**
     * @param \DateTime|null $startTime
     * @param \DateTime|null $stopTime
     * @return array
     */
    private function calculateDifferenceTimeAndStatus(?\DateTime $startTime, ?\DateTime $stopTime): array
    {
        if (!$startTime || !$stopTime) {
            return [
                'differenceTime' => '-',
                'status' => $status = StatusEnum::IN_PROGRESS,
            ];
        }

        $interval = $startTime->diff($stopTime);
        $hours = $interval->h - self::BREAK_TIME; // Ajouter les heures des jours de différence
        $minutes = $interval->i;
        $differenceTime = sprintf('%02dh %02d', $hours, $minutes);

        if ($hours >= 8) {
            $status = StatusEnum::COMPLETED;
        } elseif ($hours < 6) {
            $status = StatusEnum::ALERT;
        } else { // $hours >= 6 && $hours < 8
            $status = StatusEnum::WARNING;
        }

        return [
            'differenceTime' => $differenceTime,
            'status' => $status,
        ];
    }
}
