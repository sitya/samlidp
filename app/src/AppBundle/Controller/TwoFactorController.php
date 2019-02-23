<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_USER')")
 */
class TwoFactorController extends Controller
{
    /**
     * @Route("/reset2fa")
     */
    public function reset2faAction()
    {
        $response = new JsonResponse();

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $tfa_provider = $this->get('appbundle.twofactor.google.provider');
        $secret = $tfa_provider->generateSecret();
        $user->setGoogleAuthenticatorCode($secret);

        $em->persist($user);
        $em->flush();

        $response->setData($secret);
        return $response;
    }

    /**
     * @Route("/disable2fa")
     */
    public function disable2faAction()
    {
        $response = new JsonResponse();

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $user->setGoogleAuthenticatorCode('');

        $em->persist($user);
        $em->flush();

        $response->setData('');
        return $response;
    }

    /**
     * @Route("/verify2fa")
     */
    public function verify2faAction(Request $request)
    {
        $response = new JsonResponse();
        // dump($request->get('data'));

        $user = $this->getUser();
        $tfa_provider = $this->get('appbundle.twofactor.google.provider');
        $code = $request->get('data');
        $response->setData(
            array(
                "response" => $tfa_provider->checkCode($user, $code),
                "expected" => $tfa_provider->getCode($user)
            )
        );
        
        return $response;
    }
}
