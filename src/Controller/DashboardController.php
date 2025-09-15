<?php

namespace App\Controller;

use App\Entity\ReadLog;
use App\Repository\BookRepository;
use App\Repository\ReadLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(BookRepository $bookRepository, ReadLogRepository $readLogRepository): Response
    {
        $thisYear = new \DateTime()->format('Y');
        $logs = $readLogRepository->findByYear($thisYear);
        $unfinished = $readLogRepository->findUnfinished() ?: [];
        $books = [];
        $readingTime = 0;
        $pages = 0;
        $readingNowList = [];

        $librarySize = $bookRepository->count();
        $totalLogs = $readLogRepository->count();


        /** @var ReadLog $log */
        foreach ($logs as $log) {
            $book = $log->getBook();
            $books[] = $book->getTitle();
            $readingTime += date_diff($log->getStartDate(), $log->getFinishDate())->days;
            $pages += $book->getPages() ?? 0;
        }

        array_walk($unfinished, function (ReadLog $logItem) use (&$readingNowList) {
            $readingNowList[] = $logItem->getBook()->getTitle();
        });

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
            'redingNowList' => $readingNowList
        ]);
    }

    #[Route('/books', name: 'app_dashboard_books')]
    public function books(BookRepository $bookRepository, Request $request): Response
    {
        // Get pagination parameters
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(100, $request->query->getInt('limit', 20))); // Default 20, max 100

        // Calculate offset
        $offset = ($page - 1) * $limit;

        // Get total count
        $totalBooks = $bookRepository->count([]);

        // Calculate pagination info
        $totalPages = (int) ceil($totalBooks / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;

        // Get books for current page
        $books = $bookRepository->findBy([], ['id' => 'ASC'], $limit, $offset);

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'books',
            'books' => $books,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalBooks,
                'items_per_page' => $limit,
                'has_next' => $hasNext,
                'has_prev' => $hasPrev,
                'start_item' => $offset + 1,
                'end_item' => min($offset + $limit, $totalBooks)
            ]
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
