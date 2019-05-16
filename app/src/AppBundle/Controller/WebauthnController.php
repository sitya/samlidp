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
use AppBundle\Entity\WebauthnCredential;
use AppBundle\Security\CredentialStore;
use MadWizard\WebAuthn\Exception\WebAuthnException;
use MadWizard\WebAuthn\Format\ByteBuffer;
use MadWizard\WebAuthn\Server\Authentication\AuthenticationOptions;
use MadWizard\WebAuthn\Server\Registration\AttestationResult;
use MadWizard\WebAuthn\Server\Registration\RegistrationOptions;
use MadWizard\WebAuthn\Server\UserIdentity;
use MadWizard\WebAuthnBundle\Manager\WebAuthnManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//use Symfony\Component\Security\Core\Security;


/**
 * Controller managing security.
 *
 * @author Gyula Szabó <gyufi@szabocsalad.com>
 * @Route("/webauthn")
 * @Security("has_role('ROLE_USER')")
 */
class WebauthnController extends Controller
{

    /**
     * @param Request $request
     * @return Response
     * @Route("/register")
     */
    public function registerAction(Request $request)
    {
        $vars = [];

        $manager = $this->get('madwizard_webauthn.manager');
        /** @var CredentialStore $store */
        $store = $this->get('appbundle.webauthn_credentialstore');

        $posted = $request->isMethod('POST');
        $vars['posted'] = $posted;
        try {
            if (!$posted) {

                // Get user identity. Note that the userHandle should be a unique identifier for each user
                // (max 64 bytes). The WebAuthn specs recommend generating a random byte sequence for each
                // user. The code below is just for testing purposes!

                /** @var User $user */
                $user = $this->getUser();
                $userIdentity = new UserIdentity(ByteBuffer::fromHex(sha1($user->getUsernameCanonical())), $user->getUsernameCanonical(), $user->getGivenName() . " " . $user->getSn());
                // Setup options
                $options = new RegistrationOptions($userIdentity);

                // Get array with configuration for webauthn client
                $clientOptions = $manager->startRegistration($options);

                $vars['clientOptions'] = $clientOptions;
            } else {

                /** @var User $user */
                $user = $this->getUser();
                $userHandler = ByteBuffer::fromBase64Url($user->getUserHandleBase64Url());

                /** @var AttestationResult $result */
                $result = $manager->finishRegistrationFromRequest($request);

                /*$credentialRegistration = new CredentialRegistration(
                    $result->getCredentialId(),
                    $result->getPublicKey(),
                    $userHandler
                );
                $store->registerCredential($credentialRegistration);
                */
                $this->getDoctrine()->getManager()->flush();
                // Credential is now registered

                // For this demo, show credential ID via twig:
                $vars['credentialId'] = $result->getCredentialId();
            }
        } catch (WebAuthnException $e) {
            // NOTE: do not pass exception messages to the client. The exception messages could contain
            // security sensitive information useful for attackers.
            $this->get('logger')->error($e->getMessage());
            $vars['error'] = "Registration failed";
        }


        return $this->render("AppBundle:SecondFactor:webauthn_registration.html.twig",
            $vars
        );
    }

    /**
     * @param Request $request
     * @param WebAuthnManager $manager
     * @param CredentialStore $store
     * @return Response
     * @Route("/authenticate")
     */
    public function authenticate(Request $request)
    {
        $vars = [];

        $manager = $this->get('madwizard_webauthn.manager');
        $entityManager = $this->getDoctrine()->getManager();

        $posted = $request->isMethod('POST');
        $vars['posted'] = $posted;
        try {
            if (!$posted) {
                $options = new AuthenticationOptions();

                // Specify which credentials are allowed to authenticate
                $credential = $entityManager->getRepository(WebauthnCredential::class)->findOneByUser($this->getUser());
                $options->addAllowCredential($credential);

                // Get array with configuration for webauthn client
                $clientOptions = $manager->startAuthentication($options);
                $vars['clientOptions'] = $clientOptions;
            } else {
                $result = $manager->finishAuthenticationFromRequest($request);
                $vars['credentialId'] = $result->getUserCredential()->getCredentialId();
                // set validated 2nd factor fact to session
                $this->get('session')->set('2ndfactor', 'fido');
                $this->addFlash('success', 'You authenticate successful with 2nd factor token.');
                return $this->redirectToRoute('app_idp_idplist');
            }
        } catch (WebAuthnException $e) {
            // NOTE: do not pass exception messages to the client. The exception messages could contain
            // security sensitive information useful for attackers.
            $vars['error'] = "Authentication failed";
            $this->container->get('security.token_storage')->setToken(null);

        }

        return $this->render('AppBundle:SecondFactor:webauthn_authentication.html.twig', $vars);
    }
}
