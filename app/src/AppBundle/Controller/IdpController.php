<?php

namespace AppBundle\Controller;

use AppBundle\Entity\IdP;
use AppBundle\Entity\OrganizationElement;
use AppBundle\Entity\Domain;
use AppBundle\Entity\Scope;
use AppBundle\Entity\Entity;
use AppBundle\Form\IdPEditType;
use AppBundle\Form\IdPWizardType;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * @Route("/idp")
 * @Security("has_role('ROLE_USER')")
 */
class IdpController extends AppController
{
    /**
     * @Route("/")
     * @Template()
     */
    public function idpListAction()
    {
        $em = $this->getDoctrine()->getManager();
        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            $idps = $em->getRepository('AppBundle:IdP')->findAll();
        } else {
            $idps = $this->getUser()->getIdps();
        }

        return array(
            'idps' => $idps,
            'samlidp_hostname' => $this->getParameter('samlidp_hostname')
        );
    }

    /**
     * @Route("/add")
     * @Template()
     */
    public function idpAddAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $idp = new IdP();
        $form = $this->createForm(IdPWizardType::class, $idp);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $request->request->get('id_p_wizard');

            $now = new \DateTime();
            $idp
                ->setHostname(strtolower($idp->getHostname()))
                ->setRegistrationInstant($now)
                ->addUser($this->getUser());

            // Handle cert & key
            $privKey = new RSA();
            extract($privKey->createKey(2048));
            $privKey->loadKey($privatekey);

            $pubKey = new RSA();
            $pubKey->loadKey($publickey);
            $pubKey->setPublicKey();

            $subject = new X509();
            // $subject->setDNProp('id-at-organizationName', '');
            $subject->setDNProp('id-at-commonName', strtolower($idp->getHostname()).'.' . $this->getParameter('samlidp_hostname'));
            $subject->setPublicKey($pubKey);

            $issuer = new X509();
            $issuer->setPrivateKey($privKey);
            $issuer->setDN($subject->getDN());

            $x509 = new X509();
            $result = $x509->sign($issuer, $subject);

            $idp
                ->setCertKey($privKey->getPrivateKey())
                ->setCertPem($x509->saveX509($result));

            $defaultDomain = $em->getRepository('AppBundle:Domain')->findOneByDomain($this->getParameter('samlidp_hostname'));
            $newScope = new Scope();
            $newScope->setValue(strtolower($idp->getHostname()));
            $newScope->setDomain($defaultDomain);

            $em->persist($idp);
            $em->persist($newScope);
            $em->flush();

            $this->sendToSlackAction('New IdP created: '.$idp->getHostname().', user: '.$this->getUser()->getUsername());

            return $this->redirect($this->generateUrl('app_idp_idpedit', array('id' => $idp->getId())));
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/edit/{id}")
     */
    public function idpEditAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $idp = $em->getRepository('AppBundle:IdP')->find($id);
        if (!$idp) {
            throw $this->createNotFoundException('The idp does not exist');
        }
        if (!$this->validateOwnership($idp)) {
            throw $this->createAccessDeniedException();
        }

        $create = false;
        if (empty($idp->getInstituteName())) {
            $create = true;
        }

        // check and refresh uploaded logo to local webdir
        $logo_filename = $idp->getLogo();
        $logo_path = $this->get('kernel')->getRootDir().'/../web/images/idp_logo/';

        if ($logo_filename && !is_file($logo_path.$logo_filename)) {
            $filesystem = $this->get('oneup_flysystem.logos_filesystem');
            $contents = $filesystem->read($logo_filename);
            file_put_contents($logo_path.$logo_filename, $contents);
        }

        $form = $this->createForm(IdPEditType::class, $idp);

        $instituteName = new OrganizationElement();
        $instituteName->setType('Name');
        $instituteName->setLang('en');
        $instituteName->setIdP($idp);

        $instituteUrl = new OrganizationElement();
        $instituteUrl->setType('InformationUrl');
        $instituteUrl->setLang('en');
        $instituteUrl->setIdP($idp);

        foreach ($idp->getOrganizationElements() as $oe) {
            if ($oe->getType() == 'Name') {
                $form->get('instituteName')->setData($oe->getValue());
                $instituteName = $oe;
            }
            if ($oe->getType() == 'InformationUrl') {
                $form->get('instituteUrl')->setData($oe->getValue());
                $instituteUrl = $oe;
            }
        }

        $form->handleRequest($request);

        if (empty($form->get('instituteName')->getData()) && $form->isSubmitted()) {
            $formerror = new FormError('Name of your Organization cannot be empty.');
            $form->get('instituteName')->addError($formerror);
        }

        if (empty($form->get('instituteUrl')->getData()) && $form->isSubmitted()) {
            $formerror = new FormError('URL of your Organization cannot be empty.');
            $form->get('instituteUrl')->addError($formerror);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('instituteName') != $instituteName->getValue()) {
                $instituteName->setValue($form->get('instituteName')->getData());
                $em->persist($instituteName);
            }
            if ($form->get('instituteUrl') != $instituteUrl->getValue()) {
                $instituteUrl->setValue($form->get('instituteUrl')->getData());
                $em->persist($instituteUrl);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($idp);
            $em->flush();

            $message = $this->get('translator')->trans('edit.idp_updated_successful', array(), 'idp');
            if ($create) {
                $message = $this->get('translator')->trans('edit.create_step_second_success', array(), 'idp');
            }
            $this->get('session')->getFlashBag()->add('success', $message);

            return $this->redirect($this->generateUrl('app_idp_idpedit', array('id' => $idp->getId())).'#domaindiv');
        }

        $federations = $em->getRepository('AppBundle:Federation')->findBy(array(), array('name' => 'ASC'));

        $deleteForm = $this->createDeleteForm($id);

        return $this->render($this->getTemplateBundleName() . ':Idp:idpEdit.html.twig', array(
            'idp' => $idp,
            'form' => $form->createView(),
            'federations' => $federations,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Creates a form to delete a IdP entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('idp_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('delete', SubmitType::class, array('attr' => array('class' => 'btn-danger')))
            ->getForm();
    }

    /**
     * Deletes a IdP entity.
     *
     * @Route("/{id}", name="idp_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:IdP')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find IdP entity.');
            }

            $hostname = $entity->getHostname();
            $em->remove($entity);
            $em->flush();
            $defScope = $em->getRepository('AppBundle:Scope')->findByValue($hostname);
            foreach ($defScope as $scope) {
                $em->remove($scope);
                $em->flush($scope);
            }
            $this->get('session')->getFlashBag()->add('success', 'Identity Provider deleted.');
        }

        return $this->redirect($this->generateUrl('app_idp_idplist'));
    }

    /**
     * @Route("/delete/{id}")
     * @Template()
     */
    public function idpDeleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $idp = $em->getRepository('AppBundle:IdP')->find($id);
        if (!$idp) {
            throw $this->createNotFoundException('The idp does not exist');
        }
        if (!$this->validateOwnership($idp)) {
            throw $this->createAccessDeniedException();
        }

        return array(
            'idp' => $idp,
        );
    }

    /**
     * @Route("/checkdomain")
     * @Method("POST")
     */
    public function checkDomainOwner(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $domain = $request->request->get('domain');
            $idpid = $request->request->get('idpid');

            $em = $this->getDoctrine()->getManager();
            $idp = $em->getRepository('AppBundle:IdP')->find($idpid);

            if ($idp) {
                if (!$idp->hasDomain($domain)) {
                    $records = dns_get_record($domain, DNS_TXT);
                    if (!empty($records)) {
                        foreach ($records as $record) {
                            if ($record['type'] == 'TXT') {
                                if ($record['txt'] == $idp->getDNSCheckerHash()) {
                                    $domainObject = new Domain();
                                    $domainObject->setDomain($domain);
                                    $scope = new Scope();
                                    $scope->setValue('@');
                                    $domainObject->addScope($scope);
                                    $domainObject->setIdP($idp);
                                    $em->persist($domainObject);
                                    $em->flush();

                                    return new JsonResponse(array('success' => true));
                                }
                            }
                        }

                        return new JsonResponse(
                            array(
                                'success' => false,
                                'message' => 'There is no TXT record with the verification value: '.$idp->getDNSCheckerHash(),
                            )
                        );
                    } else {
                        return new JsonResponse(
                            array(
                                'success' => false,
                                'message' => "There is no TXT record for $domain domain",
                            )
                        );
                    }
                } else {
                    return new JsonResponse(
                        array(
                            'success' => false,
                            'message' => "It's a verified domain.",
                        )
                    );
                }
            } else {
                return new JsonResponse(
                    array(
                        'success' => false,
                        'message' => 'There is no IdP',
                    )
                );
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * @Route("/scopeadd")
     * @Method("POST")
     */
    public function scopeAdd(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $domainid = $request->request->get('domainid');
            $scopeValue = $request->request->get('scopeValue');
            if (!preg_match('/^[a-zA-Z0-9\.-]+$/', $scopeValue)) {
                return new JsonResponse(array('success' => false, 'message' => 'Invalid domain name format.'));
            }
            if (is_numeric($domainid)
                && is_string($scopeValue)
                && preg_match('/[a-z0-9-]*/', $scopeValue)
                ) {
                $em = $this->getDoctrine()->getManager();
                $domain = $em->getRepository('AppBundle:Domain')->find($domainid);
                $idp = $domain->getIdP();
                if ($idp && $domain) {
                    if ($this->validateOwnership($idp)) {
                        // itt nézzük meg, hogy a scope létezik-e már
                        foreach ($domain->getScopes() as $curr_scope) {
                            if ($curr_scope->getValue() == $scopeValue) {
                                return new JsonResponse(
                                    array(
                                        'success' => false,
                                        'message' => 'This scope already exists.',
                                    )
                                );
                            }
                        }
                        $scope = new Scope();
                        $scope->setValue($scopeValue);
                        $domain->addScope($scope);
                        $em->persist($domain);
                        $em->flush();

                        return new JsonResponse(array('success' => true, 'id' => $scope->getId()));
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => 'unauthorized'), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => 'IdP and Domain did not match.'));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => 'Wrong parameters.'));
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * @Route("/scopedelete")
     * @Method("POST")
     */
    public function scopeDelete(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $scopeid = $request->request->get('scopeid');
            if (is_numeric($scopeid)) {
                $em = $this->getDoctrine()->getManager();
                $scope = $em->getRepository('AppBundle:Scope')->find($scopeid);
                $idp = $scope->getDomain()->getIdP();
                if ($idp) {
                    if ($this->validateOwnership($idp)) {
                        if ($scope == $idp->getDefaultScope()) {
                            return new JsonResponse(
                                array(
                                    'success' => false,
                                    'message' => 'You cannot delete the default scope.',
                                )
                            );
                        } else {
                            foreach ($scope->getIdPUsers() as $IdPUser) {
                                $IdPUser->setScope(null);
                                $em->persist($IdPUser);
                            }
                            $em->remove($scope);
                            $em->flush();

                            return new JsonResponse(array('success' => true, 'message' => 'Scope deleted.'));
                        }
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => 'Unauthorized'), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => 'IdP and Domain did not match.'));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => 'Wrong parameters.'));
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * @Route("/scopeupdate")
     * @Method("POST")
     */
    public function scopeUpdate(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $scopeid = $request->get('scopeid');
            $scopeValue = $request->request->get('scopeValue');
            if (!preg_match('/^[a-zA-Z0-9\.-]+$/', $scopeValue)) {
                return new JsonResponse(array('success' => false, 'message' => 'Invalid domain name format.'));
            }
            if (is_numeric($scopeid)
                && is_string($scopeValue)
                && preg_match('/[a-z0-9-]*/', $scopeValue)
                ) {
                $em = $this->getDoctrine()->getManager();
                $scope = $em->getRepository('AppBundle:Scope')->find($scopeid);
                $idp = $scope->getDomain()->getIdP();
                if ($idp) {
                    if ($this->validateOwnership($idp)) {
                        // itt nézzük meg, hogy a scope létezik-e már
                        foreach ($scope->getDomain()->getScopes() as $curr_scope) {
                            if ($curr_scope->getValue() == $scopeValue) {
                                return new JsonResponse(array('success' => false, 'message' => 'This scope already exists.'));
                            }
                        }
                        $scope->setValue($scopeValue);

                        $em->persist($scope);
                        $em->flush();

                        return new JsonResponse(array('success' => true, 'id' => $scope->getId()));
                    } else {
                        return new JsonResponse(
                            array(
                                'success' => false,
                                'message' => 'unauthorized',
                            ),
                            403
                        );
                    }
                } else {
                    return new JsonResponse(
                        array(
                            'success' => false,
                            'message' => 'IdP and/or Domain did not exists.', )
                    );
                }
            } else {
                return new JsonResponse(
                    array(
                        'success' => false,
                        'message' => 'Wrong parameters.',
                    )
                );
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * @Route("/setdefaultscope")
     * @Method("POST")
     */
    public function setDefaultScope(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $scopeid = $request->request->get('scopeid');

            if (is_numeric($scopeid)) {
                $em = $this->getDoctrine()->getManager();
                $scope = $em->getRepository('AppBundle:Scope')->find($scopeid);
                $idp = $scope->getDomain()->getIdP();
                if ($this->validateOwnership($idp)) {
                    if ($scope) {
                        $idp->setDefaultScope($scope);
                        $em->persist($idp);
                        $em->flush();

                        return new JsonResponse(array('success' => true));
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => 'No such scope.'));
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => 'unauthorized'), 403);
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => 'Wrong parameters.'));
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * @Route("/domaindelete")
     * @Method("POST")
     */
    public function domainDelete(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $domainid = $request->request->get('domainid');
            if (is_numeric($domainid)) {
                $em = $this->getDoctrine()->getManager();
                $domain = $em->getRepository('AppBundle:Domain')->find($domainid);
                $idp = $domain->getIdP();
                if ($idp) {
                    if ($this->validateOwnership($idp)) {
                        foreach ($domain->getScopes() as $scope) {
                            if ($scope == $idp->getDefaultScope()) {
                                return new JsonResponse(
                                    array(
                                        'success' => false,
                                        'message' => 'You cannot delete domain that contain the default scope.',
                                    )
                                );
                            }
                        }
                        foreach ($domain->getScopes() as $scope) { // nem megy a cascade remove
                            $em->remove($scope);
                        }
                        $em->flush();

                        $em->remove($domain);
                        $em->flush();

                        return new JsonResponse(array('success' => true, 'message' => 'Domain deleted.'));
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => 'Unauthorized'), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => 'IdP did not match.'));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => 'Wrong parameters.'));
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * @Route("/removelogo")
     * @Method("POST")
     */
    public function removeLogoAction(Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $idp = $em->getRepository('AppBundle:IdP')->find($request->get('idpid'));
            $filename = $idp->getLogo();
            $idp->setLogo(false);
            $em->persist($idp);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(array('success' => false, 'message' => $e->getMessage()));
        }

        try {
            $fs = new Filesystem();
            $oneup_config = $this->container->getParameter('oneup_uploader.config.idplogos');
            $directory = $oneup_config['storage']['directory'];
            $fs->remove($directory.'/'.$filename);
        } catch (\Exception $e) {
            $this->get('logger')->warning('Cant remove idp logo. IdP id: '.$idp->getId().' exception message:  '.$e->getMessage);
        }

        return new JsonResponse(array('success' => true, 'message' => 'Logo removed.'));
    }

    public function validateOwnership(\AppBundle\Entity\IdP $idp)
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return true;
        }
        foreach ($idp->getUsers() as $user) {
            if ($this->getUser() == $user) {
                return true;
            }
        }

        return false;
    }

    /**
     * @Route("/checkifidpexists")
     * @Method("POST")
     */
    public function checkIfIdpExistsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $idp = $em->getRepository('AppBundle:IdP')->findOneByHostname(strtolower($request->get('hostname')));
        $ret = ($idp) ? false : true;

        return new JsonResponse(array('success' => $ret));
    }

    /**
     * @Route("/changefederation")
     * @Method("POST")
     */
    public function changeFederationAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $idp = $em->getRepository('AppBundle:IdP')->findOneById($request->get('idpid'));

        if (!$this->validateOwnership($idp)) {
            throw $this->createAccessDeniedException();
        }

        $federation = $em->getRepository('AppBundle:Federation')->findOneBySlug($request->get('fedslug'));

        $in = false;
        foreach ($idp->getFederations() as $fed) {
            if ($fed == $federation) {
                $in = true;
            }
        }

        if ($in) {
            $federation->removeIdp($idp);
        } else {
            $federation->addIdp($idp);
        }
        $em->persist($federation);
        $em->flush($federation);

        return new JsonResponse(array('success' => true));
    }

    public function sendToSlackAction($message)
    {
        $data = 'payload='.json_encode(array(
                'channel' => '#notifications',
                'text' => $message,
                'icon_emoji' => ':longbox:',
            ));
        $slackUrl = $this->getParameter('slack_url');
        $ch = curl_init('https://hooks.slack.com/services/'.$slackUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function attributeMapOid2Name($attributes)
    {
        $attributemap = array(
            'urn:oid:0.9.2342.19200300.100.1.3' => 'mail',
            'urn:oid:1.3.6.1.4.1.5923.1.1.1.10' => 'eduPersonTargetedID',
            'urn:oid:1.3.6.1.4.1.5923.1.1.1.6' => 'eduPersonPrincipalName',
            'urn:oid:1.3.6.1.4.1.5923.1.1.1.9' => 'eduPersonScopedAffiliation',
            'urn:oid:1.3.6.1.4.1.25178.1.2.10' => 'schacHomeOrganizationType',
            'urn:oid:2.16.840.1.113730.3.1.241' => 'displayName',
            'urn:oid:2.5.4.4' => 'sn',
            'urn:oid:2.5.4.42' => 'givenName',
            'urn:oasis:names:tc:SAML:attribute:pairwise-id' => 'pairwise-id',
            'urn:oasis:names:tc:SAML:attribute:subject-id' => 'subject-id'
        );
        $ret = array();
        foreach ($attributes as $oid) {
            if (isset($attributemap[$oid])) {
                $ret[] = $attributemap[$oid];
            }
        }

        return $ret;
    }

    private function attributeMapName2Oid($attributes)
    {
        $attributemap = array(
            'mail' => 'urn:oid:0.9.2342.19200300.100.1.3',
            'eduPersonTargetedID' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
            'eduPersonPrincipalName' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
            'eduPersonScopedAffiliation' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.9',
            'schacHomeOrganizationType' => 'urn:oid:1.3.6.1.4.1.25178.1.2.10',
            'displayName' => 'urn:oid:2.16.840.1.113730.3.1.241',
            'sn' => 'urn:oid:2.5.4.4',
            'givenName' => 'urn:oid:2.5.4.42',
            'subject-id' => 'urn:oasis:names:tc:SAML:attribute:subject-id',
            'pairwise-id' => 'urn:oasis:names:tc:SAML:attribute:pairwise-id'
        );
        $ret = array();
        foreach ($attributes as $name) {
            if (isset($attributemap[$name])) {
                $ret[] = $attributemap[$name];
            }
        }

        return $ret;
    }

    /**
     * @Route("/changespattributes")
     * @Method("POST")
     */
    public function spChangeAttributes(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $idpid = $request->request->get('idpid');
            $spid = $request->request->get('spid');
            $attributes = $request->request->get('attributes');
            if (is_numeric($spid) && is_numeric($idpid) && is_array($attributes)) {
                $targetAttributes = array();
                foreach ($attributes as $attribute) {
                    $targetAttributes[] = $attribute['value'];
                }

                $em = $this->getDoctrine()->getManager();
                $idp = $em->getRepository('AppBundle:IdP')->findOneById($request->get('idpid'));

                if ($idp) {
                    if ($this->validateOwnership($idp)) {
                        $sp = $em->getRepository('AppBundle:Entity')->findOneById($request->get('spid'));
                        if (!$sp) {
                            return new JsonResponse(array('success' => false, 'message' => 'Invalid SP identifier'));
                        }

                        foreach ($idp->getEntities() as $entity) {
                            if ($entity->getId() == $spid) {
                                $spEntitydata = unserialize(stream_get_contents($sp->getEntitydata()));
                                $spEntitydata['attributes'] = $this->attributeMapName2Oid($targetAttributes);
                                $spEntitydata['attributes.required'] = $this->attributeMapName2Oid($targetAttributes);
                                $sp->setEntitydata(serialize($spEntitydata));
                                $sp->setSha1sum(sha1($sp->getEntitydata()));
                                $sp->setLastModified(new \DateTime());
                                $em->persist($sp);
                                $em->flush($sp);

                                return new JsonResponse(array('success' => true, 'message' => 'Attributes saved.'));
                            }
                        }
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => 'Unauthorized'), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => 'IdP did not match.'));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => 'Wrong parameters.'));
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * @Route("/spedit")
     * @Method("POST")
     */
    public function spEdit(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $idpid = $request->request->get('idpid');
            $spid = $request->request->get('spid');
            if (is_numeric($idpid) && is_numeric($spid)) {
                $em = $this->getDoctrine()->getManager();

                $idp = $em->getRepository('AppBundle:IdP')->find($idpid);
                if ($idp) {
                    if ($this->validateOwnership($idp)) {
                        $sp = $em->getRepository('AppBundle:Entity')->find($spid);

                        if (!$sp) {
                            return new JsonResponse(array('success' => false, 'message' => 'Invalid SP identifier'));
                        }

                        foreach ($idp->getEntities() as $entity) {
                            if ($entity->getId() == $sp->getId()) {
                                $spData = unserialize(stream_get_contents($sp->getEntitydata()));
                                return new JsonResponse(array('success' => true, 'modificable' => $sp->getModificable(), 'attributes' => json_encode(isset($spData['attributes']) ? $this->attributeMapOid2Name($spData['attributes']) : array())));
                            }
                        }

                        return new JsonResponse(array('success' => false, 'message' => 'There is no connection between your IdP and the given SP identifier.'));
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => 'Unauthorized'), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => 'IdP did not match.'));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => 'Wrong parameters.'));
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * @Route("/spdelete")
     * @Method("POST")
     */
    public function spDelete(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $idpid = $request->request->get('idpid');
            $spid = $request->request->get('spid');
            if (is_numeric($idpid) && is_numeric($spid)) {
                $em = $this->getDoctrine()->getManager();

                $idp = $em->getRepository('AppBundle:IdP')->find($idpid);
                if ($idp) {
                    if ($this->validateOwnership($idp)) {
                        $sp = $em->getRepository('AppBundle:Entity')->find($spid);

                        if (!$sp) {
                            return new JsonResponse(array('success' => false, 'message' => 'Invalid SP identifier'));
                        }

                        foreach ($idp->getEntities() as $entity) {
                            if ($entity->getId() == $sp->getId()) {
                                $em->remove($sp);
                                $em->flush($sp);

                                return new JsonResponse(array('success' => true, 'message' => 'Service Provider removed.'));
                            }
                        }

                        return new JsonResponse(array('success' => false, 'message' => 'There is no connection between your IdP and the given SP identifier.'));
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => 'Unauthorized'), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => 'IdP did not match.'));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => 'Wrong parameters.'));
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * @Route("/spadd")
     * @Method("POST")
     */
    public function spAdd(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $idpid = $request->request->get('idpid');
            $spmetadataurl = trim($request->request->get('spmetadataurl'));
            $spXml = $request->request->get('spmetadataxml');
            if (is_numeric($idpid)) {
                $em = $this->getDoctrine()->getManager();
                $idp = $em->getRepository('AppBundle:IdP')->find($idpid);

                if ($idp) {
                    if ($this->validateOwnership($idp)) {
                        if ($spmetadataurl) {
                            if (filter_var($spmetadataurl, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) !== false) {
                                $parsedUrl = parse_url($spmetadataurl, PHP_URL_HOST);

                                if (filter_var($parsedUrl, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                                    //var_dump(filter_var($spmetadataurl, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE));
                                } else {
                                    if (!checkdnsrr($parsedUrl)) {
                                        return new JsonResponse(array('success' => false, 'message' => 'The given URL is not reachable.'));
                                    }
                                }
                                $rawheader = "User-Agent: SimpleSAMLphp metarefresh, run by ".$this->getParameter('samlidp_hostname'). "\r\n";
                                $context = array('http' => array('ignore_errors' => true, 'header' => $rawheader, 'timeout' => 5));
                                try {
                                    list($spXml, $responseHeaders) = \SimpleSAML\Utils\HTTP::fetch($spmetadataurl, $context, true);
                                } catch (\Exception $e) {
                                    return new JsonResponse(array('success' => false, 'message' => 'The given URL is not reachable.'));
                                }
                                if (!preg_match('/xml/', $responseHeaders['content-type'])) {
                                    return new JsonResponse(array('success' => false, 'message' => 'There is no SAML metadata file on the given URL.'));
                                }
                            } else {
                                return new JsonResponse(array('success' => false, 'message' => 'It does not seem to be a valid URL.'));
                            }
                        }
                        try {
                            $response = $this->spAddFunction($spXml, $idp, $em);

                            return $response;
                        } catch (\Exception $e) {
                            return new JsonResponse(array('success' => false, 'message' => $e->getMessage()), 500);
                        }
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => 'Unauthorized'), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => 'IdP did not match.'));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => 'It does not seem to be a valid IdP.'));
            }
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * Add the SP to database.
     *
     * @param $spXml sp metadata in xml format
     * @param $idp the idp entity
     * @param $em entity manager
     *
     * @return JsonResponse
     */
    private function spAddFunction($spXml, IdP $idp, EntityManager $em)
    {
        $m = \SimpleSAML_Metadata_SAMLParser::parseDescriptorsString($spXml);
        $m = $m[key($m)]->getMetadata20SP();
        if (is_null($m)) {
            return new JsonResponse(array('success' => false, 'message' => 'There is no SAML Service Provider\'s metadata file on the given URL.'));
        }

        $hasEncKey = false;
        $hasSignKey = false;
        foreach ($m['keys'] as $key) {
            if ($key['encryption']) {
                $hasEncKey = true;
            }
            if ($key['signing']) {
                $hasSignKey = true;
            }
        }
        if (!$hasSignKey) {
            return new JsonResponse(array('success' => false, 'message' => 'The given metadata does not contain a public key for signing.'));
        }

        if (!$hasEncKey) {
            return new JsonResponse(array('success' => false, 'message' => 'The given metadata does not contain a public key for encrypting.'));
        }

        unset($m['entityDescriptor']);
        unset($m['expire']);
        unset($m['metadata-set']);
        $spentityid = $m['entityid'];
        $modificable = (isset($m['attributes'])) ? false : true;
        $spentities = $em->getRepository('AppBundle:Entity')->findByEntityid($spentityid);
        if (!$spentities) {
            $sp = new Entity();
            $sp->setSha1sum(sha1($spXml))
                ->setEntityid($m['entityid'])
                ->setEntitydata(serialize($m))
                ->setLastModified(new \DateTime())
                ->setModificable($modificable);
            $em->persist($sp);
            $em->flush($sp);
            $idp->addEntity($sp);
            $em->persist($idp);
            $em->flush($idp);

            return new JsonResponse(array('success' => true, 'message' => 'Service Provider added', 'spid' => $sp->getId(), 'attributes' => (isset($m['attributes']) ? $this->attributeMapOid2Name($m['attributes']) : null)));
        } else {
            foreach ($spentities as $spentity) {
                $commonFederations = null;
                $alreadyIn = false;
                foreach ($idp->getFederations() as $idpFed) {
                    if ($spentity->getFederation() == $idpFed) {
                        $commonFederations = $idpFed;
                    }
                }
                foreach ($spentity->getIdps() as $spidp) {
                    if ($spidp->getEntityid() == $idp->getEntityid()) {
                        $alreadyIn = true;
                    }
                }
            }
            if ($alreadyIn) {
                return new JsonResponse(array('success' => false, 'message' => 'This Service Provider has been already added.'));
            } elseif ($commonFederations != null) {
                return new JsonResponse(array('success' => 'warning', 'message' => 'Your Identity Provider already knows that Service Provider via '.$commonFederations->getName()));
            } else {
                $newsp = new Entity();
                $newsp->setSha1sum(sha1($spXml))
                    ->setEntityid($m['entityid'])
                    ->setEntitydata(serialize($m))
                    ->setLastModified(new \DateTime())
                    ->setModificable($modificable);
                $em->persist($newsp);
                $em->flush($newsp);

                $idp->addEntity($newsp);
                $em->persist($idp);
                $em->flush($idp);

                return new JsonResponse(array('success' => true, 'message' => 'Service Provider added', 'spid' => $newsp->getId(), 'attributes' => (isset($m['attributes']) ? $this->attributeMapOid2Name($m['attributes']) : null)));
            }
        }
    }
}
