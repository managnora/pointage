<?php

namespace App\Controller;

use App\Service\LogFileManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route('/', name: 'app_homepage')]
    public function homepage(LogFileManager $logFileManager): Response
    {
        // Définir la locale PHP
        setlocale(LC_TIME, 'fr_FR.utf8');

        $result = $logFileManager->execute();

        return $this->render('main/homepage.html.twig', ['logs' => $result]);
    }


}
