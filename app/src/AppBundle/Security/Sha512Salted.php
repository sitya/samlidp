<?php
namespace AppBundle\Security;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class Sha512Salted implements PasswordEncoderInterface
{

    public function encodePassword($raw, $salt)
    {
        $digested = openssl_digest($raw . $salt, 'sha512', TRUE);
        return base64_encode($digested . $salt);
    }

    public function isPasswordValid($encoded, $raw, $salt)
    {
        return $encoded === $this->encodePassword($raw, $salt);
    }

}
