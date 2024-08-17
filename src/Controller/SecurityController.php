<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login()
    {
        if ($this->isGranted('IS_AUTHENTICATED')) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->render(
            'login.html.twig'
        );
    }

    #[Route('/', name: 'dashboard')]
    #[IsGranted('IS_AUTHENTICATED')]
    public function dashboard()
    {
        return $this->render(
            'default/dashboard.html.twig'
        );
    }

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        return $this->render(
            'default/profile.html.twig',
            [
                'user' => $this->getUser(),
            ]
        );
    }
}
