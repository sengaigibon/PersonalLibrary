<?php

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\ReadLogRepository;
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
    public function books(BookRepository $bookRepository): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'current_page' => 'books',
            'books' => $bookRepository->findAll(),
        ]);
    }

    #[Route('/dashboard/reading-log', name: 'app_dashboard_reading_log')]
    public function readingLog(ReadLogRepository $readLogRepository): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'current_page' => 'reading_log',
            'read_logs' => $readLogRepository->findAll(),
        ]);
    }
}
