<?php
use Doctrine\Common\Annotations\AnnotationRegistry;

require_once '/app/vendor/simplesamlphp/simplesamlphp/lib/_autoload.php';
require_once '/app/vendor/simplesamlphp/simplesamlphp/www/_include.php';

$loader = require '/app/vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$kernel = new AppKernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$sspgetter = $container->get('appbundle.sspgetter');
$idp = $sspgetter->getLoginPageData($_SERVER['HTTP_HOST']);
$samlidphostname = $sspgetter->getSamlidpHostname();


$as = new SimpleSAML\Auth\Simple('default-sp');
$as->requireAuth();

$attributes = attributeMapOid2Name($as->getAttributes());

function attributeMapOid2Name($attributes)
{
    $attributemap = array(
            'urn:oid:0.9.2342.19200300.100.1.3' => 'mail',
            'urn:oid:1.3.6.1.4.1.5923.1.1.1.10' => 'eduPersonTargetedID',
            'urn:oid:1.3.6.1.4.1.5923.1.1.1.6' => 'eduPersonPrincipalName',
            'urn:oid:1.3.6.1.4.1.5923.1.1.1.9' => 'eduPersonScopedAffiliation',
            'urn:oid:2.16.840.1.113730.3.1.241' => 'displayName',
            'urn:oid:2.5.4.4' => 'sn',
            'urn:oid:2.5.4.42' => 'givenName',
            'urn:oid:1.3.6.1.4.1.25178.1.2.9'    => 'schacHomeOrganization',
            'urn:oid:2.5.4.10'                   => 'o',
            'urn:oasis:names:tc:SAML:attribute:pairwise-id' => 'pairwise-id',
            'urn:oasis:names:tc:SAML:attribute:subject-id' => 'subject-id'
        );
    $ret = array();
    foreach ($attributes as $oid => $value) {
        if (isset($attributemap[$oid])) {
            $ret[$attributemap[$oid]] = $attributes[$oid];
        } else {
            $ret[$oid] = $attributes[$oid];
        }
    }

    return $ret;
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="HandheldFriendly" content="true" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<meta name="robots" content="noindex, nofollow" />
	<title>Attribute Release Checking</title>

	<link rel="stylesheet" type="text/css" href="https://<?php echo $samlidphostname; ?>/assetic/bootstrap_css.css" />
	<link rel="stylesheet" type="text/css" href="https://<?php echo $samlidphostname; ?>/assetic/fontawesome_css.css" />
	<link rel="stylesheet" type="text/css" href="https://<?php echo $samlidphostname; ?>/bundles/app/css/outer.css" />
  	
</head>
<body>
	<div class="container">
		<div class="row text-center p-lg">
			<h1>Attribute Release Checking</h1>
		</div>
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="navy-line"></div>
                <h1>Result</h1>
            </div>
        </div>
        <div class="row p-lg">
        	<div class="col-md-6 col-md-offset-3">
            	<table class="table">
                     <tbody>
                      	<tr>
                            <td><p>SAML Authentication via your Identity Provider</p></td>
                            <td><p><span class="label label-success pull-right">success</span></p></td>
                        </tr>
                        <?php foreach ($attributes as $key => $value) : ?>
                      	<tr>
                            <td><p>Received <b><?php echo $key; ?></b> attribute</p></td>
                            <td><p><span class="label label-success pull-right">success</span></p></td>
                        </tr>
                    	<?php endforeach; ?>
                    </tbody>
                </table>
                <hr>
                <h3 class="text-center">Great! Everything seems to be fine. </h3>        
            </div>
        </div>
    </div>
</body>
</html>
