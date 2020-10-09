<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function main(Request $request)
    {
        return $this->render('main/index.html.twig');
    }

    /**
     * @Route("/lang/{lang}", name="change_lang")
     */
    public function lang(string $lang, Request $request)
    {
        $response = $this->redirectToRoute('main');

        if (in_array($lang, ['pl', 'en'])) {
            $referer = $request->headers->get('referer');
            if (false !== strpos($referer, $request->getHost())) {
                $response = $this->redirect($referer);
            }
            $response->headers->setCookie(new Cookie('lang', $lang, 'now +1 year'));
        }

        return $response;
    }
}
