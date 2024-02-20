<?php     $_message = explode('=', $message); ?>
<?php     $__message = explode('|', $_message[1]); ?>	
<?php     foreach ($__message as $___message) { ?>
			<div class="message <?php echo $_message[0]; ?>"><?php echo $___message; ?></div>
<?php     } ?>
