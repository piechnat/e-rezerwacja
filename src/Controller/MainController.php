<?php

namespace App\Controller;

use App\CustomTypes\Lang;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function main()
    {
        return $this->render('main/index.html.twig');
    }

    /**
     * @Route("/lang/{lang}", name="change_lang")
     */
    public function lang(string $lang, Request $request)
    {
        $user = $this->getUser();
        $response = $this->redirectToRoute('main');
        $referer = $request->headers->get('referer');

        if (false !== strpos($referer, $request->getHost())) {
            $response = $this->redirect($referer);
        }
        if ($user) {
            $user->setLang(Lang::valid($lang));
            $this->getDoctrine()->getManager()->flush();
        }
        $response->headers->setCookie(Lang::createCookie($lang));

        return $response;
    }
}
