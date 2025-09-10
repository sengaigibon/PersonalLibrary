<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'current_page' => 'dashboard',
        ]);
    }

    #[Route('/dashboard/books', name: 'app_dashboard_books')]
    public function books(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'current_page' => 'books',
        ]);
    }

    #[Route('/dashboard/reading-log', name: 'app_dashboard_reading_log')]
    public function readingLog(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'current_page' => 'reading_log',
        ]);
    }
}
