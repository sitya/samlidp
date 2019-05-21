<?php

namespace AppBundle\Security\TwoFactor;

use AppBundle\Entity\WebauthnCredential;
use Doctrine\ORM\EntityManagerInterface;
use MadWizard\WebAuthn\Exception\WebAuthnException;
use MadWizard\WebAuthn\Server\Authentication\AuthenticationOptions;
use MadWizard\WebAuthnBundle\Manager\WebAuthnManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class WebauthnTwoFactorFormRenderer implements TwoFactorFormRendererInterface
{
    /** @var Environment */
    private $twig;

    /** @var WebAuthnManager */
    private $webauthnManager;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var Router */
    private $router;

    /**
     * PHP 5 allows developers to declare constructor methods for classes.
     * Classes which have a constructor method call this method on each newly-created object,
     * so it is suitable for any initialization that the object may need before it is used.
     *
     * Note: Parent constructors are not called implicitly if the child class defines a constructor.
     * In order to run a parent constructor, a call to parent::__construct() within the child constructor is required.
     *
     * param [ mixed $args [, $... ]]
     * @link https://php.net/manual/en/language.oop5.decon.php
     */
    public function __construct($twig, $entityManager, $webauthnManager, $tokenStorage, $router)
    {
        $this->entityManager = $entityManager;
        $this->webauthnManager = $webauthnManager;
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }


    /**
     * Render the authentication form of a two-factor provider.
     *
     * @param Request $request
     * @param array $templateVars
     *
     * @return Response
     * @throws
     */
    public function renderForm(Request $request, array $templateVars): Response
    {
        // TODO: Implement renderForm() method.
        $vars = [];

        if ($request->getMethod() == "POST") {
            $result = $this->webauthnManager->finishAuthenticationFromRequest($request);
            $vars['credentialId'] = $result->getUserCredential()->getCredentialId();
            // set validated 2nd factor fact to session
//            $this->addFlash('success', 'You authenticate successful with 2nd factor token.');
            //return new RedirectResponse($this->router->generate('app_idp_idplist'));
            return new Response("OK");
        }

        try {
            $options = new AuthenticationOptions();

            // Specify which credentials are allowed to authenticate
            $credential = $this->entityManager
                ->getRepository(WebauthnCredential::class)
                ->findOneByUser($this->tokenStorage->getToken()->getUser());
            $options->addAllowCredential($credential);
            // Get array with configuration for webauthn client
            $clientOptions = $this->webauthnManager->startAuthentication($options);
            $vars['clientOptions'] = $clientOptions;
        } catch (WebAuthnException $e) {
            // NOTE: do not pass exception messages to the client. The exception messages could contain
            // security sensitive information useful for attackers.
            $vars['error'] = "Webauthn authentication failed";
        }

        $content = $this->twig->render('AppBundle:SecondFactor:webauthn_authentication.html.twig', $vars);
        return new Response($content);
    }
}