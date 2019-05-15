<?php


namespace AppBundle\Security;

use AppBundle\Entity\IdPUser;
use AppBundle\Entity\User;
use AppBundle\Entity\WebauthnCredential;
use AppBundle\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use MadWizard\WebAuthn\Credential\CredentialStoreInterface;
use MadWizard\WebAuthn\Credential\UserCredentialInterface;
use MadWizard\WebAuthn\Credential\CredentialRegistration;

/**
 * Class CredentialStore
 * @package AppBundle\Security
 */
class CredentialStore implements CredentialStoreInterface
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * CredentialStore constructor.
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $credentialId
     * @return UserCredentialInterface|null
     */
    public function findCredential(string $credentialId): ?UserCredentialInterface
    {
        return $this->entityManager->getRepository(WebauthnCredential::class)->findOneByCredentialId($credentialId);
    }

    /**
     * @param CredentialRegistration $credential
     */
    public function registerCredential(CredentialRegistration $credential)
    {
        $user = $this->entityManager->getRepository(User::class)->findOneByUserHandle($credential->getUserHandle());
        dump($user, $credential->getUserHandle()->getBase64Url());
        $webauthnCredential = new WebauthnCredential();
        $webauthnCredential->setUser($user);
        $webauthnCredential->setCredentialId($credential->getCredentialId());
        $webauthnCredential->setPublicKey($credential->getPublicKey());
        $webauthnCredential->setSignatureCounter(0);

        $this->entityManager->persist($webauthnCredential);
    }

    /**
     * @param string $credentialId
     * @return int|null
     */
    public function getSignatureCounter(string $credentialId): ?int
    {
        return $this->entityManager->getRepository(WebauthnCredential::class)->findOneByCredentialId($credentialId)->getSignatureCounter();
    }

    /**
     * @param string $credentialId
     * @param int $counter
     */
    public function updateSignatureCounter(string $credentialId, int $counter): void
    {
        /** @var WebauthnCredential $webauthnCredential */
        $webauthnCredential = $this->entityManager->getRepository(WebauthnCredential::class)->findOneByCredentialId($credentialId);
        $currentcounter = $webauthnCredential->getSignatureCounter();
        $webauthnCredential->setSignatureCounter($counter + $currentcounter);
        $this->entityManager->persist($webauthnCredential);
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getUserCredentials(User $user)
    {
        return $this->entityManager->getRepository(WebauthnCredential::class)->findByUser($user);
    }
}
