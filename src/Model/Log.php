<?php

namespace App\Model;

class Log
{
    private ?\DateTime $createdAt;

    public function __construct(
        private string $date,
        private ?\DateTime $startTime,
        private ?\DateTime $endTime,
        private string $between,
        private StatusEnum $status,
    ) {
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTime $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTime $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getBetween(): string
    {
        return $this->between;
    }

    public function setBetween(string $between): self
    {
        $this->between = $between;
        return $this;
    }

    public function getStatus(): StatusEnum
    {
        return $this->status;
    }

    public function setStatus(StatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusString(): string
    {
        return $this->status->value;
    }

    /**
     * @throws \Exception
     */
    public function getStatusImageFilename(): string
    {
        return match ($this->status) {
            StatusEnum::ALERT => 'images/complain.png',
            StatusEnum::IN_PROGRESS => 'images/hourglass.png',
            StatusEnum::WARNING => 'images/warning.png',
            StatusEnum::COMPLETED => 'images/check-mark.png',
        };
    }
}
