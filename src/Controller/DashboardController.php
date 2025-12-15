<?php

namespace App\Controller;

use App\Entity\ReadLog;
use App\Repository\BookRepository;
use App\Repository\ReadLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(BookRepository $bookRepository, ReadLogRepository $readLogRepository, SessionInterface $session): Response
    {
        $readerId = $session->get('current_reader_id');
        if (!$readerId) {
            $this->addFlash('error', 'Choose a reader please');
            return $this->redirectToRoute('app_main');
        }

        $thisYear = new \DateTime()->format('Y');
        $logs = $readLogRepository->findByYear($thisYear, $readerId);
        $unfinished = $readLogRepository->findUnfinished($readerId) ?: [];
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

        $readingSpeed = $logs ? round($readingTime / count($logs), 2) : 0;
        $totalReadPercentage = $librarySize ? round($totalLogs * 100 / $librarySize, 2) : 0;

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'dashboard',
            'thisYear' => $thisYear,
            'booksList' => $books,
            'avgDays' => $readingSpeed,
            'booksCount' => count($books),
            'pages' => $pages,
            'librarySize' => $librarySize,
            'totalLogs' => $totalLogs,
            'totalReadPercentage' => $totalReadPercentage,
            'redingNowList' => $readingNowList
        ]);
    }

    #[Route('/books', name: 'app_dashboard_books')]
    public function books(BookRepository $bookRepository, Request $request): Response
    {
        // Get pagination parameters
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(100, $request->query->getInt('limit', 20))); // Default 20, max 100

        // Get search parameters
        $titleSearch = $request->query->get('title', '');
        $authorSearch = $request->query->get('author', '');
        $statusSearch = $request->query->get('status', '');

        // Calculate offset
        $offset = ($page - 1) * $limit;

        if (!empty($titleSearch) || !empty($authorSearch) || !empty($statusSearch)) {
            $books = $bookRepository->findBySearchCriteria($titleSearch, $authorSearch, $statusSearch, $limit, $offset);
            $totalBooks = $bookRepository->countBySearchCriteria($titleSearch, $authorSearch, $statusSearch);
        } else {
            $totalBooks = $bookRepository->count([]);
            $books = $bookRepository->findBy([], ['id' => 'ASC'], $limit, $offset);
        }

        // Calculate pagination info
        $totalPages = (int) ceil($totalBooks / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'books',
            'books' => $books,
            'search' => [
                'title' => $titleSearch,
                'author' => $authorSearch,
                'status' => $statusSearch,
            ],
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
