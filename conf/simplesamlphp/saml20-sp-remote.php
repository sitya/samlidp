<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

require_once('/app/vendor/simplesamlphp/simplesamlphp/www/_include.php');

$loader = require '/app/vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$kernel = new AppKernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$sspgetter = $container->get('appbundle.sspgetter');

$metadata = $sspgetter->getSaml20spremoteForAnIdp($_SERVER['HTTP_HOST']);

$metadata['https://attributes.'.$sspgetter->getSamlidpHostname().'/module.php/saml/sp/metadata.php/default-sp'] = array(
  'SingleLogoutService' => array(
    0 => array(
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://attributes.'.$sspgetter->getSamlidpHostname().'/module.php/saml/sp/saml2-logout.php/default-sp',
    ),
  ),
  'AssertionConsumerService' => array(
    0 => array(
      'index' => 0,
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://attributes.'.$sspgetter->getSamlidpHostname().'/module.php/saml/sp/saml2-acs.php/default-sp',
    ),
  ),
  'attributes' => array(
        'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
        'urn:oid:2.16.840.1.113730.3.1.241',
        'urn:oid:0.9.2342.19200300.100.1.3',
        'urn:oid:1.3.6.1.4.1.5923.1.1.1.9',
        'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
        'urn:oid:1.3.6.1.4.1.25178.1.2.9',
        'urn:oid:2.5.4.10'
  ),
  'name' => array(
      'en' => $sspgetter->getSamlidpHostname().' - attribute releasing tester',
  ),
  'certificate' => 'attributes.' . $sspgetter->getSamlidpHostname() . '.crt'
);
