<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends AppController
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render($this->getTemplateBundleName() . ':Default:index.html.twig', array());
    }

    /**
     * @Route("/docs")
     */
    public function docsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $feds = $em->getRepository('AppBundle:Federation')->findBy(array(), array('name' => 'ASC'));

        return $this->render('AppBundle:Default:docs.html.twig', array(
            'feds'             => $feds,
        ));

    }

    /**
     * @Route("/privacypolicy")
     */
    public function privacypolicyAction()
    {
        return $this->render($this->getTemplateBundleName() . ':Default:privacypolicy.html.twig', array(
        ));
    }
}
