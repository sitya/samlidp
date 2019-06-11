<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * IdPUser.
 *
 * @ORM\Table(name="idp_internal_mysql_user", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="username_uniq", columns={"username", "idp_id"}),
 *     @ORM\UniqueConstraint(name="email_uniq", columns={"email", "idp_id"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\IdPUserRepository")
 * @UniqueEntity(
 *     fields={"username", "IdP"},
 *     message="This username is already taken in this institute."
 * )
 * @UniqueEntity(
 *     fields={"email", "IdP"},
 *     message="This email address is already used in this institute."
 * )
 */
class IdPUser implements UserInterface, \Serializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="username", type="string", length=255, nullable=false)
     * @Assert\NotNull()
     */
    protected $username;

    /**
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
    protected $password;

    /**
     * @ORM\Column(name="password_ntml", type="string", length=255, nullable=true)
     */
    protected $password_ntml;

    /**
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     * @Assert\NotNull()
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     checkMX = true
     * )
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="givenName", type="string", length=255)
     */
    private $givenName;

    /**
     * @var string
     *
     * @ORM\Column(name="surName", type="string", length=255)
     */
    private $surName;

    /**
     * @ORM\Column(type="string")
     */
    private $displayName;

    /**
     * @var string
     *
     * @ORM\Column(name="affiliation", type="string", length=255)
     */
    private $affiliation;

    /**
     * @ORM\ManyToOne(targetEntity="Scope", inversedBy="IdPUsers", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $scope;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    protected $deleted = false;

    /**
     * The salt to use for hashing.
     *
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=255, nullable=true)
     */
    protected $salt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastLogin", type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * Random string sent to the user email address in order to verify it.
     *
     * @var string
     *
     * @ORM\Column(name="confirmationToken", type="string", length=255, nullable=true)
     */
    protected $confirmationToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="passwordRequestedAt", type="datetime", nullable=true)
     */
    protected $passwordRequestedAt;

    /**
     * @ORM\ManyToOne(targetEntity="IdP", inversedBy="IdPUsers", cascade={"persist"})
     * @ORM\JoinColumn(name="idp_id", referencedColumnName="id")
     */
    private $IdP;

    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set givenName.
     *
     * @param string $givenName
     *
     * @return IdPUser
     */
    public function setGivenName($givenName)
    {
        $this->givenName = $givenName;

        return $this;
    }

    /**
     * Get givenName.
     *
     * @return string
     */
    public function getGivenName()
    {
        return $this->givenName;
    }

    /**
     * Set surName.
     *
     * @param string $surName
     *
     * @return IdPUser
     */
    public function setSurName($surName)
    {
        $this->surName = $surName;

        return $this;
    }

    /**
     * Get surName.
     *
     * @return string
     */
    public function getSurName()
    {
        return $this->surName;
    }

    /**
     * Set affiliation.
     *
     * @param string $affiliation
     *
     * @return IdPUser
     */
    public function setAffiliation($affiliation)
    {
        $this->affiliation = $affiliation;

        return $this;
    }

    /**
     * Get affiliation.
     *
     * @return string
     */
    public function getAffiliation()
    {
        return $this->affiliation;
    }

    /**
     * Set idP.
     *
     * @param \AppBundle\Entity\IdP $idP
     *
     * @return IdPUser
     */
    public function setIdP(\AppBundle\Entity\IdP $idP = null)
    {
        $this->IdP = $idP;

        return $this;
    }

    /**
     * Get idP.
     *
     * @return \AppBundle\Entity\IdP
     */
    public function getIdP()
    {
        return $this->IdP;
    }

    /**
     * Set Scope.
     *
     * @param \AppBundle\Entity\Scope $idP
     *
     * @return IdPUser
     */
    public function setScope(\AppBundle\Entity\Scope $scope = null)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get Scope.
     *
     * @return \AppBundle\Entity\Scope
     */
    public function getScope($samlidp_hostname = null)
    {
        if (empty($samlidp_hostname)) {
            $http_host = preg_split("/:/", $_SERVER['HTTP_HOST']);
            if (ip2long($http_host[0]) > 0) {
                $samlidp_hostname = "samlidp.io"; // XXX this is just for testing
            } elseif (substr_count($_SERVER['HTTP_HOST'], '.') == 1) {
                $samlidp_hostname = $_SERVER['HTTP_HOST'];
            } else {
                $samlidp_hostname = substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.')+1);
            }
            
        }
        if (!empty($this->scope)) {
            return $this->scope;
        }
        return $this->getIdp()->getHostname() . '.' .$samlidp_hostname;
    }

    /**
     * Gets the value of displayName.
     *
     * @return mixed
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Sets the value of displayName.
     *
     * @param mixed $displayName the display name
     *
     * @return self
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return IdPUser
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return IdPUser
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return $this->password;
    }

    
    /**
     * Set salt.
     *
     * @param string $salt
     *
     * @return IdPUser
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Set NTML password.
     *
     * @param string $password_ntml
     *
     * @return IdPUser
     */
    public function setPasswordNtml($ntml_hash)
    {
        $this->password_ntml = $ntml_hash;

        return $this;
    }

    /**
     * Returns the password used to authenticate the user with eduroam.
     *
     * This should be the NT Hash encoded password.
     *
     * @return string The password
     */
    public function getPasswordNtml()
    {
        return $this->password_ntml;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return IdPUser
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set enabled.
     *
     * @param bool $enabled
     *
     * @return IdPUser
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set lastLogin.
     *
     * @param \DateTime $lastLogin
     *
     * @return IdPUser
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin.
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set confirmationToken.
     *
     * @param string $confirmationToken
     *
     * @return IdPUser
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * Get confirmationToken.
     *
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * Set passwordRequestedAt.
     *
     * @param \DateTime $passwordRequestedAt
     *
     * @return IdPUser
     */
    public function setPasswordRequestedAt($passwordRequestedAt)
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    /**
     * Get passwordRequestedAt.
     *
     * @return \DateTime
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     * Returns the roles granted to the user.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles()
    {
        return array('ROLE_USER');
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        return $this;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }

    public function saltUser()
    {
        $this->setPassword(sha1($this->getRandomString()));
        $this->setEmail(sha1($this->getRandomString()));
        $this->setSurName(sha1($this->getRandomString()));
        $this->setGivenName(sha1($this->getRandomString()));
        $this->setDisplayName(sha1($this->getRandomString()));
        $this->setAffiliation(sha1($this->getRandomString()));
        $this->setConfirmationToken(sha1($this->getRandomString()));
    }

    public function getRandomString()
    {
        return substr(
            str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
            0,
            32
        );
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return IdPUser
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }
}
