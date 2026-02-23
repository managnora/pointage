<?php

namespace App\Service;

use App\Config\TimeConfig;
use App\DTO\LogDTO;
use App\Entity\WorkLog;
use App\Enum\StatusEnum;
use App\Enum\WorkLogType;
use App\Factory\WorkLogFactory;
use App\Repository\WorkLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class WorkLogManager
{
    public function __construct(
        private readonly WorkLogRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly WorkLogFactory $factory,
    ) {
    }

    public function createEvent(LogDTO $dto): WorkLog
    {
        $existing = $this->repository->findOneByDate($dto->date);

        if ($existing && StatusEnum::IN_PROGRESS !== $existing->getStatus()) {
            throw new \DomainException('Un événement existe déjà pour cette date');
        }

        if ($existing && StatusEnum::IN_PROGRESS === $existing->getStatus()) {
            return $this->updateExisting($existing, $dto);
        }

        $workLog = $this->factory->createFromDto($dto);

        $this->em->persist($workLog);
        $this->em->flush();

        return $workLog;
    }

    private function updateExisting(WorkLog $log, LogDTO $dto): WorkLog
    {
        $log->setStatus($dto->status);
        if ($dto->type) {
            $log->setType($dto->type);

            $workedMinutes = match ($dto->type) {
                WorkLogType::WORK, WorkLogType::RECOVERY => $dto->workedMinutes ?? $log->getWorkedMinutes(),
                default => 0,
            };
            $log->setWorkedMinutes($workedMinutes);
        }

        $this->em->flush();

        return $log;
    }

    public function updateEvent(int $id, LogDTO $dto): WorkLog
    {
        $log = $this->repository->find($id);

        if (!$log) {
            throw new \DomainException('Event introuvable');
        }

        if ($dto->date) {
            $log->setDate($dto->date);
        }

        if (null !== $dto->startTime) {
            $log->setStartTime($dto->startTime);
        }

        if (null !== $dto->endTime) {
            $log->setEndTime($dto->endTime);
        }

        if (null !== $dto->workedMinutes) {
            $log->setWorkedMinutes($dto->workedMinutes);
        }

        if ($dto->type) {
            $log->setType($dto->type);

            $workedMinutes = match ($dto->type) {
                WorkLogType::WORK, WorkLogType::RECOVERY => $dto->workedMinutes ?? $log->getWorkedMinutes(),
                default => 0,
            };
            $log->setWorkedMinutes($workedMinutes);
        } elseif (null !== $dto->workedMinutes) {
            $log->setWorkedMinutes($dto->workedMinutes);
        }

        if ($dto->status) {
            $log->setStatus($dto->status);
        }

        $this->em->flush();

        return $log;
    }

    public function deleteEvent(int $id): void
    {
        $log = $this->repository->find($id);

        if (!$log) {
            throw new \DomainException('Event introuvable');
        }

        $this->em->remove($log);
        $this->em->flush();
    }

    public function calculateWorkData(
        ?\DateTime $start,
        ?\DateTime $end,
    ): array {
        if (!$start || !$end) {
            return [0, null];
        }

        $interval = $start->diff($end);

        $minutes = max(
            0,
            ($interval->h * 60 + $interval->i)
            - TimeConfig::BREAK_MINUTES
        );

        $status = $minutes >= TimeConfig::WORK_DAY_MINUTES
            ? StatusEnum::COMPLETED
            : StatusEnum::WARNING;

        return [$minutes, $status];
    }

    /**
     * @throws \Exception
     */
    public function createDateTime(
        \DateTime $date,
        ?string $time,
    ): ?\DateTime {
        if (!$time) {
            return null;
        }

        return new \DateTime(
            $date->format('Y-m-d').' '.$time
        );
    }
}
