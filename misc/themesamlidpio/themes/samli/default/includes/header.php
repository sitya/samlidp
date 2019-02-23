<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

require_once('/app/vendor/simplesamlphp/simplesamlphp/www/_include.php');

$loader = require '/app/vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$kernel = new AppKernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$sspgetter = $container->get('appbundle.sspgetter');
$idp = $sspgetter->getLoginPageData($_SERVER['HTTP_HOST']);
$samlidphostname = $sspgetter->getSamlidpHostname();

$this->data['idp'] = $idp;

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
	<link rel="stylesheet" type="text/css" href="https://<?php echo $_SERVER['HTTP_HOST']; ?>/module.php/themesamli/style.css" />
  	
</head>

<body class="white-bg">
<?php if ($this->data['header'] == 'Select your identity provider') : ?>
    <div class="row">
        <h1 class="text-center"><?php echo $samlidphostname; ?> - Attribute Release Checking</h1>
        <p class="text-center">This is a Service Provider for testing if your Identity Provider works fine.</p>
        <hr />
    </div>
<? else : ?>
<div class="navbar-wrapper">
        <nav class="navbar navbar-default" role="navigation">
            <div class="container">
                <div class="navbar-header page-scroll">
                    <div class="navbar-header"><img src="<?php echo $idp['Logo']['url']; ?>" height="60" /></div>
                    <p class="navbar-text"><?php echo $idp['OrganizationName']; ?></p>
                </div>
            </div>
        </nav>
</div>
<?php endif; ?>
<div class="container">

