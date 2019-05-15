<?php

namespace AppBundle\Security\TwoFactor\Google;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RequestListener
{
    /**
     * @var \AppBundle\Security\TwoFactor\Google\Helper
     */
    protected $helper;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    protected $templating;

    /**
     * @param \AppBundle\Security\TwoFactor\Google\Helper                $helper
     * @param \Symfony\Component\Security\Core\SecurityContextInterface  $tokenStorage
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating
     */
    public function __construct(Helper $helper, tokenStorage $tokenStorage, EngineInterface $templating)
    {
        $this->helper = $helper;
        $this->tokenStorage = $tokenStorage;
        $this->templating = $templating;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     *
     * @return
     */
    public function onCoreRequest(GetResponseEvent $event)
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return;
        }

        if (!$token instanceof UsernamePasswordToken) {
            return;
        }

        $key = $this->helper->getSessionKey($this->tokenStorage->getToken());
        $request = $event->getRequest();
        $session = $event->getRequest()->getSession();
        $user = $this->tokenStorage->getToken()->getUser();

        //Check if user has to do two-factor authentication
        if (!$session->has($key)) {
            return;
        }
        if ($session->get($key) === true) {
            return;
        }

        if ($request->getMethod() == 'POST') {
            //Check the authentication code
            if ($this->helper->checkCode($user, $request->get('_auth_code')) == true) {
                //Flag authentication complete
                $session->set($key, true);

                //Redirect to user's dashboard
                $redirect = new RedirectResponse($this->router->generate('user_dashboard'));
                $event->setResponse($redirect);

                return;
            } else {
                $session->getFlashBag()->set('error', 'The verification code is not valid.');
            }
        }

        //Force authentication code dialog
        $response = $this->templating->renderResponse('AppBundle:TOTP:form.html.twig');
        $event->setResponse($response);
    }
}
