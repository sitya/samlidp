<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OrganizationElement
 *
 * @ORM\Table(name="organization_element")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OrganizationElementRepository")
 */
class OrganizationElement
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
     * @ORM\ManyToOne(targetEntity="IdP", inversedBy="organizationElements",cascade={"persist"})
     */
    private $idp;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string")
     * @Assert\Choice({"Name", "displayName", "Description", "InformationURL", "PrivacyStatementURL"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="lang", type="string", length=5)
     */
    private $lang = 'en';

    /**
     * @ORM\Column(name="value", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $value;

    public function __toString()
    {
        return $this->type . " " . $this->value;
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
     * Set type
     *
     * @param \string(columnDefinition $type
     *
     * @return OrganizationElement
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \string(columnDefinition
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set lang
     *
     * @param string $lang
     *
     * @return OrganizationElement
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set idp
     *
     * @param \AppBundle\Entity\IdP $idp
     *
     * @return OrganizationElement
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

    /**
     * Set value
     *
     * @param string $value
     *
     * @return OrganizationElement
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
}
