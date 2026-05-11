<?php

namespace App\Controller;

use App\Entity\ReadLog;
use App\Entity\Book;
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
    public function index(BookRepository $bookRepository, ReadLogRepository $readLogRepository, SessionInterface $session, Request $request): Response
    {
        $readerId = $request->query->get('readerId') ?: $session->get('current_reader_id');
    
        if (!$readerId) {
            $this->addFlash('error', 'Choose a reader please');
            return $this->redirectToRoute('app_main');
        }
        
        // Store readerId in session for future requests
        $session->set('current_reader_id', $readerId);

        $currentYear = (int) new \DateTime()->format('Y');
        $currentYearReadLogs = $readLogRepository->findByYear($currentYear, $readerId);
        $booksReadingNow = $bookRepository->findReadingNow($readerId);
        $booksReadCurrentYearCount = [];
        $readingTime = 0;
        $pages = 0;

        $librarySize = $bookRepository->count();
        $totalFinished = $bookRepository->countBySearchCriteria($readerId, '', '', Book::STATUS_FINISHED);


        /** @var ReadLog $log */
        foreach ($currentYearReadLogs as $log) {
            $book = $log->getBook();
            $booksReadCurrentYearCount[] = $book->getTitle();
            $readingTime += date_diff($log->getStartDate(), $log->getFinishDate())->days;
            $pages += $book->getPages() ?? 0;
        }

        $readingSpeed = $currentYearReadLogs ? round($readingTime / count($currentYearReadLogs), 2) : 0;
        $totalReadPercentage = $librarySize ? round($totalFinished * 100 / $librarySize, 2) : 0;

        $booksBroughtCurrentYear = $bookRepository->findBooksBroughtInYear($currentYear, $readerId);
        $booksBroughtPreviousYear = $bookRepository->findBooksBroughtInYear($currentYear - 1, $readerId);

        $booksBroughtPreviousYearUnread = [];
        $booksBroughtPreviousYearRead = [];
        foreach ($booksBroughtPreviousYear as $book) {
            if (empty($book->getReadLogs()->toArray())) {
                $booksBroughtPreviousYearUnread[] = $book;
            } else {
                $booksBroughtPreviousYearRead[] = $book;
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'dashboard',
            'currentYear' => $currentYear,
            'booksReadCurrentYear' => $booksReadCurrentYearCount,
            'avgDays' => $readingSpeed,
            'booksReadCurrentYearCount' => count($booksReadCurrentYearCount),
            'pages' => $pages,
            'librarySize' => $librarySize,
            'totalLogs' => $totalFinished,
            'totalReadPercentage' => $totalReadPercentage,
            'booksReadingNow' => $booksReadingNow,
            'booksBroughtCurrentYear' => $booksBroughtCurrentYear,
            'booksBroughtPreviousYearUnread' => $booksBroughtPreviousYearUnread,
            'booksBroughtPreviousYearRead' => $booksBroughtPreviousYearRead,
            'booksBroughtCurrentYearCount' => count($booksBroughtCurrentYear),
            'booksBroughtPreviousYearCount' => count($booksBroughtPreviousYear),
            'booksBroughtPreviousYearReadCount' => count($booksBroughtPreviousYearRead),
        ]);
    }

    #[Route('/books', name: 'app_dashboard_books')]
    public function books(BookRepository $bookRepository, Request $request, SessionInterface $session): Response
    {
        $readerId = $session->get('current_reader_id');
        if (!$readerId) {
            $this->addFlash('error', 'Choose a reader please');
            return $this->redirectToRoute('app_main');
        }

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
            $books = $bookRepository->findBySearchCriteria($readerId, $titleSearch, $authorSearch, $statusSearch, $limit, $offset);
            $totalBooks = $bookRepository->countBySearchCriteria($readerId, $titleSearch, $authorSearch, $statusSearch);
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
            'readerId' => $readerId,
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
    public function readingLog(ReadLogRepository $readLogRepository, SessionInterface $session): Response
    {
        $readerId = $session->get('current_reader_id');
        if (!$readerId) {
            $this->addFlash('error', 'Choose a reader please');
            return $this->redirectToRoute('app_main');
        }

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'logs',
            'readLogs' => $readLogRepository->findBy(['reader' => $readerId], ['startDate' => 'ASC']),
        ]);
    }
}
