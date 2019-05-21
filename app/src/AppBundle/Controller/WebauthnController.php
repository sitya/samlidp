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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     * @Route("/delete")
     * @return RedirectResponse
     */
    public function deleteAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $this->getUser();
        foreach ($user->getWebauthnCredentials() as $webauthnCredential) {
            $entityManager->remove($webauthnCredential);
        }
        $entityManager->flush();

        $this->addFlash('success', 'Your FIDO token has been deleted.');
        return $this->redirectToRoute('app_secondfactormanager_list');

    }

}
