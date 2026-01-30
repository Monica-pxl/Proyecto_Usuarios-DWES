<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * Página de inicio - Redirige según autenticación
     */
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    /**
     * Página de login
     */
    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('home/login.html.twig');
    }

    /**
     * Página de home (protegida)
     */
    #[Route('/home', name: 'app_home_page')]
    public function home(): Response
    {
        return $this->render('home/home.html.twig');
    }

    /**
     * Documentación de APIs
     */
    #[Route('/api', name: 'app_api_docs')]
    public function apiDocs(): Response
    {
        return $this->render('home/api_docs.html.twig');
    }
}
