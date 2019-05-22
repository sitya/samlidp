<?php

namespace AppBundle\Security\TwoFactor;

use AppBundle\Entity\WebauthnCredential;
use Doctrine\ORM\EntityManagerInterface;
use MadWizard\WebAuthn\Server\Authentication\AuthenticationOptions;
use MadWizard\WebAuthnBundle\Manager\WebAuthnManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @param array $vars templateVars
     *
     * @return Response
     * @throws
     */
    public function renderForm(Request $request, array $vars): Response
    {
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
        } catch (\Exception $exception) {
            // NOTE: do not pass exception messages to the client. The exception messages could contain
            // security sensitive information useful for attackers.
            $vars['error'] = "Webauthn authentication failed";
        }

        $content = $this->twig->render('AppBundle:TwoFactor:webauthn_authentication.html.twig', $vars);
        return new Response($content);
    }
}