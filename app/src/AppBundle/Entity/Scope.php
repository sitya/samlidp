<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scope
 *
 * @ORM\Table(name="scope")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ScopeRepository")
 */
class Scope
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
     * @var string
     * Csak a domain előtt álló részt tároljuk el.
     * @ORM\Column(name="value", type="string", length=255)
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="Domain", inversedBy="scopes", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $domain;

    /**
     * @ORM\OneToMany(targetEntity="IdPUser", mappedBy="scope")
     */
    private $IdPUsers;

    public function __toString()
    {
        return $this->getFullScope();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return Scope
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set domain
     *
     * @param \AppBundle\Entity\Domain $domain
     *
     * @return Scope
     */
    public function setDomain(\AppBundle\Entity\Domain $domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return \AppBundle\Entity\Domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * get full scope with domain part
     * @return string scope
     */
    public function getFullScope()
    {
        return preg_replace('/@./', '', $this->value . '.' .  $this->getDomain()->getDomain());
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->IdPUsers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add idPUser
     *
     * @param \AppBundle\Entity\IdPUser $idPUser
     *
     * @return Scope
     */
    public function addIdPUser(\AppBundle\Entity\IdPUser $idPUser)
    {
        $this->IdPUsers[] = $idPUser;

        return $this;
    }

    /**
     * Remove idPUser
     *
     * @param \AppBundle\Entity\IdPUser $idPUser
     */
    public function removeIdPUser(\AppBundle\Entity\IdPUser $idPUser)
    {
        $this->IdPUsers->removeElement($idPUser);
    }

    /**
     * Get idPUsers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdPUsers()
    {
        return $this->IdPUsers;
    }

    /**
     * Return the user count of Scope
     * @param bool $with_deleted if true return the full count.
     * @return int
     */
    public function getIdPUserCount($with_deleted = false)
    {
        $idpUsers = $this->getIdPUsers();
        if ($with_deleted) {
            return count($idpUsers);
        }

        $count = 0;
        foreach ($idpUsers as $idpUser) {
            if (! $idpUser->getDeleted()) {
                $count++;
            }
        }
        return $count;
    }
}
