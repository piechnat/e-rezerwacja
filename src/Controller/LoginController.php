<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function login(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')->redirect([
            'profile', 'email', // the scopes you want to access
        ], []);
    }

    /**
     * The "redirect_route" configured in config/packages/knpu_oauth2_client.yaml.
     *
     * @Route("/login/check", name="login_check")
     */
    public function check(): Response
    {
        return $this->redirectToRoute('main');
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): Response
    {
        return $this->redirectToRoute('main');
    }
}
