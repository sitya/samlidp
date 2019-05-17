<?php


namespace AppBundle\Controller;


use AppBundle\Entity\User;
use AppBundle\Entity\WebauthnCredential;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecondFactorDispatcherController
 * @package AppBundle\Controller
 * @Route("/secondfactor")
 */
class SecondFactorManagerController extends Controller
{
    /**
     * @Route("/list")
     * @return Response
     */
    public function listAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $fido_tokens = $entityManager->getRepository(WebauthnCredential::class)->findByUser($this->getUser());

        return $this->render('AppBundle:Secondfactor:index.html.twig', array(
            'fido_tokens' => $fido_tokens,
        ));
    }
}