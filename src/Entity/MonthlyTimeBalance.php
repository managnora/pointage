<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MonthlyTimeBalance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column]
    private string $month; // Y-m

    #[ORM\Column]
    private int $expectedMinutes;

    #[ORM\Column]
    private int $workedMinutes;

    #[ORM\Column]
    private int $balanceMinutes;

    public function getMonth(): string
    {
        return $this->month;
    }

    public function setMonth(string $month): MonthlyTimeBalance
    {
        $this->month = $month;

        return $this;
    }

    public function getExpectedMinutes(): int
    {
        return $this->expectedMinutes;
    }

    public function setExpectedMinutes(int $expectedMinutes): MonthlyTimeBalance
    {
        $this->expectedMinutes = $expectedMinutes;

        return $this;
    }

    public function getWorkedMinutes(): int
    {
        return $this->workedMinutes;
    }

    public function setWorkedMinutes(int $workedMinutes): MonthlyTimeBalance
    {
        $this->workedMinutes = $workedMinutes;

        return $this;
    }

    public function getBalanceMinutes(): int
    {
        return $this->balanceMinutes;
    }

    public function setBalanceMinutes(int $balanceMinutes): MonthlyTimeBalance
    {
        $this->balanceMinutes = $balanceMinutes;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): MonthlyTimeBalance
    {
        $this->id = $id;
        return $this;
    }
}
