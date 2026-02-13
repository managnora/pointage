<?php

namespace App\Entity;

use App\Enum\LogSource;
use App\Enum\StatusEnum;
use App\Enum\WorkLogType;
use App\Repository\WorkLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkLogRepository::class)]
#[ORM\Table(name: 'work_logs')]
#[ORM\UniqueConstraint(columns: ['date'])]
class WorkLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: 'integer')]
    private int $workedMinutes = 0;

    #[ORM\Column(enumType: WorkLogType::class)]
    private WorkLogType $type;

    #[ORM\Column(enumType: StatusEnum::class)]
    private StatusEnum $status;

    #[ORM\Column(enumType: LogSource::class)]
    private LogSource $source;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        \DateTimeInterface $date,
        ?\DateTimeInterface $startTime,
        ?\DateTimeInterface $endTime,
        int $workedMinutes,
        WorkLogType $type,
        StatusEnum $status,
        LogSource $source,
    ) {
        $this->date = $date;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->workedMinutes = $workedMinutes;
        $this->type = $type;
        $this->status = $status;
        $this->source = $source;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getMonth(): string
    {
        return $this->date->format('Y-m');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): WorkLog
    {
        $this->id = $id;
        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): WorkLog
    {
        $this->date = $date;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): WorkLog
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): WorkLog
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getWorkedMinutes(): int
    {
        return $this->workedMinutes;
    }

    public function setWorkedMinutes(int $workedMinutes): WorkLog
    {
        $this->workedMinutes = $workedMinutes;
        return $this;
    }

    public function getType(): WorkLogType
    {
        return $this->type;
    }

    public function setType(WorkLogType $type): WorkLog
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): StatusEnum
    {
        return $this->status;
    }

    public function setStatus(StatusEnum $status): WorkLog
    {
        $this->status = $status;
        return $this;
    }

    public function getSource(): LogSource
    {
        return $this->source;
    }

    public function setSource(LogSource $source): WorkLog
    {
        $this->source = $source;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): WorkLog
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}

