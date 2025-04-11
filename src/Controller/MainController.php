<?php

namespace App\Controller;

use App\Service\LogFileManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(Request $request, LogFileManager $logFileManager): Response
    {
        setlocale(LC_TIME, 'fr_FR.utf8');

        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 1; // Nombre de mois par page

        $paginatedResult = $logFileManager->execute($page, $itemsPerPage);

        return $this->render('main/homepage.html.twig', [
            'logs' => $paginatedResult->getItems(),
            'pagination' => $paginatedResult,
        ]);
    }
}
