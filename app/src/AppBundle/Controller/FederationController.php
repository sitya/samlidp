<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Federation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Federation controller.
 *
 * @Route("federation")
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 */
class FederationController extends Controller
{
    /**
     * Lists all federation entities.
     *
     * @Route("/", name="federation_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $federations = $em->getRepository('AppBundle:Federation')->findAll();

        return $this->render('AppBundle:Federation:index.html.twig', array(
            'federations' => $federations,
        ));
    }

    /**
     * Creates a new federation entity.
     *
     * @Route("/new", name="federation_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $federation = new Federation();
        $form = $this->createForm('AppBundle\Form\FederationType', $federation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($federation);
            $em->flush();

            return $this->redirectToRoute('federation_show', array('id' => $federation->getId()));
        }

        return $this->render('AppBundle:Federation:new.html.twig', array(
            'federation' => $federation,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a federation entity.
     *
     * @Route("/{id}", name="federation_show")
     * @Method("GET")
     */
    public function showAction(Federation $federation)
    {
        return $this->render('AppBundle:Federation:show.html.twig', array(
            'federation' => $federation,
        ));
    }

    /**
     * Displays a form to edit an existing federation entity.
     *
     * @Route("/{id}/edit", name="federation_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Federation $federation)
    {
        $editForm = $this->createForm('AppBundle\Form\FederationType', $federation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('federation_edit', array('id' => $federation->getId()));
        }

        return $this->render('AppBundle:Federation:edit.html.twig', array(
            'federation' => $federation,
            'edit_form' => $editForm->createView(),
        ));
    }
}

