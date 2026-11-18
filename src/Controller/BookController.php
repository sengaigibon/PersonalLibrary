<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\ReadLog;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Repository\ReadLogRepository;
use App\Services\CurrentReader;
use App\Services\OpenLibraryApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/books')]
final class BookController extends AbstractController
{
    public function __construct(
        private CurrentReader $currentReader,
    ) {
    }

    #[Route('/new', name: 'app_book_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository): Response
    {
        $reader = $this->currentReader->get();
        if (!$reader) {
            return $this->redirectToWelcomePage();
        }

        // Get pagination parameters to preserve them
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bookExists = !empty($bookRepository->findBy(['title' => $book->getTitle(), 'owner' => $reader]));
            if ($bookExists) {
                $this->addFlash('error', 'Book already exists!!');
                return $this->redirectToRoute('app_book_new', ['page' => $page, 'limit' => $limit], Response::HTTP_SEE_OTHER);
            }

            $book->setOwner($reader);
            $entityManager->persist($book);
            $entityManager->flush();

            $this->addFlash('success', 'Book created successfully!');
            return $this->redirectToRoute('app_dashboard_books', ['page' => $page, 'limit' => $limit], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'new-book',
            'book' => $book,
            'form' => $form,
            'pagination_params' => ['page' => $page, 'limit' => $limit]
        ]);
    }

    #[Route('/{id}', name: 'app_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        if (!$this->currentReader->getId()) {
            return $this->redirectToWelcomePage();
        }
        $this->denyUnlessOwner($book);

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'show-book',
            'book' => $book,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if (!$this->currentReader->getId()) {
            return $this->redirectToWelcomePage();
        }
        $this->denyUnlessOwner($book);

        // Get pagination parameters to preserve them
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Book saved successfully!');
            return $this->redirectToRoute('app_dashboard_books', ['page' => $page, 'limit' => $limit], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'edit-book',
            'book' => $book,
            'form' => $form,
            'pagination_params' => ['page' => $page, 'limit' => $limit]
        ]);
    }

    #[Route('/{id}', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if (!$this->currentReader->getId()) {
            return $this->redirectToWelcomePage();
        }
        $this->denyUnlessOwner($book);

        if (!$this->isCsrfTokenValid('delete'.$book->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'The book could not be deleted, please try again.');
            return $this->redirectToRoute('app_dashboard_books', [], Response::HTTP_SEE_OTHER);
        }

        $readLogsCount = $book->getReadLogs()->count();
        if ($readLogsCount > 0) {
            $this->addFlash('error', sprintf(
                'This book can\'t be deleted because it has %d reading log %s. Delete %s from the Reading Log first.',
                $readLogsCount,
                $readLogsCount === 1 ? 'entry' : 'entries',
                $readLogsCount === 1 ? 'it' : 'them'
            ));
            return $this->redirectToRoute('app_book_show', ['id' => $book->getId()], Response::HTTP_SEE_OTHER);
        }

        $entityManager->remove($book);
        $entityManager->flush();

        $this->addFlash('success', 'Book deleted successfully!');
        return $this->redirectToRoute('app_dashboard_books', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/query/{isbn}', name: 'app_book_query', methods: ['GET'])]
    public function query(string $isbn, OpenLibraryApi $olAPI): Response
    {
        $result = $olAPI->getBookData($isbn);

        return $this->json($result);
    }

    #[Route('/start/{id}', name: 'app_book_start', methods: ['GET'])]
    public function startReading(Book $book, EntityManagerInterface $entityManager): Response
    {
        $reader = $this->currentReader->get();

        if (!$reader) {
            return $this->json(['error' => 'No reader has been set, go to the welcome page'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyUnlessOwner($book);

        try {
            $readLog = new ReadLog();
            $readLog->setStartDate(new \DateTime());
            $readLog->setReader($reader);
            $readLog->setBook($book);
            $entityManager->persist($readLog);
            $entityManager->flush();
        } catch (\Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }

    #[Route('/finish/{id}', name: 'app_book_finish', methods: ['GET'])]
    public function finishReading(Book $book, EntityManagerInterface $entityManager, ReadLogRepository $readLogRepository): Response
    {
        $readerId = $this->currentReader->getId();

        if (!$readerId) {
            return $this->json(['error' => 'No reader has been set, go to the welcome page'], Response::HTTP_BAD_REQUEST);
        }
        $this->denyUnlessOwner($book);

        $log = $readLogRepository->findOneBy(
            ['book' => $book, 'reader' => $readerId, 'finishDate' => null],
            ['startDate' => 'DESC', 'id' => 'DESC']
        );

        if (!$log) {
            return $this->json(['error' => 'You are not reading this book, start reading it first'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $log->setFinishDate(new \DateTime());
            $entityManager->flush();
        } catch (\Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }

    private function denyUnlessOwner(Book $book): void
    {
        if (!$this->currentReader->owns($book)) {
            throw $this->createNotFoundException('Book not found');
        }
    }

    private function redirectToWelcomePage(): Response
    {
        $this->addFlash('error', 'Choose a reader please');

        return $this->redirectToRoute('app_main');
    }
}
