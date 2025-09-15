<?php

namespace App\Controller;

use App\Entity\ReadLog;
use App\Repository\BookRepository;
use App\Repository\ReadLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(BookRepository $bookRepository, ReadLogRepository $readLogRepository): Response
    {
        $thisYear = new \DateTime()->format('Y');
        $logs = $readLogRepository->findByYear($thisYear);
        $books = [];
        $readingTime = 0;
        $pages = 0;

        $librarySize = $bookRepository->count();
        $totalLogs = $readLogRepository->count();


        /** @var ReadLog $log */
        foreach ($logs as $log) {
            $book = $log->getBook();
            $books[] = $book->getTitle();
            $readingTime += date_diff($log->getStartDate(), $log->getFinishDate())->days;
            $pages += $book->getPages() ?? 0;
        }

        $readingSpeed = round($readingTime / count($logs), 2);

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'dashboard',
            'thisYear' => $thisYear,
            'booksList' => $books,
            'avgDays' => $readingSpeed,
            'booksCount' => count($books),
            'pages' => $pages,
            'librarySize' => $librarySize,
            'totalLogs' => $totalLogs,
            'totalReadPercentage' => round($totalLogs * 100 / $librarySize, 2),
        ]);
    }

    #[Route('/books', name: 'app_dashboard_books')]
    public function books(BookRepository $bookRepository): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'books',
            'books' => $bookRepository->findBy([], ['id' => 'ASC']),
        ]);
    }

    #[Route('/read/log', name: 'app_dashboard_reading_log')]
    public function readingLog(ReadLogRepository $readLogRepository): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'logs',
            'readLogs' => $readLogRepository->findBy([], ['startDate' => 'ASC']),
        ]);
    }
}
