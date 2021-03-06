<?php

namespace AppBundle\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\GuardTokenInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Tistre\SimpleOAuthLogin\OAuthInfo;


class SessionAuthenticator implements AuthenticatorInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var RouterInterface */
    protected $router;

    /** @var SimpleOAuthConfig */
    protected $simpleOAuthConfig;


    public function __construct(RouterInterface $router, LoggerInterface $logger, SimpleOAuthConfig $simpleOAuthConfig)
    {
        $this->router = $router;
        $this->logger = $logger;
        $this->simpleOAuthConfig = $simpleOAuthConfig;
    }


    /**
     * Does the authenticator support the given Request?
     *
     * If this returns false, the authenticator will be skipped.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        return true;
    }


    /**
     * Get the authentication credentials from the request and return them
     * as any type (e.g. an associate array).
     *
     * Whatever value you return here will be passed to getUser() and checkCredentials()
     *
     * @param Request $request
     * @return mixed Any non-null value
     *
     * @throws \UnexpectedValueException If null is returned
     */
    public function getCredentials(Request $request)
    {
        $sessionInfo = $request->getSession()->get('oauth_info');

        /*
        Not logged in? Check for API key in Authorization bearer token and look for a corresponding
        user in parameters.yml (simple_oauth_login.user_details) with this value in the api_key property.

        Example HTTP header:

        Authorization: "Bearer ChangeThisToSomeWeirdLongApiKey"

        Example parameters.yml entry:

        simple_oauth_login:
            user_details:
                'dummy@example.com':
                    name: 'Dummy user'
                    api_key: 'ChangeThisToSomeWeirdLongApiKey'
                    roles:
                        - 'ROLE_USER'
        */

        if (empty($sessionInfo)) {
            $headers = apache_request_headers();

            if (isset($headers['Authorization']) && (substr($headers['Authorization'], 0, 7) === 'Bearer ')) {
                $apiKey = trim(substr($headers['Authorization'], 7));

                foreach ($this->simpleOAuthConfig->getUserDetails() as $userMail => $userDetail) {
                    if (isset($userDetail['api_key']) && ($userDetail['api_key'] === $apiKey)) {
                        return (new OAuthInfo([]))
                            ->setAuthenticated(true)
                            ->setProvider('fake')
                            ->setName($userDetail['name'])
                            ->setMail($userMail)
                            ->setGroups($userDetail['roles']);
                    }
                }
            }
        }

        return new OAuthInfo($sessionInfo);
    }


    /**
     * Return a UserInterface object based on the credentials.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * You may throw an AuthenticationException if you wish. If you return
     * null, then a UsernameNotFoundException is thrown for you.
     *
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @throws AuthenticationException
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (!(is_object($credentials) && ($credentials instanceof OAuthInfo))) {
            $credentials = new OAuthInfo([]);
        }

        $user = $userProvider->loadUserByUsername($credentials->getMail());

        if (!$user instanceof User) {
            throw new AuthenticationException('User provider must return an instance of ' . User::class);
        }

        // If the user cannot be loaded, keep at least mail and name

        if ($user->getUsername() === User::DEFAULT_USERNAME) {
            $user->setMail($credentials->getMail());
            $user->setName($credentials->getName());

            $roles = [];

            foreach ($credentials->getGroups() as $group) {
                $roles[] = 'ROLE_' . strtoupper($group['name']);
            }

            $user->setRoles($roles);
        }

        return $user;
    }


    /**
     * Returns true if the credentials are valid.
     *
     * If any value other than true is returned, authentication will
     * fail. You may also throw an AuthenticationException if you wish
     * to cause authentication to fail.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * @param mixed $credentials
     * @param UserInterface $user
     *
     * @return bool
     *
     * @throws AuthenticationException
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        if (!(is_object($credentials) && ($credentials instanceof OAuthInfo))) {
            $credentials = new OAuthInfo([]);
        }

        if (!$credentials->isAuthenticated()) {
            throw new AuthenticationException();
        }

        return true;
    }


    /**
     * Create an authenticated token for the given user.
     *
     * If you don't care about which token class is used or don't really
     * understand what a "token" is, you can skip this method by extending
     * the AbstractGuardAuthenticator class from your authenticator.
     *
     * @see AbstractGuardAuthenticator
     *
     * @param UserInterface $user
     * @param string $providerKey The provider (i.e. firewall) key
     *
     * @return GuardTokenInterface
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        // TODO: Just copied from AbstractGuardAuthenticator ...
        return new PostAuthenticationGuardToken(
            $user,
            $providerKey,
            $user->getRoles()
        );
    }


    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new RedirectResponse($this->router->generate('login'));
    }


    /**
     * Called when authentication executed and was successful!
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey The provider (i.e. firewall) key
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }


    /**
     * Does this method support remember me cookies?
     *
     * Remember me cookie will be set if *all* of the following are met:
     *  A) This method returns true
     *  B) The remember_me key under your firewall is configured
     *  C) The "remember me" functionality is activated. This is usually
     *      done by having a _remember_me checkbox in your form, but
     *      can be configured by the "always_remember_me" and "remember_me_parameter"
     *      parameters under the "remember_me" firewall key
     *  D) The onAuthenticationSuccess method returns a Response object
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }


    /**
     * Returns a response that directs the user to authenticate.
     *
     * This is called when an anonymous request accesses a resource that
     * requires authentication. The job of this method is to return some
     * response that "helps" the user start into the authentication process.
     *
     * Examples:
     *  A) For a form login, you might redirect to the login page
     *      return new RedirectResponse('/login');
     *  B) For an API token authentication system, you return a 401 response
     *      return new Response('Auth header required', 401);
     *
     * @param Request $request The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse($this->router->generate('login'));
    }
}
