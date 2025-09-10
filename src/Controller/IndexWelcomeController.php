<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexWelcomeController extends AbstractController
{
    #[Route('/index', name: 'app_index')]
    #[Route('/welcome', name: 'app_welcome')]
    public function index(): Response
    {
        return $this->render('index_welcome/index.html.twig', [
            'controller_name' => 'IndexWelcomeController',
        ]);
    }
}
