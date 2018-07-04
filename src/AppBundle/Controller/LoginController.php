<?php

namespace AppBundle\Controller;

use AppBundle\Security\SimpleOAuthConfig;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tistre\SimpleOAuthLogin\Login;
use Tistre\SimpleOAuthLogin\OAuthInfo;


class LoginController extends Controller
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var SimpleOAuthConfig */
    protected $simpleOAuthConfig;


    /**
     * LoginController constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, SimpleOAuthConfig $simpleOAuthConfig)
    {
        $this->logger = $logger;
        $this->simpleOAuthConfig = $simpleOAuthConfig;
    }


    /**
     * @Route("/login", name="login")
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $services = array_keys($this->simpleOAuthConfig->getOAuthConfigs());

        // TODO: Support more than one OAuth provider (let the user choose from a list)
        // (Currently, we simply use the first configured provider.)

        foreach ($services as $service) {
            return new RedirectResponse($this->generateUrl('serviceLogin', ['service' => $service]));
        }

        return new RedirectResponse($this->generateUrl('fakeLogin'));
    }


    /**
     * TODO: Who on earth insists on redirecting to login/ ?
     *
     * @Route("/login/")
     * @param Request $request
     * @return Response
     */
    public function loginSlashAction(Request $request)
    {
        return $this->loginAction($request);
    }


    /**
     * @Route("/login/fake", name="fakeLogin")
     * @param Request $request
     * @return Response
     */
    public function fakeLoginAction(Request $request)
    {
        $oAuthFakeParams = $this->simpleOAuthConfig->getFakeOAuth();

        if (!$oAuthFakeParams['enabled']) {
            return new Response('Not found.', 404);
        }

        $user = $this->simpleOAuthConfig->getUserByUsername($oAuthFakeParams['mail']);

        $oAuthInfo = (new OAuthInfo([]))
            ->setAuthenticated(true)
            ->setProvider('fake')
            ->setMail($user->getMail())
            ->setName($user->getName());

        $request->getSession()->set('oauth_info', $oAuthInfo->getArray());

        return new RedirectResponse($request->getBaseUrl());
    }


    /**
     * @Route("/login/{service}", name="serviceLogin")
     * @param Request $request
     * @param string $service
     * @return Response
     */
    public function serviceLoginAction(Request $request, $service)
    {
        $configs = $this->simpleOAuthConfig->getOAuthConfigs();

        if (!isset($configs[$service])) {
            return new Response('Not found.', 404);
        }

        $oauthLogin = new Login();
        $oauthLogin->addServiceConfigsFromArray($configs);

        if ($request->getMethod() === 'GET') {
            if ($request->query->has('code')) {
                return $this->onReturnFromService($request, $oauthLogin, $service);
            } elseif ($request->query->has('error')) {
                $this->logger->error(__METHOD__ . ': Service returned error: ' . $request->query->get('error'));
                return new Response("Error. See log file for details.\n", 400);
            } else {
                return $this->redirectToService($request, $oauthLogin, $service);
            }
        } else {
            $this->logger->error(__METHOD__ . ': Unsupported request method ' . $request->getMethod());
            return new Response("Error. See log file for details.\n", 400);
        }
    }


    /**
     * @param Request $request
     * @param Login $oauthLogin
     * @param $service
     * @return Response
     */
    protected function redirectToService(Request $request, Login $oauthLogin, $service)
    {
        $oauthService = $oauthLogin->getService($service);

        $authorizationUrl = $oauthService->getAuthorizationUrl();

        // The OAuth library automatically generates a state value that we can
        // validate later. We just save it for now.

        $oAuthInfo = new OAuthInfo($request->getSession()->get('oauth_info'));

        $oAuthInfo
            ->setState($oauthService->getProvider()->getState())
            ->setRedirectAfterlogin($request->query->get('redirect_after_login', ''));

        $request->getSession()->set('oauth_info', $oAuthInfo->getArray());

        return new RedirectResponse($authorizationUrl);
    }


    /**
     * @param Request $request
     * @param Login $oauthLogin
     * @param string $service
     * @return Response
     */
    protected function onReturnFromService(Request $request, Login $oauthLogin, $service)
    {
        $oAuthInfo = new OAuthInfo($request->getSession()->get('oauth_info'));

        // Validate the OAuth state parameter

        $state = $request->query->get('state');

        if ((strlen($state) === 0) || ($state !== $oAuthInfo->getState())) {
            $oAuthInfo->setState('');
            $request->getSession()->set('oauth_info', $oAuthInfo->getArray());

            $this->logger->error(__METHOD__ . ': State value does not match the one initially sent');

            return new Response("Error. See log file for details.\n", 400);
        }

        // With the authorization code, we can retrieve access tokens and other data.
        try {
            $oauthService = $oauthLogin->getService($service);

            // Get an access token using the authorization code grant
            $accessToken = $oauthService->getAuthorizationCodeAccessToken($_GET['code']);

            // We got an access token, let's now get the user's details
            $userDetails = $oauthService->getUserDetails($accessToken);

            $oAuthInfo
                ->setAuthenticated(true)
                ->setProvider($oauthService->getService())
                ->setAccessToken($accessToken->getToken())
                ->setName($userDetails['name'])
                ->setMail($userDetails['mail'])
                ->setImage($userDetails['image'])
                ->setUrl($userDetails['url'])
                ->setGroups($oauthService->getUserGroups($accessToken));

            $request->getSession()->set('oauth_info', $oAuthInfo->getArray());

            $redirect = $oAuthInfo->getRedirectAfterlogin();

            if (strlen($redirect) === 0) {
                $redirect = $request->getBaseUrl();
            }

            return new RedirectResponse($redirect);
        } catch (IdentityProviderException $e) {
            $this->logger->error(__METHOD__ . ': Something went wrong, couldn\'t get tokens: ' . $e->getMessage());

            return new Response("Error. See log file for details.\n", 400);
        }
    }
}
