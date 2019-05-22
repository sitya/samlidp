<?php


namespace AppBundle\Controller;


use AppBundle\Entity\User;
use AppBundle\Entity\WebauthnCredential;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TwoFactorDispatcherController
 * @package AppBundle\Controller
 * @Route("/twofactor")
 * @Security("has_role('ROLE_USER')")
 */
class TwoFactorManagerController extends Controller
{
    /**
     * @Route("/list")
     * @return Response
     */
    public function listAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $fido_tokens = $entityManager->getRepository(WebauthnCredential::class)->findByUser($this->getUser());

        return $this->render('AppBundle:TwoFactor:index.html.twig', array(
            'fido_tokens' => $fido_tokens,
        ));
    }
}