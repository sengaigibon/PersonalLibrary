<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\ReadLog;
use App\Form\ReadLogType;
use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/read/log')]
final class ReadLogController extends AbstractController
{
    #[Route('/new', name: 'app_read_log_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SessionInterface $session, PersonRepository $personRepository): Response
    {
        $readerId = $session->get('current_reader_id');

        if (!$readerId) {
            $this->addFlash('error', 'Choose a reader please');
            return $this->redirectToRoute('app_dashboard');
        }

        $readLog = new ReadLog();
        $form = $this->createForm(ReadLogType::class, $readLog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($readLog);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_reading_log', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'new-log',
            'read_log' => $readLog,
            'form' => $form,
        ]);
    }

    #[Route('/quicknew/{id}', name: 'app_read_log_quicknew', methods: ['POST'])]
    public function quickNew(Book $book, Request $request, EntityManagerInterface $entityManager, SessionInterface $session, PersonRepository $personRepository): Response
    {
        $readerId = $session->get('current_reader_id');

        if (!$readerId) {
            return $this->json([
                'status' => false,
                'message' => 'No reader has been set, go to the welcome page',
            ], Response::HTTP_BAD_REQUEST);
        }

        $readLog = new ReadLog();
        $content = json_decode($request->getContent() ?? '', true);

        if (empty($content)) {
            return $this->json([
                'status' => false,
                'message' => 'Empty read log',
            ], Response::HTTP_BAD_REQUEST);
        }

        $startYear = $content['startYear'] ?? null;
        $startDate = $content['startDate'] ?? null;
        $endDate = $content['endDate'] ?? null;
        $rating = $content['rating'] ?? 0;

        if ($startYear) {
            $randomDay = rand(1, 28);
            $randomMonth = rand(1, 12);
            $startDate = new \DateTime("$startYear-$randomMonth-$randomDay");
            $endDate = new \DateTime("$startYear-$randomMonth-$randomDay");
            $endDate->modify('+2 day');
        } elseif ($startDate && $endDate) {
            $startDate = new \DateTime($startDate);
            $endDate = new \DateTime($endDate);

            if ($startDate > $endDate) {
                return $this->json([
                    'status' => false,
                    'message' => 'Wrong start date',
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json([
                'status' => false,
                'message' => 'Wrong dates',
            ], Response::HTTP_BAD_REQUEST);
        }

        $readLog->setReader($personRepository->find($readerId));
        $readLog->setBook($book);
        $readLog->setStartDate($startDate);
        $readLog->setFinishDate($endDate);
        $readLog->setRating($rating);

        try {
            $entityManager->persist($readLog);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => true]);
    }

    #[Route('/{id}', name: 'app_read_log_show', methods: ['GET'])]
    public function show(ReadLog $readLog): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'show-log',
            'read_log' => $readLog,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_read_log_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ReadLog $readLog, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReadLogType::class, $readLog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_reading_log', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/index.html.twig', [
            'currentPage' => 'edit-log',
            'read_log' => $readLog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_read_log_delete', methods: ['POST'])]
    public function delete(Request $request, ReadLog $readLog, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$readLog->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($readLog);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_reading_log', [], Response::HTTP_SEE_OTHER);
    }
}
