<?php

namespace App\EventSubscriber;

use App\CustomTypes\Lang;
use App\CustomTypes\UserLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

class LocaleSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $security;

    public function __construct(Environment $twig, Security $security)
    {
        $this->twig = $twig;
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $request->setLocale(Lang::fromCookie($request->cookies));
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        /** @var User */
        $user = $this->security->getUser();
        if (null !== $user) {
            if ($this->security->isGranted(UserLevel::DISABLED)) {
                $event->setResponse(new Response($this->twig->render('main/forbidden.html.twig')));
            }
            if ($user->getLang() !== $event->getRequest()->cookies->get('lang')) {
                $event->getResponse()->headers->setCookie(Lang::createCookie($user->getLang()));
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
            KernelEvents::RESPONSE => [['onKernelResponse', 20]],
        ];
    }
}
