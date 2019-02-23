<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Domain
 *
 * @ORM\Table(name="domain")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DomainRepository")
 */
class Domain
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
     *
     * @ORM\Column(name="domain", type="string", length=255)
     */
    private $domain;

    /**
     * @ORM\OneToMany(targetEntity="Scope", mappedBy="domain", cascade={"persist","remove"})
     */
    private $scopes;

    /**
     * @ORM\ManyToOne(targetEntity="IdP", inversedBy="domains",cascade={"persist"})
     */
    private $idp;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->scopes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return (string)$this->getDomain();
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
     * Set domain
     *
     * @param string $domain
     *
     * @return Domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Add scope
     *
     * @param \AppBundle\Entity\Scope $scope
     *
     * @return Domain
     */
    public function addScope(\AppBundle\Entity\Scope $scope)
    {
        $scope->setDomain($this);
        $this->scopes[] = $scope;

        return $this;
    }

    /**
     * Remove scope
     *
     * @param \AppBundle\Entity\Scope $scope
     */
    public function removeScope(\AppBundle\Entity\Scope $scope)
    {
        $this->scopes->removeElement($scope);
    }

    /**
     * Get scopes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Set idp
     *
     * @param \AppBundle\Entity\IdP $idp
     *
     * @return Domain
     */
    public function setIdp(\AppBundle\Entity\IdP $idp = null)
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
}
