<?php

namespace App\Controller;

use App\Entity\ReadLog;
use App\Form\ReadLogType;
use App\Repository\ReadLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/read/log')]
final class ReadLogController extends AbstractController
{
    #[Route(name: 'app_read_log_index', methods: ['GET'])]
    public function index(ReadLogRepository $readLogRepository): Response
    {
        return $this->render('read_log/index.html.twig', [
            'read_logs' => $readLogRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_read_log_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $readLog = new ReadLog();
        $form = $this->createForm(ReadLogType::class, $readLog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($readLog);
            $entityManager->flush();

            return $this->redirectToRoute('app_read_log_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('read_log/new.html.twig', [
            'read_log' => $readLog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_read_log_show', methods: ['GET'])]
    public function show(ReadLog $readLog): Response
    {
        return $this->render('read_log/show.html.twig', [
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

            return $this->redirectToRoute('app_read_log_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('read_log/edit.html.twig', [
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

        return $this->redirectToRoute('app_read_log_index', [], Response::HTTP_SEE_OTHER);
    }
}
