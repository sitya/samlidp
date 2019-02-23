<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

require_once('/app/vendor/simplesamlphp/simplesamlphp/www/_include.php');

$loader = require '/app/vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$kernel = new AppKernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$sspgetter = $container->get('appbundle.sspgetter');
$samlidphostname = $sspgetter->getSamlidpHostname();

if (!array_key_exists('header', $this->data)) {
    $this->data['header'] = 'selectidp';
}
$this->data['header'] = $this->t($this->data['header']);
$this->data['autofocus'] = 'dropdownlist';
$this->includeAtTemplateBase('includes/header.php');

foreach ($this->data['idplist'] as $idpentry) {
    if (!empty($idpentry['name'])) {
        $this->includeInlineTranslation('idpname_'.$idpentry['entityid'], $idpentry['name']);
    } elseif (!empty($idpentry['OrganizationDisplayName'])) {
        $this->includeInlineTranslation('idpname_'.$idpentry['entityid'], $idpentry['OrganizationDisplayName']);
    }
    if (!empty($idpentry['description'])) {
        $this->includeInlineTranslation('idpdesc_'.$idpentry['entityid'], $idpentry['description']);
    }
}
?>
<div class="col-md-8 col-md-offset-2 text-center">
    <h3><?php echo $this->data['header']; ?></h3>
    <p><?php echo $this->t('selectidp_full'); ?></p>
    <form method="get" action="<?php echo $this->data['urlpattern']; ?>">
    <div class="form-group col-md-8 col-md-offset-2">
        <input type="hidden" name="entityID" value="<?php echo htmlspecialchars($this->data['entityID']); ?>"/>
        <input type="hidden" name="return" value="<?php echo htmlspecialchars($this->data['return']); ?>"/>
        <input type="hidden" name="returnIDParam" value="<?php echo htmlspecialchars($this->data['returnIDParam']); ?>"/>
        <input type="hidden" id="idpentityid" name="idpentityid" value="" />
        <input type="text" class="typeahead form-control col-md-3" autocomplete="off" placeholder="Search for your Identity Provider..." style="width: 80%">
        <input class="btn btn-primary" id="submitter" disabled="disabled" type="submit" value="<?php echo $this->t('select'); ?>" />
        </div>
    </form>
</div>
<script type="text/javascript" src="https://<?php echo $samlidphostname; ?>/assetic/jquery_js.js"></script>
<script type="text/javascript" src="https://<?php echo $samlidphostname; ?>/assetic/typeahead_js.js"></script>
    <script>
        $(document).ready(function(){
            $('.typeahead').typeahead({
                source: [
                <?php
                $GLOBALS['__t'] = $this;
                usort($this->data['idplist'], function ($idpentry1, $idpentry2) {
                    return strcasecmp(
                        $GLOBALS['__t']->t('idpname_'.$idpentry1['entityid']),
                        $GLOBALS['__t']->t('idpname_'.$idpentry2['entityid'])
                    );
                });
                unset($GLOBALS['__t']);
                foreach ($this->data['idplist'] as $idpentry) {
                    echo '{"value":"'.htmlspecialchars($idpentry['entityid']).'","name":"'.htmlspecialchars($this->t('idpname_'.$idpentry['entityid'])).'"},';
                }?>
                ],
                afterSelect: function(element){
                    $('#idpentityid').val(element.value);
                    $('#submitter').removeAttr("disabled");
                }
            });


        });
    </script>
