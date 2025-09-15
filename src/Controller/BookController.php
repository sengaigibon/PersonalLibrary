<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\ReadLog;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Services\OpenLibraryApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/books')]
final class BookController extends AbstractController
{
    #[Route('/new', name: 'app_book_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bookExists = !empty($bookRepository->findBy(['title' => $book->getTitle()]));
            if ($bookExists) {
                $this->addFlash('error', 'Book already exists!!');
                return $this->redirectToRoute('app_book_new', [], Response::HTTP_SEE_OTHER);
            }
            $entityManager->persist($book);
            $entityManager->flush();

            $this->addFlash('success', 'Book created successfully!');
            return $this->redirectToRoute('app_dashboard_books', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'new-book',
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'show-book',
            'book' => $book,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_books', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'edit-book',
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_books', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/query/{isbn}', name: 'app_book_query', methods: ['GET'])]
    public function query(Request $request, string $isbn, OpenLibraryApi $olAPI): Response
    {
        $result = $olAPI->getBookData($isbn);

        return $this->json($result);
    }

    #[Route('/start/{id}', name: 'app_book_start', methods: ['GET'])]
    public function startReading(Book $book, EntityManagerInterface $entityManager): Response
    {
        try {
            $readLog = new ReadLog();
            $readLog->setStartDate(new \DateTime());
            $readLog->setBook($book);
            $entityManager->persist($readLog);
            $entityManager->flush();
        } catch (\Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }

    #[Route('/finish/{id}', name: 'app_book_finish', methods: ['GET'])]
    public function finishReading(Book $book, EntityManagerInterface $entityManager): Response
    {
        try {
            $logs = $book->getReadLogs();
            if ($logs->isEmpty()) {
                $log = new ReadLog();
            } else {
                $log = $logs->first();
            }

            $log->setFinishDate(new \DateTime());
            $entityManager->persist($log);
            $entityManager->flush();
        } catch (\Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }
}
