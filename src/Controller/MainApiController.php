<?php

namespace App\Controller;

use App\Service\LogFileManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainApiController extends AbstractController
{
    /**
     * @param LogFileManager $logFileManager
     */
    public function __construct(
        private readonly LogFileManager $logFileManager,
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
}
