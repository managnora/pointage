<?php

namespace App\Controller;

use App\DTO\LeaveRequestDTO;
use App\Entity\Leave;
use App\Enum\LeaveType;
use App\Service\LeaveManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/leaves')]
class LeaveAdminController extends AbstractController
{
    public function __construct(private LeaveManager $manager)
    {
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new LeaveRequestDTO(
            $data['start'],
            $data['end'],
            $data['type'],
            $data['minutes'] ?? null
        );

        $leave = $this->manager->createLeave($dto);

        return $this->json($this->mapLeaveToCalendar($leave));
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new LeaveRequestDTO(
            $data['start'],
            $data['end'],
            $data['type'],
            $data['minutes'] ?? null
        );

        $leave = $this->manager->updateLeave($id, $dto);

        return $this->json($this->mapLeaveToCalendar($leave));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->manager->deleteLeave($id);

        return $this->json(['success' => true]);
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $leaves = $this->manager->listLeaves();

        return $this->json(array_map([$this, 'mapLeaveToCalendar'], $leaves));
    }

    private function mapLeaveToCalendar(Leave $leave): array
    {
        $color = match ($leave->getType()) {
            LeaveType::CONGE_PAYE => '#9E9E9E',
            LeaveType::RECUP => '#2196F3',
            LeaveType::RTT, LeaveType::MALADIE => '#F44336',
        };

        return [
            'id' => $leave->getId(),
            'title' => $leave->getType()->value,
            'start' => $leave->getStart()->format('Y-m-d'),
            'end' => $leave->getEnd()->format('Y-m-d'),
            'color' => $color,
            'extendedProps' => [
                'minutes' => $leave->getMinutes(),
            ],
        ];
    }
}
