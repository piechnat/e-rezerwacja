<?php

namespace App\Controller;

use App\CustomTypes\Lang;
use App\CustomTypes\UserLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function main(): Response
    {
        return $this->forward('App\\Controller\\'.(
            $this->isGranted(UserLevel::USER) ? 'UserController::show' : 'MainController::about'
        ));
    }

    /**
     * @Route("/about", name="about")
     */
    public function about(Request $request): Response
    {
        return $this->render('main/about.html.twig');
    }

    /**
     * @Route("/lang/{lang}", name="change_lang")
     */
    public function lang(string $lang, Request $request): Response
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
