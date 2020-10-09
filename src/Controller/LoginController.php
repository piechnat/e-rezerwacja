<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry->getClient('google')->redirect([
            'profile', 'email', // the scopes you want to access
        ], []);
    }

    /**
     * The "redirect_route" configured in config/packages/knpu_oauth2_client.yaml.
     *
     * @Route("/login/check", name="login_check")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
        $session = $request->getSession();
        $redirect_uri = $session->get('login_success_redirect');

        if (null !== $redirect_uri) {
            $session->remove('login_success_redirect');

            return $this->redirect($redirect_uri);
        }

        return $this->redirectToRoute('main');
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout()
    {
        return $this->redirectToRoute('main');
    }
}
