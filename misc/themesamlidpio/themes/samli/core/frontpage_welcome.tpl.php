<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

require_once '/app/vendor/simplesamlphp/simplesamlphp/www/_include.php';

$loader = require '/app/vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$kernel = new AppKernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$sspgetter = $container->get('appbundle.sspgetter');
$idp = $sspgetter->getLoginPageData($_SERVER['HTTP_HOST']);
$samlidphostname = $sspgetter->getSamlidpHostname();

$this->data['header'] = 'Identity Provider of '.$idp['OrganizationName'];

if (!isset($idp['hostname'])) {
    header('Location:https://' . $samlidphostname);
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="HandheldFriendly" content="true" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex, nofollow" />
    <title><?php echo $this->data['header'];?> - <?php echo $samlidphostname; ?></title>

    <link rel="stylesheet" type="text/css" href="https://<?php echo $samlidphostname; ?>/assetic/bootstrap_css.css" />
    <link rel="stylesheet" type="text/css" href="https://<?php echo $samlidphostname; ?>/assetic/fontawesome_css.css" />
    <link rel="stylesheet" type="text/css" href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/module.php/themesamli/style.css" />
    
</head>

<body class="white-bg">
<div class="middle-box text-center loginscreen">
    <div>
        
        <?php if ($idp['Logo']['url'] != '') : ?>
        <h3><img src="<?php echo $idp['Logo']['url']; ?>" style="max-height: 200px;max-width: 200px;" /></h3>
    	<?php endif; ?>
        <p>Identity Provider for </p>
        <h2><?php echo $idp['OrganizationName']; ?></h2>
	</div>
</div>

<div class="col-md-6 col-md-offset-3 clearfix">
<hr>
<h3>For administrators</h3>
<ul>
	<li><a href="https://<?php echo $samlidphostname; ?>/login" target="_blank">Login to <?php echo $samlidphostname; ?> as <b>Administrator</b> of this Identity Provider</a></li>
	<li><a href="https://<?php echo $_SERVER['HTTP_HOST']; ?>/saml2/idp/metadata.php" target="_blank">SAML metadata of this Identity Provider</a></li>
</ul>
<h3>For users</h3>
<ul>
	<li><a href="https://<?php echo $_SERVER['HTTP_HOST']; ?>/saml2/idp/SSOService.php?spentityid=https%3A%2F%2Fattributes.<?php echo $samlidphostname; ?>%2Fmodule.php%2Fsaml%2Fsp%2Fmetadata.php%2Fdefault-sp&return=https%3A%2F%2Fattributes.<?php echo $samlidphostname; ?>" target="_blank">Check my released attributes of this Identity Provider</a></li>
	<li><a href="https://<?php echo $samlidphostname; ?>/IdPUserSelfService/resetting/request/<?php echo $idp['hostname'];?>" target="_blank">I am a user of this Identity Provider, and I forgot my password.</a></li>
</ul>
</div>
<div class="clearfix"></div>
