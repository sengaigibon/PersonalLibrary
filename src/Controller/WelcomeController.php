<?php

namespace App\Controller;

use App\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class WelcomeController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(PersonRepository $personRepo, SessionInterface $session): Response
    {
        $session->clear();
        
        $readers = $personRepo->findAll();
        return $this->render('welcome/index.html.twig', [
            'controller_name' => 'WelcomeController',
            'readersList' => $readers,
        ]);
    }

    #[Route('/setReader', name: 'app_set_reader', methods: ['POST'])]
    public function setReader(Request $request, PersonRepository $personRepo, SessionInterface $session): Response
    {
        $readerId = json_decode($request->getContent() ?? '{}', true)['readerId'] ?? null;
        if (!$readerId) {
            return $this->json(['message' => 'Choose a reader'], Response::HTTP_BAD_REQUEST);
        }

        $reader = $personRepo->find($readerId);

        if (!$reader) {
            return $this->json(['message' => 'Reader not found'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $session->set('current_reader_id', $reader->getId());
            $session->set('current_reader', [
                'id' => $reader->getId(),
                'nickname' => $reader->getNickname(),
                'fullName' => $reader->getFullName()
            ]);
        } catch (\Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => true], Response::HTTP_OK);
    }
}
