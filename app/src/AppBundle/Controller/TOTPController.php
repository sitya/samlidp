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
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

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
        if (! $user->isTotpAuthenticationEnabled()) {

            /** @var TotpAuthenticator $totpAuthenticator */
            $totpAuthenticator = $this->get("scheb_two_factor.security.totp_authenticator");

            $posted = $request->isMethod('POST');
            $vars = [];

            try {
                if ($posted) {
                    $message = $request->get('message');
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
                        $vars['message'] = $message;
                        $vars['secret'] = $secret;
                        $vars['error'] = "Invalid TOTP!";
                    }
                } else {
                    $secret = $totpAuthenticator->generateSecret();
                    $user->setTotpSecret($secret);
                    $qrCodeContent = $totpAuthenticator->getQRContent($user);
                    $vars['message'] = $qrCodeContent;
                    $vars['secret'] = $secret;
                }
            } catch (\Exception $e) {
                // NOTE: do not pass exception messages to the client. The exception messages could contain
                // security sensitive information useful for attackers.
                $this->get('logger')->error($e->getMessage());
                $vars['error'] = "Registration failed";
            }

            return $this->render("AppBundle:SecondFactor:totp_registration.html.twig", $vars);
        }
        $this->addFlash('error', 'You already have registered TOTP. You have to delete it before regsiter a new one.');
        return $this->redirectToRoute('app_secondfactormanager_list');

    }

    /**
     * @Route("/delete")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction()
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setTotpSecret(null);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Your TOTP token has been deleted.');
        return $this->redirectToRoute('app_secondfactormanager_list');

    }
}
