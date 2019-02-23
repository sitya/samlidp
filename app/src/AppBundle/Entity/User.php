<?php
namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="sn", type="string", length=255)
     */
    private $sn;

    /**
     * @ORM\Column(name="givenName", type="string", length=255)
     */
    private $givenName;

    /**
     * Bidirectional
     *
     * @ORM\ManyToMany(targetEntity="IdP", mappedBy="users")
     * @ORM\JoinTable(name="user_idp")
     */
    private $idps;

    /**
     * @var string $googleAuthenticatorCode Stores the secret code
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $googleAuthenticatorCode = null;

    /**
     * Add idP
     *
     * @param \AppBundle\Entity\IdP $idP
     *
     * @return User
     */
    public function addIdP(\AppBundle\Entity\IdP $idP)
    {
        $this->IdPs[] = $idP;

        return $this;
    }

    /**
     * Remove idP
     *
     * @param \AppBundle\Entity\IdP $idP
     */
    public function removeIdP(\AppBundle\Entity\IdP $idP)
    {
        $this->IdPs->removeElement($idP);
    }

    /**
     * Get idPs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdPs()
    {
        return $this->idps;
    }

    /**
     * Gets the value of id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the value of sn.
     *
     * @return mixed
     */
    public function getSn()
    {
        return $this->sn;
    }

    /**
     * Sets the value of sn.
     *
     * @param mixed $sn the sn
     *
     * @return self
     */
    public function setSn($sn)
    {
        $this->sn = $sn;

        return $this;
    }

    /**
     * Gets the value of givenName.
     *
     * @return mixed
     */
    public function getGivenName()
    {
        return $this->givenName;
    }

    /**
     * Sets the value of givenName.
     *
     * @param mixed $givenName the given name
     *
     * @return self
     */
    public function setGivenName($givenName)
    {
        $this->givenName = $givenName;

        return $this;
    }

    /**
     * hogy menjen az easyadmin
     * @return [type] [description]
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * hogy menjen az easyadmin
     * @return [type] [description]
     */
    public function getCredentialsExpireAt()
    {
        return $this->credentialsExpireAt;
    }

    /**
     * Set googleAuthenticatorCode
     *
     * @param string $googleAuthenticatorCode
     *
     * @return User
     */
    public function setGoogleAuthenticatorCode($googleAuthenticatorCode)
    {
        $this->googleAuthenticatorCode = $googleAuthenticatorCode;

        return $this;
    }

    /**
     * Get googleAuthenticatorCode
     *
     * @return string
     */
    public function getGoogleAuthenticatorCode()
    {
        return $this->googleAuthenticatorCode;
    }
}
