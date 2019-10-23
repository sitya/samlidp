<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MadWizard\WebAuthn\Credential\CredentialRegistration;
use MadWizard\WebAuthn\Credential\CredentialStoreInterface;
use MadWizard\WebAuthn\Credential\UserCredentialInterface;
use MadWizard\WebAuthn\Crypto\CoseKey;
use MadWizard\WebAuthn\Crypto\RsaKey;
use MadWizard\WebAuthn\Format\ByteBuffer;

/**
 * WebauthnCredentials
 *
 * @ORM\Table(name="webauthn_credentials")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WebauthnCredentialRepository")
 */
class WebauthnCredential implements UserCredentialInterface
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
     * @ORM\Column(type="string")
     */
    private $base64cbor;

    /**
     * @ORM\Column(type="string", unique=true)
     */
    private $credentialId;

    /**
     * @var string
     */
    private $displayName;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="webauthnCredentials")
     */
    private $user;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true, "default":0})
     */
    private $signatureCounter;

    public function __toString()
    {
        return $this->displayName?:"..";
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
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getDisplayName()
    {
        return substr(sha1($this->credentialId), 0, 8);
    }

    /**
     * @param mixed $credentialId
     */
    public function setCredentialId($credentialId): void
    {
        $this->credentialId = $credentialId;
    }

    /**
     * @param CoseKey $publicKey
     */
    public function setPublicKey(CoseKey $publicKey): void
    {
        $cbor = $publicKey->getCbor();
        $this->base64cbor = $cbor->getBase64Url();
    }

    /**
     * @return mixed
     */
    public function getSignatureCounter()
    {
        return $this->signatureCounter;
    }

    /**
     * @param mixed $signatureCounter
     */
    public function setSignatureCounter($signatureCounter): void
    {
        $this->signatureCounter = $signatureCounter;
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function getPublicKey(): CoseKey
    {
        return CoseKey::parseCbor(ByteBuffer::fromBase64Url($this->base64cbor));
    }

    public function getUserHandle(): ByteBuffer
    {
        return ByteBuffer::fromHex("");
        // must return empty object because yubikey not present userHandle.
        // return ByteBuffer::fromHex(sha1($this->user->getUsernameCanonical()));
    }

}
