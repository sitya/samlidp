<?php


namespace AppBundle\Controller;


use AppBundle\Entity\WebauthnCredential;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class SecondFactorDispatcherController
 * @package AppBundle\Controller
 * @Route("/secondfactor")
 */
class SecondFactorDispatcherController extends Controller
{
    /**
     * @Route("/")
     * @return RedirectResponse
     */
    public function indexAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $fido_tokens = $entityManager->getRepository(WebauthnCredential::class)->findByUser($this->getUser());
        return $this->render('AppBundle:Secondfactor:index.html.twig', array(
            'fido_tokens' => $fido_tokens,
        ));
        //return $this->redirectToRoute('app_webauthn_index');
    }
}