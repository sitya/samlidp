<?php

namespace AppBundle\Utils;

use AppBundle\Entity\IdPAudit;

class SSPGetter
{
    protected $em;

    public function __construct(\Doctrine\ORM\EntityManager $em, $database_host, $database_name, $database_user, $database_password, $database_driver, $database_port, $samlidp_hostname)
    {
        $this->em = $em;
        $this->database_host = $database_host;
        $this->database_name = $database_name;
        $this->database_user = $database_user;
        $this->database_port = $database_port;
        $this->database_password = $database_password;
        $this->database_type = preg_replace('/pdo_/', '', $database_driver);
        $this->samlidp_hostname = $samlidp_hostname;
    }

    public function getSamlidpHostname()
    {
        return $this->samlidp_hostname;
    }

    public function getLoginPageData($host)
    {
        $idp = $this->em->getRepository('AppBundle:IdP')->findOneByHostname(str_replace('.' . $this->samlidp_hostname, '', $host));
        if ($idp) {
            $result = array();
            foreach ($idp->getOrganizationElements() as $orgElem) {
                if ($orgElem->getType() == 'Name') {
                    $result['OrganizationName'] = $orgElem->getValue();
                }
            }
            if (!empty($idp->getLogo())) {
                $result['Logo'] = array(
                        'url' => 'https://'.$this->samlidp_hostname.'/images/idp_logo/'.$idp->getLogo(),
                        'width' => 200,
                        'height' => 200,
                        );
            }
            foreach ($idp->getUsers() as $contact) {
                $result['contact'] = array(
                    'name' => $contact->getGivenName().' '.$contact->getSn(),
                    'email' => $contact->getEmail(),
                );
            }
            $result['status'] = $idp->getStatus();
            $result['hostname'] = $idp->getHostname();

            return $result;
        }
    }

    public function getSaml20spremoteForAnIdp($host)
    {
        // Itt állítjuk össze az adott IdP-hez tartozó saml20-sp-remote.php listát.
        $idp = $this->em->getRepository('AppBundle:IdP')->findOneByHostname(str_replace('.' . $this->samlidp_hostname, '', $host));
        if (!$idp) {
            // TODO: throw correct exception!
            exit;
        }
        $metadata = array();

        // Itt szedjük ki a föderációs SP-ket, melyeket az IdP szeret
        $federations = $idp->getFederations();
        foreach ($federations as $federation) {
            $entities = $federation->getEntities();
            foreach ($entities as $entity) {
                $entityData = unserialize(stream_get_contents($entity->getEntitydata()));
                $metadata[$entity->getEntityid()] = $entityData;
            }
        }

        // Itt szedjük ki a föderáción kívüli SP-ket, melyeket az IdP szeret
        $entities = $idp->getEntities();
        foreach ($entities as $entity) {
            if (!isset($metadata[$entity->getEntityid()])) {
                $entityData = unserialize(stream_get_contents($entity->getEntitydata()));
                $metadata[$entity->getEntityid()] = $entityData;
            }
        }
        return $metadata;
    }

    public function getIdps($host)
    {
        $idps = $this->em->getRepository('AppBundle:IdP')->findByHostname(str_replace('.' . $this->samlidp_hostname, '', $host));
        if (count($idps) == 0) {
            // szándékos fallback az összes IdP listázására, ha valahol nem direktben hívják meg
            $idps = $this->em->getRepository('AppBundle:IdP')->findAll();
        }
        $result = array();
        foreach ($idps as $idp) {
            if (strlen($idp->getInstituteName())>1) {
                $handle = fopen('/app/vendor/simplesamlphp/simplesamlphp/cert/'.$idp->getHostname().'.key', 'w');
                fwrite($handle, $idp->getCertKey());
                fclose($handle);

                $certData = explode("\n", $idp->getCertPem());
                unset($certData[0]);
                unset($certData[count($certData)]);
                $result[$idp->getEntityId($this->samlidp_hostname)] = array(
                    'host' => $idp->getHostname().'.'.$this->samlidp_hostname,
                    'privatekey' => $idp->getHostname().'.key',
                    'scope' => $idp->getScopes(),
                    'certData' => implode("\n", $certData),
                    'auth' => 'as-'.$idp->getHostname(),
                    'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
                    'userid.attribute' => 'username',
                    'attributeencodings' => array(
                      'urn:oid:1.3.6.1.4.1.5923.1.1.1.10' => 'raw',
                    ),
                    'sign.logout' => true,
                    'redirect.sign' => true,
                    'assertion.encryption' => true,
                    'EntityAttributes' => array(
                        'http://macedir.org/entity-category-support' => array('http://refeds.org/category/research-and-scholarship', 'http://www.geant.net/uri/dataprotection-code-of-conduct/v1'),
                    ),

                    'name' => array(
                        'en' => $idp->getInstituteName(),
                    ),
                    'SingleSignOnService' => 'https://'.$idp->getHostname().'.'.$this->samlidp_hostname.'/saml2/idp/SSOService.php',
                    'SingleLogoutService' => 'https://'.$idp->getHostname().'.'.$this->samlidp_hostname.'/saml2/idp/SingleLogoutService.php',
                    'SingleSignOnServiceBinding' => array(
                        'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
                        ),
                    'SingleLogoutServiceBinding' => array(
                        'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
                        )

                );
                foreach ($idp->getUsers() as $contact) {
                    $result[$idp->getEntityId($this->samlidp_hostname)]['contacts'][] = array(
                        'contactType' => 'technical',
                        'surName' => $contact->getSn(),
                        'givenName' => $contact->getGivenName(),
                        'emailAddress' => 'mailto:' . $contact->getEmail(),
                    );
                }

                if (!empty($idp->getLogo())) {
                    $result[$idp->getEntityId($this->samlidp_hostname)]['UIInfo']['Logo'] = array(
                        array(
                            'url' => 'https://'. $this->getSamlidpHostname().'/images/idp_logo/'.$idp->getLogo(),
                            'width' => 200,
                            'height' => 200,
                            ),
                        );
                }
                $o_elements = array();
                foreach ($idp->getOrganizationElements() as $orgElem) {
                    if ($orgElem->getType() == 'Name') {
                        $result[$idp->getEntityId($this->samlidp_hostname)]['OrganizationName'][$orgElem->getLang()] = $orgElem->getValue();
                        $result[$idp->getEntityId($this->samlidp_hostname)]['UIInfo']['DisplayName'][$orgElem->getLang()] = $orgElem->getValue();
                        $o_elements[] = $orgElem->getValue();
                    }
                    if ($orgElem->getType() == 'InformationUrl') {
                        $result[$idp->getEntityId($this->samlidp_hostname)]['OrganizationURL'][$orgElem->getLang()] = $orgElem->getValue();
                    }
                }

                # authproc dynamic parts
                $result[$idp->getEntityId($this->samlidp_hostname)]['authproc'][16] = array(
                    'class' => 'core:AttributeAdd',
                    'o' => $o_elements
                );
            }
        }

        return $result;
    }

    public function getAuthsources()
    {
        $config = array();
        $config['admin'] = array('core:AdminPassword');
        $config['default-sp'] = array(
                'saml:SP',
                'entityID' => null,
                'idp' => null,
                'discoURL' => null,
                'privatekey' => 'attributes.' . $this->samlidp_hostname . '.key',
                'certificate' => 'attributes.' . $this->samlidp_hostname . '.crt',
                // 'privatekey' => 'attributes_samlidp_io.key',
                // 'certificate' => 'attributes_samlidp_io.crt',
                'attributes' => array(
                    'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
                    'urn:oid:2.16.840.1.113730.3.1.241',
                    'urn:oid:0.9.2342.19200300.100.1.3',
                    'urn:oid:1.3.6.1.4.1.5923.1.1.1.9',
                    'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                    'urn:oid:2.5.4.10',
                    'urn:oid:1.3.6.1.4.1.25178.1.2.9',
                    'urn:oasis:names:tc:SAML:attribute:pairwise-id',
                    'urn:oasis:names:tc:SAML:attribute:subject-id'
                ),
                'name' => array(
                    'en' => 'attribute releasing tester',
                ),
        );

        $host = $_SERVER['HTTP_HOST'];

        if ($host != 'attributes.' . $this->samlidp_hostname) {
            $idp = $this->em->getRepository('AppBundle:IdP')->findOneByHostname(str_replace('.' . $this->samlidp_hostname, '', $host));
            $id_p_id = $idp->getId();
            $config['as-'.$idp->getHostname()] = array(
                'sqlauth:SQL',
                'dsn' => $this->database_type . ':host='.$this->database_host.';port='. $this->database_port. ';dbname='.$this->database_name,
                'username' => $this->database_user,
                'password' => $this->database_password,
                'query' => "SELECT username, email, givenName, surName, display_name, affiliation, (CASE scope.value WHEN '@' THEN domain.domain ELSE CONCAT_WS('.',scope.value, domain.domain) END) AS scope FROM idp_internal_mysql_user, scope, domain WHERE (username = :username OR email = :username) AND password = :password AND idp_internal_mysql_user.scope_id=scope.id AND scope.domain_id=domain.id AND (domain.idp_id=$id_p_id OR domain.idp_id IS NULL);",
                );
        }

        return $config;
    }

    public function addIdpAuditRecord($host, $username, $sp)
    {
        $idp = $this->em->getRepository('AppBundle:IdP')->findOneByHostname(str_replace('.' . $this->samlidp_hostname, '', $host));

        if (preg_match('/@/', $username)) {
            $idpUser = $this->em->getRepository('AppBundle:IdPUser')->findOneByEmail($username);
        } else {
            $idpUser = $this->em->getRepository('AppBundle:IdPUser')->findOneBy(
                array('username' => $username, 'IdP' => $idp)
            );
        }

        $now = new \DateTime();

        $newidpaudit = new IdPAudit($idpUser, $now, $idp, 'none');

        $this->em->persist($newidpaudit);
        $this->em->flush();
    }
}
