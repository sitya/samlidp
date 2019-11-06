<?php

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Class TokenAuthenticator
 * @package AppBundle\Security
 */
class TokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return $request->headers->has('X-AUTH-TOKEN') && $request->headers->has('X-AUTH-CLIENT-ID');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        return [
            'token' => $request->headers->get('X-AUTH-TOKEN'),
            'clientId' => $request->headers->get('X-AUTH-CLIENT-ID'),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $secret = $credentials['token'];
        $clientId = $credentials['clientId'];

        if (null === $secret || null === $clientId) {
            return;
        }

        // if a User object, checkCredentials() is called
        return $userProvider->loadUserByUsername($clientId);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        return $user->getPassword() === $credentials['token'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $reason = "";
        if (!$request->headers->has('X-AUTH-TOKEN')){
            $reason .= " X-AUTH-TOKEN header is missing.";
        }
        if (!$request->headers->has('X-AUTH-CLIENT-ID')){
            $reason .= " X-AUTH-CLIENT-ID header is missing.";
        }
        $data = [
            // you might translate this message
            'message' => 'Authentication Required.'.$reason,
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
