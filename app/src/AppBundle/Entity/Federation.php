<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Federation
 *
 * @ORM\Table(name="federation")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FederationRepository")
 */
class Federation
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
     * @ORM\Column(name="metadataUrl", type="string", length=255, nullable=true)
     * @Assert\Url()
     */
    private $metadataUrl;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastChecked;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="federationUrl", type="string", length=255, nullable=true)
     * @Assert\Url()
     */
    private $federationUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="contactName", type="string", length=255, nullable=true)
     */
    private $contactName;

    /**
     * @var string
     *
     * @ORM\Column(name="contactEmail", type="string", length=255, nullable=true)
     * @Assert\Email()
     */
    private $contactEmail;

    /**
     * @ORM\Column(name="sps", type="integer", nullable=true)
     */
    private $sps;

    /**
     * @ORM\OneToMany(targetEntity="Entity", mappedBy="federation", cascade={"remove"})
     */
    private $entities;

    /**
     * @ORM\ManyToMany(targetEntity="IdP",inversedBy="federations")
     */
    private $idps;

    /**
     * @ORM\ManyToMany(targetEntity="IdP",inversedBy="federationsContaining")
     * @ORM\JoinTable(name="federation_containing_idp",
     *      joinColumns={@ORM\JoinColumn(name="federation_id", referencedColumnName="id")},
     *       inverseJoinColumns={@ORM\JoinColumn(name="idp_id", referencedColumnName="id")}
     * )
     */
    protected $idpsContained;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->entities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->idps = new \Doctrine\Common\Collections\ArrayCollection();
        $this->idpsContained = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
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
     * Set metadataUrl
     *
     * @param string $metadataUrl
     *
     * @return Federation
     */
    public function setMetadataUrl($metadataUrl)
    {
        $this->metadataUrl = $metadataUrl;

        return $this;
    }

    /**
     * Get metadataUrl
     *
     * @return string
     */
    public function getMetadataUrl()
    {
        return $this->metadataUrl;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Federation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set federationUrl
     *
     * @param string $federationUrl
     *
     * @return Federation
     */
    public function setFederationUrl($federationUrl)
    {
        $this->federationUrl = $federationUrl;

        return $this;
    }

    /**
     * Get federationUrl
     *
     * @return string
     */
    public function getFederationUrl()
    {
        return $this->federationUrl;
    }

    /**
     * Set contactName
     *
     * @param string $contactName
     *
     * @return Federation
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;

        return $this;
    }

    /**
     * Get contactName
     *
     * @return string
     */
    public function getContactName()
    {
        return $this->contactName;
    }

    /**
     * Set contactEmail
     *
     * @param string $contactEmail
     *
     * @return Federation
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    /**
     * Get contactEmail
     *
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Federation
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set shasum
     *
     * @param string $shasum
     *
     * @return Federation
     */
    public function setShasum($shasum)
    {
        $this->shasum = $shasum;

        return $this;
    }

    /**
     * Get shasum
     *
     * @return string
     */
    public function getShasum()
    {
        return $this->shasum;
    }

    /**
     * Add entity
     *
     * @param \AppBundle\Entity\Entity $entity
     *
     * @return Federation
     */
    public function addEntity(\AppBundle\Entity\Entity $entity)
    {
        $this->entities[] = $entity;

        return $this;
    }

    /**
     * Remove entity
     *
     * @param \AppBundle\Entity\Entity $entity
     */
    public function removeEntity(\AppBundle\Entity\Entity $entity)
    {
        $this->entities->removeElement($entity);
    }

    /**
     * Get entities
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * Add idp
     *
     * @param \AppBundle\Entity\IdP $idp
     *
     * @return Federation
     */
    public function addIdp(\AppBundle\Entity\IdP $idp)
    {
        $this->idps[] = $idp;

        return $this;
    }

    /**
     * Remove idp
     *
     * @param \AppBundle\Entity\IdP $idp
     */
    public function removeIdp(\AppBundle\Entity\IdP $idp)
    {
        $this->idps->removeElement($idp);
    }

    /**
     * Get idps
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdps()
    {
        return $this->idps;
    }

    /**
     * Add idpContained
     *
     * @param \AppBundle\Entity\IdP $idpContained
     *
     * @return Federation
     */
    public function addIdpContained(\AppBundle\Entity\IdP $idpContained)
    {
        $this->idpsContained[] = $idpContained;

        return $this;
    }

    /**
     * Remove idpContained
     *
     * @param \AppBundle\Entity\IdP $idpContained
     */
    public function removeIdpContained(\AppBundle\Entity\IdP $idpContained)
    {
        $this->idpsContained->removeElement($idpContained);
    }

    /**
     * Get idpsContained
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdpsContained()
    {
        return $this->idpsContained;
    }

    /**
     * Clear idpsContained
     */
    public function clearIdpsContained()
    {
        $this->idpsContained->clear();
    }

    /**
     * Set sps
     *
     * @param integer $sps
     *
     * @return Federation
     */
    public function setSps($sps)
    {
        $this->sps = $sps;

        return $this;
    }

    /**
     * Get sps
     *
     * @return integer
     */
    public function getSps()
    {
        return $this->sps;
    }

    /**
     * Set lastChecked
     *
     * @param \DateTime $lastChecked
     *
     * @return Federation
     */
    public function setLastChecked($lastChecked)
    {
        $this->lastChecked = $lastChecked;

        return $this;
    }

    /**
     * Get lastChecked
     *
     * @return \DateTime
     */
    public function getLastChecked()
    {
        if (!isset($this->lastChecked)) {
            return new \DateTime("-2 days");
        }
        return $this->lastChecked;
    }
}

// TODO: getter, setter,
