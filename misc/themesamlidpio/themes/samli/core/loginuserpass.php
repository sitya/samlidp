<?php
/**
 * Do not allow to frame simpleSAMLphp pages from another location.
 * This prevents clickjacking attacks in modern browsers.
 *
 * If you don't want any framing at all you can even change this to
 * 'DENY', or comment it out if you actually want to allow foreign
 * sites to put simpleSAMLphp in a frame. The latter is however
 * probably not a good security practice.
 */
use Doctrine\Common\Annotations\AnnotationRegistry;

require_once('/app/vendor/simplesamlphp/simplesamlphp/www/_include.php');

$loader = require '/app/vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$kernel = new AppKernel('prod', true);
$kernel->boot();
$container = $kernel->getContainer();
$sspgetter = $container->get('appbundle.sspgetter');
$idp = $sspgetter->getLoginPageData($_SERVER['HTTP_HOST']);
$samlidphostname = $sspgetter->getSamlidpHostname();

header('X-Frame-Options: SAMEORIGIN');
?>
<!DOCTYPE html>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="HandheldFriendly" content="true" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<meta name="robots" content="noindex, nofollow" />
	<title>Login page for <?php echo $idp['OrganizationName']; ?></title>

	<link rel="stylesheet" type="text/css" href="https://<?php echo $samlidphostname; ?>/assetic/bootstrap_css.css" />
	<link rel="stylesheet" type="text/css" href="https://<?php echo $samlidphostname; ?>/assetic/fontawesome_css.css" />
    <link rel="stylesheet" type="text/css" href="https://<?php echo $_SERVER['HTTP_HOST'] ?>/module.php/themesamli/style.css" />
	
  	
	<script type="text/javascript">
	function initiate(){
		document.getElementById('username').focus();
	}
	</script>
	
</head>

<body class="white-bg" onload="initiate()">	
<div class="middle-box text-center loginscreen animated fadeInDown">
    <div>

            <h3><img src="<?php echo $idp['Logo']['url']; ?>" style="max-height: 200px;max-width: 200px;" /></h3>
            <p>Login page of </p>
        <h2><?php echo $idp['OrganizationName']; ?></h2>
        
        <?php if ($idp['status'] != 'banned') : ?>
        <form class="m-t" role="form" action="?" name="f" method="post">
        	<?php if ($this->data['errorcode'] !== null) : ?>
			<div class="alert alert-danger"><?php echo $this->t('{errors:descr_'.$this->data['errorcode'].'}');?></div> 
			<?php endif; ?>
            <div class="form-group">
                <input type="text" name="username" id="username" class="form-control" placeholder="username or email address" required="true" value="<?php echo htmlspecialchars($this->data['username']); ?>">
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" class="form-control" placeholder="password" required="">
            </div>
            <button id="submit" type="submit" class="btn btn-primary block full-width m-b">Login</button>

            <a href="https://<?php echo $samlidphostname; ?>/IdPUserSelfService/resetting/request/<?php echo $idp['hostname'];?>"><small>Forgot password?</small></a>
			<?php foreach ($this->data['stateparams'] as $name => $value) {
                echo '<input type="hidden" name="'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" />';
} ?>
        </form>
        <?php else : ?>
            <div class="row p-lg">
                <div class="panel panel-danger">
                    <div class="panel-heading">
                         Attention
                    </div>
                    <div class="panel-body">
                        <p>This Identity Provider is suspended because the current subscription has been expired. Contact your administrator at your organization to reenable this Identity Provider.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<hr>
<p class="text-center">This is Identity Provider of <?php echo $idp['OrganizationName']; ?>. <br />Your contact at that organization: <b><?php echo $idp['contact']['name']; ?> (<?php echo str_replace(array('@','.'), array(' [at] ',' [dot] '), $idp['contact']['email']); ?>)</b></p>
</body>
</html>
