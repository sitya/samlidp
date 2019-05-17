<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use MadWizard\WebAuthn\Exception\WebAuthnException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//use Symfony\Component\Security\Core\Security;


/**
 * Controller managing schab two factor TOTP tokens.
 *
 * @author Gyula Szabó <gyufi@szabocsalad.com>
 * @Route("/totp")
 * @Security("has_role('ROLE_USER')")
 */
class TOTPController extends Controller
{

    /**
     * @param Request $request
     * @return Response
     * @Route("/register")
     */
    public function registerAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var TotpAuthenticator $totpAuthenticator */
        $totpAuthenticator = $this->get("scheb_two_factor.security.totp_authenticator");

        $posted = $request->isMethod('POST');
        $vars = [];

        try {
            $secret = $totpAuthenticator->generateSecret();
            $qrCodeContent = $totpAuthenticator->getQRContent($user);
            $vars['message'] = $qrCodeContent . $secret;
            $vars['secret'] = $secret;

            if ($posted) {
                $secret = $request->get('secret');
                $code = $request->get('code');

                $user->setTotpSecret($secret);

                // persist secret and redirect to secondfactor manager
                if ($totpAuthenticator->checkCode($user, $code)) {

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('success', 'Your TOTP token has been registered.');
                    return $this->redirectToRoute('app_secondfactormanager_list');
                } else {
                    $vars['error'] = "Invalid TOTP!";
                }
            }
        } catch (WebAuthnException $e) {
            // NOTE: do not pass exception messages to the client. The exception messages could contain
            // security sensitive information useful for attackers.
            $this->get('logger')->error($e->getMessage());
            $vars['error'] = "Registration failed";
        }

        return $this->render("AppBundle:SecondFactor:totp_registration.html.twig", $vars);
    }

}
