<?php


namespace AppBundle\Security\TwoFactor\Provider;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;

/**
 * Class Webauth
 * @package AppBundle\Security\TwoFactor\Provider
 */
class Webauthn implements TwoFactorProviderInterface
{

    private $formRenderer;
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
    public function __construct(TwoFactorFormRendererInterface $formRenderer)
    {
        $this->formRenderer = $formRenderer;
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
        // TODO: Implement beginAuthentication() method.
        return false;
    }

    /**
     * Do all steps necessary to prepare authentication, e.g. generate & send a code.
     *
     * @param mixed $user
     */
    public function prepareAuthentication($user): void
    {
        // TODO: Implement prepareAuthentication() method.
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
        // TODO: Implement validateAuthenticationCode() method.
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