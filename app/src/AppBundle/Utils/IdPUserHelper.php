<?php

namespace AppBundle\Utils;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * IdPUserHelper.
 */
class IdPUserHelper
{
    public function __construct()
    {
        # code...
    }

    public static function sendPasswordResetToken($idpuser, $token, $router, $twig, $mailer, $fromaddress, $template)
    {
        $url = $router->generate(
            'idpuser_reset_password',
            array('token' => $token),
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $message = new \Swift_Message();
        $message->setSubject('Password token');
        $message->setTo($idpuser->getEmail());
        $message->setFrom($fromaddress);
        $message->setBody($twig->render('AppBundle:IdPUser:'.$template.'.txt.twig', array('idpuser' => $idpuser, 'url' => $url)), 'text/plain');
        $mailer->send($message);
    }
}
