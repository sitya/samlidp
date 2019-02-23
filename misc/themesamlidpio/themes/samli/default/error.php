<?php
header('X-Frame-Options: SAMEORIGIN');

$this->includeAtTemplateBase('includes/header.php');

?>
<div id="content">
	<div class="item">
		<h1 class="text-danger"><?php echo $this->t($this->data['dictTitle']); ?></h1>
		<p><?php echo htmlspecialchars($this->t($this->data['dictDescr'], $this->data['parameters']));?></p>
		<p>

            <?php echo $this->t('report_trackid'); ?>
            <?php echo $this->data['error']['trackId']; ?>
		</p>		
	</div>	
</div>

<?php  $this->includeAtTemplateBase('includes/footer.php');

