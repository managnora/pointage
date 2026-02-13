<?php

namespace App\Entity;

use App\Enum\LeaveType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Leave
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $start;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $end;

    #[ORM\Column(enumType: LeaveType::class)]
    private LeaveType $type;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $minutes = null;

    public function getStart(): \DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): Leave
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): \DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(\DateTimeInterface $end): Leave
    {
        $this->end = $end;

        return $this;
    }

    public function getType(): LeaveType
    {
        return $this->type;
    }

    public function setType(LeaveType $type): Leave
    {
        $this->type = $type;

        return $this;
    }

    public function getMinutes(): ?int
    {
        return $this->minutes;
    }

    public function setMinutes(?int $minutes): Leave
    {
        $this->minutes = $minutes;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Leave
    {
        $this->id = $id;
        return $this;
    }
}
