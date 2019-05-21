<?php


namespace AppBundle\Security\TwoFactor;

use Doctrine\ORM\PersistentCollection;
use MadWizard\WebAuthnBundle\Exception\ClientRegistrationException;
use MadWizard\WebAuthnBundle\Manager\WebAuthnManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Webauth
 * @package AppBundle\Security\TwoFactor\Provider
 */
class WebauthnTwoFactorProvider implements TwoFactorProviderInterface
{

    private $formRenderer;
    private $requestStack;
    private $webauthnManager;

    /**
     */
    public function __construct(TwoFactorFormRendererInterface $formRenderer, RequestStack $requestStack, WebAuthnManager $webauthnManager)
    {
        $this->formRenderer = $formRenderer;
        $this->requestStack = $requestStack;
        $this->webauthnManager = $webauthnManager;
    }


    /**
     * Return true when two-factor authentication process should be started.
     *
     * @param AuthenticationContextInterface $context
     *
     * @return bool
     */
    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        /** @var PersistentCollection $webautnCredentials */
        $webautnCredentials = $context->getUser()->getWebauthnCredentials();
        return $webautnCredentials->count() ? true : false;
    }

    /**
     * Do all steps necessary to prepare authentication, e.g. generate & send a code.
     *
     * @param mixed $user
     */
    public function prepareAuthentication($user): void
    {
        return;
    }

    /**
     * Validate the two-factor authentication code.
     *
     * @param mixed $user
     * @param string $authenticationCode
     *
     * @return bool
     */
    public function validateAuthenticationCode($user, string $authenticationCode): bool
    {
        $request = $this->requestStack->getMasterRequest();
        try {
            $this->webauthnManager->finishAuthenticationFromRequest($request);
        } catch (ClientRegistrationException $exception) {
            return false;
        }
        // set validated 2nd factor fact to session
        return true;
    }

    /**
     * Return the form renderer for two-factor authentication.
     *
     * @return TwoFactorFormRendererInterface
     */
    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return $this->formRenderer;
    }

}