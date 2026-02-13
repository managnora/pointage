<?php

namespace App\Controller;

use App\DTO\LogDTO;
use App\Enum\StatusEnum;
use App\Enum\WorkLogType;
use App\Repository\WorkLogRepository;
use App\Service\LogFileManager;
use App\Service\TimeReportService;
use App\Service\WorkLogManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainApiController extends AbstractController
{
    public function __construct(
        private readonly LogFileManager $logFileManager,
        private readonly WorkLogRepository $repository,
        private readonly TimeReportService $timeReportService,
        private readonly WorkLogManager $workLogManager,
    ) {
    }

    #[Route('/api/logs')]
    public function getCollection(): Response
    {
        // Définir la locale PHP
        setlocale(LC_TIME, 'fr_FR.utf8');

        $result = $this->logFileManager->execute();

        return $this->json($result);
    }

    /**
     * @Route("/api/worklogs", name="api_worklogs_by_month", methods={"GET"})
     */
    #[Route('/api/worklogs')]
    public function worklogsByMonth(Request $request): JsonResponse
    {
        $year = (int) $request->query->get('year', (int) date('Y'));
        $month = (int) $request->query->get('month', (int) date('m'));

        $report = $this->timeReportService->getMonthlyReport($year, $month);

        return $this->json([
            'year' => $year,
            'month' => $month,
            'workedMinutes' => $report['workedMinutes'],
            'data' => $report['logs'],
            'balance' => $report['balance'],
            'availableMonths' => $this->repository->getAvailableMonths(),
        ]);
    }

    #[Route('/api/worklogs/event', methods: ['POST'])]
    public function createEvent(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        $dto = new LogDTO(
            date: new \DateTime($payload['date']),
            startTime: $payload['startTime'] ?? null,
            endTime: $payload['endTime'] ?? null,
            workedMinutes: $payload['workedMinutes'] ?? null,
            status: StatusEnum::from($payload['status']) ?? null,
            type: WorkLogType::from($payload['type']) ?? null
        );

        $workLog = $this->workLogManager->createEvent($dto);

        return $this->json(
            $this->timeReportService->singleLog($workLog),
            201
        );
    }

    #[Route('/api/worklogs/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        $dto = new LogDTO(
            new \DateTime($payload['date']) ?? null,
            $payload['startTime'] ?? null,
            $payload['endTime'] ?? null,
            $payload['workedMinutes'] ?? null,
            StatusEnum::from($payload['status']) ?? null,
            WorkLogType::from($payload['type']) ?? null
        );

        $log = $this->workLogManager->updateEvent($id, $dto);

        return $this->json(
            $this->timeReportService->singleLog($log)
        );
    }

    #[Route('/api/worklogs/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->workLogManager->deleteEvent($id);

        return $this->json(['success' => true]);
    }

}
