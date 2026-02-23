<?php

namespace App\Service;

use App\DTO\LogDTO;
use App\Enum\StatusEnum;
use App\Enum\WorkLogType;

class HolidayManager
{
    public function __construct(private WorkLogManager $workLogManager)
    {
    }

    /**
     * @throws \Exception
     */
    public function generateMadagascarHolidays(int $year): void
    {
        $holidays = $this->getMadagascarHolidays($year);

        foreach ($holidays as $holiday) {
            $date = $holiday['date'] instanceof \DateTimeImmutable
                ? \DateTime::createFromImmutable($holiday['date'])
                : $holiday['date'];

            $start = $this->workLogManager->createDateTime($date, '08:00');
            $end = $this->workLogManager->createDateTime($date, '17:00');

            $dto = new LogDTO(
                date: $date,
                startTime: $start,
                endTime: $end,
                workedMinutes: 0,
                status: StatusEnum::COMPLETED,
                type: WorkLogType::HOLIDAY
            );

            try {
                $this->workLogManager->createEvent($dto);
            } catch (\DomainException $e) {
                // Ignore si déjà existant
                continue;
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function getMadagascarHolidays(int $year): array
    {
        $holidays = [];

        // --- Jours fixes ---
        $fixed = [
            '01-01' => 'Jour de l\'An',
            '03-29' => 'Commémoration 29 Mars',
            '05-01' => 'Fête du Travail',
            '06-26' => 'Fête Nationale',
            '08-15' => 'Assomption',
            '11-01' => 'Toussaint',
            '12-25' => 'Noël',
        ];

        foreach ($fixed as $date => $name) {
            $holidays[] = [
                'date' => new \DateTimeImmutable("$year-$date"),
                'name' => $name,
            ];
        }

        // --- Calcul Pâques ---
        $easter = new \DateTimeImmutable('@'.easter_date($year));

        $holidays[] = [
            'date' => $easter->modify('+1 day'),
            'name' => 'Lundi de Pâques',
        ];

        $holidays[] = [
            'date' => $easter->modify('+39 days'),
            'name' => 'Ascension',
        ];

        $holidays[] = [
            'date' => $easter->modify('+50 days'),
            'name' => 'Lundi de Pentecôte',
        ];

        return $holidays;
    }
}
