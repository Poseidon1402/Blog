<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private $urlGenerator;
    private $flash;

    public function __construct(UrlGeneratorInterface $urlGenerator, FlashBagInterface $flash)
    {
        $this->urlGenerator = $urlGenerator;
        $this->flash = $flash;
    }

    public function authenticate(Request $request): PassportInterface
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            $this->flash->add('success','Welcome');
            
            return new RedirectResponse($targetPath);
        }

        $this->flash->add('success','Welcome '.$token->getUser()->getFirstName().' '.$token->getUser()->getLastName());

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
        
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    /**
     * Override to control what happens when the user hits a secure page
     * but isn't logged in yet.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $this->flash->add('danger', 'You should log in before accessing this page');
        
        $url = $this->getLoginUrl($request);

        return new RedirectResponse($url);
    }
}
