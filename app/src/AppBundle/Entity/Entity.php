<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entity
 *
 * @ORM\Table(name="entity")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EntityRepository")
 * @UniqueEntity(
 *     fields={"entityid", "federation"}
 * )
 */
class Entity
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
     * @ORM\Column(name="entityid", type="string", length=255)
     */
    private $entityid;

    /**
     * @var string
     *
     * @ORM\Column(name="sha1sum", type="string", length=255)
     */
    private $sha1sum;

    /**
     * @var string
     *
     * @ORM\Column(name="entitydata", type="blob")
     */
    private $entitydata;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_modified", type="datetime", nullable=true)
     */
    private $lastModified;

    /**
     * @var bool
     *
     * @ORM\Column(name="modificable", type="boolean", options={"default": false})
     */
    protected $modificable = false;

    /**
     * @var \AppBundle\Federation
     *
     * @ORM\ManyToOne(targetEntity="Federation", inversedBy="entities")
     */
    private $federation;

    /**
     * @ORM\ManyToMany(targetEntity="IdP", mappedBy="entities", cascade={"persist"})
     */
    private $idps;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->idps = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return $this->getEntityid();
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
     * Set entityid
     *
     * @param string $entityid
     *
     * @return Entity
     */
    public function setEntityid($entityid)
    {
        $this->entityid = $entityid;

        return $this;
    }

    /**
     * Get entityid
     *
     * @return string
     */
    public function getEntityid()
    {
        return $this->entityid;
    }

    /**
     * Set sha1sum
     *
     * @param string $sha1sum
     *
     * @return Entity
     */
    public function setSha1sum($sha1sum)
    {
        $this->sha1sum = $sha1sum;

        return $this;
    }

    /**
     * Get sha1sum
     *
     * @return string
     */
    public function getSha1sum()
    {
        return $this->sha1sum;
    }

    /**
     * Set entitydata
     *
     * @param string $entitydata
     *
     * @return Entity
     */
    public function setEntitydata($entitydata)
    {
        $this->entitydata = $entitydata;

        return $this;
    }

    /**
     * Get entitydata
     *
     * @return string
     */
    public function getEntitydata()
    {
        return $this->entitydata;
    }

    /**
     * Set federation
     *
     * @param \AppBundle\Entity\Federation $federation
     *
     * @return Entity
     */
    public function setFederation(\AppBundle\Entity\Federation $federation = null)
    {
        $this->federation = $federation;

        return $this;
    }

    /**
     * Get federation
     *
     * @return \AppBundle\Entity\Federation
     */
    public function getFederation()
    {
        return $this->federation;
    }


    /**
     * Add idp
     *
     * @param \AppBundle\Entity\IdP $idp
     *
     * @return Entity
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
     * Set lastModified
     *
     * @param \DateTime $lastModified
     *
     * @return Entity
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    /**
     * Get lastModified
     *
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Set modificable.
     *
     * @param bool $modificable
     *
     * @return Entity
     */
    public function setModificable($modificable)
    {
        $this->modificable = $modificable;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return bool
     */
    public function getModificable()
    {
        return $this->modificable;
    }
}
