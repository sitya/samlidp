<?php

namespace AppBundle\Controller;

use AppBundle\Entity\IdPUser;
use AppBundle\Entity\IdP;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Utils\IdPUserHelper;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Idpuser controller.
 *
 * @Route("idpuser")
 * @Security("has_role('ROLE_USER')")
 */
class IdPUserController extends Controller
{
    /**
     * Lists all idPUser entities.
     *
     * @Route("/idp/{id}", name="idpuser_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $idp = $em->getRepository('AppBundle:IdP')->find($id);
        $idp->validateAccess($this->getUser());

        $idPUsers = $em->getRepository('AppBundle:IdPUser')->findBy(array('deleted' => false, 'IdP' => $idp));

        return array(
            'idPUsers' => $idPUsers,
            'IdP' => $idp,
        );
    }

    /**
     * Creates a new idPUser entity.
     *
     * @Route("/new/{id}", name="idpuser_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $idp = $em->getRepository('AppBundle:IdP')->find($id);
        $idp->validateAccess($this->getUser());

        $idPUser = new Idpuser();
        $idPUser->setIdP($idp);
        $form = $this->createForm('AppBundle\Form\IdPUserType', $idPUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $token = $this->get('fos_user.util.token_generator')->generateToken();
            $idPUser->setConfirmationToken($token);
            $idPUser->setPasswordRequestedAt(new \DateTime());

            $idPUser->setPassword($this->getRandomPassword());
            $em->persist($idPUser);
            $em->flush();

            IdPUserHelper::sendPasswordResetToken($idPUser, $token, $this->get('router'), $this->get('twig'), $this->get('mailer'), $this->getParameter('mailer_sender'), 'sendPasswordCreateToken');

            $this->get('session')->getFlashBag()->add('success', 'User created successful.');

            return $this->redirectToRoute('idpuser_index', array('id' => $idp->getId()));
        }

        return array(
            'IdP' => $idp,
            'idPUser' => $idPUser,
            'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing idPUser entity.
     *
     * @Route("/{id}/edit", name="idpuser_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, IdPUser $idPUser)
    {
        $idp = $idPUser->getIdP();
        $idp->validateAccess($this->getUser());

        //$deleteForm = $this->createDeleteForm($idPUser);
        $editForm = $this->createForm('AppBundle\Form\IdPUserType', $idPUser);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->get('session')->getFlashBag()->add('success', 'User updated successful.');

            return $this->redirectToRoute('idpuser_edit', array('id' => $idPUser->getId()));
        }

        return array(
            'idPUser' => $idPUser,
            'edit_form' => $editForm->createView(),
            //'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Make an idPUser entity to inactive NOT DELETE.
     *
     * @Route("/inactivate/{id}", name="idpuser_inactivate")
     */
    public function inactivateAction(Request $request, IdPUser $idPUser)
    {
        $idPUser = $request->attributes->get('idPUser');

        $idp = $idPUser->getIdP();
        $idp->validateAccess($this->getUser());

        $em = $this->getDoctrine()->getManager();
        $idPUser->setEnabled(false);
        $em->persist($idPUser);
        $em->flush($idPUser);
        $this->get('session')->getFlashBag()->add('success', 'User inactivated successful.');

        return $this->redirectToRoute('idpuser_index', array('id' => $idp->getId()));
    }

    /**
     * Make an idPUser entity to inactive NOT DELETE.
     *
     * @Route("/delete/{id}", name="idpuser_delete")
     */
    public function deleteAction(Request $request, IdPUser $idPUser)
    {
        $idPUser = $request->attributes->get('idPUser');

        $idp = $idPUser->getIdP();
        $idp->validateAccess($this->getUser());

        $em = $this->getDoctrine()->getManager();
        $idPUser->setDeleted(true);
        $idPUser->saltUser();
        $em->persist($idPUser);
        $em->flush($idPUser);
        $this->get('session')->getFlashBag()->add('success', 'User deleted successful.');

        return $this->redirectToRoute('idpuser_index', array('id' => $idp->getId()));
    }

    /**
     * Displays a form to edit an existing idPUser entity.
     *
     * @Route("/massimport/{id}", name="idpuser_massimport")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function massimportAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $idp = $em->getRepository('AppBundle:IdP')->find($id);

        $idp->validateAccess($this->getUser());

        return array(
            'idp' => $idp,
        );
    }

    /**
     * Creates a form to delete a idPUser entity.
     *
     * @param IdPUser $idPUser The idPUser entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    // private function createDeleteForm(IdPUser $idPUser)
    // {
    //     return $this->createFormBuilder()
    //         ->setAction($this->generateUrl('idpuser_delete', array('id' => $idPUser->getId())))
    //         ->setMethod('DELETE')
    //         ->getForm()
    //     ;
    // }

    /**
     * Displays a form to edit an existing idPUser entity.
     *
     * @Route("/initpasswordreset/{id}", name="idpuser_initpasswordreset")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function initPasswordResetAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $idpuser = $em->getRepository('AppBundle:IdPUser')->find($id);

        $idpuser->getIdP()->validateAccess($this->getUser());

        $token = $this->get('fos_user.util.token_generator')->generateToken();
        $idpuser->setConfirmationToken($token);
        $idpuser->setPasswordRequestedAt(new \DateTime());

        $em->persist($idpuser);
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', 'Password reset mail sent successful.');

        IdPUserHelper::sendPasswordResetToken($idpuser, $token, $this->get('router'), $this->get('twig'), $this->get('mailer'), $this->getParameter('mailer_sender'), 'sendPasswordResetToken');

        return $this->redirectToRoute('idpuser_index', array('id' => $idpuser->getIdP()->getId()));
    }

    private function getRandomPassword()
    {
        return substr(
            str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
            0,
            32
        );
    }

    /**
     * @Route("/downloadcsvtemplate/{id}", name="idpuser_downloadcsvtemplate")
     */
    public function downloadCsvTemplateAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $idp = $em->getRepository('AppBundle:IdP')->find($id);
        $idp->validateAccess($this->getUser());

        $idPUsers = $em->getRepository('AppBundle:IdPUser')->findBy(array('deleted' => false, 'IdP' => $idp));

        $response = new StreamedResponse();
        $response->setCallback(function () use ($idPUsers) {
            $handle = fopen('php://output', 'w+');

            // Add the header of the CSV file
            fputcsv($handle, array('action', 'username', 'email', 'surname', 'givenname', 'displayname', 'affiliation', 'scope', 'enabled'), ',');
            // Add the data queried from database
            if (count($idPUsers) == 0) {
                fputcsv(
                    $handle,
                    array('add', 'alice', 'alice@example.com', 'Smith', 'Alice', 'Alice Smith', 'staff', 'test.example.com', 'true'),
                    ','
                );
                fputcsv(
                    $handle,
                    array('update', 'bob', 'bob@example.com', 'Smith', 'Bob', 'Bob Smith', 'student', 'test.example.com', 'false'),
                    ','
                );
                fputcsv(
                    $handle,
                    array('delete', 'carol', 'carol@example.com', 'Smith', 'Carol', 'Carol Smith', 'staff', 'test.example.com', 'true'),
                    ','
                );
            } else {
                foreach ($idPUsers as $user) {
                    $enabled = ($user->getEnabled()) ? 'true' : 'false';
                    fputcsv(
                        $handle,
                        array('update', $user->getUsername(), $user->getEmail(), $user->getSurname(), $user->getGivenName(), $user->getDisplayName(), $user->getAffiliation(), $user->getScope($this->getParameter('samlidp_hostname')), $enabled),
                        ','
                    );
                }
            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="samlidp-'.$idp->getHostname().'-users-export-'.date('Y-m-d').'.csv"');

        return $response;
    }
}
