<?php

namespace App\Service;

use App\DTO\LeaveRequestDTO;
use App\Entity\Leave;
use App\Enum\LeaveType;
use App\Repository\LeaveRepository;
use Doctrine\ORM\EntityManagerInterface;

class LeaveManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private LeaveRepository $repository,
    ) {
    }

    public function createLeave(LeaveRequestDTO $dto): Leave
    {
        $leave = new Leave();
        $leave->setStart(new \DateTimeImmutable($dto->start));
        $leave->setEnd(new \DateTimeImmutable($dto->end));
        $leave->setType(LeaveType::from($dto->type));
        $leave->setMinutes($dto->minutes);

        $this->em->persist($leave);
        $this->em->flush();

        return $leave;
    }

    public function updateLeave(int $id, LeaveRequestDTO $dto): Leave
    {
        $leave = $this->repository->find($id);
        if (!$leave) {
            throw new \DomainException('Leave not found');
        }

        $leave->setStart(new \DateTimeImmutable($dto->start));
        $leave->setEnd(new \DateTimeImmutable($dto->end));
        $leave->setType(LeaveType::from($dto->type));
        $leave->setMinutes($dto->minutes);

        $this->em->flush();

        return $leave;
    }

    public function deleteLeave(int $id): void
    {
        $leave = $this->repository->find($id);
        if (!$leave) {
            throw new \DomainException('Leave not found');
        }

        $this->em->remove($leave);
        $this->em->flush();
    }

    public function listLeaves(): array
    {
        return $this->repository->findAll();
    }
}
