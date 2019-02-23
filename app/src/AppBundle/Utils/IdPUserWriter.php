<?php

namespace AppBundle\Utils;

use Doctrine\ORM\EntityManager;
use Port\Writer;
use Port\Exception\WriterException as Exception;
use AppBundle\Entity\IdP;
use AppBundle\Entity\IdPUser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

/**
 *
 * ACTION: a felhasználóval végzendő művelet. Értékei lehetnek:
 * ADD (alapértelmezett): a felhasználói bejegyzés hozzáadása. Ha már létezik ilyen azonosítójú bejegyzés, a művelet sikertelen lesz és hibaüzenetet kapunk.
 * UPDATE: a felhasználói bejegyzés módosítása. Minden mező felülíródik. Ha a felhasználói bejegyzés nem létezik, akkor megegyezik az ADD művelettel.
 * DELETE: a felhasználói bejegyzés törlése. A program nem foglalkozik a sor többi mezőjével.
 *
 * SN (kötelező): a felhasználó vezetékneve. (Több szóból is állhat.)
 * GIVENNAME (kötelező): a felhasználó keresztneve. (Több szóból is állhat.)
 *
 * DISPLAYNAME: (kötelező)
 *
 * ENABLED: (kötelező) boolean
 *
 * ADD művelet esetén a felhasználó jelszómódosító formra irányuló tokent kap email-ben.
 * UPDATE művelet esetén a jelszó nem változik meg.
 */
class IdPUserWriter implements Writer
{

    private $idp;
    private $em;
    private $mailer;
    private $router;
    private $twig;
    private $doctrine;
    private $samlidp_hostname;

    public function __construct(IdP $idp, EntityManager $em, \Swift_Mailer $mailer, Router $router, \Twig_Environment $twig, Doctrine $doctrine, $samlidp_hostname)
    {
        $this->idp = $idp;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->router = $router;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->samlidp_hostname = $samlidp_hostname;
    }

    /**
     * Prepare the writer before writing the items
     */
    public function prepare()
    {
        # code ...
    }

    /**
     * Write one data item
     *
     * @param array $item The data item with converted values
     */
    public function writeItem(array $item)
    {
        $idpuser = null;
        foreach ($this->idp->getIdPUsers() as $iu) {
            if ($iu->getUsername() == $item['username']) {
                $idpuser = $iu;
                break;
            }
        }

        switch ($item['action']) {
            case 'add':
                if ($idpuser) {
                    throw new Exception("Can not add user, it is already exists.", 1);
                } else {
                    $this->validateEmailDuplum($item['email']);
                    $this->add($item, $idpuser);
                }
                break;
            case 'update':
                if (!$idpuser) {
                    throw new Exception("There is no user with this username.", 1);
                }
                $this->validateEmailDuplum($item['email'], $idpuser->getId());
                $this->update($item, $idpuser);
                break;
            case 'delete':
                if (!$idpuser) {
                    throw new Exception("Can not delete user, it is not exists.", 1);
                }
                $this->delete($idpuser);
                break;

            default:
                throw new Exception("Invalid action: " . $item['action'], 1);
                break;
        }
    }

    /**
     * Wrap up the writer after all items have been written
     */
    public function finish()
    {

    }

    private function update($item, IdPUser $idpuser)
    {
        $this->setProperties($idpuser, $item);
        try {
            $this->em->flush();
        } catch (\Exception $e) {
            throw new Exception($e);
        }
    }

    private function add($item)
    {
        $idpuser = new IdPUser();

        $idpuser->setIdP($this->idp);

        $idpuser = $this->setProperties($idpuser, $item);

        $idpuser->setPassword(md5(uniqid()));
        $token = $this->generateToken();
        $idpuser->setConfirmationToken($token);
        try {
            $this->em->persist($idpuser);
            $this->em->flush();
            IdPUserHelper::sendPasswordResetToken($idpuser, $token, $this->router, $this->twig, $this->mailer, $this->getParameter('mailer_sender'), 'sendPasswordCreateToken');

        } catch (\Exception $e) {
            throw new Exception($e);
        }
    }

    private function delete(IdPUser $idpuser)
    {
        $idpuser->setDeleted(true);
        $idpuser->saltUser();
        $this->em->persist($idpuser);
        $this->em->flush();
    }

    private function setProperties(IdPUser $idpuser, $item)
    {
        $samliScope = $this->em->getRepository('AppBundle:Scope')->findOneByValue($idpuser->getIdP()->getHostname());
        $scope = $this->idp->getScopeByValue($item['scope'], $samliScope, $this->samlidp_hostname);

        if (!$scope) {
            throw new Exception('Invalid scope ' . $item['scope'] . '.');
        };

        if (!isset($item['enabled'])) {
            throw new Exception('You must set if user is enabled or not.');
        }
        $enabled = ($item['enabled'] == 'true') ? true : false;

        $idpuser->setUsername($item['username']);
        $idpuser->setGivenName($item['givenname']);
        $idpuser->setSurName($item['surname']);
        $idpuser->setDisplayName($item['displayname']);
        $idpuser->setEmail($item['email']);
        $idpuser->setAffiliation($item['affiliation']);
        $idpuser->setScope($scope);
        $idpuser->setEnabled($enabled);

        return $idpuser;
    }

    public function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function validateEmailDuplum($email, $idp_user_id = null)
    {
        $existingUsers = $this->em->getRepository("AppBundle:IdPUser")->findByEmail($email);
        if (!$existingUsers) { // nincs egyáltalán ilyen email, mehet az akció
            return true;
        }

        foreach ($existingUsers as $existingUser) { // vannak már ilyen emaillel felhasználók
            if ($existingUser->getIdP() != $this->idp) { // de nem ebben az IdP-ben, mehet.
                continue;
            }
            if (!$idp_user_id || $idp_user_id != $existingUser->getId()) { // ne hozzuk létre az új felhasználót, vagy nem önmaga, ezért duplum.
                throw new Exception("Email is already taken.");
            }
        }
        return true;
    }
}
