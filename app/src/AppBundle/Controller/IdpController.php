<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ApiToken;
use AppBundle\Entity\Domain;
use AppBundle\Entity\Entity;
use AppBundle\Entity\IdP;
use AppBundle\Entity\OrganizationElement;
use AppBundle\Entity\Scope;
use AppBundle\Form\ApiTokenType;
use AppBundle\Form\IdPEditType;
use AppBundle\Form\IdPWizardType;
use Doctrine\ORM\EntityManager;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\DataCollectorTranslator;

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
            throw $this->createNotFoundException($this->trans('edit.404.exception'));
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
            $error_message = $this->trans('edit.validate.organizationname.notblank');
            $formerror = new FormError($error_message);
            $form->get('instituteName')->addError($formerror);
        }

        if (empty($form->get('instituteUrl')->getData()) && $form->isSubmitted()) {
            $error_message = $this->trans('edit.validate.organizationurl.notblank');
            $formerror = new FormError($error_message);
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

            $message = $this->trans('edit.idp_updated_successful');
            if ($create) {
                $message = $this->trans('edit.create_step_second_success');
            }
            $this->get('session')->getFlashBag()->add('success', $message);

            return $this->redirect($this->generateUrl('app_idp_idpedit', array('id' => $idp->getId())).'#domaindiv');
        }

        $federations = $em->getRepository('AppBundle:Federation')->findBy(array(), array('name' => 'ASC'));

        $deleteForm = $this->createDeleteForm($id);

        $newApiToken = new ApiToken();
        $newApiToken->setIdp($idp);

        $newapitokenform = $this->createForm(ApiTokenType::class, $newApiToken,  [
            'action' => $this->generateUrl('idp_apitoken'),
            'method' => 'POST',
        ]);

        $apiTokenFormsViews = [];
        /** @var ApiToken $apiToken */
        foreach ($idp->getApiTokens() as $apiToken) {
            $apiTokenFormsViews[] = $this->createForm(ApiTokenType::class, $apiToken,  [
                'action' => $this->generateUrl('idp_apitoken'),
                'method' => 'POST',
            ])->createView();
        }

        return $this->render($this->getTemplateBundleName() . ':Idp:idpEdit.html.twig', array(
            'idp' => $idp,
            'form' => $form->createView(),
            'federations' => $federations,
            'delete_form' => $deleteForm->createView(),
            'newapitokenform' => $newapitokenform->createView(),
            'apitokenforms' => $apiTokenFormsViews,
        ));
    }

    /**
     * Creates a form to delete a IdP entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\FormInterface The form
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
                throw $this->createNotFoundException($this->trans('idp.delete.404'));
            }

            $hostname = $entity->getHostname();
            $em->remove($entity);
            $em->flush();
            $defScope = $em->getRepository('AppBundle:Scope')->findByValue($hostname);
            foreach ($defScope as $scope) {
                $em->remove($scope);
                $em->flush($scope);
            }
            $this->get('session')->getFlashBag()->add('success', $this->trans('idp.delete.deleted'));
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
            throw $this->createNotFoundException($this->trans('idp.delete.404'));
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
                                'message' => $this->trans('idp.checkdomain.txt.notok').$idp->getDNSCheckerHash(),
                            )
                        );
                    } else {
                        return new JsonResponse(
                            array(
                                'success' => false,
                                'message' => $this->trans('idp.checkdomain.txt.empty', array('%domain%' => $domain)),

                            )
                        );
                    }
                } else {
                    return new JsonResponse(
                        array(
                            'success' => false,
                            'message' => $this->trans('idp.checkdomain.verified_domain'),
                        )
                    );
                }
            } else {
                return new JsonResponse(
                    array(
                        'success' => false,
                        'message' => $this->trans('idp.checkdomain.noidp'),
                    )
                );
            }
        }

        return new Response($this->trans('idp.not_ajax'), 400);
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
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.scope.invalid_domain_name_format')));
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
                                        'message' => $this->trans('idp.scope.already_exists'),
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
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.unauthorized')), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.scope.idp_domain_not_match')));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.wrong_parameters')));
            }
        }

        return new Response($this->trans('idp.not_ajax'), 400);
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
                                    'message' => $this->trans('idp.scope.delete.default'),
                                )
                            );
                        } else {
                            foreach ($scope->getIdPUsers() as $IdPUser) {
                                $IdPUser->setScope(null);
                                $em->persist($IdPUser);
                            }
                            $em->remove($scope);
                            $em->flush();

                            return new JsonResponse(array('success' => true, 'message' => $this->trans('idp.scope.delete.deleted')));
                        }
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.unauthorized')), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.scope.idp_domain_not_match')));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.wrong_parameters')));
            }
        }

        return new Response($this->trans('idp.not_ajax'), 400);
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
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.scope.invalid_domain_name_format')));
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
                                return new JsonResponse(array(
                                    'success' => false,
                                    'message' => $this->trans('idp.scope.already_exists'),
                                    )
                                );
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
                                'message' => $this->trans('idp.unauthorized'),
                            ),
                            403
                        );
                    }
                } else {
                    return new JsonResponse(
                        array(
                            'success' => false,
                            'message' => $this->trans('idp.scope.update.not_exists'),
                            )
                    );
                }
            } else {
                return new JsonResponse(
                    array(
                        'success' => false,
                        'message' => $this->trans('idp.wrong_parameters'),
                    )
                );
            }
        }

        return new Response($this->trans('idp.not_ajax'), 400);
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
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.scope.no_such_scope')));
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.unauthorized')), 403);
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.wrong_parameters')));
            }
        }

        return new Response($this->trans('idp.not_ajax'), 400);
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
                                        'message' => $this->trans('idp.domain.delete.default_scope'),
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
                        return new JsonResponse(array('success' => true, 'message' => $this->trans('idp.domain.delete.deleted')));
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.unauthorized')), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.idp_not_match')));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.wrong_parameters')));

            }
        }

        return new Response($this->trans('idp.not_ajax'), 400);
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
                            return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp_attributes.404')));
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

                                return new JsonResponse(array('success' => true, 'message' => $this->trans('idp.sp_attributes.saved')));
                            }
                        }
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.unauthorized')), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.idp_not_match')));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.wrong_parameters')));
            }
        }

        return new Response($this->trans('idp.not_ajax'), 400);
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
                            return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp_attributes.404')));
                        }

                        foreach ($idp->getEntities() as $entity) {
                            if ($entity->getId() == $sp->getId()) {
                                $spData = unserialize(stream_get_contents($sp->getEntitydata()));
                                return new JsonResponse(array('success' => true, 'modificable' => $sp->getModificable(), 'attributes' => json_encode(isset($spData['attributes']) ? $this->attributeMapOid2Name($spData['attributes']) : array())));
                            }
                        }
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp_edit.no_connection')));
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.unauthorized')), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.idp_not_match')));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.wrong_parameters')));
            }
        }
        return new Response($this->trans('idp.not_ajax'), 400);
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
                            return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp_attributes.404')));
                        }

                        foreach ($idp->getEntities() as $entity) {
                            if ($entity->getId() == $sp->getId()) {
                                $em->remove($sp);
                                $em->flush($sp);
                                return new JsonResponse(array('success' => true, 'message' => $this->trans('idp.sp.delete.deleted')));
                            }
                        }
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp_edit.no_connection')));
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.unauthorized')), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.idp_not_match')));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.wrong_parameters')));
            }
        }

        return new Response($this->trans('idp.not_ajax'), 400);
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
                                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp.add.url_not_reachable')));
                                    }
                                }
                                $rawheader = "User-Agent: SimpleSAMLphp metarefresh, run by ".$this->getParameter('samlidp_hostname'). "\r\n";
                                $context = array('http' => array('ignore_errors' => true, 'header' => $rawheader, 'timeout' => 5));
                                try {
                                    list($spXml, $responseHeaders) = \SimpleSAML\Utils\HTTP::fetch($spmetadataurl, $context, true);
                                } catch (\Exception $e) {
                                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp.add.url_not_reachable')));
                                }
                                if (!preg_match('/xml/', $responseHeaders['content-type'])) {
                                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp.add.no_metadata')));
                                }
                            } else {
                                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp.add.invalid_url')));
                            }
                        }
                        try {
                            $response = $this->spAddFunction($spXml, $idp, $em);

                            return $response;
                        } catch (\Exception $e) {
                            return new JsonResponse(array('success' => false, 'message' => $e->getMessage()), 500);
                        }
                    } else {
                        return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.unauthorized')), 403);
                    }
                } else {
                    return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.idp_not_match')));
                }
            } else {
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp.add.invalid_url')));
            }
        }

        return new Response($this->trans('idp.not_ajax'), 400);
    }

    /**
     * Add the SP to database.
     *
     * @param $spXml sp metadata in xml format
     * @param IdP $idp the idp entity
     * @param EntityManager $em entity manager
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function spAddFunction($spXml, IdP $idp, EntityManager $em)
    {
        $m = \SimpleSAML_Metadata_SAMLParser::parseDescriptorsString($spXml);
        $m = $m[key($m)]->getMetadata20SP();
        if (is_null($m)) {
            return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp.add.no_metadata')));
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
            return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp.add.no_public_key.sign')));
        }

        if (!$hasEncKey) {
            return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp.add.no_public_key.enc')));
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

            return new JsonResponse(array('success' => true,
                'message' => $this->trans('idp.sp.add.added'),
                'spid' => $sp->getId(), 'attributes' => (isset($m['attributes']) ? $this->attributeMapOid2Name($m['attributes']) : null)));
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
                return new JsonResponse(array('success' => false, 'message' => $this->trans('idp.sp.add.already_exists')));
            } elseif ($commonFederations != null) {
                return new JsonResponse(array('success' => 'warning', 'message' => $this->trans('idp.sp.add.already_known', array('%federation_name%' => $commonFederations->getName()))));
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
                return new JsonResponse(array('success' => true, 'message' => $this->trans('idp.sp.add.added'), 'spid' => $newsp->getId(), 'attributes' => (isset($m['attributes']) ? $this->attributeMapOid2Name($m['attributes']) : null)));
            }
        }
    }

    /**
     * @Route("/apitoken", name="idp_apitoken")
     * @Method("POST")
     **/
    public function apiToken(Request $request)
    {
        $apiToken = new ApiToken();
        $form = $this->createForm(ApiTokenType::class, $apiToken);
        $form->handleRequest($request);

        $idp = $apiToken->getIdp();
        $idp->validateAccess($this->getUser());

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($apiToken);
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', $this->trans('idp.apitoken.add.success'));
            } else {
                $this->get('session')->getFlashBag()->add('error', $this->trans('idp.apitoken.add.error'));
            }
        }

        return $this->redirect($this->generateUrl('app_idp_idpedit', array('id' => $idp->getId())).'#apiTokens');
    }

    /**
     * @Route("/apitokendelete/{id}", name="idp_apitoken_delete")
     * @Method("GET")
     **/
    public function apiTokenDelete(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $apiToken = $em->getRepository(ApiToken::class)->find($id);
        $idp = $apiToken->getIdp();
        $idp->validateAccess($this->getUser());
        $em->remove($apiToken);
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', $this->trans('idp.apitoken.delete.success'));

        return $this->redirect($this->generateUrl('app_idp_idpedit', array('id' => $idp->getId())).'#apiTokens');
    }

/**
     * Translate from "idp" domain
     * @param $id
     * @param array $placeholders
     * @return string
     */
    private function trans($id, $placeholders = array())
    {
        /** @var DataCollectorTranslator $translator */
        $translator = $this->get('translator');
        return $translator->trans($id, $placeholders, 'idp');
    }
}
