<?php $idp = $this->data['idp']; ?>
<hr>
<p class="text-center">This is Identity Provider of <?php echo $idp['OrganizationName']; ?>. <br />Your contact at that organization: <b><?php echo $idp['contact']['name']; ?> (<?php echo str_replace(array('@','.'), array(' [at] ',' [dot] '), $idp['contact']['email']); ?>)</b></p>
</div>

</body>
</html>
