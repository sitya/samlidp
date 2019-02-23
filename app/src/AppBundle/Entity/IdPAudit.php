<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * IdPAudit.
 *
 * @ORM\Table(name="idp_audit")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\IdPAuditRepository")
 */
class IdPAudit
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var AppBundle\Entity\IdPUser
     *
     * @ORM\ManyToOne(targetEntity="IdPUser", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $idpuser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="loginTime", type="datetime")
     */
    private $loginTime;

    /**
     * @var string
     *
     * @ORM\Column(name="sp", type="string")
     */
    private $sp;

    /**
     * @ORM\ManyToOne(targetEntity="IdP", inversedBy="idpAudits", cascade={"persist"})
     */
    private $idp;

    /**
     * [__construct description]
     * @param string                $uid  [description]
     * @param \DateTime             $date [description]
     * @param \AppBundle\Entity\IdP $idp  [description]
     * @param string                $sp   [description]
     */
    public function __construct(\AppBundle\Entity\IdPUser $idpuser, \DateTime $loginTime, \AppBundle\Entity\IdP $idp, $sp = "none")
    {
        $this->idpuser = $idpuser;
        $this->loginTime = $loginTime;
        $this->idp = $idp;
        $this->sp = $sp;
    }

    public function __toString()
    {
        return (string)$this->getId();
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
     * Sets the value of id.
     *
     * @param int $id the id
     *
     * @return self
     */
    private function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets the value of loginTime.
     *
     * @return \DateTime
     */
    public function getLoginTime()
    {
        return $this->loginTime;
    }

    /**
     * Sets the value of loginTime.
     *
     * @param \DateTime $loginTime the login time
     *
     * @return self
     */
    public function setLoginTime(\DateTime $loginTime)
    {
        $this->loginTime = $loginTime;

        return $this;
    }

    /**
     * Gets the value of sp.
     *
     * @return string
     */
    public function getSp()
    {
        return $this->sp;
    }

    /**
     * Sets the value of sp.
     *
     * @param string $sp the sp
     *
     * @return self
     */
    private function setSp($sp)
    {
        $this->sp = $sp;

        return $this;
    }

    /**
     * Set idp
     *
     * @param \AppBundle\Entity\IdP $idp
     *
     * @return IdP
     */
    public function setIdp(\AppBundle\Entity\IdP $idp)
    {
        $this->idp = $idp;

        return $this;
    }

    /**
     * Get idp
     *
     * @return \AppBundle\Entity\IdP
     */
    public function getIdp()
    {
        return $this->idp;
    }

    /**
     * Set idpuser
     *
     * @param \AppBundle\Entity\IdPUser $idpuser
     *
     * @return IdPAudit
     */
    public function setIdpuser(\AppBundle\Entity\IdPUser $idpuser = null)
    {
        $this->idpuser = $idpuser;

        return $this;
    }

    /**
     * Get idpuser
     *
     * @return \AppBundle\Entity\IdPUser
     */
    public function getIdpuser()
    {
        return $this->idpuser;
    }
}
