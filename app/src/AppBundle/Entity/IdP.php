<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * IdP.
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\IdPRepository")
 * @ORM\Table(name="idp")
 * @UniqueEntity(
 *     fields={"hostname"},
 *     message="This hostname is already registered."
 * )
 */
class IdP
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
     * @ORM\Column(type="string")
     */
    private $status = 'active';

    /**
     * @var string
     *
     * @ORM\Column(name="hostname", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Regex("/^[0-9a-zA-Z]*$/")
     */
    private $hostname;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registrationInstant", type="datetime")
     */
    private $registrationInstant;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", nullable=true)
     */
    private $logo;

    /**
     * @var text
     *
     * @ORM\Column(name="cert_key", type="text", nullable=true)
     */
    private $certKey;

    /**
     * @var text
     *
     * @ORM\Column(name="cert_pem", type="text", nullable=true)
     */
    private $certPem;

    /**
     * @var Scope
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Scope")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $defaultScope;

    /**
     * Bidirectional.
     *
     * @ORM\ManyToMany(targetEntity="User", inversedBy="idps")
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity="IdPAudit", mappedBy="idp",cascade={"remove"}, orphanRemoval=true)
     */
    private $idpAudits;

    /**
     * @ORM\OneToMany(targetEntity="OrganizationElement", mappedBy="idp",cascade={"remove", "persist"}, orphanRemoval=true)
     */
    private $organizationElements;

    /**
     * @ORM\OneToMany(targetEntity="Domain", mappedBy="idp", cascade={"remove"}, orphanRemoval=true)
     */
    private $domains;

    /**
     * @ORM\OneToMany(targetEntity="IdPUser", mappedBy="IdP", cascade={"remove"}, orphanRemoval=true)
     */
    private $IdPUsers;

    /**
     * @ORM\ManyToMany(targetEntity="Entity", inversedBy="idps")
     */
    private $entities;

    /**
     * @ORM\ManyToMany(targetEntity="Federation", mappedBy="idps")
     */
    private $federations;

    /**
     * @var
     * @ORM\OneToMany(targetEntity="ApiToken", mappedBy="idp")
     */
    private $apiTokens;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->organizationElements = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contacts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->idpAudits = new \Doctrine\Common\Collections\ArrayCollection();
        $this->domains = new \Doctrine\Common\Collections\ArrayCollection();
        $this->federations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->entities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->apiTokens = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return $this->getHostname();
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
     * Get entityId.
     *
     * @return string
     */
    public function getEntityId($samlidp_hostname)
    {
        // It is a pretty dirty hack... :-/
        if (empty($samlidp_hostname)) {
            if (substr_count($_SERVER['HTTP_HOST'], '.') == 1) {
                $samlidp_hostname = $_SERVER['HTTP_HOST'];
            } else {
                $samlidp_hostname = substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.')+1);
            }
            
        }
                
        return 'https://'.$this->getHostname().'.' . $samlidp_hostname . '/saml2/idp/metadata.php';
    }

    /**
     * Set registrationInstant.
     *
     * @param \DateTime $registrationInstant
     *
     * @return IdP
     */
    public function setRegistrationInstant($registrationInstant)
    {
        $this->registrationInstant = $registrationInstant;

        return $this;
    }

    /**
     * Get registrationInstant.
     *
     * @return \DateTime
     */
    public function getRegistrationInstant()
    {
        return $this->registrationInstant;
    }

    /**
     * Get logoWidth.
     *
     * TODO: megcsinálni a tényleges lekérdezést
     *
     * @return int
     */
    public function getLogoWidth()
    {
        return 200;
    }

    /**
     * Get logoHeight.
     *
     * TODO: megcsinálni a tényleges lekérdezést
     *
     * @return int
     */
    public function getLogoHeight()
    {
        return 200;
    }

    /**
     * Add user.
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return IdP
     */
    public function addUser(\AppBundle\Entity\User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user.
     *
     * @param \AppBundle\Entity\User $user
     */
    public function removeUser(\AppBundle\Entity\User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add organizationElement.
     *
     * @param \AppBundle\Entity\OrganizationElement $organizationElement
     *
     * @return IdP
     */
    public function addOrganizationElement(\AppBundle\Entity\OrganizationElement $organizationElement)
    {
        $organizationElement->setIdP($this);
        $this->organizationElements[] = $organizationElement;

        return $this;
    }

    /**
     * Remove organizationElement.
     *
     * @param \AppBundle\Entity\OrganizationElement $organizationElement
     */
    public function removeOrganizationElement(\AppBundle\Entity\OrganizationElement $organizationElement)
    {
        $this->organizationElements->removeElement($organizationElement);
    }

    /**
     * Get organizationElements.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizationElements()
    {
        return $this->organizationElements;
    }

    /**
     * Set hostname.
     *
     * @param string $hostname
     *
     * @return IdP
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * Get hostname.
     *
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Gets the value of logo.
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Sets the value of logo.
     *
     * @param string $logo the logo
     *
     * @return self
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Gets the value of certKey.
     *
     * @return text
     */
    public function getCertKey()
    {
        return $this->certKey;
    }

    /**
     * Gets the value of certPem.
     *
     * @return text
     */
    public function getCertPem()
    {
        return $this->certPem;
    }

    /**
     * Sets the value of certKey.
     *
     * @param text $certKey the cert key
     *
     * @return self
     */
    public function setCertKey($certKey)
    {
        $this->certKey = $certKey;

        return $this;
    }

    /**
     * Sets the value of certPem.
     *
     * @param text $certPem the cert pem
     *
     * @return self
     */
    public function setCertPem($certPem)
    {
        $this->certPem = $certPem;

        return $this;
    }

    public function validateAccess($user)
    {
        if ($user->hasRole('ROLE_SUPER_ADMIN')) {
            return true;
        }
        if (!$this->users->contains($user)) {
            throw new AccessDeniedHttpException();
        }
    }

    public function getInstituteName()
    {
        foreach ($this->getOrganizationElements() as $elem) {
            if ($elem->getType() == 'Name') {
                return $elem->getValue();
            }
        }
    }

    public function hasRequiredInstituteName()
    {
        /** @var OrganizationElement $elem */
        foreach ($this->getOrganizationElements() as $elem) {
            if ($elem->getType() == 'Name' && $elem->getLang() == 'en') {
                return true;
            }
        }

        return false;
    }

    public function hasRequiredInstituteUrl()
    {
        /** @var OrganizationElement $elem */
        foreach ($this->getOrganizationElements() as $elem) {
            if ($elem->getType() == 'InformationURL' && $elem->getLang() == 'en') {
                return true;
            }
        }

        return false;
    }

    public function getInstituteUrl()
    {
        foreach ($this->getOrganizationElements() as $elem) {
            if ($elem->getType() == 'InformationURL') {
                return $elem->getValue();
            }
        }
    }

    /**
     * Gets the value of idpAudits.
     *
     * @return mixed
     */
    public function getIdpAudits()
    {
        return $this->idpAudits;
    }

    /**
     * Add idpAudit.
     *
     * @param \AppBundle\Entity\IdPAudit $idpAudit
     *
     * @return IdP
     */
    public function addIdpAudit(\AppBundle\Entity\IdPAudit $idpAudit)
    {
        $this->idpAudits[] = $idpAudit;

        return $this;
    }

    /**
     * Remove idpAudit.
     *
     * @param \AppBundle\Entity\IdPAudit $idpAudit
     */
    public function removeIdpAudit(\AppBundle\Entity\IdPAudit $idpAudit)
    {
        $this->idpAudits->removeElement($idpAudit);
    }

    /**
     * Add domain.
     *
     * @param \AppBundle\Entity\Domain $domain
     *
     * @return IdP
     */
    public function addDomain(\AppBundle\Entity\Domain $domain)
    {
        $this->domains[] = $domain;

        return $this;
    }

    /**
     * Remove domain.
     *
     * @param \AppBundle\Entity\Domain $domain
     */
    public function removeDomain(\AppBundle\Entity\Domain $domain)
    {
        $this->domains->removeElement($domain);
    }

    /**
     * Get domains.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * Get domains.
     *
     * @return bool
     */
    public function hasDomain($domainName)
    {
        foreach ($this->domains as $domain) {
            if ($domain->getDomain() == $domainName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get scopes.
     *
     * @return array
     */
    public function getScopes()
    {
        $scopes = array($_SERVER['HTTP_HOST']);
        foreach ($this->getDomains() as $domain) {
            foreach ($domain->getScopes() as $scope) {
                if ($scope->getValue() == '@') {
                    $scopes[] = $domain->getDomain();
                } else {
                    $scopes[] = $scope->getValue().'.'.$domain->getDomain();
                }
            }
        }

        return $scopes;
    }

    public function getScopeObjects()
    {
        $scopes = array();
        foreach ($this->getDomains() as $domain) {
            foreach ($domain->getScopes() as $scope) {
                $scopes[] = $scope;
            }
        }

        return $scopes;
    }

    public function getScopeByValue($value, $samliScope, $samlidp_hostname)
    {
        if (empty($samlidp_hostname)) {
            if (substr_count($_SERVER['HTTP_HOST'], '.') == 1) {
                $samlidp_hostname = $_SERVER['HTTP_HOST'];
            } else {
                $samlidp_hostname = substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.')+1);
            }
            
        }
        foreach ($this->getScopeObjects() as $scope) {
            $domain = $scope->getDomain();
            if ($scope->getValue().'.'.$domain->getDomain() == $value) {
                return $scope;
            } elseif ($scope->getValue() == '@' && $value == $domain->getDomain()) {
                return $scope;
            }
        }
        if ($samliScope && $samliScope->getValue() . "." . $samlidp_hostname == $value) {
            return $samliScope;
        }

        return false;
    }

    /**
     * Set defaultScope.
     *
     * @param \AppBundle\Entity\Scope $defaultScope
     *
     * @return IdP
     */
    public function setDefaultScope(\AppBundle\Entity\Scope $defaultScope = null)
    {
        $this->defaultScope = $defaultScope;

        return $this;
    }

    /**
     * Get defaultScope.
     *
     * @return \AppBundle\Entity\Scope
     */
    public function getDefaultScope($samlidp_hostname = null)
    {
        if (empty($samlidp_hostname)) {
            if (substr_count($_SERVER['HTTP_HOST'], '.') == 1) {
                $samlidp_hostname = $_SERVER['HTTP_HOST'];
            } else {
                $samlidp_hostname = substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.')+1);
            }
            
        }

        if (empty($this->defaultScope)) {
            return $this->getHostname().'.' . $samlidp_hostname;
        } else {
            return $this->defaultScope;
        }
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return IdP
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getDNSCheckerHash()
    {
        return 'idp-verification='.sha1($this->getHostname());
    }

    /**
     * Add idPUser.
     *
     * @param \AppBundle\Entity\IdPUser $idPUser
     *
     * @return IdP
     */
    public function addIdPUser(\AppBundle\Entity\IdPUser $idPUser)
    {
        $this->IdPUsers[] = $idPUser;

        return $this;
    }

    /**
     * Remove idPUser.
     *
     * @param \AppBundle\Entity\IdPUser $idPUser
     */
    public function removeIdPUser(\AppBundle\Entity\IdPUser $idPUser)
    {
        $this->IdPUsers->removeElement($idPUser);
    }

    /**
     * Get idPUsers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdPUsers()
    {
        return $this->IdPUsers;
    }

    /**
     * Add federation.
     *
     * @param \AppBundle\Entity\Federation $federation
     *
     * @return IdP
     */
    public function addFederation(\AppBundle\Entity\Federation $federation)
    {
        $this->federations[] = $federation;

        return $this;
    }

    /**
     * Remove federation.
     *
     * @param \AppBundle\Entity\Federation $federation
     */
    public function removeFederation(\AppBundle\Entity\Federation $federation)
    {
        $this->federations->removeElement($federation);
    }

    /**
     * Get federations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFederations()
    {
        return $this->federations;
    }

    /**
     * Set entities.
     *
     * @param \AppBundle\Entity\Entity $entities
     *
     * @return IdP
     */
    public function setEntities(\AppBundle\Entity\Entity $entities = null)
    {
        $this->entities = $entities;

        return $this;
    }

    /**
     * Get entities.
     *
     * @return \AppBundle\Entity\Entity
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * Add entity.
     *
     * @param \AppBundle\Entity\Entity $entity
     *
     * @return IdP
     */
    public function addEntity(\AppBundle\Entity\Entity $entity)
    {
        $this->entities[] = $entity;

        return $this;
    }

    /**
     * Remove entity.
     *
     * @param \AppBundle\Entity\Entity $entity
     */
    public function removeEntity(\AppBundle\Entity\Entity $entity)
    {
        $this->entities->removeElement($entity);
    }

    /**
     * Return the user count of IdP
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

    /**
     * @return mixed
     */
    public function getApiTokens()
    {
        return $this->apiTokens;
    }

    /**
     * @param mixed $apiTokens
     */
    public function setApiTokens($apiTokens): void
    {
        $this->apiTokens = $apiTokens;
    }

    public function hasApiToken(ApiToken $apiToken)
    {
        return in_array($apiToken, $this->apiTokens);
    }

    public function addApiToken(ApiToken $apiToken)
    {
        if ($this->hasApiToken($apiToken)) {
            throw new \InvalidArgumentException('Token already exists.');
        }
        $this->apiTokens->add($apiToken);
    }

    public function removeApiToken(ApiToken $apiToken)
    {
        if (!$this->hasApiToken($apiToken)) {
            throw new \InvalidArgumentException('Token not exists.');
        }
        $this->apiTokens->removeElement($apiToken);
    }
}
