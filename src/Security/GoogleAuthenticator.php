<?php

namespace App\Security;

use App\CustomTypes\Lang;
use App\CustomTypes\UserLevel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class GoogleAuthenticator extends SocialAuthenticator
{
    private $clientRegistry;
    private $em;
    private $request;
    private $router;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        RouterInterface $router
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
    }

    public function supports(Request $request)
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return 'login_check' === $request->attributes->get('_route');
    }

    public function getCredentials(Request $request)
    {
        // this method is only called if supports() returns true
        $this->request = $request;

        return $this->fetchAccessToken($this->getGoogleClient());
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var GoogleUser $googleUser */
        $googleUser = $this->getGoogleClient()->fetchUserFromToken($credentials);
        $email = $googleUser->getEmail();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $fullname = $googleUser->getName();
            $email_fullname = explode('@', $email)[0];
            if (0 === strlen($fullname)) {
                $fullname = ucwords(str_replace('.', ' ', $email_fullname));
            }
            $isStudent = preg_match('/\\d{2}/', $email_fullname);
            $user = new User();
            $user->setEmail($email);
            $user->setFullname($fullname);
            $user->setLang(Lang::fromCookie($this->request->cookies));
            $user->setAccess($isStudent ? UserLevel::USER : UserLevel::SUPER_USER);
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $session = $request->getSession();
        $redirect_uri = $session->get('login_success_redirect');

        if (null !== $redirect_uri) {
            $session->remove('login_success_redirect');
        } else {
            $redirect_uri = $this->router->generate('main');
        }

        return new RedirectResponse($redirect_uri, Response::HTTP_TEMPORARY_REDIRECT);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $request->getSession()->set('login_success_redirect', $request->getRequestUri());

        return new RedirectResponse('/login', Response::HTTP_TEMPORARY_REDIRECT);
    }

    public function supportsRememberMe(): bool
    {
        return true;
    }

    /**
     * @return GoogleClient
     */
    private function getGoogleClient()
    {
        return $this->clientRegistry->getClient('google');
    }
}
